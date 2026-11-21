<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAtividade extends ConcursosModel
{
    protected $table = 'meta_atividades';

    public const TIPOS = ['teoria' => 'Teoria', 'questoes' => 'Questões', 'revisao' => 'Revisão de erros'];

    protected function casts(): array
    {
        return ['questao_ids' => 'array', 'concluida_em' => 'datetime'];
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }

    public function topico(): BelongsTo
    {
        return $this->belongsTo(EditalTopico::class, 'topico_id');
    }

    /** Questões da atividade já respondidas desde o dia da meta. @return list<int> */
    public function respondidas(int $userId): array
    {
        if (!$this->questao_ids) {
            return [];
        }

        return Tentativa::where('user_id', $userId)->whereIn('questao_id', $this->questao_ids)
            ->where('created_at', '>=', $this->meta->data->copy()->startOfDay())
            ->distinct()->pluck('questao_id')->all();
    }
}
