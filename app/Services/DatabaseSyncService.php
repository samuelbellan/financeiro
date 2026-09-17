<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseSyncService
{
    /**
     * Cache key for storing the timestamp of the last successful synchronization.
     */
    public const LAST_SYNC_CACHE_KEY = 'database_last_sync_at';

    /**
     * Tables to sync in proper foreign-key dependency order.
     *
     * @var array<string>
     */
    protected array $tables = [
        'users',
        'categorias',
        'subcategorias',
        'cartoes',
        'transacoes',
        'transacao_previsoes',
        'cartao_compras',
        'cartao_parcelas',
        'cartao_previsoes',
        'study_goals',
        'study_logs',
        'salary_profiles',
        'fiscal_concursos',
        'fiscal_noticias',
        'fiscal_telegram_configs',
        'notas_fiscais',
        'nota_fiscal_itens',
        'whatsapp_logs',
        'exercises',
        'workout_plans',
        'workout_plan_items',
        'workout_sessions',
        'workout_session_exercises',
        'workout_sets',
        'running_logs',
        'running_splits',
        'stretching_logs',
        'gear_items',
        'exercise_personal_records',
        'workout_goals',
        'user_body_metrics',
    ];

    /**
     * Test whether cloud database connection is reachable.
     *
     * @return array{online: bool, error: ?string}
     */
    public function testCloudConnection(): array
    {
        try {
            DB::connection('pgsql_cloud')->select('SELECT 1');
            return ['online' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get overall synchronization status and metrics.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $connectionTest = $this->testCloudConnection();
        $lastSync = Cache::get(self::LAST_SYNC_CACHE_KEY);

        return [
            'cloud_online' => $connectionTest['online'],
            'cloud_error' => $connectionTest['error'],
            'local_driver' => DB::getDriverName(),
            'last_sync_at' => $lastSync,
        ];
    }

    /**
     * Pull data from Cloud to Local.
     *
     * @return array{success: bool, total_rows: int, tables: array<string, int>, message: string}
     */
    public function pull(): array
    {
        return $this->transferData(
            sourceConnection: 'pgsql_cloud',
            destinationConnection: config('database.default'),
            directionName: 'Nuvem ➔ Local (PULL)'
        );
    }

    /**
     * Push data from Local to Cloud.
     *
     * @return array{success: bool, total_rows: int, tables: array<string, int>, message: string}
     */
    public function push(): array
    {
        return $this->transferData(
            sourceConnection: config('database.default'),
            destinationConnection: 'pgsql_cloud',
            directionName: 'Local ➔ Nuvem (PUSH)'
        );
    }

    /**
     * Full bidirectional synchronization (PULL then PUSH).
     *
     * @return array{success: bool, pulled: int, pushed: int, message: string}
     */
    public function sync(): array
    {
        $check = $this->testCloudConnection();
        if (!$check['online']) {
            return [
                'success' => false,
                'pulled' => 0,
                'pushed' => 0,
                'message' => 'Falha ao conectar com o banco na nuvem: ' . $check['error'],
            ];
        }

        $pullResult = $this->pull();
        if (!$pullResult['success']) {
            return [
                'success' => false,
                'pulled' => 0,
                'pushed' => 0,
                'message' => 'Erro ao puxar dados da nuvem: ' . $pullResult['message'],
            ];
        }

        $pushResult = $this->push();
        if (!$pushResult['success']) {
            return [
                'success' => false,
                'pulled' => $pullResult['total_rows'],
                'pushed' => 0,
                'message' => 'Erro ao enviar dados para a nuvem: ' . $pushResult['message'],
            ];
        }

        Cache::put(self::LAST_SYNC_CACHE_KEY, now()->toIso8601String());

        return [
            'success' => true,
            'pulled' => $pullResult['total_rows'],
            'pushed' => $pushResult['total_rows'],
            'message' => "Sincronização completa realizada com sucesso! ({$pullResult['total_rows']} baixados, {$pushResult['total_rows']} enviados)",
        ];
    }

    /**
     * Core transfer algorithm supporting PostgreSQL and SQLite.
     *
     * @param string $sourceConnection
     * @param string $destinationConnection
     * @param string $directionName
     * @return array{success: bool, total_rows: int, tables: array<string, int>, message: string}
     */
    protected function transferData(string $sourceConnection, string $destinationConnection, string $directionName): array
    {
        $sourceDb = DB::connection($sourceConnection);
        $destDb = DB::connection($destinationConnection);
        $isDestPgsql = $destDb->getDriverName() === 'pgsql';

        if ($sourceConnection === $destinationConnection || (
            !empty($sourceDb->getConfig('host')) &&
            $sourceDb->getConfig('host') === $destDb->getConfig('host') &&
            $sourceDb->getConfig('database') === $destDb->getConfig('database')
        )) {
            return [
                'success' => true,
                'total_rows' => 0,
                'tables' => [],
                'message' => 'Origem e destino apontam para a mesma base de dados. Sincronização não necessária.',
            ];
        }

        $totalRows = 0;
        $tableCounts = [];

        try {
            foreach ($this->tables as $table) {
                // Checa se a tabela existe na base local (que é rápida)
                $localDb = ($sourceConnection === config('database.default')) ? $sourceDb : $destDb;
                if (!$localDb->getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                try {
                    $query = $sourceDb->table($table);
                    if ($sourceConnection === config('database.default')) {
                        if ($sourceDb->getSchemaBuilder()->hasColumn($table, 'id')) {
                            $query->orderBy('id');
                        }
                    } else {
                        // Quando a fonte é remota, ordena por id direto
                        $query->orderBy('id');
                    }
                    $rows = $query->get()->map(function ($row) {
                        return (array) $row;
                    })->toArray();
                } catch (\Throwable $e) {
                    // Tabela não existe na fonte ou coluna id ausente, tenta sem order
                    try {
                        $rows = $sourceDb->table($table)->get()->map(function ($row) {
                            return (array) $row;
                        })->toArray();
                    } catch (\Throwable) {
                        continue;
                    }
                }

                if (empty($rows)) {
                    continue;
                }

                $destHasId = in_array('id', array_keys($rows[0]), true);

                // Process in chunks of 100
                foreach (array_chunk($rows, 100) as $chunk) {
                    // Normalize boolean and array/json columns
                    foreach ($chunk as &$item) {
                        foreach ($item as $k => $v) {
                            if (is_array($v)) {
                                $item[$k] = json_encode($v);
                            }
                        }
                    }
                    unset($item);

                    if ($destHasId) {
                        $updateColumns = array_diff(array_keys($chunk[0]), ['id']);
                        $destDb->table($table)->upsert($chunk, ['id'], $updateColumns);
                    } else {
                        $destDb->table($table)->insertOrIgnore($chunk);
                    }
                }

                // If destination is PostgreSQL, fix sequence auto-increment
                if ($isDestPgsql && $destHasId) {
                    try {
                        $destDb->statement("SELECT setval(pg_get_serial_sequence('\"{$table}\"', 'id'), coalesce(max(id), 1), max(id) IS NOT null) FROM \"{$table}\"");
                    } catch (\Throwable) {
                        // Ignore if table does not use serial sequence
                    }
                }

                $count = count($rows);
                $tableCounts[$table] = $count;
                $totalRows += $count;
            }

            Cache::put(self::LAST_SYNC_CACHE_KEY, now()->toIso8601String());

            return [
                'success' => true,
                'total_rows' => $totalRows,
                'tables' => $tableCounts,
                'message' => "Operação {$directionName} finalizada com sucesso. Total de registros processados: {$totalRows}.",
            ];
        } catch (\Throwable $e) {
            Log::error("Erro durante {$directionName}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'total_rows' => $totalRows,
                'tables' => $tableCounts,
                'message' => "Erro em {$directionName}: " . $e->getMessage(),
            ];
        }
    }
}
