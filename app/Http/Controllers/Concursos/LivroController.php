<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnexo;
use App\Models\Concursos\MaterialAnotacao;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialGrifoQuestao;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use App\Services\Concursos\Leis\Citacoes;
use App\Services\Concursos\Livro\ConversorLivro;
use App\Services\Concursos\Livro\Documento;
use App\Services\Concursos\Livro\GrifosQuestoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * O material de resumo como livro: sumário de capítulos (assuntos do TEC + criados à mão), página
 * do capítulo com o editor visual, notas na margem, imagens, busca e impressão do livro inteiro.
 * Cada aluno só vê os próprios livros (404 para os outros).
 */
class LivroController extends Controller
{
    public function __construct(private readonly Documento $documento)
    {
    }

    /** Capa do livro: sumário completo e progresso. Converte na hora o que ainda está só em seções. */
    public function show(Request $request, Material $material, ConversorLivro $conversor): View
    {
        $this->doAluno($request, $material);
        $this->converterSePreciso($material, $conversor);

        return view('concursos.material.livro', $this->base($material) + ['capitulo' => null]);
    }

    public function capitulo(Request $request, Material $material, MaterialCapitulo $capitulo, Citacoes $citacoes, GrifosQuestoes $grifos): View
    {
        $this->doAluno($request, $material);
        abort_unless($capitulo->material_id === $material->id, 404);

        $doc = $this->documento->limpar($capitulo->conteudo);
        $pontos = MaterialPonto::whereIn('id', $this->documento->ids($doc, 'pontoErro', 'pontoId'))->get()->keyBy('id');
        $questoes = MaterialQuestao::with('ultimaTentativa')->where('material_id', $material->id)
            ->where(fn ($q) => $q->where('capitulo_id', $capitulo->id)->orWhereIn('id', $this->documento->ids($doc, 'questao', 'questaoId')))
            ->orderBy('ordem')->orderBy('id')->get()->keyBy('id');
        // Grifos ligados a questões: cor e nota saem da última tentativa (Fase 2).
        [$doc, $anotacoes, $grifosQ] = $grifos->aplicar($capitulo, $doc, $capitulo->anotacoes()->get());
        $refazer = route('concursos.material.refazer', $material);
        $grifosQ = array_map(fn ($g) => $g + ['url' => $refazer . '?' . http_build_query(['ids' => implode(',', $g['ids']), 'situacao' => 'todas', 'iniciar' => 1, 'qtd' => 200])], $grifosQ);

        $base = $this->base($material);
        $plano = $base['plano'];
        $i = $plano->search(fn ($c) => $c['id'] === $capitulo->id);

        return view('concursos.material.livro', $base + [
            'capitulo' => $capitulo,
            'caminho' => $this->caminho($capitulo),
            'pdf' => $capitulo->pdf_anexo_id ? route('concursos.material.anexos.show', $capitulo->pdf_anexo_id) : null,
            'anterior' => $i > 0 ? $plano[$i - 1] : null,
            'proximo' => $i !== false && $i < $plano->count() - 1 ? $plano[$i + 1] : null,
            // Capítulo de teoria: lê-se só a teoria (os pontos ficam na área de Questões e no editor).
            // Capítulo só do diário de erros (assunto do TEC): mostra os pontos, que é tudo o que ele tem.
            'html' => $this->documento->html($doc, $citacoes, ['pontos' => $pontos, 'questoes' => $questoes,
                'soTeoria' => !request()->boolean('todos') && $this->documento->temTeoria($doc)]),
            'dadosEditor' => [
                'doc' => $doc,
                'capituloId' => $capitulo->id,
                'pontos' => $pontos->map(fn ($p) => $this->dadosPonto($p))->all(),
                'questoes' => $questoes->map(fn ($q) => $this->dadosQuestao($q))->all(),
                'doCapitulo' => $questoes->where('capitulo_id', $capitulo->id)->keys()->values()->all(),
                'anotacoes' => $anotacoes->map(fn ($a) => $this->dadosAnotacao($a))->values()->all(),
                'grifosQ' => (object) $grifosQ,
                'caixas' => Documento::CAIXAS,
                'status' => MaterialPonto::STATUS,
                'urls' => [
                    'salvar' => route('concursos.material.capitulos.conteudo', $capitulo),
                    'ponto' => route('concursos.material.capitulos.pontos', $capitulo),
                    'anotacao' => route('concursos.material.capitulos.anotacoes', $capitulo),
                    'anexo' => route('concursos.material.anexos.store', $material),
                    'citacao' => route('concursos.material.citacao'),
                    'questoes' => route('concursos.material.questoes', $material),
                    'refazer' => $refazer,
                ],
            ],
        ]);
    }

