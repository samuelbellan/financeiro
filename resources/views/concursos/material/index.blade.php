@extends('concursos.layout')

@section('title', 'Resumos')

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,600;7..72,700&display=swap" rel="stylesheet">
@endsection

@section('css')
    @include('concursos.material._estilo')
    .wrap { max-width: 1100px; }
    .intro { margin: 0 0 28px; }

    /* estante: cada matéria é a capa de um livro */
    .estante { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 36px 28px; padding: 4px 4px 8px; }
    .livro-item { display: flex; flex-direction: column; gap: 10px; text-decoration: none; color: var(--ink); perspective: 900px; }
    .capa-livro { --cor: #7f77dd; position: relative; aspect-ratio: 3 / 4.2; border-radius: 3px 10px 10px 3px;
        background:
            radial-gradient(120% 70% at 85% 0%, rgba(255,255,255,.22), transparent 60%),
            linear-gradient(160deg, color-mix(in srgb, var(--cor) 85%, #fff) 0%, var(--cor) 45%, color-mix(in srgb, var(--cor) 68%, #000) 100%);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.08), 6px 10px 22px rgba(16,24,40,.22), 3px 0 0 -1px #f3efe6, 5px 1px 0 -1px #e2ddd2, 7px 2px 0 -1px #d3cdc0;
        transform-origin: left center; transition: transform .25s ease, box-shadow .25s ease; color: #fff; container-type: inline-size; display: flex; flex-direction: column; padding: 18px 14px 14px 26px; }
    /* lombada */
    .capa-livro::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 14px; border-radius: 3px 0 0 3px;
        background: linear-gradient(90deg, rgba(0,0,0,.32), rgba(0,0,0,.12) 55%, rgba(255,255,255,.18) 70%, rgba(0,0,0,.18)); }
    /* moldura gravada */
    .capa-livro::after { content: ''; position: absolute; inset: 10px 10px 10px 22px; border: 1px solid rgba(255,255,255,.35); border-radius: 4px; pointer-events: none; }
    .capa-rotulo { font: 700 10.5px/1 Inter, system-ui, sans-serif; letter-spacing: .14em; text-transform: uppercase; opacity: .85; }
    .capa-titulo { margin: auto 0; font-family: 'Literata', Georgia, serif; font-weight: 700; font-size: clamp(15px, 12cqi, 24px); line-height: 1.15; letter-spacing: -.01em; text-shadow: 0 1px 2px rgba(0,0,0,.25); overflow-wrap: break-word; }
    .capa-titulo.longo { font-size: clamp(13px, 9.6cqi, 20px); }
    .capa-filete { width: 36px; height: 3px; border-radius: 2px; background: rgba(255,255,255,.7); margin-bottom: 10px; }
    .capa-rodape { display: flex; justify-content: space-between; align-items: end; font: 600 11px/1.2 Inter, system-ui, sans-serif; opacity: .9; }
    .capa-sigla { font-family: 'Literata', Georgia, serif; font-size: 22px; font-weight: 700; opacity: .4; line-height: 1; }
    .livro-item:hover .capa-livro, .livro-item:focus-visible .capa-livro { transform: rotateY(-14deg) translateY(-3px);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.08), 14px 16px 30px rgba(16,24,40,.28), 3px 0 0 -1px #f3efe6, 5px 1px 0 -1px #e2ddd2, 7px 2px 0 -1px #d3cdc0; }
    .livro-item:focus-visible { outline: none; } .livro-item:focus-visible .capa-livro { outline: 3px solid var(--accent); outline-offset: 3px; }
    .livro-item { animation: surgir .45s var(--mola) both; animation-delay: calc(var(--i, 0) * 45ms); }
    .livro-legenda { font-size: 14px; font-weight: 600; line-height: 1.3; }
    .livro-legenda span { display: block; font-size: 12.5px; font-weight: 500; color: var(--muted); }
    .livro-vazio { grid-column: 1 / -1; }
    /* "novo livro" como capa em branco */
    .capa-nova { aspect-ratio: 3 / 4.2; border: 2px dashed var(--line); border-radius: 3px 10px 10px 3px; display: grid; place-items: center; text-align: center; color: var(--muted); font-weight: 600; font-size: 14px; background: none; cursor: pointer; font-family: inherit; width: 100%; transition: border-color .15s, color .15s; }
    .capa-nova b { display: block; font-size: 34px; font-weight: 400; line-height: 1; margin-bottom: 6px; }
    .capa-nova:hover { border-color: var(--accent); color: var(--accent); }
    @media (prefers-reduced-motion: reduce) { .capa-livro { transition: none; } .livro-item:hover .capa-livro { transform: none; } }
    @media (max-width: 520px) { .estante { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px 18px; } }

    /* novo livro */
    dialog.novo-livro { border: 1px solid var(--line); border-radius: 14px; background: var(--card); color: var(--ink); padding: 20px; width: min(440px, calc(100% - 32px)); }
    dialog.novo-livro::backdrop { background: rgba(0,0,0,.45); }
    dialog.novo-livro h2 { font-size: 18px; margin: 0 0 4px; }
    dialog.novo-livro input { width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 10px 12px; font: inherit; margin: 12px 0 16px; }
    dialog.novo-livro input:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
    dialog.novo-livro .acoes { display: flex; justify-content: flex-end; gap: 8px; }

@endsection

@section('content')
<div class="cabeca"><h1>Resumos</h1></div>
<p class="muted small intro">Um livro por matéria, com a teoria para ler, grifar e anotar. As questões e a fila do dia ficam em <a href="{{ route('concursos.revisoes') }}">Revisões</a>.</p>

<div class="estante">
    @foreach ($materiais as $m)
        @php
            $n = $capitulos[$m->id] ?? 0;
            $palavras = preg_split('/\s+/u', trim(preg_replace('/\b(de|da|do|das|dos|e|para)\b/iu', ' ', $m->titulo)));
            $sigla = mb_strtoupper(collect($palavras)->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
        @endphp
        <a class="livro-item" style="--i: {{ $loop->index }}" href="{{ route('concursos.material.show', $m) }}" aria-label="Abrir o resumo de {{ $m->titulo }}">
            <span class="capa-livro" style="--cor: {{ $m->cor() }}" aria-hidden="true">
                <span class="capa-rotulo">Resumo</span>
                <span class="capa-titulo{{ collect($palavras)->max(fn ($p) => mb_strlen($p)) >= 12 ? ' longo' : '' }}">{{ $m->titulo }}</span>
                <span class="capa-filete"></span>
                <span class="capa-rodape"><span>{{ $n }} {{ $n === 1 ? 'capítulo' : 'capítulos' }}</span><span class="capa-sigla">{{ $sigla }}</span></span>
            </span>
            <span class="livro-legenda">{{ $m->titulo }}</span>
        </a>
    @endforeach
    <div class="livro-item" style="--i: {{ $materiais->count() }}">
        <button type="button" class="capa-nova" data-novo-livro><span><b>+</b>Novo livro</span></button>
    </div>
    @if ($materiais->isEmpty())
        <p class="muted small livro-vazio">Nenhum livro ainda. Crie um em <b>Novo livro</b> ou importe os cadernos com <code>php artisan concursos:importar-material</code>.</p>
    @endif
</div>

<dialog class="novo-livro" aria-labelledby="novo-livro-titulo">
    <form method="POST" action="{{ route('concursos.material.store') }}">
        @csrf
        <h2 id="novo-livro-titulo">Novo livro</h2>
        <p class="muted small" style="margin:0">Um livro por matéria. Os capítulos você cria depois, por assunto.</p>
        <input name="titulo" required maxlength="160" placeholder="Direito Administrativo" aria-label="Título do novo livro" autocomplete="off">
        <div class="acoes">
            <button type="button" class="btn fantasma" data-fechar>Cancelar</button>
            <button class="btn main" type="submit">Criar livro</button>
        </div>
    </form>
</dialog>
@endsection

@section('js')
<script>
    (() => {
        const dlg = document.querySelector('dialog.novo-livro');
        document.querySelector('[data-novo-livro]').addEventListener('click', () => { dlg.showModal(); dlg.querySelector('input').focus(); });
        dlg.querySelector('[data-fechar]').addEventListener('click', () => dlg.close());
        dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
    })();
</script>
@endsection
