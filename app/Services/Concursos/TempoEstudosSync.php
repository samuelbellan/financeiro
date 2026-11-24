<?php

namespace App\Services\Concursos;

use App\Http\Controllers\EstudosApiController;
use App\Models\Concursos\MetaAtividade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Traz do /estudos o tempo cronometrado de cada atividade da meta.
 * As sessões ligadas a uma atividade carregam atividade_ref = "concursos:<id>"; aqui somamos os
 * minutos que contam no total do app e gravamos em meta_atividades.minutos_reais.
 * Usa a API de leitura do /estudos com token pessoal (CONCURSOS_ESTUDOS_TOKEN): por HTTP quando o /estudos
 * roda em outra URL (services.estudos.cloud_url) e, sem ela (tudo no mesmo PC), chamando a API em
 * processo, sem rede (o servidor local atende uma requisição por vez e não pode chamar a si mesmo).
 * O tempo só calibra o tamanho da meta: nunca entra no domínio.
 */
class TempoEstudosSync
{
    public const CACHE = 'concursos:tempo-estudos:ultimo';

    public function configurado(): bool
    {
        return (bool) config('concursos.estudos.token');
    }

    /**
     * @return array{atividades: int, minutos: float}
     *
     * @throws \RuntimeException se a API não responder
     */
    public function sincronizar(int $dias = 14, int $timeout = 20): array
    {
        if (!$this->configurado()) {
            throw new \RuntimeException('Defina CONCURSOS_ESTUDOS_TOKEN (token da API em /estudos → Dados) no .env.');
        }

        $registros = $this->buscar($dias, $timeout);

        $porAtividade = self::somar($registros);
        foreach ($porAtividade as $id => $min) {
            MetaAtividade::whereKey($id)->update(['minutos_reais' => $min]);
        }
        Cache::put(self::CACHE, now()->toIso8601String(), now()->addDays(30));

        return ['atividades' => count($porAtividade), 'minutos' => array_sum($porAtividade)];
    }

    /** @return list<array> dias de /api/estudos/periodo?sessoes=1 */
    private function buscar(int $dias, int $timeout): array
    {
        $consulta = ['desde' => today()->subDays($dias - 1)->toDateString(), 'ate' => today()->toDateString(), 'sessoes' => 1];
        $nuvem = rtrim((string) config('services.estudos.cloud_url'), '/');

        if ($nuvem === '') {
            $req = Request::create('/api/estudos/periodo', 'GET', $consulta);
            $req->headers->set('Authorization', 'Bearer ' . config('concursos.estudos.token'));
            $resp = app(EstudosApiController::class)->periodo($req);
            if ($resp->getStatusCode() >= 400) {
                throw new \RuntimeException("O /estudos respondeu {$resp->getStatusCode()}: " . mb_strimwidth((string) ($resp->getData(true)['message'] ?? ''), 0, 200, '…'));
            }

            return $resp->getData(true)['dias'] ?? [];
        }

        $resp = Http::withToken(config('concursos.estudos.token'))->acceptJson()->timeout($timeout)
            ->get($nuvem . '/api/estudos/periodo', $consulta);
        if (!$resp->successful()) {
            throw new \RuntimeException("O /estudos respondeu {$resp->status()}: " . mb_strimwidth((string) ($resp->json('message') ?? $resp->body()), 0, 200, '…'));
        }

        return $resp->json('dias') ?? [];
    }

    /** Sincroniza só se a última vez foi há mais de 15 min; erros são engolidos (a página não pode travar). */
    public function talvez(): void
    {
        $ultimo = Cache::get(self::CACHE);
        if (!$this->configurado() || ($ultimo && now()->diffInMinutes($ultimo, true) < 15)) {
            return;
        }
        try {
            $this->sincronizar(14, 4);
        } catch (\Throwable $e) {
            Cache::put(self::CACHE, now()->toIso8601String(), now()->addMinutes(15)); // não tenta a cada clique
            report($e);
        }
    }

    /**
     * @param  list<array{sessoes?: list<array>}>  $dias  resposta de /api/estudos/periodo?sessoes=1
     * @return array<int, float> atividade_id => minutos
     */
    public static function somar(array $dias): array
    {
        $soma = [];
        foreach ($dias as $dia) {
            foreach ($dia['sessoes'] ?? [] as $s) {
                if (!($s['conta_no_total'] ?? false) || !preg_match('/^concursos:(\d+)$/', (string) ($s['atividade_ref'] ?? ''), $m)) {
                    continue;
                }
                $soma[(int) $m[1]] = round(($soma[(int) $m[1]] ?? 0) + (float) $s['minutos_no_dia'], 1);
            }
        }

        return $soma;
    }
}
