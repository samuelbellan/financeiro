<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Meta extends ConcursosModel
{
    protected $table = 'metas';

    protected function casts(): array
    {
        return ['data' => 'date'];
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(MetaAtividade::class)->orderBy('ordem');
    }
}
