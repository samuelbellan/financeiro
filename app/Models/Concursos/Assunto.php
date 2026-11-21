<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assunto extends ConcursosModel
{
    protected $table = 'assuntos';

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function subtopicos(): HasMany
    {
        return $this->hasMany(Subtopico::class);
    }

    public function questoes(): BelongsToMany
    {
        return $this->belongsToMany(Questao::class, 'questao_assunto')->withPivot('principal');
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(Assunto::class, 'parent_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(Assunto::class, 'parent_id');
    }

    /**
     * Os ids informados mais todos os descendentes (árvore do TEC). O edital liga o tópico a um
     * assunto intermediário ("Lei nº 8.112/1990 - Estatuto…") e as questões estão nas folhas.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    public static function comDescendentes(array $ids): array
    {
        $todos = array_fill_keys($ids, true);
        $fronteira = $ids;
        while ($fronteira) {
            $fronteira = array_values(array_diff(
                static::whereIn('parent_id', $fronteira)->pluck('id')->all(),
                array_keys($todos)
            ));
            $todos += array_fill_keys($fronteira, true);
        }

        return array_keys($todos);
    }
}
