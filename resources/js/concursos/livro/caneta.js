// Modo caneta: com a caneta (S Pen, Apple Pencil, caneta do Windows) passar sobre o texto vira grifo.
// O dedo continua rolando a página e o mouse continua selecionando como antes. O grifo é a mesma marca
// do TipTap da bolha de seleção, então salva junto com o texto e aparece em qualquer aparelho.
import { CORES } from './extensoes.js';
import { el, guardar, ler } from './util.js';
import { segurarCaneta } from '../segurar.js';

export { segurarCaneta };

const NOMES = { amarelo: 'Amarelo', verde: 'Verde', azul: 'Azul', rosa: 'Rosa' };
const LETRA = /[\p{L}\p{N}]/u;
const MOVIMENTO_MINIMO = 6; // px: menos que isso é toque (abre link, citação etc.)

export function montarCaneta(editor, alvo, botao) {
    let ligado = ler('livro-caneta') === '1';
    let cor = CORES.includes(ler('livro-caneta-cor')) ? ler('livro-caneta-cor') : 'amarelo';
    let borracha = false;
    let traco = null; // { inicio, fim, x0, y0, moveu, apagar, id }

    // ---- paleta flutuante ---------------------------------------------------------------------
    const paleta = el('div', { class: 'caneta-paleta', role: 'toolbar', 'aria-label': 'Caneta', hidden: true });
    const botoesCor = CORES.map(c => {
        const b = el('button', { type: 'button', title: `Caneta ${NOMES[c].toLowerCase()}`, 'aria-label': `Caneta ${NOMES[c].toLowerCase()}` }, el('span', { class: `cor ${c}` }));
        b.addEventListener('click', () => { cor = c; borracha = false; guardar('livro-caneta-cor', c); pintar(); });
        return [c, b];
    });
    const bBorracha = el('button', { type: 'button', title: 'Borracha: passe a caneta sobre um grifo para tirar (o botão lateral da caneta também apaga)', 'aria-label': 'Borracha' }, '⌫');
    bBorracha.addEventListener('click', () => { borracha = !borracha; pintar(); });
    const bSair = el('button', { type: 'button', title: 'Desligar a caneta', 'aria-label': 'Desligar a caneta' }, '✕');
    bSair.addEventListener('click', () => alternar(false));
    paleta.append(...botoesCor.map(([, b]) => b), bBorracha, bSair);
    document.body.append(paleta);

    const previa = el('div', { class: 'caneta-previa', 'aria-hidden': 'true' });
    document.body.append(previa);

    function pintar() {
        paleta.hidden = !ligado;
        document.body.classList.toggle('caneta-ligada', ligado);
        botao?.setAttribute('aria-pressed', String(ligado));
        botoesCor.forEach(([c, b]) => b.setAttribute('aria-pressed', String(!borracha && c === cor)));
        bBorracha.setAttribute('aria-pressed', String(borracha));
    }

    function alternar(valor = !ligado) {
        ligado = valor;
        guardar('livro-caneta', ligado ? '1' : '0');
        pintar();
    }
    botao?.addEventListener('click', () => alternar());
    pintar();

    // ---- traço --------------------------------------------------------------------------------
    const ehCaneta = e => e.pointerType === 'pen';
    const foraDoTexto = alvoEv => alvoEv.closest?.('.rascunho, .questao-livro button, .caixa-rotulo, .ponto-erro header');

    // Enquanto a caneta encosta no texto, a página não rola (o dedo continua rolando).
    segurarCaneta(e => ligado && alvo.contains(e.target) && !foraDoTexto(e.target));

    alvo.addEventListener('pointerdown', e => {
        if (!ligado || !ehCaneta(e) || foraDoTexto(e.target)) return;
        const pos = posicao(e);
        if (pos == null) return;
        e.preventDefault();
        try { alvo.setPointerCapture(e.pointerId); } catch (err) { /* ponteiro já solto */ }
        // Botão lateral (buttons & 2) ou ponta de borracha (buttons & 32) apagam.
        traco = { inicio: pos, fim: pos, x0: e.clientX, y0: e.clientY, moveu: false, apagar: borracha || !!(e.buttons & 34), id: e.pointerId };
    });

    alvo.addEventListener('pointermove', e => {
        if (!traco || e.pointerId !== traco.id) return;
        if (!traco.moveu && Math.hypot(e.clientX - traco.x0, e.clientY - traco.y0) < MOVIMENTO_MINIMO) return;
        traco.moveu = true;
        if (e.buttons & 34) traco.apagar = true;
        const pos = posicao(e);
        if (pos != null) traco.fim = pos;
        mostrarPrevia();
    });

    const terminar = e => {
        if (!traco || e.pointerId !== traco.id) return;
        const t = traco;
        traco = null;
        previa.replaceChildren();
        // Se o navegador cancelar no meio (gesto do sistema), grifa o que já foi passado.
        if (e.type === 'pointercancel') { if (t.moveu) aplicar(t); return; }
        if (!t.moveu) {
            // Toque da caneta: funciona como clique (abrir citação, nota, link).
            document.elementFromPoint(e.clientX, e.clientY)?.click();
            return;
        }
        aplicar(t);
    };
    alvo.addEventListener('pointerup', terminar);
    alvo.addEventListener('pointercancel', terminar);

    function posicao(e) {
        return editor.view.posAtCoords({ left: e.clientX, top: e.clientY })?.pos ?? null;
    }

    /** Faixa do traço, estendida até o fim das palavras das pontas (a caneta nunca para no lugar exato). */
    function faixa(t) {
        let from = Math.min(t.inicio, t.fim);
        let to = Math.max(t.inicio, t.fim);
        const doc = editor.state.doc;
        const $a = doc.resolve(from);
        const textoA = $a.parent.textBetween(0, $a.parent.content.size, null, '￼');
        let i = $a.parentOffset;
        while (i > 0 && LETRA.test(textoA[i - 1])) { i--; from--; }
        const $b = doc.resolve(to);
        const textoB = $b.parent.textBetween(0, $b.parent.content.size, null, '￼');
        let j = $b.parentOffset;
        while (j < textoB.length && LETRA.test(textoB[j])) { j++; to++; }
        return from < to ? { from, to } : null;
    }

    function mostrarPrevia() {
        const f = faixa(traco);
        previa.replaceChildren();
        if (!f) return;
        let r;
        try {
            const a = editor.view.domAtPos(f.from);
            const b = editor.view.domAtPos(f.to);
            r = document.createRange();
            r.setStart(a.node, a.offset);
            r.setEnd(b.node, b.offset);
        } catch (err) { return; }
        previa.className = `caneta-previa ${traco.apagar ? 'apagar' : cor}`;
        for (const q of r.getClientRects()) {
            if (q.width < 1) continue;
            previa.append(el('i', { style: `left:${q.left + window.scrollX}px;top:${q.top + window.scrollY}px;width:${q.width}px;height:${q.height}px` }));
        }
    }

    function aplicar(t) {
        const f = faixa(t);
        if (!f) return;
        const tipo = editor.schema.marks.grifo;
        const tr = editor.state.tr;
        // Trechos com nota na margem ficam como estão: a caneta não apaga nem troca a cor deles
        // (sem o id do grifo a nota sumiria ao salvar). A nota tem o próprio botão Apagar.
        editor.state.doc.nodesBetween(f.from, f.to, (n, pos) => {
            if (!n.isText) return;
            if (n.marks.some(m => m.type === tipo && m.attrs.id)) return;
            const a = Math.max(pos, f.from);
            const b = Math.min(pos + n.nodeSize, f.to);
            tr.removeMark(a, b, tipo);
            if (!t.apagar) tr.addMark(a, b, tipo.create({ cor }));
        });
        if (tr.docChanged) editor.view.dispatch(tr);
    }

    return { alternar };
}

