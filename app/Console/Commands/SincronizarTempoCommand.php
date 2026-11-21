<?php

namespace App\Console\Commands;

use App\Services\Concursos\TempoEstudosSync;
use Illuminate\Console\Command;

/**
 * Traz do /estudos (nuvem) o tempo cronometrado de cada atividade da meta.
 *   php artisan concursos:sincronizar-tempo
 * A página da meta já faz isso sozinha (no máximo a cada 15 min); o comando serve para forçar.
 */
class SincronizarTempoCommand extends Command
{
    protected $signature = 'concursos:sincronizar-tempo {--dias=14 : quantos dias para trás}';

    protected $description = 'Atualiza o tempo real das atividades da meta a partir do cronômetro do /estudos.';

    public function handle(TempoEstudosSync $sync): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        try {
            $r = $sync->sincronizar((int) $this->option('dias'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info("{$r['atividades']} atividade(s) com tempo cronometrado, {$r['minutos']} min no total.");

        return self::SUCCESS;
    }
}
