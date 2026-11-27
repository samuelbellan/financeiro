// Nós e marcas próprios do livro de resumo (TipTap). O formato salvo (JSON) é validado de novo no
// servidor (App\Services\Concursos\Livro\Documento): manter os nomes e atributos iguais aos de lá.
import { Node, Mark, InputRule, mergeAttributes } from '@tiptap/core';
import { estado, api, abrirCitacao, quebrarItens, el } from './util.js';

export const CORES = ['amarelo', 'verde', 'azul', 'rosa'];

/** Caixa de destaque: Pegadinha, Regra certa, Macete, Atenção, Exemplo, Base legal. */
export const Caixa = Node.create({
    name: 'caixa',
    group: 'block',
    content: 'block+',
    defining: true,

    addAttributes() {
        return { tipo: { default: 'atencao', parseHTML: e => e.getAttribute('data-caixa') || 'atencao' } };
    },
    parseHTML() {
        return [{ tag: 'aside[data-caixa]' }];
    },
    renderHTML({ node, HTMLAttributes }) {
        return ['aside', mergeAttributes(HTMLAttributes, { 'data-caixa': node.attrs.tipo, class: `caixa ${node.attrs.tipo}` }), 0];
    },

    addCommands() {
        return {
            inserirCaixa: tipo => ({ commands }) => commands.wrapIn(this.name, { tipo }),
        };
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const dom = el('aside', { class: `caixa ${node.attrs.tipo}`, 'data-caixa': node.attrs.tipo });
            const rotulo = el('div', { class: 'caixa-rotulo', contenteditable: 'false' });
            const sel = el('select', { class: 'caixa-tipo', 'aria-label': 'Tipo da caixa' });
            for (const [k, nome] of Object.entries(estado.caixas)) sel.append(new Option(nome, k));
            const texto = el('span');
            rotulo.append(texto, sel);
            const corpo = el('div', { class: 'caixa-corpo' });
            dom.append(rotulo, corpo);

            sel.addEventListener('change', () => {
                const pos = getPos();
                if (typeof pos === 'number') editor.chain().command(({ tr }) => { tr.setNodeAttribute(pos, 'tipo', sel.value); return true; }).run();
            });
            const pintar = n => {
                dom.className = `caixa ${n.attrs.tipo}`;
                dom.dataset.caixa = n.attrs.tipo;
                texto.textContent = estado.caixas[n.attrs.tipo] ?? 'Atenção';
                sel.value = n.attrs.tipo;
            };
            pintar(node);

            return {
                dom,
                contentDOM: corpo,
                update: n => (n.type.name === 'caixa' ? (pintar(n), true) : false),
                stopEvent: e => rotulo.contains(e.target),
                ignoreMutation: m => rotulo.contains(m.target),
            };
        };
    },
});

/**
 * Ponto de erro: título + situação (ainda erro / revisando / dominado) + "errei de novo", com o
 * conteúdo (as caixas) editável dentro. A situação vive no servidor (material_pontos).
 */
