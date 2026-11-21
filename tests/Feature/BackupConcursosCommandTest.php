<?php

namespace Tests\Feature;

use App\Console\Commands\BackupConcursosCommand;
use Tests\TestCase;

class BackupConcursosCommandTest extends TestCase
{
    private function dumps(string $inicio, int $dias): array
    {
        $d = new \DateTimeImmutable($inicio);
        $out = [];
        for ($i = 0; $i < $dias; $i++) {
            $out[] = 'G:/b/db/concursos_' . $d->modify("+{$i} days")->format('Ymd') . '_030000.dump';
        }

        return $out;
    }

    public function test_retencao_mantem_14_diarios_e_1_por_semana(): void
    {
        $arquivos = $this->dumps('2026-09-01', 90); // 01/09 a 29/11
        $remover = BackupConcursosCommand::aplicarRetencao($arquivos, 14, 8);
        $mantidos = array_values(array_diff($arquivos, $remover));

        // Os 14 mais recentes ficam
        foreach (array_slice($arquivos, -14) as $recente) {
            $this->assertContains($recente, $mantidos);
        }
        // Nada além de 14 diários + até 8 semanais
        $this->assertLessThanOrEqual(22, count($mantidos));
        // O mais antigo (01/09) sai
        $this->assertContains($arquivos[0], $remover);
        // Um por semana: não sobram dois arquivos antigos da mesma semana ISO
        $antigos = array_diff($mantidos, array_slice($arquivos, -14));
        $semanas = array_map(fn ($a) => \DateTimeImmutable::createFromFormat('Ymd', substr(basename($a), 10, 8))->format('o-W'), $antigos);
        $this->assertSame(count($semanas), count(array_unique($semanas)));
    }

    public function test_retencao_com_poucos_arquivos_nao_remove_nada(): void
    {
        $arquivos = $this->dumps('2026-11-15', 5);

        $this->assertSame([], BackupConcursosCommand::aplicarRetencao($arquivos, 14, 8));
    }

    public function test_falha_sem_pasta_configurada(): void
    {
        config(['concursos.backup.dir' => null]);

        $this->artisan('concursos:backup')->assertFailed();
    }
}
