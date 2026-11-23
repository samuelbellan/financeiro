<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialQuestao extends ConcursosModel
{
    protected $table = 'material_questoes';

    protected $casts = ['alternativas' => 'array'];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function secao(): BelongsTo
    {
        return $this->belongsTo(MaterialSecao::class, 'secao_id');
    }

    public function capitulo(): BelongsTo
    {
        return $this->belongsTo(MaterialCapitulo::class, 'capitulo_id');
    }

    /** Assunto do TEC: coluna própria ou, nas importadas antes dela, a linha "Assunto:" do comentário. */
    public function assuntoTec(): ?string
    {
        if ($this->assunto) {
            return $this->assunto;
        }

        return preg_match('/^Assunto:\s*(.+)$/mu', (string) $this->comentario, $m) ? trim($m[1]) : null;
    }

    public function ponto(): BelongsTo
    {
        return $this->belongsTo(MaterialPonto::class, 'ponto_id');
    }

    public function tentativas(): HasMany
    {
        return $this->hasMany(MaterialTentativa::class, 'questao_id');
    }

    public function ultimaTentativa(): HasOne
    {
        return $this->hasOne(MaterialTentativa::class, 'questao_id')->latestOfMany('id');
    }

    /** Letras que o aluno pode marcar. */
    public function opcoes(): array
    {
        return $this->tipo === 'CE' ? ['C', 'E'] : array_column($this->alternativas ?? [], 'letra');
    }
}