export const PontoErro = Node.create({
    name: 'pontoErro',
    group: 'block',
    content: 'block+',
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            pontoId: { default: null, parseHTML: e => Number(e.getAttribute('data-ponto')) || null },
            titulo: { default: 'Ponto que eu erro', parseHTML: e => e.getAttribute('data-titulo') || 'Ponto que eu erro' },
        };
    },
    parseHTML() {
        return [{ tag: 'section[data-ponto]' }];
    },
    renderHTML({ node, HTMLAttributes }) {
        return ['section', mergeAttributes(HTMLAttributes, { 'data-ponto': node.attrs.pontoId ?? '', 'data-titulo': node.attrs.titulo, class: 'ponto-erro' }), 0];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            let atual = node;
            const dom = el('section', { class: 'ponto-erro' });
            const cab = el('header', { contenteditable: 'false' });
            const titulo = el('input', { class: 'ponto-titulo', 'aria-label': 'Título do ponto', maxlength: '200' });
            const h = el('h3', { class: 'ponto-titulo-ler' });
            const tag = el('span', { class: 'tag' });
            const vezes = el('span', { class: 'tag vezes' });
            const acoes = el('div', { class: 'ponto-acoes' });
            const seg = el('div', { class: 'seg', role: 'group', 'aria-label': 'Situação' });
            for (const [k, nome] of Object.entries(estado.status)) {
                const b = el('button', { type: 'button', value: k }, nome);
                b.addEventListener('click', () => mudar(estado.pontos[atual.attrs.pontoId]?.urlStatus, { status: k }));
                seg.append(b);
            }
            const errei = el('button', { type: 'button', class: 'btn mini' }, 'Errei de novo');
            errei.addEventListener('click', () => mudar(estado.pontos[atual.attrs.pontoId]?.urlErrei, {}));
            acoes.append(errei, seg);
            cab.append(el('div', { class: 'ponto-linha' }, h, titulo, tag, vezes), acoes);
            const corpo = el('div', { class: 'ponto-corpo' });
            dom.append(cab, corpo);

            async function mudar(url, dados) {
                if (!url) return;
                const r = await api(url, { method: 'POST', body: dados });
                if (!r) return;
                const p = estado.pontos[atual.attrs.pontoId];
                Object.assign(p, { status: r.status, vezes: r.vezes_errado, ultimo: r.ultimo_erro_em });
                pintar(atual);
                document.dispatchEvent(new CustomEvent('livro:pontos'));
            }

            titulo.addEventListener('input', () => {
                const pos = getPos();
                if (typeof pos === 'number') editor.chain().command(({ tr }) => { tr.setNodeAttribute(pos, 'titulo', titulo.value || 'Ponto que eu erro'); return true; }).run();
            });
            titulo.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); editor.commands.focus((getPos() ?? 0) + 2); }
            });

            function pintar(n) {
                const p = estado.pontos[n.attrs.pontoId] ?? { status: 'errando', vezes: 1 };
                dom.id = n.attrs.pontoId ? `ponto-${n.attrs.pontoId}` : '';
                dom.dataset.status = p.status;
                h.textContent = n.attrs.titulo;
                if (document.activeElement !== titulo) titulo.value = n.attrs.titulo;
                tag.className = `tag ${p.status}`;
                tag.textContent = estado.status[p.status] ?? '';
                vezes.textContent = `errei ${p.vezes}×`;
                vezes.hidden = !(p.vezes > 1);
                seg.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(b.value === p.status)));
                errei.title = p.ultimo ? `Último erro em ${p.ultimo}` : '';
            }
            pintar(node);
            const ouvir = () => pintar(atual);
            document.addEventListener('livro:pontos', ouvir);

            return {
                dom,
                contentDOM: corpo,
                update: n => {
                    if (n.type.name !== 'pontoErro') return false;
                    atual = n;
                    pintar(n);
                    return true;
                },
                stopEvent: e => cab.contains(e.target),
                ignoreMutation: m => cab.contains(m.target),
                destroy: () => document.removeEventListener('livro:pontos', ouvir),
            };
        };
    },
});

/** Questão errada no meio do texto, respondível ali mesmo (usa a rota do Refazer erradas). */
export const Questao = Node.create({
    name: 'questao',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return { questaoId: { default: null, parseHTML: e => Number(e.getAttribute('data-questao')) || null } };
    },
    parseHTML() {
        return [{ tag: 'section[data-questao]' }];
    },
    renderHTML({ node }) {
        return ['section', { 'data-questao': node.attrs.questaoId, class: 'questao-livro' }];
    },

    addNodeView() {
        return ({ node }) => {
            const dom = el('section', { class: 'questao-livro', contenteditable: 'false' });
            montarQuestao(dom, node.attrs.questaoId);

            return { dom, stopEvent: e => e.target.closest?.('button, a, summary') != null, ignoreMutation: () => true };
        };
    },
});

