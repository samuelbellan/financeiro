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

    /** Cor de cada disciplina (estante, breadcrumb, chips, callouts), pelo slug do livro. */
    public const CORES_DISCIPLINA = [
        'dcon' => '#3b82f6', // Constitucional: azul
        'dtrib' => '#16a34a', // Tributário: verde
        'dadm' => '#ea580c', // Administrativo: laranja
        'dciv' => '#8b5cf6', // Civil: roxo
        'dpen' => '#dc2626', // Penal: vermelho
        'demp' => '#ca8a04', // Empresarial: âmbar
        'mfin' => '#0d9488', // Matemática Financeira: verde-água
        'ctbga' => '#4f46e5', // Contabilidade: índigo
        'ti' => '#0891b2', // TI: ciano
    ];

    /** Cor de destaque do livro: a da disciplina; sem ela, fixa por id (sem coluna no banco). */
    public function cor(): string
    {
        if (isset(self::CORES_DISCIPLINA[$this->slug])) {
            return self::CORES_DISCIPLINA[$this->slug];
        }
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