    public function criar(Request $request, Material $material): JsonResponse|RedirectResponse
    {
        $this->doAluno($request, $material);
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:250'],
            'parent_id' => ['nullable', 'integer', Rule::exists('concursos_pgsql.material_capitulos', 'id')->where('material_id', $material->id)],
        ]);
        $ordem = MaterialCapitulo::where('material_id', $material->id)->where('parent_id', $dados['parent_id'] ?? null)->max('ordem') ?? 0;
        $cap = MaterialCapitulo::create([
            'material_id' => $material->id,
            'parent_id' => $dados['parent_id'] ?? null,
            'titulo' => $dados['titulo'],
            'ordem' => $ordem + 1,
            'conteudo' => Documento::vazio(),
            'texto' => '',
        ]);
        $url = route('concursos.material.capitulo', [$material, $cap]) . '?editar=1';

        return $request->expectsJson() ? response()->json(['id' => $cap->id, 'url' => $url]) : redirect()->to($url);
    }

    public function renomear(Request $request, MaterialCapitulo $capitulo): JsonResponse
    {
        $this->doAluno($request, $capitulo->material);
        $capitulo->update($request->validate(['titulo' => ['required', 'string', 'max:250']]));

        return response()->json(['titulo' => $capitulo->titulo]);
    }

    /** Nova ordem do sumário (arrastar): lista de {id, parent_id, ordem}. */
    public function ordenar(Request $request, Material $material): JsonResponse
    {
        $this->doAluno($request, $material);
        $dados = $request->validate([
            'itens' => ['required', 'array', 'max:2000'],
            'itens.*.id' => ['required', 'integer'],
            'itens.*.parent_id' => ['nullable', 'integer'],
            'itens.*.ordem' => ['required', 'integer', 'min:0', 'max:60000'],
        ]);
        $ids = MaterialCapitulo::where('material_id', $material->id)->pluck('parent_id', 'id');
        $pais = collect($dados['itens'])->pluck('parent_id', 'id');
        foreach ($dados['itens'] as $item) {
            abort_unless($ids->has($item['id']) && ($item['parent_id'] === null || $ids->has($item['parent_id'])), 422, 'Capítulo de outro livro.');
            // Sem ciclos: subindo pelos pais (já com a ordem nova) nunca volta ao próprio capítulo.
            for ($p = $item['parent_id'], $passos = 0; $p !== null; $p = $pais->has($p) ? $pais[$p] : $ids[$p], $passos++) {
                abort_if($p === $item['id'] || $passos > 50, 422, 'Um capítulo não pode ficar dentro dele mesmo.');
            }
        }
        DB::connection('concursos_pgsql')->transaction(function () use ($dados) {
            foreach ($dados['itens'] as $item) {
                MaterialCapitulo::whereKey($item['id'])->update(['parent_id' => $item['parent_id'], 'ordem' => $item['ordem']]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function apagar(Request $request, MaterialCapitulo $capitulo): JsonResponse|RedirectResponse
    {
        $material = $capitulo->material;
        $this->doAluno($request, $material);
        DB::connection('concursos_pgsql')->transaction(function () use ($capitulo) {
            // Pontos e questões dos subcapítulos também saem do livro (as questões continuam no Refazer).
            $ids = [$capitulo->id];
            for ($nivel = [$capitulo->id]; $nivel; ) {
                $nivel = MaterialCapitulo::whereIn('parent_id', $nivel)->pluck('id')->all();
                array_push($ids, ...$nivel);
            }
            MaterialPonto::whereIn('capitulo_id', $ids)->delete();
            $capitulo->delete();
        });
        $url = route('concursos.material.show', $material);

        return $request->expectsJson() ? response()->json(['url' => $url]) : redirect()->to($url)->with('status', 'Capítulo apagado.');
    }

    /**
     * Salvamento automático do editor. Pontos de erro que saíram do texto são apagados; os que
     * apontam para um ponto inexistente (colado de outro capítulo) são recriados aqui.
     */
    public function salvar(Request $request, MaterialCapitulo $capitulo): JsonResponse
    {
        $material = $capitulo->material;
        $this->doAluno($request, $material);
        $request->validate(['conteudo' => ['required', 'array']]);
        abort_if(strlen((string) $request->getContent()) > 4_000_000, 413, 'Capítulo grande demais.');

        $doc = $this->documento->limpar($request->input('conteudo'));

        $resultado = DB::connection('concursos_pgsql')->transaction(function () use ($doc, $capitulo, $material) {
            $noTexto = $this->documento->ids($doc, 'pontoErro', 'pontoId');
            $doMaterial = Material::pontosDe($material->id)->whereIn('id', $noTexto)->get()->keyBy('id');
            $titulos = $this->titulosDosPontos($doc);

            $mapa = [];
            foreach ($noTexto as $id) {
                $p = $doMaterial->get($id);
                if (!$p) {
                    $p = $this->novoPonto($capitulo, $titulos[$id] ?? 'Ponto que eu erro');
                    $mapa[$id] = $p->id;
                } else {
                    $p->fill(['capitulo_id' => $capitulo->id, 'titulo' => $titulos[$id] ?? $p->titulo])->save();
                }
            }
            if ($mapa) {
                $doc = $this->documento->trocarIds($doc, 'pontoErro', 'pontoId', $mapa);
            }
            $ficam = $this->documento->ids($doc, 'pontoErro', 'pontoId');
            MaterialPonto::where('capitulo_id', $capitulo->id)->whereNotIn('id', $ficam ?: [0])->delete();

            // Notas cujo grifo sumiu do texto saem junto.
            $marcas = $this->marcas($doc);
            $capitulo->anotacoes()->whereNotIn('marca', $marcas ?: [''])->delete();
            MaterialGrifoQuestao::where('capitulo_id', $capitulo->id)->whereNotIn('marca', $marcas ?: [''])->delete();

            $capitulo->update(['conteudo' => $doc, 'texto' => $this->documento->texto($doc)]);

            return ['mapa' => $mapa];
        });

        return response()->json([
            'ok' => true,
            'mapa' => (object) $resultado['mapa'],
            'pontos' => (object) MaterialPonto::where('capitulo_id', $capitulo->id)->get()->keyBy('id')->map(fn ($p) => $this->dadosPonto($p))->all(),
            'salvo_em' => now()->format('H:i'),
        ]);
    }

    /** Ponto de erro novo, inserido no texto pelo editor. */
    public function novoPontoNoTexto(Request $request, MaterialCapitulo $capitulo): JsonResponse
    {
        $this->doAluno($request, $capitulo->material);
        $dados = $request->validate(['titulo' => ['nullable', 'string', 'max:200']]);
        $p = $this->novoPonto($capitulo, $dados['titulo'] ?? null ?: 'Ponto que eu erro');

        return response()->json($this->dadosPonto($p));
    }

    public function anotar(Request $request, MaterialCapitulo $capitulo): JsonResponse
    {
        $this->doAluno($request, $capitulo->material);
        $dados = $request->validate([
            'marca' => ['required', 'string', 'regex:/^[a-z0-9-]{1,40}$/'],
            'cor' => ['nullable', Rule::in(MaterialAnotacao::CORES)],
            'trecho' => ['nullable', 'string', 'max:2000'],
            'nota' => ['nullable', 'string', 'max:5000'],
        ]);
        if (trim($dados['nota'] ?? '') === '') {
            $capitulo->anotacoes()->where('marca', $dados['marca'])->delete();

            return response()->json(['apagada' => true]);
        }
        $a = MaterialAnotacao::updateOrCreate(
            ['capitulo_id' => $capitulo->id, 'marca' => $dados['marca']],
            ['cor' => $dados['cor'] ?? 'amarelo', 'trecho' => $dados['trecho'] ?? null, 'nota' => $dados['nota']],
        );

        return response()->json($this->dadosAnotacao($a));
    }

    public function enviarAnexo(Request $request, Material $material): JsonResponse
    {
        $this->doAluno($request, $material);
        $request->validate(['arquivo' => ['required', 'file', 'max:8192', 'mimetypes:image/png,image/jpeg,image/gif,image/webp']]);
        $arq = $request->file('arquivo');
        $caminho = $arq->store('concursos/anexos/' . $request->user()->id, 'local');
        $anexo = MaterialAnexo::create([
            'material_id' => $material->id,
            'caminho' => $caminho,
            'nome' => mb_substr($arq->getClientOriginalName(), 0, 200),
            'mime' => $arq->getMimeType(),
            'tamanho' => $arq->getSize(),
        ]);

        return response()->json(['src' => route('concursos.material.anexos.show', $anexo, false)]);
    }

    public function verAnexo(Request $request, MaterialAnexo $anexo): StreamedResponse
    {
        $this->doAluno($request, $anexo->material);
        abort_unless(Storage::disk('local')->exists($anexo->caminho), 404);

        return Storage::disk('local')->response($anexo->caminho, null, [
            'Content-Type' => $anexo->mime,
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Link de uma citação ("ctn 98") para o nó de lei do editor. */
    public function citacao(Request $request, Citacoes $citacoes): JsonResponse
    {
        $ref = (string) $request->validate(['ref' => ['required', 'string', 'max:80']])['ref'];
        $link = $citacoes->link($ref);

        return $link ? response()->json($link) : response()->json(['erro' => 'Citação não reconhecida.'], 404);
    }

    /** Questões do livro para inserir no texto: por capítulo, nº do TEC ou trecho do enunciado. */
    public function questoes(Request $request, Material $material): JsonResponse
    {
        $this->doAluno($request, $material);
        $f = $request->validate(['capitulo' => ['nullable', 'integer'], 'q' => ['nullable', 'string', 'max:100'], 'ids' => ['nullable', 'string', 'max:2000']]);

        $qs = MaterialQuestao::with('ultimaTentativa')->where('material_id', $material->id)
            ->when($f['ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', array_map('intval', explode(',', $ids))))
            ->when($f['capitulo'] ?? null, fn ($q, $c) => $q->where('capitulo_id', $c))
            ->when(trim($f['q'] ?? ''), function ($q, $t) {
                $q->where(fn ($q) => $q->where('id_externo', $t)->orWhere('enunciado', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $t) . '%'));
            })
            ->orderBy('ordem')->orderBy('id')->limit(40)->get();

        return response()->json($qs->map(fn ($q) => $this->dadosQuestao($q))->values());
    }

    /** Busca no livro inteiro: capítulos cujo título ou texto tem todas as palavras. */
    public function buscar(Request $request, Material $material): JsonResponse
    {
        $this->doAluno($request, $material);
        $termo = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']])['q']);
        $sem = fn (string $s) => Str::lower(Str::ascii($s));
        $palavras = array_filter(preg_split('/\s+/u', $sem($termo)));

        $achados = $material->capitulos()->get(['id', 'titulo', 'texto'])->filter(function ($c) use ($palavras, $sem) {
            $alvo = $sem($c->titulo . "\n" . $c->texto);
            foreach ($palavras as $p) {
                if (!str_contains($alvo, $p)) {
                    return false;
                }
            }

            return true;
        })->take(30)->map(function ($c) use ($palavras, $sem, $material) {
            $texto = (string) $c->texto;
            $pos = mb_stripos($sem($texto), reset($palavras));
            $trecho = $pos === false ? Str::limit($texto, 160) : '…' . mb_substr($texto, max(0, $pos - 60), 180) . '…';

            return ['titulo' => $c->titulo, 'trecho' => $trecho, 'url' => route('concursos.material.capitulo', [$material, $c->id]) . '?q=' . rawurlencode(reset($palavras))];
        })->values();

        return response()->json($achados);
    }

    /** O livro inteiro numa página, para imprimir ou salvar em PDF. */
    public function imprimir(Request $request, Material $material, Citacoes $citacoes): View
    {
        $this->doAluno($request, $material);
        $capitulos = $material->capitulos()->get();
        $docs = $capitulos->mapWithKeys(fn ($c) => [$c->id => $this->documento->limpar($c->conteudo)]);
        $idsP = $docs->flatMap(fn ($d) => $this->documento->ids($d, 'pontoErro', 'pontoId'))->all();
        $idsQ = $docs->flatMap(fn ($d) => $this->documento->ids($d, 'questao', 'questaoId'))->all();
        $ctx = [
            'pontos' => MaterialPonto::whereIn('id', $idsP)->get()->keyBy('id'),
            'questoes' => MaterialQuestao::where('material_id', $material->id)->whereIn('id', $idsQ)->get()->keyBy('id'),
        ];
        $notas = MaterialAnotacao::whereIn('capitulo_id', $capitulos->pluck('id'))->get()->groupBy('capitulo_id');

        $plano = $this->plano($capitulos)->map(fn ($c) => $c + [
            'html' => $this->documento->html($docs[$c['id']], $citacoes, $ctx),
            'notas' => $notas->get($c['id'], collect()),
        ]);

        return view('concursos.material.imprimir', compact('material', 'plano'));
    }

    private function converterSePreciso(Material $material, ConversorLivro $conversor): void
    {
        $pendente = MaterialQuestao::where('material_id', $material->id)->whereNull('capitulo_id')->exists()
            || MaterialPonto::whereNull('capitulo_id')->whereIn('secao_id', $material->secoes()->select('id'))->exists();
        if ($pendente) {
            $conversor->converter($material);
        }
    }

    /** Sumário, plano de leitura (capítulos em ordem) e contagens por capítulo. */
    /**
     * Sumário, plano de leitura (capítulos em ordem) e contagens por capítulo. O Resumo mostra só os
     * capítulos com teoria (e os pais deles); os do diário de erros (só pontos/questões) aparecem com
     * ?todos=1. As contagens vêm da situação calculada das questões (MaterialQuestao::estado).
     */
    private function base(Material $material): array
    {
        $todosCaps = $material->capitulos()->get(['id', 'parent_id', 'titulo', 'ordem', 'assunto_id', 'conteudo', 'pdf_anexo_id']);
        $todos = request()->boolean('todos');
        $mostrar = $todos ? collect() : $this->comTeoria($todosCaps);
        if ($mostrar->isEmpty()) {
            $mostrar = $todosCaps->pluck('id')->flip(); // livro sem teoria nenhuma (ex.: Auditoria): mostra tudo
        }
        $capitulos = $todosCaps->filter(fn ($c) => isset($mostrar[$c->id]))->values();

        $questoes = MaterialQuestao::with('ultimaTentativa')->where('material_id', $material->id)
            ->get(['id', 'capitulo_id', 'resultado_tec', 'caixa', 'revisar_em']);
        // Questões de cada capítulo: as do assunto do TEC (capitulo_id) mais as ligadas aos grifos do texto.
        $ids = $questoes->whereNotNull('capitulo_id')->groupBy('capitulo_id')->map(fn ($qs) => $qs->pluck('id'));
        MaterialGrifoQuestao::whereIn('capitulo_id', $todosCaps->pluck('id'))->get(['capitulo_id', 'questao_id'])
            ->groupBy('capitulo_id')
            ->each(function ($ls, $capId) use ($ids) {
                $ids[$capId] = $ids->get($capId, collect())->merge($ls->pluck('questao_id'))->unique()->values();
            });
        $porId = $questoes->keyBy('id');
        $porCap = $ids->map(fn ($qids) => $qids->map(fn ($id) => $porId->get($id))->filter()->countBy(fn ($q) => $q->estado()));
        $estados = $questoes->countBy(fn ($q) => $q->estado());

        $plano = $this->plano($capitulos)->map(function ($c) use ($porCap) {
            $e = $porCap->get($c['id'], collect());

            return $c + [
                'errando' => (int) $e->get('errando', 0),
                'revisando' => (int) $e->get('revisando', 0),
                'dominado' => (int) $e->get('dominado', 0),
                'pontos' => (int) $e->sum(),
                'questoes' => (int) $e->sum(),
            ];
        });

        return [
            'material' => $material,
            'plano' => $plano,
            'todos' => $todos,
            'ocultos' => $todosCaps->count() - $capitulos->count(),
            'resumo' => [
                'capitulos' => $capitulos->count(),
                'errando' => (int) $estados->get('errando', 0),
                'revisando' => (int) $estados->get('revisando', 0),
                'dominado' => (int) $estados->get('dominado', 0),
                'questoes' => $questoes->count(),
                'hoje' => $questoes->filter(fn ($q) => $q->naFilaHoje())->count(),
            ],
        ];
    }

    /** @return Collection<int, int> ids (como chaves) dos capítulos com teoria e de todos os pais deles */
    private function comTeoria(Collection $capitulos): Collection
    {
        $pais = $capitulos->pluck('parent_id', 'id');
        $saida = collect();
        foreach ($capitulos as $c) {
            if (!$c->pdf_anexo_id && !$this->documento->temTeoria($c->conteudo)) {
                continue;
            }
            for ($id = $c->id, $guarda = 0; $id && !$saida->has($id) && $guarda < 20; $id = $pais[$id] ?? null, $guarda++) {
                $saida[$id] = 1;
            }
        }

        return $saida;
    }

    /** Capítulos em ordem de leitura (pré-ordem da árvore), com nível e numeração "2.1.3". */
    private function plano(Collection $capitulos): Collection
    {
        $filhos = $capitulos->sortBy([['ordem', 'asc'], ['id', 'asc']])->groupBy(fn ($c) => $c->parent_id ?? 0);
        $saida = collect();
        $andar = function (int $pai, int $nivel, string $prefixo) use (&$andar, $filhos, $saida) {
            foreach ($filhos->get($pai, collect())->values() as $i => $c) {
                $num = $prefixo . ($i + 1);
                $saida->push(['id' => $c->id, 'parent_id' => $c->parent_id, 'titulo' => $c->titulo, 'nivel' => $nivel, 'num' => $num]);
                $andar($c->id, $nivel + 1, $num . '.');
            }
        };
        $andar(0, 0, '');

        return $saida;
    }

    /** @return list<array{id: int, titulo: string}> do capítulo raiz até o pai (migalhas). */
    private function caminho(MaterialCapitulo $c): array
    {
        $saida = [];
        for ($p = $c->pai, $guarda = 0; $p && $guarda < 20; $p = $p->pai, $guarda++) {
            array_unshift($saida, ['id' => $p->id, 'titulo' => $p->titulo]);
        }

        return $saida;
    }

    private function novoPonto(MaterialCapitulo $capitulo, string $titulo): MaterialPonto
    {
        return MaterialPonto::create([
            'capitulo_id' => $capitulo->id,
            'titulo' => mb_substr($titulo, 0, 200),
            'regra' => '',
            'origem' => 'manual',
            'status' => 'errando',
            'vezes_errado' => 1,
            'ultimo_erro_em' => today(),
            'ordem' => 0,
        ]);
    }

    /** @return array<int, string> */
    private function titulosDosPontos(array $doc): array
    {
        $saida = [];
        $andar = function (array $nos) use (&$andar, &$saida) {
            foreach ($nos as $no) {
                if (($no['type'] ?? null) === 'pontoErro' && ($id = (int) ($no['attrs']['pontoId'] ?? 0))) {
                    $saida[$id] = $no['attrs']['titulo'] ?? 'Ponto que eu erro';
                }
                $andar($no['content'] ?? []);
            }
        };
        $andar($doc['content'] ?? []);

        return $saida;
    }

    /** @return list<string> ids dos grifos no texto */
    private function marcas(array $doc): array
    {
        $saida = [];
        $andar = function (array $nos) use (&$andar, &$saida) {
            foreach ($nos as $no) {
                foreach ($no['marks'] ?? [] as $m) {
                    if (($m['type'] ?? null) === 'grifo' && !empty($m['attrs']['id'])) {
                        $saida[] = $m['attrs']['id'];
                    }
                }
                $andar($no['content'] ?? []);
            }
        };
        $andar($doc['content'] ?? []);

        return array_values(array_unique($saida));
    }

    private function dadosPonto(MaterialPonto $p): array
    {
        return [
            'id' => $p->id,
            'titulo' => $p->titulo,
            'status' => $p->status,
            'vezes' => (int) $p->vezes_errado,
            'ultimo' => $p->ultimo_erro_em?->format('d/m/Y'),
            'urlStatus' => route('concursos.material.pontos.status', $p),
            'urlErrei' => route('concursos.material.pontos.errei', $p),
        ];
    }

    private function dadosQuestao(MaterialQuestao $q): array
    {
        return [
            'id' => $q->id,
            'tec' => $q->id_externo,
            'tipo' => $q->tipo,
            'origem' => $q->origem,
            'enunciado' => $q->enunciado,
            'alternativas' => $q->tipo === 'ME' ? array_values($q->alternativas ?? []) : [['letra' => 'C', 'texto' => 'Certo'], ['letra' => 'E', 'texto' => 'Errado']],
            'antes' => $q->ultimaTentativa ? ($q->ultimaTentativa->acertou ? 'acertou' : 'errou') . ' em ' . $q->ultimaTentativa->created_at->format('d/m') : null,
            'url' => route('concursos.material.questoes.responder', $q),
        ];
    }

    private function dadosAnotacao(MaterialAnotacao $a): array
    {
        return ['id' => $a->id, 'marca' => $a->marca, 'cor' => $a->cor, 'trecho' => $a->trecho, 'nota' => $a->nota, 'em' => $a->updated_at?->format('d/m/Y')];
    }

    private function doAluno(Request $request, ?Material $material): void
    {
        abort_unless($material && $material->user_id === $request->user()->id, 404);
    }
}
