<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackupEstudosCommandTest extends TestCase
{
    use RefreshDatabase;

    protected string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        // Pasta temporária: os testes nunca tocam nos backups reais em storage/app/backups/estudos.
        $this->dir = sys_get_temp_dir() . '/estudos_backup_test_' . Str::random(8);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/estudos_*.json') ?: []);
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_backup_writes_all_study_tables_to_json(): void
    {
        $user = User::factory()->create();
        $subjectId = (string) Str::uuid();
        DB::table('study_subjects')->insert(['id' => $subjectId, 'user_id' => $user->id, 'nome' => 'Português', 'cor' => '#4F86F7', 'ordem' => 0]);
        DB::table('study_sessions')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'subject_id' => $subjectId,
            'inicio' => '2026-10-30 13:00:00.000', 'fim' => '2026-10-30 14:00:00.000', 'origem' => 'timer', 'status' => 'ok',
        ]);

        $this->artisan('estudos:backup', ['--connection' => config('database.default'), '--dir' => $this->dir])->assertSuccessful();

        $files = glob($this->dir . '/estudos_*.json');
        $this->assertCount(1, $files);
        $json = json_decode(file_get_contents($files[0]), true);
        $this->assertSame('Português', $json['tabelas']['study_subjects'][0]['nome']);
        $this->assertCount(1, $json['tabelas']['study_sessions']);
        $this->assertSame([], $json['tabelas']['study_ddays']);
    }

    public function test_backup_keeps_only_the_most_recent_files(): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
        foreach (['20260101_000000', '20260102_000000', '20260103_000000'] as $stamp) {
            file_put_contents("{$this->dir}/estudos_{$stamp}.json", '{}');
        }

        $this->artisan('estudos:backup', ['--connection' => config('database.default'), '--keep' => 2, '--dir' => $this->dir])->assertSuccessful();

        $files = array_map('basename', glob($this->dir . '/estudos_*.json'));
        $this->assertCount(2, $files);
        $this->assertNotContains('estudos_20260102_000000.json', $files);
        $this->assertContains('estudos_20260103_000000.json', $files);
    }

    public function test_backup_fails_cleanly_when_source_is_unreachable(): void
    {
        $this->artisan('estudos:backup', ['--connection' => 'conexao_inexistente', '--dir' => $this->dir])->assertFailed();
    }

    public function test_backup_is_skipped_when_cloud_url_is_not_configured(): void
    {
        config(['database.connections.pgsql_cloud.url' => null]);

        $this->artisan('estudos:backup', ['--dir' => $this->dir])->assertSuccessful();

        $this->assertSame([], glob($this->dir . '/estudos_*.json') ?: []);
    }
}
