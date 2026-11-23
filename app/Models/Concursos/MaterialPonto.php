<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialPonto extends ConcursosModel
{
    public const STATUS = ['errando' => 'Ainda erro', 'revisando' => 'Revisando', 'dominado' => 'Dominado'];

    protected $table = 'material_pontos';

    protected $casts = [
        'questoes' => 'array',
        'ultimo_erro_em' => 'date',
    ];

    public function secao(): BelongsTo
    {
        return $this->belongsTo(MaterialSecao::class, 'secao_id');
    }
}
