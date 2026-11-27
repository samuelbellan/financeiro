// Peças de leitura em volta do texto: prévia de artigo ao passar o mouse, modal com as questões
// de uma seção, sumário "Nesta página" (quando não há notas), barra de progresso e modo foco.
import { estado, api, el, abrirCitacao, guardar, ler } from './util.js';
import { chaveLeitura } from './leitura.js';

export function montarPainel(editor) {
    const alvo = editor.view.dom;
    const secoes = () => chaveLeitura.getState(editor.state)?.secoes ?? [];
    montarPrevia(alvo);
    montarGavetaErros(alvo, secoes);
    montarNestaPagina(editor, secoes);
    montarProgresso();
    montarFoco();
}

// ---- lei: prévia no hover, diálogo no clique ----------------------------------------------------
const links = new Map();
function link(ref) {
    if (!links.has(ref)) links.set(ref, api(`${estado.urls.citacao}?ref=${encodeURIComponent(ref)}`, { silencioso: true }));
    return links.get(ref);
}
const textos = new Map();
function artigo(json) {
    if (!textos.has(json)) {
        textos.set(json, fetch(json, { headers: { Accept: 'application/json' } }).then(r => (r.ok ? r.json() : null)).catch(() => null));
    }
    return textos.get(json);
}

function montarPrevia(alvo) {
    const pop = el('div', { class: 'previa-lei', role: 'tooltip', hidden: '' });
    document.body.append(pop);
    let timer = null;
    let atual = null;

    const fechar = () => { clearTimeout(timer); pop.hidden = true; atual = null; };
    async function dadosDe(a) {
        if (a.dataset.json) return { json: a.dataset.json, href: a.href, rotulo: a.textContent };
        const l = await link(a.dataset.ref);
        return l ? { json: l.json, href: l.href, rotulo: l.rotulo } : null;
    }

    alvo.addEventListener('mouseover', e => {
        const a = e.target.closest('.lei-auto, a.lei');
        if (!a || a === atual || matchMedia('(hover: none)').matches) return;
        atual = a;
        clearTimeout(timer);
        timer = setTimeout(async () => {
            const d = await dadosDe(a);
            if (atual !== a) return;
            if (!d) { a.classList.add('lei-sem-acervo'); return; }
            const r = await artigo(d.json);
            if (atual !== a) return;
            pop.replaceChildren(el('b', {}, r?.titulo ?? d.rotulo));
            const corpo = el('div', { class: 'previa-corpo' });
            if (r?.html) corpo.innerHTML = r.html; // HTML do acervo, montado no servidor
            else corpo.textContent = 'Texto não encontrado no acervo.';
            pop.append(corpo, el('small', {}, 'Clique para abrir'));
            const box = a.getBoundingClientRect();
            pop.hidden = false;
            const larg = pop.offsetWidth;
            pop.style.left = `${Math.max(8, Math.min(window.scrollX + box.left, window.scrollX + innerWidth - larg - 8))}px`;
            const embaixo = box.bottom + pop.offsetHeight + 12 < innerHeight;
            pop.style.top = `${window.scrollY + (embaixo ? box.bottom + 6 : box.top - pop.offsetHeight - 6)}px`;
        }, 350);
    });
    alvo.addEventListener('mouseout', e => {
        const a = e.target.closest('.lei-auto, a.lei');
        if (a && !a.contains(e.relatedTarget)) fechar();
    });
    window.addEventListener('scroll', fechar, { passive: true });

    alvo.addEventListener('click', async e => {
        const a = e.target.closest('.lei-auto');
        if (!a || document.body.classList.contains('editando')) return;
        e.preventDefault();
        fechar();
        const d = await dadosDe(a);
        if (d) abrirCitacao(d.rotulo, d.href, d.json);
    });
}

// ---- selo "N questões": modal para resolver as questões da seção ---------------------------------
// O modal carrega a tela de Questões em modo embutido; "Expandir" abre a mesma lista em página própria.
function urlQuestoes(s) {
    // O selo conta os números do TEC citados nas notas da seção: abre exatamente essas.
    if (s.total?.size) {
        const u = new URL(estado.urls.refazer, location.href);
        u.search = new URLSearchParams({ tec: [...s.total].join(','), situacao: 'todas', iniciar: '1', qtd: '200' });
        return u;
    }
    const ids = new Set();
    s.todasMarcas.forEach(m => (estado.grifosQ?.[m]?.ids ?? []).forEach(id => ids.add(id)));
    if (ids.size) {
        const u = new URL(estado.urls.refazer, location.href);
        u.search = new URLSearchParams({ ids: [...ids].join(','), situacao: 'todas', iniciar: '1', qtd: '200' });
        return u;
    }
    // Sem grifo ligado: cai nas questões do capítulo inteiro.
    const cap = document.querySelector('.questoes-cap a.btn');
    return cap ? new URL(cap.href, location.href) : null;
}

