<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Liga um grifo do resumo (capítulo + marca) a uma questão que ele decide. */
class MaterialGrifoQuestao extends ConcursosModel
{
    protected $table = 'material_grifo_questoes';

    public function questao(): BelongsTo
    {
        return $this->belongsTo(MaterialQuestao::class, 'questao_id');
    }

    public function capitulo(): BelongsTo
    {
        return $this->belongsTo(MaterialCapitulo::class, 'capitulo_id');
    }
}
