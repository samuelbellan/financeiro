// Livro de resumo (Concursos → Resumos): sumário, capítulo com editor, busca e navegação.
import { estado, api, el, avisar, debounce, guardar, ler, abrirCitacao } from './livro/util.js';

const dados = JSON.parse(document.getElementById('livro-dados')?.textContent ?? '{}');
Object.assign(estado, dados.editor ?? {}, { csrf: dados.csrf, livro: dados.livro });
const chaveUltimo = `livro-ultimo-${dados.livro?.id}`;

montarSumario();
montarBusca();
montarGaveta();
montarRecolher();
montarIndice();

if (dados.editor) {
    montarCapitulo();
} else {
    montarCapa();
}

// ---------------------------------------------------------------------------------------------
async function montarCapitulo() {
    guardar(chaveUltimo, JSON.stringify({ url: location.pathname, titulo: dados.editor.titulo }));
    const { criarEditor } = await import('./livro/editor.js');
    const alvo = document.querySelector('[data-editor]');
    const estatico = document.querySelector('[data-estatico]');
    const params = new URLSearchParams(location.search);
    const btn = document.querySelector('[data-modo]');
    const titulo = document.querySelector('[data-titulo-capitulo]');

    const { definirModo, salvarAgora } = criarEditor({
        alvo,
        doc: dados.editor.doc,
        editavel: params.has('editar'),
        aoMudarModo: editando => {
            btn.textContent = editando ? 'Concluir' : 'Editar';
            btn.setAttribute('aria-pressed', String(editando));
            titulo.contentEditable = editando ? 'plaintext-only' : 'false';
        },
    });
    estatico.remove();
    alvo.hidden = false;

    btn.addEventListener('click', async () => {
        const editando = btn.getAttribute('aria-pressed') !== 'true';
        if (!editando) await salvarAgora();
        definirModo(editando);
        const u = new URL(location.href);
        editando ? u.searchParams.set('editar', '1') : u.searchParams.delete('editar');
        history.replaceState(null, '', u);
    });

    // Título do capítulo: editável no modo edição, salvo ao sair do campo.
    let original = titulo.textContent.trim();
    titulo.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); titulo.blur(); } });
    titulo.addEventListener('blur', async () => {
        const novo = titulo.textContent.trim();
        if (!novo) { titulo.textContent = original; return; }
        if (novo === original) return;
        const r = await api(dados.urls.renomear, { method: 'PATCH', body: { titulo: novo } });
        if (!r) { titulo.textContent = original; return; }
        original = r.titulo;
        document.querySelectorAll(`[data-cap="${dados.editor.capituloId}"] > .cap-linha .cap-nome`).forEach(n => (n.textContent = r.titulo));
        document.title = `${r.titulo} · Concursos`;
    });

    // Veio da busca: destaca e rola até a primeira ocorrência.
    const q = params.get('q');
    if (q) setTimeout(() => destacar(alvo, q), 100);
    if (location.hash) setTimeout(() => document.querySelector(location.hash)?.scrollIntoView({ block: 'start' }), 150);

    // Atalhos: e = editar (fora de campos), ← → = capítulo anterior / próximo no modo leitura.
    document.addEventListener('keydown', e => {
        if (e.target.closest('input, textarea, select, [contenteditable=true], [contenteditable=plaintext-only]') || e.ctrlKey || e.metaKey || e.altKey) return;
        if (document.body.classList.contains('editando')) return;
        if (e.key === 'e') { e.preventDefault(); btn.click(); }
        if (e.key === 'ArrowLeft') document.querySelector('[data-anterior]')?.click();
        if (e.key === 'ArrowRight') document.querySelector('[data-proximo]')?.click();
    });
}

function destacar(raiz, termo) {
    const sem = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const alvo = sem(termo);
    const andar = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT);
    const faixas = [];
    for (let n = andar.nextNode(); n; n = andar.nextNode()) {
        const t = sem(n.textContent);
        for (let i = t.indexOf(alvo); i >= 0 && faixas.length < 200; i = t.indexOf(alvo, i + alvo.length)) {
            const r = document.createRange();
            r.setStart(n, i);
            r.setEnd(n, i + alvo.length);
            faixas.push(r);
        }
    }
    if (!faixas.length) return;
    if (window.CSS?.highlights && window.Highlight) CSS.highlights.set('busca', new Highlight(...faixas));
    faixas[0].startContainer.parentElement?.scrollIntoView({ block: 'center' });
}

// ---------------------------------------------------------------------------------------------
function montarCapa() {
    const ultimo = (() => { try { return JSON.parse(ler(chaveUltimo) ?? 'null'); } catch (e) { return null; } })();
    const cont = document.querySelector('[data-continuar]');
    if (cont && ultimo?.url) {
        cont.href = ultimo.url;
        cont.querySelector('span').textContent = ultimo.titulo;
        cont.hidden = false;
    }
}

