<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Resolução comentada do Gemini: resumo, fundamentação e a explicação de cada alternativa. */
class QuestaoResolucao extends ConcursosModel
{
    protected $table = 'questao_resolucoes';

    protected function casts(): array
    {
        return ['dispositivos' => 'array', 'alternativas' => 'array'];
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    public function explicacao(string $letra): ?string
    {
        foreach ($this->alternativas as $a) {
            if ($a['letra'] === $letra) {
                return $a['explicacao'];
            }
        }

        return null;
    }
}