function montarGavetaErros(alvo, secoes) {
    const dlg = el('dialog', { class: 'modal-questoes', 'aria-labelledby': 'mq-titulo' });
    const titulo = el('h2', { id: 'mq-titulo' });
    const sub = el('small');
    const expandir = el('a', { class: 'btn mini', title: 'Abrir as questões em página própria' }, '⤢ Expandir');
    const fechar = el('button', { type: 'button', class: 'btn mini', 'aria-label': 'Fechar' }, '✕');
    const quadro = el('iframe', { title: 'Questões da seção' });
    // Skeleton no formato de uma questão enquanto a tela carrega dentro do modal.
    const cartao = () => el('div', { class: 'c' }, el('div', { class: 'esqueleto l t' }), el('div', { class: 'esqueleto l' }), el('div', { class: 'esqueleto l', style: 'width:85%' }),
        el('div', { class: 'esqueleto o' }), el('div', { class: 'esqueleto o' }), el('div', { class: 'esqueleto o' }));
    const esqueleto = el('div', { class: 'mq-esqueleto', 'aria-hidden': 'true' }, cartao());
    dlg.append(el('header', {}, el('div', {}, sub, titulo), expandir, fechar), el('div', { class: 'mq-corpo' }, quadro, esqueleto));
    quadro.addEventListener('load', () => { if (quadro.src !== 'about:blank') dlg.classList.remove('carregando'); });
    document.body.append(dlg);
    fechar.addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    // Espera a animação de saída antes de descarregar a tela de dentro.
    dlg.addEventListener('close', () => { document.body.style.overflow = ''; setTimeout(() => { if (!dlg.open) quadro.src = 'about:blank'; }, 300); });

    alvo.addEventListener('mousedown', e => { if (e.target.closest('.badge-erros')) e.preventDefault(); });
    alvo.addEventListener('click', e => {
        const b = e.target.closest('.badge-erros');
        if (!b) return;
        e.preventDefault();
        const s = secoes().find(x => x.id === b.dataset.secao);
        const url = s && urlQuestoes(s);
        if (!url) return;
        sub.textContent = `${s.num} · ${s.total.size} ${s.total.size === 1 ? 'questão' : 'questões'}`;
        titulo.textContent = s.titulo;
        expandir.href = url.href;
        const embutido = new URL(url); embutido.searchParams.set('embed', '1');
        dlg.classList.add('carregando');
        quadro.src = embutido.href;
        document.body.style.overflow = 'hidden';
        dlg.showModal();
        fechar.focus();
    });
}

// ---- "Nesta página" (quando não há notas) -------------------------------------------------------
function montarNestaPagina(editor, secoes) {
    const margem = document.querySelector('.margem');
    if (!margem) return;
    const nav = el('nav', { class: 'nesta-pagina', 'aria-label': 'Nesta página' });
    margem.prepend(nav);

    function desenhar() {
        const lista = secoes().filter(s => s.nivel <= 3 && document.getElementById(s.id)?.offsetParent);
        if (lista.length < 2) { nav.replaceChildren(); return; }
        nav.replaceChildren(el('h2', {}, 'Nesta página'), el('ol', {}, ...lista.map(s => {
            const a = el('a', { href: `#${s.id}`, 'data-alvo': s.id, class: `n${s.nivel}` }, el('span', { class: 'np-num' }, s.num), s.titulo);
            if (s.total?.size) a.append(el('span', { class: 'np-err', title: 'questões erradas' }, String(s.total.size)));
            a.addEventListener('click', e => { e.preventDefault(); document.getElementById(s.id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }); history.replaceState(null, '', `#${s.id}`); });
            return el('li', {}, a);
        })));
        espiar();
    }

    let obs = null;
    function espiar() {
        obs?.disconnect();
        const visiveis = new Map();
        obs = new IntersectionObserver(ents => {
            ents.forEach(en => visiveis.set(en.target.id, en.isIntersecting));
            const ids = secoes().map(s => s.id);
            // Ativa a última seção cujo título já passou do topo.
            let ativo = null;
            for (const id of ids) {
                const h = document.getElementById(id);
                if (h && h.getBoundingClientRect().top < innerHeight * 0.3) ativo = id;
            }
            nav.querySelectorAll('a').forEach(a => a.classList.toggle('ativo', a.dataset.alvo === ativo));
        }, { rootMargin: '0px 0px -60% 0px' });
        secoes().forEach(s => { const h = document.getElementById(s.id); if (h) obs.observe(h); });
    }
    let t;
    editor.on('update', () => { clearTimeout(t); t = setTimeout(desenhar, 400); });
    setTimeout(desenhar, 60);
}

// ---- progresso de leitura e modo foco -----------------------------------------------------------
function montarProgresso() {
    const barra = el('div', { class: 'progresso-leitura', 'aria-hidden': 'true' }, el('i'));
    document.body.prepend(barra);
    const pagina = document.querySelector('.pagina');
    const pintar = () => {
        const r = pagina.getBoundingClientRect();
        const total = r.height - innerHeight * 0.6;
        const p = total > 0 ? Math.min(1, Math.max(0, -r.top / total)) : 1;
        barra.firstChild.style.transform = `scaleX(${p})`;
    };
    window.addEventListener('scroll', pintar, { passive: true });
    window.addEventListener('resize', pintar);
    pintar();
}

function montarFoco() {
    const chave = 'livro-foco';
    const aplicar = on => {
        document.body.classList.toggle('foco', on);
        guardar(chave, on ? '1' : '0');
        document.querySelectorAll('[data-foco]').forEach(b => b.setAttribute('aria-pressed', String(on)));
    };
    aplicar(ler(chave) === '1');
    document.querySelectorAll('[data-foco]').forEach(b => b.addEventListener('click', () => aplicar(!document.body.classList.contains('foco'))));
    document.addEventListener('keydown', e => {
        if (e.target.closest('input, textarea, select, [contenteditable=true], [contenteditable=plaintext-only]') || e.ctrlKey || e.metaKey || e.altKey) return;
        if (document.body.classList.contains('editando')) return;
        if (e.key === 'f' || e.key === 'F') { e.preventDefault(); aplicar(!document.body.classList.contains('foco')); }
    });
}
