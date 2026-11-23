// Refazer erros (refazer.html): reaplica as questões erradas do Caderno e dos Simulados com técnicas de estudo
// que têm apoio em pesquisa:
//  1. recuperação ativa: refazer sem ver o erro antigo, com alternativas embaralhadas (e, se quiser, tentar lembrar a regra antes);
//  2. confiança antes do resultado: erro com "certeza" é o que mais rende corrigir (efeito de hipercorreção);
//  3. diagnóstico do erro (não sabia, confundi, li mal…) e a regra escrita com as próprias palavras (autoexplicação);
//  4. correção na mesma sessão: errou ou chutou, a questão volta mais adiante até sair certa;
//  5. repetição espaçada em caixas (Leitner): 0, 1, 3, 7 e 14 dias, sem passar do dia anterior à prova;
//  6. mistura de assuntos (intercalação) em vez de bloco por assunto.
// Estado em localStorage 'refazer-v1': { "s:S1-03" | "c:12": {box, due, tent, hist:[{d,ok,conf,causa,sel}], regra, causa} }.
// Erro com causa "confundi", "não sabia" ou "número" vira card na hora (js/meus-cards.js: contraste A × B ou card da
// regra), no baralho "Meus erros" dos flashcards; o fim da sessão mostra os cards montados, em vez de só sugerir.
// As respostas originais (simulados/caderno) não são alteradas.
(async function () {
const root = document.getElementById('rf');
// A página ocupa exatamente a janela, sem rolagem (css/refazer.css, "Tela inteira"): só os painéis rolam por dentro.
document.documentElement.classList.add('rf-fixa');
const KEY = 'refazer-v1', CFG = 'refazer-v1-cfg', L = 'ABCDE';
const PROVA = (window.CONFIG && CONFIG.dataProva) || '';   // js/config.js: nenhuma revisão fica marcada depois do dia anterior
const INTERVALO = [0, 1, 3, 7, 14];         // dias até a próxima revisão, por caixa
const DOMINADA = 3;                         // caixa 3 = acertou 3 vezes seguidas em dias diferentes
const MAX_VOLTAS = 2;                       // quantas vezes a mesma questão volta na sessão
const CAUSAS = [
  ['nao_sabia', 'Não sabia o conteúdo', 'Releia o dispositivo e transforme a regra em flashcard.'],
  ['confundi', 'Sabia, mas confundi com outra regra', 'Monte um card de contraste (A × B) e estude os dois lado a lado.'],
  ['li_mal', 'Li o enunciado errado', 'Sublinhe palavras como "exceto", "incorreta", "apenas" e "somente" antes de olhar as alternativas.'],
  ['numero', 'Errei prazo, número ou quórum', 'Estude com lacuna (flashcard de números) e repita em dias diferentes.'],
  ['chute', 'Chutei', 'Sem base não há aprendizado: leia o tema antes de tentar de novo.'],
];
const CONF = [['chute', 'Chute'], ['duvida', 'Em dúvida'], ['certeza', 'Tenho certeza']];

const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const ler = (k, vazio) => { try { const v = JSON.parse(localStorage.getItem(k) || 'null'); return v && typeof v === 'object' && !Array.isArray(v) ? v : vazio; } catch (_) { return vazio; } };
const gravar = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) { } };
const hoje = () => (window.Historico ? Historico.hoje() : new Date().toISOString().slice(0, 10));
const somaDias = (d, n) => { const [y, m, dd] = d.split('-').map(Number), t = new Date(y, m - 1, dd + n, 12); return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0'); };
const dataBr = d => d.split('-').reverse().join('/');
const plural = (n, a, b) => `${n} ${n === 1 ? a : b}`;
const embaralha = a => { a = a.slice(); for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; };

// ---------- dados ----------
const PAINEL = await carregarJson('dados/painel.json');
const sim = PAINEL.simulados, lom = PAINEL.lom, temas = sim.temas || [];
const respSim = ler('simulados-respostas-v1', {});
const respLom = ler('lom_st', {});
let S = ler(KEY, {});
const cfg = Object.assign({ n: 10, mix: true, recall: true, novasPrimeiro: true }, ler(CFG, {}));

// Erros atuais entram na fila (caixa 0, vencem hoje). Quem já está no estado fica como está.
const errosSim = Object.entries(respSim).filter(([id, a]) => a && sim.q[id] && !(a.ok === true || L[a.c] === a.checked)).map(([id]) => id);
const errosLom = Object.entries(respLom).filter(([n, a]) => a && n in lom.q && !a.ok).map(([n]) => n);
for (const id of errosSim) if (!S['s:' + id]) S['s:' + id] = { box: 0, due: hoje(), tent: 0, hist: [] };
for (const n of errosLom) if (!S['c:' + n]) S['c:' + n] = { box: 0, due: hoje(), tent: 0, hist: [] };
gravar(KEY, S);

const chaves = Object.keys(S);
if (!chaves.length) {
  root.innerHTML = '<h1>Refazer erros</h1><div class="card vazio"><p><b>Nenhum erro para refazer.</b></p><p class="sub">Quando você errar questões nos Simulados ou no Caderno, elas aparecem aqui.</p></div>';
  return;
}
root.innerHTML = '<p class="sub">Carregando questões…</p>';

const temSim = chaves.some(k => k[0] === 's'), temLom = chaves.some(k => k[0] === 'c');
const [SIM, CAD, R, FUND] = await Promise.all([
  temSim ? carregarJson('dados/simulados.json') : { questions: [], references: {} },
  temLom ? carregarJson('dados/caderno/atual.json') : [],
  carregarJson('dados/revisar.json'),
  carregarJson('dados/fundamentos.json'),
]);
const porId = new Map(SIM.questions.map(q => [q.id, q]));
const cadPorN = new Map(CAD.map(q => [String(q.n), q]));

const split = t => { const i = t.indexOf(' — '); return i < 0 ? [t, ''] : [t.slice(0, i), t.slice(i + 3)]; };
const SEM_EMBARALHAR = /\b(todas|nenhuma|anteriores|acima|abaixo|alternativas|ambas)\b/i;
const LETRA_NO_TEXTO = /\b(alternativas?|letras?|gabarito)\s+[A-E]\b|\([A-E]\)|\b[A-E]\)/;
// Caderno: nome e sigla da lei vêm do build (painel.lom.info, de conteudo/caderno.json).
const CAD_INFO = lom.info || {}, CAD_SIGLA = CAD_INFO.sigla || 'Caderno', CAD_NOME = CAD_INFO.titulo || 'Caderno';
const secLom = n => lom.secoes[lom.q[n]] || CAD_NOME;
const rotLei = k => CAD_SIGLA + ', art. ' + k;

