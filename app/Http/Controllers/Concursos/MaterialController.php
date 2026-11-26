<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Material de resumo: a estante (um livro por matéria) e a situação dos pontos de erro
 * ("errei de novo", ainda erro/revisando/dominado). O livro em si fica no LivroController.
 * Cada aluno só vê o próprio.
 */
class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $materiais = Material::where('user_id', $request->user()->id)->orderBy('titulo')->get();

        // Situação calculada pelas questões (última resposta no app ou no TEC + caixa da fila).
        $contagens = MaterialQuestao::with('ultimaTentativa')->whereIn('material_id', $materiais->pluck('id'))
            ->get(['id', 'material_id', 'resultado_tec', 'caixa', 'revisar_em'])
            ->groupBy('material_id')
            ->map(fn ($qs) => $qs->countBy(fn ($q) => $q->estado())->all() + ['hoje' => $qs->filter(fn ($q) => $q->naFilaHoje())->count(), 'total' => $qs->count()]);
        $capitulos = MaterialCapitulo::whereIn('material_id', $materiais->pluck('id'))
            ->whereNull('parent_id')->selectRaw('material_id, count(*) as n')->groupBy('material_id')->pluck('n', 'material_id');

        // "Para revisar hoje": matérias com questões vencidas na fila.
        $fila = $materiais->filter(fn ($m) => ($contagens[$m->id]['hoje'] ?? 0) > 0)
            ->sortByDesc(fn ($m) => $contagens[$m->id]['hoje'])->values();

        return view('concursos.material.index', compact('materiais', 'contagens', 'capitulos', 'fila'));
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate(['titulo' => ['required', 'string', 'max:160']]);
        $base = Str::slug($dados['titulo']) ?: 'material';
        $slug = $base;
        for ($i = 2; Material::where('user_id', $request->user()->id)->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        $material = Material::create(['user_id' => $request->user()->id, 'slug' => $slug, 'titulo' => $dados['titulo']]);

        return redirect()->route('concursos.material.show', $material)->with('status', 'Livro criado. Comece pelo primeiro capítulo.');
    }

    /** "Errei de novo": conta o erro e devolve o ponto para "ainda erro". */
    public function errei(Request $request, MaterialPonto $ponto): JsonResponse|RedirectResponse
    {
        $material = $this->doAluno($request, $ponto);
        $ponto->update(['vezes_errado' => $ponto->vezes_errado + 1, 'status' => 'errando', 'ultimo_erro_em' => today()]);

        return $this->responder($request, $material, $ponto);
    }

    public function status(Request $request, MaterialPonto $ponto): JsonResponse|RedirectResponse
    {
        $material = $this->doAluno($request, $ponto);
        $dados = $request->validate(['status' => ['required', Rule::in(array_keys(MaterialPonto::STATUS))]]);
        $ponto->update($dados);

        return $this->responder($request, $material, $ponto);
    }

    private function responder(Request $request, Material $material, MaterialPonto $ponto): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => $ponto->status,
                'vezes_errado' => $ponto->vezes_errado,
                'ultimo_erro_em' => $ponto->ultimo_erro_em?->format('d/m/Y'),
            ]);
        }

        return redirect()->to(self::urlDoPonto($ponto));
    }

    /** Os pontos vivem na área de Questões: as questões ligadas ao ponto, para refazer. */
    public static function urlDoPonto(MaterialPonto $ponto): string
    {
        return route('concursos.material.refazer', [$ponto->materialDono(), 'ponto' => $ponto->id, 'situacao' => 'todas', 'iniciar' => 1]);
    }

    private function doAluno(Request $request, MaterialPonto $ponto): Material
    {
        $material = $ponto->capitulo || $ponto->secao ? $ponto->materialDono() : null;
        abort_unless($material && $material->user_id === $request->user()->id, 404);

        return $material;
    }
}
