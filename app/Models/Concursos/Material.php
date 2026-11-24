<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Material extends ConcursosModel
{
    protected $table = 'materiais';

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    public function secoes(): HasMany
    {
        return $this->hasMany(MaterialSecao::class)->orderBy('ordem')->orderBy('id');
    }

    public function pontos(): HasManyThrough
    {
        return $this->hasManyThrough(MaterialPonto::class, MaterialSecao::class, 'material_id', 'secao_id');
    }

    public function capitulos(): HasMany
    {
        return $this->hasMany(MaterialCapitulo::class)->orderBy('ordem')->orderBy('id');
    }

    /** Cor de destaque do livro na estante: fixa por id, sem coluna no banco. */
    public function cor(): string
    {
        $cores = ['#7f77dd', '#1d9e75', '#d85a30', '#378add', '#d4537e', '#ba7517', '#5f9ea0', '#8e6cc7'];

        return $cores[$this->id % count($cores)];
    }

    /** Pontos de erro do material: os do livro (capítulo) e os que ainda só estão numa seção de caderno. */
    public static function pontosDe(array|int $materialIds): Builder
    {
        $ids = (array) $materialIds;

        return MaterialPonto::query()->where(fn ($q) => $q
            ->whereIn('capitulo_id', MaterialCapitulo::select('id')->whereIn('material_id', $ids))
            ->orWhere(fn ($q) => $q->whereNull('capitulo_id')
                ->whereIn('secao_id', MaterialSecao::select('id')->whereIn('material_id', $ids))));
    }

    /** id do material de cada ponto (para agrupar contagens de vários livros). */
    public static function materialDoPonto(MaterialPonto $p): ?int
    {
        return $p->capitulo?->material_id ?? $p->secao?->material_id;
    }
}
