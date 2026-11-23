// errosQuestoes(): as questões que o aluno errou (as mesmas do painel "O que revisar", em revisar.js),
// agrupadas pelo artigo de lei que cada uma cobra. Usado pelos flashcards para priorizar os cards desses artigos.
// Devolve Map "lei|artigo" → { n: nº de questões erradas, itens: [{href, rot, chave, c}] }, com as leis dos flashcards
// (chave: 's:<id>' simulado ou 'c:<n>' caderno; c: índice da alternativa que o aluno marcou):
//   lrf|20  lai|11  ct|1 (Lei 8.137)  aud|NBC TA 320  cf|156  ctn|116  l4320|43  lgpd|6  l14133|33  lc116|8-A
//   e, para cada questão errada dos simulados, q|<id da questão> (usado pelos cards do MCASP, que listam as questões).
// De onde vem o artigo: simulados → rótulos dos trechos em dados/revisar.json ("LRF, art. 20, III") e, sem
// trecho, o tema ("LRF — Despesa com pessoal (arts. 19 e 20)"); caderno → painel.lom.arts (lei em painel.lom.info.lei).
// Mesmos critérios de revisar.js: fica de fora a questão acertada ou já dominada em "Refazer erros".
// Inclua depois de carregar.js.
window.errosQuestoes = async function () {
  const read = k => { try { const v = JSON.parse(localStorage.getItem(k) || '{}'); return v && typeof v === 'object' && !Array.isArray(v) ? v : {}; } catch (_) { return {}; } };
  const out = new Map();
  const PAINEL = await carregarJson('dados/painel.json');
  const refeito = read('refazer-v1'), dominada = k => { const e = refeito[k]; return !!e && e.box >= 3; };
  const simAns = read('simulados-respostas-v1');
  // Tentativas erradas ao refazer a questão no Refazer erros (histórico em refazer-v1).
  const refeitos = k => ((refeito[k] || {}).hist || []).filter(h => h && h.ok === false).length;
  const sim = PAINEL.simulados, L = 'ABCDE', temas = sim.temas || [];
  const lomAns = read('lom_st'), lom = PAINEL.lom, lomArts = lom.arts || {};
  const cadLei = (lom.info && lom.info.lei) || 'caderno', cadNome = (lom.info && lom.info.titulo) || 'Caderno';
  const simErradas = Object.entries(simAns).filter(([id, a]) => a && sim.q[id] && !(a.ok === true || L[a.c] === a.checked) && !dominada('s:' + id));
  const lomErradas = Object.entries(lomAns).filter(([n, a]) => a && n in lom.q && !a.ok && !dominada('c:' + n));
  if (!simErradas.length && !lomErradas.length) return out;
  const R = simErradas.length ? await carregarJson('dados/revisar.json') : { blocos: [], q: {} };

  const LEI = { 'LRF': 'lrf', 'LAI': 'lai', 'Lei 8.137': 'ct', 'CF': 'cf', 'CTN': 'ctn', 'Lei 4.320': 'l4320', 'LGPD': 'lgpd',
    'Lei 14.133': 'l14133', 'Licitações (Lei 14.133)': 'l14133', 'LC 116': 'lc116', 'ISS (LC 116)': 'lc116' };
  const conta = (chaves, item) => {
    for (const k of chaves) { const e = out.get(k) || { n: 0, itens: [] }; e.n++; e.itens.push(item); out.set(k, e); }
  };
  // "arts. 19 e 20", "arts. 52 a 55", "art. 1º" → números dos artigos.
  const artsDoTema = s => {
    const m = s.match(/\(arts?\.\s*([^)]*)\)/);
    if (!m) return [];
    const nums = [];
    for (const parte of m[1].split(/,|\se\s/)) {
      const r = parte.match(/(\d+)\D*?\sa\s(\d+)/);
      if (r) { for (let i = +r[1]; i <= +r[2] && i - r[1] < 30; i++) nums.push(String(i)); }
      else { const n = parte.match(/^\s*(\d+)/); if (n) nums.push(n[1]); }
    }
    return nums;
  };

  for (const [id, a] of simErradas) {
    const m = sim.q[id], tema = temas[m[3]] || sim.areas[m[1]] || '';
    const chaves = new Set();
    for (const b of (R.q[id] || { b: [] }).b) {
      const r = ((R.blocos[b] || [''])[0]).match(/^(LRF|LAI|Lei 8\.137|CF|CTN|Lei 4\.320|LGPD|Lei 14\.133|LC 116), art\. (\d+(?:-[A-Z])?)/);
      if (r) chaves.add(`${LEI[r[1]]}|${r[2]}`);
    }
    const norma = tema.split(' — ')[0];
    if (!chaves.size && LEI[norma]) for (const n of artsDoTema(tema)) chaves.add(`${LEI[norma]}|${n}`);
    const nbc = tema.match(/NBC TA (\d+)/);
    if (norma === 'Auditoria' && nbc) chaves.add(`aud|NBC TA ${nbc[1]}`);
    chaves.add('q|' + id);   // cards que citam a própria questão (MCASP: campo q)
    const fonte = (sim.fontes[m[0]] || '').split(' · ')[0], num = id.replace(/^\D*\d*-?/, '') || id;
    conta(chaves, { href: 'simulados.html#q=' + id, rot: `${fonte} · questão ${+num || num}`, chave: 's:' + id, c: a.c, refez: refeitos('s:' + id) });
  }
  for (const [n, a] of lomErradas) {
    const chaves = (lomArts[n] || []).filter(x => /^\d/.test(x)).map(x => cadLei + '|' + x);
    conta(chaves, { href: 'caderno.html#' + n, rot: 'Caderno (' + cadNome + ') · questão ' + n, chave: 'c:' + n, c: a.c, refez: refeitos('c:' + n) });
  }
  return out;
};

