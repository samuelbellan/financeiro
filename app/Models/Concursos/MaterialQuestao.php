<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialQuestao extends ConcursosModel
{
    protected $table = 'material_questoes';

    /** Dias até a próxima revisão ao ENTRAR em cada caixa (4 = saiu da fila). */
    public const INTERVALOS = [1 => 1, 2 => 7, 3 => 30];

    public const ESTADOS = ['errando' => 'Ainda erro', 'revisando' => 'Revisando', 'dominado' => 'Dominada'];

    public const CERTEZAS = ['sei' => 'Sei', 'acho' => 'Acho', 'chute' => 'Chute'];

    protected $casts = ['alternativas' => 'array', 'revisar_em' => 'date', 'trechos_errados' => 'array'];

    /**
     * Situação calculada (substitui o status manual dos pontos):
     *  - errando: a última resposta (no app ou, sem ela, no TEC) foi errada;
     *  - revisando: acertou a última, mas errou antes e ainda está na fila (caixas 1 a 3);
     *  - dominado: nunca errou, ou passou pelas três caixas.
     * Usa ultimaTentativa: carregue com with('ultimaTentativa') em listas.
     */
    public function estado(): string
    {
        $acertouUltima = $this->ultimaTentativa ? $this->ultimaTentativa->acertou : $this->resultado_tec === 'certa';
        if (!$acertouUltima) {
            return 'errando';
        }

        return $this->caixa >= 1 && $this->caixa <= 3 ? 'revisando' : 'dominado';
    }

    public function naFilaHoje(): bool
    {
        return $this->caixa >= 1 && $this->caixa <= 3 && $this->revisar_em && $this->revisar_em->lte(today());
    }

    /**
     * Move a questão entre as caixas depois de uma resposta no app. Errou: caixa 1, volta amanhã.
     * Acertou (sem ser no chute): sobe uma caixa (7 dias, 30 dias, sai da fila). Acerto no chute
     * repete o intervalo da caixa atual. Acertar uma questão fora da fila não muda nada.
     */
    public function registrarResposta(bool $acertou, ?string $certeza): void
    {
        $hoje = today();
        if (!$acertou) {
            $this->fill(['caixa' => 1, 'revisar_em' => $hoje->copy()->addDays(self::INTERVALOS[1])]);
        } elseif ($this->caixa >= 1 && $this->caixa <= 3) {
            $caixa = $certeza === 'chute' ? $this->caixa : $this->caixa + 1;
            $this->fill(['caixa' => $caixa, 'revisar_em' => $caixa <= 3 ? $hoje->copy()->addDays(self::INTERVALOS[$caixa]) : null]);
        }
        $this->save();
    }

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
