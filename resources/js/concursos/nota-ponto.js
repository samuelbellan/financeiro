// Nota num ponto do texto (resumo do livro e lei seca): botão direito do mouse ou dois toques da
// caneta abrem um menu ali mesmo; "Adicionar nota" deixa só um ícone no texto, e o ícone abre a nota
// num modal. A nota tem texto e tinta: a caneta escreve/desenha em qualquer lugar da folha (por cima
// do texto também); o dedo rola e o teclado digita. Formato: { texto, tracos, altura } (tinta.js).
import './nota-ponto.css';
import { LARGURA, TINTAS, caminho, svgEl, arred } from './tinta.js';
import { segurarCaneta } from './segurar.js';
import { el } from './livro/util.js';

const ALTURA_MIN = 420; // em unidades da largura 1000
const ALTURA_MAX = 6000;
const RAIO_BORRACHA = 12;
const LETRA = /[\p{L}\p{N}]/u;

/** Sem a nota estar vazia (sem texto e sem traço) ela não é guardada. */
export const notaVazia = d => !(d?.texto ?? '').trim() && !(d?.tracos ?? []).length;

/** Resumo da nota para o title do ícone. */
export function dicaDaNota(d) {
    const t = (d?.texto ?? '').trim().split('\n')[0].slice(0, 120);
    const n = (d?.tracos ?? []).length;
    return t ? `Nota: ${t}` : n ? 'Nota à mão' : 'Nota';
}

/** Avança a posição até o fim da palavra (a nota não parte uma palavra ao meio). */
export function fimDaPalavra(texto, i) {
    const cs = Array.from(texto);
    while (i < cs.length && LETRA.test(cs[i])) i++;
    return i;
}

// ---- menu (botão direito / dois toques da caneta) -----------------------------------------------
let menu = null;
function fecharMenu() { if (menu) { menu.remove(); menu = null; } }

function abrirMenu(x, y, itens) {
    fecharMenu();
    if (!itens.length) return;
    menu = el('div', { class: 'np-menu', role: 'menu' });
    for (const it of itens) {
        const b = el('button', { type: 'button', role: 'menuitem' }, el('span', { class: 'np-ico', 'aria-hidden': 'true' }, it.icone ?? ''), it.rotulo);
        b.addEventListener('click', () => { fecharMenu(); it.acao(); });
        menu.append(b);
    }
    document.body.append(menu);
    const larg = menu.offsetWidth, alt = menu.offsetHeight;
    const vw = document.documentElement.clientWidth, vh = window.innerHeight;
    menu.style.left = `${Math.max(8, Math.min(x, vw - larg - 8)) + window.scrollX}px`;
    menu.style.top = `${(y + alt + 8 > vh ? Math.max(8, y - alt) : y) + window.scrollY}px`;
    menu.querySelector('button')?.focus({ preventScroll: true });
}
document.addEventListener('pointerdown', e => { if (menu && !menu.contains(e.target)) fecharMenu(); }, true);
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharMenu(); });
window.addEventListener('scroll', fecharMenu, { passive: true });
window.addEventListener('resize', fecharMenu);

/**
 * Liga o menu num contêiner. itens({ x, y, alvo }) devolve [{ icone, rotulo, acao }] (vazio = não abre).
 * Shift + botão direito continua abrindo o menu do navegador.
 */
export function instalarMenuNota(container, itens) {
    container.addEventListener('contextmenu', e => {
        if (e.shiftKey || e.target.closest('input, textarea, select, .rascunho, .np-menu')) return;
        const lista = itens({ x: e.clientX, y: e.clientY, alvo: e.target });
        if (!lista.length) return;
        e.preventDefault();
        abrirMenu(e.clientX, e.clientY, lista);
    });

    // Dois toques da caneta: dois "taps" (sem arrastar) em menos de 400 ms, perto um do outro.
    let baixo = null;
    let ultimo = null;
    container.addEventListener('pointerdown', e => {
        if (e.pointerType === 'pen') baixo = { x: e.clientX, y: e.clientY, t: e.timeStamp };
    }, true);
    container.addEventListener('pointerup', e => {
        if (e.pointerType !== 'pen' || !baixo) return;
        const tap = Math.hypot(e.clientX - baixo.x, e.clientY - baixo.y) < 10 && e.timeStamp - baixo.t < 350;
        baixo = null;
        if (!tap || e.target.closest('input, textarea, select, button, a, .rascunho, .nota-ponto')) { ultimo = null; return; }
        if (ultimo && e.timeStamp - ultimo.t < 400 && Math.hypot(e.clientX - ultimo.x, e.clientY - ultimo.y) < 28) {
            ultimo = null;
            const lista = itens({ x: e.clientX, y: e.clientY, alvo: e.target });
            if (!lista.length) return;
            // O duplo toque também seleciona a palavra: limpa depois que o navegador terminar.
            setTimeout(() => window.getSelection()?.removeAllRanges(), 0);
            setTimeout(() => abrirMenu(e.clientX, e.clientY, lista), 0);
            return;
        }
        ultimo = { x: e.clientX, y: e.clientY, t: e.timeStamp };
    }, true);
}

