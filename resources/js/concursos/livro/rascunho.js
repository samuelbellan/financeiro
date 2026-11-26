// Bloco de rascunho à mão: área para escrever/desenhar com a caneta (esquema, mapa mental, conta)
// dentro do capítulo. Os traços ficam no próprio documento (atributo "tracos"), em coordenadas com
// largura fixa de 1000, então o desenho escala com a tela e não se desalinha do texto.
// Formato validado de novo no servidor (Documento::limparRascunho): manter igual.
import { Node } from '@tiptap/core';
import { getStroke } from 'perfect-freehand';
import { el } from './util.js';
import { segurarCaneta } from './caneta.js';

export const LARGURA = 1000;
export const TINTAS = {
    preto: { nome: 'Preto', tam: 4 },
    azul: { nome: 'Azul', tam: 4 },
    vermelho: { nome: 'Vermelho', tam: 4 },
    verde: { nome: 'Verde', tam: 4 },
    marca: { nome: 'Marca-texto', tam: 22 },
};
const ALTURA_PADRAO = 360;

// Caneta e mouse desenham na folha; o dedo rola a página.
segurarCaneta(e => !!e.target?.closest?.('.rascunho-folha'));
const ALTURA_MAX = 6000;
const RAIO_BORRACHA = 12;

/** Contorno do traço (perfect-freehand) como "d" de um path SVG. */
function caminho(traco, final = true) {
    const pts = [];
    for (let i = 0; i < traco.p.length; i += 3) pts.push([traco.p[i], traco.p[i + 1], traco.p[i + 2]]);
    const tinta = TINTAS[traco.c] ?? TINTAS.preto;
    const contorno = getStroke(pts, {
        size: tinta.tam,
        thinning: traco.c === 'marca' ? 0 : 0.55,
        smoothing: 0.5,
        streamline: 0.45,
        simulatePressure: !!traco.s,
        last: final,
    });
    if (!contorno.length) return '';
    const d = contorno.reduce((acc, [x0, y0], i, arr) => {
        const [x1, y1] = arr[(i + 1) % arr.length];
        acc.push(x0.toFixed(1), y0.toFixed(1), ((x0 + x1) / 2).toFixed(1), ((y0 + y1) / 2).toFixed(1));
        return acc;
    }, ['M', ...contorno[0].map(v => v.toFixed(1)), 'Q']);
    return d.join(' ') + ' Z';
}

const svgEl = (tag, attrs = {}) => {
    const n = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
    return n;
};