async function montarQuestao(dom, id) {
    let q = estado.questoes[id];
    if (!q) {
        dom.textContent = 'Carregando questão…';
        const lista = await api(`${estado.urls.questoes}?ids=${id}`);
        q = lista?.[0];
        if (q) estado.questoes[id] = q;
    }
    dom.replaceChildren();
    if (!q) {
        dom.append(el('p', { class: 'muted' }, 'Questão não encontrada neste livro.'));
        return;
    }
    const meta = [q.tec ? `TEC ${q.tec}` : null, q.origem, q.antes ? `antes: ${q.antes}` : null].filter(Boolean).join(' · ');
    dom.append(el('div', { class: 'caixa-rotulo' }, 'Questão', el('span', { class: 'q-meta' }, meta)));
    dom.append(el('p', { class: 'q-enun' }, quebrarItens(q.enunciado)));
    const opts = el('div', { class: 'q-opts' });
    const ret = el('div', { class: 'q-ret', hidden: '' });
    for (const a of q.alternativas) {
        const b = el('button', { type: 'button', class: 'q-opt', 'data-letra': a.letra }, el('b', {}, a.letra), el('span', {}, quebrarItens(a.texto)));
        b.addEventListener('click', async () => {
            if (opts.dataset.respondida) return;
            opts.dataset.respondida = '1';
            const r = await api(q.url, { method: 'POST', body: { resposta: a.letra } });
            if (!r) { delete opts.dataset.respondida; return; }
            opts.querySelectorAll('.q-opt').forEach(o => {
                o.disabled = true;
                if (o.dataset.letra === r.gabarito) o.classList.add('certa');
                else if (o.dataset.letra === a.letra) o.classList.add('errada');
            });
            ret.hidden = false;
            ret.replaceChildren(el('p', { class: r.acertou ? 'ok' : 'no' }, r.acertou ? `Acertou! Gabarito: ${r.gabarito}.` : `Errou: você marcou ${a.letra}; o gabarito é ${r.gabarito}.`));
            if (r.comentario_html) {
                const corpo = el('div', { class: 'q-com' });
                corpo.innerHTML = r.comentario_html; // limpo no servidor (HtmlComentario)
                ret.append(el('details', {}, el('summary', {}, 'Comentário'), corpo));
            } else if (r.comentario) ret.append(el('details', {}, el('summary', {}, 'Comentário do caderno'), el('pre', {}, r.comentario)));
            const refazer = el('button', { type: 'button', class: 'btn mini' }, 'Responder de novo');
            refazer.addEventListener('click', () => montarQuestao(dom, id));
            ret.append(el('div', { class: 'q-acoes' }, refazer, r.tec ? el('a', { href: r.tec, target: '_blank', rel: 'noopener', class: 'btn mini' }, 'Ver no TEC') : ''));
            if (!r.acertou) document.dispatchEvent(new CustomEvent('livro:errou'));
        });
        opts.append(b);
    }
    dom.append(opts, ret);
}

/** Citação de lei ou jurisprudência em linha: [[ctn 98]] vira link que abre o artigo num diálogo. */
export const Lei = Node.create({
    name: 'lei',
    group: 'inline',
    inline: true,
    atom: true,

    addAttributes() {
        return { ref: { default: '', parseHTML: e => e.getAttribute('data-ref') || '' } };
    },
    parseHTML() {
        return [{ tag: 'span[data-ref]' }];
    },
    renderHTML({ node }) {
        return ['span', { 'data-ref': node.attrs.ref, class: 'lei' }, `[[${node.attrs.ref}]]`];
    },
    renderText({ node }) {
        return `[[${node.attrs.ref}]]`;
    },

    addInputRules() {
        return [new InputRule({
            find: /\[\[([a-z][a-z0-9]*\s+\d[^\]]*)\]\]$/,
            handler: ({ state, range, match }) => {
                state.tr.replaceWith(range.from, range.to, this.type.create({ ref: match[1].trim() }));
            },
        })];
    },

    addNodeView() {
        return ({ node, editor }) => {
            const dom = el('a', { class: 'lei', href: '#', contenteditable: 'false' });
            const pintar = async n => {
                dom.textContent = n.attrs.ref;
                const link = await resolverCitacao(n.attrs.ref);
                if (!link) { dom.classList.add('lei-sem'); dom.title = 'Citação não reconhecida'; return; }
                dom.textContent = link.rotulo;
                dom.href = link.href;
                dom.dataset.json = link.json;
                dom.classList.toggle('juris', link.juris);
                dom.title = editor.isEditable ? 'Ctrl+clique para abrir o artigo' : 'Abrir o artigo';
            };
            dom.addEventListener('click', e => {
                if (editor.isEditable && !(e.ctrlKey || e.metaKey)) { e.preventDefault(); return; }
                e.preventDefault();
                if (dom.dataset.json) abrirCitacao(dom.textContent, dom.href, dom.dataset.json);
            });
            pintar(node);

            return { dom, update: n => (n.type.name === 'lei' ? (pintar(n), true) : false), ignoreMutation: () => true };
        };
    },
});

const citacoes = new Map();
function resolverCitacao(ref) {
    if (!citacoes.has(ref)) citacoes.set(ref, api(`${estado.urls.citacao}?ref=${encodeURIComponent(ref)}`, { silencioso: true }));
    return citacoes.get(ref);
}

/** Grifo (marca-texto) em 4 cores; com id, liga a uma nota na margem. */
export const Grifo = Mark.create({
    name: 'grifo',
    inclusive: false,
    excludes: 'grifo',

    addAttributes() {
        return {
            cor: { default: 'amarelo', parseHTML: e => CORES.find(c => e.classList.contains(c)) ?? 'amarelo' },
            id: { default: null, parseHTML: e => e.getAttribute('data-nota') || null },
        };
    },
    parseHTML() {
        return [{ tag: 'mark.grifo' }];
    },
    renderHTML({ mark }) {
        return ['mark', { class: `grifo ${mark.attrs.cor}`, ...(mark.attrs.id ? { 'data-nota': mark.attrs.id } : {}) }, 0];
    },
});
