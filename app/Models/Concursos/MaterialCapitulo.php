<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Capítulo (ou subcapítulo) do livro de resumo de uma matéria. */
class MaterialCapitulo extends ConcursosModel
{
    protected $table = 'material_capitulos';

    protected $casts = ['conteudo' => 'array'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordem')->orderBy('id');
    }

    public function assunto(): BelongsTo
    {
        return $this->belongsTo(Assunto::class);
    }

    public function pontos(): HasMany
    {
        return $this->hasMany(MaterialPonto::class, 'capitulo_id');
    }

    public function questoes(): HasMany
    {
        return $this->hasMany(MaterialQuestao::class, 'capitulo_id');
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(MaterialAnotacao::class, 'capitulo_id');
    }
}
