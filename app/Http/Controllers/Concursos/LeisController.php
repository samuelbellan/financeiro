<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Services\Concursos\Leis\AcervoLeis;
use App\Services\Concursos\Leis\Citacoes;
use App\Services\Concursos\Leis\Jurisprudencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            return view('concursos.leis.erro', ['lei' => $acervo->info($lei), 'erro' => $e->getMessage(), 'fonte' => $acervo->urlFonte($lei)]);
        }

        return view('concursos.leis.show', [
            'chave' => $lei,
            'lei' => $acervo->info($lei),
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
            'titulo' => $acervo->info($lei)['sigla'] . ', ' . $a['rotulo'],
            'lei' => $acervo->info($lei)['sigla'],
            'rotulo' => $a['rotulo'],
            'html' => $acervo->mdParaHtml($a['md']),
            'url' => route('concursos.leis.show', $lei) . '#' . $a['ancora'],
        ]);
    }

    /** Súmula, tema ou ação de controle concentrado: página própria ou JSON para o diálogo do material. */
    public function juris(Request $request, string $tipo, string $numero, Jurisprudencia $juris, AcervoLeis $acervo): View|JsonResponse
    {
        abort_unless($juris->existe($tipo), 404);
        try {
            $p = $juris->ler($tipo, $numero);
        } catch (RuntimeException) {
            abort_if($request->expectsJson(), 404, 'Não encontrei esse precedente no acervo.');

            return view('concursos.leis.erro', [
                'lei' => ['nome' => $juris->rotulo($tipo, $numero)],
                'erro' => 'Não encontrei esse precedente no acervo (ou não consegui baixá-lo agora).',
                'fonte' => config('concursos.leis.web') . $juris->caminho($tipo, $numero),
            ]);
        }

        $html = $acervo->mdParaHtml($p['md']);
        if ($request->expectsJson()) {
            return response()->json(['titulo' => $p['rotulo'], 'html' => $html, 'url' => route('concursos.juris.show', [$tipo, $numero])]);
        }

        return view('concursos.leis.documento', [
            'titulo' => $p['titulo'],
            'subtitulo' => Jurisprudencia::TIPOS[$tipo][2],
            'html' => $html,
            'fonte' => config('concursos.leis.web') . $p['path'],
            'origem' => $p['meta']['fonte'] ?? null,
        ]);
    }

    /** Busca rápida: tipo + número → lei (sigla dinâmica) ou precedente. */
    public function buscar(Request $request, AcervoLeis $acervo): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:' . implode(',', array_merge(array_keys(AcervoLeis::TIPOS_FEDERAIS), array_keys(Jurisprudencia::TIPOS)))],
            'numero' => ['required', 'regex:/^\d{1,6}$/'],
        ]);
        $numero = ltrim($dados['numero'], '0') ?: '0';
        if (isset(Jurisprudencia::TIPOS[$dados['tipo']])) {
            return redirect()->route('concursos.juris.show', [$dados['tipo'], $numero]);
        }
        $sigla = $dados['tipo'] . $numero;
        if (!$acervo->existe($sigla)) {
            return back()->withInput()->with('status', $acervo->raizLocal()
                ? 'Não encontrei essa norma no acervo.'
                : 'Para abrir qualquer norma pelo número, configure a cópia local do acervo (CONCURSOS_LEIS_LOCAL).');
        }

        return redirect()->route('concursos.leis.show', $sigla);
    }

    /** Navega pelas pastas da cópia local do repositório (E:\leis) e abre qualquer arquivo. */
    public function acervo(Request $request, AcervoLeis $acervo): View|RedirectResponse
    {
        $raiz = $acervo->raizLocal();
        abort_unless($raiz, 404);
        $rel = trim(str_replace('\\', '/', (string) $request->query('p', '')), '/');
        abort_if(str_contains($rel, '..') || str_contains($rel, ':') || str_contains($rel, "\0"), 404);
        $abs = $raiz . ($rel === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));

        if (is_file($abs)) {
            abort_unless(str_ends_with($rel, '.md'), 404);
            if ($sigla = AcervoLeis::siglaDoCaminho($rel)) {
                return redirect()->route('concursos.leis.show', $sigla);
            }
            $md = $acervo->lerCaminho($rel);
            $analise = AcervoLeis::analisar($md);
            $corpo = preg_replace('/\A---\r?\n.*?\r?\n---\r?\n/s', '', $md);

            return view('concursos.leis.documento', [
                'titulo' => $analise['meta']['titulo'] ?? basename($rel, '.md'),
                'subtitulo' => $rel,
                'html' => $acervo->mdParaHtml($corpo),
                'fonte' => config('concursos.leis.web') . $rel,
                'origem' => $analise['meta']['fonte'] ?? null,
            ]);
        }
        abort_unless(is_dir($abs), 404);

        $pastas = $arquivos = [];
        foreach (new \DirectoryIterator($abs) as $f) {
            $nome = $f->getFilename();
            if ($f->isDot() || str_starts_with($nome, '.') || str_starts_with($nome, '_')) {
                continue;
            }
            if ($f->isDir()) {
                $pastas[] = $nome;
            } elseif (str_ends_with($nome, '.md')) {
                $arquivos[] = $nome;
            }
        }
        natsort($pastas);
        natsort($arquivos);

        return view('concursos.leis.acervo', [
            'rel' => $rel,
            'pastas' => array_values($pastas),
            'arquivos' => array_values($arquivos),
        ]);
    }

    /** @return Collection<string, list<array{id: int, titulo: string, status: string, material: Material}>> âncora → pontos */
    /**
     * Onde o livro do aluno cita cada artigo desta lei: o ponto de erro em volta da citação (ou o
     * capítulo, se ela está no texto corrido) e, nos materiais ainda não convertidos, o ponto da seção.
     */
    private function citados(int $userId, string $lei, Citacoes $citacoes): Collection
    {
        $materiais = Material::where('user_id', $userId)->get()->keyBy('id');
        if ($materiais->isEmpty()) {
            return collect();
        }

        $mapa = [];
        $capitulos = MaterialCapitulo::whereIn('material_id', $materiais->keys())->where('texto', 'like', "% {$lei} %")->get();
        $pontos = MaterialPonto::whereIn('capitulo_id', $capitulos->pluck('id'))->get()->keyBy('id');
        foreach ($capitulos as $cap) {
            $url = route('concursos.material.capitulo', [$cap->material_id, $cap->id]);
            $andar = function (array $nos, ?array $ponto) use (&$andar, &$mapa, $lei, $citacoes, $cap, $url, $pontos) {
                foreach ($nos as $no) {
                    $tipo = $no['type'] ?? null;
                    if ($tipo === 'pontoErro') {
                        $p = $pontos->get($no['attrs']['pontoId'] ?? 0);
                        $andar($no['content'] ?? [], ['chave' => 'p' . ($p?->id ?? 0), 'titulo' => $no['attrs']['titulo'] ?? $cap->titulo, 'status' => $p?->status, 'url' => $url . '#ponto-' . ($p?->id ?? 0)]);
                        continue;
                    }
                    if ($tipo === 'lei') {
                        foreach ($citacoes->extrair('[[' . ($no['attrs']['ref'] ?? '') . ']]') as $r) {
                            if ($r['lei'] === $lei) {
                                $onde = $ponto ?? ['chave' => 'c' . $cap->id, 'titulo' => $cap->titulo, 'status' => null, 'url' => $url];
                                $mapa[$r['ancora']][$onde['chave']] = $onde;
                            }
                        }
                    }
                    $andar($no['content'] ?? [], $ponto);
                }
            };
            $andar($cap->conteudo['content'] ?? [], null);
        }

        MaterialPonto::query()
            ->join('material_secoes', 'material_secoes.id', '=', 'material_pontos.secao_id')
            ->whereNull('material_pontos.capitulo_id')
            ->whereIn('material_secoes.material_id', $materiais->keys())
            ->where(fn ($q) => $q->where('base_legal', 'like', "%[[{$lei} %")->orWhere('regra', 'like', "%[[{$lei} %")->orWhere('armadilha', 'like', "%[[{$lei} %"))
            ->get(['material_pontos.*', 'material_secoes.material_id'])
            ->each(function ($p) use (&$mapa, $lei, $citacoes) {
                $refs = array_merge($citacoes->extrair($p->base_legal), $citacoes->extrair($p->regra), $citacoes->extrair($p->armadilha));
                foreach ($refs as $r) {
                    if ($r['lei'] === $lei) {
                        $mapa[$r['ancora']]['p' . $p->id] = ['titulo' => $p->titulo, 'status' => $p->status, 'url' => route('concursos.material.show', $p->material_id)];
                    }
                }
            });

        return collect($mapa)->map(fn ($p) => array_values($p));
    }
}
