// Configuração do site: edite este arquivo para o SEU concurso.
// É carregado antes dos outros scripts em todas as páginas.
window.CONFIG = {
  // Nome que aparece no canto da barra de navegação.
  nome: 'Estudo para Concursos',

  // Data da prova (AAAA-MM-DD). Com ela, o início mostra a contagem regressiva e as revisões
  // (flashcards e Refazer erros) nunca ficam marcadas para depois da prova. Deixe '' se ainda não souber.
  dataProva: '2027-03-21',

  // Nota mínima (de 0 a 10) usada na janela "Notas" dos simulados.
  notaMinima: 5,

  // Edital verticalizado: grupos → disciplinas → tópicos. Você marca o que já estudou.
  // id: sem espaço nem acento (é a chave em que as marcações ficam salvas: não mude depois de começar).
  // banco: link para questões da disciplina (opcional). fontes: [rótulo, link] (opcional).
  edital: [
    { grupo: 'Conhecimentos gerais', disciplinas: [
      { id: 'portugues', titulo: 'Língua Portuguesa',
        topicos: ['Interpretação de texto', 'Ortografia', 'Acentuação gráfica', 'Crase', 'Pontuação', 'Concordância verbal e nominal', 'Regência'],
        banco: 'https://www.qconcursos.com/questoes-de-concursos/disciplinas/portugues-lingua-portuguesa' },
      { id: 'constitucional', titulo: 'Direito Constitucional',
        topicos: ['Princípios fundamentais (arts. 1º a 4º)', 'Direitos e garantias fundamentais (art. 5º)', 'Organização do Estado (art. 18)', 'Administração pública (art. 37)'],
        banco: 'https://www.qconcursos.com/questoes-de-concursos/disciplinas/direito-direito-constitucional',
        fontes: [['Constituição Federal', 'https://www.planalto.gov.br/ccivil_03/constituicao/constituicaocompilado.htm']] },
    ]},
    { grupo: 'Conhecimentos específicos', disciplinas: [
      { id: 'tributario', titulo: 'Direito Tributário',
        topicos: ['Conceito e espécies de tributo', 'Impostos, taxas e contribuição de melhoria', 'Lançamento', 'Suspensão do crédito tributário', 'Extinção do crédito tributário'],
        banco: 'https://www.qconcursos.com/questoes-de-concursos/disciplinas/direito-direito-tributario',
        fontes: [['CTN — Lei nº 5.172/1966', 'https://www.planalto.gov.br/ccivil_03/leis/l5172compilado.htm']] },
      { id: 'legislacao', titulo: 'Legislação',
        topicos: ['Lei de Responsabilidade Fiscal (LC nº 101/2000)', 'Lei de Acesso à Informação (Lei nº 12.527/2011)'],
        caderno: true,   // mostra o link "Praticar no caderno"
        fontes: [['LRF', 'https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp101.htm'], ['LAI', 'https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm']] },
    ]},
  ],
};
