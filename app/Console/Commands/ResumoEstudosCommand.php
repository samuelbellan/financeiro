<?php

namespace App\Console\Commands;

use App\Services\StudySummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resumo (JSON) de um período de estudo, usado pelo gerador de mídias do Instagram (skill /post-estudos).
 * Somente leitura. Ver docs/sabatina-2026-11-08-projecao-e-midias.md.
 *
 *   php artisan estudos:resumo --dia=2026-11-07
 *   php artisan estudos:resumo --inicio="2026-10-25 10:47" --fim="2026-11-02 08:01"   (datas locais, ex.: meta da Guruja)
 */
class ResumoEstudosCommand extends Command
{
    protected $signature = 'estudos:resumo
                            {--dia= : Dia de estudo (Y-m-d); respeita a virada do dia}
                            {--inicio= : Início do período (horário local do usuário)}
                            {--fim= : Fim do período (horário local do usuário)}
                            {--comparar-inicio= : Início do período de comparação (padrão: mesmo tamanho, logo antes)}
                            {--comparar-fim= : Fim do período de comparação}
                            {--usuario= : E-mail ou id do usuário (padrão: o dono do sistema)}
                            {--connection=pgsql_cloud : Conexão de origem dos dados}
                            {--saida= : Arquivo onde gravar o JSON (padrão: imprime na tela)}';

    protected $description = 'Gera o resumo (JSON) de um dia ou período de estudo para as mídias do Instagram.';

    public function handle(): int
    {
        $connection = $this->option('connection');
        if ($connection === 'pgsql_cloud' && empty(config('database.connections.pgsql_cloud.url'))) {
            $this->error('CLOUD_DATABASE_URL não configurada no .env: os estudos ficam na nuvem.');
            return self::FAILURE;
        }

        try {
            $db = DB::connection($connection);
            $userId = $this->resolveUser($db);
        } catch (\Throwable $e) {
            $this->error('Não foi possível ler os estudos: ' . $e->getMessage());
            return self::FAILURE;
        }
        if (!$userId) {
            $this->error('Usuário não encontrado.');
            return self::FAILURE;
        }

        $service = new StudySummaryService($db, $userId);

        if ($day = $this->option('dia')) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                $this->error('--dia deve estar no formato Y-m-d.');
                return self::FAILURE;
            }
            [$from, $to] = $service->dayBounds($day);
            $previous = $service->dayBounds(CarbonImmutable::parse($day)->subDay()->format('Y-m-d'));
            $kind = 'diario';
        } elseif ($this->option('inicio') && $this->option('fim')) {
            $from = $service->localToMs($this->option('inicio'));
            $to = $service->localToMs($this->option('fim'));
            if ($to <= $from) {
                $this->error('--fim deve ser depois de --inicio.');
                return self::FAILURE;
            }
            $previous = $this->option('comparar-inicio') && $this->option('comparar-fim')
                ? [$service->localToMs($this->option('comparar-inicio')), $service->localToMs($this->option('comparar-fim'))]
                : null;
            $kind = 'periodo';
        } else {
            $this->error('Informe --dia ou --inicio e --fim.');
            return self::FAILURE;
        }

        $json = json_encode(['tipo' => $kind] + $service->summarize($from, $to, $previous), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($file = $this->option('saida')) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            file_put_contents($file, $json);
            $this->info("Resumo salvo em {$file}");
        } else {
            $this->line($json);
        }
        return self::SUCCESS;
    }

    protected function resolveUser($db): ?int
    {
        $user = $this->option('usuario');
        $query = $db->table('users');
        if ($user === null) {
            return $query->where('is_owner', true)->value('id') ?? $db->table('users')->orderBy('id')->value('id');
        }
        return ctype_digit((string) $user) ? $query->where('id', (int) $user)->value('id') : $query->where('email', $user)->value('id');
    }
}
