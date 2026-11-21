<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Tentativa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tela de resolução de questões do módulo novo.
 * Fluxo: escolhe assunto + filtro → resolve (alternativa + certeza) → vê gabarito e resolução
 * → se errou, diz o motivo → próxima. O contexto (assunto, filtro) viaja na query string.
 */
class QuestoesController extends Controller
{
    public const FILTROS = [
        'nao' => 'Não respondidas',
        'erradas' => 'Que errei ou chutei',
        'todas' => 'Todas',
    ];

    public function index(Request $request): View
    {
        $userId = $request->user()->id;
        $ultimas = $this->ultimasTentativas($userId);

        // questão → assunto principal (sem anuladas)
        $porAssunto = [];
        DB::connection(config('concursos.connection'))->table('questao_assunto as qa')
            ->join('questoes as q', 'q.id', '=', 'qa.questao_id')
            ->where('qa.principal', true)->where('q.anulada', false)
            ->get(['qa.assunto_id', 'q.id'])
            ->each(function ($r) use (&$porAssunto) {
                $porAssunto[$r->assunto_id][] = $r->id;
            });

        $assuntos = Assunto::whereNull('unido_em_id')->orderBy('nome')->get(['id', 'disciplina_id', 'parent_id', 'nome']);
        $filhos = $assuntos->groupBy('parent_id');
        $stats = [];
        $contar = function (int $id) use (&$contar, &$stats, $filhos, $porAssunto, $ultimas): array {
            if (isset($stats[$id])) {
                return $stats[$id];
            }
            $s = ['total' => 0, 'respondidas' => 0, 'acertos' => 0];
            foreach ($porAssunto[$id] ?? [] as $qid) {
                $s['total']++;
                if ($t = $ultimas[$qid] ?? null) {
                    $s['respondidas']++;
                    $s['acertos'] += $t->correta ? 1 : 0;
                }
            }
            foreach ($filhos[$id] ?? [] as $f) {
                $fs = $contar($f->id);
                foreach ($s as $k => $v) {
                    $s[$k] = $v + $fs[$k];
                }
            }

            return $stats[$id] = $s;
        };

        $arvore = Disciplina::orderBy('nome')->get()->map(function ($d) use ($filhos, $contar) {
            $raizes = ($filhos[''] ?? collect())->where('disciplina_id', $d->id)
                ->map(fn ($a) => ['assunto' => $a, 's' => $contar($a->id),
                    'filhos' => ($filhos[$a->id] ?? collect())->map(fn ($f) => ['assunto' => $f, 's' => $contar($f->id)])
                        ->filter(fn ($f) => $f['s']['total'] > 0)->values()])
                ->filter(fn ($r) => $r['s']['total'] > 0)->values();

            return ['disciplina' => $d, 'raizes' => $raizes, 'total' => $raizes->sum(fn ($r) => $r['s']['total'])];
        })->filter(fn ($d) => $d['total'] > 0)->values();

        return view('concursos.questoes.index', [
            'arvore' => $arvore,
            'filtros' => self::FILTROS,
            'hoje' => Tentativa::where('user_id', $userId)->where('created_at', '>=', today())->get(['correta']),
        ]);
    }

    /** Vai para a próxima questão do contexto (depois da atual, se houver). */
    public function resolver(Request $request): RedirectResponse
    {
        $ctx = $this->contexto($request);
        $ids = $this->fila($request->user()->id, $ctx);
        $depois = (int) $request->query('depois', 0);
        $prox = isset($ctx['atividade'])
            ? collect($ids)->first(fn ($id) => $id !== $depois)   // ordem da atividade
            : (collect($ids)->first(fn ($id) => $id > $depois) ?? ($depois ? collect($ids)->first(fn ($id) => $id !== $depois) : null));

        if (!$prox && isset($ctx['atividade'])) {
            $a = MetaAtividade::find($ctx['atividade']);
            if (!array_diff($a->questao_ids, $a->respondidas($request->user()->id))) {
                $a->update(['status' => 'feita', 'concluida_em' => now()]);
            }

            return redirect()->route('concursos.meta')->with('status', 'Atividade concluída. Próxima da meta:');
        }
        if (!$prox) {
            return redirect()->route('concursos.questoes.index')
                ->with('status', 'Não há mais questões nesse filtro. Escolha outro assunto ou filtro.');
        }

        return redirect()->route('concursos.questoes.show', ['questao' => $prox] + $ctx);
    }

    public function show(Request $request, Questao $questao): View
    {
        $userId = $request->user()->id;
        $ctx = $this->contexto($request);
        $questao->load(['alternativas', 'resolucao', 'classificacao', 'assuntos' => fn ($q) => $q->wherePivot('principal', true)]);

        $tentativa = $request->query('t')
            ? Tentativa::where('id', $request->query('t'))->where('user_id', $userId)->where('questao_id', $questao->id)->first()
            : null;

        $fila = $this->fila($userId, ['filtro' => 'todas'] + $ctx);
        $atividade = isset($ctx['atividade']) ? MetaAtividade::with('topico', 'meta')->find($ctx['atividade']) : null;
        $ultimas = $this->ultimasTentativas($userId, $atividade ? $atividade->respondidas($userId) : $fila);

        return view('concursos.questoes.show', [
            'q' => $questao,
            't' => $tentativa,
            'ctx' => $ctx,
            'ctxAssunto' => isset($ctx['assunto']) ? Assunto::with('disciplina')->find($ctx['assunto']) : null,
            'ctxAtividade' => $atividade,
            'filtros' => self::FILTROS,
            'certezas' => Tentativa::CERTEZAS,
            'motivos' => Tentativa::MOTIVOS,
            'historico' => Tentativa::where('user_id', $userId)->where('questao_id', $questao->id)
                ->when($tentativa, fn ($q) => $q->where('id', '<', $tentativa->id))->latest('id')->limit(5)->get(),
            'progresso' => ['total' => count($fila), 'respondidas' => $ultimas->count(), 'acertos' => $ultimas->where('correta', true)->count()],
        ]);
    }

