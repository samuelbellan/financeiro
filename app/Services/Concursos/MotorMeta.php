<?php

namespace App\Services\Concursos;

use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Meta;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Tentativa;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Motor determinístico da meta do dia. Sem IA: mesma entrada → mesma meta, e cada atividade
 * diz por que está ali.
 *
 *  valor do tópico   = questões da área × pontos/questão × fatia do tópico na área
 *                      (fatia = questões do tópico / questões da área, suavizada; a contagem é a
 *                      da árvore do TEC quando existe, senão a do banco: o que for maior)
 *  domínio           = (acertos sem chute + 2×prior) / (tentativas + 2), nos últimos 60 dias;
 *                      o prior vem da declaração (Não vi / Vi parte / Vi tudo)
 *  prioridade        = valor × (1 − domínio) ÷ (1 + tentativas dos últimos 3 dias / 10)
 *                      (o último termo faz o motor girar entre tópicos)
 *
 * Ordem do dia: 1) atividades adiadas; 2) revisão de erros vencida (1/3/7/15/30 dias, até 30%
 * do tempo); 3) por prioridade: Teoria (se "Não vi" ou rede de segurança) + bloco de questões.
 * Tempo NUNCA vira domínio: só tentativas contam.
 */
class MotorMeta
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('concursos.motor');
    }

    /** A meta do dia: gera na primeira chamada e depois só devolve (congelada). */
    public function metaDoDia(Perfil $perfil, CarbonInterface $data): Meta
    {
        $existente = Meta::where('user_id', $perfil->user_id)->whereDate('data', $data->toDateString())->first();
        if ($existente) {
            return $existente;
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($perfil, $data) {
            $meta = Meta::create(['user_id' => $perfil->user_id, 'data' => $data->toDateString(), 'minutos_orcamento' => $perfil->minutosDoDia($data)]);
            foreach ($this->planejar($perfil, $data) as $i => $a) {
                $meta->atividades()->create($a + ['ordem' => $i + 1]);
            }

            return $meta;
        });
    }

    /**
     * Índices de cada tópico do cargo (também usados na tela do perfil).
     *
     * @return Collection<int, array{topico: EditalTopico, area: string, valor: float, dominio: float, tentativas: int,
     *     acertos: int, recentes: int, declarado: ?string, prioridade: float, questoes: list<int>, teoria_feita: bool}>
     */
    public function indices(Perfil $perfil, CarbonInterface $data): Collection
    {
        $areas = $perfil->cargo->areas()->with('disciplinas.topicos')->get();
        $declarado = $perfil->declaracoes();
        $teoriaFeita = MetaAtividade::where('tipo', 'teoria')->where('status', 'feita')
            ->whereHas('meta', fn ($q) => $q->where('user_id', $perfil->user_id))
            ->get(['topico_id', 'concluida_em'])->groupBy('topico_id')
            ->map(fn ($g) => $g->max('concluida_em'));

        $inicioJanela = $data->copy()->subDays($this->cfg['janela_dias'])->startOfDay();
        $inicioRecente = $data->copy()->subDays(3)->startOfDay();
        $tentativas = Tentativa::where('user_id', $perfil->user_id)->where('created_at', '>=', $inicioJanela)
            ->where('created_at', '<', $data->copy()->endOfDay())
            ->get(['questao_id', 'correta', 'certeza', 'created_at'])->groupBy('questao_id');

        $tec = $this->incidenciaTec($areas->flatMap->disciplinas->flatMap->topicos->pluck('id')->all());

        $saida = collect();
        foreach ($areas as $area) {
            $topicos = $area->disciplinas->flatMap->topicos;
            // Tópico "sem questões no TEC" (norma própria) ainda pode ter questões inéditas ligadas.
            $ids = $topicos->mapWithKeys(fn ($t) => [$t->id => $t->questoesQuery()->where('anulada', false)->orderBy('id')->pluck('id')->all()]);
            $contagens = $ids->map(fn ($l, $tid) => max(count($l), $tec[$tid] ?? 0));
            // Suavização: tópico sem questão no banco (caderno ainda não baixado) não some do mapa.
            $k = max(1.0, $contagens->avg() * 0.2);
            $soma = $contagens->sum(fn ($c) => $c + $k);
            $pontosArea = $area->n_questoes * $area->pontos_por_questao;

            foreach ($topicos as $t) {
                $n = $acertos = $recentes = 0;
                foreach ($ids[$t->id] as $qid) {
                    foreach ($tentativas[$qid] ?? [] as $tt) {
                        $n++;
                        $acertos += $tt->dominio() ? 1 : 0;
                        $recentes += $tt->created_at >= $inicioRecente ? 1 : 0;
                    }
                }
                $dec = $declarado[$t->id] ?? null;
                $dominio = ($acertos + 2 * $this->cfg['prior'][$dec ?? '']) / ($n + 2);
                $valor = $pontosArea * ($contagens[$t->id] + $k) / $soma;

                $saida->push([
                    'topico' => $t, 'area' => $area->nome, 'questoes_tec' => $tec[$t->id] ?? null, 'pontos_por_questao' => $area->pontos_por_questao,
                    'valor' => $valor, 'dominio' => $dominio, 'tentativas' => $n, 'acertos' => $acertos, 'recentes' => $recentes,
                    'declarado' => $dec, 'questoes' => $ids[$t->id], 'teoria_feita' => isset($teoriaFeita[$t->id]),
                    'teoria_recente' => isset($teoriaFeita[$t->id]) && $teoriaFeita[$t->id] >= $data->copy()->subDays(14),
                    'prioridade' => $valor * (1 - $dominio) / (1 + $recentes / 10),
                ]);
            }
        }

        return $saida->sortByDesc('prioridade')->values();
    }

    /**
     * Questões do TEC por tópico, pela contagem da árvore (assunto_incidencias, somando cadernos).
     * Como a contagem do pai já inclui os filhos, só entram os assuntos do tópico que não têm
     * um ancestral também ligado ao tópico.
     *
     * @param  list<int>  $topicoIds
     * @return array<int, int> topico_id => questões
     */
    public function incidenciaTec(array $topicoIds): array
    {
        $db = DB::connection(config('concursos.connection'));
        $porAssunto = $db->table('assunto_incidencias')->groupBy('assunto_id')
            ->selectRaw('assunto_id, sum(questoes) as n')->pluck('n', 'assunto_id')->all();
        if (!$porAssunto) {
            return [];
        }
        $pai = $db->table('assuntos')->pluck('parent_id', 'id')->all();
        $ligados = $db->table('edital_topico_assunto')->whereIn('topico_id', $topicoIds ?: [0])->get(['topico_id', 'assunto_id'])->groupBy('topico_id');

        $saida = [];
        foreach ($ligados as $tid => $linhas) {
            $set = array_flip($linhas->pluck('assunto_id')->all());
            $soma = 0;
            foreach (array_keys($set) as $aid) {
                for ($p = $pai[$aid] ?? null, $coberto = false; $p && !$coberto; $p = $pai[$p] ?? null) {
                    $coberto = isset($set[$p]);
                }
                if (!$coberto) {
                    $soma += (int) ($porAssunto[$aid] ?? 0);
                }
            }
            if ($soma > 0) {
                $saida[$tid] = $soma;
            }
        }

        return $saida;
    }

    /** @return list<array> atributos de MetaAtividade, na ordem do dia */
    public function planejar(Perfil $perfil, CarbonInterface $data): array
    {
        $orcamento = $perfil->minutosDoDia($data);
        $restante = $orcamento;
        $atividades = [];
        $usadas = [];

        // 1) Adiadas de dias anteriores voltam primeiro.
        $adiadas = MetaAtividade::where('status', 'adiada')
            ->whereHas('meta', fn ($q) => $q->where('user_id', $perfil->user_id)->whereDate('data', '<', $data->toDateString()))
            ->whereNull('concluida_em')->orderBy('id')->get();
        foreach ($adiadas as $a) {
            $atividades[] = ['tipo' => $a->tipo, 'topico_id' => $a->topico_id, 'questao_ids' => $a->questao_ids,
                'minutos' => $a->minutos,
                'motivo' => 'Adiada ' . ($a->adiamentos > 1 ? "{$a->adiamentos} vezes" : '1 vez') . '. ' . preg_replace('/^Adiada [^.]*\. /u', '', $a->motivo),
                'adiamentos' => $a->adiamentos];
            $a->update(['concluida_em' => now()]); // a cópia de hoje assume; a antiga sai da fila
            $restante -= $a->minutos;
            array_push($usadas, ...($a->questao_ids ?? []));
        }
        if ($orcamento <= 0) {
            return $atividades;
        }

        // 2) Revisão de erros vencida. Minutos por questão: o padrão, ou o seu ritmo real no cronômetro.
        $mq = $this->minutosPorQuestao($perfil->user_id, $data)['valor'];
        $cabem = fn (float $min) => (int) floor($min / $mq);
        $vencidas = array_values(array_diff($this->revisoesVencidas($perfil->user_id, $data), $usadas));
        $maxRevisao = $cabem($orcamento * $this->cfg['revisao_max_pct'] / 100);
        if ($vencidas && $maxRevisao > 0 && $restante > 0) {
            $lote = array_slice($vencidas, 0, min($maxRevisao, $cabem($restante)));
            if ($lote) {
                $atividades[] = ['tipo' => 'revisao', 'topico_id' => null, 'questao_ids' => $lote, 'minutos' => (int) round(count($lote) * $mq),
                    'motivo' => count($vencidas) . ' questão(ões) que você errou ou chutou chegaram no dia de rever (intervalos de 1, 3, 7, 15 e 30 dias).'
                        . (count($vencidas) > count($lote) ? ' As outras ficam para amanhã.' : '')];
                $restante -= (int) round(count($lote) * $mq);
                array_push($usadas, ...$lote);
            }
        }

        // 3) Tópicos por prioridade: Teoria quando precisa, depois um bloco de questões.
        $indices = $this->indices($perfil, $data);
        $bloco = $this->cfg['questoes_por_bloco'];
        $ultimas = Tentativa::where('user_id', $perfil->user_id)->get(['questao_id'])->pluck('questao_id')->flip();
        foreach ($indices as $ix) {
            if ($restante < $mq * 3) {
                break;
            }
            $t = $ix['topico'];
            $pct = $ix['tentativas'] ? round(100 * $ix['acertos'] / $ix['tentativas']) : null;
            $base = sprintf('%s: vale cerca de %s ponto(s) na prova (%s pt/questão). ',
                $ix['area'], number_format($ix['valor'], 1, ',', ''), rtrim(rtrim(number_format($ix['pontos_por_questao'], 2, ',', ''), '0'), ','));
            $situacao = $ix['tentativas']
                ? "Seu acerto: {$pct}% em {$ix['tentativas']} questão(ões)."
                : 'Você ainda não fez questões deste tópico' . ($ix['declarado'] ? ' (declarou "' . Perfil::DECLARACOES[$ix['declarado']] . '").' : '.');

            $redeSeguranca = $ix['tentativas'] >= $this->cfg['rede_minimo'] && $ix['acertos'] / $ix['tentativas'] < $this->cfg['rede_acerto'];
            $precisaTeoria = (!$ix['teoria_feita'] && $ix['declarado'] === 'nao_vi') || ($redeSeguranca && !$ix['teoria_recente']);
            if ($precisaTeoria) {
                if ($restante < $this->cfg['minutos_teoria']) {
                    continue;
                }
                $atividades[] = ['tipo' => 'teoria', 'topico_id' => $t->id, 'questao_ids' => null, 'minutos' => $this->cfg['minutos_teoria'],
                    'motivo' => $base . ($redeSeguranca ? "Acerto abaixo de 50% depois de {$ix['tentativas']} questões: vale rever a teoria." : 'Você marcou "Não vi": comece pela teoria.')];
                $restante -= $this->cfg['minutos_teoria'];
            }
            if (!$ix['questoes']) {
                continue;
            }

            $n = min($bloco, $cabem($restante));
            $escolhidas = $this->escolher($ix['questoes'], $usadas, $ultimas, $n, $perfil->user_id, $data);
            if (count($escolhidas) < min(3, $n)) {
                continue;
            }
            $atividades[] = ['tipo' => 'questoes', 'topico_id' => $t->id, 'questao_ids' => $escolhidas,
                'minutos' => (int) round(count($escolhidas) * $mq), 'motivo' => $base . $situacao];
            $restante -= (int) round(count($escolhidas) * $mq);
            array_push($usadas, ...$escolhidas);
        }

        return $atividades;
    }

    /**
     * Minutos por questão usados na meta. Com 3+ atividades de questões concluídas e cronometradas
     * no /estudos nos últimos 21 dias, vale a mediana do seu ritmo real (entre 1,5 e 8 min);
     * senão, o padrão do config. É só tamanho de meta: o tempo nunca entra no domínio.
     *
     * @return array{valor: float, calibrado: bool, amostras: int}
     */
    public function minutosPorQuestao(int $userId, CarbonInterface $data): array
    {
        $ritmos = MetaAtividade::whereIn('tipo', ['questoes', 'revisao'])->where('status', 'feita')
            ->where('minutos_reais', '>', 0)
            ->whereHas('meta', fn ($q) => $q->where('user_id', $userId)
                ->whereDate('data', '>=', $data->copy()->subDays(21)->toDateString())
                ->whereDate('data', '<', $data->toDateString()))
            ->get(['minutos_reais', 'questao_ids'])
            ->filter(fn ($a) => count($a->questao_ids ?? []) > 0)
            ->map(fn ($a) => (float) $a->minutos_reais / count($a->questao_ids))
            ->sort()->values();
        if ($ritmos->count() < 3) {
            return ['valor' => (float) $this->cfg['minutos_por_questao'], 'calibrado' => false, 'amostras' => $ritmos->count()];
        }

        return ['valor' => round(min(8.0, max(1.5, (float) $ritmos->median())), 1), 'calibrado' => true, 'amostras' => $ritmos->count()];
    }

    /**
     * Questões com erro/chute cuja próxima revisão venceu. A cada acerto sem chute depois do
     * último erro, o intervalo sobe (1 → 3 → 7 → 15 → 30 dias); depois do 5º, sai da revisão.
     *
     * @return list<int>
     */
    public function revisoesVencidas(int $userId, CarbonInterface $data): array
    {
        $intervalos = $this->cfg['revisao_intervalos'];
        $vencidas = [];
        $porQuestao = Tentativa::where('user_id', $userId)->where('created_at', '<', $data->copy()->startOfDay())
            ->orderBy('id')->get(['questao_id', 'correta', 'certeza', 'created_at'])->groupBy('questao_id');
        foreach ($porQuestao as $qid => $lista) {
            $k = null;
            foreach ($lista as $tt) {
                $k = $tt->dominio() ? ($k === null ? null : $k + 1) : 0;
            }
            if ($k === null || $k >= count($intervalos)) {
                continue;
            }
            $vence = $lista->last()->created_at->copy()->startOfDay()->addDays($intervalos[$k]);
            if ($vence->lte($data)) {
                $vencidas[$qid] = $vence->timestamp;
            }
        }
        asort($vencidas); // as mais atrasadas primeiro

        return array_values(array_intersect(array_keys($vencidas),
            Questao::whereIn('id', array_keys($vencidas) ?: [0])->where('anulada', false)->pluck('id')->all()));
    }

    /** Inéditas primeiro; a ordem dentro de cada grupo é embaralhada de forma estável (usuário + dia). */
    private function escolher(array $ids, array $usadas, Collection $jaFeitas, int $n, int $userId, CarbonInterface $data): array
    {
        $livres = array_diff($ids, $usadas);
        $semente = "{$userId}-{$data->toDateString()}";
        usort($livres, fn ($a, $b) => [isset($jaFeitas[$a]), crc32("{$semente}-{$a}")] <=> [isset($jaFeitas[$b]), crc32("{$semente}-{$b}")]);

        return array_slice($livres, 0, $n);
    }
}
