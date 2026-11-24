<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Tentativa;
use App\Models\Concursos\UsoEvento;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Lê a telemetria (uso_eventos) e as respostas (tentativas) para mostrar como cada aluno usa o módulo.
 * Sessão = sequência de eventos com no máximo 30 min de intervalo.
 */
class UsoConcursos
{
    public const PAUSA_SESSAO_MIN = 30;

    /** @return list<array{user_id:int, ultimo:CarbonInterface, dias:int, sessoes:int, minutos:int, eventos:int, questoes:int, acertos:int, dispositivo:string}> */
    public function visaoGeral(CarbonInterface $desde): array
    {
        $eventos = UsoEvento::where('created_at', '>=', $desde)->orderBy('created_at')->get()->groupBy('user_id');
        $tentativas = Tentativa::where('created_at', '>=', $desde)->get(['user_id', 'correta'])->groupBy('user_id');

        $linhas = [];
        foreach ($eventos as $userId => $evs) {
            $t = $tentativas->get($userId, collect());
            $linhas[] = [
                'user_id' => (int) $userId,
                'ultimo' => $evs->last()->created_at,
                'dias' => $evs->groupBy(fn ($e) => $e->created_at->toDateString())->count(),
                'sessoes' => count($this->sessoes($evs)),
                'minutos' => $this->minutos($evs),
                'eventos' => $evs->count(),
                'questoes' => $t->count(),
                'acertos' => $t->where('correta', true)->count(),
                'dispositivo' => $evs->countBy('dispositivo')->sortDesc()->keys()->first(),
            ];
        }
        usort($linhas, fn ($a, $b) => $b['ultimo'] <=> $a['ultimo']);

        return $linhas;
    }

    public function detalhe(int $userId, CarbonInterface $desde): array
    {
        $evs = UsoEvento::where('user_id', $userId)->where('created_at', '>=', $desde)->orderBy('created_at')->get();
        $tent = Tentativa::where('user_id', $userId)->where('created_at', '>=', $desde)->get(['correta', 'created_at', 'tempo_segundos', 'origem']);

        $dias = [];
        foreach ($evs->groupBy(fn ($e) => $e->created_at->toDateString()) as $data => $doDia) {
            $dias[$data] = ['data' => $data, 'minutos' => $this->minutos($doDia), 'eventos' => $doDia->count(), 'questoes' => 0, 'acertos' => 0];
        }
        foreach ($tent as $t) {
            $data = $t->created_at->toDateString();
            $dias[$data] ??= ['data' => $data, 'minutos' => 0, 'eventos' => 0, 'questoes' => 0, 'acertos' => 0];
            $dias[$data]['questoes']++;
            $dias[$data]['acertos'] += $t->correta ? 1 : 0;
        }
        krsort($dias);

        $telas = $evs->groupBy(fn ($e) => UsoEvento::tela($e->rota))->map(fn (Collection $g, $tela) => [
            'tela' => $tela,
            'vezes' => $g->count(),
            'minutos' => (int) round($g->sum('visivel_s') / 60),
        ])->sortByDesc(fn ($l) => [$l['minutos'], $l['vezes']])->values()->all();

        $sessoes = array_map(function (Collection $s) {
            $telas = $s->map(fn ($e) => UsoEvento::tela($e->rota))->unique()->values();

            return [
                'inicio' => $s->first()->created_at,
                'fim' => $s->last()->created_at->copy()->addSeconds((int) $s->last()->visivel_s),
                'minutos' => $this->minutos($s),
                'eventos' => $s->count(),
                'dispositivo' => $s->first()->dispositivo,
                'telas' => $telas->take(6)->implode(' · ') . ($telas->count() > 6 ? ' …' : ''),
            ];
        }, array_reverse($this->sessoes($evs)));

        $origens = $tent->countBy('origem')->sortDesc()->all();
        $tempos = $tent->pluck('tempo_segundos')->filter();

        return [
            'kpis' => [
                'dias' => count(array_filter($dias, fn ($d) => $d['eventos'] > 0)),
                'sessoes' => count($sessoes),
                'minutos' => $this->minutos($evs),
                'questoes' => $tent->count(),
                'acertos' => $tent->where('correta', true)->count(),
                'tempo_medio_s' => $tempos->isNotEmpty() ? (int) round($tempos->avg()) : null,
                'ultimo' => $evs->last()?->created_at,
                'erros' => $evs->where('status', '>=', 500)->count(),
            ],
            'dias' => array_values($dias),
            'telas' => $telas,
            'sessoes' => array_slice($sessoes, 0, 20),
            'origens' => $origens,
            'recentes' => $evs->reverse()->take(60)->values(),
        ];
    }

    /** @return list<Collection<int, UsoEvento>> */
    private function sessoes(Collection $evs): array
    {
        $sessoes = [];
        $atual = null;
        $anterior = null;
        foreach ($evs as $e) {
            $fimAnterior = $anterior?->created_at->copy()->addSeconds((int) $anterior->visivel_s);
            if (!$atual || $fimAnterior->diffInMinutes($e->created_at) > self::PAUSA_SESSAO_MIN) {
                $sessoes[] = $atual = collect();
            }
            $atual->push($e);
            $anterior = $e;
        }

        return $sessoes;
    }

    /** Minutos com tela à vista; sem o dado do navegador, usa o intervalo até o evento seguinte (até 5 min). */
    private function minutos(Collection $evs): int
    {
        $seg = 0;
        $lista = $evs->values();
        foreach ($lista as $i => $e) {
            if ($e->visivel_s !== null) {
                $seg += $e->visivel_s;
            } elseif ($e->metodo === 'GET' && isset($lista[$i + 1])) {
                $seg += min(300, max(0, $e->created_at->diffInSeconds($lista[$i + 1]->created_at)));
            }
        }

        return (int) round($seg / 60);
    }
}
