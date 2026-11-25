// Camada de leitura do capítulo: só enfeita a tela (decorações do ProseMirror), nunca muda o texto
// salvo. Classifica citações em blocos (pegadinha, jurisprudência, lei seca, doutrina, definição),
// liga "art. 5º, LXIX, da CF" à aba Leis, destaca autores, numera as seções e mostra, por seção,
// quantas questões erradas caem ali (mapa de calor na margem + gaveta com as questões).
import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';
import { estado } from './util.js';

export const MARCADOR = 'Base teórica: ';

export const TIPOS = {
    pegadinha: '⚠️ Pegadinha de banca',
    jurisprudencia: '⚖️ Jurisprudência',
    leiseca: '📜 Lei seca',
    doutrina: '🎓 Doutrina',
    definicao: '📘 Definição',
    nota: '💡 Observação',
};

const AUTORES = [
    'José Afonso da Silva', 'Paulo Bonavides', 'Bonavides', 'Celso Antônio Bandeira de Mello', 'Bandeira de Mello', 'Hely Lopes Meirelles',
    'Maria Sylvia Zanella Di Pietro', 'Di Pietro', 'José dos Santos Carvalho Filho', 'Carvalho Filho', 'Marçal Justen Filho', 'Pontes de Miranda',
    'Hans Kelsen', 'Kelsen', 'Ferdinand Lassalle', 'Lassalle', 'Carl Schmitt', 'Canotilho', 'Alexandre de Moraes', 'Gilmar Mendes', 'Pedro Lenza',
    'Konrad Hesse', 'Robert Alexy', 'Ronald Dworkin', 'Norberto Bobbio', 'Miguel Reale', 'Flávio Tartuce', 'Carlos Roberto Gonçalves',
    'Maria Helena Diniz', 'Caio Mário', 'Orlando Gomes', 'Cezar Roberto Bitencourt', 'Bitencourt', 'Rogério Greco', 'Guilherme de Souza Nucci', 'Nucci',
    'Nelson Hungria', 'Damásio', 'Claus Roxin', 'Roxin', 'Welzel', 'Fábio Ulhoa Coelho', 'Ulhoa Coelho', 'Rubens Requião', 'Vivante', 'Asquini',
    'Sacha Calmon', 'Ricardo Alexandre', 'Paulo de Barros Carvalho', 'Hugo de Brito Machado', 'Geraldo Ataliba', 'Aliomar Baleeiro',
    'Luciano Amaro', 'Roque Carrazza', 'Leandro Paulsen', 'Rui Barbosa', 'Montesquieu', 'Sieyès', 'Peter Häberle', 'Häberle',
];
const RE_AUTOR = new RegExp(`(?<![\\p{L}])(${AUTORES.sort((a, b) => b.length - a.length).map(a => a.replace(/\./g, '\\.')).join('|')})(?![\\p{L}])`, 'gu');

// Lei citada por nome no texto → sigla do acervo (config/concursos.php e siglas dinâmicas).
const LEIS = [
    [/^(?:CF(?:\/88)?|Constitui[çc][ãa]o(?: Federal)?|Carta Magna)\b/i, 'cf'],
    [/^ADCT\b/, 'adct'],
    [/^CTN\b|^C[óo]digo Tribut[áa]rio/i, 'ctn'],
    [/^(?:CC(?:\/02)?|C[óo]digo Civil)\b/, 'lei10406'],
    [/^(?:CP|C[óo]digo Penal)\b/, 'del2848'],
    [/^(?:CPC|C[óo]digo de Processo Civil)\b/, 'lei13105'],
    [/^(?:CPP|C[óo]digo de Processo Penal)\b/, 'del3689'],
    [/^CLT\b/, 'del5452'],
    [/^CDC\b|^C[óo]digo de Defesa do Consumidor/i, 'lei8078'],
    [/^(?:Lei Complementar|LC)\s*(?:n[º°.]?\s*)?(\d{1,3}(?:\.\d{3})*)/i, m => `lcp${m[1].replace(/\./g, '')}`],
    [/^Lei\s*(?:n[º°.]?\s*)?(\d{1,2}\.?\d{3})(?:\/\d{2,4})?/i, m => `lei${m[1].replace(/\./g, '')}`],
    [/^Decreto-Lei\s*(?:n[º°.]?\s*)?(\d{1,2}\.?\d{3})/i, m => `del${m[1].replace(/\./g, '')}`],
];
const PADRAO_LEI = /\b(?:CF(?:\/88)?|Constitui[çc][ãa]o(?: Federal)?|ADCT|CTN|CC(?:\/02)?|C[óo]digo (?:Civil|Penal|Tribut[áa]rio(?: Nacional)?|de Processo (?:Civil|Penal)|de Defesa do Consumidor)|CPC|CPP|CP|CLT|CDC|Lei Complementar\s*(?:n[º°.]?\s*)?\d[\d.]*|LC\s*\d[\d.]*|Decreto-Lei\s*(?:n[º°.]?\s*)?\d[\d.]*|Lei\s*(?:n[º°.]?\s*)?\d{1,2}\.?\d{3}(?:\/\d{2,4})?)/g;
const PADRAO_ART = /\b(?:[Aa]rt(?:igo)?s?\.?)\s*(\d{1,4})\s*(?:[º°o](?![\p{L}]))?(?:-([A-Z])\b)?/gu;
const TEM_ART = /\b[Aa]rt(?:igo)?s?\.?\s*\d/u;
const PADRAO_SUMULA = /\b(S[úu]mula(?:\s+Vinculante)?|SV)\s*(?:n[º°.]?\s*)?(\d{1,4})(?:\s*(?:do|da)?\s*(STF|STJ))?/g;