// Enunciado que veio num bloco só ("…a seguir: I. … II. … III. … Todas as conclusões corretas estão em:"):
// um parágrafo para a introdução, um para cada item, na ordem I, II, III…, e um para o comando final.
// Quebras que o enunciado já tem (antes de um artigo citado, por exemplo) ficam. Os itens seguem o separador do
// item I ("I."), para incisos citados dentro de um item ("I – Regime Jurídico Único; II – …") não virarem itens.
const ROMANOS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
const COMANDO = /[.;:)]\s+(?=(?:Todas as|Todos os|É correto|É CORRETO|É incorreto|Est(?:á|ão) corret[ao]s?|Assinale|Marque|Indique|Quais|Qual (?:das|dos)|A alternativa|A sequência)\b)/;
function paragrafos(t) {
  const pos = [];
  let de = 0, sep = '[.)\\-–]';
  for (const r of ROMANOS) {
    const re = new RegExp(`(^|[\\s:;.])${r}\\s*(${sep})\\s`, 'g');
    re.lastIndex = de;
    const m = re.exec(t);
    if (!m) break;
    pos.push(m.index + m[1].length);
    de = pos[pos.length - 1] + r.length;
    sep = '\\' + m[2];
  }
  // Com todos os itens já começando linha, o enunciado já está formatado.
  if (pos.length < 2 || pos.every(p => /(^|\n)[ \t]*$/.test(t.slice(0, p)))) return t;
  const partes = [t.slice(0, pos[0])];
  // "III.\n\nSomente…" (quebra dentro do item, vinda do dado) → "III. Somente…"
  pos.forEach((p, i) => partes.push(t.slice(p, pos[i + 1]).replace(/^([IVX]+\s*[.)\-–])\s+/, '$1 ')));
  const ult = partes.pop(), m = ult.match(COMANDO);
  if (m) partes.push(ult.slice(0, m.index + 1), ult.slice(m.index + m[0].length));
  else partes.push(ult);
  return partes.map(s => s.trim()).filter(Boolean).join('\n\n');
}

// Monta tudo o que a tela precisa de uma questão; null se a questão não existir mais nos dados.
const cache = new Map();
function info(key) {
  if (cache.has(key)) return cache.get(key);
  let o = null;
  if (key[0] === 's') {
    const id = key.slice(2), q = porId.get(id), m = sim.q[id];
    if (q && m) {
      const rq = R.q[id] || { b: [], g: '', o: [] };
      const [norma, assunto] = split(temas[m[3]] || sim.areas[m[1]]);
      const fonte = (sim.fontes[m[0]] || '').split(' · ');
      const blocos = rq.b.map(b => R.blocos[b]).filter(Boolean).map(([rot, txt]) => ({ rot, txt }));
      if (!blocos.length && q.legal && q.legal.text) {
        // "Constituição…" + "arts. 21, 23 e 26" → "CF, arts. 21, 23 e 26", para o balão achar os artigos.
        const t = q.legal.title || '', sigla = /Constitui/.test(t) ? 'CF' : null;
        blocos.push({ rot: sigla && q.legal.article ? sigla + ', ' + q.legal.article : t || 'Fundamento legal', txt: q.legal.text });
      }
      o = {
        key, norma, assunto, stem: paragrafos(q.stem), opts: q.options, certa: L.indexOf(q.checked),
        marcouAntes: respSim[id] ? respSim[id].c : null,
        regra: rq.g || '', explica: q.explanation || '', itens: q.items || [], porOpcao: q.alts || [], blocos,
        refs: (q.refs || []).filter(k => SIM.references[k]).map(k => [...SIM.references[k], k]), area: q.area,
        origem: fonte[0] + ' · questão ' + (+q.number || q.number) + (fonte[1] ? ' (' + fonte[1].replace(/ — .*/, '') + ')' : ''),
        href: 'simulados.html#q=' + id,
        letras: LETRA_NO_TEXTO.test(q.explanation + ' ' + (q.items || []).map(i => i.note).join(' ')),
      };
    }
  } else {
    const n = key.slice(2), salvo = respLom[n], atual = cadPorN.get(n);
    const q = atual;
    if (q) {
      let blocos = [];
      if (q.law && q.law.length) blocos = q.law.map(b => ({ rot: rotLei(b.k), txt: b.paras.join('\n') }));
      else blocos = (lom.arts[n] || []).map(a => R.lom[a]).filter(b => b !== undefined).map(b => ({ rot: R.blocos[b][0], txt: R.blocos[b][1] }));
      // No Caderno, o julgamento de cada item vem repetido dentro de toda alternativa: tira de lá e põe no "Item a item".
      const itensLom = [], xs = (q.x || []).map(t => (t || '').split(/\n{2,}/).filter(par => {
        const m = par.match(/^\s*([IVX]+) — (correta|incorreta)\.\s*([\s\S]*)$/);
        if (!m) return true;
        if (!itensLom.some(i => i.id === m[1])) itensLom.push({ id: m[1], ok: m[2] === 'correta', note: m[3].trim() });
        return false;
      }).join('\n\n'));
      o = {
        key, norma: CAD_SIGLA, assunto: secLom(n), stem: paragrafos(q.stem), opts: q.opts, certa: q.a,
        marcouAntes: salvo ? salvo.c : null, regra: '', explica: '', itens: itensLom, porOpcao: xs, blocos,
        refs: [], area: CAD_NOME,
        origem: 'Caderno (' + CAD_NOME + ') · questão ' + n + (q.ref ? ' (' + q.ref + ')' : ''),
        href: 'caderno.html#' + n, letras: false,
      };
    }
  }
  if (o) o.embaralhar = !o.opts.some(t => SEM_EMBARALHAR.test(t)) && !o.letras;
  cache.set(key, o);
  return o;
}

// ---------- agenda (caixas de Leitner) ----------
function agenda(e, ok, conf) {
  const h = hoje();
  e.tent = (e.tent || 0) + 1;
  if (!ok) e.box = 0;
  else if (conf !== 'chute') e.box = Math.min(INTERVALO.length - 1, e.box + 1);   // acerto no chute não sobe de caixa
  let dias = !ok ? 1 : conf === 'chute' ? 1 : INTERVALO[e.box];
  if (ok && conf !== 'chute' && e.box === 0) dias = 1;
  let due = somaDias(h, dias);
  const limite = PROVA ? somaDias(PROVA, -1) : '';
  if (limite && h < limite && due > limite) due = limite;                                    // tudo revisto antes da prova
  e.due = due;
}
const dominada = e => e.box >= DOMINADA;
const info_ok = k => info(k) !== null;
const itens = () => Object.keys(S).filter(info_ok);

