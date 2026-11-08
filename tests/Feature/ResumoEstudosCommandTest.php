<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StudySummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResumoEstudosCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $port;

    protected string $ti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_owner' => true]);
        DB::table('study_settings')->insert([
            'user_id' => $this->user->id, 'virada_hora' => 1, 'limite_suspeita_min' => 180,
            'timezone' => 'America/Sao_Paulo', 'alvo_horas' => 2000, 'updated_at' => now(),
        ]);
        $this->port = $this->subject('Português', '#4F86F7', 0);
        $this->ti = $this->subject('TI', '#9B5DE5', 1);

        // Horários em UTC (São Paulo = UTC-3). Virada do dia às 01:00 local = 04:00 UTC.
        $this->addSession($this->port, '2026-11-06 13:00', '2026-11-06 15:00', 'questoes');            // 06/11 10h–12h local
        $this->addSession($this->ti, '2026-11-08 02:00', '2026-11-08 05:00', 'teoria');                 // 07/11 23h → 08/11 02h local
        $this->addSession($this->ti, '2026-11-07 12:00', '2026-11-07 13:00', 'revisao', ['deleted_at' => '2026-11-07 14:00']);
        $this->addSession($this->port, '2026-11-07 15:00', '2026-11-07 16:00', 'teoria', ['status' => 'descartada']);
        DB::table('study_ddays')->insert(['id' => (string) Str::uuid(), 'user_id' => $this->user->id, 'titulo' => 'Prova', 'data' => '2026-11-17']);
    }

    protected function subject(string $nome, string $cor, int $ordem): string
    {
        $id = (string) Str::uuid();
        DB::table('study_subjects')->insert(['id' => $id, 'user_id' => $this->user->id, 'nome' => $nome, 'cor' => $cor, 'ordem' => $ordem]);
        return $id;
    }

    protected function addSession(string $subject, string $inicio, ?string $fim, string $tipo, array $extra = []): void
    {
        DB::table('study_sessions')->insert($extra + [
            'id' => (string) Str::uuid(), 'user_id' => $this->user->id, 'subject_id' => $subject,
            'inicio' => $inicio . ':00.000', 'fim' => $fim ? $fim . ':00.000' : null, 'origem' => 'timer', 'status' => 'ok', 'tipo' => $tipo,
        ]);
    }

    protected function service(): StudySummaryService
    {
        return new StudySummaryService(DB::connection(), $this->user->id, CarbonImmutable::parse('2026-11-08 15:00', 'UTC'));
    }

    public function test_daily_summary_splits_sessions_at_the_day_turnover(): void
    {
        $s = $this->service();
        [$from, $to] = $s->dayBounds('2026-11-07');
        $r = $s->summarize($from, $to, $s->dayBounds('2026-11-06'));

        $this->assertSame(120, $r['total_min']);           // 23h–01h local; 01h–02h já é dia 08
        $this->assertSame(120, $r['anterior_total_min']);  // 06/11
        $this->assertSame([['nome' => 'TI', 'cor' => '#9B5DE5', 'min' => 120]], $r['por_materia']);
        $this->assertSame('teoria', $r['por_tipo'][0]['tipo']);
        $this->assertSame(2, $r['sequencia_dias']);        // 06 e 07
        $this->assertSame(2000, $r['alvo_horas']);
        $this->assertSame(300, $r['total_historico_min']); // ignora excluída e descartada
        $this->assertSame([['titulo' => 'Prova', 'data' => '2026-11-17', 'dias' => 10]], $r['ddays']);
    }

    public function test_period_summary_uses_local_times_and_counts_days(): void
    {
        $s = $this->service();
        $r = $s->summarize($s->localToMs('2026-11-06 00:00'), $s->localToMs('2026-11-08 12:00'));

        $this->assertSame(300, $r['total_min']);
        $this->assertSame(['2026-11-05' => 0, '2026-11-06' => 120, '2026-11-07' => 120, '2026-11-08' => 60], $r['por_dia']);
        $this->assertSame(3, $r['dias_estudados']);
        $this->assertSame(180, $r['por_materia'][0]['min']); // TI
        $this->assertSame(['dia' => '2026-11-06', 'min' => 120], $r['melhor_dia']);
    }

    public function test_command_prints_json_for_a_day(): void
    {
        $file = sys_get_temp_dir() . '/resumo_' . Str::random(6) . '.json';
        $this->artisan('estudos:resumo', ['--dia' => '2026-11-06', '--connection' => config('database.default'), '--saida' => $file])
            ->assertSuccessful();
        $json = json_decode(file_get_contents($file), true);
        @unlink($file);
        $this->assertSame('diario', $json['tipo']);
        $this->assertSame(120, $json['total_min']);
        $this->assertSame('Questões', $json['por_tipo'][0]['nome']);
    }

    public function test_command_requires_a_period(): void
    {
        $this->artisan('estudos:resumo', ['--connection' => config('database.default')])->assertFailed();
        $this->artisan('estudos:resumo', ['--inicio' => '2026-11-07 10:00', '--fim' => '2026-11-06 10:00', '--connection' => config('database.default')])->assertFailed();
    }
}