// ---------------------------------------------------------------------------------------------
/**
 * Sumário: árvore de capítulos. Menu de cada capítulo: renomear, subcapítulo, mover (subir/
 * descer, entrar no anterior, sair para o nível de cima) e apagar. A ordem nova vai inteira.
 */
function montarSumario() {
    const raiz = document.querySelector('[data-sumario]');
    if (!raiz) return;

    raiz.addEventListener('click', async e => {
        const b = e.target.closest('[data-acao]');
        if (!b) return;
        const li = b.closest('[data-cap]');
        const id = li ? Number(li.dataset.cap) : null;
        const acao = b.dataset.acao;
        b.closest('details')?.removeAttribute('open');

        if (acao === 'novo' || acao === 'sub') {
            const t = prompt(acao === 'sub' ? 'Título do subcapítulo:' : 'Título do capítulo:', '');
            if (!t?.trim()) return;
            const r = await api(dados.urls.criar, { method: 'POST', body: { titulo: t.trim(), parent_id: acao === 'sub' ? id : null } });
            if (r) location.href = r.url;
        } else if (acao === 'renomear') {
            const nome = li.querySelector(':scope > .cap-linha .cap-nome');
            const t = prompt('Novo título:', nome.textContent.trim());
            if (!t?.trim()) return;
            const r = await api(dados.urls.renomearCap.replace(/\/0$/, `/${id}`), { method: 'PATCH', body: { titulo: t.trim() } });
            if (r) {
                nome.textContent = r.titulo;
                if (id === dados.editor?.capituloId) document.querySelector('[data-titulo-capitulo]').textContent = r.titulo;
            }
        } else if (acao === 'apagar') {
            const n = li.querySelectorAll('[data-cap]').length;
            const aviso = `Apagar “${li.querySelector('.cap-nome').textContent.trim()}”${n ? ` e ${n} subcapítulo(s)` : ''}? O texto e as notas somem (as questões continuam no Refazer erradas).`;
            if (!confirm(aviso)) return;
            const r = await api(dados.urls.apagarCap.replace(/\/0$/, `/${id}`), { method: 'DELETE' });
            if (r) location.href = id === dados.editor?.capituloId ? r.url : location.href;
        } else if (['subir', 'descer', 'entrar', 'sair'].includes(acao)) {
            if (mover(li, acao)) await salvarOrdem(raiz);
        }
    });
}

function mover(li, acao) {
    const lista = li.parentElement;
    if (acao === 'subir' && li.previousElementSibling) { lista.insertBefore(li, li.previousElementSibling); return true; }
    if (acao === 'descer' && li.nextElementSibling) { lista.insertBefore(li.nextElementSibling, li); return true; }
    if (acao === 'entrar' && li.previousElementSibling) {
        const anterior = li.previousElementSibling;
        let sub = anterior.querySelector(':scope > ol');
        if (!sub) { sub = el('ol'); anterior.append(sub); }
        sub.append(li);
        return true;
    }
    if (acao === 'sair') {
        const pai = lista.closest('[data-cap]');
        if (!pai) return false;
        pai.after(li);
        if (!lista.children.length) lista.remove();
        return true;
    }
    avisar('Não dá para mover para lá.', 'info');
    return false;
}

async function salvarOrdem(raiz) {
    const itens = [];
    const andar = (ol, pai) => [...ol.children].forEach((li, i) => {
        itens.push({ id: Number(li.dataset.cap), parent_id: pai, ordem: i + 1 });
        const sub = li.querySelector(':scope > ol');
        if (sub) andar(sub, Number(li.dataset.cap));
    });
    andar(raiz.querySelector(':scope > ol'), null);
    const r = await api(dados.urls.ordem, { method: 'POST', body: { itens } });
    if (!r) { location.reload(); return; }
    renumerar(raiz);
}

function renumerar(raiz) {
    const andar = (ol, prefixo, nivel) => [...ol.children].forEach((li, i) => {
        const num = `${prefixo}${i + 1}`;
        li.querySelector(':scope > .cap-linha .cap-num').textContent = num;
        li.style.setProperty('--nivel', nivel);
        const sub = li.querySelector(':scope > ol');
        if (sub) andar(sub, `${num}.`, nivel + 1);
    });
    andar(raiz.querySelector(':scope > ol'), '', 0);
}

