<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Banco de concursos (SQLite em memória nos testes) zerado e migrado a cada teste.
 * O RefreshDatabase só reaproveita/migra a conexão padrão; esta é uma segunda conexão.
 */
trait UsaBancoConcursos
{
    protected function setUpBancoConcursos(): void
    {
        DB::purge('concursos_pgsql');
        Artisan::call('migrate:fresh', [
            '--database' => 'concursos_pgsql',
            '--path' => 'database/migrations/concursos',
            '--force' => true,
        ]);
    }
}
