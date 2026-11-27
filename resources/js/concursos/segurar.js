// Usado pelo livro (caneta.js, rascunho.js) e pelas notas com caneta (nota-ponto.js).
import { el } from './livro/util.js';

// ---------------------------------------------------------------------------------------------
// Segurar a página enquanto a caneta encosta. No Chrome do Android a caneta gera pointer events e
// também touch events; só cancelar o touchstart/touchmove impede a rolagem (touch-action não separa
// caneta de dedo). O pointerdown chega antes do touchstart, então é nele que se decide se este toque
// é "da caneta no texto". Durante o traço também ficam bloqueados toque longo (menu, seleção, arrastar).
const pedidos = [];
let segurando = false;
let instalado = false;

export function segurarCaneta(quer) {
    pedidos.push(quer);
    if (instalado) return;
    instalado = true;
    const opc = { capture: true, passive: false };
    const ehStylus = e => [...(e.changedTouches ?? [])].some(t => t.touchType === 'stylus');
    document.addEventListener('pointerdown', e => {
        if (e.pointerType === 'pen') segurando = pedidos.some(f => f(e));
        diag(e);
    }, opc);
    const soltar = e => { if (e.pointerType === 'pen') segurando = false; diag(e); };
    document.addEventListener('pointerup', soltar, opc);
    document.addEventListener('pointercancel', soltar, opc);
    document.addEventListener('pointermove', diag, opc);
    for (const tipo of ['touchstart', 'touchmove']) {
        document.addEventListener(tipo, e => {
            if (segurando || (tipo === 'touchstart' && ehStylus(e) && pedidos.some(f => f(e)))) {
                if (e.cancelable) e.preventDefault();
            }
            diag(e);
        }, opc);
    }
    for (const tipo of ['contextmenu', 'selectstart', 'dragstart']) {
        document.addEventListener(tipo, e => { if (segurando) e.preventDefault(); diag(e); }, opc);
    }
    window.addEventListener('scroll', () => diag({ type: 'scroll' }), { passive: true });
}

// Diagnóstico no aparelho: abra o capítulo com ?caneta=debug e mande print da lista.
const painel = new URLSearchParams(location.search).get('caneta') === 'debug' ? (() => {
    const p = el('pre', { class: 'caneta-debug' });
    document.body.append(p);
    return p;
})() : null;
const linhas = [];
let ultimoMove = 0;
function diag(e) {
    if (!painel) return;
    if ((e.type === 'pointermove' || e.type === 'touchmove') && performance.now() - ultimoMove < 250) return;
    if (e.type.endsWith('move')) ultimoMove = performance.now();
    const t = e.changedTouches?.[0];
    linhas.push([
        Math.round(performance.now() % 100000), e.type, e.pointerType ?? (t ? `touch:${t.touchType ?? '?'}` : ''),
        e.pressure != null ? `p=${e.pressure.toFixed(2)}` : '', e.buttons ? `b=${e.buttons}` : '',
        e.cancelable === false ? 'NÃO-cancelável' : '', e.defaultPrevented ? 'bloq' : '', segurando ? 'seg' : '',
    ].filter(Boolean).join(' '));
    while (linhas.length > 18) linhas.shift();
    painel.textContent = linhas.join('\n');
}
