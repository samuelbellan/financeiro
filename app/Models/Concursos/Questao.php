<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Questao extends ConcursosModel
{
    protected $table = 'questoes';

    protected function casts(): array
    {
        return ['anulada' => 'boolean', 'desatualizada' => 'boolean', 'ano' => 'integer'];
    }

    public function alternativas(): HasMany
    {
        return $this->hasMany(QuestaoAlternativa::class)->orderBy('ordem');
    }

    public function assuntos(): BelongsToMany
    {
        return $this->belongsToMany(Assunto::class, 'questao_assunto')->withPivot('principal');
    }

    public function classificacao(): HasOne
    {
        return $this->hasOne(QuestaoClassificacao::class);
    }

    public function resolucao(): HasOne
    {
        return $this->hasOne(QuestaoResolucao::class);
    }

    public function subtopicos(): BelongsToMany
    {
        return $this->belongsToMany(Subtopico::class, 'questao_subtopico')->withPivot('principal');
    }

    public function urlTec(): ?string
    {
        return $this->fonte === 'tec' ? "https://www.tecconcursos.com.br/questoes/{$this->id_externo}" : null;
    }
}