/** Lei padrão do livro quando o texto diz só "art. 5º" (Constitucional → CF etc.). */
const LEI_DO_LIVRO = { dcon: 'cf', dtrib: 'ctn', dciv: 'lei10406', dpen: 'del2848' };

function siglaDe(nome) {
    for (const [re, s] of LEIS) {
        const m = nome.match(re);
        if (m) return typeof s === 'function' ? s(m) : s;
    }
    return null;
}

export function classificar(texto) {
    const t = texto.trim().replace(/^[“"']/, '').slice(0, 120);
    const b = t.toLowerCase();
    if (/^(ponto de aten|aten[çc][ãa]o|importante|pegadinha|cuidado|armadilha|n[ãa]o confunda|aten[çc][ãa]o cr[íi]tica|erro comum)/.test(b)) return 'pegadinha';
    if (/^(jurisprud|s[úu]mula|sv\b|stf|stj|tema\s*\d|informativo|entendimento d[oa] (stf|stj|tribuna))/.test(b) || /\b(STF|STJ)\b/.test(t.slice(0, 50))) return 'jurisprudencia';
    if (/^(art\.?\s*\d|artigo\s*\d|§|par[áa]grafo [úu]nico|(cf|ctn|cc|cp|adct|cpc|lei|lc)\b[\s,\d/]|\([a-z]\))/.test(b) || /^[IVXLC]+(\s*[-–.)]\s|\s*$)/.test(t)) return 'leiseca';
    if (/^(conceito|defini[çc][ãa]o)/.test(b)) return 'definicao';
    if (/^(doutrina|segundo|para (o|a) (professor|doutrina|autor)|conforme)/.test(b) || RE_AUTOR.test(t)) { RE_AUTOR.lastIndex = 0; return 'doutrina'; }
    RE_AUTOR.lastIndex = 0;
    return 'nota';
}

const normal = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();

/** Questões citadas na nota de um grifo ("✔ Q123 (…)"). */
function questoesDaNota(nota) {
    return [...new Set([...(nota ?? '').matchAll(/(?:^|\n)[✔✘•] Q(\d+)/gu)].map(m => m[1]))];
}

/** Análise do documento: decorações + resumo por seção (para gaveta e sumário "Nesta página"). */
export function analisar(doc) {
    const decos = [];
    const secoes = []; // {id, titulo, nivel, pos, questoes:Set, marcas:Set}
    const porMarca = new Map((estado.anotacoes ?? []).map(a => [a.marca, a]));
    const tituloCap = normal(estado.titulo ?? '');
    const leiPadrao = LEI_DO_LIVRO[estado.livro?.slug] ?? null;
    let viuMarcador = false;
    let repetidoFeito = false;
    let atual = null;
    const contagem = [0, 0, 0];

    doc.descendants((node, pos, parent) => {
        const nome = node.type.name;
        if (nome === 'heading') {
            const nivel = node.attrs.level;
            const texto = node.textContent.trim();
            if (!repetidoFeito && viuMarcador && normal(texto) === tituloCap) {
                repetidoFeito = true;
                if (node.attrs.level === 2) contagem[0]++; // conta como a seção 1, só não aparece
                decos.push(Decoration.node(pos, pos + node.nodeSize, { class: 'titulo-repetido' }));
                return false;
            }
            repetidoFeito = repetidoFeito || viuMarcador;
            const i = nivel - 2;
            contagem[i]++;
            for (let k = i + 1; k < contagem.length; k++) contagem[k] = 0;
            const num = contagem.slice(0, i + 1).map(n => n || 1).join('.');
            const id = `s-${secoes.length + 1}`;
            atual = { id, titulo: texto, nivel, pos, fim: pos + node.nodeSize, num, questoes: new Set(), marcas: new Set() };
            secoes.push(atual);
            decos.push(Decoration.node(pos, pos + node.nodeSize, { id, 'data-num': num, class: 'sec' }));
            return false;
        }
        if (nome === 'paragraph' && parent?.type.name === 'doc' && node.textContent.startsWith(MARCADOR)) {
            viuMarcador = true;
            decos.push(Decoration.node(pos, pos + node.nodeSize, { class: 'chip-base', 'data-fonte': node.textContent.slice(MARCADOR.length).replace(/^.*?—\s*/u, '') }));
            return false;
        }
        if (nome === 'blockquote') {
            const tipo = classificar(node.textContent);
            decos.push(Decoration.node(pos, pos + node.nodeSize, { class: `callout ${tipo}`, 'data-rotulo': TIPOS[tipo] }));
        }
        if (nome === 'listItem' && /^(conceito|defini[çc][ãa]o)\s*:/i.test(node.textContent.trim())) {
            decos.push(Decoration.node(pos, pos + node.nodeSize, { class: 'li-def' }));
        }
        if ((nome === 'paragraph' || nome === 'tableCell') && node.textContent) {
            // Mapa de calor: quantas questões erradas caem neste parágrafo.
            const qs = new Set();
            node.descendants(filho => {
                const g = filho.marks?.find(m => m.type.name === 'grifo' && m.attrs.id);
                if (!g) return;
                const a = porMarca.get(g.attrs.id);
                questoesDaNota(a?.nota).forEach(q => qs.add(q));
                if (atual) { atual.marcas.add(g.attrs.id); questoesDaNota(a?.nota).forEach(q => atual.questoes.add(q)); }
            });
            if (qs.size) decos.push(Decoration.node(pos, pos + node.nodeSize, { class: 'quente', 'data-calor': String(Math.min(qs.size, 3)), title: `${qs.size} ${qs.size === 1 ? 'questão errada cai' : 'questões erradas caem'} aqui` }));
        }
        if (node.isText) {
            textoInline(node.text, pos, decos, leiPadrao);
        }
        return true;
    });

    // Badge "✗ N erros" ao lado de cada seção com questões (soma as subseções no título de cima).
    for (let i = 0; i < secoes.length; i++) {
        const s = secoes[i];
        const total = new Set(s.questoes);
        const marcas = new Set(s.marcas);
        for (let j = i + 1; j < secoes.length && secoes[j].nivel > s.nivel; j++) {
            secoes[j].questoes.forEach(q => total.add(q));
            secoes[j].marcas.forEach(m => marcas.add(m));
        }
        s.total = total;
        s.todasMarcas = marcas;
        if (!total.size) continue;
        decos.push(Decoration.widget(s.fim - 1, () => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'badge-erros';
            b.contentEditable = 'false';
            b.dataset.secao = s.id;
            b.textContent = `✗ ${total.size} ${total.size === 1 ? 'erro' : 'erros'}`;
            b.title = 'Ver as questões erradas desta seção';
            return b;
        }, { side: 1, key: `badge-${s.id}-${total.size}`, ignoreSelection: true }));
    }

    return { decos: DecorationSet.create(doc, decos), secoes };
}