function resumo() {
  const ks = itens(), h = hoje();
  const venc = ks.filter(k => S[k].due <= h && !(dominada(S[k]) && S[k].due > h));
  return {
    ks, venc,
    novas: ks.filter(k => !S[k].tent).length,
    aprendendo: ks.filter(k => S[k].tent && !dominada(S[k])).length,
    dominadas: ks.filter(k => dominada(S[k])).length,
    caixas: INTERVALO.map((_, b) => ks.filter(k => S[k].box === b && S[k].tent).length),
  };
}

// ---------- início ----------
// Painel sem rolagem da página: no alto, a trilha das caixas (de "nunca refeita" a "dominada", com o que vence hoje
// em cada caixa); embaixo, a sessão de hoje, os erros por assunto e as causas; à direita, as regras do aluno.
const NOMES_CAIXA = ['Errou', '1 dia', '3 dias', '7 dias', '14 dias'];
const CORES_CAIXA = ['#c0392b', '#e08a1e', '#d9b300', '#5b8def', '#0f7a55'];
function inicio() {
  root.dataset.tela = 'inicio';
  const r = resumo(), h = hoje(), dias = Math.ceil((new Date(PROVA + 'T12:00') - new Date(h + 'T12:00')) / 864e5);
  const porNorma = new Map();
  for (const k of r.ks) { if (dominada(S[k])) continue; const n = info(k).norma, t = porNorma.get(n) || { n, total: 0, venc: 0 }; t.total++; if (S[k].due <= h) t.venc++; porNorma.set(n, t); }
  const normas = [...porNorma.values()].sort((a, b) => b.venc - a.venc || b.total - a.total);
  const maxNorma = Math.max(1, ...normas.map(t => t.total));
  const causas = new Map();
  for (const k of r.ks) for (const x of S[k].hist) if (!x.ok && x.causa) causas.set(x.causa, (causas.get(x.causa) || 0) + 1);
  const totCausas = [...causas.values()].reduce((a, b) => a + b, 0);
  const ordemCausas = CAUSAS.filter(c => causas.get(c[0])).sort((a, b) => causas.get(b[0]) - causas.get(a[0]));
  // Mais recentes primeiro (data da última tentativa).
  const ultima = k => { const hs = S[k].hist; return hs.length ? hs[hs.length - 1].d : ''; };
  const regras = r.ks.filter(k => S[k].regra).sort((a, b) => ultima(b).localeCompare(ultima(a)));
  const vencSet = new Set(r.venc);
  const hojeNovas = r.ks.filter(k => !S[k].tent && vencSet.has(k)).length;
  const hojeCaixa = INTERVALO.map((_, b) => r.ks.filter(k => S[k].tent && S[k].box === b && vencSet.has(k)).length);
  const estacao = (n, rot, hj, cor, extra = '') =>
    `<li class="tr-est${extra}" style="--cor:${cor}"><b>${n}</b><span>${rot}</span><small>${hj ? plural(hj, 'vence hoje', 'vencem hoje') : '&nbsp;'}</small></li>`;
  const somaAprendendo = r.caixas.slice(0, DOMINADA).reduce((a, b) => a + b, 0);
  root.innerHTML =
    `<header class="rf-cab"><div><h1>Refazer erros</h1><p class="sub">${plural(r.ks.length, 'questão errada', 'questões erradas')}. ` +
    `${!PROVA ? '' : dias > 0 ? `Prova em ${plural(dias, 'dia', 'dias')}, em ${dataBr(PROVA)}.` : `Prova em ${dataBr(PROVA)}.`}</p></div>` +
    `<details class="rf-como"><summary>Por que funciona assim?</summary><ul class="how">` +
    `<li><b>Refazer sem olhar</b> (recuperação ativa) fixa muito mais do que reler a resposta. As alternativas vêm embaralhadas para você não decorar a letra.</li>` +
    `<li><b>Dizer a confiança antes do resultado</b>: o erro cometido com certeza é o que mais se corrige quando você vê a resposta certa.</li>` +
    `<li><b>Explicar com suas palavras</b> e dizer por que errou transforma o erro em regra, e mostra se o problema é conteúdo, atenção ou confusão.</li>` +
    `<li><b>Voltar depois</b>: a questão errada reaparece na mesma sessão e em dias espaçados. Revisar em intervalos crescentes dura mais do que revisar tudo de uma vez.</li>` +
    `</ul></details></header>` +
    `<div class="rf-painel">` +
    // Trilha: cada questão anda da esquerda para a direita a cada acerto com confiança.
    `<section class="card rf-trilha" aria-labelledby="trT"><h2 id="trT">Onde cada questão está</h2>` +
    `<ol class="tr-linha">` + estacao(r.novas, 'Nunca refeitas', hojeNovas, '#9a9aa4', ' tr-nova') +
    r.caixas.map((n, b) => estacao(n, NOMES_CAIXA[b], hojeCaixa[b], CORES_CAIXA[b], b === DOMINADA ? ' tr-dom' : '')).join('') + `</ol>` +
    `<div class="tr-chaves"><span class="tr-chave tr-ch-nova"></span>` +
    `<span class="tr-chave tr-ch-apr"><b>${r.aprendendo}</b> aprendendo</span>` +
    `<span class="tr-chave tr-ch-dom"><b>${r.dominadas}</b> dominadas (3 acertos seguidos)</span></div>` +
    `<p class="dica tr-nota">Cada acerto com confiança leva a questão para a caixa seguinte, e ela volta mais tarde (sempre antes da prova). Errou ou chutou: volta para “Errou”.</p></section>` +
    // Sessão de hoje
    `<section class="card rf-sessao"><h2>Sessão de hoje</h2>` +
    `<p class="ss-hoje"><b>${r.venc.length}</b> ${r.venc.length === 1 ? 'questão vence' : 'questões vencem'} hoje</p>` +
    (r.venc.length ? `<p class="dica">Você refaz sem ver o erro antigo, diz o quanto tem certeza e só depois vê a resposta, com a base do conhecimento.</p>`
      : `<p class="dica">Nada vence hoje. Dá para fazer um treino extra com as que você menos domina.</p>`) +
    `<div class="cfg"><label>Tamanho <select id="cN">${[5, 10, 20, 0].map(n => `<option value="${n}"${cfg.n === n ? ' selected' : ''}>${n ? n + ' questões' : 'todas'}</option>`).join('')}</select></label>` +
    `<label><input type="checkbox" id="cMix"${cfg.mix ? ' checked' : ''}> Misturar assuntos</label>` +
    `<label><input type="checkbox" id="cNov"${cfg.novasPrimeiro ? ' checked' : ''}> Nunca feitas primeiro</label>` +
    `<label><input type="checkbox" id="cRec"${cfg.recall ? ' checked' : ''}> Tentar lembrar antes de ver as alternativas</label></div>` +
    `<p class="ss-acao"><button class="btn main" id="ir">${r.venc.length ? 'Começar sessão' : 'Treino extra'}</button></p></section>` +
    // Por assunto
    `<section class="card rf-assuntos"><h2>Por assunto</h2>` +
    (normas.length ? `<table class="ass"><thead><tr><th scope="col">Assunto</th><th scope="col" class="nm">Erradas</th><th scope="col" class="nm">Hoje</th><th scope="col"><span class="vh">Treinar</span></th></tr></thead><tbody>` +
      normas.map(t => `<tr><th scope="row">${esc(t.n)}<i class="ass-bar" style="width:${t.total / maxNorma * 100}%"></i></th><td class="nm">${t.total}</td>` +
        `<td class="nm${t.venc ? ' hj' : ''}">${t.venc || '–'}</td><td><button class="link d" data-norma="${esc(t.n)}">treinar</button></td></tr>`).join('') + `</tbody></table>`
      : `<p class="dica">Todas as questões erradas já estão dominadas.</p>`) + `</section>` +
    // Por que você erra
    `<section class="card rf-causas"><h2>Por que você erra</h2>` +
    (totCausas ? ordemCausas.map(c => `<div class="bar"><span>${esc(c[1])}</span><div><i style="width:${causas.get(c[0]) / totCausas * 100}%"></i></div><b>${causas.get(c[0])}</b></div>`).join('') +
      `<p class="causa-dica"><b>O que fazer:</b> ${esc(ordemCausas[0][2])}</p>`
      : `<p class="dica">Ao corrigir um erro, diga por que errou. As causas aparecem aqui.</p>`) + `</section>` +
    // Suas regras
    `<section class="card rf-regras"><h2>Suas regras <span class="rg-tot">(${regras.length})</span></h2>` +
    (regras.length ? `<p class="dica regra-sub">As frases que você escreveu ao corrigir cada erro, separadas por lei.</p><div class="rf-regras-corpo">${regrasHTML(regras.map(k => [k, S[k].regra]))}</div>`
      : `<p class="dica">Ao corrigir um erro, escreva a regra com suas palavras. Ela aparece aqui, separada por lei.</p>`) + `</section>` +
    `</div>`;
  const salva = () => { cfg.n = +document.getElementById('cN').value; cfg.mix = document.getElementById('cMix').checked; cfg.novasPrimeiro = document.getElementById('cNov').checked; cfg.recall = document.getElementById('cRec').checked; gravar(CFG, cfg); };
  document.getElementById('ir').onclick = () => { salva(); comecar(null); };
  root.querySelectorAll('[data-norma]').forEach(b => b.onclick = () => { salva(); comecar(b.dataset.norma); });
  ligaRegras(root);
}

