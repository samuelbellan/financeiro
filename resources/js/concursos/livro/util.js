// Estado compartilhado do livro (preenchido em livro.js com os dados que o servidor põe na página)
// e pequenos utilitários de DOM e de rede.

export const estado = {
    pontos: {},
    questoes: {},
    anotacoes: [],
    caixas: {},
    status: {},
    urls: {},
    csrf: '',
};

/** Cria um elemento: el('a', { href: '#' }, 'texto', outroEl). Atributo false/null é ignorado. */
export function el(tag, attrs = {}, ...filhos) {
    const e = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
        if (v === false || v === null || v === undefined) continue;
        e.setAttribute(k, v === true ? '' : v);
    }
    for (const f of filhos) if (f !== '' && f != null) e.append(f);
    return e;
}

/** Itens "I. … II. …" que o PDF juntou numa linha voltam a ficar um por linha (mostrado com pre-line). */
export function quebrarItens(t) {
    return String(t ?? '').replace(/\s+(?=(?:I|II|III|IV|V|VI)\s?[.\-–)]\s)/gu, '\n');
}

let avisoTimer;
export function avisar(msg, tipo = 'erro') {
    let a = document.querySelector('.livro-aviso');
    if (!a) {
        a = el('div', { class: 'livro-aviso', role: 'status', 'aria-live': 'polite' });
        document.body.append(a);
    }
    a.textContent = msg;
    a.dataset.tipo = tipo;
    a.hidden = false;
    clearTimeout(avisoTimer);
    avisoTimer = setTimeout(() => (a.hidden = true), 4000);
}

/**
 * fetch JSON com o token CSRF. body objeto → JSON; FormData vai como está.
 * Devolve o JSON ou null (e avisa, a menos que silencioso).
 */
export async function api(url, { method = 'GET', body, silencioso = false, keepalive = false } = {}) {
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': estado.csrf, 'X-Requested-With': 'XMLHttpRequest' };
    let corpo = body;
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        corpo = JSON.stringify(body);
    }
    try {
        const r = await fetch(url, { method, headers, body: corpo, credentials: 'same-origin', keepalive });
        if (r.status === 419 || r.status === 401) {
            if (!silencioso) avisar('Sua sessão expirou. Recarregue a página (o que não foi salvo pode se perder).');
            return null;
        }
        const dados = await r.json().catch(() => null);
        if (!r.ok) {
            if (!silencioso) avisar(dados?.message ?? 'Não consegui salvar. Tente de novo.');
            return null;
        }
        return dados;
    } catch (e) {
        if (!silencioso) avisar('Sem conexão com o servidor. Tente de novo.');
        return null;
    }
}

/** Abre o artigo/precedente num diálogo, sem sair da página. */
export async function abrirCitacao(titulo, href, json) {
    const dlg = document.querySelector('dialog.art');
    if (!dlg) { location.href = href; return; }
    const corpo = dlg.querySelector('[data-corpo]');
    dlg.querySelector('#art-titulo').textContent = titulo;
    dlg.querySelector('[data-abrir]').href = href;
    corpo.textContent = 'Carregando…';
    dlg.showModal();
    try {
        const resp = await fetch(json, { headers: { Accept: 'application/json' } });
        const r = await resp.json().catch(() => ({}));
        if (!resp.ok) { corpo.textContent = r.erro ?? 'Texto não encontrado no acervo.'; return; }
        dlg.querySelector('#art-titulo').textContent = r.titulo;
        corpo.innerHTML = r.html; // HTML montado no servidor a partir do acervo (Markdown escapado)
    } catch (e) {
        corpo.textContent = 'Não consegui carregar o artigo.';
    }
}

export function debounce(fn, ms) {
    let t;
    const d = (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    d.agora = (...args) => { clearTimeout(t); return fn(...args); };
    d.cancelar = () => clearTimeout(t);
    return d;
}

export const novoId = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);

export function guardar(chave, valor) {
    try { valor === null ? localStorage.removeItem(chave) : localStorage.setItem(chave, valor); } catch (e) { /* sem storage */ }
}

export function ler(chave) {
    try { return localStorage.getItem(chave); } catch (e) { return null; }
}