// ---- modal da nota -------------------------------------------------------------------------------
// Enquanto a caneta encosta na folha, a página não rola (o dedo continua rolando).
segurarCaneta(e => !!e.target?.closest?.('.np-papel'));

let dlg = null;

function montarDialogo() {
    const d = el('dialog', { class: 'np-dlg', 'aria-labelledby': 'np-titulo' });
    d.innerHTML = `
        <header>
            <div><h2 id="np-titulo">Nota</h2><small class="np-onde"></small></div>
            <button type="button" class="np-x" aria-label="Fechar" title="Fechar (salva)">✕</button>
        </header>
        <div class="np-ferr" role="toolbar" aria-label="Tinta"></div>
        <div class="np-folha">
            <div class="np-papel">
                <textarea class="np-texto" maxlength="5000" placeholder="Escreva com o teclado ou com a caneta em qualquer lugar da folha…" aria-label="Texto da nota"></textarea>
                <svg class="np-tinta" aria-hidden="true"><g></g><path class="vivo"></path></svg>
            </div>
        </div>
        <footer>
            <button type="button" class="btn np-apagar">Apagar nota</button>
            <span class="np-status" role="status"></span>
            <button type="button" class="btn main np-ok">Concluir</button>
        </footer>`;
    document.body.append(d);
    return d;
}

/**
 * Abre a nota. dados = { texto, tracos, altura }; onde = texto curto do lugar (ex.: "art. 5º").
 * aoSalvar(dados) → Promise<bool>; aoApagar() → Promise<bool>. Fechar (✕, Esc, Concluir, fora) salva.
 */
