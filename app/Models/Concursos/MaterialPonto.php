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

    public function capitulo(): BelongsTo
    {
        return $this->belongsTo(MaterialCapitulo::class, 'capitulo_id');
    }

    /** Material dono do ponto: pelo capítulo do livro ou, se ainda não convertido, pela seção do caderno. */
    public function materialDono(): Material
    {
        return ($this->capitulo ?? $this->secao)->material;
    }
}
