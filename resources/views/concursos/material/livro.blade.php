@extends('concursos.layout')

@section('title', $capitulo?->titulo ?? $material->titulo)

@php
    $filhos = $plano->groupBy(fn ($c) => $c['parent_id'] ?? 0);
    $atual = $capitulo?->id;
    $rotulos = \App\Models\Concursos\MaterialPonto::STATUS;
    $dados = [
        'csrf' => csrf_token(),
        'livro' => ['id' => $material->id, 'titulo' => $material->titulo],
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

@section('css')
    .wrap { max-width: 1480px; }
    .livro { display: grid; grid-template-columns: 270px minmax(0, 1fr) 250px; gap: 24px; align-items: start; }
    .livro.capa { grid-template-columns: 270px minmax(0, 1fr); }

    /* ---- sumário ---- */
    .livro-lateral { position: sticky; top: 12px; max-height: calc(100vh - 24px); overflow: auto; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px 10px 14px 14px; font-size: 14px; }
    .lat-topo { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .lat-topo a { font-weight: 700; color: var(--ink); text-decoration: none; line-height: 1.3; flex: 1; }
    .lat-topo .fechar { display: none; }
    .busca-livro { width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 10px; font: inherit; font-size: 14px; margin-bottom: 10px; }
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
    .pagina-texto, [data-estatico] { font-size: 17px; line-height: 1.7; outline: none; max-width: 72ch; }
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
    @media (prefers-color-scheme: dark) {
        mark.grifo.amarelo { background: #5c4d0f; } mark.grifo.verde { background: #1d4a2c; }
        mark.grifo.azul { background: #1d3557; } mark.grifo.rosa { background: #5a2238; }
        mark.grifo[data-nota] { border-bottom-color: rgba(255,255,255,.5); }
    }
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
    .capa-topo h1 { font-size: 30px; margin: 0 0 6px; }
    .capa-nums { display: flex; gap: 16px; flex-wrap: wrap; font-size: 14px; color: var(--muted); margin: 12px 0 16px; }
    .capa-nums b { font-variant-numeric: tabular-nums; color: var(--ink); font-size: 18px; display: block; }
    .capa-nums .err b { color: var(--no); } .capa-nums .dom b { color: var(--ok); }
    .capa-acoes { display: flex; gap: 8px; flex-wrap: wrap; }
    .indice { list-style: none; margin: 0; padding: 0; columns: 2 300px; column-gap: 32px; }
    .indice li { break-inside: avoid; padding: 5px 0 5px calc(var(--nivel) * 18px); border-bottom: 1px dotted var(--line); display: flex; gap: 8px; align-items: baseline; }
    .indice li[style*="--nivel: 0"] { font-weight: 700; padding-top: 10px; }
    .indice a { color: var(--ink); text-decoration: none; flex: 1; }
    .indice a:hover { color: var(--accent); }
    .indice .meta { font-size: 12px; color: var(--muted); white-space: nowrap; font-weight: 400; }

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
        .livro.capa { grid-template-columns: minmax(0, 1fr); }
        .livro-lateral { position: fixed; inset: 0 auto 0 0; width: min(320px, 88vw); max-height: none; z-index: 40; border-radius: 0 14px 14px 0; transform: translateX(-105%); transition: transform .2s; box-shadow: 0 0 40px rgba(0,0,0,.25); }
        body.gaveta-aberta .livro-lateral { transform: none; }
        .lat-topo .fechar, .abrir-sumario { display: inline-flex; }
    }
    @media (max-width: 860px) {
        .livro { grid-template-columns: minmax(0, 1fr); }
        .pagina { padding: 22px 18px 32px; border-radius: 12px; }
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
        .menu, .livro-lateral, .pag-topo, .barra, .navega, .questoes-cap, .margem, .bolha, .slash { display: none !important; }
        .livro { display: block; }
        .pagina { border: 0; padding: 0; }
    }
@endsection

@section('content')
<div class="livro {{ $capitulo ? '' : 'capa' }}">
    <aside class="livro-lateral" aria-label="Sumário do livro">
        <div class="lat-topo">
            <a href="{{ route('concursos.material.show', $material) }}">{{ $material->titulo }}</a>
            <button type="button" class="btn mini fechar" data-gaveta aria-label="Fechar sumário">✕</button>
        </div>
        <input type="search" class="busca-livro" data-busca placeholder="Buscar no livro  ( / )" aria-label="Buscar no livro">
        <div class="resultados" data-resultados hidden></div>
        <nav class="sumario-livro" data-sumario aria-label="Capítulos">
            @include('concursos.material._sumario', ['pai' => 0])
            <button type="button" class="novo-cap" data-acao="novo">+ Novo capítulo</button>
        </nav>
        <div class="lat-rodape">
            @if ($resumo['questoes'])<a href="{{ route('concursos.material.refazer', $material) }}">↻ Refazer erradas ({{ $resumo['questoes'] }})</a>@endif
            <a href="{{ route('concursos.material.imprimir', $material) }}" target="_blank">🖨 Imprimir / PDF do livro</a>
            <a href="{{ route('concursos.material.index') }}">← Todos os livros</a>
        </div>
    </aside>

    @if ($capitulo)
        <main class="livro-pagina">
            <div class="pag-topo">
                <button type="button" class="btn mini abrir-sumario" data-gaveta>☰ Sumário</button>
                <nav class="migalhas" aria-label="Você está em">
                    <a href="{{ route('concursos.material.show', $material) }}">{{ $material->titulo }}</a>
                    @foreach ($caminho as $c) › <a href="{{ route('concursos.material.capitulo', [$material, $c['id']]) }}">{{ $c['titulo'] }}</a>@endforeach
                </nav>
                <span class="salvo" data-salvo role="status" aria-live="polite"></span>
                <button type="button" class="btn mini main" data-modo aria-pressed="false" title="Atalho: e">Editar</button>
            </div>
            <div class="barra" data-barra hidden role="toolbar" aria-label="Formatação"></div>
            <article class="pagina">
                <h1 data-titulo-capitulo>{{ $capitulo->titulo }}</h1>
                <div data-estatico>{!! $html !!}</div>
                <div data-editor hidden></div>
            </article>

            @if (count($dadosEditor['doCapitulo']))
                <div class="card questoes-cap">
                    <p><b>{{ count($dadosEditor['doCapitulo']) }}</b> {{ count($dadosEditor['doCapitulo']) === 1 ? 'questão errada' : 'questões erradas' }} deste assunto. Insira no texto pelo botão <b>Questão</b> (ou digite <code>/questão</code>) ou refaça em sequência.</p>
                    <a class="btn" href="{{ route('concursos.material.refazer', [$material, 'capitulo' => $capitulo->id, 'situacao' => 'todas', 'iniciar' => 1]) }}">Refazer as deste capítulo</a>
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
        <main class="livro-pagina">
            <div class="pag-topo"><button type="button" class="btn mini abrir-sumario" data-gaveta>☰ Sumário</button></div>
            <section class="card capa-topo">
                <h1>{{ $material->titulo }}</h1>
                @if ($material->descricao)<p class="muted" style="margin:0">{{ $material->descricao }}</p>@endif
                <div class="capa-nums">
                    <span><b>{{ $resumo['capitulos'] }}</b> capítulos</span>
                    <span class="err"><b>{{ $resumo['errando'] }}</b> pontos que ainda erro</span>
                    <span><b>{{ $resumo['revisando'] }}</b> revisando</span>
                    <span class="dom"><b>{{ $resumo['dominado'] }}</b> dominados</span>
                    <span><b>{{ $resumo['questoes'] }}</b> questões para refazer</span>
                </div>
                <div class="capa-acoes">
                    <a class="btn main" data-continuar hidden href="#">Continuar: <span></span></a>
                    @if ($plano->isNotEmpty())
                        <a class="btn" href="{{ route('concursos.material.capitulo', [$material, $plano->first()['id']]) }}">Começar do início</a>
                    @endif
                    <button type="button" class="btn" onclick="document.querySelector('.novo-cap').click()">+ Novo capítulo</button>
                </div>
            </section>

            <section class="card">
                <h2 style="margin:0 0 8px;font-size:18px">Sumário</h2>
                @if ($plano->isEmpty())
                    <p class="muted">Este livro ainda não tem capítulos. Crie o primeiro (ex.: um assunto do edital) e escreva o resumo nele.</p>
                @else
                    <ol class="indice">
                        @foreach ($plano as $c)
                            <li style="--nivel: {{ $c['nivel'] }}">
                                <span class="cap-num">{{ $c['num'] }}</span>
                                <a href="{{ route('concursos.material.capitulo', [$material, $c['id']]) }}">{{ $c['titulo'] }}</a>
                                <span class="meta">
                                    @if ($c['errando'])<span style="color:var(--no)">{{ $c['errando'] }} erro{{ $c['errando'] > 1 ? 's' : '' }}</span>@endif
                                    @if ($c['questoes']) · {{ $c['questoes'] }} q.@endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
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
