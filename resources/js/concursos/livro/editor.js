// Editor do capítulo: modo leitura (com grifo e notas) e modo edição (barra, menu "/", caixas,
// pontos de erro, questões, lei, imagens, tabelas), com salvamento automático.
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Typography from '@tiptap/extension-typography';
import { TableKit } from '@tiptap/extension-table';
import { Caixa, PontoErro, Questao, Lei, Grifo, CORES } from './extensoes.js';
import { Leitura } from './leitura.js';
import { Rascunho } from './rascunho.js';
import { montarCaneta } from './caneta.js';
import { NotaPonto } from './nota-ponto-no.js';
import { instalarMenuNota } from '../nota-ponto.js';
import { estado, api, el, avisar, debounce, novoId } from './util.js';

const NOMES_COR = { amarelo: 'Amarelo', verde: 'Verde', azul: 'Azul', rosa: 'Rosa' };

export function criarEditor({ alvo, doc, editavel, aoMudarModo }) {
    const status = document.querySelector('[data-salvo]');
    let pendente = false;

    const editor = new Editor({
        element: alvo,
        editable: editavel,
        content: doc,
        extensions: [
            StarterKit.configure({ heading: { levels: [2, 3, 4] }, codeBlock: false, link: { openOnClick: false, autolink: true, protocols: ['http', 'https'] } }),
            Image.configure({ inline: false }),
            Placeholder.configure({ placeholder: ({ node }) => (node.type.name === 'paragraph' ? 'Escreva aqui… (digite / para inserir caixa, questão, lei, imagem)' : '') }),
            Typography.configure({ oneHalf: false, oneQuarter: false, threeQuarters: false, plusMinus: false, notEqual: false, laquo: false, raquo: false }),
            TableKit.configure({ table: { resizable: false } }),
            Caixa, PontoErro, Questao, Lei, Grifo, Leitura, Rascunho, NotaPonto,
        ],
        editorProps: {
            attributes: { class: 'pagina-texto', 'aria-label': 'Texto do capítulo' },
            handlePaste: (view, event) => enviarImagens([...(event.clipboardData?.files ?? [])]),
            handleDrop: (view, event) => enviarImagens([...(event.dataTransfer?.files ?? [])], view.posAtCoords({ left: event.clientX, top: event.clientY })?.pos),
            handleKeyDown: (view, event) => slash.tecla(event),
        },
    });

    // ---- salvamento automático ---------------------------------------------------------------
    const salvar = debounce(async (keepalive = false) => {
        pendente = false;
        pintarStatus('Salvando…');
        const r = await api(estado.urls.salvar, { method: 'PUT', body: { conteudo: editor.getJSON() }, keepalive });
        if (!r) { pendente = true; pintarStatus('Não salvo', true); return; }
        Object.assign(estado.pontos, r.pontos);
        aplicarMapa(r.mapa);
        document.dispatchEvent(new CustomEvent('livro:pontos'));
        pintarStatus(`Salvo às ${r.salvo_em}`);
    }, 1200);

    function pintarStatus(txt, erro = false) {
        if (!status) return;
        status.textContent = txt;
        status.classList.toggle('erro', erro);
    }

    /** Pontos colados de outro capítulo ganharam id novo no servidor: troca no texto sem entrar no desfazer. */
    function aplicarMapa(mapa) {
        if (!mapa || !Object.keys(mapa).length) return;
        const tr = editor.state.tr;
        editor.state.doc.descendants((n, pos) => {
            if (n.type.name === 'pontoErro' && mapa[n.attrs.pontoId]) tr.setNodeAttribute(pos, 'pontoId', mapa[n.attrs.pontoId]);
        });
        tr.setMeta('addToHistory', false).setMeta('semSalvar', true);
        editor.view.dispatch(tr);
    }

    editor.on('update', ({ transaction }) => {
        if (transaction.getMeta('semSalvar')) return;
        pendente = true;
        pintarStatus('Alterações não salvas…');
        salvar();
    });
    window.addEventListener('beforeunload', e => {
        if (!pendente) return;
        salvar.agora(true);
        e.preventDefault();
    });
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden' && pendente) salvar.agora(true); });

    // ---- imagens -----------------------------------------------------------------------------
    function enviarImagens(arquivos, pos) {
        const imgs = arquivos.filter(f => f.type.startsWith('image/'));
        if (!imgs.length || !editor.isEditable) return false;
        (async () => {
            for (const f of imgs) {
                if (f.size > 8 * 1024 * 1024) { avisar('Imagem maior que 8 MB.'); continue; }
                pintarStatus('Enviando imagem…');
                const fd = new FormData();
                fd.append('arquivo', f);
                const r = await api(estado.urls.anexo, { method: 'POST', body: fd });
                if (!r) continue;
                const c = editor.chain().focus();
                (typeof pos === 'number' ? c.insertContentAt(pos, { type: 'image', attrs: { src: r.src, alt: f.name } }) : c.setImage({ src: r.src, alt: f.name })).run();
            }
        })();
        return true;
    }

    function escolherImagem() {
        const input = el('input', { type: 'file', accept: 'image/png,image/jpeg,image/gif,image/webp', multiple: true });
        input.addEventListener('change', () => enviarImagens([...input.files]));
        input.click();
    }

    // ---- comandos de inserção (barra e menu "/") ---------------------------------------------
    async function inserirPonto() {
        const titulo = prompt('Título do ponto (o que você erra):', '');
        if (titulo === null) return;
        const p = await api(estado.urls.ponto, { method: 'POST', body: { titulo } });
        if (!p) return;
        estado.pontos[p.id] = p;
        editor.chain().focus().insertContent({
            type: 'pontoErro',
            attrs: { pontoId: p.id, titulo: p.titulo },
            content: [
                { type: 'caixa', attrs: { tipo: 'pegadinha' }, content: [{ type: 'paragraph' }] },
                { type: 'caixa', attrs: { tipo: 'regra' }, content: [{ type: 'paragraph' }] },
            ],
        }).run();
    }

    function inserirLei() {
        const ref = prompt('Citação (ex.: ctn 98, cf 155 §6, sv 24, sumstj 392, lei8137 1):', '');
        if (!ref?.trim()) return;
        editor.chain().focus().insertContent({ type: 'lei', attrs: { ref: ref.trim().replace(/^\[\[|\]\]$/g, '') } }).insertContent(' ').run();
    }

    function inserirLink() {
        const atual = editor.getAttributes('link').href ?? '';
        const href = prompt('Endereço do link (deixe vazio para tirar):', atual);
        if (href === null) return;
        if (!href.trim()) editor.chain().focus().extendMarkRange('link').unsetLink().run();
        else editor.chain().focus().extendMarkRange('link').setLink({ href: href.trim() }).run();
    }

    const comandos = [
        { id: 'h2', nome: 'Título', dica: 'Título de seção', run: c => c.toggleHeading({ level: 2 }) },
        { id: 'h3', nome: 'Subtítulo', dica: 'Título menor', run: c => c.toggleHeading({ level: 3 }) },
        { id: 'ul', nome: 'Lista', dica: 'Lista com marcadores', run: c => c.toggleBulletList() },
        { id: 'ol', nome: 'Lista numerada', dica: '1, 2, 3…', run: c => c.toggleOrderedList() },
        ...Object.entries(estado.caixas).map(([k, nome]) => ({ id: `caixa-${k}`, nome: `Caixa: ${nome}`, dica: 'Bloco de destaque', run: c => c.inserirCaixa(k) })),
        { id: 'ponto', nome: 'Ponto de erro', dica: 'Pegadinha + regra, com situação', acao: inserirPonto },
        { id: 'questao', nome: 'Questão errada', dica: 'Questão para responder no texto', acao: () => escolherQuestao(editor) },
        { id: 'lei', nome: 'Artigo de lei / súmula', dica: '[[ctn 98]], [[sv 24]]…', acao: inserirLei },
        { id: 'tabela', nome: 'Tabela', dica: 'Quadro comparativo', run: c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }) },
        { id: 'imagem', nome: 'Imagem', dica: 'Print, esquema, mapa mental', acao: escolherImagem },
        { id: 'rascunho', nome: 'Rascunho à mão', dica: 'Área para escrever ou desenhar com a caneta', run: c => c.inserirRascunho() },
        { id: 'nota', nome: 'Nota aqui', dica: 'Ícone que abre uma nota (texto e caneta)', acao: () => editor.commands.inserirNotaPonto(editor.state.selection.from) },
        { id: 'citacao', nome: 'Citação', dica: 'Texto recuado', run: c => c.toggleBlockquote() },
        { id: 'linha', nome: 'Linha divisória', dica: '', run: c => c.setHorizontalRule() },
    ];
    const executar = cmd => (cmd.acao ? cmd.acao() : cmd.run(editor.chain().focus()).run());

    // ---- barra de ferramentas (modo edição) --------------------------------------------------
    const barra = document.querySelector('[data-barra]');
    const botoesBarra = [];
    function botao(rotulo, titulo, fn, ativo) {
        const b = el('button', { type: 'button', class: 'tb', title: titulo, 'aria-label': titulo }, rotulo);
        b.addEventListener('mousedown', e => e.preventDefault());
        b.addEventListener('click', fn);
        if (ativo) botoesBarra.push([b, ativo]);
        return b;
    }
    if (barra) {
        const grupo = (...bs) => el('div', { class: 'tb-grupo' }, ...bs);
        const menuCaixa = el('select', { class: 'tb-sel', 'aria-label': 'Inserir caixa' });
        menuCaixa.append(new Option('Caixa…', ''));
        for (const [k, nome] of Object.entries(estado.caixas)) menuCaixa.append(new Option(nome, k));
        menuCaixa.addEventListener('change', () => { if (menuCaixa.value) editor.chain().focus().inserirCaixa(menuCaixa.value).run(); menuCaixa.value = ''; });

        barra.append(
            grupo(
                botao(el('b', {}, 'N'), 'Negrito (Ctrl+B)', () => editor.chain().focus().toggleBold().run(), () => editor.isActive('bold')),
                botao(el('i', {}, 'I'), 'Itálico (Ctrl+I)', () => editor.chain().focus().toggleItalic().run(), () => editor.isActive('italic')),
                botao(el('u', {}, 'S'), 'Sublinhado (Ctrl+U)', () => editor.chain().focus().toggleUnderline().run(), () => editor.isActive('underline')),
                botao(el('s', {}, 'T'), 'Tachado', () => editor.chain().focus().toggleStrike().run(), () => editor.isActive('strike')),
            ),
            grupo(
                botao('T1', 'Título', () => editor.chain().focus().toggleHeading({ level: 2 }).run(), () => editor.isActive('heading', { level: 2 })),
                botao('T2', 'Subtítulo', () => editor.chain().focus().toggleHeading({ level: 3 }).run(), () => editor.isActive('heading', { level: 3 })),
                botao('•', 'Lista', () => editor.chain().focus().toggleBulletList().run(), () => editor.isActive('bulletList')),
                botao('1.', 'Lista numerada', () => editor.chain().focus().toggleOrderedList().run(), () => editor.isActive('orderedList')),
                botao('❝', 'Citação', () => editor.chain().focus().toggleBlockquote().run(), () => editor.isActive('blockquote')),
            ),
            grupo(
                ...CORES.map(c => botao(el('span', { class: `cor ${c}` }), `Grifar em ${NOMES_COR[c].toLowerCase()}`, () => grifar(c))),
                botao('✎', 'Nota no trecho', () => notaNaSelecao()),
            ),
            grupo(menuCaixa,
                botao('Ponto', 'Ponto de erro', inserirPonto),
                botao('Questão', 'Inserir questão errada', () => escolherQuestao(editor)),
                botao('§', 'Artigo de lei ou súmula', inserirLei),
                botao('🔗', 'Link', inserirLink, () => editor.isActive('link')),
                botao('▦', 'Tabela', () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()),
                botao('🖼', 'Imagem', escolherImagem),
                botao('✍', 'Rascunho à mão (caneta)', () => editor.chain().focus().inserirRascunho().run()),
            ),
            grupo(
                botao('↶', 'Desfazer (Ctrl+Z)', () => editor.chain().focus().undo().run()),
                botao('↷', 'Refazer (Ctrl+Y)', () => editor.chain().focus().redo().run()),
            ),
        );
        const tabela = grupo(
            botao('+Lin', 'Linha abaixo', () => editor.chain().focus().addRowAfter().run()),
            botao('+Col', 'Coluna à direita', () => editor.chain().focus().addColumnAfter().run()),
            botao('−Lin', 'Apagar linha', () => editor.chain().focus().deleteRow().run()),
            botao('−Col', 'Apagar coluna', () => editor.chain().focus().deleteColumn().run()),
            botao('✕▦', 'Apagar tabela', () => editor.chain().focus().deleteTable().run()),
        );
        tabela.classList.add('tb-tabela');
        barra.append(tabela);
        const pintarBarra = () => {
            botoesBarra.forEach(([b, ativo]) => b.setAttribute('aria-pressed', String(!!ativo())));
            tabela.hidden = !editor.isActive('table');
        };
        editor.on('selectionUpdate', pintarBarra);
        editor.on('transaction', pintarBarra);
    }

    // ---- grifo e notas (também no modo leitura) -----------------------------------------------
    /** Seleção atual em posições do documento; no modo leitura vem do DOM. */
    function selecao() {
        const { from, to, empty } = editor.state.selection;
        if (editor.isEditable) return empty ? null : { from, to };
        const s = window.getSelection();
        if (!s || s.isCollapsed || !alvo.contains(s.anchorNode) || !alvo.contains(s.focusNode)) return null;
        try {
            const a = editor.view.posAtDOM(s.anchorNode, s.anchorOffset);
            const b = editor.view.posAtDOM(s.focusNode, s.focusOffset);
            return a === b ? null : { from: Math.min(a, b), to: Math.max(a, b) };
        } catch (e) { return null; }
    }

    function grifar(cor, id = null) {
        const sel = selecao();
        if (!sel) { avisar('Selecione um trecho do texto primeiro.', 'info'); return null; }
        const tipo = editor.schema.marks.grifo;
        const tr = editor.state.tr.removeMark(sel.from, sel.to, tipo).addMark(sel.from, sel.to, tipo.create({ cor, id }));
        editor.view.dispatch(tr);
        window.getSelection()?.removeAllRanges();
        bolha.hidden = true;
        return sel;
    }

    function tirarGrifo() {
        const sel = selecao();
        if (!sel) return;
        editor.view.dispatch(editor.state.tr.removeMark(sel.from, sel.to, editor.schema.marks.grifo));
        window.getSelection()?.removeAllRanges();
        bolha.hidden = true;
    }

    function notaNaSelecao(cor = 'amarelo') {
        const sel = selecao();
        if (!sel) { avisar('Selecione o trecho que a nota comenta.', 'info'); return; }
        const trecho = editor.state.doc.textBetween(sel.from, sel.to, ' ').slice(0, 300);
        const id = novoId();
        grifar(cor, id);
        notas.abrir({ marca: id, cor, trecho, nota: '' }, true);
    }

    // Bolha que aparece ao selecionar texto (leitura e edição): cores, nota, tirar grifo.
    const bolha = el('div', { class: 'bolha', hidden: true, role: 'toolbar', 'aria-label': 'Grifar trecho' });
    for (const c of CORES) {
        const b = el('button', { type: 'button', title: `Grifar em ${NOMES_COR[c].toLowerCase()}` }, el('span', { class: `cor ${c}` }));
        b.addEventListener('mousedown', e => e.preventDefault());
        b.addEventListener('click', () => grifar(c));
        bolha.append(b);
    }
    const bNota = el('button', { type: 'button', title: 'Grifar e anotar' }, '✎ Nota');
    bNota.addEventListener('mousedown', e => e.preventDefault());
    bNota.addEventListener('click', () => notaNaSelecao());
    const bTirar = el('button', { type: 'button', title: 'Tirar o grifo' }, '⌫');
    bTirar.addEventListener('mousedown', e => e.preventDefault());
    bTirar.addEventListener('click', tirarGrifo);
    bolha.append(bNota, bTirar);
    document.body.append(bolha);

    const posicionarBolha = () => {
        const s = window.getSelection();
        if (!s || s.isCollapsed || !alvo.contains(s.anchorNode) || slash.aberto) { bolha.hidden = true; return; }
        const r = s.getRangeAt(0).getBoundingClientRect();
        if (!r.width) { bolha.hidden = true; return; }
        bolha.hidden = false;
        const larg = bolha.offsetWidth;
        bolha.style.left = `${Math.max(8, Math.min(window.innerWidth - larg - 8, r.left + r.width / 2 - larg / 2 + window.scrollX))}px`;
        bolha.style.top = `${r.top + window.scrollY - bolha.offsetHeight - 8}px`;
    };
    document.addEventListener('selectionchange', debounce(posicionarBolha, 120));
    window.addEventListener('scroll', () => { bolha.hidden = true; }, { passive: true });

    // ---- notas na margem ----------------------------------------------------------------------
    const notas = criarNotas(editor, alvo, () => (pendente ? salvar.agora() : Promise.resolve()));

    // ---- caneta: grifar passando a caneta sobre o texto ---------------------------------------
    montarCaneta(editor, alvo, document.querySelector('[data-caneta]'));

    // ---- botão direito / dois toques da caneta: nota ou edição naquele ponto -------------------
    /** Posição do documento sob o ponteiro, no fim da palavra; null fora de um parágrafo. */
    function posNoPonto(x, y) {
        const r = editor.view.posAtCoords({ left: x, top: y });
        if (!r) return null;
        const $p = editor.state.doc.resolve(r.pos);
        if (!$p.parent.inlineContent) return null;
        const resto = $p.parent.textBetween($p.parentOffset, $p.parent.content.size, null, '￼');
        let i = 0;
        while (i < resto.length && /[\p{L}\p{N}]/u.test(resto[i])) i++;
        return r.pos + i;
    }
    const botaoModo = document.querySelector('[data-modo]');
    instalarMenuNota(alvo, ({ x, y, alvo: t }) => {
        if (t.closest('.rascunho, .nota-ponto, .questao-livro')) return [];
        const pos = posNoPonto(x, y);
        if (pos == null) return [];
        const itens = [{ icone: '📝', rotulo: 'Adicionar nota aqui', acao: () => editor.commands.inserirNotaPonto(pos) }];
        if (botaoModo || editor.isEditable) {
            itens.push({
                icone: '✏️', rotulo: editor.isEditable ? 'Pôr o cursor aqui' : 'Editar o texto aqui',
                acao: () => {
                    if (!editor.isEditable) botaoModo.click();
                    setTimeout(() => editor.chain().focus().setTextSelection(pos).scrollIntoView().run(), 0);
                },
            });
        }
        const sel = selecao();
        if (sel) {
            itens.push({ icone: '🖍', rotulo: 'Grifar a seleção', acao: () => grifar('amarelo') });
            itens.push({ icone: '✎', rotulo: 'Nota (seleção)', acao: () => notaNaSelecao() });
        }
        return itens;
    });

    // ---- menu "/" -----------------------------------------------------------------------------
    const slash = criarMenuBarra(editor, comandos, executar);

    // ---- modos -------------------------------------------------------------------------------
    function definirModo(editavelAgora) {
        editor.setEditable(editavelAgora, false);
        document.body.classList.toggle('editando', editavelAgora);
        if (barra) barra.hidden = !editavelAgora;
        alvo.querySelectorAll('a.lei').forEach(a => (a.title = editavelAgora ? 'Ctrl+clique para abrir o artigo' : 'Abrir o artigo'));
        if (editavelAgora) editor.commands.focus('end');
        aoMudarModo?.(editavelAgora);
    }
    definirModo(editavel);

    return { editor, definirModo, salvarAgora: () => salvar.agora(), notas };
}