export const Rascunho = Node.create({
    name: 'rascunho',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: false,

    addAttributes() {
        return {
            altura: { default: ALTURA_PADRAO, parseHTML: e => Number(e.getAttribute('data-altura')) || ALTURA_PADRAO },
            tracos: {
                default: [],
                parseHTML: e => { try { return JSON.parse(e.getAttribute('data-tracos') || '[]'); } catch (err) { return []; } },
            },
        };
    },
    parseHTML() {
        return [{ tag: 'div[data-rascunho]' }];
    },
    renderHTML({ node }) {
        return ['div', { 'data-rascunho': '', 'data-altura': node.attrs.altura, 'data-tracos': JSON.stringify(node.attrs.tracos), class: 'rascunho' }];
    },

    addCommands() {
        return {
            inserirRascunho: () => ({ commands }) => commands.insertContent({ type: this.name }),
        };
    },

    addNodeView() {
        return ({ node: inicial, getPos, editor }) => {
            let node = inicial;
            let tinta = 'preto';
            let borracha = false;

            const dom = el('div', { class: 'rascunho', contenteditable: 'false' });
            const barra = el('div', { class: 'rascunho-barra' });
            const svg = svgEl('svg', { class: 'rascunho-folha', role: 'img', 'aria-label': 'Rascunho à mão' });
            const gTracos = svgEl('g');
            const vivo = svgEl('path');
            svg.append(gTracos, vivo);
            dom.append(barra, svg);

            // ---- barra ----
            const botao = (rotulo, titulo, fn) => {
                const b = el('button', { type: 'button', title: titulo, 'aria-label': titulo }, rotulo);
                b.addEventListener('click', fn);
                return b;
            };
            const botoesTinta = Object.entries(TINTAS).map(([k, t]) => [k, botao(el('span', { class: `tinta ${k}` }), t.nome, () => { tinta = k; borracha = false; pintarBarra(); })]);
            const bBorracha = botao('⌫', 'Borracha (o botão lateral da caneta também apaga)', () => { borracha = !borracha; pintarBarra(); });
            const bDesfazer = botao('↶', 'Desfazer o último traço', () => {
                if (!node.attrs.tracos.length) return;
                gravar({ tracos: node.attrs.tracos.slice(0, -1) });
            });
            const bMais = botao('+ espaço', 'Aumentar a área', () => gravar({ altura: Math.min(ALTURA_MAX, node.attrs.altura + 240) }));
            const bMenos = botao('− espaço', 'Diminuir a área (até o último traço)', () => {
                const fundo = node.attrs.tracos.reduce((m, t) => { for (let i = 1; i < t.p.length; i += 3) m = Math.max(m, t.p[i]); return m; }, 0);
                gravar({ altura: Math.max(160, Math.ceil(fundo + 30), node.attrs.altura - 240) });
            });
            const bLimpar = botao('Limpar', 'Apagar todos os traços', () => {
                if (node.attrs.tracos.length && confirm('Apagar todo o rascunho?')) gravar({ tracos: [] });
            });
            const bRemover = botao('✕', 'Tirar o bloco de rascunho do texto', () => {
                if (node.attrs.tracos.length && !confirm('Tirar este rascunho do capítulo?')) return;
                const pos = getPos();
                if (typeof pos === 'number') editor.view.dispatch(editor.state.tr.delete(pos, pos + node.nodeSize));
            });
            bRemover.classList.add('so-editando');
            barra.append(el('span', { class: 'rascunho-rotulo' }, '✍ Rascunho'), ...botoesTinta.map(([, b]) => b), bBorracha, bDesfazer, el('span', { class: 'esp' }), bMenos, bMais, bLimpar, bRemover);
            function pintarBarra() {
                botoesTinta.forEach(([k, b]) => b.setAttribute('aria-pressed', String(!borracha && k === tinta)));
                bBorracha.setAttribute('aria-pressed', String(borracha));
            }
            pintarBarra();

            // ---- desenho ----
            function desenhar() {
                svg.setAttribute('viewBox', `0 0 ${LARGURA} ${node.attrs.altura}`);
                svg.style.aspectRatio = `${LARGURA} / ${node.attrs.altura}`;
                gTracos.replaceChildren(...node.attrs.tracos.map((t, i) => svgEl('path', { d: caminho(t), class: `tinta ${t.c}`, 'data-i': i })));
                bDesfazer.disabled = !node.attrs.tracos.length;
            }
            desenhar();

            function gravar(attrs) {
                const pos = getPos();
                if (typeof pos !== 'number') return;
                const tr = editor.state.tr.setNodeMarkup(pos, undefined, { ...node.attrs, ...attrs });
                editor.view.dispatch(tr);
            }

            const ponto = e => {
                const r = svg.getBoundingClientRect();
                const k = LARGURA / r.width;
                return [Math.round((e.clientX - r.left) * k * 10) / 10, Math.round((e.clientY - r.top) * k * 10) / 10];
            };


            let atual = null; // traço em andamento ou { apagando: Set }
            svg.addEventListener('pointerdown', e => {
                if (e.pointerType === 'touch' || (e.pointerType === 'mouse' && e.button !== 0)) return;
                e.preventDefault();
                try { svg.setPointerCapture(e.pointerId); } catch (err) { /* ponteiro já solto */ }
                const apagar = borracha || !!(e.buttons & 34);
                if (apagar) {
                    atual = { id: e.pointerId, apagando: new Set() };
                    apagarEm(ponto(e));
                    return;
                }
                const [x, y] = ponto(e);
                const s = e.pointerType !== 'pen';
                atual = { id: e.pointerId, traco: { c: tinta, s: s ? 1 : 0, p: [x, y, s ? 0.5 : arred(e.pressure)] } };
                if (!s) delete atual.traco.s;
                vivo.setAttribute('class', `tinta ${tinta}`);
                vivo.setAttribute('d', caminho(atual.traco, false));
            });
            svg.addEventListener('pointermove', e => {
                if (!atual || e.pointerId !== atual.id) return;
                if (atual.apagando) { apagarEm(ponto(e)); return; }
                const juntos = e.getCoalescedEvents?.();
                const eventos = juntos?.length ? juntos : [e];
                for (const ev of eventos) {
                    const [x, y] = ponto(ev);
                    const p = atual.traco.p;
                    if (Math.hypot(x - p[p.length - 3], y - p[p.length - 2]) < 1) continue;
                    p.push(x, y, atual.traco.s ? 0.5 : arred(ev.pressure));
                }
                vivo.setAttribute('d', caminho(atual.traco, false));
            });
            const fim = e => {
                if (!atual || e.pointerId !== atual.id) return;
                const a = atual;
                atual = null;
                vivo.removeAttribute('d');
                if (a.apagando) {
                    if (a.apagando.size) gravar({ tracos: node.attrs.tracos.filter((_, i) => !a.apagando.has(i)) });
                    return;
                }
                if (e.type === 'pointercancel' && a.traco.p.length < 6) return;
                const fundo = Math.max(...a.traco.p.filter((_, i) => i % 3 === 1));
                const altura = fundo > node.attrs.altura - 60 ? Math.min(ALTURA_MAX, node.attrs.altura + 240) : node.attrs.altura;
                gravar({ tracos: [...node.attrs.tracos, a.traco], altura });
            };
            svg.addEventListener('pointerup', fim);
            svg.addEventListener('pointercancel', fim);

            function apagarEm([x, y]) {
                node.attrs.tracos.forEach((t, i) => {
                    if (atual.apagando.has(i)) return;
                    for (let j = 0; j < t.p.length; j += 3) {
                        if (Math.hypot(t.p[j] - x, t.p[j + 1] - y) <= RAIO_BORRACHA + (TINTAS[t.c]?.tam ?? 4) / 2) {
                            atual.apagando.add(i);
                            gTracos.querySelector(`[data-i="${i}"]`)?.classList.add('apagando');
                            return;
                        }
                    }
                });
            }

            return {
                dom,
                update: n => {
                    if (n.type.name !== 'rascunho') return false;
                    node = n;
                    desenhar();
                    return true;
                },
                stopEvent: () => true,
                ignoreMutation: () => true,
            };
        };
    },
});

const arred = v => Math.round((v || 0.5) * 100) / 100;
