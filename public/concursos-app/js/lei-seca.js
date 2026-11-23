// Lei seca (leis.html): o texto das leis de leis/*.md para ler e marcar.
// - Selecionar um trecho abre a barra de marcação: 4 cores de grifo, sublinhado, negrito, nota e borracha.
//   As marcações ficam no localStorage ('leiseca-marcas-v1'), gravadas direto no banco do iniciar.py (armazenamento.js).
//   Cada marcação guarda a linha do .md (l), o começo e o fim no texto da linha (a, b) e o próprio texto (x),
//   que serve para reencontrá-la se o .md mudar. Uma seleção de várias linhas vira uma marcação por linha,
//   com o mesmo grupo (g): cor, nota e remoção valem para o grupo.
// - Citações de outros dispositivos ("art. 165, § 2º, da Constituição") viram links (refLei.html, js/ref-lei.js);
//   o clique abre um modal com o artigo citado inteiro e o inciso/parágrafo citado em destaque.
// - Abre na lei e na posição em que parou ('leiseca-pos-v1'); leis.html?lei=lrf&art=4 abre direto num artigo.
(function () {
  'use strict';
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const $ = s => document.querySelector(s);

  // ref = id da lei em js/ref-lei.js (citações sem lei nomeada são desta lei).
  const LEIS = [
    { arq: 'constituicao-federal-1988.md', sigla: 'CF', nome: 'Constituição Federal de 1988', ref: 'cf', grupo: 'Leis' },
    { arq: 'lei-5172-1966-ctn.md', sigla: 'CTN', nome: 'Código Tributário Nacional (Lei 5.172/1966)', ref: 'ctn', grupo: 'Leis' },
    { arq: 'lei-complementar-101-2000-lrf.md', sigla: 'LRF', nome: 'Lei de Responsabilidade Fiscal (LC 101/2000)', ref: 'lrf', grupo: 'Leis' },
    { arq: 'lei-complementar-116-2003-iss.md', sigla: 'LC 116', nome: 'ISS (LC 116/2003)', ref: 'lc116', grupo: 'Leis' },
    { arq: 'lei-4320-1964.md', sigla: 'Lei 4.320', nome: 'Direito financeiro (Lei 4.320/1964)', ref: 'l4320', grupo: 'Leis' },
    { arq: 'lei-8137-1990-crimes-ordem-tributaria.md', sigla: 'Lei 8.137', nome: 'Crimes contra a ordem tributária (Lei 8.137/1990)', ref: 'ct', grupo: 'Leis' },
    { arq: 'lei-12527-2011-lai.md', sigla: 'LAI', nome: 'Acesso à Informação (Lei 12.527/2011)', ref: 'lai', grupo: 'Leis' },
    { arq: 'lei-13709-2018-lgpd.md', sigla: 'LGPD', nome: 'LGPD (Lei 13.709/2018)', ref: 'lgpd', grupo: 'Leis' },
    { arq: 'lei-14133-2021-licitacoes.md', sigla: 'Lei 14.133', nome: 'Licitações e Contratos (Lei 14.133/2021)', ref: 'l14133', grupo: 'Leis' },
    { arq: 'lei-9784-1999-processo-administrativo.md', sigla: 'Lei 9.784', nome: 'Processo administrativo (Lei 9.784/1999)', ref: 'l9784', grupo: 'Leis' },
    { arq: 'lei-14063-2020-assinaturas-eletronicas.md', sigla: 'Lei 14.063', nome: 'Assinaturas eletrônicas (Lei 14.063/2020)', ref: 'l14063', grupo: 'Leis' },
    { arq: 'lei-6404-1976.md', sigla: 'Lei 6.404', nome: 'Sociedades por Ações (Lei 6.404/1976)', ref: 'l6404', grupo: 'Leis' },
    { arq: 'decreto-1171-1994-etica.md', sigla: 'Dec. 1.171', nome: 'Código de Ética (Decreto 1.171/1994)', ref: null, grupo: 'Leis' },
    { arq: 'mcasp-11.md', sigla: 'MCASP', nome: 'MCASP, 11ª edição', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tsp-11-r1.md', sigla: 'NBC TSP 11', nome: 'NBC TSP 11 (R1) — Demonstrações contábeis', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-ec.md', sigla: 'NBC TG EC', nome: 'NBC TG Estrutura Conceitual', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-03.md', sigla: 'NBC TG 03', nome: 'NBC TG 03 (R3) — Fluxos de caixa', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-07.md', sigla: 'NBC TG 07', nome: 'NBC TG 07 (R2) — Subvenção governamental', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-16.md', sigla: 'NBC TG 16', nome: 'NBC TG 16 (R2) — Estoques', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-23.md', sigla: 'NBC TG 23', nome: 'NBC TG 23 (R2) — Políticas, estimativas e erros', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-26.md', sigla: 'NBC TG 26', nome: 'NBC TG 26 (R5) — Apresentação das demonstrações', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tg-27.md', sigla: 'NBC TG 27', nome: 'NBC TG 27 (R4) — Ativo imobilizado', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tsp-ec-r1.md', sigla: 'NBC TSP EC', nome: 'NBC TSP Estrutura Conceitual (R1)', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-tsp-07.md', sigla: 'NBC TSP 07', nome: 'NBC TSP 07 — Ativo imobilizado (setor público)', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-ta-330-r1.md', sigla: 'NBC TA 330', nome: 'NBC TA 330 (R1) — Resposta aos riscos', ref: null, grupo: 'Normas e manuais' },
    { arq: 'nbc-ta-530.md', sigla: 'NBC TA 530', nome: 'NBC TA 530 — Amostragem', ref: null, grupo: 'Normas e manuais' },
  ];
  const porArq = Object.fromEntries(LEIS.map(l => [l.arq, l]));
  const porRef = Object.fromEntries(LEIS.filter(l => l.ref).map(l => [l.ref, l]));

  // Jurisprudência do STF por artigo: dados/jurisprudencia/<ref>.json, gerado por tools/stf_jurisprudencia.py.
  const juris = {};
  const COM_JURIS = new Set(['cf', 'ctn', 'lrf', 'lc116', 'l4320', 'ct', 'lai', 'lgpd', 'l14133', 'l9784', 'l6404']);
  function carregarJuris(ref) {
    if (!COM_JURIS.has(ref)) return Promise.resolve(null);
    return juris[ref] || (juris[ref] = fetch(`dados/jurisprudencia/${ref}.json`).then(r => r.ok ? r.json() : null).catch(() => null));
  }
  // Botão "STF n" antes da linha do artigo (fora da linha: o texto dela é a base das posições das marcações).
  function botaoSTF(J, ref, L) {
    const a = J && L.tipo === 'art' && !L.adct && J.arts[L.k];
    if (!a) return '';
    const n = a.n || 0, ns = (a.sum || []).length, destaque = (a.sum || []).some(x => x.vinc);
    const dica = [n ? `${n} acórdão${n > 1 ? 's' : ''} do STF cita${n > 1 ? 'm' : ''} este artigo` : '',
      ns ? `${ns} súmula${ns > 1 ? 's' : ''}` : '', destaque ? 'tem súmula vinculante' : ''].filter(Boolean).join(' · ');
    return `<button type="button" class="ls-stf${destaque ? ' ls-stf-forte' : ''}" data-ref="${esc(ref)}" data-k="${esc(L.k)}" data-rot="${esc(L.rot.replace(/(\d)o\b/, '$1º').replace(/\.$/, ''))}" title="${esc(dica)}" aria-label="Jurisprudência do STF sobre o ${esc(L.rot)}: ${esc(dica)}">STF <span>${n + ns}</span></button>`;
  }

  const TIPOS = {
    am: { nome: 'Grifo amarelo', cat: 'cor' }, vd: { nome: 'Grifo verde', cat: 'cor' },
    az: { nome: 'Grifo azul', cat: 'cor' }, rs: { nome: 'Grifo rosa', cat: 'cor' },
    sub: { nome: 'Sublinhado', cat: 'sub' }, neg: { nome: 'Negrito', cat: 'neg' },
  };

  // ---------- armazenamento ----------
  const CH_MARCAS = 'leiseca-marcas-v1', CH_POS = 'leiseca-pos-v1';
  const ler = (k, d) => { try { return JSON.parse(localStorage.getItem(k)) || d; } catch (_) { return d; } };
  const marcas = ler(CH_MARCAS, {});
  const pos = ler(CH_POS, { ultima: null, linha: {} });
  const salvarMarcas = () => { try { localStorage.setItem(CH_MARCAS, JSON.stringify(marcas)); } catch (_) {} };
  const salvarPos = () => { try { localStorage.setItem(CH_POS, JSON.stringify(pos)); } catch (_) {} };
  const marcasDe = arq => marcas[arq] || (marcas[arq] = []);
  const novoId = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

  // ---------- leitura e análise do .md ----------
  const norm = n => n.replace(/(\d)o\b/g, '$1').replace(/[.º°ª\s]/g, '').toLowerCase();
  const limpa = t => t.replace(/\*\*/g, '').replace(/`/g, '').replace(/^\*(.+)\*$/, '$1').trim();

  // Nota de rodapé do MCASP ("> Nota 1: ..."): a base legal citada e, quando vem junto, o texto da lei transcrito.
  // Só a primeira 'Art. N' com A maiúsculo abre a transcrição; "art. 3º § 2º" em minúsculo é só citação.
  function partirNota(rot, resto) {
    const i = resto.search(/(^|\s)Art\.\s*\d/), ini = i < 0 ? -1 : (resto[i] === ' ' ? i + 1 : i);
    const fonte = ini > 0 ? resto.slice(0, ini).trim() : '';
    if (ini === 0 || (ini > 0 && fonte.length <= 240 && /Lei|LRF|Decreto|Portaria|Constitui|CF\b|C[óo]digo|Resolu|NBC|Emenda|art\./.test(fonte)))
      return { kind: 'cit', rot, fonte, lei: resto.slice(ini), txt: [rot, fonte, resto.slice(ini)].filter(Boolean).join(' ') };
    const base = resto.length <= 170 && /^(Lei|LRF|LC|CF|Decreto|Portaria|Constitui|C[óo]digo|Resolu|NBC|Emenda|Instru)/.test(resto);
    return { kind: base ? 'base' : 'obs', rot, resto, txt: (rot + ' ' + resto).trim() };
  }
  // Quebras (só visuais: o texto não muda) para ler § / incisos / alíneas e lançamentos linha a linha.
  const QUEBRA_LEI = /(?<=[.:;\]]\s)(?=§\s*\d|Parágrafo único|[IVX]{1,5}\s*[-–]\s|[a-z]\)\s)/;
  const QUEBRA_LANC = /(?=Natureza da informa[çc][ãa]o:)|(?<=\s)(?=[DC]\s\d(?:\.[\dx]+){2,})/;
  const clsQ = s => /^(§|Parágrafo)/.test(s) ? 'ls-qb ls-q-par' : /^[IVX]+\s*[-–]/.test(s) ? 'ls-qb ls-q-inc' : /^[a-z]\)/.test(s) ? 'ls-qb ls-q-ali' : 'ls-qb ls-q-cap';
  const clsL = s => /^Natureza/.test(s) ? 'ls-qb ls-lc-n' : /^D\s/.test(s) ? 'ls-qb ls-lc-d' : /^C\s/.test(s) ? 'ls-qb ls-lc-c' : 'ls-qb';

  const cache = {};
  async function carregar(lei) {
    if (cache[lei.arq]) return cache[lei.arq];
    const r = await fetch('leis/' + lei.arq);
    if (!r.ok) throw new Error('lei');
    const md = await r.text(), src = md.split('\n');
    const brutas = src.map((t, i) => ({ i, t }));
    const linhas = [], arts = {}, manual = lei.grupo === 'Normas e manuais';
    let art = null, adct = false, ai = null;
    for (const { i, t: raw } of brutas) {
      const t = raw.trim();
      if (!t) continue;
      let m;
      if ((m = raw.match(/^(#{1,6})\s+(.*)/))) {
        const nv = m[1].length;
        if (nv === 2) adct = /Transit[óo]rias/i.test(m[2]);
        linhas.push({ i, tipo: 'h', nv, txt: limpa(m[2]) });
        art = null; ai = null;
        continue;
      }
      if ((m = t.match(/^\*\*(Art\.?\s*[^*]+?)\*\*\s*(.*)$/))) {
        art = m[1].replace(/\s+/g, ' ').trim();
        const k = (adct ? 'adct:' : '') + norm(art.replace(/^Art\.?/, ''));
        ai = linhas.length;
        if (!(k in arts)) arts[k] = ai;
        linhas.push({ i, tipo: 'art', rot: art, txt: limpa(m[2]), k, art, adct, ai });
        continue;
      }
      let tipo = 'p', ext = {};
      const mn = /^>/.test(t) && t.replace(/^>\s*/, '').match(/^(Nota\s+\d+:)\s*(.*)$/);
      if (mn) { tipo = 'nota'; ext = partirNota(mn[1], limpa(mn[2])); }
      else if (manual && /^Art\.\s*\d/.test(t)) tipo = 'qart';
      else if (/^(§|Par[áa]grafo [úu]nico)/i.test(t)) tipo = 'par';
      else if (/^[IVXLC]+(?:-[A-Z])?\s*[-–.]\s/.test(t)) tipo = 'inc';
      else if (/^[a-z]\)\s/.test(t)) tipo = 'ali';
      else if (/^\s+-\s/.test(raw)) tipo = 'li2';
      else if (/^-\s/.test(t)) tipo = 'li';
      else if (/^\*[^*].*\*$/.test(t)) tipo = 'epi';
      else if (manual && /^(Natureza da informa[çc][ãa]o:|[DC]\s\d(\.[\dx]+){2,})/.test(t)) tipo = 'lanc';
      else if (manual && /^Exemplo\b/.test(t)) tipo = 'ex';
      else if (manual && /^(i{1,3}|iv|vi{0,3}|ix|x)\.\s/.test(t)) tipo = 'mi';
      else if (manual && /^[a-z]\.\s/.test(t)) tipo = 'ma';
      else if (manual && /^•\s/.test(t)) tipo = 'mb';
      else if (manual && /^▪\s/.test(t)) tipo = 'mb2';
      // Num manual, o que é artigo/parágrafo/inciso é lei transcrita; nota, lançamento e o resto se separam.
      const q = manual && /^(qart|par|inc|ali)$/.test(tipo);
      const tp = tipo === 'nota' ? 'nota' : q ? 'lei' : /^(lanc|ex)$/.test(tipo) ? 'lanc' : 'man';
      linhas.push({ i, tipo, tp: manual ? tp : null, q, ...ext,
        txt: tipo === 'nota' ? ext.txt : limpa(tipo === 'li' || tipo === 'li2' ? t.replace(/^-\s+/, '') : t), art, adct, ai });
    }
    // Trechos seguidos de lei transcrita formam um bloco só.
    linhas.forEach((L, j) => { if (L.q) { L.qini = !(linhas[j - 1] && linhas[j - 1].q); L.qfim = !(linhas[j + 1] && linhas[j + 1].q); } });
    const porLinha = new Map(linhas.map(L => [L.i, L]));
    return (cache[lei.arq] = { lei, linhas, arts, porLinha });
  }

  // HTML de uma linha. O texto (textContent) não depende dos links: é nele que contam as posições das marcações.
  function htmlLinha(L, lei) {
    if (L.tipo === 'h') {
      const n = Math.min(L.nv + 1, 6);
      return `<h${n} class="ls-l ls-h ls-h${L.nv}" data-l="${L.i}" id="ls-h-${L.i}">${esc(L.txt)}</h${n}>`;
    }
    const ctx = { lei: lei.ref, art: L.art ? (L.adct ? 'ADCT ' : '') + L.art : '', inicio: true };
    const ref = t => window.refLei ? window.refLei.html(t, ctx) : esc(t);
    const blocos = (t, re, cls) => t.split(re).filter(Boolean).map(x => `<span class="${cls(x)}">${ref(x)}</span>`).join('');
    const tp = L.tp ? ` data-tp="${L.tp}"` : '';
    if (L.tipo === 'nota') {
      const rot = `<span class="ls-nota-rot">${esc(L.rot)}</span> `;
      const corpo = L.kind === 'cit'
        ? `<span class="ls-nota-cab">${rot}${L.fonte ? `<span class="ls-nota-fonte">${ref(L.fonte)}</span>` : ''}</span> <span class="ls-nota-lei">${blocos(L.lei, QUEBRA_LEI, clsQ)}</span>`
        : `${rot}<span class="ls-nota-${L.kind === 'base' ? 'fonte' : 'txt'}">${ref(L.resto)}</span>`;
      return `<p class="ls-l ls-nota ls-nota-${L.kind}" data-l="${L.i}"${tp}>${corpo}</p>`;
    }
    const corpo = L.tipo === 'qart' ? blocos(L.txt, QUEBRA_LEI, clsQ) : L.tipo === 'lanc' ? blocos(L.txt, QUEBRA_LANC, clsL) : ref(L.txt);
    if (L.tipo === 'art')
      return `<p class="ls-l ls-art" data-l="${L.i}" data-art="${esc(L.k)}"><b class="ls-rot">${esc(L.rot)}</b> ${corpo}</p>`;
    const cls = `ls-l ls-${L.tipo}${L.q ? ' ls-q' : ''}${L.qini ? ' ls-qini' : ''}${L.qfim ? ' ls-qfim' : ''}`;
    return `<p class="${cls}" data-l="${L.i}"${tp}>${corpo}</p>`;
  }

  // ---------- marcações no DOM ----------
  function envolver(el, a, b, m, ultimo) {
    const w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT), alvos = [];
    let p = 0;
    for (let n; (n = w.nextNode());) {
      const len = n.data.length, s = Math.max(a, p), e = Math.min(b, p + len);
      if (s < e) alvos.push([n, s - p, e - p]);
      p += len;
    }
    alvos.forEach(([n, s, e], j) => {
      let x = n;
      if (s > 0) x = x.splitText(s);
      if (e - s < x.data.length) x.splitText(e - s);
      const sp = document.createElement('span');
      sp.className = 'ls-m ls-' + m.t + (m.n && ultimo && j === alvos.length - 1 ? ' ls-com-nota' : '');
      sp.dataset.id = m.id;
      if (m.n) sp.title = m.n;
      x.replaceWith(sp); sp.appendChild(x);
    });
  }

  // Confere se a marcação ainda bate com o texto da linha; se o .md mudou, procura o trecho na mesma linha.
  function ancorar(el, m) {
    const t = el.textContent;
    if (t.slice(m.a, m.b) === m.x) return true;
    const j = t.indexOf(m.x);
    if (j < 0) return false;
    m.a = j; m.b = j + m.x.length;
    return true;
  }

  const ORDEM = { cor: 0, sub: 1, neg: 2 };
  function pintarLinha(el, arq) {
    const l = +el.dataset.l, lista = marcasDe(arq).filter(m => m.l === l);
    if (!lista.length) return;
    const ult = ultimosDosGrupos(arq);
    lista.sort((x, y) => ORDEM[TIPOS[x.t].cat] - ORDEM[TIPOS[y.t].cat] || x.a - y.a);
    for (const m of lista) if (ancorar(el, m)) envolver(el, m.a, m.b, m, ult.has(m.id));
  }
  function ultimosDosGrupos(arq) {
    const fim = {};
    for (const m of marcasDe(arq)) { const f = fim[m.g]; if (!f || m.l > f.l || (m.l === f.l && m.a > f.a)) fim[m.g] = m; }
    return new Set(Object.values(fim).map(m => m.id));
  }
  function pintarTudo(doc, arq) {
    const linhas = new Set(marcasDe(arq).map(m => m.l));
    for (const l of linhas) { const el = doc.querySelector(`.ls-l[data-l="${l}"]`); if (el) pintarLinha(el, arq); }
  }
  // Redesenha as linhas l (de arq) em todo lugar onde aparecem: no leitor e no modal.
  function redesenhar(arq, ls) {
    const d = cache[arq]; if (!d) return;
    for (const doc of document.querySelectorAll(`.ls-doc[data-arq="${arq}"]`))
      for (const l of ls) {
        const el = doc.querySelector(`.ls-l[data-l="${l}"]`), L = d.porLinha.get(l);
        if (!el || !L) continue;
        const tpl = document.createElement('template');
        tpl.innerHTML = htmlLinha(L, d.lei);
        const novo = tpl.content.firstElementChild;
        if (el.classList.contains('ls-alvo')) novo.classList.add('ls-alvo');
        el.replaceWith(novo); pintarLinha(novo, arq);
      }
    if (atual && atual.lei.arq === arq) listarMarcas();
  }

  // ---------- seleção → trechos {arq, l, a, b} ----------
  function deslocamento(el, node, off) {
    const r = document.createRange(); r.setStart(el, 0); r.setEnd(node, off); return r.toString().length;
  }
  function trechosDaSelecao() {
    const s = getSelection();
    if (!s.rangeCount || s.isCollapsed) return null;
    const r = s.getRangeAt(0), no = r.commonAncestorContainer, base = no.nodeType === 1 ? no : no.parentElement;
    const doc = base && base.closest('.ls-doc');
    if (!doc || !doc.dataset.arq) return null;
    const arq = doc.dataset.arq, els = [...doc.querySelectorAll('.ls-l')].filter(el => r.intersectsNode(el));
    const out = [];
    for (const el of els) {
      const txt = el.textContent;
      let a = el.contains(r.startContainer) ? deslocamento(el, r.startContainer, r.startOffset) : 0;
      let b = el.contains(r.endContainer) ? deslocamento(el, r.endContainer, r.endOffset) : txt.length;
      while (a < b && /\s/.test(txt[a])) a++;
      while (b > a && /\s/.test(txt[b - 1])) b--;
      if (b > a) out.push({ el, l: +el.dataset.l, a, b, x: txt.slice(a, b) });
    }
    return out.length ? { arq, doc, trechos: out } : null;
  }

  // Tira o intervalo [a, b) das marcações da linha l (só da categoria cat, ou de todas), cortando as que sobram.
  function subtrair(arq, l, a, b, cat, txt) {
    const lista = marcasDe(arq), out = [];
    for (const m of lista) {
      if (m.l !== l || m.b <= a || m.a >= b || (cat && TIPOS[m.t].cat !== cat)) { out.push(m); continue; }
      if (m.a < a) out.push({ ...m, id: novoId(), b: a, x: txt.slice(m.a, a) });
      if (m.b > b) out.push({ ...m, id: novoId(), a: b, x: txt.slice(b, m.b) });
    }
    marcas[arq] = out;
  }

  function marcar(t) {
    const sel = trechosDaSelecao();
    if (!sel) return;
    const g = novoId(), ls = new Set();
    for (const tr of sel.trechos) {
      const txt = tr.el.textContent;
      subtrair(sel.arq, tr.l, tr.a, tr.b, t ? TIPOS[t].cat : null, txt);
      if (t) marcasDe(sel.arq).push({ id: novoId(), g, l: tr.l, a: tr.a, b: tr.b, x: tr.x, t, d: Date.now() });
      ls.add(tr.l);
    }
    salvarMarcas();
    getSelection().removeAllRanges();
    esconderBarra();
    redesenhar(sel.arq, ls);
    return { arq: sel.arq, g };
  }

  // ---------- barra de marcação (aparece sobre a seleção) ----------
  const barra = $('#lsBarra'), pop = $('#lsPop');
  function esconderBarra() { barra.hidden = true; }
  function mostrarBarra() {
    const sel = trechosDaSelecao();
    if (!sel) { esconderBarra(); return; }
    const r = getSelection().getRangeAt(0).getBoundingClientRect();
    barra.hidden = false;
    const w = barra.offsetWidth, h = barra.offsetHeight;
    let x = r.left + r.width / 2 - w / 2, y = r.top - h - 10;
    if (y < 8) y = r.bottom + 10;
    barra.style.left = Math.max(8, Math.min(x, innerWidth - w - 8)) + 'px';
    barra.style.top = Math.max(8, Math.min(y, innerHeight - h - 8)) + 'px';
  }
  let tempoSel;
  document.addEventListener('selectionchange', () => { clearTimeout(tempoSel); tempoSel = setTimeout(mostrarBarra, 120); });
  barra.addEventListener('mousedown', e => e.preventDefault()); // não perde a seleção ao clicar na barra
  barra.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    const acao = b.dataset.acao;
    if (acao === 'nota') { const r = marcar('am'); if (r) abrirNota(r.arq, r.g); }
    else if (acao === 'apagar') marcar(null);
    else if (acao === 'copiar') { navigator.clipboard && navigator.clipboard.writeText(getSelection().toString()); esconderBarra(); }
    else marcar(acao);
  });
  // Atalhos com texto selecionado: 1–4 cores, S sublinhar, N negrito, A anotar, Delete apaga.
  document.addEventListener('keydown', e => {
    if (barra.hidden || e.ctrlKey || e.metaKey || e.altKey || e.target.closest('input,textarea,select')) return;
    const k = { 1: 'am', 2: 'vd', 3: 'az', 4: 'rs', s: 'sub', n: 'neg', a: 'nota', Delete: 'apagar', Backspace: 'apagar' }[e.key.length === 1 ? e.key.toLowerCase() : e.key];
    if (!k) return;
    e.preventDefault();
    barra.querySelector(`[data-acao="${k}"]`).click();
  });
  addEventListener('scroll', () => { if (!barra.hidden) mostrarBarra(); fecharPop(); }, true);

  // ---------- balão de uma marcação (clique num trecho marcado) ----------
  let popAlvo = null;
  function fecharPop() { pop.hidden = true; popAlvo = null; }
  function grupo(arq, g) { return marcasDe(arq).filter(m => m.g === g); }
  function abrirPop(sp) {
    const doc = sp.closest('.ls-doc'), arq = doc.dataset.arq, m = marcasDe(arq).find(x => x.id === sp.dataset.id);
    if (!m) return;
    popAlvo = { arq, g: m.g };
    const g = grupo(arq, m.g), nota = (g.find(x => x.n) || {}).n || '';
    pop.innerHTML = `<div class="ls-pop-cores">${Object.keys(TIPOS).map(t =>
      `<button type="button" class="ls-bt ls-bt-${t}${g.some(x => x.t === t) ? ' on' : ''}" data-t="${t}" title="${TIPOS[t].nome}" aria-label="${TIPOS[t].nome}">${t === 'sub' ? '<u>S</u>' : t === 'neg' ? '<b>N</b>' : ''}</button>`).join('')}</div>` +
      (nota ? `<p class="ls-pop-nota">${esc(nota)}</p>` : '') +
      `<div class="ls-pop-acoes"><button type="button" data-a="nota">${nota ? 'Editar nota' : 'Anotar'}</button><button type="button" data-a="tirar">Remover</button></div>`;
    pop.hidden = false;
    const r = sp.getBoundingClientRect(), w = pop.offsetWidth, h = pop.offsetHeight;
    pop.style.left = Math.max(8, Math.min(r.left, innerWidth - w - 8)) + 'px';
    pop.style.top = (r.bottom + 6 + h > innerHeight ? r.top - h - 6 : r.bottom + 6) + 'px';
  }
  pop.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b || !popAlvo) return;
    const { arq, g } = popAlvo, membros = grupo(arq, g), ls = new Set(membros.map(m => m.l));
    if (b.dataset.t) {
      for (const m of membros) {
        // trocar para outra categoria (ex.: grifo → sublinhado) mantém o grifo e acrescenta a nova marca
        if (TIPOS[m.t].cat === TIPOS[b.dataset.t].cat) m.t = b.dataset.t;
        else if (!marcasDe(arq).some(x => x.l === m.l && x.t === b.dataset.t && x.a <= m.a && x.b >= m.b))
          marcasDe(arq).push({ id: novoId(), g: g + ':' + b.dataset.t, l: m.l, a: m.a, b: m.b, x: m.x, t: b.dataset.t, d: Date.now() });
      }
    } else if (b.dataset.a === 'tirar') {
      marcas[arq] = marcasDe(arq).filter(m => m.g !== g);
    } else if (b.dataset.a === 'nota') { fecharPop(); abrirNota(arq, g); return; }
    salvarMarcas(); fecharPop(); redesenhar(arq, ls);
  });

  // ---------- nota ----------
  const dlgNota = $('#lsNota');
  function abrirNota(arq, g) {
    const membros = grupo(arq, g); if (!membros.length) return;
    dlgNota.querySelector('.ls-nota-trecho').textContent = membros.map(m => m.x).join(' … ');
    const ta = dlgNota.querySelector('textarea');
    ta.value = (membros.find(m => m.n) || {}).n || '';
    dlgNota.onclose = () => {
      if (dlgNota.returnValue !== 'ok') return;
      const v = ta.value.trim();
      for (const m of membros) { if (v) m.n = v; else delete m.n; }
      salvarMarcas(); redesenhar(arq, new Set(membros.map(m => m.l)));
    };
    dlgNota.returnValue = '';
    dlgNota.showModal(); ta.focus();
  }

  document.addEventListener('click', e => {
    if (!pop.hidden && !pop.contains(e.target)) fecharPop();
    const sp = e.target.closest('.ls-doc .ls-m');
    if (sp && !e.target.closest('a') && getSelection().isCollapsed) abrirPop(sp);
  });

  // ---------- leitor ----------
  const sel = $('#lsLei'), docEl = $('#lsDoc'), texto = $('#lsTexto'), sumario = $('#lsSum'), listaM = $('#lsMarcas');
  let atual = null;
  const grupos = [...new Set(LEIS.map(l => l.grupo))];
  sel.innerHTML = grupos.map(gr => `<optgroup label="${esc(gr)}">${LEIS.filter(l => l.grupo === gr).map(l =>
    `<option value="${esc(l.arq)}">${esc(l.nome)}</option>`).join('')}</optgroup>`).join('');

  // Nos manuais (MCASP, NBC): botões para ver só o manual, só a lei transcrita, só as notas ou só os lançamentos.
  const CH_VER = 'leiseca-ver-v1', legenda = $('#lsLegenda');
  const oculto = new Set(ler(CH_VER, []));
  function aplicarVer(lei) {
    const manual = lei.grupo === 'Normas e manuais';
    legenda.hidden = !manual;
    for (const b of legenda.querySelectorAll('.ls-leg-b')) {
      b.setAttribute('aria-pressed', String(!oculto.has(b.dataset.tp)));
      docEl.classList.toggle('ls-sem-' + b.dataset.tp, manual && oculto.has(b.dataset.tp));
    }
  }
  legenda.addEventListener('click', e => {
    const b = e.target.closest('.ls-leg-b'); if (!b) return;
    oculto.has(b.dataset.tp) ? oculto.delete(b.dataset.tp) : oculto.add(b.dataset.tp);
    try { localStorage.setItem(CH_VER, JSON.stringify([...oculto])); } catch (_) {}
    aplicarVer(atual.lei);
  });

  async function abrirLei(arq, ir) {
    const lei = porArq[arq] || LEIS[0];
    sel.value = lei.arq;
    docEl.dataset.arq = lei.arq;
    docEl.innerHTML = '<p class="ls-aviso">Carregando…</p>';
    let d;
    try { d = await carregar(lei); } catch (_) { docEl.innerHTML = '<p class="ls-aviso">Não foi possível carregar o texto. Abra o site pelo iniciar.py.</p>'; return; }
    const J = await carregarJuris(lei.ref);
    if (docEl.dataset.arq !== lei.arq) return; // trocou de lei enquanto carregava
    atual = d;
    document.title = `${lei.sigla} — Lei seca`;
    docEl.innerHTML = d.linhas.map(L => botaoSTF(J, lei.ref, L) + htmlLinha(L, lei)).join('');
    pintarTudo(docEl, lei.arq);
    aplicarVer(lei);
    montarSumario(d);
    listarMarcas();
    pos.ultima = lei.arq; salvarPos();
    $('#lsArt').disabled = !Object.keys(d.arts).length;
    if (ir && ir.art) irArtigo(ir.art, ir.adct);
    else if (ir && ir.linha != null) irLinha(ir.linha, false);
    else { const l = pos.linha[lei.arq]; l != null ? irLinha(l, false) : texto.scrollTo(0, 0); }
  }

  function montarSumario(d) {
    const hs = d.linhas.filter(L => L.tipo === 'h' && L.nv > 1);
    sumario.innerHTML = hs.length ? hs.map(L =>
      `<a href="#ls-h-${L.i}" class="ls-s${Math.min(L.nv, 5)}" data-l="${L.i}">${esc(L.txt)}</a>`).join('')
      : '<p class="ls-aviso">Esta lei não tem divisões.</p>';
  }
  sumario.addEventListener('click', e => {
    const a = e.target.closest('a[data-l]'); if (!a) return;
    e.preventDefault(); irLinha(+a.dataset.l, true); fecharGaveta();
  });

  function irLinha(l, piscar) {
    const el = docEl.querySelector(`.ls-l[data-l="${l}"]`);
    if (!el) return;
    texto.scrollTop = el.offsetTop - texto.offsetTop - 16;
    if (piscar) { el.classList.remove('ls-pisca'); void el.offsetWidth; el.classList.add('ls-pisca'); }
  }
  function irArtigo(v, adct) {
    if (!atual) return false;
    const k = (adct ? 'adct:' : '') + norm(String(v).replace(/^\s*art(igo)?\.?\s*/i, ''));
    const p = atual.arts[k];
    if (p == null) return false;
    irLinha(atual.linhas[p].i, true);
    return true;
  }
  $('#lsIr').addEventListener('submit', e => {
    e.preventDefault();
    const inp = $('#lsArt'), v = inp.value.trim();
    if (!v) return;
    const adct = /adct/i.test(v), ok = irArtigo(v.replace(/adct/i, ''), adct);
    inp.setCustomValidity(ok ? '' : 'Artigo não encontrado nesta lei');
    inp.reportValidity();
    if (ok) { inp.value = ''; fecharGaveta(); }
  });
  $('#lsArt').addEventListener('input', e => e.target.setCustomValidity(''));
  sel.addEventListener('change', () => abrirLei(sel.value));

  // Guarda a linha do topo da tela, para voltar a ela.
  let tempoPos;
  texto.addEventListener('scroll', () => {
    clearTimeout(tempoPos);
    tempoPos = setTimeout(() => {
      if (!atual) return;
      const r = texto.getBoundingClientRect(), el = document.elementFromPoint(r.left + r.width / 2, r.top + 24);
      const l = el && el.closest('.ls-l');
      if (l) { pos.linha[atual.lei.arq] = +l.dataset.l; salvarPos(); }
    }, 300);
  });

  // ---------- lista de marcações da lei aberta ----------
  function rotulo(d, l) {
    const L = d.porLinha.get(l); if (!L) return '';
    if (L.ai != null) return d.linhas[L.ai].rot;
    for (let j = d.linhas.indexOf(L); j >= 0; j--) if (d.linhas[j].tipo === 'h') return d.linhas[j].txt;
    return '';
  }
  function listarMarcas() {
    if (!atual) return;
    const arq = atual.lei.arq, gs = {};
    for (const m of marcasDe(arq)) (gs[m.g] = gs[m.g] || []).push(m);
    const lista = Object.values(gs).map(g => g.sort((x, y) => x.l - y.l || x.a - y.a)).sort((x, y) => x[0].l - y[0].l || x[0].a - y[0].a);
    $('#lsNMarcas').textContent = lista.length ? `(${lista.length})` : '';
    listaM.innerHTML = lista.length ? lista.map(g => {
      const n = (g.find(m => m.n) || {}).n;
      return `<button type="button" class="ls-mi" data-l="${g[0].l}"><span class="ls-mi-onde"><i class="ls-chip ls-${g[0].t}"></i>${esc(rotulo(atual, g[0].l))}</span>` +
        `<span class="ls-mi-x">${esc(g.map(m => m.x).join(' … ').slice(0, 220))}</span>${n ? `<span class="ls-mi-n">${esc(n)}</span>` : ''}</button>`;
    }).join('') : '<p class="ls-aviso">Nada marcado nesta lei. Selecione um trecho do texto para grifar, sublinhar ou anotar.</p>';
  }
  listaM.addEventListener('click', e => {
    const b = e.target.closest('.ls-mi'); if (!b) return;
    irLinha(+b.dataset.l, true); fecharGaveta();
  });

  // abas do painel
  for (const t of document.querySelectorAll('.ls-aba')) t.addEventListener('click', () => {
    for (const x of document.querySelectorAll('.ls-aba')) x.setAttribute('aria-selected', x === t);
    sumario.hidden = t.dataset.p !== 'sum'; listaM.hidden = t.dataset.p !== 'marcas';
  });

  // gaveta (tela estreita)
  const app = $('.ls-app');
  function fecharGaveta() { app.classList.remove('ls-gaveta'); }
  // Tela larga: a barra lateral pode ser ocultada (fica oculta nas próximas visitas); "\\" alterna.
  const CH_LADO = 'leiseca-sem-lado', estreita = () => matchMedia('(max-width:900px)').matches;
  function ocultarLado(sim) {
    app.classList.toggle('ls-sem-lado', sim);
    try { localStorage.setItem(CH_LADO, sim ? '1' : ''); } catch (_) {}
  }
  try { if (localStorage.getItem(CH_LADO)) app.classList.add('ls-sem-lado'); } catch (_) {}
  $('#lsOcultar').addEventListener('click', () => { if (estreita()) fecharGaveta(); else { ocultarLado(true); $('#lsMenu').focus(); } });
  $('#lsMenu').addEventListener('click', () => {
    if (estreita()) app.classList.toggle('ls-gaveta');
    else { ocultarLado(false); $('#lsLei').focus(); }
  });
  document.addEventListener('keydown', e => {
    if (e.key !== '\\' || e.ctrlKey || e.metaKey || e.altKey || e.target.closest('input,textarea,select') || estreita() || $('#lsModal').open) return;
    e.preventDefault(); ocultarLado(!app.classList.contains('ls-sem-lado'));
  });
  $('#lsFundo').addEventListener('click', fecharGaveta);

  // ---------- modal da citação ----------
  const modal = $('#lsModal'), mCorpo = $('#lsModalCorpo'), mTit = $('#lsModalTit'), mVoltar = $('#lsModalVoltar'), mIr = $('#lsModalIr');
  let pilha = [];
  const so = t => t.normalize('NFD').replace(/[^a-zA-Z0-9]/g, '').toLowerCase();

  async function mostrarCitacao(c) {
    const lei = porRef[c.lei];
    mTit.textContent = `${lei ? lei.sigla : ''} · ${c.cit}`;
    mVoltar.hidden = pilha.length < 2;
    mCorpo.innerHTML = '<p class="ls-aviso">Carregando…</p>';
    mCorpo.dataset.arq = lei ? lei.arq : '';
    mIr.hidden = true;
    if (!lei) { mCorpo.innerHTML = '<p class="ls-aviso">O site não tem o texto desta lei.</p>'; return; }
    let d, trecho, J;
    try { d = await carregar(lei); trecho = await window.trechoLei('leis/' + lei.arq, c.cit, c.adct); J = await carregarJuris(lei.ref); }
    catch (_) { mCorpo.innerHTML = '<p class="ls-aviso">Não foi possível carregar o texto da lei.</p>'; return; }
    if (pilha[pilha.length - 1] !== c) return;
    // trechoLei diz quais artigos e quais linhas a citação pega; aqui mostramos o artigo inteiro com elas em destaque.
    const tmp = document.createElement('div');
    tmp.innerHTML = trecho || '';
    const blocos = [...tmp.querySelectorAll('.tl-bloco')];
    if (!blocos.length) { mCorpo.innerHTML = `<p class="ls-aviso">Não achei esse artigo no texto da ${esc(lei.sigla)}.</p>`; return; }
    let html = '', primeiro = null;
    const alvos = [];
    for (const bl of blocos) {
      const rot = bl.querySelector('b'), k = (c.adct ? 'adct:' : '') + norm((rot ? rot.textContent : '').replace(/^Art\.?/, ''));
      const p = d.arts[k];
      if (p == null) continue;
      if (primeiro == null) primeiro = d.linhas[p].i;
      const ps = [...bl.querySelectorAll('p')], hl = ps.filter(x => x.classList.contains('tl-hl'));
      const todos = hl.length === ps.length;
      const alvo = new Set(todos ? [] : hl.map(x => so(x.textContent)));
      html += '<div class="ls-bloco">';
      for (let j = p; j < d.linhas.length && (j === p || d.linhas[j].ai === p); j++) {
        const L = d.linhas[j];
        let h = (j === p ? botaoSTF(J, lei.ref, L) : '') + htmlLinha(L, lei);
        if (alvo.has(so((L.rot ? L.rot + ' ' : '') + L.txt))) { h = h.replace('class="ls-l ', 'class="ls-l ls-alvo '); alvos.push(L.i); }
        html += h;
      }
      html += '</div>';
    }
    if (!html) { mCorpo.innerHTML = `<p class="ls-aviso">Não achei esse artigo no texto da ${esc(lei.sigla)}.</p>`; return; }
    mCorpo.innerHTML = html;
    pintarTudo(mCorpo, lei.arq);
    mCorpo.scrollTop = 0;
    const a = mCorpo.querySelector('.ls-alvo');
    if (a && a.offsetTop > mCorpo.clientHeight * .6) mCorpo.scrollTop = a.offsetTop - mCorpo.offsetTop - 48;
    mIr.hidden = false;
    mIr.textContent = atual && atual.lei.arq === lei.arq ? 'Ir para o artigo' : `Abrir na ${lei.sigla}`;
    mIr.onclick = () => { modal.close(); if (atual && atual.lei.arq === lei.arq) irLinha(alvos[0] != null ? alvos[0] : primeiro, true); else abrirLei(lei.arq, { linha: alvos[0] != null ? alvos[0] : primeiro }); };
  }

  // ---------- jurisprudência do STF no modal ----------
  const dataBR = d => d ? d.split('-').reverse().join('/') : '';
  const comLinks = t => window.refLei ? window.refLei.html(t, { lei: null, art: '', inicio: false }) : esc(t);
  async function mostrarSTF(c) {
    const lei = porRef[c.ref];
    mTit.textContent = `STF · ${lei.sigla}, ${c.rot}`;
    mVoltar.hidden = pilha.length < 2;
    mCorpo.dataset.arq = '';
    mCorpo.innerHTML = '<p class="ls-aviso">Carregando…</p>';
    const J = await carregarJuris(c.ref);
    if (pilha[pilha.length - 1] !== c) return;
    const a = J && J.arts[c.k];
    if (!a) { mCorpo.innerHTML = '<p class="ls-aviso">Sem jurisprudência do STF guardada para este artigo.</p>'; return; }
    const ac = a.ac || [], sum = a.sum || [];
    const disp = x => x.disp && x.disp.length ? `<p class="ls-j-disp">Cita: ${x.disp.map(d => esc(c.rot + (/^\d|^§|^[IVXLC]+\b|^[a-z]\b|^caput/.test(d) ? ', ' : ' ') + d)).join(' · ')}</p>` : '';
    let html = `<p class="ls-j-intro">${a.n ? `<b>${a.n}</b> acórdão${a.n > 1 ? 's' : ''} do STF cita${a.n > 1 ? 'm' : ''} o ${esc(c.rot)} da ${esc(lei.sigla)}. ` +
      `Aqui ${ac.length < a.n ? `os ${ac.length} mais relevantes` : ac.length > 1 ? 'todos' : 'ele'}: primeiro os com tese de repercussão geral, depois os do Plenário, os mais recentes antes.` : ''}` +
      ` Fonte: <a href="https://jurisprudencia.stf.jus.br/" target="_blank" rel="noopener noreferrer">jurisprudencia.stf.jus.br</a>, consultado em ${dataBR(J.atualizado)}.</p>`;
    if (sum.length) html += `<h3 class="ls-j-h">Súmulas</h3>` + sum.map(s => `<article class="ls-j ls-j-sum${s.vinc ? ' ls-j-vinc' : ''}">` +
      `<header><b>${esc(s.t)}</b>${s.vinc ? '<span class="ls-j-tag">Vinculante</span>' : ''}<span>${dataBR(s.data)}</span></header>` +
      `<p class="ls-j-tese">${comLinks(s.texto)}</p>${disp(s)}${s.url ? `<a class="ls-j-link" href="${esc(s.url)}" target="_blank" rel="noopener noreferrer">Aprovação ↗</a>` : ''}</article>`).join('');
    if (ac.length) html += `<h3 class="ls-j-h">Acórdãos</h3>` + ac.map(x => `<article class="ls-j${x.tese ? ' ls-j-rg' : ''}">` +
      `<header><b>${esc(x.t)}</b>${x.tese ? `<span class="ls-j-tag">${esc(x.tipo_tese || 'Tese')}</span>` : ''}<span>${esc(x.org === 'Tribunal Pleno' ? 'Plenário' : x.org || '')} · ${dataBR(x.data)}${x.rel ? ' · Rel. ' + esc(x.rel) : ''}</span></header>` +
      (x.tese ? `<div class="ls-j-tese"><span class="ls-j-rot">Tese${x.tema ? ' — Tema ' + esc(x.tema.split(' - ')[0]) : ''}</span>${comLinks(x.tese)}${x.tema && x.tema.includes(' - ') ? `<span class="ls-j-tema">${esc(x.tema.split(' - ').slice(1).join(' - '))}</span>` : ''}</div>` : '') +
      disp(x) +
      (x.ementa ? `<p class="ls-j-ementa">${comLinks(x.ementa)}</p><button type="button" class="ls-j-mais">Ler ementa inteira</button>` : '') +
      (x.url ? ` <a class="ls-j-link" href="${esc(x.url)}" target="_blank" rel="noopener noreferrer">Inteiro teor ↗</a>` : '') + '</article>').join('');
    mCorpo.innerHTML = html;
    mCorpo.scrollTop = 0;
    for (const e of mCorpo.querySelectorAll('.ls-j-ementa')) if (e.scrollHeight <= e.clientHeight + 2) e.nextElementSibling.hidden = true;
    mIr.hidden = false;
    const aqui = atual && atual.lei.arq === lei.arq;
    mIr.textContent = aqui ? 'Ir para o artigo' : `Abrir na ${lei.sigla}`;
    mIr.onclick = () => { modal.close(); if (aqui) irArtigo(c.k); else abrirLei(lei.arq, { art: c.k }); };
  }
  mCorpo.addEventListener('click', e => {
    const b = e.target.closest('.ls-j-mais'); if (!b) return;
    const ab = b.previousElementSibling.classList.toggle('aberta');
    b.textContent = ab ? 'Recolher ementa' : 'Ler ementa inteira';
  });

  const mostrar = c => c.stf ? mostrarSTF(c) : mostrarCitacao(c);
  function empilhar(c) {
    if (!modal.open) { pilha = []; modal.showModal(); }
    pilha.push(c);
    mostrar(c);
  }
  window.refLei && (window.refLei.aoClicar = b => {
    if (!getSelection().isCollapsed) return;
    empilhar({ lei: b.dataset.lei, cit: b.dataset.cit, adct: !!b.dataset.adct });
  });
  document.addEventListener('click', e => {
    const b = e.target.closest('.ls-stf'); if (!b) return;
    empilhar({ stf: true, ref: b.dataset.ref, k: b.dataset.k, rot: b.dataset.rot });
  });
  mVoltar.addEventListener('click', () => { if (pilha.length > 1) { pilha.pop(); mostrar(pilha[pilha.length - 1]); } });
  $('#lsModalX').addEventListener('click', () => modal.close());
  modal.addEventListener('click', e => { if (e.target === modal) modal.close(); }); // clique fora da caixa
  modal.addEventListener('close', () => { pilha = []; esconderBarra(); fecharPop(); });

  // ---------- início ----------
  const q = new URLSearchParams(location.search);
  const qLei = q.get('lei'), alvoLei = qLei && (porRef[qLei] || LEIS.find(l => l.arq.startsWith(qLei) || l.sigla.toLowerCase() === qLei.toLowerCase()));
  if (alvoLei) abrirLei(alvoLei.arq, q.get('art') ? { art: q.get('art'), adct: q.has('adct') } : null);
  else abrirLei(pos.ultima || LEIS[0].arq);
})();