/** Diálogo para escolher uma questão errada (do capítulo ou buscando no livro) e inserir no texto. */
function escolherQuestao(editor) {
    const dlg = document.querySelector('dialog.escolher-questao');
    const lista = dlg.querySelector('[data-lista]');
    const busca = dlg.querySelector('input[type=search]');
    const pintar = qs => {
        lista.replaceChildren();
        if (!qs.length) { lista.append(el('p', { class: 'muted' }, 'Nenhuma questão. Busque pelo nº do TEC ou um trecho do enunciado.')); return; }
        for (const q of qs) {
            estado.questoes[q.id] = q;
            const b = el('button', { type: 'button', class: 'q-item' },
                el('b', {}, q.tec ? `TEC ${q.tec}` : `#${q.id}`), ' ', el('span', {}, q.enunciado.slice(0, 180) + (q.enunciado.length > 180 ? '…' : '')));
            b.addEventListener('click', () => {
                dlg.close();
                editor.chain().focus().insertContent({ type: 'questao', attrs: { questaoId: q.id } }).run();
            });
            lista.append(b);
        }
    };
    pintar(estado.doCapitulo.map(id => estado.questoes[id]).filter(Boolean));
    busca.value = '';
    busca.oninput = debounce(async () => {
        const t = busca.value.trim();
        if (!t) { pintar(estado.doCapitulo.map(id => estado.questoes[id]).filter(Boolean)); return; }
        pintar((await api(`${estado.urls.questoes}?q=${encodeURIComponent(t)}`)) ?? []);
    }, 300);
    dlg.showModal();
}

