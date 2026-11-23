@extends('concursos.layout')

@section('title', $material->titulo)

@php
    $cit = app(\App\Services\Concursos\Leis\Citacoes::class);
    $rotulos = \App\Models\Concursos\MaterialPonto::STATUS;
@endphp

@section('css')
    .cab h1 { font-size: 24px; line-height: 1.25; margin: 0 0 6px; }
    .cab p { margin: 0 0 12px; }
    .filtros { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top: 12px; }
    .chip { border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 99px; padding: 6px 12px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
    .chip[aria-pressed=true] { background: var(--ink); color: var(--bg); border-color: var(--ink); }
    .chip b { font-variant-numeric: tabular-nums; margin-left: 4px; }
    .busca { flex: 1; min-width: 180px; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 10px; padding: 8px 12px; font: inherit; font-size: 14px; }
    .sumario { columns: 2 260px; gap: 24px; margin: 0; padding: 0; list-style: none; font-size: 14px; }
    .sumario li { break-inside: avoid; padding: 3px 0; }
    .sumario a { text-decoration: none; }
    .sumario .n { color: var(--no); font-weight: 700; font-variant-numeric: tabular-nums; }
    .secao > h2 { font-size: 19px; margin: 0 0 2px; }
    .secao > .origem { font-size: 13px; color: var(--muted); margin-bottom: 12px; }
    .intro { font-size: 15px; border-left: 3px solid var(--accent); padding: 2px 0 2px 12px; margin-bottom: 16px; }
    .intro p { margin: 0 0 6px; }
    .ponto { border-top: 1px solid var(--line); padding: 16px 0; scroll-margin-top: 16px; }
    .ponto:target { background: var(--accent-soft); border-radius: 10px; padding: 16px 12px; }
    .ponto h3 { font-size: 16px; margin: 0; flex: 1; min-width: 200px; }
    .ponto .linha { display: flex; gap: 8px; flex-wrap: wrap; align-items: baseline; margin-bottom: 8px; }
    .tag { font-size: 12px; font-weight: 700; border-radius: 99px; padding: 2px 8px; white-space: nowrap; }
    .tag.errando { background: var(--no-soft); color: var(--no); }
    .tag.revisando { background: var(--warn-soft); color: var(--warn); }
    .tag.dominado { background: var(--ok-soft); color: var(--ok); }
    .tag.vezes { background: var(--line); color: var(--ink); }
    .bloco { display: grid; grid-template-columns: 112px 1fr; gap: 4px 12px; margin: 6px 0; font-size: 15px; }
    .bloco > span { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); padding-top: 3px; }
    .bloco > div > :first-child { margin-top: 0; }
    .bloco > div > :last-child { margin-bottom: 0; }
    .bloco.armadilha > span { color: var(--no); }
    .bloco.regra > span { color: var(--ok); }
    .bloco ul, .intro ul { padding-left: 20px; margin: 4px 0; }
    .bloco table { border-collapse: collapse; font-size: 14px; margin: 6px 0; }
    .bloco th, .bloco td { border: 1px solid var(--line); padding: 4px 8px; text-align: left; vertical-align: top; }
    a.lei { font-weight: 600; text-decoration: underline dotted; text-underline-offset: 3px; }
    .qs { font-size: 13px; color: var(--muted); margin-top: 6px; }
    .qs a { color: var(--muted); }
    .acoes { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top: 10px; }
    .acoes form { margin: 0; }
    .acoes .btn { padding: 6px 12px; font-size: 14px; }
    .seg { display: inline-flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .seg button { border: 0; background: var(--card); color: var(--muted); font: inherit; font-size: 13px; font-weight: 600; padding: 6px 10px; cursor: pointer; }
    .seg button + button { border-left: 1px solid var(--line); }
    .seg button[aria-pressed=true] { background: var(--ink); color: var(--bg); }
    details.edit > summary { cursor: pointer; font-size: 14px; font-weight: 600; color: var(--accent); list-style: none; }
    details.edit > summary::-webkit-details-marker { display: none; }
    .form { display: grid; gap: 8px; margin-top: 10px; }
    .form label { font-size: 13px; font-weight: 600; color: var(--muted); display: grid; gap: 4px; }
    .form input, .form textarea { border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 8px; padding: 8px 10px; font: inherit; font-size: 14px; }
    .form textarea { min-height: 70px; resize: vertical; }
    .dica { font-size: 12px; color: var(--muted); }
    .vazio { color: var(--muted); font-size: 14px; padding: 8px 0; }
    .escondido { display: none !important; }
    dialog.art { border: 1px solid var(--line); border-radius: 14px; background: var(--card); color: var(--ink); max-width: 680px; width: calc(100% - 32px); max-height: 80vh; padding: 0; }
    dialog.art::backdrop { background: rgba(0,0,0,.45); }
    dialog.art .topo { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); position: sticky; top: 0; background: var(--card); }
    dialog.art .topo b { flex: 1; }
    dialog.art .corpo { padding: 4px 16px 16px; font-size: 15px; overflow: auto; }
    @media (max-width: 560px) { .bloco { grid-template-columns: 1fr; } }
    @media print {
        .menu, .no-print, .acoes, details.edit, .filtros, dialog { display: none !important; }
        body { background: #fff; color: #000; font-size: 12px; }
        .card { border: 0; padding: 0; break-inside: auto; }
        .secao { break-before: page; }
        .ponto { break-inside: avoid; padding: 8px 0; }
        a.lei { color: #000; text-decoration: none; }
        .escondido { display: none !important; }
    }
@endsection

@section('content')
<header class="card cab">
    <h1>{{ $material->titulo }}</h1>
    @if ($material->descricao)
        <p class="muted">{{ $material->descricao }}</p>
    @endif
    <div class="filtros" role="group" aria-label="Filtrar pontos">
        <button type="button" class="chip" data-filtro="" aria-pressed="false">Todos<b>{{ $resumo['total'] }}</b></button>
        <button type="button" class="chip" data-filtro="errando" aria-pressed="true">Ainda erro<b data-conta="errando">{{ $resumo['errando'] }}</b></button>
        <button type="button" class="chip" data-filtro="revisando" aria-pressed="false">Revisando<b data-conta="revisando">{{ $resumo['revisando'] }}</b></button>
        <button type="button" class="chip" data-filtro="dominado" aria-pressed="false">Dominado<b data-conta="dominado">{{ $resumo['dominado'] }}</b></button>
        <input type="search" class="busca" placeholder="Buscar (ex.: decadência, art. 133)" aria-label="Buscar no material">
        <button type="button" class="btn no-print" onclick="window.print()">Imprimir / PDF</button>
    </div>
</header>

@if ($material->secoes->isNotEmpty())
<nav class="card no-print" aria-label="Sumário">
    <ol class="sumario">
        @foreach ($material->secoes as $secao)
            @php $nErr = $secao->pontos->where('status', 'errando')->count(); @endphp
            <li><a href="#secao-{{ $secao->id }}">{{ $secao->titulo }}</a> @if ($nErr)<span class="n" title="pontos que ainda erro">· {{ $nErr }}</span>@endif</li>
        @endforeach
    </ol>
</nav>
@endif

@foreach ($material->secoes as $secao)
<section class="card secao" id="secao-{{ $secao->id }}">
    <h2>{{ $secao->titulo }}</h2>
    @if ($secao->origem)<div class="origem">{{ $secao->origem }}</div>@endif
    @if ($secao->intro)<div class="intro">{!! $cit->html($secao->intro) !!}</div>@endif

    @foreach ($secao->pontos as $p)
    <article class="ponto" id="ponto-{{ $p->id }}" data-status="{{ $p->status }}">
        <div class="linha">
            <h3>{{ $p->titulo }}</h3>
            <span class="tag {{ $p->status }}" data-tag>{{ $rotulos[$p->status] }}</span>
            <span class="tag vezes" data-vezes @if ($p->vezes_errado < 2) hidden @endif>errei {{ $p->vezes_errado }}×</span>
        </div>
        @if ($p->armadilha)
            <div class="bloco armadilha"><span>Onde caio</span><div>{!! $cit->html($p->armadilha) !!}</div></div>
        @endif
        <div class="bloco regra"><span>Regra certa</span><div>{!! $cit->html($p->regra) !!}</div></div>
        @if ($p->macete)
            <div class="bloco"><span>Macete</span><div>{!! $cit->html($p->macete) !!}</div></div>
        @endif
        @if ($p->base_legal)
            <div class="bloco"><span>Base legal</span><div>{!! $cit->html($p->base_legal) !!}</div></div>
        @endif
        @if ($p->questoes)
            <div class="qs">Questões:
                @foreach ($p->questoes as $q)<a href="https://www.tecconcursos.com.br/questoes/{{ $q }}" target="_blank" rel="noopener">{{ $q }}</a>@if (!$loop->last), @endif @endforeach
                @if ($p->ultimo_erro_em) · último erro em <span data-data>{{ $p->ultimo_erro_em->format('d/m/Y') }}</span>@endif
            </div>
        @endif

        <div class="acoes">
            <form method="POST" action="{{ route('concursos.material.pontos.errei', $p) }}" data-ajax>
                @csrf
                <button class="btn" type="submit">Errei de novo</button>
            </form>
            <form method="POST" action="{{ route('concursos.material.pontos.status', $p) }}" data-ajax class="seg" role="group" aria-label="Situação">
                @csrf
                @foreach ($rotulos as $valor => $nome)
                    <button type="submit" name="status" value="{{ $valor }}" aria-pressed="{{ $p->status === $valor ? 'true' : 'false' }}">{{ $nome }}</button>
                @endforeach
            </form>
        </div>
        <details class="edit">
            <summary>Editar</summary>
            <form method="POST" action="{{ route('concursos.material.pontos.update', $p) }}" class="form">
                @csrf @method('PUT')
                @include('concursos.material._campos', ['p' => $p])
                <div class="acoes"><button class="btn main" type="submit">Salvar</button></div>
            </form>
            <form method="POST" action="{{ route('concursos.material.pontos.destroy', $p) }}" onsubmit="return confirm('Apagar este ponto?')" style="margin-top:8px">
                @csrf @method('DELETE')
                <button class="btn" type="submit">Apagar ponto</button>
            </form>
        </details>
    </article>
    @endforeach
    <p class="vazio escondido" data-vazio>Nenhum ponto nesta seção com esse filtro.</p>

    <details class="edit no-print" style="margin-top:12px">
        <summary>+ Novo ponto que estou errando</summary>
        <form method="POST" action="{{ route('concursos.material.pontos.store', $secao) }}" class="form">
            @csrf
            @include('concursos.material._campos', ['p' => null])
            <div class="acoes"><button class="btn main" type="submit">Adicionar</button></div>
        </form>
    </details>
</section>
@endforeach

<section class="card no-print">
    <details class="edit">
        <summary>+ Nova seção (ex.: um caderno novo)</summary>
        <form method="POST" action="{{ route('concursos.material.secoes.store', $material) }}" class="form">
            @csrf
            <label>Título <input name="titulo" required maxlength="200" placeholder="C12 · Processo administrativo fiscal"></label>
            <label>Origem <input name="origem" maxlength="200" placeholder="Caderno C12 · 25 questões · 9 erros"></label>
            <div class="acoes"><button class="btn main" type="submit">Criar seção</button></div>
        </form>
    </details>
</section>

<dialog class="art" aria-labelledby="art-titulo">
    <div class="topo"><b id="art-titulo">Artigo</b><a href="#" data-abrir class="btn">Abrir na lei</a><button type="button" class="btn" data-fechar>Fechar</button></div>
    <div class="corpo" data-corpo></div>
</dialog>
@endsection

@section('js')
<script>
(() => {
    const pontos = [...document.querySelectorAll('.ponto')];
    const chips = [...document.querySelectorAll('.chip[data-filtro]')];
    const busca = document.querySelector('.busca');
    let filtro = 'errando';
    try { filtro = localStorage.getItem('material-filtro-{{ $material->id }}') ?? filtro; } catch (e) {}
    const semAcento = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    function aplicar() {
        const q = semAcento(busca.value.trim());
        chips.forEach(c => c.setAttribute('aria-pressed', String(c.dataset.filtro === filtro)));
        pontos.forEach(p => {
            const ok = (!filtro || p.dataset.status === filtro) && (!q || semAcento(p.textContent).includes(q));
            p.classList.toggle('escondido', !ok);
        });
        document.querySelectorAll('.secao').forEach(s => {
            const visiveis = s.querySelectorAll('.ponto:not(.escondido)').length;
            s.querySelector('[data-vazio]').classList.toggle('escondido', visiveis > 0 || !s.querySelector('.ponto'));
        });
    }
    chips.forEach(c => c.addEventListener('click', () => {
        filtro = c.dataset.filtro;
        try { localStorage.setItem('material-filtro-{{ $material->id }}', filtro); } catch (e) {}
        aplicar();
    }));
    busca.addEventListener('input', aplicar);
    if (location.hash.startsWith('#ponto-')) filtro = '';
    aplicar();

    const rotulos = @json($rotulos);
    function contar() {
        for (const s of Object.keys(rotulos)) {
            const el = document.querySelector(`[data-conta="${s}"]`);
            if (el) el.textContent = pontos.filter(p => p.dataset.status === s).length;
        }
    }

    document.querySelectorAll('form[data-ajax]').forEach(form => form.addEventListener('submit', async ev => {
        ev.preventDefault();
        const dados = new FormData(form);
        if (ev.submitter?.name) dados.set(ev.submitter.name, ev.submitter.value);
        const resp = await fetch(form.action, { method: 'POST', body: dados, headers: { 'Accept': 'application/json' } });
        if (!resp.ok) { alert('Não consegui salvar. Recarregue a página.'); return; }
        const r = await resp.json();
        const p = form.closest('.ponto');
        p.dataset.status = r.status;
        const tag = p.querySelector('[data-tag]');
        tag.className = 'tag ' + r.status;
        tag.textContent = rotulos[r.status];
        const vezes = p.querySelector('[data-vezes]');
        vezes.textContent = `errei ${r.vezes_errado}×`;
        vezes.hidden = r.vezes_errado < 2;
        const data = p.querySelector('[data-data]');
        if (data && r.ultimo_erro_em) data.textContent = r.ultimo_erro_em;
        p.querySelectorAll('.seg button').forEach(b => b.setAttribute('aria-pressed', String(b.value === r.status)));
        contar();
    }));

    const dlg = document.querySelector('dialog.art');
    const base = @json(url('/concursos/legislacao'));
    dlg.querySelector('[data-fechar]').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    document.addEventListener('click', async e => {
        const a = e.target.closest('a.lei');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        const corpo = dlg.querySelector('[data-corpo]');
        dlg.querySelector('#art-titulo').textContent = a.textContent;
        dlg.querySelector('[data-abrir]').href = a.href;
        corpo.textContent = 'Carregando…';
        dlg.showModal();
        try {
            const resp = await fetch(`${base}/${a.dataset.lei}/artigos/${encodeURIComponent(a.dataset.art)}`, { headers: { 'Accept': 'application/json' } });
            const r = await resp.json();
            if (!resp.ok) { corpo.textContent = r.erro ?? 'Artigo não encontrado no texto da lei.'; return; }
            dlg.querySelector('#art-titulo').textContent = `${r.lei}, ${r.rotulo}`;
            corpo.innerHTML = r.html;
        } catch (err) {
            corpo.textContent = 'Não consegui carregar o artigo.';
        }
    });
})();
</script>
@endsection
