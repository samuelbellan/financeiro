<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GurujaMcpService;
use App\Services\GurujaPostMetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstudosGurujaPostMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function connectGuruja(User $user): void
    {
        DB::table('study_guruja_tokens')->insert([
            'user_id' => $user->id,
            'client_id' => 'mcp-test-client',
            'access_token' => 'valid-mcp-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function fakeMcpSequences(array $calls): void
    {
        $seq = Http::sequence();
        foreach ($calls as $call) {
            $historico = $call['historico'];
            $detalhe = $call['detalhe'] ?? null;

            $seq->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-03-26']], 200, ['Mcp-Session-Id' => 'sess-post-1'])
                ->push('', 202)
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 2,
                    'result' => [
                        'tools' => [
                            ['name' => 'historico_de_metas', 'description' => 'Lista metas'],
                            [
                                'name' => 'detalhe_de_uma_meta',
                                'description' => 'Detalhe de uma meta',
                                'inputSchema' => ['type' => 'object', 'properties' => ['target_id' => ['type' => 'integer']]],
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 3,
                    'result' => ['content' => [['type' => 'text', 'text' => json_encode($historico)]]],
                ], 200);

            if ($detalhe !== null) {
                $seq->push([
                    'jsonrpc' => '2.0',
                    'id' => 4,
                    'result' => ['content' => [['type' => 'text', 'text' => json_encode($detalhe)]]],
                ], 200);
            }
        }

        Http::fake([
            GurujaMcpService::MCP_ENDPOINT => $seq,
        ]);
    }

    protected function fakeMcpSequence(array $historico, ?array $detalhe = null): void
    {
        $this->fakeMcpSequences([['historico' => $historico, 'detalhe' => $detalhe]]);
    }

    public function test_sem_conexao_com_guruja_retorna_409_com_mensagem_para_conectar(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta')
            ->assertStatus(409)
            ->json();

        $this->assertStringContainsString('Conecte', $res['message']);
        $this->assertStringContainsString('/estudos/guruja/auth', $res['mcp_auth_url']);
    }

    public function test_meta_concluida_retorna_totais_destaques_horas_por_materia_horarios_e_cacheia(): void
    {
        $user = User::factory()->create();
        $this->connectGuruja($user);

        DB::table('study_settings')->insert([
            'user_id' => $user->id,
            'timezone' => 'America/Cuiaba',
            'virada_hora' => 1,
            'meta_diaria_min' => 240,
            'updated_at' => now(),
        ]);

        $subjectId = (string) Str::uuid();
        DB::table('study_subjects')->insert([
            'id' => $subjectId,
            'user_id' => $user->id,
            'nome' => 'Direito Tributário',
            'cor' => '#4F86F7',
            'ordem' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Janela em Brasília (-03:00): 2026-11-09 04:00 (-03:00 = 07:00Z = 03:00 Cuiabá) até 2026-11-11 22:00 (-03:00 = 2026-11-12 01:00Z = 21:00 Cuiabá)
        // 3 dias de estudo: 09/11, 10/11, 11/11.
        DB::table('study_sessions')->insert([
            // Dia 09/11: timer 04:00–05:30 Cuiabá (08:00–09:30Z) -> 90 min (todo antes das 7h), maior foco = 90
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'inicio' => '2026-11-09 08:00:00.000',
                'fim' => '2026-11-09 09:30:00.000',
                'origem' => 'timer',
                'status' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Dia 09/11: manual 06:30–07:30 Cuiabá (10:30–11:30Z) -> 60 min (30 min antes das 7h, 30 min depois)
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'inicio' => '2026-11-09 10:30:00.000',
                'fim' => '2026-11-09 11:30:00.000',
                'origem' => 'manual',
                'status' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Dia 10/11: timer 05:00–06:00 Cuiabá (09:00–10:00Z) -> 60 min (todo antes das 7h)
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'inicio' => '2026-11-10 09:00:00.000',
                'fim' => '2026-11-10 10:00:00.000',
                'origem' => 'timer',
                'status' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Dia 11/11: importado 14:00–16:00 Cuiabá (18:00–20:00Z) -> deve aparecer nos blocos, mas ficar de fora das estatísticas!
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'inicio' => '2026-11-11 18:00:00.000',
                'fim' => '2026-11-11 20:00:00.000',
                'origem' => 'importado',
                'status' => 'ok',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $historico = [
            'metas' => [
                ['target_id' => 640, 'ordem' => 64, 'concluida' => false, 'esta_em_andamento' => false, 'status' => 'futura', 'vezes_adiada' => 0],
                ['target_id' => 630, 'ordem' => 63, 'concluida' => false, 'esta_em_andamento' => true, 'status' => 'em_andamento', 'vezes_adiada' => 0],
                [
                    'target_id' => 620,
                    'ordem' => 62,
                    'concluida' => true,
                    'esta_em_andamento' => false,
                    'status' => 'ontime',
                    'data_inicio' => '2026-11-09T04:00:00',
                    'data_conclusao' => '2026-11-11T22:00:00',
                    'dias_atraso' => 0,
                    'vezes_adiada' => 0,
                ],
                [
                    'target_id' => 610,
                    'ordem' => 61,
                    'concluida' => true,
                    'esta_em_andamento' => false,
                    'status' => 'late',
                    'data_inicio' => '2026-11-02T04:00:00',
                    'data_conclusao' => '2026-11-09T03:00:00',
                    'dias_atraso' => 2,
                    'vezes_adiada' => 1,
                ],
            ],
        ];

        $detalhe62 = [
            'target_id' => 620,
            'ordem' => 62,
            // Totais da meta propositalmente diferentes da soma simples das atividades (inclui aprofundamentos)
            'questoes_resolvidas' => 218,
            'acertos' => 160,
            'minutos_estudados' => 1539, // 25h39
            'atividades' => [
                // 90% com 10 questões (empate com a próxima, perde no desempate)
                ['titulo' => 'Direito Tributário - Impostos Municipais Muito Longo Para Testar Truncamento', 'disciplina' => 'Direito Tributário', 'concluida' => true, 'questoes' => 10, 'acertos' => 9, 'minutos' => 100],
                // 90% com 20 questões (ganha no desempate de 90%)
                ['titulo' => 'Princípios Tributários', 'disciplina' => 'Direito Tributário', 'concluida' => true, 'questoes' => 20, 'acertos' => 18, 'minutos' => 80],
                // 95% com 20 questões (1º lugar)
                ['titulo' => 'Controle de Constitucionalidade', 'disciplina' => 'Direito Constitucional', 'concluida' => true, 'questoes' => 20, 'acertos' => 19, 'minutos' => 120],
                // 70% com 30 questões (4º lugar — fica fora dos 3 destaques)
                ['titulo' => 'Atos Administrativos', 'disciplina' => 'Direito Administrativo', 'concluida' => true, 'questoes' => 30, 'acertos' => 21, 'minutos' => 90],
                // Sem questões (não entra nos destaques)
                ['titulo' => 'Leitura Seca CF/88', 'disciplina' => 'Direito Constitucional', 'concluida' => true, 'questoes' => 0, 'acertos' => 0, 'minutos' => 39],
            ],
            'reforcos' => [
                ['titulo' => 'Reforço Tributário', 'disciplina' => 'Direito Tributário', 'concluido' => true, 'minutos' => 45],
                ['titulo' => 'Reforço Contabilidade', 'disciplina' => 'Contabilidade', 'concluido' => true, 'minutos' => 35],
            ],
        ];

        $this->fakeMcpSequence($historico, $detalhe62);

        $res = $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta')
            ->assertOk()
            ->json();

        $this->assertFalse($res['cached']);
        $this->assertNull($res['aviso']);

        // Metas disponíveis: apenas concluídas (62, 61), nunca futura (64)
        $this->assertSame([62, 61], array_column($res['metas_disponiveis'], 'ordem'));

        // Totais da meta (usando totais da meta, não soma das atividades)
        $this->assertSame(62, $res['meta']['ordem']);
        $this->assertSame('no prazo', $res['meta']['status_texto']);
        $this->assertSame('no prazo', $res['meta']['status_curto']);
        $this->assertSame('09/11 a 11/11', $res['meta']['periodo']);
        $this->assertSame(5, $res['meta']['atividades_feitas']);
        $this->assertSame(5, $res['meta']['atividades_total']);
        $this->assertSame(218, $res['meta']['questoes_resolvidas']);
        $this->assertSame(160, $res['meta']['acertos']);
        $this->assertSame(73.4, $res['meta']['pct_acerto']); // 160 / 218 = 73.394... -> 73.4
        $this->assertSame('25h39', $res['meta']['horas_formatado']);

        // Destaques: top 3 com desempate por mais questões e truncamento em 38 caracteres
        $this->assertCount(3, $res['destaques']);
        $this->assertSame('Controle de Constitucionalidade', $res['destaques'][0]['titulo']);
        $this->assertEquals(95.0, $res['destaques'][0]['pct_acerto']);
        $this->assertSame('Princípios Tributários', $res['destaques'][1]['titulo']);
        $this->assertSame(20, $res['destaques'][1]['questoes']);
        $this->assertSame(10, $res['destaques'][2]['questoes']);
        $this->assertLessThanOrEqual(38, mb_strlen($res['destaques'][2]['rotulo']));
        $this->assertStringEndsWith('…', $res['destaques'][2]['rotulo']);

        // Horas por matéria somando atividades + reforços (ordem decrescente, mantém soma mesmo divergindo de minutos_estudados)
        // Direito Tributário: 100 + 80 + 45 = 225 (3h45)
        // Direito Constitucional: 120 + 39 = 159 (2h39)
        // Direito Administrativo: 90 (1h30)
        // Contabilidade: 35 (35min)
        $this->assertSame([
            ['disciplina' => 'Direito Tributário', 'minutos' => 225, 'horas_formatado' => '3h45'],
            ['disciplina' => 'Direito Constitucional', 'minutos' => 159, 'horas_formatado' => '2h39'],
            ['disciplina' => 'Direito Administrativo', 'minutos' => 90, 'horas_formatado' => '1h30'],
            ['disciplina' => 'Contabilidade', 'minutos' => 35, 'horas_formatado' => '35min'],
        ], $res['horas_por_materia']);

        // Horários do app: 3 dias (09, 10, 11), somente timer + manual contam nas estatísticas:
        // Total non-imported = 90 + 60 + 60 = 210 min -> media_dia_min = 210 / 3 = 70.0
        // Antes das 7h = 90 + 30 + 60 = 180 min -> 180 / 210 = 85.7%
        // Inicio medio (timer): dia 09 04:00 (240), dia 10 05:00 (300) -> media 270 -> 04:30
        // Maior foco: 90 min; dias_com_estudo (timer+manual): 2 de 3; tem_importado: true
        $this->assertSame(2, $res['horarios']['dias_com_estudo']);
        $this->assertSame(3, $res['horarios']['dias_total']);
        $this->assertSame(70, (int) $res['horarios']['media_dia_min']);
        $this->assertSame('04:30', $res['horarios']['inicio_medio']);
        $this->assertSame(85.7, $res['horarios']['pct_antes_7h']);
        $this->assertSame(90, (int) $res['horarios']['maior_foco_min']);
        $this->assertTrue($res['horarios']['tem_importado']);
        $this->assertCount(3, $res['horarios']['dias']);
        $this->assertSame('seg 09', $res['horarios']['dias'][0]['rotulo']);
        $this->assertCount(1, $res['horarios']['dias'][2]['sessoes']);
        $this->assertSame('importado', $res['horarios']['dias'][2]['sessoes'][0]['origem']);

        // Histórico
        $this->assertSame(2, $res['historico']['metas_concluidas']);
        $this->assertSame(1, $res['historico']['metas_adiadas']);

        // Cache salvo em study_guruja_posts
        $this->assertDatabaseHas('study_guruja_posts', [
            'user_id' => $user->id,
            'ordem' => 62,
        ]);

        // Segunda chamada com ?ordem=62 usa o cache sem novas chamadas HTTP ao MCP
        Http::assertSentCount(5);
        $cachedRes = $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta?ordem=62')
            ->assertOk()
            ->json();

        $this->assertTrue($cachedRes['cached']);
        $this->assertSame(62, $cachedRes['meta']['ordem']);
        Http::assertSentCount(5);
    }

    public function test_meta_inexistente_ou_futura_retorna_404(): void
    {
        $user = User::factory()->create();
        $this->connectGuruja($user);

        $historico = [
            'metas' => [
                ['target_id' => 640, 'ordem' => 64, 'concluida' => false, 'esta_em_andamento' => false, 'status' => 'futura'],
                ['target_id' => 620, 'ordem' => 62, 'concluida' => true, 'status' => 'ontime'],
            ],
        ];

        $this->fakeMcpSequences([
            ['historico' => $historico],
            ['historico' => $historico],
        ]);
        $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta?ordem=999')
            ->assertNotFound();

        // Meta futura também não pode ser exposta (404)
        $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta?ordem=64')
            ->assertNotFound();
    }

    public function test_status_antecipada_e_atraso_singular_plural(): void
    {
        $user = User::factory()->create();
        $this->connectGuruja($user);

        $historico = [
            'metas' => [
                ['target_id' => 610, 'ordem' => 61, 'concluida' => true, 'status' => 'anticipated', 'data_inicio' => '2026-11-01T08:00:00', 'data_conclusao' => '2026-11-06T20:00:00', 'dias_atraso' => 0],
                ['target_id' => 600, 'ordem' => 60, 'concluida' => true, 'status' => 'late', 'data_inicio' => '2026-10-20T08:00:00', 'data_conclusao' => '2026-10-28T20:00:00', 'dias_atraso' => 1],
                ['target_id' => 590, 'ordem' => 59, 'concluida' => true, 'status' => 'late', 'data_inicio' => '2026-10-10T08:00:00', 'data_conclusao' => '2026-10-20T20:00:00', 'dias_atraso' => 3],
            ],
        ];

        $this->fakeMcpSequences([
            ['historico' => $historico, 'detalhe' => ['target_id' => 610, 'ordem' => 61, 'atividades' => [], 'reforcos' => []]],
            ['historico' => $historico, 'detalhe' => ['target_id' => 600, 'ordem' => 60, 'atividades' => [], 'reforcos' => []]],
            ['historico' => $historico, 'detalhe' => ['target_id' => 590, 'ordem' => 59, 'atividades' => [], 'reforcos' => []]],
        ]);

        $r61 = $this->actingAs($user)->getJson('/estudos/guruja/post-meta?ordem=61')->assertOk()->json();
        $this->assertSame('antecipada', $r61['meta']['status_texto']);
        $this->assertSame('antes do prazo', $r61['meta']['status_curto']);

        $r60 = $this->actingAs($user)->getJson('/estudos/guruja/post-meta?ordem=60')->assertOk()->json();
        $this->assertSame('com 1 dia de atraso', $r60['meta']['status_texto']);
        $this->assertSame('', $r60['meta']['status_curto']);

        $r59 = $this->actingAs($user)->getJson('/estudos/guruja/post-meta?ordem=59')->assertOk()->json();
        $this->assertSame('com 3 dias de atraso', $r59['meta']['status_texto']);
        $this->assertSame('', $r59['meta']['status_curto']);
    }

    public function test_meta_em_andamento_100_por_cento_mostra_aviso_e_nao_cacheia(): void
    {
        $user = User::factory()->create();
        $this->connectGuruja($user);

        $historico = [
            'metas' => [
                ['target_id' => 630, 'ordem' => 63, 'concluida' => false, 'esta_em_andamento' => true, 'status' => 'em_andamento', 'data_inicio' => '2026-11-16T08:00:00'],
                ['target_id' => 620, 'ordem' => 62, 'concluida' => true, 'status' => 'ontime', 'data_inicio' => '2026-11-09T08:00:00', 'data_conclusao' => '2026-11-15T20:00:00'],
            ],
        ];
        $detalhe63 = [
            'target_id' => 630,
            'ordem' => 63,
            'atividades' => [
                ['titulo' => 'Atividade 1', 'disciplina' => 'Direito Civil', 'concluida' => true, 'minutos' => 45],
                ['titulo' => 'Atividade 2', 'disciplina' => 'Direito Civil', 'concluida' => true, 'minutos' => 30],
            ],
            'reforcos' => [],
        ];

        $this->fakeMcpSequence($historico, $detalhe63);

        $res = $this->actingAs($user)
            ->getJson('/estudos/guruja/post-meta?ordem=63')
            ->assertOk()
            ->json();

        $this->assertSame(GurujaPostMetaService::AVISO_FINALIZAR_NO_PORTAL, $res['aviso']);
        $this->assertFalse($res['meta']['concluida']);
        $this->assertDatabaseMissing('study_guruja_posts', [
            'user_id' => $user->id,
            'ordem' => 63,
        ]);
    }

    public function test_cache_de_post_nao_vaza_entre_usuarios(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();

        DB::table('study_guruja_posts')->insert([
            'user_id' => $dono->id,
            'ordem' => 62,
            'payload' => json_encode(['meta' => ['ordem' => 62]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Outro usuário sem conexão recebe 409 mesmo com cache do dono existente
        $this->actingAs($outro)
            ->getJson('/estudos/guruja/post-meta?ordem=62')
            ->assertStatus(409);
    }
}
