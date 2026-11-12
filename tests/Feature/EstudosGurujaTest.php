<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstudosGurujaTest extends TestCase
{
    use RefreshDatabase;

    protected function token(User $user): string
    {
        $plain = Str::random(40);
        DB::table('study_api_tokens')->insert([
            'user_id' => $user->id, 'nome' => 'teste', 'token_hash' => hash('sha256', $plain),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $plain;
    }

    protected function payload(int $ordem = 61): array
    {
        return [
            'lido_em' => '2026-11-12T10:00:00Z',
            'meta' => [
                'target_id' => 1069184, 'ordem' => $ordem, 'status' => 'ontime', 'data_inicio' => '2026-11-09T04:44:36',
                'dias_atraso' => 0,
                'atividades' => [
                    ['titulo' => 'IBS e CBS', 'disciplina' => 'Direito Tributário', 'tipo' => 'Questões', 'concluida' => true, 'questoes' => 25, 'acertos' => 17, 'minutos' => 85],
                    ['titulo' => 'Arts. 37 a 42 da CF/88', 'disciplina' => 'Direito Constitucional', 'tipo' => 'Lei Seca', 'concluida' => false],
                ],
                'reforcos' => [['titulo' => 'CPC 46', 'concluido' => true]],
            ],
        ];
    }

    public function test_envio_exige_token(): void
    {
        $this->postJson('/api/estudos/guruja/meta', $this->payload())->assertUnauthorized();
        $this->postJson('/api/estudos/guruja/meta', $this->payload(), ['Authorization' => 'Bearer x'])->assertUnauthorized();
    }

    public function test_envio_substitui_a_foto_e_o_app_le_a_ultima(): void
    {
        $user = User::factory()->create();
        $headers = ['Authorization' => 'Bearer ' . $this->token($user)];

        $this->postJson('/api/estudos/guruja/meta', $this->payload(60), $headers)->assertOk();
        $this->postJson('/api/estudos/guruja/meta', $this->payload(61), $headers)->assertOk();
        $this->assertSame(1, DB::table('study_guruja_metas')->count());

        $json = $this->actingAs($user)->getJson('/estudos/guruja')->assertOk()->json();
        $this->assertSame(61, $json['meta']['ordem']);
        $this->assertCount(2, $json['meta']['atividades']);
        $this->assertSame('2026-11-12T10:00:00Z', $json['lido_em']);
    }

    public function test_sem_foto_retorna_meta_nula_e_nao_vaza_entre_usuarios(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $this->postJson('/api/estudos/guruja/meta', $this->payload(), ['Authorization' => 'Bearer ' . $this->token($dono)])->assertOk();

        $this->actingAs($outro)->getJson('/estudos/guruja')->assertOk()->assertJson(['meta' => null]);
    }

    public function test_valida_atividades(): void
    {
        $user = User::factory()->create();
        $bad = $this->payload();
        unset($bad['meta']['atividades'][0]['concluida']);
        $this->postJson('/api/estudos/guruja/meta', $bad, ['Authorization' => 'Bearer ' . $this->token($user)])->assertUnprocessable();
    }

    public function test_comando_salvo_pelo_favorito_volta_no_card_com_resumo_do_gemini(): void
    {
        config(['services.gemini.key' => 'chave-teste']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => "• Aula 19 (versão original): págs. 149 a 169\n• Pare antes de **Resumo dos Assuntos**"]]]]],
        ])]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $texto = 'Aula 19 - Versão ORIGINAL - Estude da pág. 149 até a pág. 169';

        $json = $this->postJson('/estudos/guruja/comando', ['codigo' => 'DTRIB.3886.115658', 'texto' => "  $texto  ", 'titulo' => 'Simples Nacional'])
            ->assertOk()->json();
        $this->assertSame("• Aula 19 (versão original): págs. 149 a 169\n• Pare antes de Resumo dos Assuntos", $json['resumo']);
        // Mesmo texto de novo: reaproveita o resumo, sem chamar a IA.
        $this->postJson('/estudos/guruja/comando', ['codigo' => 'DTRIB.3886.115658', 'texto' => $texto])->assertOk();
        Http::assertSentCount(1);
        $this->postJson('/estudos/guruja/comando', ['codigo' => 'x; drop', 'texto' => 'a'])->assertUnprocessable();

        $cmd = $this->getJson('/estudos/guruja')->assertOk()->json('comandos')['DTRIB.3886.115658'];
        $this->assertSame($texto, $cmd['texto']);
        $this->assertStringStartsWith('• Aula 19', $cmd['resumo']);
        $this->assertSame(1, DB::table('study_guruja_comandos')->count());

        $outro = User::factory()->create();
        $this->actingAs($outro)->getJson('/estudos/guruja')->assertOk()->assertExactJson(['meta' => null, 'lido_em' => null, 'comandos' => []]);
    }

    public function test_sem_resposta_do_gemini_salva_so_o_comando(): void
    {
        config(['services.gemini.key' => 'chave-teste']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('erro', 500)]);
        $this->actingAs(User::factory()->create());

        $json = $this->postJson('/estudos/guruja/comando', ['codigo' => 'AUD.3709.112600', 'texto' => 'Faça o teste das aulas 00 a 02'])->assertOk()->json();
        $this->assertNull($json['resumo']);
        $this->assertSame('Faça o teste das aulas 00 a 02', $json['texto']);
    }
}
