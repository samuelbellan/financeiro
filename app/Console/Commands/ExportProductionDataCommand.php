<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExportProductionDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-production';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta todos os dados do banco atual para dumps (JSON e SQL) e prepara o Seeder de produção.';

    /**
     * Tables to export in proper foreign key dependency order.
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
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando exportação dos dados de produção...');

        $dumpsDir = database_path('dumps');
        if (!is_dir($dumpsDir)) {
            mkdir($dumpsDir, 0755, true);
        }

        $exportData = [];
        $totalRows = 0;

        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Tabela '{$table}' não existe no banco atual. Pulando...");
                continue;
            }

            $query = DB::table($table);
            if (Schema::hasColumn($table, 'id')) {
                $query->orderBy('id');
            }

            $rows = $query->get()->map(function ($row) {
                return (array) $row;
            })->toArray();

            $exportData[$table] = $rows;
            $count = count($rows);
            $totalRows += $count;
            $this->line("  - <info>{$table}</info>: {$count} registros");
        }

        $this->newLine();
        $this->info("Total de registros exportados: {$totalRows}");

        // 1. JSON Dump
        $jsonPath = $dumpsDir . '/production_data.json';
        file_put_contents(
            $jsonPath,
            json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $this->info(" Arquivo JSON gerado: database/dumps/production_data.json");

        // 2. PostgreSQL SQL Dump
        $sqlDump = "-- PostgreSQL Production Data Dump\n";
        $sqlDump .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Total rows: " . $totalRows . "\n\n";
        $sqlDump .= "BEGIN;\n\n";

        foreach ($this->tables as $table) {
            if (!isset($exportData[$table]) || empty($exportData[$table])) {
                continue;
            }

            $rows = $exportData[$table];
            $sqlDump .= "-- Table: \"{$table}\" (" . count($rows) . " rows)\n";
            $columns = array_keys($rows[0]);
            $colsList = implode(', ', array_map(fn($c) => "\"{$c}\"", $columns));

            foreach ($rows as $row) {
                $valList = [];
                foreach ($columns as $c) {
                    $val = $row[$c];
                    if (is_null($val)) {
                        $valList[] = 'NULL';
                    } elseif (is_bool($val)) {
                        $valList[] = $val ? 'TRUE' : 'FALSE';
                    } elseif (is_int($val) || (is_numeric($val) && !is_string($val))) {
                        $valList[] = $val;
                    } else {
                        $escaped = str_replace("'", "''", (string)$val);
                        $valList[] = "'{$escaped}'";
                    }
                }
                $sqlDump .= "INSERT INTO \"{$table}\" ({$colsList}) VALUES (" . implode(', ', $valList) . ") ON CONFLICT (id) DO NOTHING;\n";
            }

            // PostgreSQL sequence update
            if (Schema::hasColumn($table, 'id')) {
                $sqlDump .= "SELECT setval(pg_get_serial_sequence('\"{$table}\"', 'id'), coalesce(max(id), 1), max(id) IS NOT null) FROM \"{$table}\";\n";
            }
            $sqlDump .= "\n";
        }

        $sqlDump .= "COMMIT;\n";

        $sqlPath = $dumpsDir . '/production_dump.sql';
        file_put_contents($sqlPath, $sqlDump);
        $this->info(" Arquivo SQL gerado: database/dumps/production_dump.sql");

        $this->newLine();
        $this->info(" Exportação concluída com sucesso!");

        return 0;
    }
}
