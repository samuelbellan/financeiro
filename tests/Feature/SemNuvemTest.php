<?php

namespace Tests\Feature;

use App\Models\NotaFiscal;
use App\Models\User;
use App\Services\DatabaseSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Com CLOUD_APP_URL vazio (tudo no mesmo PC), nada deve tentar falar com uma nuvem. */
class SemNuvemTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_local_sem_nuvem_nao_faz_chamada_e_termina_bem(): void
    {
        config(['services.estudos.cloud_url' => '']);
        Http::fake();

        $this->artisan('telegram:register-local', ['url' => 'https://exemplo.ts.net'])
            ->expectsOutputToContain('Sem nuvem configurada')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_download_de_fotos_da_nuvem_e_pulado_sem_nuvem(): void
    {
        config(['services.estudos.cloud_url' => '']);
        $user = User::factory()->create();
        NotaFiscal::create(['user_id' => $user->id, 'estabelecimento' => 'Mercado', 'valor_total' => 10, 'foto_path' => 'notas_fiscais/some-que-nao-existe.jpg']);
        Http::fake();

        $metodo = new \ReflectionMethod(DatabaseSyncService::class, 'syncMissingReceiptPhotos');
        $metodo->invoke(app(DatabaseSyncService::class));

        Http::assertNothingSent();
    }

    public function test_download_de_fotos_continua_funcionando_com_nuvem(): void
    {
        config(['services.estudos.cloud_url' => 'https://nuvem.test']);
        $user = User::factory()->create();
        NotaFiscal::create(['user_id' => $user->id, 'estabelecimento' => 'Mercado', 'valor_total' => 10, 'foto_path' => 'notas_fiscais/some-que-nao-existe.jpg']);
        Http::fake(['nuvem.test/*' => Http::response('x', 404)]);

        $metodo = new \ReflectionMethod(DatabaseSyncService::class, 'syncMissingReceiptPhotos');
        $metodo->invoke(app(DatabaseSyncService::class));

        Http::assertSent(fn ($req) => $req->url() === 'https://nuvem.test/storage/notas_fiscais/some-que-nao-existe.jpg');
    }
}