// Chave "lei|artigo" de um flashcard, para cruzar com errosQuestoes(). Sem artigo reconhecível → ''.
// Questões erradas de um card: as do artigo dele ou, nos cards que listam as questões de onde vieram (MCASP, campo q),
// as dessas questões. Devolve {n, itens} ou undefined.
window.errosDoCard = function (c, mapa) {
  const chaves = c.q ? c.q.map(([id]) => 'q|' + id) : [window.chaveArtigoCard(c)];
  const itens = [];
  for (const k of chaves) for (const i of ((mapa.get(k) || {}).itens || [])) if (!itens.some(x => x.href === i.href)) itens.push(i);
  return itens.length ? { n: itens.length, refez: itens.reduce((t, i) => t + (i.refez || 0), 0), itens } : undefined;
};
window.chaveArtigoCard = function (c) {
  if (c.lei === 'aud') { const m = c.art.match(/^NBC TA (\d+)/); return m ? `aud|NBC TA ${m[1]}` : ''; }
  const m = c.art.match(/^Art\. (\d+)º?(-[A-Z])?/);
  return m ? `${c.lei}|${m[1]}${m[2] || ''}` : '';
};

// O que o aluno marcou numa questão errada e por que estava errado (para mostrar o erro, e não só a resposta).
// item: um dos itens de errosQuestoes(). Busca o texto em dados/simulados.json ou no caderno só quando é pedido.
// Devolve {letra, marcou, porque} (textos vazios se a questão não tem o comentário daquela alternativa).
window.detalheErro = async function (item) {
  const L = 'ABCDE', i = item.c;
  if (item.chave.startsWith('s:')) {
    const S = await carregarJson('dados/simulados.json');
    const q = (window._errosSim ||= new Map(S.questions.map(x => [x.id, x]))).get(item.chave.slice(2));
    if (!q || !(i >= 0)) return null;
    return { letra: L[i], marcou: (q.options || [])[i] || '', porque: (q.alts || [])[i] || porqueAssertivas(q, i) };
  }
  const C = await carregarJson('dados/caderno/atual.json');
  const q = (window._errosCad ||= new Map(C.map(x => [String(x.n), x]))).get(item.chave.slice(2));
  if (!q || !(i >= 0)) return null;
  return { letra: L[i], marcou: (q.opts || [])[i] || '', porque: (q.x || [])[i] || '' };
};

// Questão de assertivas sem comentário por alternativa (q.alts vazio, análise em q.items): da combinação marcada
// ("II e III apenas.", "Todas as assertivas estão corretas.") tira os itens que o aluno julgou errado e devolve a
// nota de cada um: "Item I é verdadeiro: … Item II é falso: …". Sem itens, ''.
window.porqueAssertivas = function (q, i) {
  const itens = q.items || [], opt = (q.options || [])[i];
  if (!itens.length || !opt) return '';
  const marcados = /todas/i.test(opt) ? itens.map(x => x.id) : /nenhuma/i.test(opt) ? [] : opt.match(/\b(?:IV|V?I{1,3}|V)\b/g) || [];
  return itens.filter(x => marcados.includes(x.id) !== !!x.ok)
    .map(x => `Item ${x.id} é ${x.ok ? 'verdadeiro' : 'falso'}: ${x.note}`).join(' ');
};