/** Menu de inserção ao digitar "/" no começo de um parágrafo. */
function criarMenuBarra(editor, comandos, executar) {
    const menu = el('div', { class: 'slash', hidden: true, role: 'listbox', 'aria-label': 'Inserir' });
    document.body.append(menu);
    let itens = [];
    let ativo = 0;
    let faixa = null;

    function abrir(lista, coords) {
        itens = lista;
        ativo = 0;
        menu.replaceChildren(...lista.map((c, i) => {
            const b = el('button', { type: 'button', role: 'option' }, el('b', {}, c.nome), c.dica ? el('small', {}, c.dica) : '');
            b.addEventListener('mousedown', e => { e.preventDefault(); escolher(i); });
            return b;
        }));
        menu.hidden = !lista.length;
        menu.style.left = `${coords.left + window.scrollX}px`;
        menu.style.top = `${coords.bottom + window.scrollY + 6}px`;
        pintar();
    }
    const fechar = () => { menu.hidden = true; faixa = null; };
    const pintar = () => [...menu.children].forEach((b, i) => b.setAttribute('aria-selected', String(i === ativo)));

    function escolher(i) {
        const cmd = itens[i];
        if (!cmd || !faixa) return fechar();
        editor.chain().focus().deleteRange(faixa).run();
        fechar();
        executar(cmd);
    }

    editor.on('transaction', () => {
        if (!editor.isEditable) return fechar();
        const { $from, empty } = editor.state.selection;
        const antes = $from.parent.textBetween(0, $from.parentOffset, '\n', '￼');
        const m = empty && $from.parent.type.name === 'paragraph' ? antes.match(/^\/([\p{L}\d ]{0,20})$/u) : null;
        if (!m) return fechar();
        const sem = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
        const q = sem(m[1]);
        faixa = { from: $from.pos - m[0].length, to: $from.pos };
        abrir(comandos.filter(c => sem(c.nome).includes(q) || sem(c.dica).includes(q)), editor.view.coordsAtPos($from.pos));
    });

    return {
        get aberto() { return !menu.hidden; },
        tecla(e) {
            if (menu.hidden) return false;
            if (e.key === 'ArrowDown') { ativo = (ativo + 1) % itens.length; pintar(); return true; }
            if (e.key === 'ArrowUp') { ativo = (ativo - 1 + itens.length) % itens.length; pintar(); return true; }
            if (e.key === 'Enter' || e.key === 'Tab') { escolher(ativo); return true; }
            if (e.key === 'Escape') { fechar(); return true; }
            return false;
        },
    };
}