// ---------- suas regras ----------
// O caderno de regras do aluno, com a linguagem da ficha dos flashcards (Literata, margem vermelha, pauta azul).
// Agrupado por norma (grupos recolhíveis, o de mais regras primeiro); em cada regra, a frase do aluno vem
// primeiro, cortada em 4 linhas com "mostrar tudo", e embaixo o tema com os artigos clicáveis (js/ref-lei.js).
// Com 6 regras ou mais aparece a busca, presa no topo da coluna.
const semAcento = t => t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const comRefs = (t, lei) => window.refLei ? refLei.html(t, { lei }) : esc(t);
function regrasHTML(lista) {
  const grupos = new Map();   // norma → [html das regras], na ordem recebida (mais recentes primeiro)
  for (const [k, txt] of lista) {
    const o = info(k), lei = window.refLei ? refLei.leiDe(o.norma) : null;
    if (!grupos.has(o.norma)) grupos.set(o.norma, []);
    grupos.get(o.norma).push(`<li class="regra-i" data-busca="${esc(semAcento(txt + ' ' + o.norma + ' ' + o.assunto))}">` +
      `<p class="regra-txt">${comRefs(txt, lei)}</p><p class="regra-tema">${comRefs(o.assunto, lei)}</p></li>`);
  }
  const ordem = [...grupos].sort((a, b) => b[1].length - a[1].length || a[0].localeCompare(b[0], 'pt-BR'));
  const busca = lista.length >= 6
    ? `<div class="regra-ctl"><input type="search" class="regra-q" placeholder="Buscar nas suas regras" aria-label="Buscar nas suas regras">` +
      `<p class="regra-n" aria-live="polite"></p></div>` : '';
  return `<div class="regras">${busca}` + ordem.map(([n, itens]) =>
    `<details class="regra-grupo" open><summary><span class="rg-nome">${esc(n)}</span><span class="rg-n" data-total="${itens.length}">${itens.length}</span></summary>` +
    `<ul class="regra-lista">${itens.join('')}</ul></details>`).join('') + `</div>`;
}
// Liga a busca e o "mostrar tudo". O corte só é medido com a regra visível (grupo ou bloco podem estar fechados).
function ligaRegras(root) {
  for (const box of root.querySelectorAll('.regras')) {
    const itens = [...box.querySelectorAll('.regra-i')], q = box.querySelector('.regra-q'), n = box.querySelector('.regra-n');
    const medir = () => {
      for (const p of box.querySelectorAll('.regra-txt:not(.aberta)')) {
        const b = p.nextElementSibling, tem = b && b.classList.contains('regra-mais');
        if (!p.clientHeight) continue;
        const corta = p.scrollHeight > p.clientHeight + 2;
        if (corta && !tem) p.insertAdjacentHTML('afterend', '<button type="button" class="link regra-mais">mostrar tudo</button>');
        else if (!corta && tem) b.remove();
      }
    };
    if (q) q.oninput = () => {
      const termo = semAcento(q.value.trim());
      let vis = 0;
      for (const g of box.querySelectorAll('.regra-grupo')) {
        let noGrupo = 0;
        for (const li of g.querySelectorAll('.regra-i')) { li.hidden = !!termo && !li.dataset.busca.includes(termo); if (!li.hidden) noGrupo++; }
        const c = g.querySelector('.rg-n');
        c.textContent = termo ? `${noGrupo} de ${c.dataset.total}` : c.dataset.total;
        g.hidden = !noGrupo;
        if (termo && noGrupo) g.open = true;
        vis += noGrupo;
      }
      n.textContent = !termo ? '' : vis ? `${vis} de ${itens.length} regras` : 'Nenhuma regra com esse termo.';
      medir();
    };
    box.addEventListener('click', e => {
      const b = e.target.closest('.regra-mais');
      if (!b) return;
      const p = b.previousElementSibling, aberta = p.classList.toggle('aberta');
      b.textContent = aberta ? 'mostrar menos' : 'mostrar tudo';
    });
    // "toggle" não sobe pela árvore: escuta cada <details> (os grupos e o bloco "Suas regras").
    for (const d of [...box.querySelectorAll('details'), box.closest('details')].filter(Boolean)) d.addEventListener('toggle', () => { if (d.open) medir(); });
    medir();
  }
}

