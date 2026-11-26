@extends('concursos.layout')

@section('title', $capitulo?->titulo ?? $material->titulo)

@php
    $filhos = $plano->groupBy(fn ($c) => $c['parent_id'] ?? 0);
    $atual = $capitulo?->id;
    $rotulos = \App\Models\Concursos\MaterialPonto::STATUS;
    $dados = [
        'csrf' => csrf_token(),
        'livro' => ['id' => $material->id, 'titulo' => $material->titulo, 'slug' => $material->slug],
        'urls' => [
            'criar' => route('concursos.material.capitulos.store', $material),
            'ordem' => route('concursos.material.capitulos.ordem', $material),
            'buscar' => route('concursos.material.buscar', $material),
            'renomearCap' => route('concursos.material.capitulos.update', 0),
            'apagarCap' => route('concursos.material.capitulos.destroy', 0),
            'renomear' => $capitulo ? route('concursos.material.capitulos.update', $capitulo) : null,
        ],
        'editor' => $capitulo ? $dadosEditor + ['titulo' => $capitulo->titulo] : null,
    ];
@endphp

@section('head')
    <link href="https://fonts.googleapis.com/css2?family=Literata:ital,opsz,wght@0,7..72,400..700;1,7..72,400..700&display=swap" rel="stylesheet">
    <style>body { --disc: {{ $material->cor() }}; }</style>
@endsection

