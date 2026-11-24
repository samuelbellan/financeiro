<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
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
        $contagens = Material::pontosDe($materiais->pluck('id')->all())->with(['capitulo:id,material_id', 'secao:id,material_id'])->get(['id', 'capitulo_id', 'secao_id', 'status'])
            ->groupBy(fn ($p) => Material::materialDoPonto($p))
            ->map(fn ($ps) => $ps->countBy('status'));
        $capitulos = MaterialCapitulo::whereIn('material_id', $materiais->pluck('id'))
            ->whereNull('parent_id')->selectRaw('material_id, count(*) as n')->groupBy('material_id')->pluck('n', 'material_id');

        // Fila "para revisar agora": capítulos de todos os livros com pontos ainda em erro ou em revisão.
        $fila = MaterialCapitulo::whereIn('material_id', $materiais->pluck('id'))
            ->withCount([
                'pontos as errando' => fn ($q) => $q->where('status', 'errando'),
                'pontos as revisando' => fn ($q) => $q->where('status', 'revisando'),
            ])
            ->get(['id', 'material_id', 'titulo'])
            ->filter(fn ($c) => $c->errando + $c->revisando > 0)
            ->sortBy([['errando', 'desc'], ['revisando', 'desc']])
            ->take(6)
            ->values();

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

    /** Onde o ponto aparece no livro (capítulo) ou, se ainda não convertido, a capa do livro. */
    public static function urlDoPonto(MaterialPonto $ponto): string
    {
        $material = $ponto->materialDono();

        return $ponto->capitulo_id
            ? route('concursos.material.capitulo', [$material, $ponto->capitulo_id]) . '#ponto-' . $ponto->id
            : route('concursos.material.show', $material);
    }

    private function doAluno(Request $request, MaterialPonto $ponto): Material
    {
        $material = $ponto->capitulo || $ponto->secao ? $ponto->materialDono() : null;
        abort_unless($material && $material->user_id === $request->user()->id, 404);

        return $material;
    }
}
