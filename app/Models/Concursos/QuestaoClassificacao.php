<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ledger do Gemini para uma questão: tipologia, dificuldade, ponto cobrado, distrator. */
class QuestaoClassificacao extends ConcursosModel
{
    protected $table = 'questao_classificacoes';

    protected function casts(): array
    {
        return ['dispositivos' => 'array', 'flags' => 'array'];
    }

    public function questao(): BelongsTo
    {
        return $this->belongsTo(Questao::class);
    }

    public function temFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }
}
