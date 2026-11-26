@extends('concursos.layout')

@section('title', $lei['sigla'])

@php
    $rotulos = \App\Models\Concursos\MaterialPonto::STATUS;
    $nMarcados = $marcas->where('marcado', true)->count();
    $nNotas = $marcas->whereNotNull('nota')->count();
    $nProva = count($incidencia);
@endphp

@section('css')
    .cab h1 { font-size: 22px; margin: 0 0 4px; }
    .cab .ementa { font-size: 14px; color: var(--muted); margin: 0 0 10px; }
    .ferr { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; font-size: 14px; }
    .ferr input[type=text], .ferr select { border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 10px; font: inherit; }
    .ferr input[type=text] { width: 120px; }
    .texto { font-size: 16px; line-height: 1.65; }
    .texto h1, .texto h2, .texto h3, .texto h4, .texto h5, .texto h6 { font-size: 15px; text-transform: none; text-align: center; margin: 28px 0 8px; color: var(--muted); letter-spacing: .02em; }
    .art { scroll-margin-top: 16px; padding: 2px 0; position: relative; }
    .art p { margin: 0 0 8px; }
    .art:target { background: var(--warn-soft); border-radius: 8px; box-shadow: 0 0 0 8px var(--warn-soft); }
    .art.citado, .art.marcado { border-left: 3px solid var(--accent); padding-left: 12px; margin-left: -15px; }
    .art.marcado { border-left-color: var(--warn); }

    .barra { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 0 0 4px; min-height: 26px; }
    .barra .acoes { margin-left: auto; display: flex; gap: 2px; }
    .ico { border: 0; background: none; color: var(--muted); cursor: pointer; padding: 3px 6px; border-radius: 8px; font: inherit; font-size: 13px; line-height: 1; }
    .ico:hover { background: var(--bg); color: var(--ink); }
    .ico[aria-pressed=true] { color: var(--warn); }
    .ico svg { width: 16px; height: 16px; vertical-align: -3px; }
    .badge { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; border-radius: 999px; padding: 2px 9px; border: 1px solid transparent; cursor: pointer; background: none; font-family: inherit; }
    .badge.banco { color: var(--accent); background: var(--accent-soft); }
    .badge.errei { color: var(--no); background: var(--no-soft); }
    .badge.resumo { color: var(--ok); background: var(--ok-soft); }

    .detalhe { font-size: 13px; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px; margin: 4px 0 10px; }
    .detalhe .partes { display: flex; flex-wrap: wrap; gap: 4px 10px; color: var(--muted); margin-bottom: 4px; }
    .detalhe .partes b { color: var(--ink); font-weight: 600; }
    .detalhe .qs { display: flex; flex-wrap: wrap; gap: 4px; }
    .detalhe .qs a { font-size: 12px; text-decoration: none; border: 1px solid var(--line); border-radius: 6px; padding: 1px 6px; color: var(--ink); background: var(--card); }
    .detalhe .qs a.e { border-color: var(--no); color: var(--no); }

    .notas { margin: 4px 0 12px; display: flex; flex-direction: column; gap: 4px; }
    .nota { font-size: 13px; background: var(--accent-soft); border-radius: 8px; padding: 6px 10px; text-decoration: none; color: var(--ink); }
    .nota b { color: var(--accent); }
    .nota .st { font-size: 12px; color: var(--muted); }

    .minha { font-size: 14px; background: var(--warn-soft); border-radius: 10px; padding: 8px 12px; margin: 4px 0 12px; white-space: pre-wrap; cursor: pointer; }
    .minha small { display: block; color: var(--muted); font-size: 12px; margin-top: 2px; white-space: normal; }
    .editor { margin: 4px 0 12px; display: flex; flex-direction: column; gap: 6px; }
    .editor textarea { width: 100%; min-height: 80px; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 10px; font: inherit; font-size: 14px; resize: vertical; box-sizing: border-box; }
    .editor .linha { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .editor .linha[hidden], .detalhe[hidden] { display: none; }
    .editor select { max-width: 100%; flex: 1 1 220px; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 7px 8px; font: inherit; font-size: 13px; }
    .editor .msg { font-size: 12px; color: var(--muted); }

    mark.grifo { border-radius: 3px; padding: 0 1px; color: inherit; cursor: pointer; }
    mark.grifo.amarelo, #paleta .cor.amarelo { background: #fff0a3; }
    mark.grifo.verde, #paleta .cor.verde { background: #c8f2d4; }
    mark.grifo.azul, #paleta .cor.azul { background: #cfe3ff; }
    mark.grifo.rosa, #paleta .cor.rosa { background: #ffd3e4; }
    :root[data-theme=dark] mark.grifo.amarelo, :root[data-theme=dark] #paleta .cor.amarelo { background: #5c4d0f; }
    :root[data-theme=dark] mark.grifo.verde, :root[data-theme=dark] #paleta .cor.verde { background: #1d4a2c; }
    :root[data-theme=dark] mark.grifo.azul, :root[data-theme=dark] #paleta .cor.azul { background: #1d3557; }
    :root[data-theme=dark] mark.grifo.rosa, :root[data-theme=dark] #paleta .cor.rosa { background: #5a2238; }
    #paleta { position: absolute; z-index: 30; display: flex; gap: 6px; align-items: center; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 8px; box-shadow: 0 6px 20px rgba(0,0,0,.18); }
    #paleta[hidden] { display: none; }
    #paleta.fixa { position: fixed; left: 50% !important; top: auto !important; bottom: 16px; transform: translateX(-50%); padding: 10px 12px; }
    #paleta button { width: 26px; height: 26px; border-radius: 50%; border: 2px solid var(--line); cursor: pointer; padding: 0; color: var(--muted); background: var(--bg); font-size: 13px; }
    #paleta.fixa button { width: 34px; height: 34px; }
    #paleta button.atual { border-color: var(--ink); }
    #paleta .tirar { display: none; }
    #paleta.editando .tirar { display: inline-block; }
    #paleta .msg { font-size: 12px; color: var(--no); max-width: 220px; }
    #paleta .msg:empty { display: none; }
    .orfao { font-size: 13px; color: var(--muted); border: 1px dashed var(--line); border-radius: 8px; padding: 6px 10px; margin: 4px 0 8px; display: flex; gap: 8px; align-items: baseline; }
    .orfao q { color: var(--ink); flex: 1; }
    .orfao button { border: 0; background: none; color: var(--no); cursor: pointer; font: inherit; font-size: 12px; }
    .dica { font-size: 12px; color: var(--muted); }
    .so-toque { display: none; }
    @media (pointer: coarse) { .so-toque { display: inline-flex; } .so-mouse { display: none; } }
    #modo-grifar[aria-pressed=true] { background: var(--warn-soft); border-color: var(--warn); color: var(--warn); }
    .filtrado .texto > section.art:not(.mostra), .filtrado .texto > :not(section.art) { display: none; }
    .filtrado .texto > .mostra { margin-bottom: 20px; }
    .fonte { font-size: 13px; color: var(--muted); }
@endsection

@section('content')
<header class="card cab">
    <h1>{{ $lei['nome'] }}</h1>
    @if (!empty($meta['ementa']))<p class="ementa">{{ $meta['ementa'] }}</p>@endif
    <div class="ferr">
        <form id="ir" class="ferr" onsubmit="return false">
            <input type="text" inputmode="text" placeholder="Ir ao art." aria-label="Ir ao artigo">
            <button class="btn" type="submit">Ir</button>
        </form>
        <select id="filtro" aria-label="Mostrar">
            <option value="">Todos os artigos</option>
            <option value="prova" @disabled(!$nProva)>Caem na prova ({{ $nProva }})</option>
            <option value="marcado" data-n="marcado">Marcados ({{ $nMarcados }})</option>
            <option value="nota" data-n="nota">Com nota ({{ $nNotas }})</option>
            <option value="grifo" data-n="grifo">Com grifo ({{ count($grifos) }})</option>
            <option value="citado" @disabled($citados->isEmpty())>Do meu resumo ({{ $citados->count() }})</option>
        </select>
        <button type="button" class="btn so-toque" id="modo-grifar" aria-pressed="false">Modo grifar</button>
        <span class="dica so-mouse">Selecione um trecho para grifar.</span>
    </div>
</header>

<div class="card" id="lei">
    <div class="texto">
        @foreach ($blocos as $b)
            @if ($b['tipo'] === 'titulo')
                {!! $acervo->mdParaHtml($b['md']) !!}
            @elseif ($b['tipo'] === 'artigo')
                @php
                    $notas = $citados[$b['ancora']] ?? [];
                    $inc = $incidencia[$b['ancora']] ?? null;
                    $marca = $marcas[$b['ancora']] ?? null;
                @endphp
                <section class="art @if ($notas) citado @endif @if ($marca?->marcado) marcado @endif" id="{{ $b['ancora'] }}"
                         data-rotulo="{{ $b['rotulo'] }}" data-prova="{{ $inc ? 1 : 0 }}"
                         data-marcado="{{ $marca?->marcado ? 1 : 0 }}" data-nota="{{ $marca?->nota }}" data-em="{{ $marca?->updated_at?->format('d/m/Y') }}"
                         @isset($grifos[$b['ancora']]) data-grifos="{{ json_encode($grifos[$b['ancora']], JSON_UNESCAPED_UNICODE) }}" @endisset>
                    <div class="barra">
                        @if ($inc)
                            <button type="button" class="badge banco js-detalhe" title="Questões do banco que cobram este artigo">{{ $inc['total'] }} {{ $inc['total'] === 1 ? 'questão' : 'questões' }}</button>
                            @if ($inc['errei'])<button type="button" class="badge errei js-detalhe" title="Questões deste artigo que você errou">você errou {{ $inc['errei'] }}</button>@endif
                        @endif
                        @if ($notas)<span class="badge resumo" title="Pontos do seu resumo que citam este artigo">no resumo · {{ count($notas) }}</span>@endif
                        <span class="acoes">
                            <button type="button" class="ico js-marcar" aria-pressed="{{ $marca?->marcado ? 'true' : 'false' }}" title="Marcar artigo" aria-label="Marcar artigo">
                                <svg viewBox="0 0 24 24" fill="{{ $marca?->marcado ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M6 3h12v18l-6-4-6 4z"/></svg>
                            </button>
                            <button type="button" class="ico js-anotar" title="Escrever nota" aria-label="Escrever nota">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h4L19 9l-4-4L4 16z"/></svg>
                            </button>
                        </span>
                    </div>
                    @if ($inc)
                        <div class="detalhe" hidden>
                            @if ($inc['partes'])
                                <div class="partes">@foreach ($inc['partes'] as $parte => $n)<span><b>{{ $parte }}</b> × {{ $n }}</span>@endforeach</div>
                            @endif
                            <div class="qs">
                                @php $erradas = array_flip($inc['erradas'] ?? []); @endphp
                                @foreach ($inc['questoes'] as $q)
                                    <a href="{{ route('concursos.questoes.show', $q) }}" target="_blank" rel="noopener" @class(['e' => isset($erradas[$q])]) title="{{ isset($erradas[$q]) ? 'Você errou' : 'Questão' }} #{{ $inc['tec'][$q] ?? $q }}">#{{ $inc['tec'][$q] ?? $q }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="corpo">{!! $acervo->mdParaHtml($b['md']) !!}</div>
                    <div class="js-orfaos"></div>
                    <div class="js-minha"></div>
                    @if ($notas)
                        <div class="notas">
                            @foreach ($notas as $n)
                                <a class="nota" href="{{ $n['url'] }}"><b>No resumo:</b> {{ $n['titulo'] }}@if ($n['status']) <span class="st">· {{ $rotulos[$n['status']] ?? $n['status'] }}</span>@endif</a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @else
                <div>{!! $acervo->mdParaHtml($b['md']) !!}</div>
            @endif
        @endforeach
    </div>
</div>
<p class="fonte">Texto de <a href="{{ $fonte }}" target="_blank" rel="noopener">ctresb/leis</a>@if (!empty($meta['fonte'])), convertido de <a href="{{ $meta['fonte'] }}" target="_blank" rel="noopener">{{ parse_url($meta['fonte'], PHP_URL_HOST) }}</a>@endif. Conversão automática: confira a fonte oficial antes de citar.
    @if ($nProva) A contagem de questões vem dos dispositivos classificados no banco.@endif</p>

<div id="paleta" role="toolbar" aria-label="Cor do grifo" hidden>
    @foreach (\App\Models\Concursos\LeiGrifo::CORES as $cor)
        <button type="button" class="cor {{ $cor }}" data-cor="{{ $cor }}" title="Grifar de {{ $cor }}" aria-label="{{ ucfirst($cor) }}"></button>
    @endforeach
    <button type="button" class="tirar" data-cor="" title="Apagar grifo" aria-label="Apagar grifo">✕</button>
    <span class="msg" role="status"></span>
</div>

<template id="tpl-editor">
    <div class="editor">
        <textarea maxlength="5000" placeholder="Sua nota sobre este artigo (o que a banca troca, o macete...)"></textarea>
        <div class="linha">
            <button type="button" class="btn main js-salvar">Salvar nota</button>
            <button type="button" class="btn js-cancelar">Cancelar</button>
            <span class="msg"></span>
        </div>
        @if ($capitulos)
            <div class="linha js-levar-linha">
                <select aria-label="Capítulo do material">
                    <option value="">Levar ao material: escolha o capítulo…</option>
                    @foreach ($capitulos as $m)
                        <optgroup label="{{ $m['material'] }}">
                            @foreach ($m['capitulos'] as $c)<option value="{{ $c['id'] }}">{{ $c['titulo'] }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
                <button type="button" class="btn js-levar">Levar ao material</button>
            </div>
        @endif
    </div>
</template>
@endsection

@section('js')
<script>
(() => {
    const prefixo = @json($lei['prefixo'] ?? '');
    const urlMarca = @json(route('concursos.leis.marcar', [$chave, '__A__']));
    const token = @json(csrf_token());
    const contagem = { marcado: @json($nMarcados), nota: @json($nNotas) };

    document.getElementById('ir').addEventListener('submit', () => {
        const v = document.querySelector('#ir input').value.trim().toLowerCase().replace(/[^0-9a-z-]/g, '');
        const el = v && document.getElementById(`${prefixo}art-${v}`);
        if (el) { location.hash = el.id; } else { alert('Artigo não encontrado nesta lei.'); }
    });

    // Filtro: todos, caem na prova, marcados, com nota, do meu resumo.
    const filtro = document.getElementById('filtro');
    const aplicarFiltro = () => {
        const f = filtro.value;
        document.body.classList.toggle('filtrado', !!f);
        document.querySelectorAll('section.art').forEach((s) => {
            const ok = f === 'prova' ? s.dataset.prova === '1'
                : f === 'marcado' ? s.dataset.marcado === '1'
                : f === 'nota' ? !!s.dataset.nota
                : f === 'grifo' ? s.querySelector('mark.grifo, .orfao') !== null
                : f === 'citado' ? s.classList.contains('citado') : true;
            s.classList.toggle('mostra', ok);
        });
    };
    filtro.addEventListener('change', aplicarFiltro);
    const atualizarContagem = () => {
        filtro.querySelector('[data-n=marcado]').textContent = `Marcados (${contagem.marcado})`;
        filtro.querySelector('[data-n=nota]').textContent = `Com nota (${contagem.nota})`;
        const comGrifo = [...document.querySelectorAll('section.art')].filter((s) => s.querySelector('mark.grifo, .orfao')).length;
        filtro.querySelector('[data-n=grifo]').textContent = `Com grifo (${comGrifo})`;
    };

    const enviar = async (url, corpo, method = 'POST') => {
        const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify(corpo) });
        const j = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(j.message || 'Não consegui salvar. Tente de novo.');
        return j;
    };
    const urlDe = (s, extra = '') => urlMarca.replace('__A__', s.id) + extra;

    const desenharNota = (s) => {
        const box = s.querySelector('.js-minha');
        box.innerHTML = '';
        if (!s.dataset.nota) return;
        const d = document.createElement('div');
        d.className = 'minha';
        d.title = 'Editar nota';
        d.textContent = s.dataset.nota;
        const em = document.createElement('small');
        em.textContent = `Minha nota${s.dataset.em ? ' · ' + s.dataset.em : ''}`;
        d.appendChild(em);
        d.addEventListener('click', () => abrirEditor(s));
        box.appendChild(d);
    };

    const abrirEditor = (s) => {
        const box = s.querySelector('.js-minha');
        box.innerHTML = '';
        box.appendChild(document.getElementById('tpl-editor').content.cloneNode(true));
        const ta = box.querySelector('textarea');
        const msg = box.querySelector('.msg');
        const levarLinha = box.querySelector('.js-levar-linha');
        ta.value = s.dataset.nota || '';
        if (levarLinha) levarLinha.hidden = !s.dataset.nota;
        ta.focus();

        box.querySelector('.js-cancelar').addEventListener('click', () => desenharNota(s));
        box.querySelector('.js-salvar').addEventListener('click', async () => {
            const tinha = !!s.dataset.nota;
            try {
                const j = await enviar(urlDe(s), { nota: ta.value });
                s.dataset.nota = j.nota || '';
                s.dataset.em = j.em || '';
                contagem.nota += (!!j.nota) - tinha;
                atualizarContagem();
                desenharNota(s);
            } catch (e) { msg.textContent = e.message; }
        });
        box.querySelector('.js-levar')?.addEventListener('click', async () => {
            const cap = box.querySelector('select').value;
            if (!cap) { msg.textContent = 'Escolha o capítulo.'; return; }
            if (ta.value.trim() !== (s.dataset.nota || '')) { msg.textContent = 'Salve a nota antes de levá-la.'; return; }
            try {
                const j = await enviar(urlDe(s, '/levar'), { capitulo_id: Number(cap) });
                msg.innerHTML = '';
                const a = document.createElement('a');
                a.href = j.url; a.textContent = 'Ponto criado no material ↗'; a.target = '_blank';
                msg.appendChild(a);
            } catch (e) { msg.textContent = e.message; }
        });
    };

    document.querySelectorAll('section.art').forEach((s) => {
        desenharNota(s);
        s.querySelector('.js-anotar').addEventListener('click', () => abrirEditor(s));
        s.querySelectorAll('.js-detalhe').forEach((b) => b.addEventListener('click', () => {
            const d = s.querySelector('.detalhe');
            d.hidden = !d.hidden;
        }));
        const bt = s.querySelector('.js-marcar');
        bt.addEventListener('click', async () => {
            const novo = s.dataset.marcado !== '1';
            try {
                await enviar(urlDe(s), { marcado: novo });
                s.dataset.marcado = novo ? '1' : '0';
                s.classList.toggle('marcado', novo);
                bt.setAttribute('aria-pressed', String(novo));
                bt.querySelector('svg').setAttribute('fill', novo ? 'currentColor' : 'none');
                contagem.marcado += novo ? 1 : -1;
                atualizarContagem();
            } catch (e) { alert(e.message); }
        });
    });

    // ---------- Grifos ----------
    // Posições em caracteres (code points) sobre o texto puro de .corpo, igual ao servidor.
    const urlGrifar = @json(route('concursos.leis.grifar', [$chave, '__A__']));
    const urlGrifo = @json(route('concursos.leis.grifos.update', '__G__'));
    const paleta = document.getElementById('paleta');
    document.body.appendChild(paleta); // posição absoluta relativa à página, não a um card
    const msgPaleta = paleta.querySelector('.msg');
    const botaoModo = document.getElementById('modo-grifar');
    const toque = matchMedia('(pointer: coarse)').matches;
    let alvo = null; // {tipo: 'novo', secao, inicio, fim} | {tipo: 'grifo', secao, id, cor}

    const cp = (s) => { let n = 0; for (const _ of s) n++; return n; };
    const u16 = (s, pontos) => { let i = 0, n = 0; for (const ch of s) { if (n === pontos) break; i += ch.length; n++; } return i; };
    const posicao = (corpo, no, off) => { const r = document.createRange(); r.setStart(corpo, 0); r.setEnd(no, off); return cp(r.toString()); };

    const envolver = (corpo, g) => {
        const w = document.createTreeWalker(corpo, NodeFilter.SHOW_TEXT);
        const nos = [];
        for (let n = w.nextNode(); n; n = w.nextNode()) nos.push(n);
        let base = 0;
        for (const no of nos) {
            const tam = cp(no.data);
            const de = Math.max(g.inicio, base), ate = Math.min(g.fim, base + tam);
            if (de < ate && no.data.trim() !== '') {
                let alvoNo = no;
                const i0 = u16(no.data, de - base), i1 = u16(no.data, ate - base);
                if (i1 < alvoNo.data.length) alvoNo.splitText(i1);
                if (i0 > 0) alvoNo = alvoNo.splitText(i0);
                const m = document.createElement('mark');
                m.className = `grifo ${g.cor}`;
                m.dataset.id = g.id;
                alvoNo.parentNode.insertBefore(m, alvoNo);
                m.appendChild(alvoNo);
            }
            base += tam;
        }
    };
    const desenvolver = (secao, id) => {
        secao.querySelectorAll(`mark.grifo[data-id="${id}"]`).forEach((m) => {
            const pai = m.parentNode;
            while (m.firstChild) pai.insertBefore(m.firstChild, m);
            pai.removeChild(m);
            pai.normalize();
        });
    };
    const desenharOrfaos = (secao, orfaos) => {
        const box = secao.querySelector('.js-orfaos');
        orfaos.forEach((g) => {
            const d = document.createElement('div');
            d.className = 'orfao';
            d.innerHTML = '<span>Grifo que não achei mais no texto atual:</span> <q></q> <button type="button">Apagar</button>';
            d.querySelector('q').textContent = g.trecho;
            d.querySelector('button').addEventListener('click', async () => {
                try { await enviar(urlGrifo.replace('__G__', g.id), {}, 'DELETE'); d.remove(); atualizarContagem(); } catch (e) { alert(e.message); }
            });
            box.appendChild(d);
        });
    };

    document.querySelectorAll('section.art[data-grifos]').forEach((s) => {
        const lista = JSON.parse(s.dataset.grifos);
        const corpo = s.querySelector('.corpo');
        lista.filter((g) => g.inicio !== null).forEach((g) => envolver(corpo, g));
        desenharOrfaos(s, lista.filter((g) => g.inicio === null));
    });
    atualizarContagem();

    const fecharPaleta = () => { paleta.hidden = true; alvo = null; msgPaleta.textContent = ''; };
    const abrirPaleta = (rect, editando, corAtual) => {
        paleta.classList.toggle('editando', editando);
        paleta.classList.toggle('fixa', toque);
        paleta.querySelectorAll('.cor').forEach((b) => b.classList.toggle('atual', b.dataset.cor === corAtual));
        msgPaleta.textContent = '';
        paleta.hidden = false;
        if (!toque) {
            const larg = paleta.offsetWidth;
            paleta.style.left = Math.max(8, Math.min(window.scrollX + rect.left + rect.width / 2 - larg / 2, window.scrollX + document.documentElement.clientWidth - larg - 8)) + 'px';
            paleta.style.top = (window.scrollY + rect.top - paleta.offsetHeight - 8) + 'px';
        }
    };

    // Seleção dentro de um artigo → intervalo [inicio, fim) sem os espaços das pontas.
    const lerSelecao = () => {
        const sel = getSelection();
        if (!sel.rangeCount || sel.isCollapsed) return null;
        const r = sel.getRangeAt(0);
        const corpoDe = (n) => (n.nodeType === 1 ? n : n.parentElement)?.closest('.corpo');
        const corpo = corpoDe(r.startContainer);
        if (!corpo || corpo !== corpoDe(r.endContainer)) return null;
        let inicio = posicao(corpo, r.startContainer, r.startOffset);
        let fim = posicao(corpo, r.endContainer, r.endOffset);
        const txt = Array.from(r.toString());
        while (txt.length && /\s/.test(txt[0])) { txt.shift(); inicio++; }
        while (txt.length && /\s/.test(txt[txt.length - 1])) { txt.pop(); fim--; }
        if (fim <= inicio) return null;
        return { tipo: 'novo', secao: corpo.closest('section.art'), inicio, fim, rect: r.getBoundingClientRect() };
    };
    const talvezAbrir = () => {
        const s = lerSelecao();
        if (!s) { if (alvo?.tipo === 'novo') fecharPaleta(); return; }
        alvo = s;
        abrirPaleta(s.rect, false, null);
    };

    if (toque) {
        let t;
        botaoModo.addEventListener('click', () => {
            const on = botaoModo.getAttribute('aria-pressed') !== 'true';
            botaoModo.setAttribute('aria-pressed', String(on));
            botaoModo.textContent = on ? 'Grifando: selecione o trecho' : 'Modo grifar';
            if (!on) fecharPaleta();
        });
        document.addEventListener('selectionchange', () => {
            if (botaoModo.getAttribute('aria-pressed') !== 'true') return;
            clearTimeout(t);
            t = setTimeout(talvezAbrir, 350);
        });
    } else {
        document.getElementById('lei').addEventListener('mouseup', () => setTimeout(talvezAbrir, 0));
    }

    document.getElementById('lei').addEventListener('click', (ev) => {
        const m = ev.target.closest('mark.grifo');
        if (!m || !getSelection().isCollapsed) return;
        alvo = { tipo: 'grifo', secao: m.closest('section.art'), id: m.dataset.id, cor: [...m.classList].find((c) => c !== 'grifo') };
        abrirPaleta(m.getBoundingClientRect(), true, alvo.cor);
    });
    document.addEventListener('mousedown', (ev) => {
        if (!paleta.hidden && !paleta.contains(ev.target) && !ev.target.closest('mark.grifo')) fecharPaleta();
    });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') fecharPaleta(); });
    paleta.addEventListener('mousedown', (ev) => ev.preventDefault()); // não perder a seleção

    paleta.querySelectorAll('button').forEach((b) => b.addEventListener('click', async () => {
        if (!alvo) return;
        const cor = b.dataset.cor;
        try {
            if (alvo.tipo === 'novo') {
                const g = await enviar(urlGrifar.replace('__A__', alvo.secao.id), { inicio: alvo.inicio, fim: alvo.fim, cor });
                envolver(alvo.secao.querySelector('.corpo'), g);
                getSelection().removeAllRanges();
            } else if (cor === '') {
                await enviar(urlGrifo.replace('__G__', alvo.id), {}, 'DELETE');
                desenvolver(alvo.secao, alvo.id);
            } else {
                await enviar(urlGrifo.replace('__G__', alvo.id), { cor }, 'PATCH');
                alvo.secao.querySelectorAll(`mark.grifo[data-id="${alvo.id}"]`).forEach((m) => { m.className = `grifo ${cor}`; });
            }
            atualizarContagem();
            fecharPaleta();
        } catch (e) { msgPaleta.textContent = e.message; }
    }));
})();
</script>
@endsection