// ---------- sessão ----------
let fila, sessao, atual;
function intercala(ks) {                  // um de cada assunto por vez
  const g = new Map();
  for (const k of ks) { const n = info(k).norma; (g.get(n) || g.set(n, []).get(n)).push(k); }
  const listas = [...g.values()].map(embaralha), out = [];
  while (listas.some(l => l.length)) for (const l of listas) if (l.length) out.push(l.shift());
  return out;
}
function comecar(norma) {
  const r = resumo(), h = hoje();
  const ok = k => !norma || info(k).norma === norma;
  let ks = r.ks.filter(k => ok(k) && S[k].due <= h && !dominada(S[k]));
  const nova = k => S[k].tent ? 0 : 1;
  ks.sort((a, b) => (cfg.novasPrimeiro ? nova(b) - nova(a) : 0) || S[a].box - S[b].box || S[a].due.localeCompare(S[b].due));
  if (!ks.length) ks = r.ks.filter(ok).sort((a, b) => S[a].box - S[b].box || (S[a].hist.length - S[b].hist.length));   // treino extra
  if (cfg.n) ks = ks.slice(0, cfg.n);
  fila = cfg.mix ? intercala(ks) : ks;
  sessao = { total: fila.length, voltas: {}, log: [], resolvidas: new Set(), comecou: h, cards: new Map() };
  proxima();
}
function proxima() {
  if (!fila.length) return fim();
  const key = fila.shift(), o = info(key);
  const perm = o.embaralhar ? embaralha(o.opts.map((_, i) => i)) : o.opts.map((_, i) => i);
  atual = { key, o, perm, sel: null, conf: null, cortes: new Set(), lembrou: '', fase: cfg.recall ? 'lembrar' : 'escolher', volta: (sessao.voltas[key] || 0) > 0 };
  tela();
}
// Faixa do topo da sessão: sair, progresso com o placar da sessão, e a lei e o assunto da questão.
function topo() {
  root.dataset.tela = 'sessao';
  const feitas = sessao.resolvidas.size, ret = fila.filter(k => (sessao.voltas[k] || 0) > 0).length;
  const acertos = sessao.log.filter(l => l.ok).length, erros = sessao.log.length - acertos;
  return `<div class="ss-cab"><button class="link" id="sai">‹ Sair da sessão</button>` +
    `<div class="ss-prog"><span class="ss-num">Questão <b>${Math.min(feitas + 1, sessao.total)}</b> de ${sessao.total}</span>` +
    `<div class="prog" role="progressbar" aria-label="Questões resolvidas" aria-valuemin="0" aria-valuemax="${sessao.total}" aria-valuenow="${feitas}"><i style="width:${feitas / sessao.total * 100}%"></i></div>` +
    `<span class="ss-placar"><span class="ok">${plural(acertos, 'acerto', 'acertos')}</span><span class="no">${plural(erros, 'erro', 'erros')}</span>` +
    `<span>${ret} na fila de correção</span></span></div>` +
    `<div class="ss-tema">${atual.volta ? '<span class="pill re">Segunda chance</span>' : ''}<span class="pill">${esc(atual.o.norma)}</span><span class="ss-ass">${esc(atual.o.assunto)}</span></div></div>`;
}
// Coluna com mais texto abaixo ganha um esmaecido no pé (some quando se chega ao fim), para não passar despercebida.
function marcarRolagem() {
  for (const c of root.querySelectorAll('.rf-q>div, .rf-fb>.rf-esq, .rf-fb>.rf-meio, .rf-fb>.rf-dir')) {
    const ver = () => c.classList.toggle('tem-mais', c.scrollTop + c.clientHeight < c.scrollHeight - 4);
    c.addEventListener('scroll', ver, { passive: true });
    ver();
  }
}
// Um <p> por parágrafo do enunciado: em colunas, a quebra cai entre parágrafos (afirmativas), nunca no meio de um.
const stemHTML = o => `<div class="stem">${o.stem.split(/\n{2,}/).map(t => `<p>${esc(t)}</p>`).join('')}</div>`;
function tela() {
  const a = atual, o = a.o;
  let h = topo() + `<section class="card rf-q"><div class="rf-esq">${stemHTML(o)}</div><div class="rf-dir">`;
  if (a.fase === 'lembrar') {
    h += `<p class="passo">Passo 1: lembre antes de ver as alternativas</p>` +
      `<textarea id="lemb" placeholder="O que a lei ou a norma diz sobre isso? Escreva em uma ou duas frases (pode deixar em branco)."></textarea>` +
      `<div class="acao"><span class="dica">Esforço de lembrar, mesmo errando, ajuda a fixar a resposta certa.</span><button class="btn main" id="ver">Ver alternativas</button></div></div></section>`;
  } else {
    h += `<p class="passo">${cfg.recall ? 'Passo 2: escolha' : 'Escolha'} e diga o quanto tem certeza</p><div class="opts" role="radiogroup" aria-label="Alternativas">` +
      a.perm.map((i, p) => {
        const cortada = a.cortes.has(i);
        return `<div class="op-linha"><button type="button" class="cut" data-cut="${i}" aria-pressed="${cortada}" aria-label="${cortada ? 'Restaurar' : 'Riscar'} alternativa ${L[p]}" title="Riscar ou restaurar esta alternativa"><span aria-hidden="true">✂</span></button>` +
          `<button type="button" class="op${a.sel === i ? ' sel' : ''}${cortada ? ' riscada' : ''}" role="radio" aria-checked="${a.sel === i}"${cortada ? ' aria-disabled="true"' : ''} data-i="${i}"><span class="l">${L[p]}</span><span class="t">${esc(o.opts[i])}</span></button></div>`;
      }).join('') + `</div>` +
      `<div class="conf" role="group" aria-label="Confiança"><span>Confiança:</span>${CONF.map(c => `<button type="button" class="chip" aria-pressed="${a.conf === c[0]}" data-c="${c[0]}">${c[1]}</button>`).join('')}</div>` +
      `<div class="acao"><span class="dica">✂ risca a alternativa que você descartou. Teclas 1–5 escolhem; Enter confirma.</span><button class="btn main" id="ok" ${a.sel !== null && a.conf ? '' : 'disabled'}>Confirmar</button></div></div></section>`;
  }
  root.innerHTML = h;
  // Enunciado que não cabe na altura vira duas colunas de leitura (só na tela larga).
  const esqQ = root.querySelector('.rf-q .rf-esq');
  if (esqQ && matchMedia('(min-width: 900px)').matches && esqQ.scrollHeight > esqQ.clientHeight + 1) esqQ.querySelector('.stem').classList.add('longo');
  marcarRolagem();
  document.getElementById('sai').onclick = () => { if (!sessao.log.length || confirm('Sair da sessão? O que você já respondeu fica salvo.')) inicio(); };
  if (a.fase === 'lembrar') {
    const t = document.getElementById('lemb'); t.focus();
    document.getElementById('ver').onclick = () => { a.lembrou = t.value.trim(); a.fase = 'escolher'; tela(); };
  } else {
    root.querySelectorAll('.op').forEach(b => b.onclick = () => { const i = +b.dataset.i; if (a.cortes.has(i)) return; a.sel = i; tela(); });
    // Riscar: a alternativa riscada não pode ser escolhida; riscar a marcada desmarca.
    root.querySelectorAll('.cut').forEach(b => b.onclick = () => {
      const i = +b.dataset.cut;
      if (a.cortes.has(i)) a.cortes.delete(i); else { a.cortes.add(i); if (a.sel === i) a.sel = null; }
      tela();
      const de = root.querySelector(`[data-cut="${i}"]`); if (de) de.focus();
    });
    root.querySelectorAll('.chip').forEach(b => b.onclick = () => { a.conf = b.dataset.c; tela(); });
    document.getElementById('ok').onclick = confirmar;
  }
}
document.addEventListener('keydown', e => {
  if (!atual || !root.contains(document.activeElement) && document.activeElement !== document.body) return;
  if (/^(TEXTAREA|INPUT|SELECT)$/.test(e.target.tagName)) return;
  if (atual.fase === 'escolher' && /^[1-5]$/.test(e.key) && atual.perm[+e.key - 1] !== undefined && !atual.cortes.has(atual.perm[+e.key - 1])) { atual.sel = atual.perm[+e.key - 1]; tela(); }
  else if (e.key === 'Enter') { const b = document.getElementById('ok') || document.getElementById('prox'); if (b && !b.disabled && e.target.tagName !== 'BUTTON') b.click(); }
});

