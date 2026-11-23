// Cards montados a partir dos erros (baralho "Erros" dos flashcards). Quem monta é o Refazer erros
// (js/refazer.js): ao dizer por que errou ("confundi", "não sabia", "número"), o erro vira card na hora.
//
// O card é sobre o ERRO, não sobre a resposta: a frente mostra a afirmação em que o aluno errou o julgamento
// (o item de assertivas, a afirmativa de V/F ou a alternativa que ele marcou) e pergunta o que ele deixou de
// considerar. O verso é um mapa mental, com setas: o que você julgou → o que deixou de considerar → o que a lei diz,
// com números, prazos e palavras decisivas (não, apenas, independe…) em vermelho. A combinação certa ("I e II
// apenas") não aparece: ela não ensina nada.
//
// Os cards ficam em localStorage 'lom_fc_meus' ({ id: card }), no formato de dados/flashcards.json, com lei 'meus' e
// os ramos do mapa em card.mapa. O progresso fica em lom_fc, como o dos outros cards. Um card por questão: errar de
// novo a mesma questão atualiza o card (mantendo o progresso). Cards de versão antiga (sem card.v === V) são
// refeitos pelo Refazer erros na próxima abertura.
window.MeusCards = (function () {
  const KEY = 'lom_fc_meus', V = 5;   // 5: associação de colunas (cada lacuna com o que foi ligado e a legenda)
  const SECAO = { confundi: 'Confundi com outra regra', nao_sabia: 'Regras que eu não sabia', numero: 'Números e prazos' };
  const LEI = { id: 'meus', sigla: 'Erros', curto: 'Cards dos seus erros', nome: 'Cards montados a partir dos seus erros (Refazer erros)',
    secoes: Object.values(SECAO).concat(['Contraste (A × B)', 'Julgue de novo']) };   // as duas últimas: cards antigos
  const CAUSAS = Object.keys(SECAO);
  const ler = () => { try { const v = JSON.parse(localStorage.getItem(KEY) || '{}'); return v && typeof v === 'object' && !Array.isArray(v) ? v : {}; } catch (_) { return {}; } };
  const gravar = v => { try { localStorage.setItem(KEY, JSON.stringify(v)); } catch (_) { /* sem armazenamento */ } };
  const limpa = t => String(t || '').replace(/\s+/g, ' ').trim();
  const corta = (t, n) => { t = limpa(t); return t.length > n ? t.slice(0, n - 1).trimEnd() + '…' : t; };
  const maiuscula = t => t ? t[0].toUpperCase() + t.slice(1) : t;

  // ---------- leitura da questão ----------
  // Alternativa que é combinação de assertivas ("II e III apenas.", "Estão corretas apenas as assertivas I e II.").
  const ITEM = '(?:assertivas?|afirmativas?|itens?|proposi[çc][õo]es|alternativas?)';
  const COMBO = new RegExp(`^\\s*(?:(?:Est[áaã]o?|S[ãa]o) corretas?|(?:Todas|Nenhuma)(?: as| das)? ${ITEM}|(?:Apenas|Somente)(?: as?| os?)?(?: ${ITEM})? (?:I|II|III|IV|V)\\b|(?:I|II|III|IV|V)(?:\\s*(?:,|e)\\s*(?:I|II|III|IV|V))*\\s*,?\\s*(?:apenas|somente)?\\s*\\.?\\s*$)`, 'i');
  // itens que a alternativa diz estarem corretos: null = todos; "Todas … incorretas" e "Nenhuma …" = nenhum
  const romanos = t => /todas/i.test(t) && !/incorret|errad|falsa/i.test(t) ? null : /nenhuma|todas/i.test(t) ? [] : (t.match(/\b(?:IV|V?I{1,3}|V)\b/g) || []);
  // Texto do item "II" no enunciado (um item por linha ou por parágrafo), sem o "II." do começo.
  const textoItem = (stem, id) => {
    const p = String(stem).split(/\n+/).find(x => new RegExp(`^${id}\\s*[.)\\-–]\\s`).test(x.trim()));
    return p ? limpa(p).replace(new RegExp(`^${id}\\s*[.)\\-–]\\s*`), '') : '';
  };
  // Caderno: a análise item a item vem no comentário da alternativa ("III — incorreta. …").
  const notasDoComentario = t => {
    const out = {};
    for (const m of String(t || '').matchAll(/(?:^|\n)\s*(I|II|III|IV|V)\s*[—–-]\s*(correta|incorreta|certa|errada)\.?\s*([\s\S]*?)(?=\n\s*\n|$)/gi))
      out[m[1]] = { ok: /^(correta|certa)$/i.test(m[2]), note: limpa(m[3]) };
    return out;
  };
  // V/F: alternativas são sequências ("V, F, F, V.") e as afirmativas, linhas que começam com "( )".
  const VF = /^\s*[VF](?:\s*[,;–—−-]\s*[VF]\b|\s+[VF]\b)+/;   // separador: vírgula, hífen, travessão ou sinal de menos
  const seqVF = t => t.match(/[VF]/g) || [];
  // Associação de colunas: a alternativa é uma sequência de números ("3 − 1 − 2.", "IV - III - II - I.").
  const ASSOC = /^\s*(?:\d+|[IVX]+)(?:\s*[–—−-]\s*(?:\d+|[IVX]+))+\s*\.?\s*$/;
  const seqAssoc = t => t.match(/\d+|[IVX]+/g) || [];
  const afirmativas = stem => String(stem).split(/\n+/).map(x => x.trim()).filter(x => /^\([\s_]*\)/.test(x)).map(x => limpa(x.replace(/^\([\s_]*\)\s*/, '')));
  // "Certa: …" / "Errada (é a exceção): …" no começo do comentário: o mapa já diz qual é qual.
  const semRotulo = t => limpa(t).replace(/^(?:cert[ao]|corret[ao]|errad[ao]|incorret[ao])(?: como resposta)?\s*(?:\(([^)]*)\))?\s*[:.—–-]\s*/i, (_, nota) => nota ? maiuscula(nota) + ': ' : '');
  // "Como resolver, afirmativa por afirmativa: 2ª — V. Moratória suspende…" / "III — F. …" → { '2ª': nota, 'III': nota }.
  const notasExplica = t => {
    const out = {};
    // "2ª — F. …" numa linha, ou "1ª V; 2ª F (“trimestre” no lugar de “bimestre”); …" numa linha só.
    for (const m of String(t || '').matchAll(/(?:^|\n|;|:)\s*(\d+ª|I|II|III|IV|V)\s*[—–-]?\s*(?:[VF]|correta|incorreta|certa|errada|verdadeira|falsa)\b\.?\s*([^;\n]*)/gi))
      if (!out[m[1]]) out[m[1]] = limpa(m[2]).replace(/^\((.*)\)\.?$/, '$1');
    return out;
  };
  // Do enunciado, o comando final ("De acordo com a LC nº 116/2003, o ISS não incide sobre, exceto:").
  const comando = stem => { const p = String(stem).split(/\n\s*\n/).filter(Boolean); return corta(p[p.length - 1] || '', 320); };
  // Enunciado resumido: a primeira frase do texto de apoio e o comando, sem os itens (eles já são as afirmações do card).
  // "De acordo com o CTN, o fato gerador da obrigação principal é… | A sequência está correta em:".
  function enunciadoResumido(stem) {
    const ps = String(stem || '').split(/\n\s*\n/).map(p => p.split('\n').filter(l => !/^\s*(?:\([\s_]*\)|(?:I|II|III|IV|V|VI|\d+)\s*[.)\-–−]\s|(?:Primeira|Segunda)\s+coluna\b|Coluna\s+(?:I|II|A|B|1|2)\b)/i.test(l)).join(' '))
      .map(limpa).filter(Boolean);
    const frases = ps.join(' ').split(/(?<=[.!?:])\s+(?=[A-ZÁÉÍÓÚÂÊÔÃÕÇ“(])/).map(limpa).filter(Boolean);
    if (!frases.length) return '';
    const ini = corta(frases[0], 220), cmd = frases.length > 1 ? corta(frases[frases.length - 1], 180) : '';
    return cmd ? `${ini}${frases.length > 2 ? ' […]' : ''} ${cmd}` : ini;
  }
  // "O que a questão cobra: … A regra: … Como resolver: …" → só a regra.
  const soRegra = t => { const m = String(t || '').match(/A regra:\s*([\s\S]*?)(?:\n\s*\n|Como resolver:|Para fixar:|$)/); return limpa(m ? m[1] : ''); };

  // ---------- o trecho da lei de cada ramo ----------
  // Números e palavras de cada texto, para achar a linha da lei que fala do mesmo ponto.
  const numeros = t => (limpa(t).match(/(?<!arts?\.\s?|§\s?|n[º°]\s?)\b\d+(?:[.,]\d+)?/g) || []).map(n => n.replace(',', '.'));
  const palavras = t => new Set((limpa(t).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').match(/[a-z]{5,}/g) || []));
  // Pontos de uma linha contra o texto do erro: números em comum (3) e palavras de 5+ letras em comum (1).
  const pontuador = texto => {
    const nums = new Set(numeros(texto)), pals = palavras(texto);
    return l => { let p = 0; for (const n of new Set(numeros(l))) if (nums.has(n)) p += 3; for (const w of palavras(l)) if (pals.has(w)) p += 1; return p; };
  };
  // Frases do comentário da questão que explicam a regra (sem o cabeçalho "Questão real…", "O que a questão cobra",
  // a sequência e as frases sobre o próprio aluno: "Na questão 40 do Simulado 3, você…").
  function frasesDoComentario(o) {
    const t = [o.explica, o.regra].filter(Boolean).join('\n');
    const out = [];
    for (let l of t.split('\n')) {
      l = limpa(l.replace(/^[•·-]\s*/, '').replace(/^(?:A regra|Como resolver(?:, \w+ por \w+)?|Para fixar|Atenção|Resumo)\s*:\s*/i, ''));
      for (const f of l.split(/(?<=[.!?”])\s+(?=[A-ZÁÉÍÓÚÂÊÔÃÕÇ“•])/))
        if (f.length > 15 && !/^(Questão real|O que a questão cobra|Sequência|Gabarito|Enunciado resumido)/i.test(f) && !/\bvocê\b|Simulado \d|gabarito|Gran Questões|\bQ\d{6,}/i.test(f)
          // frases sobre as letras ("B descreve…", "D e E não existem", "A alternativa C…") não ensinam a regra
          // ("A" sozinho é artigo: "A capacidade tributária…" fica; só "A e C…", "A, B e D…")
          && !/^[B-E]\s+[a-zà-ú]|^[A-E](?:\s*,\s*[A-E])*\s+e\s+[A-E]\b|^(?:As?\s+)?alternativas?\b|^Letra\s+[A-E]\b/.test(f)) out.push(f.trim());
    }
    return [...new Set(out)];
  }
  // A frase do comentário que mais fala do erro (2+ pontos), ou ''.
  function regraDoErro(frases, texto) {
    const ponto = pontuador(texto);
    let mi = -1, mp = 1;
    frases.forEach((f, i) => { const p = ponto(f); if (p > mp) { mp = p; mi = i; } });
    if (mi < 0) return '';
    // "Isso é poder hierárquico…" sozinho fica sem sujeito: vem com a frase seguinte.
    return /^(Isso|Isto|Esse|Essa|Assim)\b/.test(frases[mi]) && frases[mi + 1] ? `${frases[mi]} ${frases[mi + 1]}` : frases[mi];
  }
  // A linha da lei (dos artigos ligados à questão) que mais fala do erro: números e palavras em comum e o inciso que a
  // nota cita ("art. 79, II" → a linha "II -" do art. 79: 6); vale a partir de 2 pontos. Vem com o caput e o parágrafo
  // ou inciso que a introduzem, como na lei. null se nada bate.
  const INC = /^([IVXLC]+)\s*[-–—]/, ALI = /^[a-z]\)/, PAR = /^(§|Par[áa]grafo)/;
  function leiDoErro(blocos, texto) {
    const ponto = pontuador(texto);
    const citados = [...limpa(texto).matchAll(/art\.\s*(\d+)[º°]?,\s*([IVXLC]+)\b/gi)].map(m => [m[1], m[2]]);
    let melhor = null;
    for (const b of blocos) {
      const ls = String(b.txt).split('\n').map(limpa).filter(Boolean), art = (b.rot.match(/art\.\s*(\d+)/i) || [])[1];
      ls.forEach((l, i) => {
        const inc = (l.match(INC) || [])[1];
        const p = ponto(l) + (inc && citados.some(([a, x]) => a === art && x === inc) ? 6 : 0);
        if (p > (melhor ? melhor.p : 1)) melhor = { p, rot: b.rot, ls, i };
      });
    }
    if (!melhor) return null;
    const { ls, i } = melhor, linhas = [];
    if (i > 0) linhas.push(ls[0]);                                                     // caput do artigo
    const antes = ls.slice(1, i);
    if (ALI.test(ls[i])) { const inc = antes.slice().reverse().find(x => INC.test(x)); if (inc) antes.splice(antes.indexOf(inc)); if (inc) linhas.push(inc); }
    if (INC.test(ls[i]) || ALI.test(ls[i])) { const par = antes.slice().reverse().find(x => PAR.test(x)); if (par) linhas.splice(1, 0, par); }
    linhas.push(ls[i]);
    // Caput que abre uma lista ("…independe:"): sozinho não diz a regra; vem o inciso (ou alínea) que mais fala do ponto.
    if (/:\s*$/.test(ls[i]) && i + 1 < ls.length) {
      let fi = -1, fp = 0;
      fp = 1;   // o inciso precisa falar do ponto (2+ pontos); senão fica só o caput
      for (let k = i + 1; k < ls.length && !(k > i + 1 && /^Art\./.test(ls[k])); k++) { const q = ponto(ls[k]); if (q > fp) { fp = q; fi = k; } }
      if (fi > 0) linhas.push(ls[fi]);
    }
    return { rot: melhor.rot, tipo: 'lei', linhas: [...new Set(linhas)] };
  }
  // Frase do comentário que fala da afirmativa ou do item ("a primeira afirmativa tira o “não” do art. 115…").
  const ORDINAIS = ['', 'primeira', 'segunda', 'terceira', 'quarta', 'quinta', 'sexta'];
  // Do comentário que fala de todos os itens, só o pedaço deste: de "2ª" até o próximo "3ª"; ou, numa lista com ";"
  // ("Sem conjunção (assindética); “mas” (adversativa); …"), o pedaço que mais fala do item (alvo: texto + resposta).
  function focar(t, n, alvo) {
    t = limpa(t);
    const ord = new RegExp(`(?<![\\wÀ-ú])(?:${ORDINAIS[n]}|${n}ª)(?![\\wÀ-ú])`, 'i'), m = n && t.match(ord);
    if (m && /\d+ª|primeira|segunda|terceira|quarta|quinta/i.test(t.slice(0, m.index) + t.slice(m.index + m[0].length))) {
      const resto = t.slice(m.index), fim = resto.slice(m[0].length).search(/(?<![\wÀ-ú])(?:\d+ª|primeira|segunda|terceira|quarta|quinta|sexta)(?![\wÀ-ú])/i);
      return limpa(fim < 0 ? resto : resto.slice(0, m[0].length + fim)).replace(/[.;,]\s*$/, '.')
        .replace(/^\d+ª\s*[—–-]?\s*[VF]\b\.?\s*/, '');   // "3ª V. Art. 165…" → "Art. 165…"
    }
    const partes = t.split(/;\s*/);
    if (partes.length > 1 && alvo) {
      const ponto = pontuador(alvo);
      let mi = -1, mp = 0;
      partes.forEach((x, i) => { const p = ponto(x); if (p > mp) { mp = p; mi = i; } });
      if (mi >= 0) return maiuscula(partes[mi].replace(/\.$/, '')) + '.';
    }
    return t;
  }
  // ("ª" não é letra para o \b do JavaScript: a fronteira de "2ª" é feita à mão)
  const fraseDoItem = (frases, n, id) => frases.find(f => new RegExp(n ? `(?<![\\wÀ-ú])(${ORDINAIS[n]}|${n}ª)(?![\\wÀ-ú])`
    : `\\b(?:item|assertiva|afirmativa)\\s+${id}\\b|\\b${id}\\s+(?:errad|incorret|corret|falsa|verdadeir)`, 'i').test(f)) || '';

  // ---------- montagem ----------
  // o: a questão como o Refazer a monta (info(key)); sel: índice da alternativa marcada; regra: a regra do aluno.
  function montar({ key, causa, o, sel, regra }) {
    if (!o || !CAUSAS.includes(causa) || !(sel >= 0) || sel === o.certa) return null;
    const marcou = limpa(o.opts[sel]), certa = limpa(o.opts[o.certa]);
    const tema = [o.norma, o.assunto].filter(Boolean).join(' — ');
    const blocos = o.blocos || [], porItem = notasExplica(o.explica);
    const frases = frasesDoComentario(o);
    let ramos = [], legenda = '';

    if (VF.test(marcou) && VF.test(certa)) {
      // V/F: cada afirmativa em que trocou V por F (ou o contrário).
      const disse = seqVF(marcou), eh = seqVF(certa), txt = afirmativas(o.stem);
      const geral = VF.test(semRotulo(o.porOpcao[o.certa])) ? '' : semRotulo(o.porOpcao[o.certa]);   // "V, F, V, F." não explica
      ramos = eh.map((v, i) => ({ v, i })).filter(x => disse[x.i] !== x.v && txt[x.i]).map(x => ({
        rotulo: `${x.i + 1}ª afirmativa · você julgou ${x.v === 'V' ? 'FALSA' : 'VERDADEIRA'}`, texto: txt[x.i],
        // sem nota própria, o comentário geral ("só a primeira é falsa…") vale só para a afirmativa falsa
        veredito: `mas ela é ${x.v === 'V' ? 'VERDADEIRA' : 'FALSA'}`, erro: focar(porItem[`${x.i + 1}ª`] || fraseDoItem(frases, x.i + 1) || (x.v === 'F' ? geral : ''), x.i + 1, txt[x.i]),
      }));
    } else if (ASSOC.test(marcou) && ASSOC.test(certa)) {
      // Associação de colunas: a alternativa ("3 − 1 − 2") é o número de cada lacuna "(__)", na ordem. Cada lacuna
      // ligada errado vira um ramo: o texto dela, o que o aluno ligou e o certo, com o nome de cada número.
      const linhas = String(o.stem).split(/\n+/).map(x => x.trim());
      const lac = linhas.filter(l => /^\([\s_]*\)/.test(l)).map(l => limpa(l.replace(/^\([\s_]*\)\s*/, '')));
      const nome = {};
      for (const l of linhas) { const m = l.match(/^(\d+|[IVX]+)\s*[.)–—−-]\s+(.+)/); if (m && !(m[1] in nome)) nome[m[1]] = limpa(m[2]); }
      const ligou = seqAssoc(marcou), certo = seqAssoc(certa), com = n => nome[n] ? `${n} (${nome[n]})` : n;
      if (lac.length === certo.length) {
        legenda = Object.entries(nome).map(([n, t]) => `${n} = ${corta(t, 60)}`).join(' · ');
        const pqMarcada = semRotulo(o.porOpcao[sel]);
        ramos = certo.map((c, i) => ({ c, i, m: ligou[i] })).filter(x => x.m !== x.c).map(x => ({
          rotulo: `${x.i + 1}ª lacuna · você ligou a ${com(x.m)}`, texto: lac[x.i], veredito: `mas é a ${com(x.c)}`,
          erro: focar(porItem[`${x.i + 1}ª`] || fraseDoItem(frases, x.i + 1) || regraDoErro([pqMarcada], lac[x.i]) || frases.join(' '), x.i + 1, `${lac[x.i]} ${nome[x.c] || ''}`),
        }));
      }
    } else if (COMBO.test(marcou) && COMBO.test(certa)) {
      // Assertivas: cada item em que o julgamento do aluno (pela combinação marcada) não bate com o certo.
      const disse = romanos(marcou), certos = romanos(certa), cad = notasDoComentario(o.porOpcao[o.certa]);
      const nota = id => semRotulo((o.itens.find(x => x.id === id) || {}).note) || (cad[id] || {}).note || porItem[id] || fraseDoItem(frases, 0, id) || '';
      const ids = ['I', 'II', 'III', 'IV', 'V'].filter(id => textoItem(o.stem, id));
      const julgou = id => disse === null || disse.includes(id), eh = id => certos === null || certos.includes(id);
      ramos = ids.filter(id => julgou(id) !== eh(id)).map(id => ({
        rotulo: `Item ${id} · você julgou ${julgou(id) ? 'CERTO' : 'ERRADO'}`, texto: textoItem(o.stem, id),
        veredito: `mas ele está ${eh(id) ? 'CERTO' : 'ERRADO'}`, erro: nota(id),
      }));
    }
    if (!ramos.length) {
      // Comando que pede a exceção ("exceto", "incorreta", "NÃO"): a alternativa marcada costuma ser verdadeira; o que
      // faltou considerar foi o próprio comando.
      const excecao = /\b(exceto|incorret[ao]s?|errad[ao]s?|falsa)\b|\bN[ÃA]O\b/.test(comando(o.stem));
      const erro = semRotulo(o.porOpcao[sel]) || soRegra(o.regra) || semRotulo(o.porOpcao[o.certa]);
      ramos = [{
        rotulo: 'Você marcou', texto: marcou, veredito: excecao ? 'mas o comando pedia a exceção' : 'mas está errada',
        erro: excecao ? `${maiuscula(erro)} O comando pede a EXCEÇÃO: o que está na lista da lei não pode ser a resposta.` : erro,
      }];
    }
    // Sem nota do item: a regra do comentário da questão.
    for (const r of ramos) if (!r.erro) r.erro = regraDoErro(frases, r.texto) || soRegra(o.regra) || frases[0] || semRotulo(o.porOpcao[sel]) || '';
    // Nota que é só a citação ("Art. 82, I, do CTN."): junta a frase do comentário que explica o ponto; sem ela, diz
    // o que a citação significa (a afirmação concorda ou não com aquele artigo, que o mapa mostra logo abaixo).
    for (const r of ramos) if (limpa(r.erro).length < 40 && (!r.erro || /^(?:Arts?\.|§|Lei|LC)\s/i.test(limpa(r.erro)))) {
      const mais = regraDoErro(frases.filter(f => !r.erro.includes(f)), r.texto + ' ' + r.erro);
      const cit = limpa(r.erro).replace(/\.$/, '');
      if (mais) r.erro = limpa(`${r.erro} ${mais}`);
      else if (/^(?:Arts?\.|§|Lei|LC)\s/.test(cit)) r.erro = /CERT|VERDADEIR/.test(r.veredito)
        ? `Está de acordo com a lei (${cit}): o texto abaixo diz o mesmo que a afirmação.`
        : `Contraria a lei (${cit}): compare a afirmação com o texto abaixo.`;
    }
    // Cada ramo: a regra do comentário que fala do erro (se não for a própria nota) e a linha da lei.
    // A lei sai primeiro do texto da própria afirmação (o comentário pode falar de outro item); só sem nada, do conjunto.
    for (const r of ramos) {
      const regra = regraDoErro(frases, r.texto + ' ' + r.erro);
      // sem repetir: a regra que já é o erro, ou da qual o erro é um pedaço ("2ª (“em que áreas”) → funcional (2).")
      r.regra = regra && !limpa(r.erro).includes(regra.slice(0, 40)) && !regra.includes(limpa(r.erro).replace(/\.$/, '').slice(0, 30)) ? regra : '';
      r.lei = leiDoErro(blocos, r.texto) || leiDoErro(blocos, r.texto + ' ' + r.erro + ' ' + r.regra);
    }
    for (const r of ramos) {
      const d = r.lei && /CERT|VERDADEIR/.test(r.veredito) ? comparar(r.texto, r.lei.linhas) : null;
      if (d && d.fiel) { r.erro = `Está de acordo com a lei (${r.lei.rot}): o texto abaixo diz o mesmo que a afirmação.`; r.regra = ''; }
    }
    // Texto da lei completo, embaixo do mapa: os artigos que o mapa usou; se nenhum bateu, os da questão.
    const usados = [...new Set(ramos.map(r => r.lei && r.lei.rot).filter(Boolean))];
    const bs = (usados.length ? blocos.filter(b => usados.includes(b.rot)) : blocos).slice(0, 2);
    const card = {
      id: 'meu-' + key, v: V, lei: 'meus', tipo: 'pergunta', causa, secao: SECAO[causa], num: causa === 'numero',
      criado: new Date().toISOString().slice(0, 10), origem: o.origem, href: o.href,
      art: corta(bs.map(b => b.rot).join('; ') || tema, 90),
      trecho: bs.flatMap(b => String(b.txt).split('\n').map(limpa).filter(Boolean)),   // [] = sem quadro "Texto da lei"
      fonte: bs.length ? `da lei (${corta(bs.map(b => b.rot).join('; '), 80)})` : 'da questão',
      frente: ramos.length === 1 ? 'O que você deixou de considerar nesta afirmação?' : 'O que você deixou de considerar nestas afirmações?',
      tema, mapa: ramos, minha: limpa(regra), legenda,   // legenda: "1 = Institucional · 2 = Funcional…" (associação)
      // Lei das citações soltas no mapa ("art. 22" no comentário de uma questão da LRF), para virarem links (js/ref-lei.js).
      leiRef: window.refLei ? refLei.leiDe(o.norma) || refLei.leiDe((blocos[0] || {}).rot || '') : null,
      // Texto corrido (busca da lista): o que o aluno deixou de considerar.
      verso: ramos.map(r => r.erro).join(' '),
    };
    card.enunciado = enunciadoResumido(o.stem);   // o contexto da questão, curto (a frente mostra antes da afirmação)
    if (key[0] === 's') card.q = [[key.slice(2), o.origem]];
    return card;
  }

  // Guarda o card (ou atualiza o da mesma questão). Devolve o card guardado, ou null.
  function guardar(card) {
    if (!card) return null;
    const todos = ler();
    const antes = todos[card.id];
    todos[card.id] = Object.assign({}, card, antes && { criado: antes.criado });   // atualizar não muda a data de criação
    gravar(todos);
    return todos[card.id];
  }

  // ---------- desenho (flashcards e fim de sessão do Refazer) ----------
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  // Em vermelho: números com unidade (90%, 30 dias, dois terços…) e palavras que decidem o certo e o errado.
  const DECISIVAS = 'não|nunca|sempre|apenas|somente|exclusivamente|independe(?:nte)?|depende|vedad[ao]s?|proibid[ao]s?|obrigatóri[ao]s?|facultativ[ao]s?|exceto|exceção|salvo|inclusive|privativ[ao]s?|qualquer|nenhum[a]?|bimestr\\w*|quadrimestr\\w*|trimestr\\w*|semestr\\w*|anual|mensal|metade|dobro|terço|terços|maioria (?:absoluta|simples)';
  const UNID = '(?:\\s*%|\\s*\\([^)]{1,30}\\)\\s*%?|\\s+(?:dias?(?: úteis)?|anos?|meses|mês|horas?|vezes|BTN|salários?))?';
  // (aplicado ao texto já escapado: "#" e "&" de fora, para não pintar o 39 de "&#39;")
  const VERMELHO = new RegExp(`(?<![\\wÀ-ú§#&])(?<!arts?\\.\\s?)(?<!§\\s?)(?<!n[º°]\\s?)(\\d+(?:[.,]\\d+)?[º°ª]?${UNID})|\\b(${DECISIVAS})\\b`, 'gi');
  // Texto do mapa: citações de artigo viram links que abrem a lei (js/ref-lei.js) e o vermelho só pinta o texto, fora das tags.
  // frase: trecho curto que também fica vermelho onde aparecer (a alternativa marcada, dentro da lista da lei).
  // sem: só os links, sem vermelho (caput e parágrafo que só dão o contexto da linha da lei).
  const vermelho = (t, ctx, frase, sem) => {
    const html = window.refLei && ctx && ctx.lei ? refLei.html(t, ctx) : esc(t);
    if (sem) return html;
    const reFrase = frase ? new RegExp(esc(frase).replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s+'), 'gi') : null;
    // Pinta só o texto, fora das tags: primeiro números e palavras decisivas, depois a frase marcada.
    const pinta = (h, re) => h.split(/(<[^>]+>)/).map((x, i) => i % 2 ? x : x.replace(re, m => `<b class="mp-red">${m}</b>`)).join('');
    const com = pinta(html, VERMELHO);
    return reFrase ? pinta(com, reFrase) : com;
  };
  // Alternativa marcada curta ("Crédito presumido.") → "Crédito presumido", para achar na linha da lei.
  const fraseDe = r => r.rotulo === 'Você marcou' && r.texto.length <= 60 ? r.texto.replace(/[.;:]\s*$/, '').trim() : '';
  const ctxLei = r => { if (!window.refLei) return null; const [sig, art] = r.lei.rot.split(/,\s*(?=art)/i); const lei = refLei.leiDe(sig); return lei ? { lei, art: art ? maiuscula(art) : '', inicio: true } : null; };

  // ---------- a troca exata: afirmação × lei ----------
  // Quando a afirmação copia a lei (60%+ das palavras na mesma ordem), compara palavra por palavra (maior subsequência
  // comum) e devolve onde as duas diferem: na afirmação, as palavras que não estão na lei; na lei, as que sumiram ou
  // foram trocadas; e as trocas em palavras ("tirou “não”", "trocou “95%” por “90%”"). null se não é cópia.
  const chave = w => w.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9%]/g, '');
  const miuda = g => g.every(w => chave(w).length <= 2);   // "o", "a", "de": diferença de digitação, não de regra
  // Diferença que não muda a regra: a mesma palavra flexionada ("dependerá" × "depende", "auxílio" × "auxílios"), ou
  // palavras tiradas/acrescentadas sem número, sem palavra decisiva (não, apenas, salvo…) e sem palavra de conteúdo.
  const DECISIVA = () => new RegExp(`^(?:${DECISIVAS})$`, 'i');
  function irrelevante(gA, gB) {
    if (gA.length === 1 && gB.length === 1) {
      const x = chave(gA[0]), y = chave(gB[0]), n = Math.min(x.length, y.length, 6);
      return n >= 4 && x.slice(0, n) === y.slice(0, n);
    }
    const so = gA.length ? gA : gB;
    if (gA.length && gB.length) return false;
    return !so.some(w => /\d/.test(w) || DECISIVA().test(chave(w)) || DECISIVA().test(w.replace(/[^\wÀ-ú]/g, '')) || chave(w).length >= 6);
  }
  function comparar(afirmacao, linhasLei) {
    const A = limpa(afirmacao).split(' '), B = [], linhaDe = [];
    const rotulo = [];   // "Art. 126.", "II -", "a)", "§ 1º" no começo da linha da lei: não entram na comparação
    linhasLei.forEach((l, k) => {
      const ini = (limpa(l).match(/^(?:Art\.\s*\d+[º°o]?(?:-[A-Z])?\.?|§\s*\d+[º°o]?|Parágrafo único\.?|[IVXLC]+\s*[-–—]|[a-z]\))\s*/i) || [''])[0];
      const nIni = ini ? limpa(ini).split(' ').length : 0;
      limpa(l).split(' ').forEach((w, t) => { B.push(w); linhaDe.push(k); rotulo.push(t < nIni); });
    });
    const a = A.map(chave), b = B.map((w, y) => rotulo[y] ? '' : chave(w)), n = a.length, m = b.length;
    if (!n || !m || n * m > 60000) return null;
    const dp = Array.from({ length: n + 1 }, () => new Uint16Array(m + 1));
    for (let x = n - 1; x >= 0; x--) for (let y = m - 1; y >= 0; y--)
      dp[x][y] = a[x] && a[x] === b[y] ? dp[x + 1][y + 1] + 1 : Math.max(dp[x + 1][y], dp[x][y + 1]);
    const pares = [];
    for (let x = 0, y = 0; x < n && y < m;) {
      if (a[x] && a[x] === b[y]) { pares.push([x, y]); x++; y++; } else if (dp[x + 1][y] >= dp[x][y + 1]) x++; else y++;
    }
    if (pares.length < 4 || pares.length / a.filter(Boolean).length < 0.6) return null;
    // Trocas: o que está entre dois pares casados (na afirmação e na lei); no fim, o que a afirmação acrescentou.
    // Só conta como troca a diferença curta (até 4 palavras de cada lado): diferença maior é paráfrase, sem destaque.
    // fiel: nenhuma diferença (fora as miúdas) — a afirmação reproduz a lei.
    const okA = A.map(() => true), okB = B.map(() => true), trocas = [];
    let fiel = true;
    const faixa = (de, ate, k) => { const r = []; for (let t = de; t < ate; t++) if (k[t]) r.push(t); return r; };
    for (let k = 1; k <= pares.length; k++) {
      const [px, py] = pares[k - 1], [x, y] = pares[k] || [n, py + 1];
      const ia = faixa(px + 1, x, a), ib = pares[k] ? faixa(py + 1, y, b) : [];
      if (!ia.length && !ib.length) continue;
      const gA = ia.map(t => A[t]), gB = ib.map(t => B[t]);
      if (miuda(gA.concat(gB)) || irrelevante(gA, gB)) continue;
      fiel = false;
      if (ia.length > 4 || ib.length > 4) continue;
      ia.forEach(t => { okA[t] = false; }); ib.forEach(t => { okB[t] = false; });
      // na lei: a palavra antes, o trecho trocado e a palavra depois ("ato que não configure")
      const ctxLei = [B[py - 1], B[py], ...gB, pares[k] ? B[y] : ''].filter(w => w && !/^[IVXLC]+$|^[-–—]$/.test(w)).join(' ');
      trocas.push({ lei: gB.join(' '), afirm: gA.join(' '), ctxLei });
    }
    return { A, okA, B, okB, linhaDe, trocas, fiel };
  }
  const aspas = t => `“${t.replace(/[.,;:]$/, '')}”`;
  const frasesTroca = d => d.trocas.slice(0, 3).map(t =>
    (!t.afirm ? `tirou ${aspas(t.lei)}` : !t.lei ? `acrescentou ${aspas(t.afirm)}` : `trocou ${aspas(t.lei)} por ${aspas(t.afirm)}`) +
    (t.lei && t.ctxLei ? ` (na lei: ${aspas(t.ctxLei)})` : ''));
  const marcados = (toks, ok, so) => toks.map((w, k) => so && !so(k) ? null : ok[k] ? esc(w) : `<b class="mp-red mp-troca">${esc(w)}</b>`).filter(x => x !== null).join(' ');

  // Frente: o enunciado resumido e a(s) afirmação(ões) em que o aluno errou, sem dizer qual era o certo.
  const legendaHTML = c => c.legenda ? `<p class="mp-enun"><b>Os números:</b> ${esc(c.legenda)}</p>` : '';
  function frenteHTML(c) {
    return (c.enunciado ? `<p class="mp-enun"><b>Enunciado:</b> ${esc(c.enunciado)}</p>` : '') + legendaHTML(c) +
      `<div class="mp-frente">${c.mapa.map(r => `<div class="mp-no mp-voce"><span class="mp-tag">${esc(r.rotulo)}</span>${esc(r.texto)}</div>`).join('')}</div>`;
  }
  // Verso: mapa mental. No alto, o tema; de cada afirmação sai um ramo com setas: o que você julgou → o que deixou de
  // considerar (com a troca exata, quando a afirmação copia a lei) → a regra do comentário → o que a lei diz.
  function mapaHTML(c) {
    return (c.enunciado ? `<p class="mp-enun"><b>Enunciado:</b> ${esc(c.enunciado)}</p>` : '') + legendaHTML(c) +
      `<div class="mapa"><div class="mp-centro">${esc(c.tema || 'O seu erro')}</div><div class="mp-ramos">` + c.mapa.map(r => {
      const d = r.lei ? comparar(r.texto, r.lei.linhas) : null, troca = d && d.trocas.length ? d : null;
      return `<div class="mp-ramo">` +
        `<div class="mp-no mp-voce"><span class="mp-tag">${esc(r.rotulo)}</span>${troca ? marcados(d.A, d.okA) : vermelho(r.texto)}</div>` +
        `<div class="mp-seta"><span>${esc(r.veredito)}</span></div>` +
        `<div class="mp-no mp-erro"><span class="mp-tag">O que você deixou de considerar</span>` +
          (troca ? `<p class="mp-troca-txt">A afirmação ${frasesTroca(d).join('; ')}.</p>`
            : d && d.fiel && /CERT|VERDADEIR/.test(r.veredito) ? `<p class="mp-troca-txt">A afirmação copia a lei sem nenhuma troca: por isso ela está certa.</p>` : '') +
          `<p>${vermelho(r.erro, { lei: c.leiRef })}</p></div>` +
        (r.regra ? `<div class="mp-seta"><span>a regra</span></div>` +
          `<div class="mp-no mp-lei"><span class="mp-tag">Comentário da questão</span><p>${vermelho(r.regra, { lei: c.leiRef })}</p></div>` : '') +
        (r.lei ? `<div class="mp-seta"><span>o que a lei diz</span></div>` +
          `<div class="mp-no mp-lei"><span class="mp-tag">${esc(r.lei.rot)}</span>` +
          (troca ? r.lei.linhas.map((_, k) => `<p>${marcados(d.B, d.okB, y => d.linhaDe[y] === k)}</p>`).join('')
            : r.lei.linhas.map((l, i, ls) => `<p>${vermelho(l, ctxLei(r), fraseDe(r), i < ls.length - 1)}</p>`).join('')) + `</div>` : '') +
      `</div>`;
    }).join('') + `</div>` +
      (c.minha ? `<div class="mp-seta mp-seta-fim"><span>com as suas palavras</span></div><div class="mp-no mp-sua">${esc(c.minha)}</div>` : '') +
      `</div>`;
  }

  // ---------- lei citada que a questão não traz ----------
  // Quando nenhum ramo achou a linha da lei (a questão não tem trecho ligado), procura os artigos citados no tema, no
  // enunciado, no comentário e no erro ("O art. 78 do CTN…", "(CTN, art. 78)") com js/ref-lei.js e busca o texto deles
  // em leis/*.md com trechoLei() (js/texto-lei.js). Assíncrona: o Refazer guarda o card de novo quando termina.
  const SIGLA = { lrf: 'LRF', lai: 'LAI', ct: 'Lei 8.137', cf: 'CF', ctn: 'CTN', l4320: 'Lei 4.320', lgpd: 'LGPD',
    l14133: 'Lei 14.133', lc116: 'LC 116', l9784: 'Lei 9.784', l14063: 'Lei 14.063' };
  async function completarLei(card, o) {
    if (!card || !card.mapa || card.mapa.every(r => r.lei) || !window.refLei || !window.trechoLei || !window.DOMParser) return card;
    const fonte = [card.tema, o.stem, o.explica, ...card.mapa.map(r => r.erro + ' ' + (r.regra || ''))].filter(Boolean).join('\n');
    const html = d => new DOMParser().parseFromString(d, 'text/html');
    const cits = [];
    for (const a of html(refLei.html(fonte, { lei: card.leiRef })).querySelectorAll('a.ref-lei')) {
      const k = a.dataset.lei + '|' + a.dataset.cit;
      if (!cits.some(x => x.k === k)) cits.push({ k, lei: a.dataset.lei, cit: a.dataset.cit, url: a.getAttribute('href'), adct: !!a.dataset.adct });
    }
    const blocos = [];
    for (const x of cits.slice(0, 4)) {
      let h = null;
      try { h = await trechoLei(x.url, x.cit, x.adct); } catch (_) { /* lei sem o artigo */ }
      const ps = h ? [...html(h).querySelectorAll('p')].map(p => limpa(p.textContent)).filter(Boolean) : [];
      if (ps.length) blocos.push({ rot: `${SIGLA[x.lei] || x.lei.toUpperCase()}, ${x.cit.replace(/^Arts?\./, t => t.toLowerCase())}`, txt: ps.join('\n') });
    }
    if (!blocos.length) return card;
    for (const r of card.mapa) if (!r.lei) r.lei = leiDoErro(blocos, r.texto) || leiDoErro(blocos, r.texto + ' ' + r.erro + ' ' + (r.regra || ''));
    if (!card.trecho.length) {
      const usados = card.mapa.map(r => r.lei && r.lei.rot).filter(Boolean);
      const bs = (usados.length ? blocos.filter(b => usados.includes(b.rot)) : blocos).slice(0, 2);
      card.trecho = bs.flatMap(b => b.txt.split('\n'));
      card.fonte = `da lei (${corta(bs.map(b => b.rot).join('; '), 80)})`;
      card.art = corta(bs.map(b => b.rot).join('; '), 90);
    }
    return card;
  }

  return { KEY, V, LEI, CAUSAS, ler, montar, guardar, completarLei, comparar, leiDoErro, todos: () => Object.values(ler()), frenteHTML, mapaHTML };
})();
