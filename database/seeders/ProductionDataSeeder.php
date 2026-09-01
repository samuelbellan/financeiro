<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('dumps/production_data.json');
        if (!file_exists($jsonPath)) {
            $this->command?->error('Arquivo production_data.json não encontrado em database/dumps/.');
            return;
        }

        $content = file_get_contents($jsonPath);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->command?->error('Formato inválido em production_data.json.');
            return;
        }

        $isPgsql = DB::getDriverName() === 'pgsql';
        $totalImported = 0;

        foreach ($data as $table => $rows) {
            if (empty($rows)) {
                continue;
            }

            if (!Schema::hasTable($table)) {
                $this->command?->warn("Tabela '{$table}' não existe no banco. Pulando...");
                continue;
            }

            $count = count($rows);
            $this->command?->info("Populando '{$table}' ({$count} registros)...");

            // Process chunks
            foreach (array_chunk($rows, 100) as $chunk) {
                foreach ($chunk as &$item) {
                    foreach ($item as $key => $val) {
                        if (is_array($val)) {
                            $item[$key] = json_encode($val);
                        }
                    }
                }
                unset($item);

                // If table has primary key 'id', use upsert or insertOrIgnore
                if (Schema::hasColumn($table, 'id')) {
                    $uniqueBy = ['id'];
                    $updateColumns = array_diff(array_keys($chunk[0]), ['id']);
                    DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
                } else {
                    DB::table($table)->insertOrIgnore($chunk);
                }
            }

            // Correct auto-increment sequences for PostgreSQL
            if ($isPgsql && Schema::hasColumn($table, 'id')) {
                try {
                    DB::statement("SELECT setval(pg_get_serial_sequence('\"{$table}\"', 'id'), coalesce(max(id), 1), max(id) IS NOT null) FROM \"{$table}\"");
                } catch (\Throwable $e) {
                    // Ignore if sequence not found or table doesn't use serial
                }
            }

            $totalImported += $count;
        }

        $this->command?->info(" Sucesso! Total de {$totalImported} registros importados/atualizados.");
    }
}