export function abrirNota({ dados, titulo = 'Nota', onde = '', aoSalvar, aoApagar }) {
    dlg ??= montarDialogo();
    const d = dlg;
    const area = d.querySelector('.np-texto');
    const papel = d.querySelector('.np-papel');
    const svg = d.querySelector('.np-tinta');
    const gTracos = svg.querySelector('g');
    const vivo = svg.querySelector('.vivo');
    const ferr = d.querySelector('.np-ferr');
    const status = d.querySelector('.np-status');

    const nota = { texto: dados?.texto ?? '', tracos: structuredClone(dados?.tracos ?? []), altura: Math.max(ALTURA_MIN, dados?.altura ?? ALTURA_MIN) };
    const original = JSON.stringify({ texto: nota.texto, tracos: nota.tracos });
    let tinta = 'preto';
    let borracha = false;
    let mouseDesenha = false;
    let fechando = false;

    d.querySelector('#np-titulo').textContent = titulo;
    d.querySelector('.np-onde').textContent = onde;
    area.value = nota.texto;
    status.textContent = '';

    // ---- ferramentas ----
    ferr.replaceChildren();
    const botao = (rotulo, dica, fn) => {
        const b = el('button', { type: 'button', title: dica, 'aria-label': dica }, rotulo);
        b.addEventListener('click', fn);
        ferr.append(b);
        return b;
    };
    const bTintas = Object.entries(TINTAS).map(([k, t]) => [k, botao(el('span', { class: `np-cor ${k}` }), t.nome, () => { tinta = k; borracha = false; pintar(); })]);
    const bBorracha = botao('⌫', 'Borracha (o botão lateral da caneta também apaga)', () => { borracha = !borracha; pintar(); });
    const bDesfazer = botao('↶', 'Desfazer o último traço', () => { nota.tracos.pop(); desenhar(); });
    ferr.append(el('span', { class: 'np-esp' }));
    const bMouse = botao('✍ Mouse desenha', 'Desenhar também com o mouse ou o dedo (a caneta sempre desenha)', () => { mouseDesenha = !mouseDesenha; pintar(); });
    const bMais = botao('+ folha', 'Aumentar a folha', () => { nota.altura = Math.min(ALTURA_MAX, nota.altura + 300); ajustar(); });
    function pintar() {
        bTintas.forEach(([k, b]) => b.setAttribute('aria-pressed', String(!borracha && k === tinta)));
        bBorracha.setAttribute('aria-pressed', String(borracha));
        bMouse.setAttribute('aria-pressed', String(mouseDesenha));
        papel.classList.toggle('desenhando', mouseDesenha);
    }
    pintar();

    // ---- tamanho: a folha é a maior entre o texto, o último traço e a altura mínima ----
    const escala = () => LARGURA / (papel.clientWidth || LARGURA);
    function ajustar() {
        const k = escala();
        area.style.height = 'auto';
        const fundoTinta = nota.tracos.reduce((m, t) => { for (let i = 1; i < t.p.length; i += 3) m = Math.max(m, t.p[i]); return m; }, 0);
        const alturaUn = Math.max(nota.altura, Math.ceil(area.scrollHeight * k) + 40, Math.ceil(fundoTinta) + 80);
        const px = alturaUn / k;
        papel.style.height = `${px}px`;
        area.style.height = `${px}px`;
        svg.setAttribute('viewBox', `0 0 ${LARGURA} ${alturaUn}`);
        nota.altura = Math.min(ALTURA_MAX, Math.max(nota.altura, Math.ceil(fundoTinta) + 80));
    }
    function desenhar() {
        gTracos.replaceChildren(...nota.tracos.map((t, i) => svgEl('path', { d: caminho(t), class: `tinta ${t.c}`, 'data-i': i })));
        bDesfazer.disabled = !nota.tracos.length;
        ajustar();
    }

    // ---- traço ----
    const ponto = e => {
        const r = papel.getBoundingClientRect();
        const k = LARGURA / r.width;
        return [Math.round((e.clientX - r.left) * k * 10) / 10, Math.round((e.clientY - r.top) * k * 10) / 10];
    };
    let atual = null;
    const desenha = e => e.pointerType === 'pen' || (mouseDesenha && (e.pointerType !== 'mouse' || e.button === 0));

    const aoBaixar = e => {
        if (!desenha(e)) return;
        e.preventDefault();
        e.stopPropagation();
        try { papel.setPointerCapture(e.pointerId); } catch (err) { /* já solto */ }
        if (borracha || (e.buttons & 34)) {
            atual = { id: e.pointerId, apagando: new Set() };
            apagarEm(ponto(e));
            return;
        }
        const [x, y] = ponto(e);
        const simula = e.pointerType !== 'pen';
        atual = { id: e.pointerId, traco: { c: tinta, p: [x, y, simula ? 0.5 : arred(e.pressure)] } };
        if (simula) atual.traco.s = 1;
        vivo.setAttribute('class', `vivo tinta ${tinta}`);
        vivo.setAttribute('d', caminho(atual.traco, false));
    };
    const aoMover = e => {
        if (!atual || e.pointerId !== atual.id) return;
        if (atual.apagando) { apagarEm(ponto(e)); return; }
        const juntos = e.getCoalescedEvents?.();
        for (const ev of juntos?.length ? juntos : [e]) {
            const [x, y] = ponto(ev);
            const p = atual.traco.p;
            if (Math.hypot(x - p[p.length - 3], y - p[p.length - 2]) < 1) continue;
            p.push(x, y, atual.traco.s ? 0.5 : arred(ev.pressure));
        }
        vivo.setAttribute('d', caminho(atual.traco, false));
    };
    const aoSoltar = e => {
        if (!atual || e.pointerId !== atual.id) return;
        const a = atual;
        atual = null;
        vivo.removeAttribute('d');
        if (a.apagando) {
            if (a.apagando.size) nota.tracos = nota.tracos.filter((_, i) => !a.apagando.has(i));
            desenhar();
            return;
        }
        if (e.type === 'pointercancel' && a.traco.p.length < 6) return;
        nota.tracos.push(a.traco);
        const fundo = Math.max(...a.traco.p.filter((_, i) => i % 3 === 1));
        const k = escala();
        if (fundo > papel.clientHeight * k - 80) nota.altura = Math.min(ALTURA_MAX, Math.max(nota.altura, Math.ceil(fundo)) + 300);
        desenhar();
    };
    function apagarEm([x, y]) {
        nota.tracos.forEach((t, i) => {
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
    // Um único conjunto de ouvintes por diálogo (o diálogo é reaproveitado entre notas).
    d._ouvintes?.forEach(([alvo, tipo, fn, opc]) => alvo.removeEventListener(tipo, fn, opc));
    d._ouvintes = [
        [papel, 'pointerdown', aoBaixar, true],
        [papel, 'pointermove', aoMover, false],
        [papel, 'pointerup', aoSoltar, false],
        [papel, 'pointercancel', aoSoltar, false],
        [area, 'input', ajustar, false],
    ];
    d._ouvintes.forEach(([alvo, tipo, fn, opc]) => alvo.addEventListener(tipo, fn, opc));

    // ---- salvar / fechar ----
    async function fechar() {
        if (fechando) return;
        fechando = true;
        nota.texto = area.value.replace(/\s+$/u, '');
        const mudou = JSON.stringify({ texto: nota.texto, tracos: nota.tracos }) !== original;
        if (notaVazia(nota)) {
            status.textContent = 'Apagando…';
            if (!(await aoApagar())) { fechando = false; status.textContent = 'Não consegui apagar.'; return; }
        } else if (mudou) {
            status.textContent = 'Salvando…';
            if (!(await aoSalvar({ texto: nota.texto, tracos: nota.tracos, altura: nota.altura }))) { fechando = false; status.textContent = 'Não salvou. Tente de novo.'; return; }
        }
        d.close();
    }
    d.querySelector('.np-x').onclick = fechar;
    d.querySelector('.np-ok').onclick = fechar;
    d.querySelector('.np-apagar').onclick = async () => {
        if (!notaVazia({ texto: area.value, tracos: nota.tracos }) && !confirm('Apagar esta nota?')) return;
        area.value = '';
        nota.tracos = [];
        await fechar();
    };
    d.oncancel = e => { e.preventDefault(); fechar(); };
    d.onclick = e => { if (e.target === d) fechar(); }; // clique no fundo
    area.onkeydown = e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); fechar(); } };

    d.showModal();
    desenhar();
    requestAnimationFrame(ajustar);
    if (!nota.tracos.length && matchMedia('(pointer: fine)').matches) area.focus();
}
