<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Services\Concursos\Leis\AcervoLeis;
use App\Services\Concursos\Leis\Citacoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

/**
 * Lei seca a partir do acervo ctresb/leis: página da lei com âncora por artigo, artigo avulso em
 * JSON (para o material abrir o texto sem sair da página) e, ao lado de cada artigo, os pontos do
 * material do aluno que o citam.
 */
class LeisController extends Controller
{
    public function index(AcervoLeis $acervo): View
    {
        $leis = collect($acervo->catalogo())->map(fn ($l, $sigla) => $l + [
            'chave' => $sigla,
            'baixada' => $acervo->baixada($sigla),
            'fonte' => $acervo->urlFonte($sigla),
        ]);

        return view('concursos.leis.index', compact('leis'));
    }

    public function show(Request $request, string $lei, AcervoLeis $acervo, Citacoes $citacoes): View
    {
        abort_unless($acervo->existe($lei), 404);
        try {
            $blocos = $acervo->blocos($lei);
            $meta = $acervo->meta($lei);
        } catch (RuntimeException $e) {
            return view('concursos.leis.erro', ['lei' => $acervo->catalogo()[$lei], 'erro' => $e->getMessage(), 'fonte' => $acervo->urlFonte($lei)]);
        }

        return view('concursos.leis.show', [
            'chave' => $lei,
            'lei' => $acervo->catalogo()[$lei],
            'meta' => $meta,
            'blocos' => $blocos,
            'acervo' => $acervo,
            'fonte' => $acervo->urlFonte($lei),
            'citados' => $this->citados($request->user()->id, $lei, $citacoes),
        ]);
    }

    public function artigo(string $lei, string $artigo, AcervoLeis $acervo): JsonResponse
    {
        abort_unless($acervo->existe($lei), 404);
        try {
            $a = $acervo->artigo($lei, strtoupper($artigo));
        } catch (RuntimeException) {
            return response()->json(['erro' => 'Não consegui baixar a lei agora. Tente de novo mais tarde.'], 503);
        }
        abort_if($a === null, 404);

        return response()->json([
            'lei' => $acervo->catalogo()[$lei]['sigla'],
            'rotulo' => $a['rotulo'],
            'html' => $acervo->mdParaHtml($a['md']),
            'url' => route('concursos.leis.show', $lei) . '#' . $a['ancora'],
        ]);
    }

    /** @return Collection<string, list<array{id: int, titulo: string, status: string, material: Material}>> âncora → pontos */
    private function citados(int $userId, string $lei, Citacoes $citacoes): Collection
    {
        $materiais = Material::where('user_id', $userId)->get()->keyBy('id');
        if ($materiais->isEmpty()) {
            return collect();
        }

        $mapa = [];
        MaterialPonto::query()
            ->join('material_secoes', 'material_secoes.id', '=', 'material_pontos.secao_id')
            ->whereIn('material_secoes.material_id', $materiais->keys())
            ->where(fn ($q) => $q->where('base_legal', 'like', "%[[{$lei} %")->orWhere('regra', 'like', "%[[{$lei} %")->orWhere('armadilha', 'like', "%[[{$lei} %"))
            ->get(['material_pontos.*', 'material_secoes.material_id'])
            ->each(function ($p) use (&$mapa, $lei, $citacoes, $materiais) {
                $refs = array_merge($citacoes->extrair($p->base_legal), $citacoes->extrair($p->regra), $citacoes->extrair($p->armadilha));
                foreach ($refs as $r) {
                    if ($r['lei'] === $lei) {
                        $mapa[$r['ancora']][$p->id] = ['id' => $p->id, 'titulo' => $p->titulo, 'status' => $p->status, 'material' => $materiais[$p->material_id]];
                    }
                }
            });

        return collect($mapa)->map(fn ($p) => array_values($p));
    }
}
