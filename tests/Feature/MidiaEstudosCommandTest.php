<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MidiaEstudosCommandTest extends TestCase
{
    protected string $pasta = 'teste-midia-phpunit';

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/midias/' . $this->pasta));
        parent::tearDown();
    }

    protected function write(array $data): void
    {
        $dir = storage_path('app/midias/' . $this->pasta);
        File::ensureDirectoryExists($dir);
        file_put_contents($dir . '/dados.json', json_encode($data));
    }

    protected function app(): array
    {
        return [
            'periodo' => ['dias' => 2], 'total_min' => 300, 'anterior_total_min' => 200, 'dias_estudados' => 2,
            'media_dia_min' => 150, 'sequencia_dias' => 12, 'total_historico_min' => 60000, 'alvo_horas' => 2000,
            'por_dia' => ['2026-11-01' => 100, '2026-11-02' => 200],
            'por_materia' => [['nome' => 'TI', 'cor' => '#8E24AA', 'min' => 300]],
            'por_tipo' => [['tipo' => 'teoria', 'nome' => 'Teoria / leitura', 'min' => 300]],
            'ddays' => [['titulo' => 'Prova', 'data' => '2027-03-14', 'dias' => 120]],
        ];
    }

    public function test_weekly_carousel_renders_all_slides_with_handle(): void
    {
        $this->write([
            'tipo' => 'semanal', 'titulo' => 'Meta 59', 'subtitulo' => '25/10 – 02/11', 'selo' => 'concluída', 'app' => $this->app(),
            'guruja' => ['questoes' => 10, 'acertos' => 8, 'disciplinas' => [['nome' => 'TI', 'questoes' => 10, 'acertos' => 8, 'percentual' => 80]]],
            'destaques' => ['ponto_alto' => 'TI 80%', 'foco' => 'Português'], 'legenda' => 'Legenda',
        ]);

        $this->artisan('estudos:midia', ['pasta' => $this->pasta, '--sem-png' => true])->assertSuccessful();

        $dir = storage_path('app/midias/' . $this->pasta);
        foreach (['01-capa', '02-materias', '03-questoes', '04-story-semanal'] as $slide) {
            $html = file_get_contents("{$dir}/{$slide}.html");
            $this->assertStringContainsString('@samubellan', $html);
        }
        $this->assertStringContainsString('5h00', file_get_contents("{$dir}/01-capa.html"));
        $this->assertStringContainsString('+1h40 vs meta anterior', file_get_contents("{$dir}/01-capa.html"));
        $this->assertStringContainsString('Rumo às 2.000h', file_get_contents("{$dir}/01-capa.html"));
        $this->assertStringContainsString('80%', file_get_contents("{$dir}/03-questoes.html"));
        $this->assertSame('Legenda', file_get_contents("{$dir}/legenda.txt"));
    }

    public function test_weekly_without_guruja_is_refused_but_daily_works(): void
    {
        $this->write(['tipo' => 'semanal', 'app' => $this->app()]);
        $this->artisan('estudos:midia', ['pasta' => $this->pasta, '--sem-png' => true])->assertFailed();

        $this->write(['tipo' => 'diario', 'titulo' => 'Sábado', 'app' => $this->app()]);
        $this->artisan('estudos:midia', ['pasta' => $this->pasta, '--sem-png' => true])->assertSuccessful();
        $this->assertFileExists(storage_path('app/midias/' . $this->pasta . '/01-story-diario.html'));
    }

    public function test_negative_comparison_is_hidden(): void
    {
        $app = ['total_min' => 100, 'anterior_total_min' => 300] + $this->app();
        $this->write(['tipo' => 'diario', 'titulo' => 'Sábado', 'app' => $app]);
        $this->artisan('estudos:midia', ['pasta' => $this->pasta, '--sem-png' => true])->assertSuccessful();
        $this->assertStringNotContainsString('vs ontem', file_get_contents(storage_path('app/midias/' . $this->pasta . '/01-story-diario.html')));
    }

    public function test_rejects_path_traversal(): void
    {
        $this->artisan('estudos:midia', ['pasta' => '../segredos', '--sem-png' => true])->assertFailed();
    }
}
