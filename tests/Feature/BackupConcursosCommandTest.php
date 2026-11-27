<?php

namespace Tests\Feature;

use App\Console\Commands\BackupConcursosCommand;
use Illuminate\Support\Facades\Http;
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
        config(['concursos.backup.recentes' => 14, 'concursos.backup.diarios' => 0, 'concursos.backup.semanais' => 8]);
        $arquivos = $this->dumps('2026-09-01', 90); // 01/09 a 29/11
        $remover = BackupConcursosCommand::aplicarRetencao($arquivos);
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

        $this->assertSame([], BackupConcursosCommand::aplicarRetencao($arquivos));
    }

    public function test_retencao_de_hora_em_hora_guarda_24_recentes_mais_diarios_e_semanais(): void
    {
        $arquivos = [];
        $d = new \DateTimeImmutable('2026-09-01 00:00:00');
        for ($i = 0; $i < 60 * 24; $i++) {
            $arquivos[] = 'G:/b/db/concursos_' . $d->modify("+{$i} hours")->format('Ymd_His') . '.dump';
        }
        $mantidos = array_values(array_diff($arquivos, BackupConcursosCommand::aplicarRetencao($arquivos)));

        foreach (array_slice($arquivos, -24) as $recente) {
            $this->assertContains($recente, $mantidos);
        }
        $this->assertLessThanOrEqual(24 + 14 + 8, count($mantidos));
        $this->assertNotContains($arquivos[0], $mantidos);
    }

    public function test_falha_sem_pasta_configurada_e_avisa_no_telegram(): void
    {
        config(['concursos.backup.dir' => null, 'telegram.bot_token' => 'x', 'telegram.allowed_chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->artisan('concursos:backup')->assertFailed();

        Http::assertSent(fn ($r) => str_contains($r['text'], 'Backup falhou: banco de concursos') && $r['chat_id'] === '42');
    }
}
