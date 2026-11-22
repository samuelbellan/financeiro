<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Perfil;
use App\Models\Concursos\Tentativa;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Números do painel de desempenho. Usa os mesmos índices do motor (MotorMeta::indices), para o
 * painel e a meta nunca discordarem:
 *  - nota estimada = Σ valor do tópico × domínio (tópico sem questões feitas usa a declaração);
 *  - cobertura = fatia dos pontos da área em tópicos com 5+ questões feitas.
 * Tempo não entra em nada daqui (só tentativas).
 */
class PainelDesempenho
{
    public const MIN_COBERTO = 5;

    public function __construct(private MotorMeta $motor) {}

    public function calcular(Perfil $perfil, CarbonInterface $data): array
    {
        $indices = $this->motor->indices($perfil, $data);
        $areas = $perfil->cargo->areas()->get()->keyBy('nome');

        $porArea = $indices->groupBy('area')->map(function (Collection $ix, string $nome) use ($areas) {
            $pontos = $areas[$nome]->n_questoes * $areas[$nome]->pontos_por_questao;
            $n = $ix->sum('tentativas');
            $valorCoberto = $ix->filter(fn ($i) => $i['tentativas'] >= self::MIN_COBERTO)->sum('valor');

            return [
                'nome' => $nome,
                'pontos' => $pontos,
                'estimado' => $ix->sum(fn ($i) => $i['valor'] * $i['dominio']),
                'cobertura' => $pontos ? $valorCoberto / $pontos : 0,
                'tentativas' => $n,
                'acerto' => $n ? $ix->sum('acertos') / $n : null,
                'sem_questoes' => $ix->every(fn ($i) => !$i['questoes']),
                'topicos' => $ix->sortBy(fn ($i) => [$i['topico']->disciplina_id, $i['topico']->posicao])->values(),
            ];
        })->sortBy(fn ($a) => $areas[$a['nome']]->posicao)->values();

        $tentativas = Tentativa::where('user_id', $perfil->user_id)->where('created_at', '>=', $data->copy()->subWeeks(8)->startOfWeek())
            ->get(['correta', 'certeza', 'motivo_erro', 'created_at']);

        return [
            'areas' => $porArea,
            'total' => ['pontos' => $porArea->sum('pontos'), 'estimado' => $porArea->sum('estimado')],
            'semanas' => $this->semanas($tentativas, $data),
            'certeza' => collect(Tentativa::CERTEZAS)->map(function ($rotulo, $k) use ($tentativas) {
                $g = $tentativas->where('certeza', $k);

                return ['rotulo' => $rotulo, 'n' => $g->count(), 'acerto' => $g->count() ? $g->where('correta', true)->count() / $g->count() : null];
            }),
            'motivos' => $tentativas->whereNotNull('motivo_erro')->countBy('motivo_erro')->sortDesc()
                ->mapWithKeys(fn ($n, $k) => [Tentativa::MOTIVOS[$k] ?? $k => $n]),
            'tentativas_total' => Tentativa::where('user_id', $perfil->user_id)->count(),
        ];
    }

    /** Últimas 8 semanas (segunda a domingo): questões feitas e acerto. */
    private function semanas(Collection $tentativas, CarbonInterface $data): array
    {
        $saida = [];
        for ($i = 7; $i >= 0; $i--) {
            $ini = $data->copy()->subWeeks($i)->startOfWeek();
            $fim = $ini->copy()->endOfWeek();
            $g = $tentativas->filter(fn ($t) => $t->created_at->between($ini, $fim));
            $saida[] = ['inicio' => $ini, 'n' => $g->count(), 'acerto' => $g->count() ? $g->where('correta', true)->count() / $g->count() : null];
        }

        return $saida;
    }
}
