@extends('concursos.layout')

@section('title', 'Leis')

@php
    $local = app(\App\Services\Concursos\Leis\AcervoLeis::class)->raizLocal();
@endphp

@section('css')
    .topo-leis { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .topo-leis h1 { font-size: 20px; margin: 0; }
    .info-origem { position: relative; }
    .info-origem summary { list-style: none; cursor: pointer; width: 24px; height: 24px; border-radius: 99px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700;
        color: var(--muted); border: 1px solid var(--line); background: var(--card); user-select: none;
        transition: color .15s, border-color .15s, background-color .15s; }
    .info-origem summary::-webkit-details-marker { display: none; }
    .info-origem summary:hover, .info-origem[open] summary { color: var(--accent); border-color: var(--accent); background: var(--accent-soft); }
    .info-origem .info-caixa { margin-top: 8px; padding: 10px 14px; border-radius: 10px; background: var(--card);
        border: 1px solid var(--line); font-size: 13px; color: var(--muted); line-height: 1.45; max-width: 560px; }
    .info-origem[open] { width: 100%; }

    .busca { display: flex; gap: 8px; flex-wrap: wrap; padding: 14px 16px; }
    .busca select, .busca input { border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 9px 10px; font: inherit; font-size: 15px; }
    .busca input { width: 130px; }

    .barra-leis { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin: 22px 0 12px; }
    .barra-leis h2 { font-size: 16px; margin: 0; }
    .filtro-wrap { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
    .filtro-wrap input { width: 100%; border: 1px solid var(--line); background: var(--card); color: var(--ink);
        border-radius: 10px; padding: 8px 42px 8px 12px; font: inherit; font-size: 14px; }
    .filtro-wrap kbd { position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
        font: 600 11px/1 Inter, system-ui, sans-serif; color: var(--muted); background: var(--bg);
        border: 1px solid var(--line); border-radius: 5px; padding: 3px 6px; pointer-events: none; transition: opacity .15s; }
    .filtro-wrap input:focus + kbd, .filtro-wrap.tem-texto kbd { opacity: 0; }
    .dica-salto { font-size: 13px; color: var(--accent); background: var(--accent-soft); border-radius: 10px;
        padding: 8px 12px; margin-bottom: 12px; display: none; align-items: center; justify-content: space-between; gap: 8px; }
    .dica-salto.visivel { display: flex; }
    .dica-salto a { font-weight: 600; text-decoration: none; }

    .grupo { margin-bottom: 16px; }
    .grupo > summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 8px;
        font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        color: var(--muted); padding: 6px 2px 8px; user-select: none; }
    .grupo > summary::-webkit-details-marker { display: none; }
    .grupo > summary::before { content: '▸'; font-size: 11px; transition: transform .15s; display: inline-block; width: 10px; }
    .grupo[open] > summary::before { transform: rotate(90deg); }
    .grupo > summary .qtd { font-weight: 600; font-size: 12px; padding: 1px 7px; border-radius: 99px;
        background: var(--line); color: var(--muted); }
    .grupo.oculto { display: none; }

    .leis { display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 680px) {
        .leis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .lei { position: relative; display: flex; flex-direction: column; gap: 8px; padding: 14px 16px; margin: 0;
        transition: border-color .15s, transform .12s var(--mola), box-shadow .15s; }
    .lei:hover { border-color: color-mix(in srgb, var(--accent) 45%, var(--line));
        box-shadow: 0 3px 12px color-mix(in srgb, var(--ink) 6%, transparent); }
    .lei.oculto { display: none; }
    .lei-topo { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .lei-sigla { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 99px;
        background: var(--accent-soft); color: var(--accent); font-size: 12.5px; font-weight: 700; letter-spacing: .01em; }
    .lei-acoes { display: flex; align-items: center; gap: 6px; position: relative; z-index: 2; }
    .lei-tag { font-size: 11px; font-weight: 600; color: var(--muted); background: var(--bg);
        border: 1px solid var(--line); border-radius: 99px; padding: 2px 8px; max-width: 150px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fav { border: 0; background: transparent; color: var(--muted); cursor: pointer; padding: 2px 4px;
        border-radius: 6px; font-size: 16px; line-height: 1; opacity: .55; transition: opacity .15s, color .15s, transform .12s var(--mola); }
    .fav:hover { opacity: 1; color: var(--warn); }
    .fav.ativo { opacity: 1; color: var(--warn); }
    .fav:active { transform: scale(.85); }

    .lei-link { text-decoration: none; color: var(--ink); font-weight: 600; font-size: 15px; line-height: 1.35; }
    .lei-link::after { content: ''; position: absolute; inset: 0; border-radius: 14px; }
    .lei-link:focus-visible { outline: none; }
    .lei:has(.lei-link:focus-visible) { outline: 2px solid var(--accent); outline-offset: 2px; }

    .lei-meta { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 12px; color: var(--muted); margin-top: auto; }
    .lei-meta .nuvem { color: var(--muted); }
    .lei-meta .estudo { color: var(--ok); font-weight: 500; }

    .vazio-filtro { display: none; text-align: center; padding: 28px 16px; color: var(--muted); font-size: 14px; }
    .vazio-filtro.visivel { display: block; }

    .rodape-acervo { margin-top: 28px; padding-top: 14px; border-top: 1px solid var(--line);
        font-size: 12.5px; color: var(--muted); line-height: 1.5; }
@endsection

@section('content')
<div class="topo-leis">
    <h1>Lei seca e jurisprudência</h1>
    <details class="info-origem">
        <summary title="Sobre a origem dos textos" aria-label="Sobre a origem dos textos">i</summary>
        <div class="info-caixa">
            Textos do projeto <a href="https://github.com/ctresb/leis" target="_blank" rel="noopener">ctresb/leis</a>@if ($local), lidos da cópia local em <code>{{ $local }}</code>@endif.
            Conversão automática: na dúvida, confira a fonte oficial indicada na página da norma.
        </div>
    </details>
</div>

<form class="card busca" method="GET" action="{{ route('concursos.leis.buscar') }}">
    <select name="tipo" aria-label="Tipo">
        <optgroup label="Jurisprudência">
            @foreach (\App\Services\Concursos\Leis\Jurisprudencia::TIPOS as $k => $t)
                <option value="{{ $k }}" @selected(old('tipo', 'sv') === $k)>{{ $t[2] }}</option>
            @endforeach
        </optgroup>
        @if ($local)
            <optgroup label="Normas federais">
                @foreach (\App\Services\Concursos\Leis\AcervoLeis::TIPOS_FEDERAIS as $k => $t)
                    <option value="{{ $k }}" @selected(old('tipo') === $k)>{{ $t[2] }}</option>
                @endforeach
            </optgroup>
        @endif
    </select>
    <input name="numero" inputmode="numeric" required pattern="\d{1,6}" placeholder="Número" value="{{ old('numero') }}" aria-label="Número">
    <button class="btn main" type="submit">Abrir</button>
    @if ($local)<a class="btn" href="{{ route('concursos.leis.acervo') }}">Navegar no acervo</a>@endif
</form>

<div class="barra-leis">
    <h2>Leis do material</h2>
    <div class="filtro-wrap" id="filtroWrap">
        <input type="search" id="filtroLeis" placeholder="Filtrar lei ou ir ao artigo (ex.: ICMS, ctn 98)…" autocomplete="off" aria-label="Filtrar leis do material">
        <kbd aria-hidden="true">/</kbd>
    </div>
</div>

<div class="dica-salto" id="dicaSalto" role="status" aria-live="polite"></div>

<details class="grupo oculto" id="grupoFixadas" open>
    <summary>Fixadas <span class="qtd" id="qtdFixadas">0</span></summary>
    <div class="leis" id="listaFixadas"></div>
</details>

<div id="gruposLeis">
@foreach ($grupos as $nomeGrupo => $itens)
    <details class="grupo" open data-grupo="{{ $nomeGrupo }}">
        <summary>{{ $nomeGrupo }} <span class="qtd">{{ $itens->count() }}</span></summary>
        <div class="leis">
            @foreach ($itens as $l)
                @php
                    $stats = [];
                    if ($l['grifos'] > 0) $stats[] = $l['grifos'] . ' ' . ($l['grifos'] === 1 ? 'grifo' : 'grifos');
                    if ($l['notas'] > 0) $stats[] = $l['notas'] . ' ' . ($l['notas'] === 1 ? 'nota' : 'notas');
                    if ($l['marcados'] > 0 && $l['notas'] === 0) $stats[] = $l['marcados'] . ' ' . ($l['marcados'] === 1 ? 'marcado' : 'marcados');
                    $urlLei = route('concursos.leis.show', $l['chave']);
                @endphp
                <div class="card lei"
                     data-chave="{{ $l['chave'] }}"
                     data-sigla="{{ $l['sigla'] }}"
                     data-nome="{{ $l['nome'] }}"
                     data-grupo="{{ $nomeGrupo }}"
                     data-prefixo="{{ $l['prefixo'] }}"
                     data-url="{{ $urlLei }}">
                    <div class="lei-topo">
                        <span class="lei-sigla">{{ $l['sigla'] }}</span>
                        <div class="lei-acoes">
                            <button type="button" class="fav" data-fav="{{ $l['chave'] }}" title="Fixar no topo" aria-label="Fixar {{ $l['sigla'] }} no topo" aria-pressed="false">☆</button>
                        </div>
                    </div>
                    <a class="lei-link" href="{{ $urlLei }}">{{ $l['nome'] }}</a>
                    @if ($stats || !$l['baixada'])
                        <div class="lei-meta">
                            @if ($stats)<span class="estudo">{{ implode(' · ', $stats) }}</span>@endif
                            @unless ($l['baixada'])<span class="nuvem">☁ baixa ao abrir</span>@endunless
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </details>
@endforeach
</div>

<div class="card vazio-filtro" id="vazioFiltro">Nenhuma lei encontrada para essa busca.</div>

<p class="rodape-acervo">
    Acervo <a href="https://github.com/ctresb/leis" target="_blank" rel="noopener">ctresb/leis</a>@if ($local) · cópia local em <code>{{ $local }}</code>@endif.
    Na dúvida, confira a fonte oficial indicada na página de cada norma.
</p>

<script>
(function () {
    const STORAGE_KEY = 'concursos.leis.favoritas';
    const filtro = document.getElementById('filtroLeis');
    const filtroWrap = document.getElementById('filtroWrap');
    const dicaSalto = document.getElementById('dicaSalto');
    const vazio = document.getElementById('vazioFiltro');
    const grupoFixadas = document.getElementById('grupoFixadas');
    const listaFixadas = document.getElementById('listaFixadas');
    const qtdFixadas = document.getElementById('qtdFixadas');
    const grupos = Array.from(document.querySelectorAll('#gruposLeis > .grupo'));
    const cards = Array.from(document.querySelectorAll('#gruposLeis .lei'));

    function norm(s) {
        return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function compacto(s) {
        return norm(s).replace(/\s+/g, '');
    }

    const indice = cards.map((el) => {
        const chave = el.dataset.chave;
        const sigla = el.dataset.sigla;
        const nome = el.dataset.nome;
        const grupo = el.dataset.grupo;
        const siglaCurta = sigla.split('/')[0];
        return {
            el,
            chave,
            sigla,
            nome,
            grupo,
            prefixo: el.dataset.prefixo || '',
            url: el.dataset.url,
            texto: norm(`${chave} ${sigla} ${nome} ${grupo}`),
            chavesSalto: Array.from(new Set([compacto(chave), compacto(sigla), compacto(siglaCurta)])),
        };
    });

    function lerFavs() {
        try {
            const arr = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }

    function salvarFavs(arr) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); } catch (e) {}
    }

    function atualizarEstrelas(favs) {
        const set = new Set(favs);
        document.querySelectorAll('.fav[data-fav]').forEach((btn) => {
            const ativo = set.has(btn.dataset.fav);
            btn.classList.toggle('ativo', ativo);
            btn.textContent = ativo ? '★' : '☆';
            btn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
            btn.title = ativo ? 'Remover dos fixados' : 'Fixar no topo';
        });
    }

    function renderFixadas() {
        const favs = lerFavs();
        listaFixadas.innerHTML = '';
        const buscaAtiva = filtro.value.trim() !== '';
        let visiveis = 0;

        favs.forEach((chave) => {
            const item = indice.find((i) => i.chave === chave);
            if (!item) return;
            const clone = item.el.cloneNode(true);
            clone.classList.remove('oculto');
            const acoes = clone.querySelector('.lei-acoes');
            const tag = document.createElement('span');
            tag.className = 'lei-tag';
            tag.textContent = item.grupo;
            acoes.prepend(tag);
            listaFixadas.appendChild(clone);
            visiveis++;
        });

        qtdFixadas.textContent = String(visiveis);
        grupoFixadas.classList.toggle('oculto', visiveis === 0 || buscaAtiva);
        atualizarEstrelas(favs);
    }

    function alternarFav(chave) {
        const favs = lerFavs();
        const i = favs.indexOf(chave);
        if (i >= 0) favs.splice(i, 1);
        else favs.push(chave);
        salvarFavs(favs);
        renderFixadas();
    }

    document.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.fav[data-fav]');
        if (!btn) return;
        ev.preventDefault();
        ev.stopPropagation();
        alternarFav(btn.dataset.fav);
    });

    let saltoAtual = null;

    function detectarSalto(raw) {
        const limpo = raw.trim();
        const m = limpo.match(/^(.+?)\s+(?:art\.?\s*)?(\d+(?:-[a-zA-Z])?)$/i);
        if (!m) return null;
        const cand = compacto(m[1]);
        if (!cand) return null;
        const alvo = indice.find((i) => i.chavesSalto.includes(cand));
        if (!alvo) return null;
        const art = m[2].toLowerCase();
        return {
            item: alvo,
            artigo: m[2].toUpperCase(),
            url: `${alvo.url}#${alvo.prefixo}art-${art}`,
        };
    }

    function aplicarFiltro() {
        const raw = filtro.value;
        const q = norm(raw);
        filtroWrap.classList.toggle('tem-texto', raw.trim() !== '');
        saltoAtual = detectarSalto(raw);

        if (saltoAtual) {
            dicaSalto.innerHTML = '';
            const span = document.createElement('span');
            span.textContent = `Pressione Enter para abrir ${saltoAtual.item.sigla}, Art. ${saltoAtual.artigo}`;
            const link = document.createElement('a');
            link.href = saltoAtual.url;
            link.textContent = 'Abrir agora →';
            dicaSalto.append(span, link);
            dicaSalto.classList.add('visivel');
        } else {
            dicaSalto.classList.remove('visivel');
            dicaSalto.innerHTML = '';
        }

        const termos = q ? q.split(' ') : [];
        let totalVisiveis = 0;

        indice.forEach((item) => {
            let bate = true;
            if (saltoAtual) {
                bate = item.chave === saltoAtual.item.chave;
            } else if (termos.length) {
                bate = termos.every((t) => item.texto.includes(t));
            }
            item.el.classList.toggle('oculto', !bate);
            if (bate) totalVisiveis++;
        });

        grupos.forEach((g) => {
            const vis = g.querySelectorAll('.lei:not(.oculto)').length;
            g.classList.toggle('oculto', vis === 0);
            const qtd = g.querySelector('.qtd');
            if (qtd) qtd.textContent = String(vis);
            if (q && vis > 0) g.open = true;
        });

        grupoFixadas.classList.toggle('oculto', lerFavs().length === 0 || raw.trim() !== '');
        vazio.classList.toggle('visivel', totalVisiveis === 0);
    }

    filtro.addEventListener('input', aplicarFiltro);

    filtro.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') {
            filtro.value = '';
            aplicarFiltro();
            filtro.blur();
            return;
        }
        if (ev.key === 'Enter') {
            if (saltoAtual) {
                ev.preventDefault();
                window.location.href = saltoAtual.url;
                return;
            }
            const visiveis = indice.filter((i) => !i.el.classList.contains('oculto'));
            if (visiveis.length === 1) {
                ev.preventDefault();
                window.location.href = visiveis[0].url;
            }
        }
    });

    document.addEventListener('keydown', (ev) => {
        const tag = (document.activeElement?.tagName || '').toLowerCase();
        const editando = tag === 'input' || tag === 'textarea' || tag === 'select' || document.activeElement?.isContentEditable;

        if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'k') {
            ev.preventDefault();
            filtro.focus();
            filtro.select();
        } else if (ev.key === '/' && !editando && !ev.ctrlKey && !ev.metaKey && !ev.altKey) {
            ev.preventDefault();
            filtro.focus();
            filtro.select();
        }
    });

    renderFixadas();
})();
</script>
@endsection
