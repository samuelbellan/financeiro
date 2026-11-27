<?php

namespace App\Models\Concursos;

class UsoEvento extends ConcursosModel
{
    protected $table = 'uso_eventos';

    public const UPDATED_AT = null;

    /** Nome legível de cada rota; o que não estiver aqui aparece pelo nome da rota. */
    public const TELAS = [
        'concursos.meta' => 'Meta do dia',
        'concursos.meta.feita' => 'Marcou atividade como feita',
        'concursos.meta.adiar' => 'Adiou atividade',
        'concursos.perfil' => 'Perfil',
        'concursos.perfil.salvar' => 'Salvou o perfil',
        'concursos.painel' => 'Desempenho',
        'concursos.simulados' => 'Simulados',
        'concursos.simulados.store' => 'Começou simulado',
        'concursos.simulados.show' => 'Simulado (prova/resultado)',
        'concursos.simulados.salvar' => 'Respondeu no simulado',
        'concursos.simulados.entregar' => 'Entregou simulado',
        'concursos.questoes.index' => 'Lista de questões',
        'concursos.questoes.resolver' => 'Resolver questões',
        'concursos.questoes.show' => 'Questão',
        'concursos.questoes.responder' => 'Respondeu questão',
        'concursos.questoes.motivo' => 'Informou motivo do erro',
        'concursos.material.index' => 'Resumos',
        'concursos.revisoes' => 'Revisões',
        'concursos.material.store' => 'Criou resumo',
        'concursos.material.show' => 'Livro de resumo',
        'concursos.material.capitulo' => 'Capítulo do resumo',
        'concursos.material.capitulos.conteudo' => 'Editou capítulo',
        'concursos.material.capitulos.anotacoes' => 'Anotou na margem',
        'concursos.material.buscar' => 'Buscou no resumo',
        'concursos.material.imprimir' => 'Imprimiu resumo',
        'concursos.material.refazer' => 'Refazer questões do resumo',
        'concursos.material.questoes.responder' => 'Refez questão',
        'concursos.material.pontos.errei' => 'Marcou "errei" em ponto',
        'concursos.material.pontos.status' => 'Mudou status de ponto',
        'concursos.leis.index' => 'Leis',
        'concursos.leis.buscar' => 'Buscou nas leis',
        'concursos.leis.acervo' => 'Acervo de leis',
        'concursos.leis.show' => 'Lei',
        'concursos.leis.artigo' => 'Artigo de lei',
        'concursos.juris.show' => 'Jurisprudência',
        'concursos.curadoria' => 'Curadoria',
    ];

    protected function casts(): array
    {
        return ['parametros' => 'array', 'created_at' => 'datetime'];
    }

    public static function tela(string $rota): string
    {
        return self::TELAS[$rota] ?? $rota;
    }
}