    public function responder(Request $request, Questao $questao): RedirectResponse
    {
        abort_if($questao->anulada || $questao->gabarito === null, 422, 'Questão anulada.');
        $letras = $questao->alternativas()->pluck('letra')->all();
        $dados = $request->validate([
            'resposta' => ['required', Rule::in($letras)],
            'certeza' => ['required', Rule::in(array_keys(Tentativa::CERTEZAS))],
            'tempo' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ], [
            'resposta.required' => 'Escolha uma alternativa.',
            'certeza.required' => 'Diga o quanto você tem certeza antes de responder.',
        ]);

        $ctx = $this->contexto($request);
        $tipoAtividade = isset($ctx['atividade']) ? MetaAtividade::whereKey($ctx['atividade'])->value('tipo') : null;
        $t = Tentativa::create([
            'user_id' => $request->user()->id, 'questao_id' => $questao->id,
            'resposta' => $dados['resposta'], 'gabarito' => $questao->gabarito,
            'correta' => $dados['resposta'] === $questao->gabarito, 'certeza' => $dados['certeza'],
            'tempo_segundos' => $dados['tempo'] ?? null,
            'origem' => match ($tipoAtividade) { null => 'avulsa', 'revisao' => 'revisao', default => 'meta' },
        ]);

        return redirect()->route('concursos.questoes.show', ['questao' => $questao->id, 't' => $t->id] + $ctx);
    }

    public function motivo(Request $request, Tentativa $tentativa): RedirectResponse
    {
        abort_unless($tentativa->user_id === $request->user()->id, 404);
        $dados = $request->validate(['motivo' => ['required', Rule::in(array_keys(Tentativa::MOTIVOS))]]);
        $tentativa->update(['motivo_erro' => $dados['motivo']]);

        return redirect()->route('concursos.questoes.resolver', ['depois' => $tentativa->questao_id] + $this->contexto($request));
    }

    /** @return array{assunto?: int, filtro: string} */
    private function contexto(Request $request): array
    {
        $filtro = $request->input('filtro');
        $ctx = ['filtro' => array_key_exists($filtro, self::FILTROS) ? $filtro : 'nao'];
        if ($assunto = (int) $request->input('assunto')) {
            $ctx['assunto'] = $assunto;
        }
        if ($atividade = (int) $request->input('atividade')) {
            $dona = MetaAtividade::whereKey($atividade)->whereHas('meta', fn ($q) => $q->where('user_id', $request->user()->id))->exists();
            abort_unless($dona, 404);
            $ctx = ['filtro' => 'nao', 'atividade' => $atividade];
        }

        return $ctx;
    }

    /** Ids das questões do contexto, em ordem. @return list<int> */
    private function fila(int $userId, array $ctx): array
    {
        if (isset($ctx['atividade'])) {
            $a = MetaAtividade::with('meta')->find($ctx['atividade']);
            $ids = array_values(array_intersect($a->questao_ids ?? [], Questao::whereIn('id', $a->questao_ids ?: [0])->where('anulada', false)->pluck('id')->all()));

            return $ctx['filtro'] === 'todas' ? $ids : array_values(array_diff($ids, $a->respondidas($userId)));
        }
        $q = Questao::query()->where('anulada', false)->orderBy('id');
        if (isset($ctx['assunto'])) {
            $ids = Assunto::comDescendentes([$ctx['assunto']]);
            $q->whereHas('assuntos', fn ($a) => $a->whereIn('assuntos.id', $ids)->where('questao_assunto.principal', true));
        }
        $ids = $q->pluck('id')->all();
        if ($ctx['filtro'] === 'todas') {
            return $ids;
        }

        $ultimas = $this->ultimasTentativas($userId, $ids);

        return array_values(array_filter($ids, fn ($id) => $ctx['filtro'] === 'nao'
            ? !isset($ultimas[$id])
            : isset($ultimas[$id]) && !$ultimas[$id]->dominio()));
    }

    /** Última tentativa do aluno em cada questão. @return Collection<int, Tentativa> */
    private function ultimasTentativas(int $userId, ?array $questaoIds = null): Collection
    {
        // Sem whereIn: a fila pode ter milhares de ids, e o aluno tem bem menos tentativas que isso.
        // toBase(): o only() da coleção Eloquent filtra pela chave do model (id), não pela do array.
        $todas = Tentativa::where('user_id', $userId)->orderBy('id')
            ->get(['id', 'questao_id', 'correta', 'certeza'])->keyBy('questao_id')->toBase();

        return $questaoIds === null ? $todas : $todas->only($questaoIds);
    }
}