/**
 * Notas dos grifos num modal: clicar num trecho grifado com nota abre a nota e, quando o trecho
 * está ligado a questões (grifo da Fase 2 ou "✔ Q123" citado na nota), dá para passar dali para
 * essas questões sem sair do modal. A nota é salva na hora; o grifo vai junto com o texto.
 */
function criarNotas(editor, alvo, salvarTexto) {
    const porMarca = new Map(estado.anotacoes.map(a => [a.marca, a]));

    const dlg = el('dialog', { class: 'modal-questoes modal-nota so-nota', 'aria-labelledby': 'mn-titulo' });
    const sub = el('small');
    const titulo = el('h2', { id: 'mn-titulo' });
    const voltar = el('button', { type: 'button', class: 'btn mini', title: 'Voltar para a nota' }, '← Nota');
    const expandir = el('a', { class: 'btn mini', title: 'Abrir as questões em página própria' }, '⤢ Expandir');
    const fechar = el('button', { type: 'button', class: 'btn mini', 'aria-label': 'Fechar' }, '✕');
    const corpoNota = el('div', { class: 'mn-nota' });
    const quadro = el('iframe', { title: 'Questões do trecho' });
    const cartaoEsq = () => el('div', { class: 'c' }, el('div', { class: 'esqueleto l t' }), el('div', { class: 'esqueleto l' }), el('div', { class: 'esqueleto l', style: 'width:85%' }),
        el('div', { class: 'esqueleto o' }), el('div', { class: 'esqueleto o' }), el('div', { class: 'esqueleto o' }));
    const esqueleto = el('div', { class: 'mq-esqueleto', 'aria-hidden': 'true' }, cartaoEsq());
    const corpoQ = el('div', { class: 'mq-corpo' }, quadro, esqueleto);
    dlg.append(el('header', {}, voltar, el('div', {}, sub, titulo), expandir, fechar), corpoNota, corpoQ);
    document.body.append(dlg);

    let atual = null; // anotação aberta
    quadro.addEventListener('load', () => { if (quadro.src !== 'about:blank') dlg.classList.remove('carregando'); });
    fechar.addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    dlg.addEventListener('close', () => {
        document.body.style.overflow = '';
        // Nota nova fechada sem texto: o grifo sai junto.
        if (atual && !atual.nota) removerGrifo(atual.marca);
        atual = null;
        setTimeout(() => { if (!dlg.open) quadro.src = 'about:blank'; }, 300);
    });
    voltar.addEventListener('click', () => verNota());

    /** Questões ligadas ao trecho ({url, n}) ou null. */
    function questoesDe(a) {
        const gq = estado.grifosQ?.[a.marca];
        if (gq?.url) return { url: new URL(gq.url, location.href), n: gq.n };
        const tec = [...new Set([...(a.nota ?? '').matchAll(/(?:^|\n)[✔✘•] Q(\d+)/gu)].map(m => m[1]))];
        if (!tec.length || !estado.urls.refazer) return null;
        const url = new URL(estado.urls.refazer, location.href);
        url.search = new URLSearchParams({ tec: tec.join(','), situacao: 'todas', iniciar: '1', qtd: '200' });
        return { url, n: tec.length };
    }

    function verNota(editando = false) {
        dlg.classList.add('so-nota');
        voltar.hidden = expandir.hidden = true;
        sub.textContent = 'Nota';
        titulo.textContent = atual.trecho ? `“${atual.trecho}”` : 'Trecho grifado';
        titulo.title = atual.trecho ?? '';
        desenharNota(atual, editando);
    }

    function verQuestoes(q) {
        dlg.classList.remove('so-nota');
        voltar.hidden = expandir.hidden = false;
        sub.textContent = `${q.n} ${q.n === 1 ? 'questão' : 'questões'} deste trecho`;
        expandir.href = q.url.href;
        const embutido = new URL(q.url); embutido.searchParams.set('embed', '1');
        if (quadro.src !== embutido.href) { dlg.classList.add('carregando'); quadro.src = embutido.href; }
    }

    function desenharNota(a, editando) {
        const texto = el('p', { class: 'nota-texto' }, a.nota);
        const editar = el('button', { type: 'button', class: 'btn mini' }, 'Editar');
        const apagar = el('button', { type: 'button', class: 'btn mini' }, 'Apagar');
        const q = a.nota ? questoesDe(a) : null;
        const irQ = q ? el('button', { type: 'button', class: 'btn mini main' }, `Ver ${q.n === 1 ? 'a questão' : `as ${q.n} questões`} →`) : null;
        const acoes = el('div', { class: 'nota-acoes' }, ...(irQ ? [irQ] : []), editar, apagar);
        const area = el('textarea', { rows: '6', 'aria-label': 'Nota', placeholder: 'Sua anotação…', maxlength: '5000' });
        const ok = el('button', { type: 'button', class: 'btn mini main' }, 'Salvar');
        const cancelar = el('button', { type: 'button', class: 'btn mini' }, 'Cancelar');
        const form = el('div', { class: 'nota-form' }, area, el('div', { class: 'nota-acoes' }, ok, cancelar));
        corpoNota.replaceChildren(texto, acoes, form);

        const modo = ed => { form.hidden = !ed; texto.hidden = acoes.hidden = ed; if (ed) { area.value = a.nota; setTimeout(() => area.focus(), 0); } };
        modo(editando);
        irQ?.addEventListener('click', () => verQuestoes(q));
        editar.addEventListener('click', () => modo(true));
        cancelar.addEventListener('click', () => { if (!a.nota) dlg.close(); else modo(false); });
        ok.addEventListener('click', async () => {
            const nota = area.value.trim();
            if (!nota) { apagar.click(); return; }
            // O grifo precisa estar salvo antes (a nota é apagada no servidor se o grifo não existe no texto).
            await salvarTexto();
            const r = await api(estado.urls.anotacao, { method: 'POST', body: { marca: a.marca, cor: a.cor, trecho: a.trecho, nota } });
            if (!r) return;
            Object.assign(a, r);
            porMarca.set(a.marca, a);
            desenharNota(a, false);
        });
        area.addEventListener('keydown', e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) ok.click(); });
        apagar.addEventListener('click', async () => {
            if (a.nota && !confirm('Apagar esta nota? (o grifo também sai)')) return;
            if (a.nota) await api(estado.urls.anotacao, { method: 'POST', body: { marca: a.marca, nota: '' } });
            porMarca.delete(a.marca);
            a.nota = '';
            dlg.close();
        });
    }

    function removerGrifo(marca) {
        const tipo = editor.schema.marks.grifo;
        const tr = editor.state.tr;
        editor.state.doc.descendants((n, pos) => {
            const m = n.marks.find(x => x.type === tipo && x.attrs.id === marca);
            if (m) tr.removeMark(pos, pos + n.nodeSize, m);
        });
        if (tr.docChanged) editor.view.dispatch(tr);
    }

    function abrir(a, editando = false) {
        atual = a;
        dlg.className = `modal-questoes modal-nota so-nota cor-${a.cor ?? 'amarelo'}`;
        verNota(editando);
        document.body.style.overflow = 'hidden';
        if (!dlg.open) dlg.showModal();
        if (!editando) fechar.focus();
    }

    // Clique num grifo com nota (fora do modo de edição) abre o modal.
    alvo.addEventListener('click', e => {
        const m = e.target.closest('mark[data-nota]');
        if (!m || editor.isEditable || window.getSelection()?.toString()) return;
        const a = porMarca.get(m.dataset.nota);
        if (a) abrir(a);
    });

    return { abrir };
}
