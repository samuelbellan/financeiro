<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GurujaMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EstudosGurujaMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guruja_endpoint_retorna_atributos_mcp(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/estudos/guruja')->assertOk()->json();
        $this->assertFalse($res['mcp_conectado']);
        $this->assertStringContainsString('/estudos/guruja/auth', $res['mcp_auth_url']);
        $this->assertStringContainsString('/estudos/guruja/desconectar', $res['mcp_desconectar_url']);

        DB::table('study_guruja_tokens')->insert([
            'user_id' => $user->id,
            'client_id' => 'mcp-test-client',
            'access_token' => 'access-token-123',
            'refresh_token' => 'refresh-token-123',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res2 = $this->actingAs($user)->getJson('/estudos/guruja')->assertOk()->json();
        $this->assertTrue($res2['mcp_conectado']);
        $this->assertTrue($res2['atualizar_disponivel']);
    }

    public function test_guruja_auth_redireciona_para_endpoint_oauth_com_resource(): void
    {
        $user = User::factory()->create();

        Http::fake([
            GurujaMcpService::REGISTER_ENDPOINT => Http::response([
                'client_id' => 'mcp-registered-client',
            ], 201),
        ]);

        $res = $this->actingAs($user)->get('/estudos/guruja/auth');
        $res->assertRedirect();
        $target = $res->headers->get('Location');
        $this->assertStringStartsWith(GurujaMcpService::AUTH_ENDPOINT, $target);
        $this->assertStringContainsString('response_type=code', $target);
        $this->assertStringContainsString('code_challenge=', $target);
        $this->assertStringContainsString('client_id=mcp-registered-client', $target);
        $this->assertStringContainsString('resource=' . urlencode(GurujaMcpService::MCP_ENDPOINT), $target);
    }

    public function test_guruja_callback_troca_code_e_salva_token(): void
    {
        $user = User::factory()->create();
        $state = 'test-state-string-123';
        $verifier = 'test-verifier-string-456';
        $redirectUri = route('estudos.guruja.callback');

        Cache::put("guruja_oauth:{$state}", [
            'user_id' => $user->id,
            'verifier' => $verifier,
            'client_id' => 'mcp-client-xyz',
            'redirect_uri' => $redirectUri,
        ], now()->addMinutes(10));

        Http::fake([
            GurujaMcpService::TOKEN_ENDPOINT => Http::response([
                'access_token' => 'test-mcp-access-token',
                'refresh_token' => 'test-mcp-refresh-token',
                'expires_in' => 3600,
            ], 200),
            GurujaMcpService::MCP_ENDPOINT => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-03-26']], 200)
                ->push('', 202)
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => [['name' => 'obter_meta_atual']]]], 200)
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 3,
                    'result' => [
                        'content' => [['type' => 'text', 'text' => json_encode(['target_id' => 1, 'atividades' => []])]],
                    ],
                ], 200),
        ]);

        $res = $this->actingAs($user)->get("/estudos/guruja/callback?code=valid-code-789&state={$state}");
        $res->assertRedirect(route('estudos.index') . '#guruja-conectado');

        $this->assertDatabaseHas('study_guruja_tokens', [
            'user_id' => $user->id,
            'access_token' => 'test-mcp-access-token',
            'refresh_token' => 'test-mcp-refresh-token',
        ]);
    }

    public function test_guruja_callback_com_erro_redireciona_com_hash_erro(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->get('/estudos/guruja/callback?error=access_denied&error_description=Usuario+recusou');
        $res->assertRedirect(route('estudos.index') . '#guruja-erro=access_denied');
    }

    public function test_guruja_desconectar_remove_tokens(): void
    {
        $user = User::factory()->create();

        DB::table('study_guruja_tokens')->insert([
            'user_id' => $user->id,
            'client_id' => 'mcp-client',
            'access_token' => 'tok-abc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->postJson('/estudos/guruja/desconectar')->assertOk();

        $this->assertDatabaseMissing('study_guruja_tokens', [
            'user_id' => $user->id,
        ]);
    }

    public function test_guruja_importar_salva_meta_manual_e_normaliza(): void
    {
        $user = User::factory()->create();

        $payload = [
            'target_id' => 12345,
            'ordem' => 42,
            'atividades' => [
                [
                    'target_activity_id' => 101,
                    'codigo_atividade' => 'DIR.01.100',
                    'titulo' => 'Controle de Constitucionalidade',
                    'disciplina' => 'Direito Constitucional',
                    'tipo' => 'Teoria',
                    'concluido' => false,
                ],
                [
                    'target_activity_id' => 102,
                    'codigo_atividade' => 'DIR.01.102',
                    'titulo' => 'Questões Discursivas',
                    'disciplina' => 'Direito Tributário',
                    'tipo' => 'Questões',
                    'concluido' => true,
                    'questoes' => 15,
                    'acertos' => 12,
                ],
            ],
            'reforcos' => [
                [
                    'target_activity_id' => 201,
                    'titulo' => 'Reforço Lei 8.666 vs 14.133',
                    'disciplina' => 'Direito Administrativo',
                    'concluido' => false,
                ],
            ],
        ];

        $res = $this->actingAs($user)->postJson('/estudos/guruja/importar', [
            'payload' => json_encode($payload),
        ])->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertSame(12345, $res['meta']['target_id']);
        $this->assertCount(2, $res['meta']['atividades']);
        $this->assertCount(1, $res['meta']['reforcos']);

        $this->assertDatabaseHas('study_guruja_metas', [
            'user_id' => $user->id,
        ]);

        $dbRow = DB::table('study_guruja_metas')->where('user_id', $user->id)->first();
        $saved = json_decode($dbRow->payload, true);
        $this->assertSame(12345, $saved['target_id']);
        $this->assertSame('Controle de Constitucionalidade', $saved['atividades'][0]['titulo']);
    }

    public function test_guruja_importar_rejeita_json_invalido(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/estudos/guruja/importar', [
            'payload' => '{invalido...',
        ])->assertStatus(422);

        $this->actingAs($user)->postJson('/estudos/guruja/importar', [
            'payload' => '{"foo": "bar"}',
        ])->assertStatus(422);
    }

    public function test_guruja_atualizar_via_mcp_fluxo_duas_etapas_com_sse_e_estimativas(): void
    {
        $user = User::factory()->create();

        DB::table('study_guruja_tokens')->insert([
            'user_id' => $user->id,
            'client_id' => 'mcp-test-client',
            'access_token' => 'valid-mcp-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $historicoJson = json_encode([
            'metas' => [
                ['target_id' => 620, 'ordem' => 62, 'esta_em_andamento' => true, 'status' => 'em_andamento', 'data_inicio' => '2026-11-16T08:00:00', 'dias_atraso' => 1],
                ['target_id' => 610, 'ordem' => 61, 'esta_em_andamento' => false, 'status' => 'concluida'],
                ['target_id' => 600, 'ordem' => 60, 'esta_em_andamento' => false, 'status' => 'concluida'],
            ],
        ]);

        $detalheAtualJson = json_encode([
            'target_id' => 620,
            'ordem' => 62,
            'status' => 'em_andamento',
            'data_inicio' => '2026-11-16T08:00:00',
            'dias_atraso' => 1,
            'atividades' => [
                [
                    'target_activity_id' => 1001,
                    'codigo_atividade' => 'DIR.01.1001',
                    'titulo' => 'Direitos Individuais e Coletivos',
                    'disciplina' => 'Direito Constitucional',
                    'tipo' => 'Teoria',
                    'concluida' => false,
                    'ignorada' => false,
                ],
            ],
            'reforcos' => [
                [
                    'target_activity_id' => 2001,
                    'titulo' => 'Reforço Art. 5º',
                    'disciplina' => 'Direito Constitucional',
                    'instrucao' => 'Revisar remédios',
                    'concluido' => false,
                ],
            ],
        ]);

        $detalheHist1 = json_encode([
            'target_id' => 610,
            'ordem' => 61,
            'atividades' => [
                ['titulo' => 'A1', 'tipo' => 'Teoria', 'concluida' => true, 'minutos' => 60],
                ['titulo' => 'A2', 'tipo' => 'Teoria', 'concluida' => true, 'minutos' => 80],
                ['titulo' => 'A3', 'tipo' => 'Questões', 'concluida' => true, 'minutos' => 45],
            ],
            'reforcos' => [
                ['titulo' => 'R1', 'concluido' => true, 'minutos' => 20],
            ],
        ]);

        $detalheHist2 = json_encode([
            'target_id' => 600,
            'ordem' => 60,
            'atividades' => [
                ['titulo' => 'B1', 'tipo' => 'Teoria', 'concluida' => true, 'minutos' => 100],
            ],
            'reforcos' => [
                ['titulo' => 'R2', 'concluido' => true, 'minutos' => 30],
            ],
        ]);

        $sseDetalheAtual = "event: message\ndata: " . json_encode([
            'jsonrpc' => '2.0',
            'id' => 4,
            'result' => [
                'content' => [['type' => 'text', 'text' => $detalheAtualJson]],
            ],
        ]) . "\n\n";

        Http::fake([
            GurujaMcpService::MCP_ENDPOINT => Http::sequence()
                // 1. initialize (retorna Mcp-Session-Id)
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-03-26']], 200, ['Mcp-Session-Id' => 'sess-guruja-123'])
                // 2. notifications/initialized
                ->push('', 202)
                // 3. tools/list
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
                // 4. historico_de_metas
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 3,
                    'result' => ['content' => [['type' => 'text', 'text' => $historicoJson]]],
                ], 200)
                // 5. detalhe_de_uma_meta (meta atual, respondida em SSE)
                ->push($sseDetalheAtual, 200, ['Content-Type' => 'text/event-stream'])
                // 6. detalhe_de_uma_meta (meta concluída 610)
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 5,
                    'result' => ['content' => [['type' => 'text', 'text' => $detalheHist1]]],
                ], 200)
                // 7. detalhe_de_uma_meta (meta concluída 600)
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 6,
                    'result' => ['content' => [['type' => 'text', 'text' => $detalheHist2]]],
                ], 200),
        ]);

        $res = $this->actingAs($user)->postJson('/estudos/guruja/atualizar')->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertTrue($res['via_mcp']);
        $this->assertSame(620, $res['meta']['target_id']);
        $this->assertSame('2026-11-16T08:00:00-03:00', $res['meta']['data_inicio']);
        $this->assertSame('Direitos Individuais e Coletivos', $res['meta']['atividades'][0]['titulo']);
        $this->assertSame(80, $res['meta']['estimativas']['Teoria']);
        $this->assertSame(45, $res['meta']['estimativas']['Questões']);
        $this->assertSame(25, $res['meta']['estimativas']['Reforço']);

        // Garante que Mcp-Session-Id foi reenviado após o initialize
        Http::assertSent(function (Request $req) {
            return ($req->data()['method'] ?? '') === 'tools/list'
                && $req->header('Mcp-Session-Id') === ['sess-guruja-123']
                && str_contains(implode(',', $req->header('Accept')), 'text/event-stream');
        });

        $this->assertDatabaseHas('study_guruja_metas', ['user_id' => $user->id]);
    }

    public function test_guruja_atualizar_renova_token_automaticamente_apos_401(): void
    {
        $user = User::factory()->create();

        DB::table('study_guruja_tokens')->insert([
            'user_id' => $user->id,
            'client_id' => 'mcp-test-client',
            'access_token' => 'stale-token',
            'refresh_token' => 'valid-refresh-token',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $metaJson = json_encode([
            'target_id' => 777,
            'ordem' => 70,
            'atividades' => [
                ['id' => 1, 'titulo' => 'Após Refresh', 'tipo' => 'Revisão', 'concluida' => false],
            ],
            'reforcos' => [],
        ]);

        Http::fake([
            GurujaMcpService::TOKEN_ENDPOINT => Http::response([
                'access_token' => 'fresh-token-after-401',
                'refresh_token' => 'rotated-refresh-token',
                'expires_in' => 3600,
            ], 200),
            GurujaMcpService::MCP_ENDPOINT => Http::sequence()
                // Primeira tentativa falha com 401
                ->push(['error' => 'invalid_token'], 401)
                // Retry com token renovado
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-03-26']], 200)
                ->push('', 202)
                ->push(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => [['name' => 'obter_meta_atual']]]], 200)
                ->push(['jsonrpc' => '2.0', 'id' => 3, 'result' => ['content' => [['type' => 'text', 'text' => $metaJson]]]], 200),
        ]);

        $res = $this->actingAs($user)->postJson('/estudos/guruja/atualizar')->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertSame(777, $res['meta']['target_id']);
        $this->assertDatabaseHas('study_guruja_tokens', [
            'user_id' => $user->id,
            'access_token' => 'fresh-token-after-401',
            'refresh_token' => 'rotated-refresh-token',
        ]);
    }
}
