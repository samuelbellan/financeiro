<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Meta;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Tentativa;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Números da tela da meta além do dia: o calendário da semana, a sequência de dias,
 * a jornada até a prova e a trilha do edital.
 *  - Minutos feitos de uma atividade: feita → todos; questões/revisão → proporcional às respondidas.
 *  - Dia sem meta gerada (não abriu a tela) conta o planejado do perfil e zero feito.
 *  - Trilha: cada tópico é um trecho do tamanho do que vale na prova; "firme" = 5+ questões e
 *    domínio ≥ 75%; "em andamento" = alguma questão feita ou teoria estudada.
 */
class JornadaMeta
{
    public const FIRME_DOMINIO = 0.75;

    public function __construct(private MotorMeta $motor) {}

    public function calcular(Perfil $perfil, CarbonInterface $hoje): array
    {
        $hoje = CarbonImmutable::parse($hoje->toDateString());
        $prova = $perfil->cargo->edital->data_prova ? CarbonImmutable::parse($perfil->cargo->edital->data_prova->toDateString()) : null;

        $primeira = Meta::where('user_id', $perfil->user_id)->min('data');
        $inicio = CarbonImmutable::parse($primeira ?? $hoje)->min($hoje);
        $deSemana = $hoje->startOfWeek();
        $desde = $inicio->startOfWeek()->min($deSemana);

        $metas = Meta::where('user_id', $perfil->user_id)->whereDate('data', '>=', $desde->toDateString())
            ->with('atividades.topico.disciplina')->get()->keyBy(fn ($m) => $m->data->toDateString());
        $ultima = $this->ultimasRespostas($perfil->user_id, $metas);

        $dias = [];
        for ($d = $desde; $d->lte($deSemana->endOfWeek()); $d = $d->addDay()) {
            $dias[$d->toDateString()] = $this->dia($perfil, $d, $hoje, $metas[$d->toDateString()] ?? null, $ultima);
        }
        $semana = array_values(array_filter($dias, fn ($x) => $x['data']->gte($deSemana)));

        return [
            'semana' => $semana,
            'semana_total' => $this->soma($semana),
            'sequencia' => $this->sequencia($dias, $hoje),
            'jornada' => $this->jornada($inicio, $hoje, $prova, $dias),
            'trilha' => $this->trilha($perfil, $hoje),
        ];
    }

    /**
     * "questao_id|data" de cada resposta. Uma atividade só conta o que foi respondido no próprio dia
     * (a mesma questão pode voltar numa meta seguinte e ser feita lá).
     */
    private function ultimasRespostas(int $userId, Collection $metas): array
    {
        $ids = $metas->flatMap(fn ($m) => $m->atividades->flatMap(fn ($a) => $a->questao_ids ?? []))->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $saida = [];
        foreach (Tentativa::where('user_id', $userId)->whereIn('questao_id', $ids)
            ->where('created_at', '>=', $metas->min('data')->toDateString())->get(['questao_id', 'created_at']) as $t) {
            $saida[$t->questao_id . '|' . $t->created_at->toDateString()] = true;
        }

        return $saida;
    }

    private function feitoDaAtividade(MetaAtividade $a, CarbonImmutable $dia, array $respostas): array
    {
        if ($a->questao_ids) {
            $data = $dia->toDateString();
            $n = count(array_filter($a->questao_ids, fn ($q) => isset($respostas["{$q}|{$data}"])));
            $total = count($a->questao_ids);

            return [$a->status === 'feita' ? $a->minutos : (int) round($a->minutos * min($n, $total) / $total), $a->status === 'feita' ? $total : $n, $total];
        }

        return [$a->status === 'feita' ? $a->minutos : 0, null, null];
    }

    private function dia(Perfil $perfil, CarbonImmutable $d, CarbonImmutable $hoje, ?Meta $meta, array $ultima): array
    {
        $atividades = [];
        $feito = 0;
        if ($meta) {
            foreach ($meta->atividades as $a) {
                [$min, $n, $total] = $this->feitoDaAtividade($a, $d, $ultima);
                $feito += $min;
                $atividades[] = ['a' => $a, 'feito' => $min, 'respondidas' => $n, 'total' => $total,
                    'estado' => $a->status === 'adiada' ? 'adiada' : ($min >= $a->minutos ? 'feita' : ($min > 0 ? 'parcial' : 'pendente'))];
            }
        }
        $planejado = $meta ? (int) $meta->atividades->sum('minutos') : $perfil->minutosDoDia($d);
        $pct = $planejado ? min(1, $feito / $planejado) : null;

        $estado = match (true) {
            $d->gt($hoje) => $planejado ? 'futuro' : 'folga',
            $planejado === 0 => 'folga',
            $pct >= 0.999 => 'completo',
            $d->eq($hoje) => 'hoje',
            $feito > 0 => 'parcial',
            default => 'perdido',
        };

        return ['data' => $d, 'hoje' => $d->eq($hoje), 'planejado' => $planejado, 'feito' => $feito, 'pct' => $pct,
            'estado' => $estado, 'tem_meta' => (bool) $meta, 'atividades' => $atividades];
    }

