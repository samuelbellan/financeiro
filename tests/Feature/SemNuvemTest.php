<?php

namespace Tests\Feature;

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
}
