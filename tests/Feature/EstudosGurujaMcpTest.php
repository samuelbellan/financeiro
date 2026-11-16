<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GurujaMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_guruja_auth_redireciona_para_endpoint_oauth(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->get('/estudos/guruja/auth');
        $res->assertRedirect();
        $target = $res->headers->get('Location');
        $this->assertStringStartsWith(GurujaMcpService::AUTH_ENDPOINT, $target);
        $this->assertStringContainsString('response_type=code', $target);
        $this->assertStringContainsString('code_challenge=', $target);
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
            GurujaMcpService::MCP_ENDPOINT => Http::response([
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'protocolVersion' => '2024-11-05',
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

    public function test_guruja_atualizar_via_mcp_quando_conectado(): void
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

        $metaJson = json_encode([
            'target_id' => 99999,
            'ordem' => 50,
            'atividades' => [
                [
                    'id' => 501,
                    'codigo' => 'DIR.01.501',
                    'titulo' => 'Direitos Individuais e Coletivos',
                    'disciplina' => 'Direito Constitucional',
                    'tipo' => 'Lei Seca',
                    'concluido' => false,
                ],
            ],
            'reforcos' => [],
        ]);

        Http::fake([
            GurujaMcpService::MCP_ENDPOINT => Http::sequence()
                // 1. initialize
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'result' => ['protocolVersion' => '2024-11-05'],
                ], 200)
                // 2. tools/list
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 2,
                    'result' => [
                        'tools' => [
                            [
                                'name' => 'obter_meta_atual',
                                'description' => 'Retorna a meta e tarefas atuais do aluno na Guruja',
                            ],
                        ],
                    ],
                ], 200)
                // 3. tools/call
                ->push([
                    'jsonrpc' => '2.0',
                    'id' => 3,
                    'result' => [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $metaJson,
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $res = $this->actingAs($user)->postJson('/estudos/guruja/atualizar')->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertTrue($res['via_mcp']);
        $this->assertSame(99999, $res['meta']['target_id']);
        $this->assertSame('Direitos Individuais e Coletivos', $res['meta']['atividades'][0]['titulo']);

        $this->assertDatabaseHas('study_guruja_metas', [
            'user_id' => $user->id,
        ]);
    }
}