function confirmar() {
  const a = atual, ok = a.sel === a.o.certa;
  a.ok = ok; a.fase = 'feedback';
  feedback();
}

// Fundamentação: o local real da regra (lei e artigo, norma e item), a prova de origem e a bibliografia da disciplina (js/fundamento.js).
function base(o) {
  const f = Fundamento.montar({ blocos: o.blocos, refs: o.refs, refsPorChave: SIM.references, fontes: FUND[o.key.slice(2)] || [], explicacao: o.explica + ' ' + o.porOpcao.join(' '), area: o.area, norma: o.norma, assunto: o.assunto });
  atual.fontes = Fundamento.balao(f);
  return Fundamento.html(f, esc, true).replace(/<\/div>$/, `<p class="dica">Origem da questão: <a href="${esc(o.href)}">${esc(o.origem)}</a></p></div>`);
}
function feedback() {
  const a = atual, o = a.o, e = S[a.key], ok = a.ok;
  const letra = i => L[a.perm.indexOf(i)];
  const altaConf = !ok && a.conf === 'certeza';
  let msg;
  if (ok && a.conf === 'chute') msg = 'Certo, mas no chute: ainda não conta como aprendida. Confira por que está certa.';
  else if (ok) msg = 'Acertou. Confira se o seu raciocínio bate com a regra abaixo.';
  else if (altaConf) msg = 'Você tinha certeza e errou. É o tipo de erro que mais vale corrigir agora: leia com atenção a diferença.';
  else msg = 'Errou. Veja a regra e diga por que errou antes de seguir.';
  // Três colunas fixas, cada uma com um papel (nada se parte entre elas; a que não couber rola sozinha):
  // o enunciado; o veredito, as alternativas comentadas e o item a item; a regra, o comentário, a base e a lei.
  let h = topo() + `<section class="card rf-fb"><div class="rf-esq">${stemHTML(o)}</div><div class="rf-meio">` +
    `<div class="veredito ${ok ? 'ok' : 'no'}" id="vered" tabindex="-1">${ok ? 'Certo' : 'Errado'} — a certa é a ${letra(o.certa)}<small>${esc(msg)}</small></div>` +
    (a.lembrou ? `<div class="bloco eco"><b>O que você lembrou antes</b><p>${esc(a.lembrou)}</p></div>` : '') +
    `<div class="opts">` + a.perm.map(i => {
      const marca = i === o.certa ? 'certa' : i === a.sel ? 'errada' : '';
      const ex = o.porOpcao[i] ? `<span class="e">${esc(o.porOpcao[i])}</span>` : '';
      return `<div class="op ${marca}" style="cursor:default"><span class="l">${letra(i)}</span><span>${esc(o.opts[i])}${i === a.sel ? ' <b>(sua resposta)</b>' : ''}</span>${ex}</div>`;
    }).join('') + `</div>` +
    // Item a item fica junto das alternativas: é o julgamento de cada item do enunciado.
    (o.itens.length ? `<div class="bloco"><b>Item a item</b><ul class="itens">${o.itens.map(i => `<li><span class="v ${i.ok ? 'ok' : 'no'}">${esc(i.id)} ${i.ok ? '✓ correto' : '✗ errado'}</span> — ${esc(i.note)}</li>`).join('')}</ul></div>` : '') +
    `</div><div class="rf-dir">`;
  // 1) a regra em uma frase; 2) o comentário; 3) base, com o texto das fontes no balão (js/texto-lei.js)
  const regra = o.regra || o.explica;
  if (regra) h += `<div class="bloco"><b>Regra-chave</b><p>${esc(regra)}</p></div>`;
  if (o.explica && o.regra && o.explica !== o.regra) h += `<div class="bloco"><b>Comentário</b><p>${esc(o.explica)}</p></div>`;
  h += base(o);
  // diagnóstico e regra própria
  h += `</div><div class="rf-pe"><div class="rf-diag">`;
  if (!ok || a.conf === 'chute') {
    h += `<div class="rf-causa"><p class="passo">Por que ${ok ? 'foi no chute' : 'errei'}? Escolha uma</p><div class="conf" role="group">${CAUSAS.map(c => `<button type="button" class="chip" aria-pressed="${a.causa === c[0]}" data-causa="${c[0]}">${esc(c[1])}</button>`).join('')}</div></div>`;
  }
  h += `<div class="rf-sua"><p class="passo">Com suas palavras: qual é a regra?</p><textarea id="regra" placeholder="Releia a base ao lado e escreva a regra em uma frase, sem copiar.">${esc(a.regra !== undefined ? a.regra : e.regra || '')}</textarea></div></div>`;
  const falta = (!ok || a.conf === 'chute') && !a.causa;
  h += `<div class="acao"><span class="dica">${ok ? '' : 'Esta questão volta mais adiante na sessão.'}</span><button class="btn main" id="prox" ${falta ? 'disabled' : ''}>${fila.length || !ok ? 'Próxima' : 'Ver resultado'}</button></div></div></section>`;
  root.innerHTML = h;
  document.getElementById('sai').onclick = () => { if (confirm('Sair da sessão? O que você já respondeu fica salvo.')) inicio(); };
  const ta = document.getElementById('regra'), prox = document.getElementById('prox');
  ta.oninput = () => { a.regra = ta.value; };
  root.querySelectorAll('[data-causa]').forEach(b => b.onclick = () => { a.causa = b.dataset.causa; a.regra = ta.value; feedback(); document.getElementById('regra').focus(); });
  prox.onclick = seguir;
  marcarRolagem();
  document.getElementById('vered').focus({ preventScroll: false });
}

