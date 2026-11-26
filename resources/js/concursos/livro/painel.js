// Peças de leitura em volta do texto: prévia de artigo ao passar o mouse, gaveta com as questões
// erradas de uma seção, sumário "Nesta página" (quando não há notas), barra de progresso e modo foco.
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

// ---- gaveta "✗ N erros" ------------------------------------------------------------------------
function montarGavetaErros(alvo, secoes) {
    const gaveta = el('aside', { class: 'gaveta-erros', 'aria-label': 'Questões erradas da seção', hidden: '' });
    const fundo = el('div', { class: 'gaveta-fundo', hidden: '' });
    document.body.append(fundo, gaveta);
    const fechar = () => { gaveta.hidden = true; fundo.hidden = true; };
    fundo.addEventListener('click', fechar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !gaveta.hidden) fechar(); });

    alvo.addEventListener('mousedown', e => { if (e.target.closest('.badge-erros')) e.preventDefault(); });
    alvo.addEventListener('click', e => {
        const b = e.target.closest('.badge-erros');
        if (!b) return;
        e.preventDefault();
        const s = secoes().find(x => x.id === b.dataset.secao);
        if (!s) return;
        const porMarca = new Map((estado.anotacoes ?? []).map(a => [a.marca, a]));
        const fechar1 = el('button', { type: 'button', class: 'btn mini', 'aria-label': 'Fechar' }, '✕');
        fechar1.addEventListener('click', fechar);
        const itens = [...s.todasMarcas].map(m => porMarca.get(m)).filter(Boolean).map(a => {
            const ir = el('button', { type: 'button', class: 'link' }, 'Ver no texto');
            ir.addEventListener('click', () => {
                fechar();
                const m = alvo.querySelector(`mark[data-nota="${a.marca}"]`);
                m?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                m?.classList.remove('piscar'); void m?.offsetWidth; m?.classList.add('piscar');
            });
            return el('article', { class: `ge-item ${a.cor}` },
                el('blockquote', {}, `“${(a.trecho ?? '').slice(0, 180)}${(a.trecho ?? '').length > 180 ? '…' : ''}”`),
                el('p', { class: 'nota-texto' }, a.nota ?? ''),
                ir);
        });
        const refazer = document.querySelector('.questoes-cap a.btn');
        gaveta.replaceChildren(
            el('header', {}, el('div', {}, el('small', {}, `${s.num} · ${s.total.size} ${s.total.size === 1 ? 'questão errada' : 'questões erradas'}`), el('h2', {}, s.titulo)), fechar1),
            el('div', { class: 'ge-lista' }, ...itens),
            ...(refazer ? [el('a', { class: 'btn main', href: refazer.href }, 'Refazer as questões do capítulo')] : []),
        );
        gaveta.hidden = false;
        fundo.hidden = false;
        fechar1.focus();
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
