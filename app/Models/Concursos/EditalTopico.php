<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EditalTopico extends ConcursosModel
{
    protected $table = 'edital_topicos';

    protected function casts(): array
    {
        return ['normas' => 'array', 'sem_questoes_tec' => 'boolean'];
    }

    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(EditalDisciplina::class, 'disciplina_id');
    }

    public function assuntos(): BelongsToMany
    {
        return $this->belongsToMany(Assunto::class, 'edital_topico_assunto', 'topico_id', 'assunto_id')
            ->withPivot('status')->withTimestamps();
    }

    /** Ids dos assuntos que o tópico cobre, incluindo os sub-assuntos da árvore do TEC. */
    public function assuntoIdsCobertos(): array
    {
        return Assunto::comDescendentes($this->assuntos()->pluck('assuntos.id')->all());
    }

    /** Questões do TEC que caem neste tópico (pela árvore de assuntos). */
    public function questoesQuery()
    {
        $ids = $this->assuntoIdsCobertos();

        return Questao::whereHas('assuntos', fn ($q) => $q->whereIn('assuntos.id', $ids));
    }
}
