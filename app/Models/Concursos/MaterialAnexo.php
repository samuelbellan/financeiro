<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Imagem colada no livro (fica no disco local do PC, em storage/app/concursos/anexos). */
class MaterialAnexo extends ConcursosModel
{
    protected $table = 'material_anexos';

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
