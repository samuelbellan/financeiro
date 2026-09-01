<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalNoticia extends Model
{
    use HasFactory;

    protected $table = 'fiscal_noticias';

    protected $fillable = [
        'fiscal_concurso_id',
        'titulo',
        'resumo',
        'conteudo',
        'url',
        'fonte',
        'esfera',
        'uf',
        'status_detectado',
        'publicado_em',
        'notificado_telegram',
        'notificado_em',
        'dados_remuneracao_snapshot',
    ];

    protected $casts = [
        'publicado_em'               => 'datetime',
        'notificado_em'              => 'datetime',
        'notificado_telegram'        => 'boolean',
        'dados_remuneracao_snapshot' => 'array',
    ];

    public function concurso(): BelongsTo
    {
        return $this->belongsTo(FiscalConcurso::class, 'fiscal_concurso_id');
    }

    public function scopeNaoNotificadas($query)
    {
        return $query->where('notificado_telegram', false);
    }

    public function scopeRecentes($query, int $limit = 20)
    {
        return $query->apenasFuturosOuAbertos()->orderBy('publicado_em', 'desc')->limit($limit);
    }

    public function scopeApenasFuturosOuAbertos($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fiscal_concurso_id')
              ->orWhereHas('concurso', function ($cq) {
                  $cq->whereNotIn('status', ['concluido', 'encerrado']);
              });
        })->where(function ($q) {
            $q->whereNull('status_detectado')
              ->orWhereNotIn('status_detectado', ['Concluído', 'Encerrado', 'Resultado Final', 'Gabarito Definitivo']);
        });
    }
}
