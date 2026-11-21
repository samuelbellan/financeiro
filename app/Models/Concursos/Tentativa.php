<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tentativa extends ConcursosModel
{
    protected $table = 'tentativas';

    public const CERTEZAS = [
        'sei' => 'Sei',
        'acho' => 'Acho que sei',
        'chute' => 'Chutei',
    ];

    public const MOTIVOS = [
        'nao_sabia' => 'Não sabia o conteúdo',
        'confundi' => 'Confundi conceitos parecidos',
        'esqueci' => 'Sabia, mas esqueci',
        'desatencao' => 'Li errado / desatenção',
        'pegadinha' => 'Caí na pegadinha da banca',
        'calculo' => 'Errei a conta',
        'tempo' => 'Fiz com pressa',
    ];

    protected function casts(): array
    {
        return ['correta' => 'boolean', 'tempo_segundos' => 'integer'];
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    /** Acerto que vale como domínio: acertou sem chutar. */
    public function dominio(): bool
    {
        return $this->correta && $this->certeza !== 'chute';
    }
}
