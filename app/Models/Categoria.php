<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['user_id', 'nome', 'tipo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);
    }
}
