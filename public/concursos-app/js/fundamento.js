// Fundamento de uma resposta: ONDE a regra está de verdade (lei e artigo, norma e item, súmula, prova de origem)
// e, separado e rotulado, o que ler para aprofundar. Usado por refazer.js e simulados.js.
//
//   Fundamento.montar({blocos:[{rot:'CF, art. 37, VI'}], refs:[[titulo,url]], explicacao, area, norma})
//     → { dispositivos:[{cit,url}], citados:[str], provas:[str], aprofundar:[str], semFonte:bool }
//   Fundamento.html(f, esc) → bloco HTML "Fundamentação".
//
// "dispositivos" vêm do texto da lei que o site guarda (rótulo "CTN, art. 150, I"): são o local exato.
// "citados" são referências achadas no comentário da questão (art., lei, súmula, NBC).
// "aprofundar" são obras e normas gerais da disciplina (não indicam página: servem para estudar o tema).
(function () {
const LEIS = {
  'CF': ['Constituição da República Federativa do Brasil de 1988', 'cf'],
  'CTN': ['Lei nº 5.172/1966 (Código Tributário Nacional)', 'ctn'],
  'LRF': ['Lei Complementar nº 101/2000 (Lei de Responsabilidade Fiscal)', 'lrf'],
  'Lei 14.133': ['Lei nº 14.133/2021 (Lei de Licitações e Contratos Administrativos)', 'l14133'],
  'Lei 4.320': ['Lei nº 4.320/1964 (normas gerais de direito financeiro)', 'l4320'],
  'Lei 8.137': ['Lei nº 8.137/1990 (crimes contra a ordem tributária)', 'l8137'],
  'LGPD': ['Lei nº 13.709/2018 (Lei Geral de Proteção de Dados)', 'lgpd'],
  'LAI': ['Lei nº 12.527/2011 (Lei de Acesso à Informação)', 'lai'],
  'Lei 9.784': ['Lei nº 9.784/1999 (processo administrativo federal)', 'l9784'],
  'LC 116': ['Lei Complementar nº 116/2003 (ISS)', 'lc116'],
  'Lei 14.063': ['Lei nº 14.063/2020 (assinaturas eletrônicas)', 'l14063'],
};
// Para aprofundar, por disciplina: obras e normas gerais (sem página; não são a fonte de uma questão específica).
const APROFUNDAR = [
  [/portugu[eê]s/i, ['BECHARA, Evanildo. Moderna Gramática Portuguesa. Rio de Janeiro: Nova Fronteira/Lucerna.',
    'CUNHA, Celso; CINTRA, Lindley. Nova Gramática do Português Contemporâneo. Rio de Janeiro: Lexikon.',
    'CEGALLA, Domingos Paschoal. Novíssima Gramática da Língua Portuguesa. São Paulo: Companhia Editora Nacional.']],
  [/reda[cç][aã]o oficial/i, ['BRASIL. Presidência da República. Manual de Redação da Presidência da República. 3. ed. Brasília, 2018.']],
  [/arquivolog/i, ['BRASIL. Lei nº 8.159/1991 (política nacional de arquivos públicos e privados).']],
  [/auditoria|controladoria/i, ['CFC. NBC TA — Normas Brasileiras de Contabilidade, de auditoria independente (NBC TA 200 a 810).',
    'IRB. NBASP — Normas Brasileiras de Auditoria do Setor Público.',
    'CGU. Instrução Normativa SFC nº 3/2017 (Referencial Técnico da Atividade de Auditoria Interna Governamental).',
    'ATTIE, William. Auditoria: conceitos e aplicações. São Paulo: Atlas.']],
  [/contabilidade p[uú]blica|or[cç]amento/i, ['STN. Manual de Contabilidade Aplicada ao Setor Público (MCASP).',
    'STN. Plano de Contas Aplicado ao Setor Público (PCASP).', 'BRASIL. Lei nº 4.320/1964.']],
  [/contabilidade/i, ['IUDÍCIBUS, Sérgio de et al. Manual de Contabilidade Societária. São Paulo: Atlas.',
    'BRASIL. Lei nº 6.404/1976 (Sociedades por Ações).', 'CFC/CPC. NBC TG Estrutura Conceitual.']],
  [/economia/i, ['MANKIW, N. Gregory. Introdução à Economia. São Paulo: Cengage Learning.',
    'VASCONCELLOS, Marco Antonio S. Economia: micro e macro. São Paulo: Atlas.']],
  [/direito administrativo|administra[cç][aã]o p[uú]blica/i, ['DI PIETRO, Maria Sylvia Zanella. Direito Administrativo. Rio de Janeiro: Forense.',
    'MEIRELLES, Hely Lopes. Direito Administrativo Brasileiro. São Paulo: Malheiros.',
    'TCU. Referencial Básico de Governança Aplicável a Órgãos e Entidades da Administração Pública.']],
  [/tribut|ctn|ISS/i, ['MACHADO, Hugo de Brito. Curso de Direito Tributário. São Paulo: Malheiros.',
    'PAULSEN, Leandro. Curso de Direito Tributário Completo. São Paulo: Saraiva.']],
  [/[eé]tica/i, ['BRASIL. Decreto nº 1.171/1994 (Código de Ética Profissional do Servidor Público Civil do Poder Executivo Federal).']],
];
const CITADOS = [
  /S[uú]mula( Vinculante)? \d+ d[oa] (STF|STJ|TST|TCU)/g,
  /NBC T[AG]? ?\d+(\s?\(R\d\))?/g,
  /\b(Lei Complementar|Lei|Decreto-Lei|Decreto|Resolução|Instrução Normativa|Emenda Constitucional) (nº )?\d[\d.]*\/\d{2,4}/g,
  /[Aa]rts?\. \d+[º°]?(-[A-Z])?(, (§ \d+º?|[IVX]+))*( e \d+[º°]?)? d[aoe]s? (CF|CTN|LRF|LGPD|LAI|Constituição Federal|Lei [^.;,()]{3,28})/g,
];

// Texto local de cada lei (leis/*.md): é o que o balão (js/texto-lei.js) abre, com os artigos da questão grifados.
const LOCAL = {
  CF: 'leis/constituicao-federal-1988.md', CTN: 'leis/lei-5172-1966-ctn.md', LRF: 'leis/lei-complementar-101-2000-lrf.md',
  'Lei 14.133': 'leis/lei-14133-2021-licitacoes.md', 'Lei 4.320': 'leis/lei-4320-1964.md',
  'Lei 8.137': 'leis/lei-8137-1990-crimes-ordem-tributaria.md', LGPD: 'leis/lei-13709-2018-lgpd.md', LAI: 'leis/lei-12527-2011-lai.md',
  'Lei 9.784': 'leis/lei-9784-1999-processo-administrativo.md', 'LC 116': 'leis/lei-complementar-116-2003-iss.md',
  'Lei 14.063': 'leis/lei-14063-2020-assinaturas-eletronicas.md',
};
// Fontes de fundamentos.json que o site tem em texto (manual, norma, lei): o balão grifa nelas o trecho citado.
const DOCS = [
  [/MCASP/, 'leis/mcasp-11.md'], [/NBC TA 330/, 'leis/nbc-ta-330-r1.md'], [/NBC TA 530/, 'leis/nbc-ta-530.md'], [/NBC TSP 11/, 'leis/nbc-tsp-11-r1.md'],
  [/NBC TG 03/, 'leis/nbc-tg-03.md'], [/NBC TG 07/, 'leis/nbc-tg-07.md'], [/NBC TG 16/, 'leis/nbc-tg-16.md'], [/NBC TG 23/, 'leis/nbc-tg-23.md'],
  [/NBC TG 26/, 'leis/nbc-tg-26.md'], [/NBC TG 27/, 'leis/nbc-tg-27.md'], [/NBC TG Estrutura Conceitual/, 'leis/nbc-tg-ec.md'],
  [/NBC TSP 07/, 'leis/nbc-tsp-07.md'], [/NBC TSP Estrutura Conceitual/, 'leis/nbc-tsp-ec-r1.md'],
  [/6\.404/, 'leis/lei-6404-1976.md'], [/4\.320/, 'leis/lei-4320-1964.md'], [/5\.172|C[óo]digo Tribut/, 'leis/lei-5172-1966-ctn.md'],
  [/14\.133/, 'leis/lei-14133-2021-licitacoes.md'], [/101\/2000|\bLRF\b/, 'leis/lei-complementar-101-2000-lrf.md'],
];
// Rótulo curto da aba: "NBC TA 530 — Amostragem…" → "NBC TA 530"; "Lei nº 4.320/1964 (normas…)" → "Lei nº 4.320/1964".
const curto = c => c.split(/ — | \(|, /)[0].slice(0, 40);

// "CTN, art. 150, I" → { nome: 'Lei nº 5.172/1966 (…)', resto: 'art. 150, I', url }
function dispositivo(rot, refs) {
  const i = rot.indexOf(','), sigla = i < 0 ? rot : rot.slice(0, i), resto = i < 0 ? '' : rot.slice(i + 1).trim();
  const l = LEIS[sigla];
  if (!l) return { nome: rot, resto: '', url: null };
  const ref = l[1] && refs ? refs[l[1]] : null;
  return { sigla, nome: l[0], resto, url: LOCAL[sigla] || (ref ? ref[1] : null), ext: ref ? ref[1] : null };
}

function montar(o) {
  const refs = o.refsPorChave || null;
  // Mesma lei, vários artigos: uma linha só ("Lei X — art. 49; art. 54; art. 59").
  const porLei = new Map();
  for (const b of o.blocos || []) {
    if (b.txt && !LEIS[b.rot.split(',')[0]]) continue; // vai para as fontes, com o próprio texto
    const d = dispositivo(b.rot, refs), adct = /^ADCT\b/.test(d.resto), chave = d.nome + (adct ? ' ADCT' : '');
    const g = porLei.get(chave) || porLei.set(chave, { nome: d.nome, sigla: d.sigla, url: d.url, ext: d.ext, adct, restos: [] }).get(chave);
    const resto = d.resto.replace(/^ADCT,\s*/, '');
    if (resto && !g.restos.includes(resto)) g.restos.push(resto);
  }
  const dispositivos = [...porLei.values()].map(g => ({ cit: g.nome + (g.adct ? ', ADCT' : '') + (g.restos.length ? ' — ' + g.restos.join('; ') : ''),
    url: g.url, ext: g.ext, sigla: (g.sigla || g.nome) + (g.adct ? ' ADCT' : ''), artigos: g.restos.join('; ') }));
  const urlsLei = new Set(dispositivos.flatMap(d => [d.url, d.ext]).filter(Boolean));
  const citados = [];
  for (const re of CITADOS) for (const m of (o.explicacao || '').matchAll(re)) {
    const t = m[0].trim();
    if (!citados.includes(t) && !citados.some(c => c.includes(t))) citados.push(t);
  }
  const provas = (o.refs || []).filter(r => /^(qc_|gran_|orig)/.test(r[2] || '')).map(r => r[0]);
  const links = (o.refs || []).filter(r => !/^(qc_|gran_|orig)/.test(r[2] || '') && r[1] && !urlsLei.has(r[1])).map(r => ({ cit: r[0], url: r[1] }));
  // Bloco de texto de lei que o site não tem (sigla desconhecida): vai como fonte com o próprio texto.
  const fontes = (o.fontes || []).concat((o.blocos || []).filter(b => b.txt && !LEIS[b.rot.split(',')[0]]).map(b => ({ c: b.rot, t: b.txt, u: null })));
  const area = [o.norma, o.area].filter(Boolean).join(' ');
  const aprof = (APROFUNDAR.find(([re]) => re.test(area)) || [, []])[1];
  return { fontes, dispositivos, links, explicacao: o.explicacao || '', citados: citados.slice(0, 6), provas, aprofundar: aprof,
    interpretacao: /interpreta/i.test(o.assunto || ''),
    semFonte: !fontes.length && !dispositivos.length && !links.length && !citados.length && !/interpreta/i.test(o.assunto || '') };
}

// Links de "Fonte consultada" cujo texto o site tem (leis/*.md, direto ou pela tabela DOCS): abrem no balão, sem repetir o mesmo texto.
const abreLocal = l => /^leis\//.test(l.url) ? l.url : (DOCS.find(([re]) => re.test(l.cit)) || [])[1] || null;
function linksBalao(f) {
  if (f.fontes.length) return [];
  const vistos = new Set(), out = [];
  for (const l of f.links) { const u = abreLocal(l); if (u && !vistos.has(u)) { vistos.add(u); out.push({ cit: l.cit, url: u }); } }
  return out;
}

// O que o balão mostra, na ordem dos botões de html(f, esc, true): as fontes com texto e depois as leis.
function balao(f) {
  const out = [];
  for (const x of f.fontes) if (x.t) {
    const doc = DOCS.find(([re]) => re.test(x.c));
    out.push({ titulo: x.c, aba: curto(x.c), url: doc ? doc[1] : null, trecho: x.t, link: x.u || null });
  }
  for (const d of f.dispositivos) if (d.url && /^leis\//.test(d.url))
    out.push({ titulo: d.cit, aba: d.sigla, url: d.url, artigos: d.artigos, adct: d.adct, comentario: f.explicacao });
  for (const l of linksBalao(f)) out.push({ titulo: l.cit, aba: curto(l.cit), url: l.url, comentario: f.explicacao });
  return out;
}

// comBalao: em vez de copiar o texto da fonte na página, cada fonte vira um botão que abre o balão nela.
function html(f, esc, comBalao) {
  let n = 0;
  const botao = c => `<li><button type="button" class="link fd-abrir" data-fonte="${n++}">${esc(c)}</button></li>`;
  const li = d => d.url ? `<li><a href="${esc(d.url)}" target="_blank" rel="noopener noreferrer">${esc(d.cit)}</a></li>` : `<li>${esc(d.cit)}</li>`;
  let h = '<div class="sim-base"><b>Fundamentação — onde está a regra</b>';
  if (comBalao) {
    const comTexto = f.fontes.filter(x => x.t), semTexto = f.fontes.filter(x => !x.t), leis = f.dispositivos.filter(d => d.url && /^leis\//.test(d.url));
    const itens = comTexto.map(x => botao(x.c)).concat(leis.map(d => botao(d.cit)),
      semTexto.map(x => `<li>${esc(x.c)}<span class="dica"> (referência bibliográfica; o site não tem o texto)</span></li>`),
      f.dispositivos.filter(d => !leis.includes(d)).map(li));
    if (itens.length) h += `<ul class="fd">${itens.join('')}</ul>`;
  }
  else if (f.fontes.length) h += `<ul class="fd">${f.fontes.map(x => `<li>${x.u ? `<a href="${esc(x.u)}" target="_blank" rel="noopener noreferrer">${esc(x.c)}</a>` : esc(x.c)}${x.t ? `<blockquote>${esc(x.t)}</blockquote>` : '<span class="dica"> (referência bibliográfica; o site não tem o texto)</span>'}</li>`).join('')}</ul>`;
  if (!comBalao && f.dispositivos.length) h += `<ul class="fd">${f.dispositivos.map(li).join('')}</ul>`;
  if (f.citados.length && !f.fontes.length) h += `<p>Citado no comentário: ${f.citados.map(esc).join('; ')}</p>`;
  if (f.links.length && !f.fontes.length) {
    const ab = comBalao ? linksBalao(f) : [], urls = new Set(ab.map(l => l.url));
    h += `<p>Fonte consultada:</p><ul class="fd">${ab.map(l => botao(l.cit)).concat(f.links.filter(l => !urls.has(abreLocal(l))).map(li)).join('')}</ul>`;
  }
  if (f.interpretacao) h += '<p>Questão de interpretação: a fundamentação está no trecho do próprio texto-base da prova; localize nele a frase que sustenta a alternativa certa.</p>';
  if (f.semFonte) h += '<p>Esta questão não tem fonte registrada. Confirme a regra no material de estudo (veja a bibliografia abaixo) antes de memorizar.</p>';
  if (f.provas.length) h += `<p>Questão de prova: ${f.provas.map(esc).join('; ')}</p>`;
  if (f.aprofundar.length) h += `<details><summary>Para aprofundar (bibliografia geral da disciplina)</summary><ul class="fd">${f.aprofundar.map(t => `<li>${esc(t)}</li>`).join('')}</ul>` +
    '<p class="dica">Obras e normas gerais do tema: ajudam a estudar o assunto, mas não indicam a página da questão.</p></details>';
  return h + '</div>';
}

window.Fundamento = { montar, html, balao };
})();
