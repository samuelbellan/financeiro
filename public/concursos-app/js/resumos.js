// Resumos dos erros (resumos.html): o resumo de cada matéria, escrito a partir das questões que o aluno errou
// (dados/resumos.json, gerado por tools/gerar_dados.py), com a lista dessas questões em cada tópico:
// o que foi marcado, por que estava errado e qual é a certa. A lista é montada na hora, com as respostas salvas
// no navegador; erros feitos depois da geração dos resumos aparecem em "Erros novos", na primeira página.
// A página não rola: cada tópico é repartido em páginas do tamanho da janela, com Anterior/Próxima, setas do
// teclado e o tópico atual guardado em #t=N. Cada tópico traz o texto exato da lei (blocos montados pelo build) e,
// depois de cada trecho, a explicação em linguagem simples; os artigos citados abrem o dispositivo num balão.
// As questões erradas de cada tópico aparecem em ordem sorteada a cada abertura da página (para o aluno lembrar do
// erro, e não da sequência das respostas). O trecho da lei de um artigo em que ele errou questão ganha destaque, com
// as questões (errosQuestoes(), de js/erros-questoes.js: some quando a questão é dominada em "Refazer erros").
(async function () {
  const $ = document.getElementById('rs'), L = 'ABCDE';
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const ler = k => { try { const v = JSON.parse(localStorage.getItem(k) || '{}'); return v && typeof v === 'object' && !Array.isArray(v) ? v : {}; } catch (_) { return {}; } };
  const corta = (s, n) => { s = String(s).replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1).trimEnd() + '…' : s; };
  // Do enunciado, só o comando final (os textos de apoio são longos).
  const comando = stem => { const p = String(stem).split(/\n\s*\n/).filter(Boolean); return corta(p[p.length - 1], 260); };

  // ERROS: "lei|artigo" → {n, itens} das questões erradas (js/erros-questoes.js), buscado junto com o resto; sem ele, sem destaque.
  const [R, SIM, CAD, ERROS] = await Promise.all([carregarJson('dados/resumos.json'), carregarJson('dados/simulados.json'), carregarJson('dados/caderno/atual.json'),
    (window.errosQuestoes ? errosQuestoes() : Promise.resolve(new Map())).catch(() => new Map())]);
  const porId = new Map(SIM.questions.map(q => [q.id, q])), porN = new Map(CAD.map(q => [q.n, q]));
  const resp = ler('simulados-respostas-v1');
  const lomResp = ler('lom_st'), refeito = ler('refazer-v1');
  const dominada = k => { const e = refeito[k]; return !!e && e.box >= 3; };
  const embaralha = a => { for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };
  const errou = id => { const a = resp[id], q = porId.get(id); return !!a && !!q && !(a.ok === true || L[a.c] === a.checked); };

  function cartaoSim(id) {
    const q = porId.get(id), a = resp[id];
    if (!q) return '';
    const fonte = q.source.split(' · ')[0], certa = L.indexOf(q.checked), alts = q.alts || [];
    const feita = dominada('s:' + id);
    let h = `<li class="rs-q${feita ? ' feita' : ''}"><a href="simulados.html#q=${esc(id)}">${esc(fonte)} · questão ${+q.number || esc(q.number)}</a>` +
      (feita ? '<span class="rs-tag">dominada em Refazer erros</span>' : '') + `<div class="rs-stem">${esc(comando(q.stem))}</div>`;
    if (a && errou(id)) {
      h += `<p class="m"><b>Você marcou ${L[a.c]}:</b> ${esc(corta(q.options[a.c] || '', 200))}</p>`;
      const pq = alts[a.c] || (window.porqueAssertivas ? porqueAssertivas(q, a.c) : '');   // assertivas: a nota dos itens julgados errado
      if (pq) h += `<p>${esc(corta(pq, 320))}</p>`;
    }
    h += `<p class="c"><b>Certa, ${q.checked}:</b> ${esc(corta(q.options[certa] || '', 200))}</p>`;
    if (alts[certa]) h += `<p>${esc(corta(alts[certa], 320))}</p>`;
    return h + '</li>';
  }
  function cartaoLom(n) {
    const q = porN.get(n), a = lomResp[n];
    if (!q || !a || a.ok) return '';
    const feita = dominada('c:' + n);
    return `<li class="rs-q${feita ? ' feita' : ''}"><a href="caderno.html#${n}">Caderno · questão ${n}</a>` +
      (feita ? '<span class="rs-tag">dominada em Refazer erros</span>' : '') + `<div class="rs-stem">${esc(corta(q.ref, 90))}</div>` +
      `<p class="m"><b>Você marcou ${L[a.c]}:</b> ${esc(corta(q.opts[a.c] || '', 150))}</p>` + (q.x && q.x[a.c] ? `<p>${esc(corta(q.x[a.c], 260))}</p>` : '') +
      `<p class="c"><b>Certa, ${L[q.a]}:</b> ${esc(corta(q.opts[q.a] || '', 150))}</p>` + (q.x && q.x[q.a] ? `<p>${esc(corta(q.x[q.a], 260))}</p>` : '') + '</li>';
  }

  // Sem rolagem: a página ocupa exatamente a janela. O conteúdo de cada tópico (linhas do resumo e, depois, as
  // questões erradas) é repartido em quantas páginas forem precisas para caber na altura disponível; ao mudar o
  // tamanho da janela, a repartição é refeita. O aluno passa as páginas com os botões ou as setas do teclado.
  const emTopico = new Set(), topicos = [], materias = [];
  let total = 0;
  R.materias.forEach((m, i) => {
    let nMat = 0;
    const ini = topicos.length;
    m.topicos.forEach((t, j) => {
      t.ids.forEach(id => emTopico.add(id));
      const cart = embaralha(t.ids.filter(errou).map(cartaoSim).concat((t.lom || []).map(cartaoLom)).filter(Boolean));
      const abertas = (cart.join('').match(/class="rs-q"/g) || []).length;
      nMat += cart.length;
      topicos.push({ mat: i, onde: `${m.materia} · tópico ${j + 1} de ${m.topicos.length}`, abertura: j === 0 ? m.abertura : '', titulo: t.titulo,
        selo: cart.length ? `<span class="rs-n${abertas ? '' : ' ok'}">${cart.length} ${cart.length === 1 ? 'questão errada' : 'questões erradas'}</span>` : '',
        lei: t.lei, linhas: t.linhas, cart });
    });
    total += nMat;
    materias.push({ nome: m.materia, n: nMat, ini });
  });
  // Erros que não estão em nenhum tópico (cometidos depois de os resumos serem escritos): viram o primeiro bloco.
  const novos = Object.keys(resp).filter(id => errou(id) && !emTopico.has(id));
  if (novos.length) {
    topicos.unshift({ mat: -1, onde: 'Erros novos', abertura: '', titulo: 'Erros novos', selo: `<span class="rs-n">${novos.length}</span>`,
      linhas: [`Questões erradas depois de ${R.gerado.split('-').reverse().join('/')}, ainda sem lugar nos resumos.`], cart: embaralha(novos.map(cartaoSim)) });
    materias.forEach(m => m.ini++);
  }

  $.innerHTML = `<div class="rs-topo"><h1>Resumos dos seus erros</h1>` +
    `<label class="rs-sel">Matéria <select id="rsMat" title="${total} questões erradas nos simulados e no caderno">` +
    (novos.length ? `<option value="0">Erros novos (${novos.length})</option>` : '') +
    materias.map(m => `<option value="${m.ini}">${esc(m.nome)} (${m.n})</option>`).join('') + `</select></label></div>` +
    `<div id="rsPag"></div>` +
    `<nav class="rs-nav" aria-label="Páginas dos resumos"><button type="button" class="btn" id="rsAnt">← Anterior</button>` +
    `<span id="rsCont" aria-live="polite"></span><button type="button" class="btn main" id="rsProx">Próxima →</button></nav>`;
  document.documentElement.classList.add('rs-fixa');

  const pag = document.getElementById('rsPag'), ant = document.getElementById('rsAnt'), prox = document.getElementById('rsProx'),
    cont = document.getElementById('rsCont'), sel = document.getElementById('rsMat');
  // Linha do resumo: texto da lei (objeto vindo do build: rótulo + linhas exatas da lei), linha em branco ou explicação.
  // Na explicação, o rótulo do começo ("Em palavras simples:", "Na prova:"…) vai em negrito e as citações de artigo
  // viram links que abrem o dispositivo num balão (js/ref-lei.js).
  const ROT = /^(Em palavras simples|Na prova|Seu erro|Regra|Teste|Exemplos?|Para fixar|Como resolver|Atenção|Resumindo|Método):\s*/;
  const refs = (s, ctx) => window.refLei && ctx.lei ? refLei.html(s, ctx) : esc(s);
  // Questões erradas do artigo de um trecho da lei ("Art. 136" da lei 'ctn' → chave "ctn|136"), ou undefined.
  const errosDoTrecho = l => l.lei && l.art ? ERROS.get(`${l.lei}|${l.art.replace(/^Art\.\s*/, '')}`) : undefined;
  function linhaHtml(l, lei) {
    if (l && typeof l === 'object') {
      const e = errosDoTrecho(l);
      const aviso = e ? `<p class="rs-errei-tag">Você errou ${e.n} ${e.n === 1 ? 'questão' : 'questões'} sobre este artigo: ` +
        e.itens.slice(0, 3).map(i => `<a href="${esc(i.href)}">${esc(i.rot)}</a>`).join('; ') + (e.itens.length > 3 ? '…' : '') + '</p>' : '';
      return `<blockquote class="rs-lei${e ? ' rs-errei' : ''}"><p class="rs-lei-rot">${esc(l.rot)}</p>${aviso}` +
        l.txt.map(x => `<p>${refs(x, { lei: l.lei, art: l.art, inicio: true })}</p>`).join('') + '</blockquote>';
    }
    if (!l.trim()) return '<p class="vazio"></p>';
    const m = l.match(ROT), corpo = refs(m ? l.slice(m[0].length) : l, { lei });
    return m ? `<p class="rs-rot rs-${m[1] === 'Na prova' || m[1] === 'Seu erro' ? 'prova' : 'simples'}"><b>${esc(m[1])}:</b> ${corpo}</p>` : `<p>${corpo}</p>`;
  }

  // HTML de uma página: cabeçalho do tópico + as linhas e os cartões que couberam nela.
  function montar(t, parte, linhas, cart, primeiroCartao) {
    return `<p class="rs-onde">${esc(t.onde)}${parte ? ' · continuação' : ''}</p>` + (!parte && t.abertura ? `<p class="rs-abre">${esc(t.abertura)}</p>` : '') +
      `<section class="card rs-top"><h2>${esc(t.titulo)} ${t.selo}</h2>` +
      (linhas.length ? `<div class="rs-txt">${linhas.map(l => linhaHtml(l, t.lei)).join('')}</div>` : '') +
      (cart.length ? `<p class="rs-sub">${primeiroCartao ? 'Questões que você errou neste tópico' : 'Questões que você errou (continuação)'}</p><ol class="rs-lista">${cart.join('')}</ol>` : '') +
      '</section>';
  }
  // Janela larga: o resumo fica à esquerda e as questões erradas do tópico, numa coluna lateral (que rola por dentro
  // se a lista for longa). Janela estreita: as questões entram nas páginas, depois do resumo.
  const largo = matchMedia('(min-width: 900px)');
  const lateral = t => `<aside class="rs-lado" aria-label="Questões que você errou neste tópico"><p class="rs-sub">Questões que você errou neste tópico</p><ol class="rs-lista">${t.cart.join('')}</ol></aside>`;
  const duas = (t, parte, linhas) => `<div class="rs-duas"><div class="rs-esq">${montar(t, parte, linhas, [], true)}</div>${lateral(t)}</div>`;
  const cabe = html => { pag.innerHTML = html; const el = pag.querySelector('.rs-esq') || pag; return el.scrollHeight <= el.clientHeight + 1; };
  let paginas = [], atual = 0;
  function repartir() {
    paginas = [];
    topicos.forEach((t, ti) => {
      const aoLado = largo.matches && t.cart.length > 0;
      // O texto entra por blocos (linhas entre duas linhas em branco: o trecho da lei com a explicação), para a
      // explicação não ficar numa página e a lei na outra. Bloco que não cabe numa página vazia é partido em linhas.
      const vazia = l => typeof l === 'string' && !l.trim();
      const blocos = [];
      t.linhas.forEach(l => { if (vazia(l) || !blocos.length || vazia(blocos[blocos.length - 1][0])) blocos.push([l]); else blocos[blocos.length - 1].push(l); });
      const itens = blocos.map(b => ['l', b]).concat(aoLado ? [] : t.cart.map(c => ['c', c]));
      let k = 0, parte = 0, cartoesAntes = 0;
      while (k < itens.length) {
        let linhas = [], cart = [], html = '';
        while (k < itens.length) {
          const [tipo, v] = itens[k];
          const bl = tipo === 'l' ? (linhas.length ? v : v.filter((l, i) => !(vazia(l) && v.slice(0, i + 1).every(vazia)))) : [];  // sem linha em branco no alto da página
          if (tipo === 'l' && !bl.length) { k++; continue; }
          const l2 = linhas.concat(bl), c2 = tipo === 'c' ? cart.concat([v]) : cart;
          const tenta = aoLado ? duas(t, parte, l2) : montar(t, parte, l2, c2, cartoesAntes === 0);
          if (cabe(tenta)) { linhas = l2; cart = c2; html = tenta; k++; }
          else if (!linhas.length && !cart.length) {
            if (tipo === 'l' && bl.length > 1) { itens.splice(k, 1, ...bl.map(l => ['l', [l]])); continue; }   // bloco maior que a página: linha a linha
            linhas = l2; cart = c2; html = tenta; k++;   // um item sozinho sempre entra
          } else break;
        }
        if (!html) break;
        while (linhas.length && typeof linhas[linhas.length - 1] === 'string' && !linhas[linhas.length - 1].trim()) linhas.pop();
        paginas.push({ top: ti, mat: t.mat, html: aoLado ? duas(t, parte, linhas) : montar(t, parte, linhas, cart, cartoesAntes === 0) });
        cartoesAntes += cart.length; parte++;
      }
    });
  }
  function mostrar(n) {
    atual = Math.max(0, Math.min(paginas.length - 1, n));
    const p = paginas[atual];
    if (window.refLei) refLei.fechar();
    pag.innerHTML = p.html;
    pag.scrollTop = 0;
    cont.textContent = `Página ${atual + 1} de ${paginas.length}`;
    ant.disabled = atual === 0; prox.disabled = atual === paginas.length - 1;
    sel.value = String(p.mat < 0 ? 0 : materias[p.mat].ini);
    try { history.replaceState(null, '', '#t=' + (p.top + 1)); } catch (_) {}
  }
  const paginaDoTopico = ti => Math.max(0, paginas.findIndex(p => p.top === ti));
  ant.onclick = () => mostrar(atual - 1);
  prox.onclick = () => mostrar(atual + 1);
  sel.onchange = () => { mostrar(paginaDoTopico(+sel.value)); sel.blur(); };
  addEventListener('keydown', e => {
    if (e.altKey || e.ctrlKey || e.metaKey || /^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) || document.querySelector('dialog[open]')) return;
    if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); mostrar(atual - 1); }
    else if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); mostrar(atual + 1); }
  });
  // Área da página mudou de tamanho (janela redimensionada, zoom, barra do navegador): reparte de novo e volta ao
  // mesmo tópico. ResizeObserver pega qualquer mudança da área, não só o evento "resize" da janela.
  let espera = null, medida = '';
  const refazer = () => {
    const m = pag.clientWidth + 'x' + pag.clientHeight;
    if (m === medida) return;
    medida = m;
    const ti = paginas[atual] ? paginas[atual].top : 0;
    repartir(); mostrar(paginaDoTopico(ti));
  };
  const agendar = () => { clearTimeout(espera); espera = setTimeout(refazer, 120); };
  if (window.ResizeObserver) new ResizeObserver(agendar).observe(pag);
  addEventListener('resize', agendar);
  if (document.fonts && document.fonts.ready) await document.fonts.ready;
  medida = pag.clientWidth + 'x' + pag.clientHeight;
  repartir();
  const h = location.hash.match(/^#t=(\d+)$/);
  mostrar(h ? paginaDoTopico(+h[1] - 1) : 0);
})();