@section('css')
    @include('concursos.material._estilo')
    .wrap { max-width: 1680px; }
    .livro { display: grid; grid-template-columns: 270px minmax(0, 1fr) 250px; gap: 24px; align-items: start; }
    .livro.capa { grid-template-columns: 270px minmax(0, 1fr); }

    /* ---- sumário ---- */
    .livro-lateral { position: sticky; top: 12px; max-height: calc(100vh - 24px); overflow: auto; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px 10px 14px 14px; font-size: 14px; }
    .lat-topo { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .lat-topo a { font-weight: 700; color: var(--ink); text-decoration: none; line-height: 1.3; flex: 1; }
    .lat-topo .fechar { display: none; }
    .lat-topo .recolher { padding: 4px 8px; color: var(--muted); }
    .livro-lateral { scrollbar-width: thin; scrollbar-color: var(--line) transparent; }
    .livro-lateral::-webkit-scrollbar { width: 8px; }
    .livro-lateral::-webkit-scrollbar-track { background: transparent; }
    .livro-lateral::-webkit-scrollbar-thumb { background: var(--line); border-radius: 99px; border: 2px solid var(--card); }
    .livro-lateral::-webkit-scrollbar-thumb:hover { background: var(--muted); }
    .busca-caixa { position: relative; margin-bottom: 10px; }
    .busca-livro { width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 34px 8px 10px; font: inherit; font-size: 14px; }
    .busca-livro:focus { outline: 2px solid var(--accent); outline-offset: 0; border-color: transparent; }
    .busca-caixa kbd { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); pointer-events: none; font: 600 11px/1 Inter, system-ui, sans-serif; color: var(--muted); border: 1px solid var(--line); border-bottom-width: 2px; border-radius: 5px; padding: 3px 6px; background: var(--card); }
    .busca-livro:focus + kbd, .busca-livro:not(:placeholder-shown) + kbd { display: none; }
    .expandir-lateral { display: none; }
    @media (min-width: 1181px) {
        body.lateral-recolhida .livro { grid-template-columns: minmax(0, 1fr) 250px; }
        body.lateral-recolhida .livro.capa { grid-template-columns: minmax(0, 1fr); }
        body.lateral-recolhida .livro-lateral { display: none; }
        body.lateral-recolhida .expandir-lateral { display: inline-flex; }
    }
    .resultados .res { display: block; text-decoration: none; color: var(--ink); padding: 8px; border-radius: 8px; }
    .resultados .res:hover { background: var(--accent-soft); }
    .resultados .res span { display: block; color: var(--muted); font-size: 13px; }
    .sumario-livro ol { list-style: none; margin: 0; padding: 0; }
    .sumario-livro li li { padding-left: 12px; border-left: 1px solid var(--line); margin-left: 8px; }
    .cap-linha { display: flex; align-items: center; border-radius: 8px; position: relative; }
    .cap-linha > a { flex: 1; display: flex; gap: 6px; align-items: baseline; padding: 6px 6px; color: var(--ink); text-decoration: none; min-width: 0; }
    .cap-linha:hover, .cap-linha.atual { background: var(--accent-soft); }
    .cap-linha.atual > a { font-weight: 700; }
    .cap-num { color: var(--muted); font-variant-numeric: tabular-nums; font-size: 12px; flex: none; }
    .cap-nome { overflow-wrap: anywhere; }
    .cap-err { margin-left: auto; flex: none; background: var(--no-soft); color: var(--no); font-weight: 700; font-size: 12px; border-radius: 99px; padding: 0 7px; }
    .cap-menu > summary { list-style: none; cursor: pointer; padding: 2px 8px; border-radius: 6px; color: var(--muted); opacity: 0; }
    .cap-menu > summary::-webkit-details-marker { display: none; }
    .cap-linha:hover .cap-menu > summary, .cap-menu[open] > summary, .cap-menu > summary:focus-visible { opacity: 1; }
    .cap-menu-lista { position: absolute; right: 0; top: 100%; z-index: 20; background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.15); padding: 4px; min-width: 220px; }
    .cap-menu-lista button { display: block; width: 100%; text-align: left; border: 0; background: none; color: var(--ink); font: inherit; font-size: 14px; padding: 7px 10px; border-radius: 6px; cursor: pointer; }
    .cap-menu-lista button:hover { background: var(--accent-soft); }
    .cap-menu-lista .perigo { color: var(--no); }
    .novo-cap { margin-top: 8px; width: 100%; border: 1px dashed var(--line); background: none; color: var(--accent); font: inherit; font-weight: 600; font-size: 14px; padding: 8px; border-radius: 10px; cursor: pointer; }
    .lat-rodape { border-top: 1px solid var(--line); margin-top: 12px; padding-top: 10px; display: grid; gap: 4px; }
    .lat-rodape a { text-decoration: none; padding: 4px 6px; border-radius: 6px; }
    .lat-rodape a:hover { background: var(--accent-soft); }

    /* ---- página ---- */
    .livro-pagina { min-width: 0; }
    .pag-topo { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
    .migalhas { flex: 1; min-width: 160px; font-size: 13px; color: var(--muted); }
    .migalhas a { color: var(--muted); text-decoration: none; }
    .migalhas a:hover { text-decoration: underline; }
    .salvo { font-size: 13px; color: var(--muted); }
    .salvo.erro { color: var(--no); font-weight: 600; }
    .btn.mini { padding: 5px 10px; font-size: 13px; border-radius: 8px; }
    .abrir-sumario { display: none; }
    .pagina { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 36px 48px 48px; min-height: 60vh; }
    .pagina h1 { font-size: 28px; line-height: 1.25; margin: 0 0 20px; letter-spacing: -.01em; outline: none; }
    body.editando .pagina h1 { border-bottom: 1px dashed var(--line); }
    .pagina-texto, [data-estatico] { font-size: 17px; line-height: 1.7; outline: none; max-width: 100%; }
    .pagina-texto > * + *, [data-estatico] > * + * { margin-top: .75em; }
    .pagina-texto p, [data-estatico] p { margin: 0; }
    .pagina-texto h2, [data-estatico] h3 { font-size: 22px; margin: 1.4em 0 .4em; line-height: 1.3; }
    .pagina-texto h3, [data-estatico] h4 { font-size: 18px; margin: 1.2em 0 .3em; }
    .pagina-texto h4, [data-estatico] h5 { font-size: 16px; margin: 1em 0 .2em; text-transform: uppercase; letter-spacing: .03em; color: var(--muted); }
    .pagina-texto ul, .pagina-texto ol, [data-estatico] ul, [data-estatico] ol { padding-left: 1.4em; margin: .4em 0; }
    .pagina-texto blockquote, [data-estatico] blockquote { border-left: 3px solid var(--line); margin: .8em 0; padding: 2px 0 2px 14px; color: var(--muted); }
    .pagina-texto hr, [data-estatico] hr { border: 0; border-top: 1px solid var(--line); margin: 1.6em 0; }
    .pagina-texto img, [data-estatico] img { max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 8px auto; }
    .pagina-texto img.ProseMirror-selectednode { outline: 3px solid var(--accent); }
    .pagina-texto table, [data-estatico] table { border-collapse: collapse; width: 100%; font-size: 15px; margin: .8em 0; display: block; overflow-x: auto; }
    .pagina-texto th, .pagina-texto td, [data-estatico] th, [data-estatico] td { border: 1px solid var(--line); padding: 6px 10px; vertical-align: top; min-width: 80px; }
    .pagina-texto th, [data-estatico] th { background: var(--bg); text-align: left; }
    .pagina-texto .selectedCell { background: var(--accent-soft); }
    .pagina-texto p.is-empty:first-child::before, body.editando .pagina-texto p.is-empty::before { content: attr(data-placeholder); color: var(--muted); float: left; height: 0; pointer-events: none; }
    body:not(.editando) .pagina-texto p.is-empty:not(:first-child)::before { content: none; }
    ::highlight(busca) { background: #ffe066; color: #000; }

    /* marca-texto */
    mark.grifo { border-radius: 3px; padding: 0 1px; color: inherit; }
    mark.grifo.amarelo { background: #fff0a3; }
    mark.grifo.verde { background: #c8f2d4; }
    mark.grifo.azul { background: #cfe3ff; }
    mark.grifo.rosa { background: #ffd3e4; }
    mark.grifo[data-nota] { border-bottom: 2px solid rgba(0,0,0,.35); cursor: pointer; }
    :root[data-theme=dark] mark.grifo.amarelo { background: #5c4d0f; } :root[data-theme=dark] mark.grifo.verde { background: #1d4a2c; }
    :root[data-theme=dark] mark.grifo.azul { background: #1d3557; } :root[data-theme=dark] mark.grifo.rosa { background: #5a2238; }
    :root[data-theme=dark] mark.grifo[data-nota] { border-bottom-color: rgba(255,255,255,.5); }
    .cor { display: inline-block; width: 14px; height: 14px; border-radius: 4px; vertical-align: middle; border: 1px solid rgba(0,0,0,.15); }
    .cor.amarelo { background: #ffe066; } .cor.verde { background: #7bd99a; } .cor.azul { background: #8bbcff; } .cor.rosa { background: #ff9cc2; }
    .piscar { animation: piscar 1.2s; }
    @keyframes piscar { 0%, 60% { box-shadow: 0 0 0 4px var(--accent); } 100% { box-shadow: 0 0 0 0 transparent; } }

    /* caixas de destaque */
    .caixa { border-radius: 10px; padding: 10px 14px 12px; margin: 1em 0; border-left: 4px solid var(--line); background: var(--bg); }
    .caixa-rotulo { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 4px; display: flex; gap: 8px; align-items: center; user-select: none; }
    .caixa-rotulo .caixa-tipo { display: none; font: inherit; text-transform: none; letter-spacing: 0; font-weight: 600; border: 1px solid var(--line); border-radius: 6px; background: var(--card); color: var(--ink); padding: 1px 4px; }
    body.editando .caixa-rotulo .caixa-tipo { display: inline-block; }
    body.editando .caixa-rotulo > span { display: none; }
    .caixa-corpo > * + * { margin-top: .5em; }
    .caixa.pegadinha { border-color: var(--no); background: var(--no-soft); } .caixa.pegadinha .caixa-rotulo { color: var(--no); }
    .caixa.regra { border-color: var(--ok); background: var(--ok-soft); } .caixa.regra .caixa-rotulo { color: var(--ok); }
    .caixa.macete { border-color: #8a5cf6; background: color-mix(in srgb, #8a5cf6 12%, var(--card)); } .caixa.macete .caixa-rotulo { color: #7c4ddb; }
    .caixa.atencao { border-color: var(--warn); background: var(--warn-soft); } .caixa.atencao .caixa-rotulo { color: var(--warn); }
    .caixa.exemplo { border-color: var(--accent); background: var(--accent-soft); } .caixa.exemplo .caixa-rotulo { color: var(--accent); }
    .caixa.base { border-color: var(--muted); }

    /* ponto de erro */
    .ponto-erro { border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin: 1.2em 0; scroll-margin-top: 16px; background: var(--card); }
    .ponto-erro[data-status=errando] { border-left: 4px solid var(--no); }
    .ponto-erro[data-status=revisando] { border-left: 4px solid var(--warn); }
    .ponto-erro[data-status=dominado] { border-left: 4px solid var(--ok); }
    .ponto-erro > header { display: grid; gap: 8px; margin-bottom: 6px; user-select: none; }
    .ponto-linha { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .ponto-titulo-ler { font-size: 18px; margin: 0 !important; flex: 1; min-width: 180px; line-height: 1.35; }
    .ponto-titulo { display: none; flex: 1; min-width: 180px; font: inherit; font-size: 17px; font-weight: 700; border: 1px solid var(--line); border-radius: 8px; padding: 4px 8px; background: var(--bg); color: var(--ink); }
    body.editando .ponto-titulo { display: block; }
    body.editando .ponto-titulo-ler { display: none; }
    .ponto-acoes { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
    .tag { font-size: 12px; font-weight: 700; border-radius: 99px; padding: 2px 8px; white-space: nowrap; }
    .tag.errando { background: var(--no-soft); color: var(--no); }
    .tag.revisando { background: var(--warn-soft); color: var(--warn); }
    .tag.dominado { background: var(--ok-soft); color: var(--ok); }
    .tag.vezes { background: var(--line); color: var(--ink); }
    .seg { display: inline-flex; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
    .seg button { border: 0; background: var(--card); color: var(--muted); font: inherit; font-size: 13px; font-weight: 600; padding: 5px 9px; cursor: pointer; }
    .seg button + button { border-left: 1px solid var(--line); }
    .seg button[aria-pressed=true] { background: var(--ink); color: var(--bg); }

    /* questão no texto */
    .questao-livro { border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin: 1.2em 0; background: var(--bg); font-size: 15px; line-height: 1.55; }
    .questao-livro.ProseMirror-selectednode { outline: 3px solid var(--accent); }
    .q-meta { font-weight: 500; text-transform: none; letter-spacing: 0; }
    .q-enun { white-space: pre-line; margin: 4px 0 10px !important; }
    .q-opts { display: grid; gap: 6px; }
    .q-opt { display: flex; gap: 10px; text-align: left; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 10px; padding: 8px 12px; font: inherit; cursor: pointer; white-space: pre-line; }
    .q-opt:hover:not(:disabled) { border-color: var(--accent); }
    .q-opt.certa { border-color: var(--ok); background: var(--ok-soft); }
    .q-opt.errada { border-color: var(--no); background: var(--no-soft); }
    .q-ret { margin-top: 10px; }
    .q-ret .ok { color: var(--ok); font-weight: 700; } .q-ret .no { color: var(--no); font-weight: 700; }
    .q-ret pre { white-space: pre-wrap; font: inherit; font-size: 14px; }
    .q-acoes { display: flex; gap: 6px; margin-top: 8px; }
    a.lei { font-weight: 600; text-decoration: underline dotted; text-underline-offset: 3px; cursor: pointer; }
    a.lei.ProseMirror-selectednode { outline: 2px solid var(--accent); border-radius: 3px; }
    .lei-sem { color: var(--muted); text-decoration: line-through dotted; }

    /* barra de ferramentas */
    .barra { position: sticky; top: 0; z-index: 15; display: flex; flex-wrap: wrap; gap: 6px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 6px; margin-bottom: 10px; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
    .tb-grupo { display: flex; gap: 2px; align-items: center; padding-right: 6px; border-right: 1px solid var(--line); }
    .tb-grupo:last-child { border-right: 0; }
    .tb { border: 0; background: none; color: var(--ink); font: inherit; font-size: 14px; font-weight: 600; min-width: 32px; height: 32px; padding: 0 7px; border-radius: 7px; cursor: pointer; }
    .tb:hover { background: var(--bg); }
    .tb[aria-pressed=true] { background: var(--accent-soft); color: var(--accent); }
    .tb-sel { font: inherit; font-size: 14px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--ink); height: 32px; padding: 0 6px; }

    /* bolha de grifo e menu "/" */
    .bolha, .slash { position: absolute; z-index: 50; background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
    .bolha { display: flex; gap: 2px; padding: 4px; }
    .bolha button { border: 0; background: none; color: var(--ink); font: inherit; font-size: 13px; font-weight: 600; padding: 6px 8px; border-radius: 7px; cursor: pointer; }
    .bolha button:hover { background: var(--bg); }
    /* aula em PDF abaixo do resumo (caderno de TI) */
    .aula-pdf { margin-top: 20px; padding: 14px; }
    .aula-pdf-topo { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 12px; }
    .aula-pdf-topo h2 { margin: 0; font-size: 17px; }
    .aula-pdf-topo a { margin-left: auto; }
    .aula-pdf-paginas { display: flex; flex-direction: column; gap: 10px; }
    .aula-pdf-pagina { position: relative; width: 100%; background: #fff; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; }
    .aula-pdf-pagina canvas { display: block; width: 100%; height: auto; }
    .aula-pdf-pagina small { position: absolute; right: 8px; bottom: 6px; padding: 1px 6px; border-radius: 6px; background: rgba(0,0,0,.55); color: #fff; font-size: 11px; }

    /* modo caneta (grifar com a caneta) */
    .caneta-paleta { position: fixed; left: 50%; bottom: 16px; transform: translateX(-50%); z-index: 60; display: flex; gap: 4px; padding: 6px; background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,.2); }
    .caneta-paleta[hidden] { display: none; }
    .caneta-paleta button { border: 0; background: none; color: var(--ink); font: inherit; font-size: 16px; font-weight: 600; min-width: 44px; height: 44px; border-radius: 10px; cursor: pointer; }
    .caneta-paleta button[aria-pressed=true] { background: var(--accent-soft); outline: 2px solid var(--accent); }
    .caneta-paleta .cor { width: 22px; height: 22px; border-radius: 6px; }
    .caneta-previa i { position: absolute; z-index: 40; pointer-events: none; border-radius: 3px; opacity: .55; }
    .caneta-previa.amarelo i { background: #ffe066; } .caneta-previa.verde i { background: #7bd99a; }
    .caneta-previa.azul i { background: #8bbcff; } .caneta-previa.rosa i { background: #ff9cc2; }
    .caneta-previa.apagar i { background: transparent; outline: 2px dashed var(--no); opacity: .8; }
    body.caneta-ligada:not(.editando) .pagina-texto { -webkit-user-select: none; user-select: none; }
    .caneta-debug { position: fixed; top: 8px; right: 8px; z-index: 90; max-width: 70vw; margin: 0; padding: 8px; font: 11px/1.35 ui-monospace, monospace; background: rgba(0,0,0,.82); color: #9f9; border-radius: 8px; pointer-events: none; white-space: pre-wrap; }
    body.caneta-ligada [data-caneta] { color: var(--accent); }

    /* bloco de rascunho à mão */
    .pagina-texto .rascunho { margin: 1em 0; border: 1px solid var(--line); border-radius: 12px; background: var(--card); overflow: hidden; }
    .rascunho-barra { display: flex; flex-wrap: wrap; align-items: center; gap: 2px; padding: 4px 6px; border-bottom: 1px solid var(--line); background: var(--bg); font: 600 13px/1 Inter, system-ui, sans-serif; }
    .rascunho-barra .rascunho-rotulo { color: var(--muted); margin-right: 6px; }
    .rascunho-barra .esp { flex: 1; }
    .rascunho-barra button { border: 0; background: none; color: var(--ink); font: inherit; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 8px; cursor: pointer; }
    .rascunho-barra button:disabled { opacity: .35; cursor: default; }
    .rascunho-barra button[aria-pressed=true] { background: var(--accent-soft); outline: 2px solid var(--accent); }
    .rascunho-barra .so-editando { display: none; }
    body.editando .rascunho-barra .so-editando { display: inline-block; color: var(--no); }
    .rascunho-barra .tinta { display: inline-block; width: 16px; height: 16px; border-radius: 50%; vertical-align: middle; }
    .rascunho-barra .tinta.preto { background: var(--ink); } .rascunho-barra .tinta.azul { background: #2563eb; }
    .rascunho-barra .tinta.vermelho { background: #dc2626; } .rascunho-barra .tinta.verde { background: #16a34a; }
    .rascunho-barra .tinta.marca { background: #facc15; border-radius: 3px; width: 10px; height: 18px; }
    .rascunho-folha { display: block; width: 100%; height: auto; touch-action: pan-y; cursor: crosshair;
        background-image: radial-gradient(color-mix(in srgb, var(--muted) 35%, transparent) 1px, transparent 1px); background-size: 24px 24px; }
    .rascunho-folha path.tinta { fill: var(--ink); }
    .rascunho-folha path.tinta.azul { fill: #2563eb; } .rascunho-folha path.tinta.vermelho { fill: #dc2626; }
    .rascunho-folha path.tinta.verde { fill: #16a34a; } .rascunho-folha path.tinta.marca { fill: #facc15; opacity: .45; }
    .rascunho-folha path.apagando { opacity: .15; }
    .slash { width: 280px; max-height: 320px; overflow: auto; padding: 4px; }
    .slash button { display: block; width: 100%; text-align: left; border: 0; background: none; color: var(--ink); font: inherit; font-size: 14px; padding: 6px 10px; border-radius: 7px; cursor: pointer; }
    .slash button small { display: block; color: var(--muted); font-size: 12px; }
    .slash button[aria-selected=true] { background: var(--accent-soft); }
    [hidden] { display: none !important; }

    /* notas na margem */
    .margem h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 6px 0 8px; }
    .margem .dica-notas { font-size: 13px; color: var(--muted); }
    .margem:not(.vazia) .dica-notas { display: none; }
    .margem { position: static; }
    .notas-lista { position: relative; }
    .nota { position: absolute; left: 0; right: 0; background: var(--card); border: 1px solid var(--line); border-left: 4px solid #ffe066; border-radius: 10px; padding: 8px 10px; font-size: 14px; cursor: pointer; transition: top .15s; }
    .nota.verde { border-left-color: #7bd99a; } .nota.azul { border-left-color: #8bbcff; } .nota.rosa { border-left-color: #ff9cc2; }
    .nota blockquote { margin: 0 0 4px; font-size: 12px; color: var(--muted); }
    .nota-texto { margin: 0; white-space: pre-wrap; }
    .nota-acoes { display: flex; gap: 8px; margin-top: 4px; }
    .nota .link { border: 0; background: none; color: var(--accent); font: inherit; font-size: 12px; font-weight: 600; padding: 0; cursor: pointer; }
    .nota textarea { width: 100%; font: inherit; font-size: 14px; border: 1px solid var(--line); border-radius: 8px; padding: 6px 8px; background: var(--bg); color: var(--ink); resize: vertical; }

    /* questões do capítulo, navegação */
    .questoes-cap { margin-top: 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .questoes-cap p { margin: 0; flex: 1; min-width: 200px; }
    .navega { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
    .navega a { display: block; text-decoration: none; background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; color: var(--ink); }
    .navega a:hover { border-color: var(--accent); }
    .navega small { display: block; color: var(--muted); font-size: 12px; }
    .navega .prox { text-align: right; grid-column: 2; }

    /* capa */
    .capa-topo { padding: 24px; }
    .capa-topo .migalhas { display: block; margin-bottom: 8px; }
    .capa-cabeca { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
    .capa-cabeca > div:first-child { flex: 1; min-width: 240px; }
    .capa-topo h1 { font-size: 28px; margin: 0 0 4px; letter-spacing: -.015em; line-height: 1.2; }
    .capa-acoes { display: flex; gap: 8px; flex-wrap: wrap; }
    .capa-acoes [data-continuar] span { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .indicadores { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin: 20px 0 14px; }
    .ind { display: block; text-align: left; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 12px; padding: 10px 12px; font: inherit; text-decoration: none; }
    .ind small { display: block; font-size: 12px; color: var(--muted); }
    .ind b { display: block; font-size: 22px; font-variant-numeric: tabular-nums; line-height: 1.3; }
    .ind.err b { color: var(--no); } .ind.rev b { color: var(--warn); } .ind.dom b { color: var(--ok); }
    button.ind, a.ind { cursor: pointer; transition: border-color .15s; }
    button.ind:hover, a.ind:hover { border-color: var(--accent); }
    .ind[aria-pressed=true] { border-color: var(--accent); background: var(--accent-soft); }
    .progresso { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--muted); }
    .progresso .seg { flex: 1; height: 8px; }

    .sum-controles { display: flex; gap: 8px 16px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; }
    .sum-controles h2 { font-size: 18px; margin: 0; flex: 1; min-width: 120px; }
    .alternar { display: inline-flex; background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 3px; gap: 2px; max-width: 100%; overflow-x: auto; }
    .alternar button { border: 0; background: none; color: var(--muted); font: inherit; font-size: 13px; font-weight: 600; padding: 5px 10px; border-radius: 7px; cursor: pointer; white-space: nowrap; }
    .alternar button:hover { color: var(--ink); }
    .alternar button[aria-pressed=true] { background: var(--card); color: var(--ink); box-shadow: 0 1px 2px rgba(0,0,0,.12); }
    .indice { list-style: none; margin: 0; padding: 0; }
    .indice li { padding-left: calc(var(--nivel) * 20px); }
    .indice li a { display: flex; align-items: center; gap: 10px; padding: 9px 10px; color: var(--ink); text-decoration: none; border-radius: 10px; }
    .indice li a:hover, .indice li a:focus-visible { background: var(--accent-soft); outline: none; }
    .indice li[data-nivel="0"] { margin-top: 4px; }
    .indice li[data-nivel="0"] .ind-nome { font-weight: 600; }
    .indice .cap-num { min-width: 30px; }
    .ind-nome { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .ind-meta { display: flex; align-items: center; gap: 8px; flex: none; font-size: 12px; color: var(--muted); }
    .critico { width: 56px; height: 4px; border-radius: 99px; background: var(--line); overflow: hidden; }
    .critico i { display: block; height: 100%; background: var(--no); border-radius: 99px; }
    .ind-seta { color: var(--muted); opacity: 0; transition: opacity .15s; font-size: 18px; line-height: 1; }
    .indice li a:hover .ind-seta, .indice li a:focus-visible .ind-seta { opacity: 1; }
    .indice.plano li { padding-left: 0; }
    .indice-vazio { padding: 16px 10px; color: var(--muted); font-size: 14px; margin: 0; }
    @media (max-width: 860px) {
        .indicadores { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .indicadores > :last-child { grid-column: 1 / -1; }
        .capa-topo { padding: 18px; }
        .capa-topo h1 { font-size: 23px; }
        .critico { display: none; }
        .ind-seta { opacity: 1; }
    }

    /* ==== leitura: tipografia de livro, blocos, erros no texto, progresso, foco ==== */
    .livro { --disc-soft: color-mix(in srgb, var(--disc) 12%, var(--card)); --serif: 'Literata', 'Source Serif 4', Georgia, serif; }
    .migalhas a { color: color-mix(in srgb, var(--disc) 80%, var(--ink)); }
    .pagina { border: 0; background: var(--card); box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 6px 24px rgba(16,24,40,.05); position: relative; --pad-x: 56px; padding: 64px var(--pad-x) 56px; }
    :root[data-theme=dark] .pagina { box-shadow: none; }
    .pagina h1 { font-family: var(--serif); font-size: 32px; font-weight: 700; letter-spacing: -.015em; max-width: 34ch; }
    .pagina-texto, [data-estatico] { font-family: var(--serif); font-size: 18px; line-height: 1.7; max-width: 100%; font-optical-sizing: auto; }
    .pagina-texto h2, .pagina-texto h3, [data-estatico] h3, [data-estatico] h4 { font-family: var(--serif); letter-spacing: -.01em; }
    .pagina-texto h2 { font-size: 25px; margin-top: 1.8em; }
    .pagina-texto h3 { font-size: 20px; margin-top: 1.5em; }
    .pagina-texto h4, [data-estatico] h5 { font-family: Inter, system-ui, sans-serif; font-size: 12.5px; font-weight: 700; letter-spacing: .09em; color: var(--disc); margin-top: 1.6em; }
    .pagina-texto .sec[data-num]::before { content: attr(data-num); font-family: Inter, system-ui, sans-serif; font-size: .62em; font-weight: 700; color: var(--disc); margin-right: .6em; vertical-align: .18em; letter-spacing: 0; }
    .pagina-texto h4.sec[data-num]::before { font-size: 1em; vertical-align: 0; opacity: .7; }
    .pagina-texto strong, [data-estatico] strong { font-weight: 650; color: color-mix(in srgb, var(--disc) 70%, var(--ink)); }

    /* inspirado em livro digital: coluna larga justificada, seções em faixa, página sem cartão no claro */
    @media (min-width: 861px) {
        .pagina-texto, [data-estatico] { text-align: justify; hyphens: auto; line-height: 1.65; }
        .pagina-texto h2, .pagina-texto h3, .pagina-texto h4, .pagina-texto table, .pagina-texto td, .pagina-texto th { text-align: left; hyphens: manual; }
    }
    .pagina-texto h2, [data-estatico] h3 { background: var(--disc); color: #fff; text-align: center; padding: .45em .8em; border-radius: 8px; line-height: 1.3; }
    .pagina-texto h2.sec[data-num]::before { color: inherit; opacity: .75; }
    :root[data-theme=light] .livro-pagina .pagina { box-shadow: none; border: 1px solid var(--line); }
    .pagina-texto table, [data-estatico] table { font-family: Inter, system-ui, sans-serif; font-size: 14.5px; }
    body:not(.editando) .pagina-texto .titulo-repetido { display: none; }
    body:not(.editando) .pagina-texto > .titulo-repetido + * { margin-top: 0; }
    .pagina-texto .chip-base { font-family: Inter, system-ui, sans-serif; font-size: 0 !important; line-height: 1; margin: 1.6em 0 .6em; }
    .pagina-texto .chip-base::before { content: '📖 Base teórica · ' attr(data-fonte); display: inline-block; font-size: 12px; font-weight: 600; letter-spacing: .02em; color: var(--disc); background: var(--disc-soft); border-radius: 99px; padding: 5px 11px; }
    .pagina-texto > .chip-base:first-child { position: absolute; top: 26px; left: var(--pad-x); margin: 0; }
    body.editando .pagina-texto .chip-base { font-size: 14px !important; } body.editando .pagina-texto .chip-base::before { content: none; }
    body.editando .pagina-texto > .chip-base:first-child { position: static; }
    body:not(.editando) .pagina-texto.ProseMirror { position: static; }

    /* blocos semânticos (citações classificadas na leitura + caixas do editor) */
    .pagina-texto blockquote.callout, .caixa { --c: var(--disc); border-left: 3px solid var(--c); background: color-mix(in srgb, var(--c) 8%, transparent); color: var(--ink); padding: .8rem 1rem; border-radius: 0 8px 8px 0; margin: 1.1rem 0; }
    .pagina-texto blockquote.callout::before { content: attr(data-rotulo); display: block; font: 700 11.5px/1.4 Inter, system-ui, sans-serif; letter-spacing: .06em; text-transform: uppercase; color: var(--c); margin-bottom: .35rem; }
    .pagina-texto blockquote.callout > * + * { margin-top: .5em; }
    .callout.pegadinha, .caixa.pegadinha, .caixa.atencao { --c: #f5a524; background: color-mix(in srgb, #f5a524 14%, transparent); }
    .callout.pegadinha { box-shadow: 0 0 0 1px color-mix(in srgb, #f5a524 30%, transparent); }
    .callout.doutrina, .caixa.doutrina, .caixa.macete { --c: #a78bfa; }
    .callout.jurisprudencia, .caixa.jurisprudencia { --c: #0ea5a4; }
    .callout.leiseca, .caixa.leiseca, .caixa.base { --c: #94a3b8; }
    .callout.leiseca { font-size: .95em; }
    .callout.definicao, .caixa.definicao { --c: #3b82f6; }
    .caixa.regra { --c: var(--ok); } .caixa.exemplo { --c: var(--disc); }
    .caixa .caixa-rotulo, .caixa.pegadinha .caixa-rotulo, .caixa.regra .caixa-rotulo, .caixa.macete .caixa-rotulo, .caixa.atencao .caixa-rotulo, .caixa.exemplo .caixa-rotulo { color: var(--c); font-family: Inter, system-ui, sans-serif; letter-spacing: .06em; }
    .pagina-texto li.li-def { list-style: none; margin-left: -1.4em; border-left: 3px solid #3b82f6; padding: .1em 0 .1em .9em; background: color-mix(in srgb, #3b82f6 6%, transparent); border-radius: 0 6px 6px 0; }
    .pagina-texto .autor { font-weight: 650; color: color-mix(in srgb, #8b5cf6 70%, var(--ink)); font-variant: small-caps; letter-spacing: .01em; }
    .pagina-texto .lei-auto { text-decoration: underline dotted color-mix(in srgb, var(--disc) 70%, transparent); text-underline-offset: 3px; text-decoration-thickness: 1.5px; cursor: pointer; }
    .pagina-texto .lei-auto:hover { color: var(--disc); }
    body.editando .pagina-texto .lei-auto { cursor: text; }
    .pagina-texto .lei-auto.lei-sem-acervo { text-decoration: none; cursor: text; }
    .previa-lei { position: absolute; z-index: 70; width: min(420px, calc(100vw - 16px)); max-height: 300px; overflow: auto; background: var(--card); color: var(--ink); border: 1px solid var(--line); border-top: 3px solid var(--disc, var(--accent)); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,.2); padding: 10px 14px; font-size: 14px; line-height: 1.55; }
    .previa-lei b { display: block; margin-bottom: 4px; }
    .previa-lei small { display: block; color: var(--muted); margin-top: 6px; }
    .previa-corpo { font-family: var(--serif, Georgia, serif); }
    .previa-corpo > * { margin: 0 0 .4em; }

    /* erros no texto: badge por seção, mapa de calor na margem, gaveta */
    .badge-erros { display: inline-flex; align-items: center; margin-left: .6em; vertical-align: .2em; font: 700 11.5px/1 Inter, system-ui, sans-serif; letter-spacing: 0; color: var(--no); background: var(--no-soft); border: 0; border-radius: 99px; padding: 4px 9px; cursor: pointer; user-select: none; }
    .badge-erros:hover { filter: brightness(.95); box-shadow: 0 0 0 2px color-mix(in srgb, var(--no) 30%, transparent); }
    .pagina-texto .quente { position: relative; }
    .pagina-texto .quente::before { content: ''; position: absolute; left: -26px; top: .35em; bottom: .35em; width: 4px; border-radius: 2px; background: var(--no); opacity: .35; }
    .pagina-texto .quente[data-calor="2"]::before { opacity: .6; } .pagina-texto .quente[data-calor="3"]::before { opacity: .9; }
    li > .quente::before, td.quente::before { left: -44px; }
    .gaveta-fundo { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 55; }
    .gaveta-erros { position: fixed; top: 0; right: 0; bottom: 0; width: min(440px, 100vw); z-index: 56; background: var(--card); border-left: 1px solid var(--line); box-shadow: -12px 0 40px rgba(0,0,0,.2); display: flex; flex-direction: column; gap: 12px; padding: 16px; overflow: auto; }
    .gaveta-erros header { display: flex; gap: 10px; align-items: flex-start; }
    .gaveta-erros header > div { flex: 1; }
    .gaveta-erros header small { color: var(--no); font-weight: 700; font-size: 12px; }
    .gaveta-erros h2 { margin: 2px 0 0; font-size: 18px; }
    .ge-lista { display: grid; gap: 10px; }
    .ge-item { border: 1px solid var(--line); border-left: 4px solid #8bbcff; border-radius: 10px; padding: 10px 12px; font-size: 14px; }
    .ge-item.verde { border-left-color: #7bd99a; } .ge-item.rosa { border-left-color: #ff9cc2; } .ge-item.amarelo { border-left-color: #ffe066; }
    .ge-item blockquote { margin: 0 0 6px; color: var(--muted); font-family: var(--serif, Georgia, serif); }
    .ge-item .link { border: 0; background: none; color: var(--accent); font: inherit; font-size: 12px; font-weight: 600; padding: 0; cursor: pointer; }

    /* sumário lateral: bolinha de situação, erros, barra de domínio, capítulo ativo */
    .cap-linha > a { align-items: center; }
    .cap-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; background: var(--line); }
    .cap-dot.err { background: var(--no); } .cap-dot.rev { background: var(--warn); } .cap-dot.dom { background: var(--ok); } .cap-dot.q { background: color-mix(in srgb, var(--no) 45%, var(--line)); }
    .cap-q { margin-left: auto; flex: none; color: var(--muted); font-size: 12px; font-variant-numeric: tabular-nums; }
    .cap-q::before { content: '· '; }
    .cap-barra { position: absolute; left: 28px; right: 34px; bottom: 2px; height: 2px; border-radius: 2px; background: var(--line); overflow: hidden; }
    .cap-barra i { display: block; height: 100%; background: var(--ok); }
    .cap-linha.atual { background: var(--disc-soft); box-shadow: inset 3px 0 0 var(--disc); }
    .cap-linha.atual > a { color: color-mix(in srgb, var(--disc) 55%, var(--ink)); }
    .cap-linha:hover:not(.atual) { background: color-mix(in srgb, var(--disc) 7%, transparent); }

    /* margem: notas, ou "Nesta página" quando não há nota */
    .margem.vazia { position: sticky; top: 16px; max-height: calc(100vh - 32px); overflow: auto; scrollbar-width: thin; }
    .margem.vazia > h2, .margem.vazia .dica-notas { display: none; }
    .margem:not(.vazia) .nesta-pagina { display: none; }
    .nesta-pagina h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin: 6px 0 8px; }
    .nesta-pagina ol { list-style: none; margin: 0; padding: 0 0 0 1px; border-left: 1px solid var(--line); }
    .nesta-pagina a { display: flex; gap: 6px; align-items: baseline; padding: 4px 8px 4px 12px; margin-left: -1px; border-left: 2px solid transparent; color: var(--muted); text-decoration: none; font-size: 13px; line-height: 1.35; }
    .nesta-pagina a.n3 { padding-left: 24px; font-size: 12.5px; }
    .nesta-pagina a:hover { color: var(--ink); }
    .nesta-pagina a.ativo { color: var(--disc); border-left-color: var(--disc); font-weight: 600; }
    .np-num { font-variant-numeric: tabular-nums; opacity: .7; flex: none; }
    .np-err { margin-left: auto; flex: none; color: var(--no); font-weight: 700; font-size: 11px; }

    /* barra de progresso de leitura e modo foco */
    .progresso-leitura { position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 80; pointer-events: none; }
    .progresso-leitura i { display: block; height: 100%; background: var(--disc, var(--accent)); transform-origin: 0 50%; transform: scaleX(0); transition: transform .1s linear; }
    body.foco .livro, body.foco .livro.capa { grid-template-columns: minmax(0, 1fr) !important; }
    body.foco .livro-lateral, body.foco .margem, body.foco .menu, body.foco .expandir-lateral, body.foco .abrir-sumario { display: none !important; }
    body.foco .livro-pagina { max-width: 900px; margin: 0 auto; width: 100%; }
    .btn.mini[data-foco][aria-pressed=true] { background: var(--disc-soft); color: var(--disc); border-color: transparent; }

    /* diálogos */
    dialog.art, dialog.escolher-questao { border: 1px solid var(--line); border-radius: 14px; background: var(--card); color: var(--ink); max-width: 680px; width: calc(100% - 32px); max-height: 80vh; padding: 0; }
    dialog::backdrop { background: rgba(0,0,0,.45); }
    dialog .topo { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); position: sticky; top: 0; background: var(--card); }
    dialog .topo b { flex: 1; }
    dialog .corpo { padding: 12px 16px 16px; font-size: 15px; }
    dialog input[type=search] { width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 12px; font: inherit; margin-bottom: 8px; }
    .q-item { display: block; width: 100%; text-align: left; border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 10px; padding: 8px 12px; font: inherit; font-size: 14px; margin-bottom: 6px; cursor: pointer; }
    .q-item:hover { border-color: var(--accent); }
    .livro-aviso { position: fixed; left: 50%; bottom: 20px; transform: translateX(-50%); z-index: 60; background: var(--ink); color: var(--bg); padding: 10px 16px; border-radius: 10px; font-size: 14px; max-width: calc(100% - 32px); }

    /* telas menores: sumário vira gaveta; notas abaixo do texto */
    @media (max-width: 1180px) {
        .livro, .livro.capa { grid-template-columns: minmax(0, 1fr) 230px; }
        .pagina { --pad-x: 32px; padding: 52px var(--pad-x) 44px; }
        .livro.capa { grid-template-columns: minmax(0, 1fr); }
        .livro-lateral { position: fixed; inset: 0 auto 0 0; width: min(320px, 88vw); max-height: none; z-index: 40; border-radius: 0 14px 14px 0; transform: translateX(-105%); transition: transform .2s; box-shadow: 0 0 40px rgba(0,0,0,.25); }
        body.gaveta-aberta .livro-lateral { transform: none; }
        .lat-topo .fechar, .abrir-sumario { display: inline-flex; }
    }
    @media (max-width: 860px) {
        .livro { grid-template-columns: minmax(0, 1fr); }
        .pagina { --pad-x: 18px; padding: 58px var(--pad-x) 32px; border-radius: 12px; }
        .pagina-texto .quente::before, li > .quente::before, td.quente::before { left: -12px; width: 3px; }
        .pagina-texto, [data-estatico] { line-height: 1.65; }
        .pagina h1 { font-size: 23px; }
        .pagina-texto, [data-estatico] { font-size: 16px; }
        .margem { position: static; }
        .notas-lista { position: static !important; min-height: 0 !important; display: grid; gap: 8px; }
        .nota { position: static; }
        .margem.vazia { display: none; }
        .navega { grid-template-columns: 1fr; }
        .navega .prox { grid-column: auto; }
        .cap-menu > summary { opacity: 1; }
    }
    @media print {
        .menu, .livro-lateral, .pag-topo, .barra, .navega, .questoes-cap, .margem, .bolha, .slash, .caneta-paleta, .rascunho-barra, .aula-pdf { display: none !important; }
        .livro { display: block; }
        .pagina { border: 0; padding: 0; }
    }
@endsection

@section('content')
<script>try { if (localStorage.getItem('livro-lateral-recolhida') === '1') document.body.classList.add('lateral-recolhida'); } catch (e) {}</script>
<div class="livro {{ $capitulo ? '' : 'capa' }}">
    <aside class="livro-lateral" aria-label="Sumário do livro">
        <div class="lat-topo">
            <a href="{{ route('concursos.material.show', $material) }}">{{ $material->titulo }}</a>
            <button type="button" class="btn mini fantasma recolher" data-recolher aria-label="Recolher sumário" title="Recolher sumário">«</button>
            <button type="button" class="btn mini fechar" data-gaveta aria-label="Fechar sumário">✕</button>
        </div>
        <div class="busca-caixa">
            <input type="search" class="busca-livro" data-busca placeholder="Buscar no livro" aria-label="Buscar no livro (atalho: /)">
            <kbd aria-hidden="true">/</kbd>
        </div>
        <div class="resultados" data-resultados hidden></div>
        <nav class="sumario-livro" data-sumario aria-label="Capítulos">
            @include('concursos.material._sumario', ['pai' => 0])
            <button type="button" class="novo-cap" data-acao="novo">+ Novo capítulo</button>
        </nav>
        <div class="lat-rodape">
            @if ($resumo['questoes'])<a href="{{ route('concursos.material.refazer', $material) }}">↻ Questões ({{ $resumo['hoje'] }} para hoje)</a>@endif
            @if ($todos)<a href="{{ route('concursos.material.show', $material) }}">Só os capítulos de teoria</a>
            @elseif ($ocultos)<a href="{{ route('concursos.material.show', [$material, 'todos' => 1]) }}" title="Capítulos só com pontos e questões (diário de erros)">Ver também {{ $ocultos }} capítulos do diário de erros</a>@endif
            <a href="{{ route('concursos.material.imprimir', $material) }}" target="_blank">🖨 Imprimir / PDF do livro</a>
            <a href="{{ route('concursos.material.index') }}">← Todos os livros</a>
        </div>
    </aside>

    @if ($capitulo)
        <main class="livro-pagina">
            <div class="pag-topo">
                <button type="button" class="btn mini abrir-sumario" data-gaveta>☰ Sumário</button>
                <button type="button" class="btn mini expandir-lateral" data-recolher>☰ Sumário</button>
                <nav class="migalhas" aria-label="Você está em">
                    <a href="{{ route('concursos.material.index') }}">Resumos</a> /
                    <a href="{{ route('concursos.material.show', $material) }}">{{ $material->titulo }}</a>
                    @foreach ($caminho as $c) › <a href="{{ route('concursos.material.capitulo', [$material, $c['id']]) }}">{{ $c['titulo'] }}</a>@endforeach
                </nav>
                <span class="salvo" data-salvo role="status" aria-live="polite"></span>
                <button type="button" class="btn mini fantasma" data-caneta aria-pressed="false" title="Modo caneta: passe a caneta sobre o texto para grifar (o dedo continua rolando)">✒ Caneta</button>
                <button type="button" class="btn mini fantasma" data-foco aria-pressed="false" title="Modo foco: esconde sumário e notas (atalho: F)">◎ Foco</button>
                <button type="button" class="btn mini main" data-modo aria-pressed="false" title="Atalho: e">Editar</button>
            </div>
            <div class="barra" data-barra hidden role="toolbar" aria-label="Formatação"></div>
            <article class="pagina">
                <h1 data-titulo-capitulo>{{ $capitulo->titulo }}</h1>
                <div data-estatico>{!! $html !!}</div>
                <div data-editor hidden></div>
            </article>

            @if ($pdf)
                <section class="card aula-pdf" data-pdf="{{ $pdf }}">
                    <div class="aula-pdf-topo">
                        <h2>Aula em PDF</h2>
                        <span class="muted" data-pdf-info>Carregando…</span>
                        <a class="btn mini fantasma" href="{{ $pdf }}" target="_blank" rel="noopener">Abrir em outra aba ↗</a>
                    </div>
                    <div class="aula-pdf-paginas" data-pdf-paginas></div>
                </section>
            @endif

            @if (count($dadosEditor['doCapitulo']))
                <div class="card questoes-cap">
                    <p><b>{{ count($dadosEditor['doCapitulo']) }}</b> {{ count($dadosEditor['doCapitulo']) === 1 ? 'questão' : 'questões' }} deste assunto nos cadernos. Refaça na área de Questões.</p>
                    <a class="btn" href="{{ route('concursos.material.refazer', [$material, 'capitulo' => $capitulo->id, 'situacao' => 'errando', 'iniciar' => 1]) }}">Refazer as que ainda erro</a>
                </div>
            @endif

            <nav class="navega" aria-label="Capítulos">
                @if ($anterior)<a href="{{ route('concursos.material.capitulo', [$material, $anterior['id']]) }}" data-anterior><small>← Anterior</small>{{ $anterior['num'] }} {{ $anterior['titulo'] }}</a>@endif
                @if ($proximo)<a class="prox" href="{{ route('concursos.material.capitulo', [$material, $proximo['id']]) }}" data-proximo><small>Próximo →</small>{{ $proximo['num'] }} {{ $proximo['titulo'] }}</a>@endif
            </nav>
        </main>

        <aside class="margem vazia" aria-label="Notas na margem">
            <h2>Notas</h2>
            <p class="dica-notas">Selecione um trecho do texto e toque em <b>✎ Nota</b> para anotar aqui, ou numa cor para só grifar.</p>
            <div class="notas-lista" data-margem></div>
        </aside>
    @else
        @php
            $pontosTotal = $resumo['errando'] + $resumo['revisando'] + $resumo['dominado'];
            $prioritario = $resumo['questoes'] > 0;
            $maxErros = max(1, (int) $plano->max('errando'));
        @endphp
        <main class="livro-pagina">
            <div class="pag-topo">
                <button type="button" class="btn mini abrir-sumario" data-gaveta>☰ Sumário</button>
                <button type="button" class="btn mini expandir-lateral" data-recolher>☰ Sumário</button>
            </div>
            <section class="card capa-topo" style="border-top: 4px solid {{ $material->cor() }}">
                <nav class="migalhas" aria-label="Você está em"><a href="{{ route('concursos.material.index') }}">Resumos</a> / {{ $material->titulo }}</nav>
                <div class="capa-cabeca">
                    <div>
                        <h1>{{ $material->titulo }}</h1>
                        @if ($material->descricao)<p class="muted" style="margin:0">{{ $material->descricao }}</p>@endif
                    </div>
                    <div class="capa-acoes">
                        @if ($prioritario)
                            <a class="btn main" href="{{ route('concursos.material.refazer', [$material, 'situacao' => 'fila', 'iniciar' => 1]) }}">Revisar questões ({{ $resumo['hoje'] }} hoje)</a>
                            <a class="btn" data-continuar hidden href="#">Continuar: <span></span></a>
                        @else
                            <a class="btn main" data-continuar hidden href="#">Continuar: <span></span></a>
                            @if ($plano->isNotEmpty())
                                <a class="btn" href="{{ route('concursos.material.capitulo', [$material, $plano->first()['id']]) }}">Começar do início</a>
                            @endif
                        @endif
                        <button type="button" class="btn fantasma" onclick="document.querySelector('.novo-cap').click()">+ Novo capítulo</button>
                    </div>
                </div>

                <div class="indicadores">
                    <button type="button" class="ind" data-filtro-atalho="todos" aria-pressed="false" title="Mostrar todos os capítulos"><small>Capítulos</small><b>{{ $resumo['capitulos'] }}</b></button>
                    <button type="button" class="ind err" data-filtro-atalho="erros" aria-pressed="false" title="Mostrar só capítulos com erros"><small>Ainda erro</small><b>{{ $resumo['errando'] }}</b></button>
                    <div class="ind rev"><small>Revisando</small><b>{{ $resumo['revisando'] }}</b></div>
                    <div class="ind dom"><small>Dominadas</small><b>{{ $resumo['dominado'] }}</b></div>
                    @if ($resumo['questoes'])
                        <a class="ind" href="{{ route('concursos.material.refazer', $material) }}"><small>Questões →</small><b>{{ $resumo['questoes'] }}</b></a>
                    @else
                        <div class="ind"><small>Para refazer</small><b>0</b></div>
                    @endif
                </div>
                <div class="progresso">
                    <div class="seg" role="img" aria-label="{{ $resumo['errando'] }} ainda erro, {{ $resumo['revisando'] }} revisando, {{ $resumo['dominado'] }} dominados">
                        @if ($pontosTotal)
                            <i class="err" style="width: {{ $resumo['errando'] / $pontosTotal * 100 }}%"></i><i class="rev" style="width: {{ $resumo['revisando'] / $pontosTotal * 100 }}%"></i><i class="dom" style="width: {{ $resumo['dominado'] / $pontosTotal * 100 }}%"></i>
                        @endif
                    </div>
                    <span>{{ $pontosTotal ? round($resumo['dominado'] / $pontosTotal * 100) . '% das questões dominadas' : 'sem questões ainda' }}</span>
                </div>
            </section>

            <section class="card">
                <div class="sum-controles">
                    <h2>Sumário</h2>
                    @if ($plano->isNotEmpty())
                        <div class="alternar" role="group" aria-label="Ordenar o sumário">
                            <button type="button" data-ordenar="ordem" aria-pressed="true">Ordem do sumário</button>
                            <button type="button" data-ordenar="erros" aria-pressed="false">Mais erros</button>
                            <button type="button" data-ordenar="questoes" aria-pressed="false">Mais questões</button>
                        </div>
                        <div class="alternar" role="group" aria-label="Filtrar o sumário">
                            <button type="button" data-filtro="todos" aria-pressed="true">Todos</button>
                            <button type="button" data-filtro="erros" aria-pressed="false">Com erros</button>
                            <button type="button" data-filtro="limpos" aria-pressed="false">Sem erros</button>
                        </div>
                    @endif
                </div>
                @if ($plano->isEmpty())
                    <p class="muted">Este livro ainda não tem capítulos. Crie o primeiro (ex.: um assunto do edital) e escreva o resumo nele.</p>
                @else
                    <ol class="indice" data-indice>
                        @foreach ($plano as $i => $c)
                            <li style="--nivel: {{ $c['nivel'] }}" data-nivel="{{ $c['nivel'] }}" data-ordem="{{ $i }}" data-erros="{{ $c['errando'] }}" data-questoes="{{ $c['questoes'] }}">
                                <a href="{{ route('concursos.material.capitulo', [$material, $c['id']]) }}">
                                    <span class="cap-num">{{ $c['num'] }}</span>
                                    <span class="ind-nome">{{ $c['titulo'] }}</span>
                                    <span class="ind-meta">
                                        @if ($c['questoes'])<span title="questões erradas deste assunto">{{ $c['questoes'] }} q.</span>@endif
                                        @if ($c['errando'])
                                            <span class="etq err" title="pontos que ainda erro">{{ $c['errando'] }}</span>
                                            <span class="critico" aria-hidden="true"><i style="width: {{ $c['errando'] / $maxErros * 100 }}%"></i></span>
                                        @endif
                                        <span class="ind-seta" aria-hidden="true">›</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                    <p class="indice-vazio" data-indice-vazio hidden>Nenhum capítulo neste filtro.</p>
                @endif
            </section>
        </main>
    @endif
</div>

<dialog class="art" aria-labelledby="art-titulo">
    <div class="topo"><b id="art-titulo">Artigo</b><a href="#" data-abrir class="btn mini">Abrir na lei</a><button type="button" class="btn mini" data-fechar>Fechar</button></div>
    <div class="corpo" data-corpo></div>
</dialog>
<dialog class="escolher-questao" aria-labelledby="q-titulo">
    <div class="topo"><b id="q-titulo">Inserir questão errada</b><button type="button" class="btn mini" data-fechar>Fechar</button></div>
    <div class="corpo">
        <input type="search" placeholder="Nº do TEC ou trecho do enunciado" aria-label="Buscar questão">
        <div data-lista></div>
    </div>
</dialog>

<script id="livro-dados" type="application/json">@json($dados)</script>
@endsection

@section('js')
@vite('resources/js/concursos/livro.js')
@endsection
