<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalConcurso extends Model
{
    use HasFactory;

    protected $table = 'fiscal_concursos';

    protected $fillable = [
        'sigla',
        'nome_orgao',
        'esfera',
        'uf',
        'municipio',
        'cargo_principal',
        'cargos_secundarios',
        'status',
        'banca',
        'vagas_previstas',
        'requisito_escolaridade',
        'jornada',
        'remuneracao_inicial_bruta',
        'vencimento_basico',
        'produtividade_estimada',
        'produtividade_detalhes',
        'beneficios_estimados',
        'beneficios_detalhes',
        'remuneracao_real_transparencia',
        'remuneracao_teto',
        'tabela_rubricas',
        'evolucao_carreira',
        'lei_carreira',
        'disciplinas_chave',
        'ultimo_concurso_ano',
        'ultimo_concurso_banca',
        'ultimo_concurso_vagas',
        'ultimo_concurso_link',
        'regiao',
        'ultimo_concurso_status_vigencia',
        'ultimo_concurso_validade_fim',
        'ultimo_concurso_vigencia_detalhes',
        'link_portal_transparencia',
        'observacoes_estrategicas',
        'editado_manualmente',
    ];

    protected $casts = [
        'cargos_secundarios'            => 'array',
        'tabela_rubricas'               => 'array',
        'evolucao_carreira'             => 'array',
        'disciplinas_chave'             => 'array',
        'remuneracao_inicial_bruta'     => 'decimal:2',
        'vencimento_basico'             => 'decimal:2',
        'produtividade_estimada'        => 'decimal:2',
        'beneficios_estimados'          => 'decimal:2',
        'remuneracao_real_transparencia'=> 'decimal:2',
        'remuneracao_teto'              => 'decimal:2',
        'ultimo_concurso_ano'           => 'integer',
        'editado_manualmente'           => 'boolean',
    ];

    public function noticias(): HasMany
    {
        return $this->hasMany(FiscalNoticia::class, 'fiscal_concurso_id')->orderBy('publicado_em', 'desc');
    }

    // Scopes
    public function scopeFederal($query)
    {
        return $query->where('esfera', 'federal');
    }

    public function scopeEstadual($query)
    {
        return $query->where('esfera', 'estadual');
    }

    public function scopeMunicipal($query)
    {
        return $query->where('esfera', 'municipal');
    }

    public function scopeVigente($query)
    {
        return $query->whereIn('ultimo_concurso_status_vigencia', ['vigente', 'prorrogado']);
    }

    public function scopeVencido($query)
    {
        return $query->whereIn('ultimo_concurso_status_vigencia', ['vencido', 'sem_concurso_valido']);
    }

    public function scopePorRegiao($query, string $regiao)
    {
        return $query->where('regiao', $regiao);
    }

    public function scopeEmDestaque($query)
    {
        return $query->whereIn('status', [
            'edital_publicado',
            'inscricoes_abertas',
            'banca_definida',
            'comissao_formada',
            'autorizado'
        ]);
    }

    public function scopeApenasFuturosOuAbertos($query)
    {
        return $query->whereNotIn('status', ['concluido', 'encerrado']);
    }

    // Getters formatados
    public function getStatusFormatadoAttribute(): string
    {
        return match ($this->status) {
            'edital_publicado'   => 'Edital Publicado',
            'inscricoes_abertas' => 'Inscrições Abertas',
            'banca_definida'     => 'Banca Definida',
            'escolha_banca'      => 'Escolha de Banca',
            'comissao_formada'   => 'Comissão Formada',
            'autorizado'         => 'Autorizado',
            'solicitado'         => 'Solicitado',
            'previsto'           => 'Previsto',
            'em_andamento'       => 'Em Andamento',
            'concluido'          => 'Concluído',
            default              => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'edital_publicado', 'inscricoes_abertas' => 'emerald',
            'banca_definida'     => 'sky',
            'comissao_formada'   => 'amber',
            'autorizado'         => 'indigo',
            'escolha_banca'      => 'purple',
            'solicitado'         => 'blue',
            'previsto'           => 'slate',
            default              => 'gray',
        };
    }

    public function getStatusVigenciaFormatadoAttribute(): string
    {
        return match ($this->ultimo_concurso_status_vigencia) {
            'vigente'             => 'Vigente',
            'prorrogado'          => 'Prorrogado (Vigente)',
            'vencido'             => 'Vencido',
            'sem_concurso_valido' => 'Sem Concurso Válido',
            'edital_aberto'       => 'Edital Aberto',
            default               => ucfirst(str_replace('_', ' ', (string)$this->ultimo_concurso_status_vigencia)) ?: 'Vencido',
        };
    }

    public function getStatusVigenciaBadgeColorAttribute(): string
    {
        return match ($this->ultimo_concurso_status_vigencia) {
            'vigente'             => 'emerald',
            'prorrogado'          => 'teal',
            'edital_aberto'       => 'sky',
            'vencido'             => 'rose',
            'sem_concurso_valido' => 'amber',
            default               => 'rose',
        };
    }

    public function getAnosSemConcursoAttribute(): ?int
    {
        if (!$this->ultimo_concurso_ano) {
            return null;
        }

        $anoAtual = (int)date('Y');
        $diff = $anoAtual - $this->ultimo_concurso_ano;
        return max(0, $diff);
    }

    public function getRegiaoFormatadaAttribute(): string
    {
        if (!empty($this->regiao)) {
            return $this->regiao;
        }

        return match ($this->uf) {
            'SP', 'RJ', 'MG', 'ES' => 'Sudeste',
            'PR', 'SC', 'RS'       => 'Sul',
            'BA', 'PE', 'CE', 'MA', 'PB', 'RN', 'AL', 'SE', 'PI' => 'Nordeste',
            'DF', 'GO', 'MT', 'MS' => 'Centro-Oeste',
            'AM', 'PA', 'RO', 'AC', 'AP', 'RR', 'TO' => 'Norte',
            default => 'Nacional',
        };
    }

    public function getRemuneracaoInicialFormatadaAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->remuneracao_inicial_bruta, 2, ',', '.');
    }

    public function getVencimentoBasicoFormatadoAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->vencimento_basico, 2, ',', '.');
    }

    public function getRemuneracaoRealFormatadaAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->remuneracao_real_transparencia, 2, ',', '.');
    }

    public function getRemuneracaoTetoFormatadaAttribute(): string
    {
        return 'R$ ' . number_format((float)$this->remuneracao_teto, 2, ',', '.');
    }
}
