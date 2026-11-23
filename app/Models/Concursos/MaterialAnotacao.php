<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nota na margem, presa a um grifo do texto do capítulo (pelo id da marca). */
class MaterialAnotacao extends ConcursosModel
{
    public const CORES = ['amarelo', 'verde', 'azul', 'rosa'];

    protected $table = 'material_anotacoes';

    public function capitulo(): BelongsTo
    {
        return $this->belongsTo(MaterialCapitulo::class, 'capitulo_id');
    }
}