function textoInline(texto, pos, decos, leiPadrao) {
    for (const m of texto.matchAll(RE_AUTOR)) {
        decos.push(Decoration.inline(pos + m.index, pos + m.index + m[0].length, { class: 'autor' }));
    }
    // Súmulas: "Súmula Vinculante 13", "Súmula 473 do STF".
    for (const m of texto.matchAll(PADRAO_SUMULA)) {
        const vinc = /vinculante|^SV$/i.test(m[1]);
        const trib = (m[3] ?? '').toUpperCase();
        const ref = vinc ? `sv ${m[2]}` : trib ? `sum${trib.toLowerCase()} ${m[2]}` : null;
        if (ref) decos.push(Decoration.inline(pos + m.index, pos + m.index + m[0].length, { class: 'lei-auto', 'data-ref': ref }));
    }
    // Artigos: a lei vem logo depois ("art. 5º, LXIX, da CF"), logo antes ("CF, art. 5º") ou é a do livro.
    const leis = [...texto.matchAll(PADRAO_LEI)].map(m => ({ i: m.index, f: m.index + m[0].length, sigla: siglaDe(m[0]) })).filter(l => l.sigla);
    for (const m of texto.matchAll(PADRAO_ART)) {
        const ini = m.index;
        const fim = ini + m[0].length;
        const depois = leis.find(l => l.i >= fim && l.i - fim <= 45 && !/[.;:!?]/.test(texto.slice(fim, l.i)) && !TEM_ART.test(texto.slice(fim, l.i)));
        const antes = [...leis].reverse().find(l => l.f <= ini && ini - l.f <= 4);
        const sigla = (depois ?? antes)?.sigla ?? leiPadrao;
        if (!sigla) continue;
        const ref = `${sigla} ${m[1]}${m[2] ? `-${m[2]}` : ''}`;
        decos.push(Decoration.inline(pos + ini, pos + fim, { class: 'lei-auto', 'data-ref': ref, title: 'Ver o artigo' }));
    }
}

export const chaveLeitura = new PluginKey('leitura');

/** Extensão TipTap: recalcula as decorações só quando o texto muda. */
export const Leitura = Extension.create({
    name: 'leitura',
    addProseMirrorPlugins() {
        return [new Plugin({
            key: chaveLeitura,
            state: {
                init: (_, { doc }) => analisar(doc),
                apply: (tr, valor) => (tr.docChanged || tr.getMeta(chaveLeitura) ? analisar(tr.doc) : valor),
            },
            props: { decorations: st => chaveLeitura.getState(st).decos },
        })];
    },
});