    private function soma(array $dias): array
    {
        $p = array_sum(array_column($dias, 'planejado'));
        $f = array_sum(array_column($dias, 'feito'));

        return ['planejado' => $p, 'feito' => $f, 'pct' => $p ? min(1, $f / $p) : null];
    }

    /** Dias seguidos com estudo (folga não quebra; hoje ainda sem estudo também não). */
    private function sequencia(array $dias, CarbonImmutable $hoje): int
    {
        $n = 0;
        foreach (array_reverse($dias) as $x) {
            if ($x['data']->gt($hoje) || $x['estado'] === 'folga' || ($x['hoje'] && !$x['feito'])) {
                continue;
            }
            if (!$x['feito']) {
                break;
            }
            $n++;
        }

        return $n;
    }

    private function jornada(CarbonImmutable $inicio, CarbonImmutable $hoje, ?CarbonImmutable $prova, array $dias): ?array
    {
        if (!$prova || $prova->lte($inicio)) {
            return null;
        }
        $semanas = [];
        for ($w = $inicio->startOfWeek(); $w->lte($prova); $w = $w->addWeek()) {
            $doPeriodo = array_filter($dias, fn ($x) => $x['data']->between($w, $w->endOfWeek()) && $x['data']->lte($hoje));
            $semanas[] = ['inicio' => $w, 'atual' => $hoje->between($w, $w->endOfWeek()), 'passada' => $w->endOfWeek()->lt($hoje),
                'tem_prova' => $prova->between($w, $w->endOfWeek())] + $this->soma($doPeriodo);
        }
        $total = $inicio->diffInDays($prova);
        $atual = array_search(true, array_column($semanas, 'atual'), true);

        return ['inicio' => $inicio, 'prova' => $prova, 'pct_tempo' => $total ? min(1, $inicio->diffInDays($hoje) / $total) : 1,
            'semanas' => $semanas, 'semana_atual' => $atual === false ? null : $atual + 1, 'estudado' => $this->soma($dias)];
    }

    private function trilha(Perfil $perfil, CarbonImmutable $hoje): array
    {
        $indices = $this->motor->indices($perfil, $hoje);
        $areas = $perfil->cargo->areas()->get()->keyBy('nome');
        $estado = fn ($i) => $i['tentativas'] >= PainelDesempenho::MIN_COBERTO && $i['dominio'] >= self::FIRME_DOMINIO
            ? 'firme' : ($i['tentativas'] > 0 || $i['teoria_feita'] ? 'andamento' : 'novo');

        $porArea = $indices->groupBy('area')->map(function (Collection $ix, string $nome) use ($estado) {
            $trechos = $ix->sortBy(fn ($i) => [$i['topico']->disciplina_id, $i['topico']->posicao])->values()
                ->map(fn ($i) => ['topico' => $i['topico'], 'valor' => $i['valor'], 'estado' => $estado($i), 'dominio' => $i['dominio'],
                    'tentativas' => $i['tentativas']]);
            $valor = $trechos->sum('valor') ?: 1;

            return ['nome' => $nome, 'trechos' => $trechos, 'topicos' => $trechos->count(),
                'firme' => $trechos->where('estado', 'firme')->sum('valor') / $valor,
                'andamento' => $trechos->where('estado', 'andamento')->sum('valor') / $valor,
                'n_firme' => $trechos->where('estado', 'firme')->count(), 'n_andamento' => $trechos->where('estado', 'andamento')->count()];
        })->sortBy(fn ($a) => $areas[$a['nome']]->posicao ?? 0)->values();

        $valorTotal = $indices->sum('valor') ?: 1;
        $firme = $indices->filter(fn ($i) => $estado($i) === 'firme')->sum('valor') / $valorTotal;
        $andamento = $indices->filter(fn ($i) => $estado($i) === 'andamento')->sum('valor') / $valorTotal;

        return ['areas' => $porArea, 'firme' => $firme, 'andamento' => $andamento, 'trabalhado' => $firme + $andamento,
            'topicos' => $indices->count(), 'n_firme' => $indices->filter(fn ($i) => $estado($i) === 'firme')->count()];
    }
}
