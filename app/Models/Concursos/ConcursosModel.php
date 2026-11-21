<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Model;

/** Base dos models do módulo de concursos: todos vivem no banco concursos_pgsql. */
abstract class ConcursosModel extends Model
{
    protected $connection = 'concursos_pgsql';

    protected $guarded = ['id'];
}
