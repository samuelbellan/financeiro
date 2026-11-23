<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialSecao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Material de resumo: documento de revisão de cada aluno, montado a partir dos cadernos de erros
 * e enriquecido na tela (novo ponto, "errei de novo", dominado). Cada aluno só vê o próprio.
 */
class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $materiais = Material::where('user_id', $request->user()->id)->orderBy('titulo')->get();
        $contagens = MaterialPonto::query()
            ->join('material_secoes', 'material_secoes.id', '=', 'material_pontos.secao_id')
            ->whereIn('material_secoes.material_id', $materiais->pluck('id'))
            ->selectRaw('material_secoes.material_id, material_pontos.status, count(*) as n')
            ->groupBy('material_secoes.material_id', 'material_pontos.status')
            ->get()
            ->groupBy('material_id')
            ->map(fn ($linhas) => $linhas->pluck('n', 'status')->map(fn ($n) => (int) $n));

        return view('concursos.material.index', compact('materiais', 'contagens'));
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

        return redirect()->route('concursos.material.show', $material)->with('status', 'Material criado. Comece por uma seção.');
    }

    public function show(Request $request, Material $material): View
    {
        $this->doAluno($request, $material);
        $material->load('secoes.pontos');

        $todos = $material->secoes->flatMap->pontos;
        $resumo = [
            'total' => $todos->count(),
            'errando' => $todos->where('status', 'errando')->count(),
            'revisando' => $todos->where('status', 'revisando')->count(),
            'dominado' => $todos->where('status', 'dominado')->count(),
        ];

        return view('concursos.material.show', compact('material', 'resumo'));
    }

    public function storeSecao(Request $request, Material $material): RedirectResponse
    {
        $this->doAluno($request, $material);
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'origem' => ['nullable', 'string', 'max:200'],
        ]);
        $secao = $material->secoes()->create($dados + [
            'chave' => 's' . now()->format('ymdHis') . Str::lower(Str::random(3)),
            'ordem' => ($material->secoes()->max('ordem') ?? 0) + 1,
        ]);

        return redirect()->to(route('concursos.material.show', $material) . '#secao-' . $secao->id);
    }

    public function storePonto(Request $request, MaterialSecao $secao): RedirectResponse
    {
        $this->doAluno($request, $secao->material);
        $dados = $this->validarPonto($request);
        $ponto = $secao->pontos()->create($dados + [
            'origem' => 'manual',
            'status' => 'errando',
            'vezes_errado' => 1,
            'ultimo_erro_em' => today(),
            'ordem' => ($secao->pontos()->max('ordem') ?? 0) + 1,
        ]);

        return redirect()->to(route('concursos.material.show', $secao->material) . '#ponto-' . $ponto->id)->with('status', 'Ponto adicionado.');
    }

    public function updatePonto(Request $request, MaterialPonto $ponto): RedirectResponse
    {
        $material = $ponto->secao->material;
        $this->doAluno($request, $material);
        $ponto->update($this->validarPonto($request));

        return redirect()->to(route('concursos.material.show', $material) . '#ponto-' . $ponto->id)->with('status', 'Ponto atualizado.');
    }

    /** "Errei de novo": conta o erro e devolve o ponto para "ainda erro". */
    public function errei(Request $request, MaterialPonto $ponto): JsonResponse|RedirectResponse
    {
        $material = $ponto->secao->material;
        $this->doAluno($request, $material);
        $ponto->update(['vezes_errado' => $ponto->vezes_errado + 1, 'status' => 'errando', 'ultimo_erro_em' => today()]);

        return $this->responder($request, $material, $ponto);
    }

    public function status(Request $request, MaterialPonto $ponto): JsonResponse|RedirectResponse
    {
        $material = $ponto->secao->material;
        $this->doAluno($request, $material);
        $dados = $request->validate(['status' => ['required', Rule::in(array_keys(MaterialPonto::STATUS))]]);
        $ponto->update($dados);

        return $this->responder($request, $material, $ponto);
    }

    public function destroyPonto(Request $request, MaterialPonto $ponto): RedirectResponse
    {
        $material = $ponto->secao->material;
        $this->doAluno($request, $material);
        $ponto->delete();

        return redirect()->route('concursos.material.show', $material)->with('status', 'Ponto apagado.');
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

        return redirect()->to(route('concursos.material.show', $material) . '#ponto-' . $ponto->id);
    }

    private function validarPonto(Request $request): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'armadilha' => ['nullable', 'string', 'max:5000'],
            'regra' => ['required', 'string', 'max:8000'],
            'macete' => ['nullable', 'string', 'max:3000'],
            'base_legal' => ['nullable', 'string', 'max:2000'],
            'questoes' => ['nullable', 'string', 'max:500'],
        ]);
        preg_match_all('/\d{4,}/', $dados['questoes'] ?? '', $ids);
        $dados['questoes'] = array_values(array_unique($ids[0])) ?: null;

        return $dados;
    }

    private function doAluno(Request $request, Material $material): void
    {
        abort_unless($material->user_id === $request->user()->id, 404);
    }
}