function seguir() {
  const a = atual, e = S[a.key], h = hoje();
  const regra = (document.getElementById('regra').value || '').trim();
  if (regra) e.regra = regra;
  const primeira = !(sessao.voltas[a.key] > 0);
  agenda(e, a.ok, a.conf);
  e.hist.push({ d: h, ok: a.ok, conf: a.conf, causa: a.causa || null, sel: a.sel });
  gravar(KEY, S);
  // O diagnóstico vira card (js/meus-cards.js), no baralho "Erros". Se a questão não traz o trecho da lei, o card é
  // completado depois com o artigo citado (busca em leis/*.md) e guardado de novo.
  if (!a.ok && window.MeusCards) {
    const card = MeusCards.guardar(MeusCards.montar({ key: a.key, causa: a.causa, o: a.o, sel: a.sel, regra: e.regra || regra }));
    if (card) {
      sessao.cards.set(card.id, card);
      MeusCards.completarLei(card, a.o).then(c => { if (c) MeusCards.guardar(c); }).catch(() => {});
    }
  }
  if (window.Historico) Historico.registrar(a.ok, 'refazer');   // conta no "Hoje" e no gráfico do Início
  sessao.log.push({ key: a.key, ok: a.ok, conf: a.conf, causa: a.causa || null, primeira, regra });
  const preciso = !a.ok || a.conf === 'chute';
  if (preciso && (sessao.voltas[a.key] || 0) < MAX_VOLTAS) {
    sessao.voltas[a.key] = (sessao.voltas[a.key] || 0) + 1;
    fila.splice(Math.min(3, fila.length), 0, a.key);               // volta depois de algumas outras
  } else sessao.resolvidas.add(a.key);
  proxima();
}

function fim() {
  root.dataset.tela = 'fim';
  const lg = sessao.log, h = hoje();
  const prim = lg.filter(x => x.primeira), acPrim = prim.filter(x => x.ok).length;
  const corrigidas = [...new Set(lg.filter(x => !x.primeira && x.ok).map(x => x.key))].filter(k => lg.some(x => x.key === k && !x.ok));
  const certezaErro = prim.filter(x => !x.ok && x.conf === 'certeza');
  const certezaTot = prim.filter(x => x.conf === 'certeza');
  const causas = new Map();
  for (const x of lg) if (x.causa) causas.set(x.causa, (causas.get(x.causa) || 0) + 1);
  const top = [...causas].sort((a, b) => b[1] - a[1])[0], topCausa = top && CAUSAS.find(c => c[0] === top[0]);
  const cardsHoje = [...sessao.cards.values()];
  const aindaErradas = [...new Set(lg.filter(x => !x.ok).map(x => x.key))].filter(k => !lg.some(x => x.key === k && x.ok && x.conf !== 'chute'));
  const regras = lg.filter(x => x.regra);
  const vistos = new Set();
  root.innerHTML = `<h1>Sessão concluída</h1><p class="sub">${plural(sessao.total, 'questão', 'questões')} · ${dataBr(h)}</p>` +
    `<div class="res"><div class="num"><b>${prim.length ? Math.round(acPrim / prim.length * 100) : 0}%</b><span>acertos de primeira (${acPrim} de ${prim.length})</span></div>` +
    `<div class="num"><b>${corrigidas.length}</b><span>erros corrigidos na própria sessão</span></div>` +
    `<div class="num"><b>${aindaErradas.length}</b><span>ainda sem acertar com segurança</span></div></div><div class="rf-grade rf-fim">` +
    (certezaTot.length ? `<section class="card"><h2>Sua calibragem</h2><p style="margin:0">Das ${plural(certezaTot.length, 'vez', 'vezes')} em que disse ter certeza, errou ${certezaErro.length}. ` +
      (certezaErro.length ? 'Esses são os erros mais perigosos na prova, porque você não desconfia deles: releia a regra de cada um.' : 'Sua certeza bateu com os acertos.') + `</p></section>` : '') +
    (top || cardsHoje.length ? `<section class="card rf-padrao"><h2>Seu padrão de erro hoje</h2><p style="margin:0">` +
      (top ? `<b>${esc(topCausa[1])}</b> (${top[1]}×). ` : '') +
      (cardsHoje.length ? `Montei ${plural(cardsHoje.length, 'card', 'cards')} com os erros de hoje e coloquei nos <a href="flashcards.html?lei=meus">Flashcards, no baralho Erros</a>. Tente responder antes de abrir a resposta.`
        : esc(topCausa[2])) + `</p>` + cardsHoje.map(cardHTML).join('') + `</section>` : '') +
    (aindaErradas.length ? `<section class="card"><h2>Reler antes de seguir</h2><ul class="rows">${aindaErradas.map(k => { const o = info(k); return `<li><span>${esc(o.norma)} — ${esc(o.assunto)}</span><a class="d" href="${esc(o.href)}">abrir</a><span></span></li>`; }).join('')}</ul></section>` : '') +
    (regras.length ? `<section class="card"><h2>Suas regras de hoje</h2>` + regrasHTML(regras.filter(x => !vistos.has(x.key) && vistos.add(x.key)).map(x => [x.key, x.regra])) + `<p class="dica">Leia estas frases de novo amanhã, antes da próxima sessão.</p></section>` : '') +
    `</div><p class="rf-fimacao"><button class="btn main" id="outra">Voltar ao início</button> <a class="btn" href="flashcards.html${cardsHoje.length ? '?lei=meus' : ''}" style="text-decoration:none;color:inherit;display:inline-block">${cardsHoje.length ? 'Estudar os cards dos meus erros' : 'Estudar flashcards'}</a></p>`;
  document.getElementById('outra').onclick = inicio;
  ligaRegras(root);
  atual = null;
}

