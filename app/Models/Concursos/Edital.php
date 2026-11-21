<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Edital extends ConcursosModel
{
    protected $table = 'editais';

    protected function casts(): array
    {
        return ['data_publicacao' => 'date', 'data_prova' => 'date'];
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(EditalCargo::class);
    }
}
