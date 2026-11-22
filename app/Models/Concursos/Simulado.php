<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulado extends ConcursosModel
{
    protected $table = 'simulados';

    public const TIPOS = [
        'completo' => ['nome' => 'Completo', 'fator' => 1.0],
        'rapido' => ['nome' => 'Rápido (1/3 da prova)', 'fator' => 1 / 3],
    ];

    protected function casts(): array
    {
        return [
            'composicao' => 'array', 'questao_ids' => 'array', 'respostas' => 'array', 'resultado' => 'array',
            'iniciado_em' => 'datetime', 'prazo_em' => 'datetime', 'finalizado_em' => 'datetime',
            'nota_total' => 'float', 'pontos_total' => 'float',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(EditalCargo::class, 'cargo_id');
    }

    public function emAndamento(): bool
    {
        return $this->finalizado_em === null;
    }

    /** Passou do prazo (com 2 min de tolerância para o envio automático chegar). */
    public function esgotado(): bool
    {
        return now()->gt($this->prazo_em->copy()->addMinutes(2));
    }
}