// Fonte da Fundamentação: abre o balão com o texto dela, o trecho da questão grifado (js/texto-lei.js).
document.addEventListener('click', e => {
  const b = e.target.closest('.fd-abrir');
  if (!b || !atual || !atual.fontes) return;
  window.abrirFontes(atual.fontes, +b.dataset.fonte);
});

// Card montado (js/meus-cards.js), no fim da sessão: a frente à vista, a resposta só ao abrir (recuperação ativa).
function cardHTML(c) {
  // Card com mapa (js/meus-cards.js): a afirmação em que errou à vista; o mapa do erro ao abrir.
  if (c.mapa) return `<details class="mc"><summary><span class="mc-tipo">${esc(c.secao)}</span>${esc(c.frente)}` +
    `${c.ctx ? `<span class="mc-ctx">Na questão: ${esc(c.ctx)}</span>` : ''}${MeusCards.frenteHTML(c)}</summary>${MeusCards.mapaHTML(Object.assign({}, c, { enunciado: '' }))}</details>`;
  const ab = c.contraste ? `<ol class="mc-ab">${c.contraste.map((t, i) => `<li><b>${'AB'[i]}</b> ${esc(t)}</li>`).join('')}</ol>`
    : c.julgue ? `<ul class="mc-ab">${c.julgue.map(x => `<li>${esc(x.t)}</li>`).join('')}</ul>` : '';
  return `<details class="mc"><summary><span class="mc-tipo">${esc(c.secao)}</span>${esc(c.frente)}</summary>${ab}` +
    `<p class="mc-v"><b>Resposta:</b> ${esc(c.verso)}</p>${c.armadilha ? `<p class="mc-v"><b>Seu erro:</b> ${esc(c.armadilha)}</p>` : ''}</details>`;
}

// Erros diagnosticados antes de existir o baralho "Erros" também viram card (com a alternativa marcada naquela
// vez, ou a da prova original), e cards de versão antiga são refeitos no formato atual (o progresso fica).
if (window.MeusCards) {
  const feitos = MeusCards.ler();
  for (const k of Object.keys(S)) {
    if (feitos['meu-' + k] && feitos['meu-' + k].v === MeusCards.V) continue;   // versão antiga: refaz no formato novo
    const x = [...S[k].hist].reverse().find(x => !x.ok && MeusCards.CAUSAS.includes(x.causa)), o = x && info(k);
    if (!o) continue;
    const card = MeusCards.guardar(MeusCards.montar({ key: k, causa: x.causa, o, sel: x.sel !== undefined ? x.sel : o.marcouAntes, regra: S[k].regra }));
    if (card) MeusCards.completarLei(card, o).then(c => { if (c) MeusCards.guardar(c); }).catch(() => {});
  }
}

// Conferência dos cards de "Erros" (refazer.html?conferir-cards): monta, sem gravar, o card de cada questão dos
// simulados e do caderno (com a primeira alternativa errada) e lista no console os que ficariam vagos: afirmação
// genérica ("V − V − V − V"), erro sem conteúdo, sem enunciado ou sem lei (só nas matérias de lei). window.__cards
// guarda tudo para inspeção.
if (window.MeusCards && /[?&]conferir-cards\b/.test(location.search)) {
  (async () => {
    const LEI_AREA = /CTN|CF|LRF|Lei|ISS|LC|LGPD|Licita|Constitui|Tribut|Administrativo/i;
    const chaves = [...porId.keys()].filter(id => sim.q[id]).map(id => 's:' + id).concat([...cadPorN.keys()].map(n => 'c:' + n));
    const out = [];
    for (const k of chaves) {
      const o = info(k);
      if (!o) continue;
      const sel = o.opts.findIndex((_, i) => i !== o.certa);
      const c = MeusCards.montar({ key: k, causa: 'confundi', o, sel, regra: '' });
      if (!c) continue;
      await MeusCards.completarLei(c, o);
      const r0 = c.mapa[0], deLei = LEI_AREA.test(o.norma + ' ' + o.area);
      out.push({ k, norma: o.norma, generica: r0.rotulo === 'Você marcou' && /^\s*[VF]\s*\W|assertiva|^(?:I|II|III|IV)\b/i.test(r0.texto),
        // vago: o ramo não mostra o erro concreto (nem a troca afirmação × lei, nem nota de 30+ letras, nem regra)
        vago: c.mapa.some(r => !(r.lei && MeusCards.comparar(r.texto, r.lei.linhas)) && limpaTxt(r.erro).length < 30 && !r.regra && !r.lei),
        semEnun: !c.enunciado, semLei: deLei && c.mapa.every(r => !r.lei) && !c.trecho.length && c.mapa.every(r => !r.regra), c });
    }
    window.__cards = out;
    const conta = f => out.filter(f).length;
    console.log(`conferir-cards: ${out.length} cards · genérica ${conta(x => x.generica)} · erro vago ${conta(x => x.vago)} · sem enunciado ${conta(x => x.semEnun)} · sem lei (matérias de lei) ${conta(x => x.semLei)}`);
  })();
}
function limpaTxt(t) { return String(t || '').replace(/\s+/g, ' ').trim(); }

inicio();
})().catch(e => { console.error('refazer.js:', e); });
