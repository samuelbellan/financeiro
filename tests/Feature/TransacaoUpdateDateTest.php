<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransacaoUpdateDateTest extends TestCase
{
    use RefreshDatabase;

    private function criar(User $user): Transacao
    {
        return Transacao::create([
            'user_id'   => $user->id,
            'descricao' => 'Pix recebido',
            'valor'     => 3200,
            'tipo'      => 'receita',
            'data'      => '2026-11-04',
            'data_hora' => '2026-11-04 12:00:00',
        ]);
    }

    private function payload(string $data): array
    {
        return [
            'descricao' => 'Pix recebido',
            'valor'     => 3200,
            'tipo'      => 'receita',
            'data'      => $data,
            'categoria' => 'Salário',
        ];
    }

    public function test_editar_sem_mudar_data_preserva_data_e_data_hora(): void
    {
        $user = User::factory()->create();
        $t = $this->criar($user);

        $this->actingAs($user)->put("/financas/{$t->id}", $this->payload('2026-11-04'))->assertRedirect();

        $t->refresh();
        $this->assertSame('2026-11-04', $t->data->toDateString());
        $this->assertSame('2026-11-04 12:00:00', $t->data_hora->format('Y-m-d H:i:s'));
        $this->assertSame('Salário', $t->categoria);
    }

    public function test_mudar_data_acompanha_data_hora(): void
    {
        $user = User::factory()->create();
        $t = $this->criar($user);

        $this->actingAs($user)->put("/financas/{$t->id}", $this->payload('2026-11-06'))->assertRedirect();

        $t->refresh();
        $this->assertSame('2026-11-06', $t->data->toDateString());
        $this->assertSame('2026-11-06 12:00:00', $t->data_hora->format('Y-m-d H:i:s'));
    }

    public function test_modal_de_edicao_recebe_data_no_formato_do_input(): void
    {
        $view = file_get_contents(resource_path('views/financas/index.blade.php'));

        $this->assertStringContainsString(
            "document.getElementById('inputData').value = (t.data || '').substring(0, 10);",
            $view
        );
    }
}
