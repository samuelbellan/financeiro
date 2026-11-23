// Flashcards das leis: revisão com repetição espaçada, modo livre e lista.
// Dados em dados/flashcards.json (gerado por tools/gerar_dados.py a partir de conteudo/flashcards/), com FLASH.leis e c.lei em cada card.
// O aluno escolhe a lei no topo (ou "Todas"); os limites de cards novos e de revisões por dia valem
// para cada lei. Prioridade às questões erradas (painel "O que revisar", via erros-questoes.js): os cards
// dos artigos cobrados nessas questões abrem a fila do dia (revisões vencidas e novos, os artigos com mais
// questões erradas antes) e entram primeiro no limite de novos; no fim da revisão há um botão para estudar
// todos eles. No card, aviso forte antes de virar e, no verso, o que o aluno marcou e por que errou. Depois, os novos vêm misturados às demais revisões (o primeiro deles já é novo), para um acúmulo de revisões atrasadas não esconder os cards novos; o que passa do limite de revisões
// continua vencido e volta primeiro nos dias seguintes.
// Dois tipos de card:
//   pergunta  frente → verso (+ trecho literal da lei)
//   lacuna    texto da lei com [[lacunas]]; o verso grifa o que faltava
// Três modos: "Revisão de hoje" (agenda), "Estudar livre" (um por um, sem agenda) e "Lista" (busca, filtros
// e a ficha de cada card com o histórico de respostas).
// Progresso em localStorage:
//   lom_fc       { id: {due:"AAAA-MM-DD", ivl, ease, reps, lapses, first:"AAAA-MM-DD"} }
//   lom_fc_cfg   { novos: 20, revs: 100, modo: "rev"|"liv"|"lis", lei: "lom"|"lrf"|…|"todas", secao: "lei|Nome da seção", num, fav }
//   lom_fc_log   [ {id, d:"AAAA-MM-DD", t:"HH:MM", q:1-4, ivl} ]  uma entrada por resposta que mudou a agenda
//   lom_fc_fav   { id: 1 }                                         favoritos (estrela)
//   lom_fc_fav_auto { id: 1 }                                      favoritos marcados sozinhos (artigo com 3+ questões erradas)
//   lom_fc_fora  { id: "susp" | "AAAA-MM-DD" }                      suspenso, ou adiado até a data
//   lom_fc_nota  { id: "texto" }                                    anotação pessoal
// (lom_fc guarda o progresso de todas as leis: os ids não se repetem.)
// Para testar outro dia: flashcards.html?hoje=2027-01-15
(async function () {
  'use strict';
  const ST = 'lom_fc', CFG = 'lom_fc_cfg', LOG = 'lom_fc_log', FAV = 'lom_fc_fav', FORA = 'lom_fc_fora', NOTA = 'lom_fc_nota';
  const LOG_MAX = 20000;
  const FLASH = await carregarJson('dados/flashcards.json');
  // Questões erradas por artigo (Map "lei|art" → {n, itens}); sem elas, os flashcards seguem a ordem normal.
  let ERROS_Q = new Map();
  try { if (window.errosQuestoes) ERROS_Q = await errosQuestoes(); } catch (_) { /* segue sem prioridade */ }
  // Baralho "Meus erros": cards montados pelo Refazer erros a partir dos erros do aluno (js/meus-cards.js, localStorage).
  const MEUS = window.MeusCards ? MeusCards.todos() : [];
  const CARDS = FLASH.cards.concat(MEUS), BY_ID = Object.fromEntries(CARDS.map(c => [c.id, c]));
  const ORDEM = Object.fromEntries(CARDS.map((c, i) => [c.id, i]));
  const $ = id => document.getElementById(id);
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const plural = (n, um, varios) => `${n} ${n === 1 ? um : varios}`;

  // ---------- armazenamento (funciona mesmo sem localStorage) ----------
  let semStorage = false;
  function load(k) { try { const v = JSON.parse(localStorage.getItem(k) || '{}'); return v && typeof v === 'object' && !Array.isArray(v) ? v : {}; } catch (_) { semStorage = true; return {}; } }
  function loadLista(k) { try { const v = JSON.parse(localStorage.getItem(k) || '[]'); return Array.isArray(v) ? v : []; } catch (_) { semStorage = true; return []; } }
  function save(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) { semStorage = true; } }
  const state = load(ST), log = loadLista(LOG), fav = load(FAV), fora = load(FORA), notas = load(NOTA);
  const LEIS = MEUS.length ? [MeusCards.LEI].concat(FLASH.leis) : FLASH.leis, LEI = Object.fromEntries(LEIS.map(l => [l.id, l]));
  const cfg = Object.assign({ novos: 20, revs: 100, modo: 'rev', lei: 'todas', secao: '', num: false, fav: false }, load(CFG));
  // flashcards.html?lei=meus (link do Refazer erros): abre direto no baralho pedido, na revisão.
  const leiPedida = new URLSearchParams(location.search).get('lei');
  if (leiPedida && LEI[leiPedida]) Object.assign(cfg, { lei: leiPedida, secao: '', modo: 'rev', fav: false, num: false });
  if (!['rev', 'liv', 'lis'].includes(cfg.modo)) cfg.modo = 'rev';
  if (cfg.lei !== 'todas' && !LEI[cfg.lei]) cfg.lei = LEIS[0].id;
    const secaoExiste = v => LEIS.some(l => l.secoes.some(s => `${l.id}|${s}` === v));
  if (cfg.secao && (!secaoExiste(cfg.secao) || (cfg.lei !== 'todas' && !cfg.secao.startsWith(cfg.lei + '|')))) cfg.secao = '';

  // ---------- datas em dia local ----------
  const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  const parse = s => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
  const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate() + n); return iso(d); };
  const param = new URLSearchParams(location.search).get('hoje');
  const HOJE = param && /^\d{4}-\d{2}-\d{2}$/.test(param) ? param : iso(new Date());
  const br = s => s.split('-').reverse().slice(0, 2).join('/');
  const brAno = s => s.split('-').reverse().join('/');
  const agora = () => { const d = new Date(); return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`; };

  // Adiamentos vencidos saem do registro (o card volta à fila).
  let limpou = false;
  for (const [id, v] of Object.entries(fora)) if (v !== 'susp' && v <= HOJE) { delete fora[id]; limpou = true; }
  if (limpou) save(FORA, fora);

  // ---------- agenda (estilo SM-2 simplificado) ----------
  // Com a data da prova em js/config.js, nenhuma revisão cai depois do dia anterior a ela. Depois da prova (ou sem data), agenda normal.
  const ULTIMO_DIA = (window.CONFIG && CONFIG.dataProva) ? addDays(CONFIG.dataProva, -1) : '';
  // q: 1 Errei · 2 Difícil · 3 Bom · 4 Fácil. Devolve o novo estado, sem gravar.
  function schedule(s, q) {
    s = Object.assign({ ivl: 0, ease: 2.5, reps: 0, lapses: 0 }, s);
    let { ivl, ease, reps, lapses } = s;
    if (q === 1) { lapses++; reps = 0; ivl = 1; ease -= 0.2; }
    else if (q === 2) { ivl = reps === 0 ? 1 : Math.max(ivl + 1, Math.round(ivl * 1.2)); ease -= 0.15; reps++; }
    else if (q === 3) { ivl = reps === 0 ? 1 : reps === 1 ? 3 : Math.max(ivl + 1, Math.round(ivl * ease)); reps++; }
    else { ivl = reps === 0 ? 3 : Math.max(ivl + 2, Math.round(ivl * ease * 1.3)); ease += 0.15; reps++; }
    ease = Math.round(Math.min(2.8, Math.max(1.3, ease)) * 100) / 100;
    // Reta final: nenhuma revisão passa do último dia antes da prova (ivl acompanha, para "revisado hoje" seguir certo).
    if (ULTIMO_DIA && HOJE < ULTIMO_DIA && addDays(HOJE, ivl) > ULTIMO_DIA) ivl = Math.round((parse(ULTIMO_DIA) - parse(HOJE)) / 864e5);
    return { due: addDays(HOJE, ivl), ivl, ease, reps, lapses, first: s.first || HOJE };
  }
  const prazo = n => n === 1 ? 'amanhã' : n < 30 ? `${n} dias` : n < 365 ? plural(Math.round(n / 30), 'mês', 'meses') : `${(n / 365).toFixed(1).replace('.', ',')} anos`;
  const NOME_Q = { 1: 'Errei', 2: 'Difícil', 3: 'Bom', 4: 'Fácil' };

  // ---------- seleção e fila do dia ----------
  const daLei = (c, lei) => lei === 'todas' || c.lei === lei;
  const daSecao = c => !cfg.secao || `${c.lei}|${c.secao}` === cfg.secao;
  const filtros = c => (!cfg.num || c.num) && (!cfg.fav || fav[c.id]);
  // Suspensos e adiados ficam fora da revisão e do modo livre (aparecem só na lista).
  const ativo = c => !fora[c.id];
  const naSelecao = c => daLei(c, cfg.lei) && daSecao(c) && filtros(c);   // lista: inclui suspensos
  const inSel = c => naSelecao(c) && ativo(c);                               // revisão e modo livre
  const isDue = c => state[c.id] && state[c.id].due <= HOJE;
  const isNew = c => !state[c.id];
  // Limite de cards novos por dia, contado em cada lei.
  const novosHoje = lei => Object.entries(state).filter(([id, s]) => s.first === HOJE && BY_ID[id] && BY_ID[id].lei === lei).length;
  const novosRestantes = lei => Math.max(0, cfg.novos - novosHoje(lei));
  const escopo = lei => lei === 'todas' ? LEIS.map(l => l.id) : [lei];
  // Novos de cada lei, alternados (CF, LRF, LAI, … , CF, LRF, …) quando o aluno escolhe "Todas".
  function novosDoDia(lei, filtro) {
    // Dentro de cada lei, os novos dos artigos com questões erradas entram primeiro no limite do dia.
    const listas = escopo(lei).map(l => CARDS.filter(c => c.lei === l && filtro(c) && ativo(c) && isNew(c))
      .sort((a, b) => errosQ(b) - errosQ(a)).slice(0, novosRestantes(l)));
    const out = [];
    for (let i = 0; listas.some(x => i < x.length); i++) for (const x of listas) if (i < x.length) out.push(x[i]);
    return out;
  }
  // Limite de revisões por dia, contado em cada lei. Revisado hoje = due − ivl é hoje (fora os vistos pela 1ª vez hoje).
  const ultimaRevisao = s => addDays(s.due, -(s.ivl || 0));
  const revisadasHoje = lei => Object.entries(state).filter(([id, s]) =>
    s.first !== HOJE && BY_ID[id] && BY_ID[id].lei === lei && ultimaRevisao(s) === HOJE).length;
  const revsRestantes = lei => Math.max(0, cfg.revs - revisadasHoje(lei));
  const porVencimento = (a, b) => state[a.id].due.localeCompare(state[b.id].due);
  // Prioridade aos cards dos artigos das questões erradas: mais questões erradas primeiro, depois os mais atrasados.
  const chaveArt = window.chaveArtigoCard || (() => '');
  const errosCard = c => window.errosDoCard ? errosDoCard(c, ERROS_Q) : ERROS_Q.get(chaveArt(c));
  const errosQ = c => { const e = errosCard(c); return e ? e.n + (e.refez || 0) : 0; };   // prioridade: questões erradas + erros ao refazer
  // Favoritos automáticos: os cards dos artigos com 3 ou mais questões erradas ganham a estrela, uma vez só.
  // lom_fc_fav_auto guarda os já marcados, para não devolver a estrela a quem o aluno tirou depois.
  const FAV_AUTO = 'lom_fc_fav_auto', MIN_ERROS_FAV = 3;
  {
    const auto = load(FAV_AUTO);
    let mudou = false;
    for (const c of CARDS) if ((errosCard(c) || {}).n >= MIN_ERROS_FAV && !auto[c.id]) { auto[c.id] = 1; fav[c.id] = 1; mudou = true; }
    if (mudou) { save(FAV, fav); save(FAV_AUTO, auto); }
  }
  const porPrioridade = (a, b) => errosQ(b) - errosQ(a) || porVencimento(a, b);
  // Revisões de hoje: as dos artigos com questões erradas primeiro, depois as mais atrasadas, até o limite de cada lei.
  function revisoesDoDia(lei, filtro) {
    return escopo(lei).flatMap(l => CARDS.filter(c => c.lei === l && filtro(c) && ativo(c) && isDue(c)).sort(porPrioridade).slice(0, revsRestantes(l)))
      .sort(porPrioridade);
  }
  const atrasadas = (lei, filtro) => CARDS.filter(c => daLei(c, lei) && filtro(c) && ativo(c) && isDue(c)).length - revisoesDoDia(lei, filtro).length;
  // Espalha os novos entre as revisões, em proporção; o primeiro card é novo.
  function misturar(revs, novos) {
    const out = [];
    let i = 0, j = 0;
    while (i < revs.length || j < novos.length) {
      if (j < novos.length && (i >= revs.length || j / novos.length <= i / revs.length)) out.push(novos[j++]);
      else out.push(revs[i++]);
    }
    return out;
  }
  const filtroSecao = c => daSecao(c) && filtros(c);
  // Os cards (revisões e novos) dos artigos com questões erradas abrem a fila; o resto vem misturado.
  function buildQueue() {
    const revs = revisoesDoDia(cfg.lei, filtroSecao), novos = novosDoDia(cfg.lei, filtroSecao);
    const foco = revs.concat(novos).filter(c => errosQ(c) > 0).sort((a, b) => errosQ(b) - errosQ(a));
    return foco.concat(misturar(revs.filter(c => !errosQ(c)), novos.filter(c => !errosQ(c)))).map(c => c.id);
  }

  // ---------- registro de respostas ----------
  const logDoCard = id => log.filter(e => e.id === id);
  // Cards respondidos hoje na seleção (lei e seção), na ordem das respostas; só a última resposta de cada card.
  function respondidosHoje() {
    const vistos = new Map();
    for (const e of log) if (e.d === HOJE && BY_ID[e.id] && daLei(BY_ID[e.id], cfg.lei) && daSecao(BY_ID[e.id])) { vistos.delete(e.id); vistos.set(e.id, e); }
    return [...vistos.values()];
  }

  // ---------- estado da tela ----------
  let queue = [], relearn = new Set(), flipped = false, feitos = 0, undoStack = [];
  let livre = [], li = 0, rotuloLivre = '';
  let ant = -1, antLista = [];          // revisão: índice do card anterior sendo visto (-1 = card atual)
  let menuAberto = false, editandoNota = false, aviso = null;
  const lista = { q: '', filtro: 'todos', dia: HOJE, ordem: 'lei', n: 100, det: null, ids: [], scroll: 0 };

  function start(focus) {
    flipped = false; undoStack = []; feitos = 0; ant = -1; menuAberto = false; editandoNota = false; rotuloLivre = ''; aviso = null;
    lista.det = null; lista.n = 100;
    if (cfg.modo === 'rev') { queue = buildQueue(); relearn = new Set(); }
    else if (cfg.modo === 'liv') { livre = cardsDasErradas().concat(CARDS.filter(c => inSel(c) && !errosQ(c)).map(c => c.id)); li = 0; }
    render(focus);
  }

  function answer(q) {
    const id = queue[0];
    const entry = { id, prev: state[id], queue: queue.slice(), relearn: new Set(relearn), feitos, hist: null, log: false };
    undoStack.push(entry);
    if (undoStack.length > 20) undoStack.shift();
    queue.shift();
    if (relearn.has(id)) { // repetição na mesma sessão: o card já está agendado para amanhã
      if (q === 1) queue.splice(Math.min(3, queue.length), 0, id); else relearn.delete(id);
    } else {
      state[id] = schedule(state[id], q);
      save(ST, state);
      log.push({ id, d: HOJE, t: agora(), q, ivl: state[id].ivl });
      if (log.length > LOG_MAX) log.splice(0, log.length - LOG_MAX);
      save(LOG, log); entry.log = true;
      // Histórico por dia (historico.js): uma revisão por resposta que muda a agenda.
      if (window.Historico) { Historico.registrarFlash(q === 1); entry.hist = q === 1 ? 'errou' : 'lembrou'; }
      if (q === 1) { relearn.add(id); queue.splice(Math.min(3, queue.length), 0, id); }
    }
    feitos++; flipped = false; editandoNota = false; menuAberto = false; aviso = null;
    render(true);
  }

  function undo() {
    const u = undoStack.pop();
    if (!u) return;
    if (u.prev) state[u.id] = u.prev; else delete state[u.id];
    save(ST, state);
    if (u.log) {
      for (let i = log.length - 1; i >= 0; i--) if (log[i].id === u.id) { log.splice(i, 1); break; }
      save(LOG, log);
    }
    if (u.hist && window.Historico) Historico.desfazerFlash(u.hist === 'errou');
    queue = u.queue; relearn = u.relearn; feitos = u.feitos; flipped = true; ant = -1;
    render(true);
  }

  // ---------- ver cards anteriores (revisão) ----------
  function anterior() {
    if (cfg.modo !== 'rev') return;
    if (ant < 0) { antLista = respondidosHoje(); if (!antLista.length) return; ant = antLista.length - 1; }
    else if (ant > 0) ant--;
    else return;
    menuAberto = false; editandoNota = false;
    render(true);
  }
  function seguinte() {
    if (cfg.modo !== 'rev' || ant < 0) return;
    ant = ant < antLista.length - 1 ? ant + 1 : -1;
    menuAberto = false; editandoNota = false;
    render(true);
  }
  function voltarAtual() { if (ant >= 0) { ant = -1; menuAberto = false; editandoNota = false; render(true); } }

  // Card que está na tela (para estrela, menu e anotação).
  function idAtual() {
    if (cfg.modo === 'rev') return ant >= 0 ? (antLista[ant] || {}).id : queue[0];
    if (cfg.modo === 'liv') return livre[li];
    return lista.det;
  }

  // ---------- favoritos, adiar, suspender, anotação ----------
  function alternaFav(id) {
    if (!id) return;
    if (fav[id]) delete fav[id]; else fav[id] = 1;
    save(FAV, fav);
    render();
    const b = $('fc').querySelector('[data-act=fav]'); if (b) b.focus({ preventScroll: true });
  }
  function tirarDaTela(id) { // o card some da fila de hoje ou do modo livre
    if (cfg.modo === 'rev' && queue.includes(id)) {
      queue = queue.filter(x => x !== id); relearn.delete(id); if (ant < 0) flipped = false;
      // Entra no lugar o próximo card dentro dos limites do dia (novo ou revisão atrasada), como nas contagens.
      const extra = revisoesDoDia(cfg.lei, filtroSecao).concat(novosDoDia(cfg.lei, filtroSecao)).map(c => c.id).filter(x => !queue.includes(x));
      queue.push(...extra);
    }
    if (cfg.modo === 'liv') { const i = livre.indexOf(id); if (i >= 0) { livre.splice(i, 1); if (li >= livre.length) li = Math.max(0, livre.length - 1); flipped = false; } }
  }
  function marcaFora(id, valor, msg) {
    if (!id) return;
    aviso = { msg, id, antes: fora[id], queue: queue.slice(), livre: livre.slice(), li, flipped };
    fora[id] = valor;
    save(FORA, fora);
    tirarDaTela(id);
    menuAberto = false;
    render(true);
  }
  function reativar(id) {
    if (!id || !fora[id]) return;
    aviso = { msg: 'Card reativado: volta à revisão e ao modo livre.', id, antes: fora[id], queue: queue.slice(), livre: livre.slice(), li, flipped };
    delete fora[id];
    save(FORA, fora);
    menuAberto = false;
    render(true);
  }
  function desfazAviso() {
    const a = aviso;
    if (!a) return;
    if (a.antes === undefined) delete fora[a.id]; else fora[a.id] = a.antes;
    save(FORA, fora);
    queue = a.queue; livre = a.livre; li = a.li; flipped = a.flipped; aviso = null;
    render(true);
  }
  function salvaNota() {
    const id = idAtual(), t = $('fc').querySelector('.nota-ed textarea');
    if (!id || !t) return;
    const v = t.value.trim();
    if (v) notas[id] = v; else delete notas[id];
    save(NOTA, notas);
    editandoNota = false;
    render();
  }

  // ---------- render ----------
  function renderStatus() {
    const sel = CARDS.filter(inSel), vistos = sel.filter(c => !isNew(c)).length;
    let txt, pct;
    if (cfg.modo === 'rev' && ant >= 0) {
      txt = `Card anterior <b>${ant + 1}</b> de ${antLista.length} respondidos hoje nesta seleção. Só para ler: a agenda não muda.`;
      pct = (ant + 1) / antLista.length * 100;
    } else if (cfg.modo === 'rev') {
      const rev = queue.filter(id => !isNew(BY_ID[id])).length, nov = queue.length - rev, total = feitos + queue.length;
      txt = queue.length
        ? `Faltam <b>${queue.length}</b> hoje: ${plural(rev, 'revisão', 'revisões')} e ${plural(nov, 'card novo', 'cards novos')}.`
        : `Nada pendente hoje nesta seleção.`;
      const fora = atrasadas(cfg.lei, filtroSecao);
      if (fora) txt += ` ${plural(fora, 'revisão atrasada fica', 'revisões atrasadas ficam')} para os próximos dias (limite de ${cfg.revs} por dia em cada lei).`;
      txt += ` <span style="color:var(--mut)">Você já viu ${vistos} de ${sel.length} cards.</span>`;
      pct = total ? feitos / total * 100 : 100;
    } else if (cfg.modo === 'liv') {
      const quem = rotuloLivre ? `${esc(rotuloLivre)}: card` : 'Card';
      txt = livre.length ? `${quem} <b>${li + 1}</b> de ${livre.length}, sem mexer na agenda.` : 'Nenhum card nesta seleção.';
      pct = livre.length ? (li + 1) / livre.length * 100 : 0;
    } else {
      const n = lista.ids.length, i = lista.det ? lista.ids.indexOf(lista.det) : -1;
      txt = lista.det
        ? (i >= 0 ? `Card <b>${i + 1}</b> de ${n} da lista.` : 'Card aberto da lista.')
        : `<b>${n}</b> ${n === 1 ? 'card encontrado' : 'cards encontrados'}.`;
      pct = lista.det && i >= 0 ? (i + 1) / n * 100 : 0;
    }
    $('status').innerHTML = txt;
    $('bar').style.width = pct + '%';
  }

  function renderSelect() {
    const conta = v => CARDS.filter(c => daLei(c, cfg.lei) && (!v || `${c.lei}|${c.secao}` === v) && filtros(c) && (cfg.modo === 'lis' || ativo(c)));
    const opt = (v, label) => { const cs = conta(v), d = cs.filter(c => isDue(c) && ativo(c)).length; return `<option value="${esc(v)}"${cfg.secao === v ? ' selected' : ''}>${esc(label)} (${cs.length}${d ? `, ${d} para revisar` : ''})</option>`; };
    const secoes = l => l.secoes.map(s => `${l.id}|${s}`).filter(v => conta(v).length).map(v => opt(v, v.split('|')[1])).join('');
    $('sec').innerHTML = opt('', 'Todas as seções') + (cfg.lei === 'todas'
      ? LEIS.map(l => `<optgroup label="${esc(l.sigla)}">${secoes(l)}</optgroup>`).join('')
      : secoes(LEI[cfg.lei]));
    $('num').checked = !!cfg.num;
    $('soFav').checked = !!cfg.fav;
  }
  // Botões das leis: quantos cards cada uma tem e quantos há para hoje.
  function renderLeis() {
    const botao = (id, sigla, curto) => {
      const cs = CARDS.filter(c => daLei(c, id) && filtros(c) && (cfg.modo === 'lis' || ativo(c)));
      const hoje = revisoesDoDia(id, filtros).length + novosDoDia(id, filtros).length;
      const ct = cfg.modo === 'rev'
        ? (hoje ? `<b>${hoje}</b> hoje` : 'nada hoje')
        : plural(cs.length, 'card', 'cards');
      // Botão compacto (sigla e contagem numa linha); o nome por extenso fica no title.
      return `<button type="button" data-lei="${id}" aria-pressed="${cfg.lei === id}" title="${esc(curto)}"><span class="sg">${esc(sigla)}</span>` +
        `<span class="nm">${esc(curto)}</span><span class="ct">${ct}</span></button>`;
    };
    $('leis').innerHTML = LEIS.map(l => botao(l.id, l.sigla, l.curto)).join('') + botao('todas', 'Todas', 'Todas as normas');
    $('sub').textContent = cfg.lei === 'todas' ? 'Todas as leis' : LEI[cfg.lei].nome;
  }

  // "texto com [[lacuna]]" → lacunas vazias (frente) ou grifadas (verso)
  const lacunaHTML = (t, cheia) => esc(t).replace(/\[\[(.+?)\]\](\s\([^)]*\))?/g, (_, x, ext) => cheia ? `<mark>${x}</mark>${ext || ''}` : '<span class="gap" aria-label="lacuna"></span>');
  // (na frente, o valor por extenso logo após a lacuna também sumiria — entregaria a resposta)

  // Caput do artigo/parágrafo (e do inciso, para alíneas) que introduz a lista citada.
  // Cada nível fica um pouco mais recuado, como na lei: caput → inciso → alínea.
  const recuo = c => c.caput ? `padding-left:${c.caput.length * 1.25}rem` : '';
  // Citações de outros dispositivos viram botões que abrem o artigo citado num balão (js/ref-lei.js).
  const refs = (c, t, inicio) => window.refLei ? refLei.html(t, { lei: c.lei, art: c.art, inicio }) : esc(t);
  const capHTML = c => (c.caput || []).map((x, i) => `<p class="cap" style="padding-left:${i * 1.25}rem">${refs(c, x, true)}</p>`).join('');

  // Um inciso ou alínea por linha, como na lei: quebra depois de "; " quando começa outro dispositivo.
  const linhasHTML = c => [].concat(c.trecho)
    .flatMap(t => t === '…' ? [t] : t.split(/(?<=;)\s+(?=[IVXLC]+\s*[-–]\s|[a-z]\)\s)/))
    .map(t => `<p style="${recuo(c)}">${refs(c, t, true)}</p>`).join('');

  // Situação do card na agenda, em palavras.
  function situacao(id) {
    const f = fora[id], s = state[id];
    if (f === 'susp') return 'Suspenso';
    if (f) return `Adiado até ${br(f)}`;
    if (!s) return 'Novo';
    return s.due <= HOJE ? 'Para hoje' : `Revisão em ${br(s.due)}`;
  }

  function menuHTML(id) {
    const f = fora[id];
    return '<div class="menu" role="menu">' +
      (f ? `<button type="button" role="menuitem" data-act="reativar">${f === 'susp' ? 'Reativar card suspenso' : 'Trazer de volta hoje'}</button>`
        : '<button type="button" role="menuitem" data-act="adiar">Adiar para amanhã</button>' +
          '<button type="button" role="menuitem" data-act="suspender">Não mostrar mais (suspender)</button>') +
      `<button type="button" role="menuitem" data-act="nota">${notas[id] ? 'Editar anotação' : 'Escrever anotação'}</button>` +
      '</div>';
  }

  function notaHTML(id) {
    if (editandoNota) return `<div class="nota-ed"><label for="notaTxt" class="vh">Sua anotação</label>` +
      `<textarea id="notaTxt" placeholder="Sua anotação sobre este card (fica só neste navegador)">${esc(notas[id] || '')}</textarea>` +
      '<div class="bts"><button type="button" class="btn pri" data-act="notaSalva">Salvar anotação</button>' +
      '<button type="button" class="btn" data-act="notaCancela">Cancelar</button></div></div>';
    return notas[id] ? `<p class="nota"><strong>Sua anotação:</strong> ${esc(notas[id])}</p>` : '';
  }

  function histHTML(id) {
    const s = state[id], es = logDoCard(id).slice().reverse();
    let h = '<div class="hist"><h3>Histórico deste card</h3><p>';
    h += s ? `Visto pela primeira vez em ${brAno(s.first || ultimaRevisao(s))}. ${situacao(id)} (intervalo de ${plural(s.ivl, 'dia', 'dias')}). ` +
      `${s.lapses ? `Você errou ${plural(s.lapses, 'vez', 'vezes')}.` : 'Nenhum erro.'}` : `${situacao(id)}: ainda não respondido.`;
    h += '</p>';
    h += es.length
      ? '<ol>' + es.map(e => `<li>${brAno(e.d)} às ${esc(e.t || '')}: <b class="q${e.q}">${NOME_Q[e.q]}</b>, próxima em ${prazo(e.ivl)}</li>`).join('') + '</ol>'
      : `<p class="mut">Sem respostas registradas.</p>`;
    return h + '</div>';
  }

  // De onde vem o texto do card: lei, norma de auditoria ou o MCASP (manual).
  const fonteDe = c => c.lei === 'mcasp' ? 'do MCASP' : c.lei === 'aud' ? 'da norma' : c.lei === 'meus' ? c.fonte || 'da questão' : 'da lei';

  // mode: "rate" (revisão), "livre", "ver" (card anterior, só leitura) ou "lista" (ficha aberta da lista)
  function cardHTML(c, mode) {
    const virado = mode === 'ver' || mode === 'lista' || flipped;
    const f = fora[c.id];
    let h = `<div class="ficha-head"><span class="sec">${esc(LEI[c.lei].sigla)} · ${esc(c.secao)}</span><span class="acts">` +
      `<span class="art">${esc(c.art)}</span>` +
      `<button type="button" class="ic fav" data-act="fav" aria-pressed="${!!fav[c.id]}" aria-label="Favorito" title="Favorito (F)">${fav[c.id] ? '★' : '☆'}</button>` +
      `<button type="button" class="ic" data-act="menu" aria-haspopup="menu" aria-expanded="${menuAberto}" aria-label="Mais ações" title="Adiar, suspender, anotação">⋯</button>` +
      `</span>${menuAberto ? menuHTML(c.id) : ''}</div><div class="ficha-body">`;
    if (mode === 'ver') {
      const e = antLista[ant];
      h += `<p class="info">Você respondeu <b class="q${e.q}">${NOME_Q[e.q]}</b>${e.t ? ` às ${esc(e.t)}` : ''}. Próxima revisão em ${br(addDays(e.d, e.ivl))}.</p>`;
    }
    if (f) h += `<p class="info">${f === 'susp' ? 'Card suspenso: não aparece na revisão nem no modo livre.' : `Adiado: volta à revisão em ${br(f)}.`}</p>`;
    // Artigo em que o aluno errou questão: antes de virar, um aviso forte no alto (para puxar a lembrança do erro);
    // depois de virar, o que ele marcou em cada questão e por que estava errado (preencheErros, depois do render).
    const eq = c.lei === 'meus' ? null : errosCard(c);   // card de "Erros" já é o próprio erro (o verso traz "Seu erro")
    if (eq && !virado) h += `<p class="erq erq-frente"><b>Você errou ${plural(eq.n, 'questão', 'questões')} sobre ${c.q ? 'este assunto' : 'este artigo'}${eq.refez ? ` e errou de novo ${plural(eq.refez, 'vez', 'vezes')} ao refazer` : ''}.</b> ` +
      'Lembre do que confundiu antes de virar o card.</p>';
    if (c.tipo === 'lacuna') {
      h += `<p class="ctx">Complete o texto ${fonteDe(c)}: ${esc(c.contexto)}</p>${capHTML(c, 'lei')}<p class="lei" style="${recuo(c)}">${lacunaHTML(c.texto, virado)}</p>`;
    } else {
      h += `<p class="q">${esc(c.frente)}</p>`;
      // Cards de "Erros": o comando da questão (quando a afirmação é uma alternativa solta), a afirmação em que o aluno
      // errou e, virado, o mapa mental do erro (js/meus-cards.js). Cards antigos (antes do mapa): A × B ou itens a julgar.
      if (c.ctx) h += `<p class="ctx">Na questão: ${esc(c.ctx)}</p>`;
      if (c.mapa && window.MeusCards) h += virado ? MeusCards.mapaHTML(c) : MeusCards.frenteHTML(c);
      if (c.contraste) h += `<ol class="mc-ab">${c.contraste.map((t, i) => `<li><b>${'AB'[i]}</b>${esc(t)}</li>`).join('')}</ol>`;
      if (c.julgue) h += `<ul class="mc-ab">${c.julgue.map(x => `<li>${esc(x.t)}</li>`).join('')}</ul>`;
      if (virado && !c.mapa) h += `<p class="a"><mark>${refs(c, c.verso)}</mark></p>`;
      if (virado && c.minha && !c.mapa) h += `<p class="trap"><strong>A regra com as suas palavras:</strong> ${esc(c.minha)}</p>`;
    }
    if (virado && c.armadilha) h += `<p class="trap"><strong>${c.lei === 'meus' ? 'Seu erro' : 'Pegadinha'}:</strong> ${refs(c, c.armadilha)}</p>`;
    if (virado && c.lei === 'meus' && !c.q && c.href) h += `<p class="cob"><strong>Questão:</strong> <a href="${esc(c.href)}">${esc(c.origem)}</a></p>`;
    // Cards que vieram de questões (MCASP): onde o assunto caiu, com link para a questão.
    if (virado && c.q) h += `<p class="cob"><strong>Cobrado em:</strong> ${c.q.map(([id, rot]) => `<a href="simulados.html#q=${encodeURIComponent(id)}">${esc(rot)}</a>`).join('; ')}</p>`;
    if (virado && c.trecho && [].concat(c.trecho).length) h += `<blockquote class="fonte"><span>Texto ${fonteDe(c)}</span>${capHTML(c, 'fonte')}${linhasHTML(c)}</blockquote>`;
    // A lei vem antes dos seus erros: primeiro o texto, depois as questões em que você errou.
    if (eq && virado) {
      eq.itens.forEach(i => { if (i.chave) ERR_ITENS.set(i.chave, i); });
      h += `<div class="erq erq-verso"><p><b>O que você errou ${c.q ? 'neste assunto' : 'neste artigo'}</b> (${plural(eq.n, 'questão', 'questões')}${eq.refez ? `; ${plural(eq.refez, 'erro', 'erros')} ao refazer` : ''}):</p><ul>` +
        eq.itens.slice(0, 3).map(i => `<li${i.chave ? ` data-erro="${esc(i.chave)}"` : ''}><a href="${esc(i.href)}">${esc(i.rot)}</a><span class="erq-det"></span></li>`).join('') +
        '</ul>' + (eq.itens.length > 3 ? `<p>e mais ${eq.itens.length - 3}.</p>` : '') + '</div>';
    }
    if (virado || editandoNota) h += notaHTML(c.id);
    if (mode === 'lista') h += histHTML(c.id);
    h += '</div><div class="ficha-foot">';
    if (mode === 'ver') {
      h += `<div class="nav"><button type="button" class="btn" data-act="antPrev"${ant ? '' : ' disabled'}>Anterior</button>` +
        '<button type="button" class="btn mid" data-act="atual">Voltar ao card atual</button>' +
        `<button type="button" class="btn pri" data-act="antNext">${ant < antLista.length - 1 ? 'Próximo' : 'Card atual'}</button></div>` +
        '<p class="hint"><kbd>←</kbd> e <kbd>→</kbd> passam de card · <kbd>Esc</kbd> volta ao card atual · <kbd>F</kbd> favorito</p>';
    } else if (mode === 'lista') {
      const i = lista.ids.indexOf(c.id);
      h += '<div class="nav"><button type="button" class="btn" data-act="listaVolta">Voltar à lista</button>' +
        `<button type="button" class="btn mid" data-act="listaPrev"${i > 0 ? '' : ' disabled'}>Anterior</button>` +
        `<button type="button" class="btn pri" data-act="listaNext"${i >= 0 && i < lista.ids.length - 1 ? '' : ' disabled'}>Próximo</button></div>` +
        '<p class="hint"><kbd>←</kbd> e <kbd>→</kbd> passam de card · <kbd>Esc</kbd> volta à lista · <kbd>F</kbd> favorito</p>';
    } else if (!flipped) {
      h += '<button type="button" class="btn pri reveal" data-act="flip">Mostrar resposta</button><p class="hint"><kbd>Espaço</kbd> mostra a resposta · <kbd>F</kbd> favorito</p>';
    } else if (mode === 'rate') {
      const s = state[c.id], again = relearn.has(c.id);
      const lab = q => again ? (q === 1 ? 'de novo hoje' : 'amanhã') : q === 1 ? 'de novo hoje' : prazo(schedule(s, q).ivl);
      h += '<div class="rate" role="group" aria-label="Quanto você lembrou?">' +
        [[1, 'Errei'], [2, 'Difícil'], [3, 'Bom'], [4, 'Fácil']].map(([q, t]) =>
          `<button type="button" class="r${q}" data-q="${q}">${t}<span>${lab(q)}</span></button>`).join('') +
        '</div><p class="hint">Teclas <kbd>1</kbd> a <kbd>4</kbd> respondem · <kbd>Z</kbd> desfaz · <kbd>←</kbd> card anterior · <kbd>F</kbd> favorito</p>';
    } else {
      h += `<div class="nav"><button type="button" class="btn" data-act="prev"${li ? '' : ' disabled'}>Anterior</button>` +
        '<button type="button" class="btn mid" data-act="shuffle">Embaralhar</button>' +
        `<button type="button" class="btn pri" data-act="next">${li < livre.length - 1 ? 'Próximo' : 'Voltar ao primeiro'}</button></div>` +
        '<p class="hint"><kbd>←</kbd> e <kbd>→</kbd> passam de card · <kbd>F</kbd> favorito</p>';
    }
    return h + '</div>';
  }

  // Estudo dirigido (sem mexer na agenda), oferecido no fim da revisão.
  const erradosHoje = () => [...new Set(log.filter(e => e.d === HOJE && e.q === 1).map(e => e.id))]
    .filter(id => BY_ID[id] && daLei(BY_ID[id], cfg.lei) && daSecao(BY_ID[id]) && ativo(BY_ID[id]));
  // Cards da seleção cujos artigos caíram em questões erradas (mais questões erradas primeiro, depois a ordem da lei).
  const cardsDasErradas = () => CARDS.filter(c => inSel(c) && errosQ(c) > 0).sort((a, b) => errosQ(b) - errosQ(a)).map(c => c.id);
  const favoritosSel = () => CARDS.filter(c => fav[c.id] && daLei(c, cfg.lei) && daSecao(c) && (!cfg.num || c.num) && ativo(c)).map(c => c.id);

  function doneHTML() {
    const sel = CARDS.filter(inSel);
    const prox = sel.filter(c => state[c.id] && state[c.id].due > HOJE).map(c => state[c.id].due).sort()[0];
    const n = prox ? sel.filter(c => state[c.id] && state[c.id].due === prox).length : 0;
    const faltam = sel.filter(isNew).length, fora = atrasadas(cfg.lei, filtroSecao);
    const errados = erradosHoje().length, favs = favoritosSel().length, dasErradas = cardsDasErradas().length;
    return '<div class="ficha-body"><div class="empty">' +
      `<h2>${feitos ? 'Revisão de hoje concluída' : 'Nada para revisar agora'}</h2>` +
      `<p>${prox ? `A próxima revisão é em ${br(prox)}, com ${plural(n, 'card', 'cards')}.` : 'Nenhuma revisão agendada nesta seleção.'}` +
      `${faltam ? ` Ainda ${faltam === 1 ? 'falta 1 card novo' : `faltam ${faltam} cards novos`} para conhecer.` : ''}` +
      `${fora ? ` ${plural(fora, 'revisão atrasada ficou', 'revisões atrasadas ficaram')} para os próximos dias.` : ''}</p>` +
      (fora ? `<button type="button" class="btn pri" data-act="atrasadas">Revisar mais ${Math.min(20, fora)} atrasadas</button>` : '') +
      (faltam ? `<button type="button" class="btn pri" data-act="more">Estudar mais ${Math.min(10, faltam)} cards novos</button>` : '') +
      (dasErradas ? `<button type="button" class="btn pri" data-act="rquest">Estudar ${plural(dasErradas, 'card', 'cards')} das questões que errei</button>` : '') +
      (errados ? `<button type="button" class="btn" data-act="rerros">${errados === 1 ? 'Rever o card que errei hoje' : `Rever os ${errados} que errei hoje`}</button>` : '') +
      (favs ? `<button type="button" class="btn" data-act="rfavs">Rever ${plural(favs, 'favorito', 'favoritos')}</button>` : '') +
      '<button type="button" class="btn" data-act="free">Estudar livre</button></div></div>';
  }

  // ---------- lista ----------
  const semAcento = s => String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const textoCard = c => c.tipo === 'lacuna' ? c.texto.replace(/\[\[(.+?)\]\]/g, '$1') : `${c.frente} ${c.verso}`;
  const numArt = c => { const m = c.art.match(/Art\. (\d+)º?(-[A-Z])?/); return m ? (m[1] + (m[2] || '')).toLowerCase() : ''; };
  // Dia(s) em que o card foi respondido: pelo registro e, para respostas anteriores a ele, pela última revisão.
  const respondidoEm = (id, dia) => log.some(e => e.id === id && e.d === dia) || (state[id] && ultimaRevisao(state[id]) === dia);
  const errou = id => (state[id] && state[id].lapses > 0) || log.some(e => e.id === id && e.q === 1);

  function filtraLista() {
    const q = semAcento(lista.q.trim()), mArt = q.match(/^art\.?\s*(\d+(-[a-z])?)$/);
    const f = lista.filtro;
    let cs = CARDS.filter(c => naSelecao(c));
    if (f === 'fav') cs = cs.filter(c => fav[c.id]);
    else if (f === 'novos') cs = cs.filter(c => isNew(c) && ativo(c));
    else if (f === 'revisao') cs = cs.filter(c => !isNew(c) && ativo(c));
    else if (f === 'hoje') cs = cs.filter(c => respondidoEm(c.id, HOJE));
    else if (f === 'dia') cs = cs.filter(c => respondidoEm(c.id, lista.dia));
    else if (f === 'errados') cs = cs.filter(c => errou(c.id));
    else if (f === 'fora') cs = cs.filter(c => fora[c.id]);
    else if (f === 'nota') cs = cs.filter(c => notas[c.id]);
    if (mArt) cs = cs.filter(c => numArt(c) === mArt[1]);
    else if (q) cs = cs.filter(c => semAcento(`${c.art} ${textoCard(c)} ${c.contexto || ''} ${notas[c.id] || ''}`).includes(q));
    if (lista.ordem === 'prox') cs.sort((a, b) => ((state[a.id] || {}).due || '9999').localeCompare((state[b.id] || {}).due || '9999') || ORDEM[a.id] - ORDEM[b.id]);
    else if (lista.ordem === 'erros') cs.sort((a, b) => ((state[b.id] || {}).lapses || 0) - ((state[a.id] || {}).lapses || 0) || ORDEM[a.id] - ORDEM[b.id]);
    lista.ids = cs.map(c => c.id);
  }

  function listaHTML() {
    if (!lista.ids.length) return '<p class="vazio">Nenhum card com essa busca e esses filtros.' +
      (lista.filtro === 'fav' || cfg.fav ? ' Para marcar um favorito, toque na estrela ☆ no alto do card.' : '') + '</p>';
    const linha = id => {
      const c = BY_ID[id], s = state[id];
      const txt = c.tipo === 'lacuna' ? c.texto.replace(/\[\[(.+?)\]\]/g, '____') : c.frente;
      const curto = txt.length > 170 ? txt.slice(0, 168) + '…' : txt;
      return `<li><button type="button" data-abre="${id}"><span class="la">${fav[id] ? '<span class="estrela" aria-label="favorito">★</span> ' : ''}${esc(c.art)}</span>` +
        `<span class="lt">${esc(curto)}<small>${esc(LEI[c.lei].sigla)} · ${esc(c.secao)}${notas[id] ? ' · com anotação' : ''}</small></span>` +
        `<span class="ls">${esc(situacao(id))}${s && s.lapses ? `<br>${plural(s.lapses, 'erro', 'erros')}` : ''}</span></button></li>`;
    };
    const mostra = lista.ids.slice(0, lista.n);
    return `<ul>${mostra.map(linha).join('')}</ul>` +
      (lista.ids.length > lista.n ? `<button type="button" class="btn mais" data-act="listaMais">Mostrar mais ${Math.min(100, lista.ids.length - lista.n)} de ${lista.ids.length - lista.n}</button>` : '');
  }

  function render(focus) {
    if (window.refLei) refLei.fechar();
    $('mRev').setAttribute('aria-pressed', cfg.modo === 'rev');
    $('mLiv').setAttribute('aria-pressed', cfg.modo === 'liv');
    $('mLis').setAttribute('aria-pressed', cfg.modo === 'lis');
    $('undo').hidden = cfg.modo !== 'rev' || !undoStack.length || ant >= 0;
    $('antBtn').hidden = cfg.modo !== 'rev' || ant >= 0 || !respondidosHoje().length;
    $('listaCtl').hidden = cfg.modo !== 'lis';
    $('dia').hidden = lista.filtro !== 'dia';
    if (cfg.modo === 'lis') filtraLista();
    renderStatus(); renderSelect(); renderLeis();
    const fc = $('fc'), ls = $('lista');
    let virado = flipped;
    if (cfg.modo === 'lis' && !lista.det) {
      fc.hidden = true; ls.hidden = false; ls.innerHTML = listaHTML();
    } else {
      fc.hidden = false; ls.hidden = true;
      if (cfg.modo === 'lis') { fc.innerHTML = cardHTML(BY_ID[lista.det], 'lista'); virado = true; }
      else if (cfg.modo === 'rev' && ant >= 0) { fc.innerHTML = cardHTML(BY_ID[antLista[ant].id], 'ver'); virado = true; }
      else if (cfg.modo === 'rev') fc.innerHTML = queue.length ? cardHTML(BY_ID[queue[0]], 'rate') : doneHTML();
      else fc.innerHTML = livre.length ? cardHTML(BY_ID[livre[li]], 'livre')
        : '<div class="ficha-body"><div class="empty"><h2>Nenhum card nesta seleção</h2><p>' +
          (cfg.fav && !Object.keys(fav).length ? 'Você ainda não marcou favoritos. Toque na estrela ☆ no alto de um card para marcar.'
            : 'Escolha outra seção ou desmarque os filtros.') + '</p></div></div>';
    }
    fc.classList.toggle('flipped', virado);
    preencheErros(fc);
    marcaRolagem();
    $('toast').hidden = !aviso;
    if (aviso) $('toastMsg').textContent = aviso.msg;
    $('warn').innerHTML = semStorage ? '<p class="warn">Este navegador não deixou salvar o progresso. A revisão funciona, mas some ao fechar a página.</p>' : '';
    if (editandoNota) { const t = fc.querySelector('textarea'); if (t) { t.focus(); return; } }
    if (focus) {
      const b = fc.querySelector(cfg.modo === 'lis' ? '[data-act=listaNext]:not(:disabled), [data-act=listaVolta]'
        : ant >= 0 ? '[data-act=antNext]'
        : flipped ? '.r3, [data-act=next]' : '[data-act=flip], [data-act=more], [data-act=free]');
      if (b) b.focus({ preventScroll: true });
    }
  }

  // No verso de um card de artigo com questões erradas: o que o aluno marcou e por que estava errado (js/erros-questoes.js
  // busca o texto da questão só aqui, quando o card é virado; DET guarda o que já foi buscado).
  const ERR_ITENS = new Map(), DET = new Map();
  const corta = (t, n) => { t = String(t).replace(/\s+/g, ' ').trim(); return t.length > n ? t.slice(0, n - 1).trimEnd() + '…' : t; };
  function preencheErros(raiz) {
    if (!window.detalheErro) return;
    raiz.querySelectorAll('li[data-erro]').forEach(async li => {
      const it = ERR_ITENS.get(li.dataset.erro);
      if (!it) return;
      if (!DET.has(it.chave)) { let d = null; try { d = await detalheErro(it); } catch (_) { /* sem detalhe */ } DET.set(it.chave, d); }
      const d = DET.get(it.chave), sp = li.querySelector('.erq-det');
      if (!d || !sp || !li.isConnected) return;
      sp.innerHTML = `<span class="m">Você marcou ${esc(d.letra)}: ${esc(corta(d.marcou, 220))}</span>` +
        (d.porque ? `<span class="pq">${esc(corta(d.porque, 320))}</span>` : '');
      marcaRolagem();
    });
  }

  // A página não rola: quando o texto passa do tamanho da ficha, só o papel rola, e a sombra
  // sobre o rodapé da ficha avisa que ainda há texto abaixo.
  function marcaRolagem() {
    const fc = $('fc'), b = fc.querySelector('.ficha-body');
    fc.classList.toggle('rola', !!b && b.scrollHeight - b.clientHeight - b.scrollTop > 2);
  }
  $('fc').addEventListener('scroll', marcaRolagem, true);
  window.addEventListener('resize', marcaRolagem);

  // Ajustes abrem como painel por cima da página: fecham com Esc ou com um clique fora.
  const ajustes = document.querySelector('header details');
  document.addEventListener('click', e => { if (ajustes.open && !ajustes.contains(e.target)) ajustes.open = false; });
  ajustes.addEventListener('keydown', e => {
    if (e.key === 'Escape' && ajustes.open) { e.stopPropagation(); ajustes.open = false; ajustes.querySelector('summary').focus(); }
  });

  // ---------- ações ----------
  function flip() { if (!flipped && ant < 0 && (cfg.modo === 'rev' ? queue.length : cfg.modo === 'liv' && livre.length)) { flipped = true; render(true); } }
  function move(d) { if (cfg.modo !== 'liv' || !livre.length) return; li = (li + d + livre.length) % livre.length; flipped = false; editandoNota = false; menuAberto = false; render(true); }
  function setModo(m) { cfg.modo = m; save(CFG, cfg); aviso = null; start(true); }
  function sessaoLivre(ids, rotulo) {
    cfg.modo = 'liv'; save(CFG, cfg);
    start();
    livre = ids; li = 0; rotuloLivre = rotulo;
    render(true);
  }
  function abreDaLista(id) { lista.scroll = $('lista').scrollTop || window.scrollY; lista.det = id; menuAberto = false; editandoNota = false; render(true); window.scrollTo(0, Math.min(window.scrollY, $('status').offsetTop)); }
  function passaLista(d) {
    const i = lista.ids.indexOf(lista.det) + d;
    if (i < 0 || i >= lista.ids.length) return;
    lista.det = lista.ids[i]; menuAberto = false; editandoNota = false; render(true);
  }
  function voltaLista() {
    if (!lista.det) return;
    const id = lista.det; lista.det = null; menuAberto = false; editandoNota = false;
    const pos = lista.ids.indexOf(id);
    if (pos >= lista.n) lista.n = Math.ceil((pos + 1) / 100) * 100;
    render();
    if ($('lista').scrollHeight > $('lista').clientHeight) $('lista').scrollTop = lista.scroll;
    else window.scrollTo(0, lista.scroll);
    const b = $('lista').querySelector(`[data-abre="${id}"]`); if (b) b.focus({ preventScroll: true });
  }

  $('fc').addEventListener('click', e => {
    const b = e.target.closest('button');
    if (!b) { if (!flipped && !e.target.closest('.empty, .menu, .nota-ed, .hist')) flip(); return; }
    const act = b.dataset.act, id = idAtual();
    if (b.dataset.q) answer(Number(b.dataset.q));
    else if (act === 'flip') flip();
    else if (act === 'next') move(1);
    else if (act === 'prev') move(-1);
    else if (act === 'free') setModo('liv');
    else if (act === 'shuffle') shuffle();
    else if (act === 'atrasadas') { queue = CARDS.filter(c => inSel(c) && isDue(c)).sort(porPrioridade).slice(0, 20).map(c => c.id); feitos = 0; flipped = false; render(true); }
    else if (act === 'more') { queue = CARDS.filter(c => inSel(c) && isNew(c)).sort((a, b) => errosQ(b) - errosQ(a)).slice(0, 10).map(c => c.id); feitos = 0; flipped = false; render(true); }
    else if (act === 'rquest') sessaoLivre(cardsDasErradas(), 'Cards dos artigos das questões que você errou');
    else if (act === 'rerros') sessaoLivre(erradosHoje(), 'Revendo os que você errou hoje');
    else if (act === 'rfavs') sessaoLivre(favoritosSel(), 'Revendo seus favoritos');
    else if (act === 'fav') alternaFav(id);
    else if (act === 'menu') { menuAberto = !menuAberto; render(); const m = $('fc').querySelector('.menu button'); if (m) m.focus(); }
    else if (act === 'adiar') marcaFora(id, addDays(HOJE, 1), 'Card adiado para amanhã.');
    else if (act === 'suspender') marcaFora(id, 'susp', 'Card suspenso: não aparece mais na revisão nem no modo livre. Reative pela Lista.');
    else if (act === 'reativar') reativar(id);
    else if (act === 'nota') { editandoNota = true; menuAberto = false; render(); }
    else if (act === 'notaSalva') salvaNota();
    else if (act === 'notaCancela') { editandoNota = false; render(); }
    else if (act === 'antPrev') anterior();
    else if (act === 'antNext') seguinte();
    else if (act === 'atual') voltarAtual();
    else if (act === 'listaVolta') voltaLista();
    else if (act === 'listaPrev') passaLista(-1);
    else if (act === 'listaNext') passaLista(1);
  });
  $('lista').addEventListener('click', e => {
    const b = e.target.closest('button');
    if (!b) return;
    if (b.dataset.abre) abreDaLista(b.dataset.abre);
    else if (b.dataset.act === 'listaMais') { lista.n += 100; render(); }
  });
  // Clique fora do menu "⋯" fecha o menu.
  document.addEventListener('click', e => {
    if (menuAberto && !e.target.closest('.menu, [data-act=menu]')) { menuAberto = false; render(); }
  });
  $('mRev').onclick = () => setModo('rev');
  $('mLiv').onclick = () => setModo('liv');
  $('mLis').onclick = () => setModo('lis');
  $('undo').onclick = undo;
  $('antBtn').onclick = anterior;
  $('toastUndo').onclick = desfazAviso;
  $('sec').onchange = e => { cfg.secao = e.target.value; save(CFG, cfg); start(); };
  $('leis').addEventListener('click', e => {
    const b = e.target.closest('button[data-lei]');
    if (!b || b.dataset.lei === cfg.lei) return;
    cfg.lei = b.dataset.lei;
    if (cfg.lei !== 'todas' && cfg.secao && !cfg.secao.startsWith(cfg.lei + '|')) cfg.secao = '';
    save(CFG, cfg); start();
    const n = $('leis').querySelector(`[data-lei="${cfg.lei}"]`); if (n) n.focus({ preventScroll: true });
  });
  $('num').onchange = e => { cfg.num = e.target.checked; save(CFG, cfg); start(); };
  $('soFav').onchange = e => { cfg.fav = e.target.checked; save(CFG, cfg); start(); };
  $('busca').oninput = e => { lista.q = e.target.value; lista.n = 100; lista.det = null; render(); };
  $('filtro').onchange = e => { lista.filtro = e.target.value; lista.n = 100; lista.det = null; render(); };
  $('dia').value = lista.dia; $('dia').max = HOJE;
  $('dia').onchange = e => { if (e.target.value) { lista.dia = e.target.value; lista.n = 100; render(); } };
  $('ordem').onchange = e => { lista.ordem = e.target.value; lista.n = 100; render(); };
  function shuffle() {
    for (let i = livre.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [livre[i], livre[j]] = [livre[j], livre[i]]; }
    li = 0; flipped = false; render();
    const n = $('fc').querySelector('[data-act=shuffle]'); if (n) n.focus({ preventScroll: true });
  }
  $('revs').value = cfg.revs;
  $('revs').onchange = e => { const n = Math.max(0, Math.min(999, Math.round(Number(e.target.value) || 0))); e.target.value = n; cfg.revs = n; save(CFG, cfg); if (cfg.modo === 'rev') start(); };
  $('novos').value = cfg.novos;
  $('novos').onchange = e => { const n = Math.max(0, Math.min(200, Math.round(Number(e.target.value) || 0))); e.target.value = n; cfg.novos = n; save(CFG, cfg); if (cfg.modo === 'rev') start(); };
  $('reset').onclick = () => {
    if (!confirm('Apagar todo o progresso dos flashcards (agenda e histórico de respostas), de todas as leis, neste navegador? Favoritos, anotações e cards suspensos ficam. Isso não pode ser desfeito.')) return;
    for (const k of Object.keys(state)) delete state[k];
    log.length = 0;
    save(ST, state); save(LOG, log); start();
  };

  document.addEventListener('keydown', e => {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (document.querySelector('dialog[open]')) return; // janela do edital aberta por cima
    const t = e.target.tagName;
    if (t === 'INPUT' || t === 'SELECT' || t === 'TEXTAREA') return;
    if (e.key === 'Escape') {
      if (menuAberto) { menuAberto = false; render(); const b = $('fc').querySelector('[data-act=menu]'); if (b) b.focus(); }
      else if (cfg.modo === 'rev' && ant >= 0) voltarAtual();
      else if (cfg.modo === 'lis' && lista.det) voltaLista();
      return;
    }
    if ((e.key === ' ' || e.key === 'Enter') && (t === 'BUTTON' || t === 'A')) return; // o próprio botão ou link responde
    const naFicha = cfg.modo !== 'lis' || lista.det;
    if (e.key === ' ' || e.key === 'Enter') { if (!flipped && naFicha) { e.preventDefault(); flip(); } }
    else if (cfg.modo === 'rev' && ant < 0 && flipped && queue.length && '1234'.includes(e.key)) answer(Number(e.key));
    else if ((e.key === 'z' || e.key === 'Z') && cfg.modo === 'rev') undo();
    else if ((e.key === 'f' || e.key === 'F') && naFicha) alternaFav(idAtual());
    else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && naFicha) {
      const b = $('fc').querySelector('.ficha-body');
      if (b && b.scrollHeight > b.clientHeight) { e.preventDefault(); b.scrollBy({ top: (e.key === 'ArrowDown' ? 1 : -1) * b.clientHeight * .6, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); }
    }
    else if (e.key === 'ArrowRight') { if (cfg.modo === 'rev') seguinte(); else if (cfg.modo === 'lis') passaLista(1); else move(1); }
    else if (e.key === 'ArrowLeft') { if (cfg.modo === 'rev') anterior(); else if (cfg.modo === 'lis') passaLista(-1); else move(-1); }
  });

  $('foot').textContent = `${CARDS.length} cards. O texto da lei de cada card vem de leis/*.md. Atualizados em ${FLASH.gerado.split('-').reverse().join('/')}.` +
    (param ? ` Simulando o dia ${HOJE.split('-').reverse().join('/')}.` : '');

  window.FC = { schedule, state, log, fav, fora, notas, HOJE }; // para depuração no console
  start();
})();
