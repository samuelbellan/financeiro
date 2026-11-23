// Citações de dispositivo dentro de um texto ("previsto no art. 15", "inciso XIII do caput do art. 37 da
// Constituição", "o disposto no § 2º") viram links; o clique abre, junto do link, um balão com o que diz
// o artigo, com o inciso ou parágrafo citado grifado. O texto vem de leis/*.md, via trechoLei() de
// js/texto-lei.js; "Lei inteira" abre o balão móvel de lá.
//   refLei.html(texto, {lei, art, inicio})  HTML escapado, com as citações como links
//     lei     id da lei do texto (a dos flashcards: 'lrf', 'cf'…); citação sem lei nomeada é dela
//     art     dispositivo de onde vem o texto ("Art. 16, § 4º"): "§ 2º" ou "inciso I" soltos são desse artigo
//     inicio  true em linhas da lei: não liga o rótulo do próprio dispositivo no começo ("Art. 18.", "§ 4º")
//   refLei.leiDe(norma)  id da lei de uma norma do site ("LRF" → 'lrf'), ou null
//   refLei.fechar()  fecha o balão
//   refLei.aoClicar  se a página definir (b => …), o clique no link chama essa função em vez de abrir o balão
//                    (a lei seca abre um modal); b é o <a> com data-lei, data-cit e data-adct
(function () {
  'use strict';
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const LEIS = {
    lrf: ['LRF', 'leis/lei-complementar-101-2000-lrf.md'],
    lai: ['LAI', 'leis/lei-12527-2011-lai.md'],
    ct: ['Lei 8.137', 'leis/lei-8137-1990-crimes-ordem-tributaria.md'],
    cf: ['CF', 'leis/constituicao-federal-1988.md'],
    ctn: ['CTN', 'leis/lei-5172-1966-ctn.md'],
    l4320: ['Lei 4.320', 'leis/lei-4320-1964.md'],
    lgpd: ['LGPD', 'leis/lei-13709-2018-lgpd.md'],
    l14133: ['Lei 14.133', 'leis/lei-14133-2021-licitacoes.md'],
    lc116: ['LC 116', 'leis/lei-complementar-116-2003-iss.md'],
    l9784: ['Lei 9.784', 'leis/lei-9784-1999-processo-administrativo.md'],
    l14063: ['Lei 14.063', 'leis/lei-14063-2020-assinaturas-eletronicas.md'],
    l6404: ['Lei 6.404', 'leis/lei-6404-1976.md'],
  };
  // Lei nomeada logo depois da citação; a que aparece primeiro vale. 'propria' = "desta Lei", "deste Código".
  const NOMES = [
    ['outra', /Constitui[çc][ãa]o (?:Estadual|do Estado)/],
    ['propria', /\bdest[ae]\s+(?:Lei|Constitui|C[óo]digo)/],
    ['cf', /Constitui[çc][ãa]o|\bCF\b/],
    ['ctn', /\bCTN\b|C[óo]digo Tribut[áa]rio/],
    ['lrf', /\bLRF\b|Complementar\s*n?[º°.]?\s*101\b|Responsabilidade Fiscal/],
    ['lc116', /Complementar\s*n?[º°.]?\s*116\b/],
    ['lai', /12\.527/], ['ct', /8\.137/], ['lgpd', /13\.709|LGPD/], ['l14133', /14\.133/], ['l4320', /4\.320/],
    ['l9784', /9\.784/], ['l14063', /14\.063/], ['l6404', /6\.404/],
    ['outra', /C[óo]digo (?:Penal|Civil|de Processo)|Decreto|Emenda|Lei\s+(?:Complementar\s+)?n[º°.]?\s*[\d.]+|ADCT|Transit[óo]rias/],
  ];
  function leiNomeada(resto) {
    resto = resto.split(/(?<!\b[Aa]rts?|n[º°])\.\s|;|:/)[0]; // o ponto de "art. 169" não encerra a frase
    let melhor = null, pos = 1e9;
    for (const [k, re] of NOMES) { const m = resto.match(re); if (m && m.index < pos) { pos = m.index; melhor = k; } }
    return melhor;
  }

  // Lei de uma norma pelo nome que o site usa ("LRF", "Licitações (Lei 14.133)", "ISS (LC 116)"…); null se não há texto dela.
  const NORMAS = [['lai', /\bLAI\b|Acesso [àa] Informa/], ['ct', /Crimes contra a ordem/i], ['lc116', /\bLC\s*116\b/]];
  function leiDe(norma) {
    for (const [k, re] of NORMAS.concat(NOMES)) if (LEIS[k] && re.test(norma || '')) return k;
    return null;
  }

  const ROM = '[IVXLC]+(?:-[A-Z])?';
  const NUM = '\\d+(?:\\.\\d+)?[º°o]?(?:-[A-Z])?';
  const ALI = `[Aa]l[ií]neas?\\s+["“']?[a-z]["”']?(?:\\s*(?:,|\\be\\b)\\s*["“']?[a-z]["”']?)*(?![\\wà-ú])`;
  const INC = `[Ii]ncisos?\\s+${ROM}(?:\\s*(?:,|\\be\\b|\\ba\\b)\\s*${ROM})*(?![\\wà-ú])`;
  const PAR = `(?:§§?\\s*${NUM}(?:\\s*(?:,|\\be\\b)\\s*${NUM})*|[Pp]ar[áa]grafo [úu]nico)`;
  const DO = '\\s+(?:do|da|dos|das)\\s+';
  const POS = `(?:\\s*(?:,|\\be\\b)\\s*(?:${PAR}|${ROM}(?=[\\s,;.)])|["“'][a-z]["”'](?=[\\s,;.)])))*`;
  // "arts. 16 e 17", "arts. 148, I, 153, I e II; e 154, II": cada número é outro artigo, com o próprio recorte.
  const ART = `(?:(?:[Aa]rts\\.|[Aa]rtigos)\\s*${NUM}${POS}(?:\\s*[,;]?\\s*(?:\\b[ea]\\b\\s*)?${NUM}(?![\\w§])${POS})*|(?:[Aa]rt\\.|[Aa]rtigo)\\s*${NUM})`;
  const ANCORA = `((?:(?:${ALI}|${INC}|[Cc]aput|${PAR})${DO})*)(${ART})(${POS})`;
  const SOLTA = `(?:${ALI}${DO})?(?:${INC}(?:${DO}(?:[Cc]aput|${PAR}))?|§§?\\s*${NUM}(?:\\s*(?:,|\\be\\b)\\s*${NUM})*)`;
  const RE = new RegExp(`${ANCORA}|(${SOLTA})`, 'g');

  const oNum = s => s.replace(/(\d)o\b/g, '$1º');
  // Partes antes do "art." ("alínea c do inciso I do § 2º do") na ordem que trechoLei entende: §, inciso, alínea.
  function recorte(pre) {
    let par = '', inc = '', ali = '';
    for (const p of pre.split(/\s+(?:do|da|dos|das)\s+/)) {
      if (/^§|^[Pp]ar/.test(p)) par = ', ' + oNum(p);
      else if (/^[Ii]nc/.test(p)) inc = ', ' + p.replace(/^[Ii]ncisos?\s+/, '');
      else if (/^[Aa]l/.test(p)) ali = ', ' + p.replace(/^[Aa]l[ií]neas?\s+/, '').split(/\s*(?:,|\be\b)\s*/).map(x => `"${x.replace(/["“”']/g, '')}"`).join(' e ');
    }
    return par + inc + ali;
  }
  // "Arts. 4º a 7º" → "Arts. 4º, 5º, 6º e 7º", que trechoLei entende.
  const intervalo = s => s.replace(/(\d+)[º°]?\s+a\s+(\d+)[º°]?/g, (x, a, b) => b - a > 0 && b - a < 30
    ? Array.from({ length: b - a + 1 }, (_, i) => `${+a + i}º`).join(', ') : x);
  const artBase = art => { const m = (art || '').match(/Art\.\s*\d+[º°]?(?:-[A-Z])?/); return m ? m[0] : null; };

  window.refLei = {
    html(texto, ctx) {
      texto = String(texto);
      let out = '', i = 0;
      for (const m of texto.matchAll(RE)) {
        const ini = m.index, fim = ini + m[0].length;
        // Rótulo do próprio dispositivo, ou artigo de outra lei transcrito entre aspas (“Art. 126-A. …”).
        if (ctx.inicio && !texto.slice(0, ini).replace(/[\s“"']/g, '')) continue;
        let lei = ctx.lei, cit, adct = false;
        if (m[2]) {
          // Lei dita antes ("CTN, art. 198"; "Código Civil, art. 99") ou depois ("art. 37 da Constituição").
          const antes = texto.slice(Math.max(0, ini - 40), ini).match(/([^(),;]{1,30}),\s*$/);
          const dono = (antes && leiNomeada(antes[1])) || leiNomeada(texto.slice(fim, fim + 110));
          if (dono === 'outra') continue;
          if (dono && dono !== 'propria') lei = dono;
          if (!LEIS[lei]) continue;
          cit = intervalo(oNum(m[2])).replace(/^[Aa]rtigos\s*|^[Aa]rts\.\s*/, 'Arts. ').replace(/^[Aa]rtigo\s*|^[Aa]rt\.\s*/, 'Art. ') + m[3] + (/^[Aa]rt(?:s|igos)\b/.test(m[2]) ? '' : recorte(m[1].replace(/\s+(?:do|da|dos|das)\s+$/, '')));
        } else {
          let base = artBase(ctx.art);
          if (!base || !LEIS[lei]) continue;
          // "o disposto no § 3º do artigo anterior": o artigo de antes, não o da linha.
          if (/^\s+d[oe]\s+(?:[Aa]rtigo|[Aa]rt\.)\s+anterior/.test(texto.slice(fim))) {
            const n = base.match(/^Art\.\s*(\d+)[º°]?$/);
            if (!n || +n[1] < 2) continue;
            base = `Art. ${n[1] - 1}${n[1] - 1 < 10 ? 'º' : ''}`;
          }
          cit = base + recorte(m[4].replace(/\s+(?:do|da|dos|das)\s+[Cc]aput$/, ''));
          adct = /^ADCT/.test(ctx.art);
        }
        out += esc(texto.slice(i, ini)) +
          `<a class="ref-lei" href="${LEIS[lei][1]}" data-lei="${lei}" data-cit="${esc(cit)}"${adct ? ' data-adct="1"' : ''} aria-haspopup="dialog">${esc(m[0])}</a>`;
        i = fim;
      }
      return out + esc(texto.slice(i));
    },
    leiDe,
    fechar,
  };

  let pop = null, dono = null;
  function fechar() {
    if (!pop) return;
    pop.remove(); pop = null;
    if (dono) dono.setAttribute('aria-expanded', 'false');
    dono = null;
  }

  async function abrir(b) {
    fechar();
    const [sigla, url] = LEIS[b.dataset.lei], cit = b.dataset.cit, adct = !!b.dataset.adct;
    dono = b; b.setAttribute('aria-expanded', 'true');
    pop = document.createElement('div');
    pop.className = 'ref-pop'; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', `${sigla}, ${cit}`);
    pop.innerHTML = `<div class="ref-head"><b>${esc(sigla)} · ${esc(cit)}</b>` +
      '<button type="button" class="ref-inteira">Lei inteira</button><button type="button" class="ref-x" aria-label="Fechar">×</button></div>' +
      '<div class="ref-body tl-body">Carregando…</div>';
    document.body.appendChild(pop);
    const r = b.getBoundingClientRect(), w = pop.offsetWidth;
    pop.style.left = Math.max(8, Math.min(scrollX + r.left, scrollX + document.documentElement.clientWidth - w - 8)) + 'px';
    pop.style.top = (scrollY + r.bottom + 6) + 'px';
    const p = pop;
    p.querySelector('.ref-x').onclick = () => { fechar(); b.focus(); };
    p.querySelector('.ref-inteira').onclick = () => { fechar(); window.abrirTextoLei(url, `${sigla} · ${cit}`, cit + (b.dataset.lei === 'cf' ? ' da CF' : ''), null, adct); };
    let html;
    try { html = await window.trechoLei(url, cit, adct); } catch (_) { html = undefined; }
    if (pop !== p) return;
    p.querySelector('.ref-body').innerHTML = html || `<p class="tl-aviso">${html === undefined ? 'Não foi possível carregar o texto da lei.' : 'Não achei esse artigo no texto da lei. Use "Lei inteira".'}</p>`;
    // Se passou da parte de baixo da tela, abre acima do botão.
    const alto = p.offsetHeight;
    if (r.bottom + 6 + alto > innerHeight && r.top - 6 - alto > 0) p.style.top = (scrollY + r.top - 6 - alto) + 'px';
    p.querySelector('.ref-x').focus({ preventScroll: true });
  }

  document.addEventListener('click', e => {
    const b = e.target.closest('.ref-lei');
    // Link de verdade (quebra de linha como texto e Ctrl+clique abre o arquivo da lei); o clique simples abre o balão.
    if (b && !(e.ctrlKey || e.metaKey || e.shiftKey)) {
      e.preventDefault(); e.stopPropagation();
      if (window.refLei.aoClicar) { fechar(); window.refLei.aoClicar(b); return; }
      if (b === dono) fechar(); else abrir(b);
      return;
    }
    if (pop && !pop.contains(e.target)) fechar();
  }, true);
  // O balão fica na posição da página: se a ficha rolar por dentro, ele se soltaria do link.
  document.addEventListener('scroll', e => { if (pop && e.target !== document && !pop.contains(e.target)) fechar(); }, true);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && pop) { e.stopImmediatePropagation(); const b = dono; fechar(); if (b && b.isConnected) b.focus(); }
  }, true);
})();
