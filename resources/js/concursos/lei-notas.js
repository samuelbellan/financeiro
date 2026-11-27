// Lei seca: nota num ponto do texto do artigo. Botão direito (ou dois toques da caneta) → "Adicionar
// nota aqui"; a nota fica só como ícone no texto e abre num modal com texto e tinta (nota-ponto.js).
// Posições em caracteres (code points) do texto puro de .corpo, igual ao servidor e aos grifos.
import { abrirNota, instalarMenuNota, dicaDaNota, fimDaPalavra } from './nota-ponto.js';

const cfg = JSON.parse(document.getElementById('lei-notas-dados')?.textContent ?? '{}');
const lei = document.getElementById('lei');
const notas = new Map(); // id → { texto, tracos, altura }

const cp = s => Array.from(s).length;
const u16 = (s, pontos) => { let i = 0, n = 0; for (const ch of s) { if (n === pontos) break; i += ch.length; n++; } return i; };

async function enviar(url, corpo, method = 'POST') {
    try {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: corpo ? JSON.stringify(corpo) : undefined,
            credentials: 'same-origin',
        });
        const j = await r.json().catch(() => ({}));
        if (!r.ok) { alert(r.status === 419 ? 'Sua sessão expirou. Recarregue a página.' : (j.message || 'Não consegui salvar. Tente de novo.')); return null; }
        return j;
    } catch (e) {
        alert('Sem conexão com o servidor. Tente de novo.');
        return null;
    }
}

/** Botão da nota no ponto `posicao` do texto de .corpo (null = fim do artigo, quando o ponto sumiu). */
function porIcone(corpo, nota) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'nota-ponto';
    b.dataset.id = nota.id;
    pintar(b, nota);

    let colocado = false;
    if (nota.posicao != null) {
        const w = document.createTreeWalker(corpo, NodeFilter.SHOW_TEXT);
        let base = 0;
        for (let no = w.nextNode(); no; no = w.nextNode()) {
            const tam = cp(no.data);
            if (nota.posicao <= base + tam && no.data.trim() !== '') {
                const resto = no.splitText(u16(no.data, nota.posicao - base));
                resto.parentNode.insertBefore(b, resto);
                colocado = true;
                break;
            }
            base += tam;
        }
    }
    if (!colocado) (corpo.lastElementChild ?? corpo).append(' ', b);
    return b;
}

function pintar(b, nota) {
    b.title = dicaDaNota(nota) + (nota.posicao === null ? ' (o trecho mudou; a nota foi para o fim do artigo)' : '');
    b.setAttribute('aria-label', b.title);
    b.classList.toggle('tinta-so', !(nota.texto ?? '').trim() && (nota.tracos ?? []).length > 0);
}

function rotulo(secao) {
    return secao.dataset.rotulo || secao.id;
}

function abrirExistente(b) {
    const id = b.dataset.id;
    const secao = b.closest('section.art');
    abrirNota({
        dados: notas.get(id),
        titulo: 'Nota',
        onde: rotulo(secao),
        aoSalvar: async dados => {
            const r = await enviar(cfg.nota.replace('__N__', id), dados, 'PUT');
            if (!r) return false;
            const n = { ...notas.get(id), ...r, posicao: notas.get(id)?.posicao };
            notas.set(id, n);
            pintar(b, n);
            return true;
        },
        aoApagar: async () => {
            if (!(await enviar(cfg.nota.replace('__N__', id), null, 'DELETE'))) return false;
            const pai = b.parentNode;
            b.remove();
            pai?.normalize();
            notas.delete(id);
            return true;
        },
    });
}

function criar(secao, posicao) {
    abrirNota({
        dados: { texto: '', tracos: [] },
        titulo: 'Nova nota',
        onde: rotulo(secao),
        aoSalvar: async dados => {
            const r = await enviar(cfg.criar.replace('__A__', secao.id), { ...dados, posicao });
            if (!r) return false;
            notas.set(String(r.id), r);
            porIcone(secao.querySelector('.corpo'), r);
            return true;
        },
        aoApagar: async () => true, // nada foi criado ainda
    });
}

/** Ponto do texto sob o ponteiro: { secao, posicao } (no fim da palavra) ou null. */
function pontoEm(x, y) {
    let no, off;
    if (document.caretPositionFromPoint) {
        const c = document.caretPositionFromPoint(x, y);
        if (!c) return null;
        no = c.offsetNode; off = c.offset;
    } else {
        const r = document.caretRangeFromPoint?.(x, y);
        if (!r) return null;
        no = r.startContainer; off = r.startOffset;
    }
    const corpo = (no.nodeType === 1 ? no : no.parentElement)?.closest('.corpo');
    if (!corpo || !lei.contains(corpo)) return null;
    const r = document.createRange();
    r.setStart(corpo, 0);
    r.setEnd(no, off);
    return { secao: corpo.closest('section.art'), posicao: fimDaPalavra(corpo.textContent, cp(r.toString())) };
}

if (lei && cfg.criar) {
    // Notas já salvas.
    document.querySelectorAll('section.art[data-notas-ponto]').forEach(s => {
        const corpo = s.querySelector('.corpo');
        for (const n of JSON.parse(s.dataset.notasPonto)) {
            notas.set(String(n.id), n);
            porIcone(corpo, n);
        }
    });

    lei.addEventListener('click', e => {
        const b = e.target.closest('.nota-ponto');
        if (!b) return;
        e.preventDefault();
        e.stopPropagation(); // não abre a paleta do grifo em volta
        abrirExistente(b);
    }, true);

    instalarMenuNota(lei, ({ x, y, alvo }) => {
        if (alvo.closest('.nota-ponto, .barra, .editor, .notas, .orfao')) return [];
        const p = pontoEm(x, y);
        if (!p) return [];
        const itens = [{ icone: '📝', rotulo: 'Adicionar nota aqui', acao: () => criar(p.secao, p.posicao) }];
        const marcar = p.secao.querySelector('.js-marcar');
        if (marcar) itens.push({ icone: '🔖', rotulo: marcar.getAttribute('aria-pressed') === 'true' ? 'Desmarcar artigo' : 'Marcar artigo', acao: () => marcar.click() });
        const anotar = p.secao.querySelector('.js-anotar');
        if (anotar) itens.push({ icone: '✎', rotulo: 'Nota geral do artigo', acao: () => anotar.click() });
        return itens;
    });
}
