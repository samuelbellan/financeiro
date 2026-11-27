// Nota num ponto do capítulo: nó em linha que aparece só como um ícone; o ícone abre a nota (texto +
// tinta) no modal de nota-ponto.js. A nota fica no próprio documento (salva junto com o texto).
// Formato validado de novo no servidor (Documento::limparNo, tipo notaPonto): manter igual.
import { Node } from '@tiptap/core';
import { abrirNota, dicaDaNota } from '../nota-ponto.js';
import { novoId } from './util.js';

const jsonAttr = (nome, padrao) => ({
    default: padrao,
    parseHTML: e => { try { return JSON.parse(e.getAttribute(nome) ?? 'null') ?? padrao; } catch (err) { return padrao; } },
});

export const NotaPonto = Node.create({
    name: 'notaPonto',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            id: { default: null, parseHTML: e => e.getAttribute('data-nota-ponto') || null },
            texto: { default: '', parseHTML: e => e.getAttribute('data-texto') ?? '' },
            tracos: jsonAttr('data-tracos', []),
            altura: { default: 420, parseHTML: e => Number(e.getAttribute('data-altura')) || 420 },
        };
    },
    parseHTML() {
        return [{ tag: 'button[data-nota-ponto]' }];
    },
    renderHTML({ node }) {
        return ['button', {
            type: 'button', class: 'nota-ponto', 'data-nota-ponto': node.attrs.id ?? '', 'data-texto': node.attrs.texto,
            'data-tracos': JSON.stringify(node.attrs.tracos), 'data-altura': node.attrs.altura,
        }];
    },
    renderText() {
        return '';
    },

    addCommands() {
        return {
            /** Põe uma nota nova em pos e já abre o modal; fechada vazia, ela sai do texto. */
            inserirNotaPonto: pos => ({ editor, tr, dispatch }) => {
                if (!dispatch) return true;
                const id = novoId();
                tr.insert(pos, this.type.create({ id }));
                setTimeout(() => abrirDoTexto(editor, id, true), 0);
                return true;
            },
        };
    },

    addNodeView() {
        return ({ node: inicial, editor }) => {
            let node = inicial;
            const dom = document.createElement('button');
            dom.type = 'button';
            dom.className = 'nota-ponto';
            dom.contentEditable = 'false';
            const pintar = () => {
                dom.dataset.notaPonto = node.attrs.id ?? '';
                dom.title = dicaDaNota(node.attrs);
                dom.setAttribute('aria-label', dom.title);
                dom.classList.toggle('tinta-so', !node.attrs.texto.trim() && node.attrs.tracos.length > 0);
            };
            pintar();
            dom.addEventListener('click', e => {
                e.preventDefault();
                e.stopPropagation();
                abrirDoTexto(editor, node.attrs.id);
            });
            return {
                dom,
                update: n => { if (n.type.name !== 'notaPonto') return false; node = n; pintar(); return true; },
                stopEvent: e => /^(click|mousedown|mouseup|pointerdown|pointerup|touchstart|touchend)$/.test(e.type),
                ignoreMutation: () => true,
            };
        };
    },
});

/** Acha o nó da nota pelo id (a posição muda conforme o texto é editado). */
function achar(editor, id) {
    let achado = null;
    editor.state.doc.descendants((n, pos) => {
        if (achado) return false;
        if (n.type.name === 'notaPonto' && n.attrs.id === id) achado = { n, pos };
        return true;
    });
    return achado;
}

/** Trecho em volta da nota, para o cabeçalho do modal. */
function contexto(editor, pos) {
    const $p = editor.state.doc.resolve(pos);
    const antes = $p.parent.textBetween(Math.max(0, $p.parentOffset - 60), $p.parentOffset, ' ', ' ').trim();
    return antes ? `…${antes.split(/\s+/).slice(-8).join(' ')}` : '';
}

function abrirDoTexto(editor, id, nova = false) {
    const a = achar(editor, id);
    if (!a) return;
    const gravar = attrs => {
        const b = achar(editor, id);
        if (!b) return false;
        editor.view.dispatch(editor.state.tr.setNodeMarkup(b.pos, undefined, { ...b.n.attrs, ...attrs }));
        return true;
    };
    abrirNota({
        dados: a.n.attrs,
        titulo: nova ? 'Nova nota' : 'Nota',
        onde: contexto(editor, a.pos),
        aoSalvar: async dados => gravar(dados),
        aoApagar: async () => {
            const b = achar(editor, id);
            if (b) editor.view.dispatch(editor.state.tr.delete(b.pos, b.pos + b.n.nodeSize));
            return true;
        },
    });
}