// ---------------------------------------------------------------------------------------------
function montarBusca() {
    const campo = document.querySelector('[data-busca]');
    const saida = document.querySelector('[data-resultados]');
    const sumario = document.querySelector('[data-sumario]');
    if (!campo) return;
    campo.addEventListener('input', debounce(async () => {
        const q = campo.value.trim();
        if (q.length < 2) { saida.hidden = true; sumario.hidden = false; return; }
        const r = await api(`${dados.urls.buscar}?q=${encodeURIComponent(q)}`, { silencioso: true });
        saida.replaceChildren();
        saida.hidden = false;
        sumario.hidden = true;
        if (!r?.length) { saida.append(el('p', { class: 'muted small' }, 'Nada encontrado no livro.')); return; }
        for (const x of r) saida.append(el('a', { href: x.url, class: 'res' }, el('b', {}, x.titulo), el('span', {}, x.trecho)));
    }, 250));
    document.addEventListener('keydown', e => {
        if (e.key === '/' && !e.target.closest('input, textarea, [contenteditable=true]') && !document.body.classList.contains('editando')) {
            e.preventDefault();
            document.body.classList.add('gaveta-aberta');
            document.body.classList.remove('lateral-recolhida');
            campo.focus();
        }
    });
}

/** Sumário lateral recolhível no desktop (modo foco). O estado fica no navegador. */
function montarRecolher() {
    document.querySelectorAll('[data-recolher]').forEach(b => b.addEventListener('click', () => {
        const recolhida = document.body.classList.toggle('lateral-recolhida');
        guardar('livro-lateral-recolhida', recolhida ? '1' : null);
    }));
}

/**
 * Sumário da capa: ordenar (ordem do livro, mais erros, mais questões) e filtrar (todos, com
 * erros, sem erros). Fora da ordem do livro a lista fica plana, sem recuo por nível.
 */
function montarIndice() {
    const lista = document.querySelector('[data-indice]');
    if (!lista) return;
    const itens = [...lista.children];
    const vazio = document.querySelector('[data-indice-vazio]');
    const estadoIndice = { ordem: 'ordem', filtro: 'todos' };
    const num = (li, k) => Number(li.dataset[k]);

    const aplicar = () => {
        const { ordem, filtro } = estadoIndice;
        const ordenados = ordem === 'ordem'
            ? [...itens].sort((a, b) => num(a, 'ordem') - num(b, 'ordem'))
            : [...itens].sort((a, b) => num(b, ordem) - num(a, ordem) || num(a, 'ordem') - num(b, 'ordem'));
        lista.replaceChildren(...ordenados);
        lista.classList.toggle('plano', ordem !== 'ordem');
        let visiveis = 0;
        for (const li of itens) {
            const erros = num(li, 'erros');
            li.hidden = (filtro === 'erros' && !erros) || (filtro === 'limpos' && erros > 0);
            if (!li.hidden) visiveis++;
        }
        if (vazio) vazio.hidden = visiveis > 0;
        document.querySelectorAll('[data-ordenar]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.ordenar === ordem)));
        document.querySelectorAll('[data-filtro]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.filtro === filtro)));
        document.querySelectorAll('[data-filtro-atalho]').forEach(b => b.setAttribute('aria-pressed', String(filtro !== 'todos' && b.dataset.filtroAtalho === filtro)));
    };

    document.addEventListener('click', e => {
        const b = e.target.closest('[data-ordenar], [data-filtro], [data-filtro-atalho]');
        if (!b) return;
        if (b.dataset.ordenar) estadoIndice.ordem = b.dataset.ordenar;
        if (b.dataset.filtro) estadoIndice.filtro = b.dataset.filtro;
        if (b.dataset.filtroAtalho) {
            estadoIndice.filtro = b.dataset.filtroAtalho;
            if (estadoIndice.filtro === 'erros') estadoIndice.ordem = 'erros';
            lista.closest('.card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        aplicar();
    });
}

function montarGaveta() {
    document.querySelectorAll('[data-gaveta]').forEach(b => b.addEventListener('click', () => document.body.classList.toggle('gaveta-aberta')));
    document.querySelector('.livro-lateral')?.addEventListener('click', e => {
        if (e.target.closest('a[href]')) document.body.classList.remove('gaveta-aberta');
    });
    const dlg = document.querySelector('dialog.art');
    dlg?.querySelector('[data-fechar]')?.addEventListener('click', () => dlg.close());
    dlg?.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    document.querySelectorAll('dialog [data-fechar]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));

    // Links de lei no texto estático (sem JS do editor ainda) e na capa.
    document.addEventListener('click', e => {
        const a = e.target.closest('[data-estatico] a.lei[data-json]');
        if (!a || e.ctrlKey || e.metaKey) return;
        e.preventDefault();
        abrirCitacao(a.textContent, a.href, a.dataset.json);
    });
}
