<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $material->titulo }} · Livro de resumo</title>
    <style>
        :root { --ink: #1d2433; --muted: #667085; --line: #d9dde5; --no: #b42318; --ok: #12805c; --warn: #9a6400; --accent: #3b6fe0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: var(--ink); font: 15px/1.6 Georgia, 'Times New Roman', serif; }
        .folha { max-width: 760px; margin: 0 auto; padding: 32px 24px 64px; }
        .acoes { font: 14px system-ui, sans-serif; display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        .acoes button, .acoes a { font: inherit; padding: 8px 14px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--ink); text-decoration: none; cursor: pointer; }
        .capa { text-align: center; padding: 18vh 0 12vh; page-break-after: always; }
        .capa h1 { font-size: 38px; margin: 0 0 8px; }
        .capa p { color: var(--muted); }
        .indice { page-break-after: always; }
        .indice h2 { font-size: 22px; }
        .indice ol { list-style: none; padding: 0; margin: 0; }
        .indice li { padding: 3px 0; border-bottom: 1px dotted var(--line); }
        .cap { page-break-before: always; }
        .cap:first-of-type { page-break-before: auto; }
        .cap > h1 { font-size: 26px; margin: 0 0 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; }
        .cap.n1 > h1 { font-size: 22px; border-bottom-width: 1px; }
        .cap.n2 > h1, .cap.n3 > h1 { font-size: 19px; border-bottom-width: 1px; }
        .cap.n1, .cap.n2, .cap.n3 { page-break-before: auto; margin-top: 28px; }
        h3 { font-size: 19px; margin: 1.2em 0 .3em; } h4 { font-size: 16px; margin: 1em 0 .2em; } h5 { font-size: 14px; text-transform: uppercase; color: var(--muted); }
        p { margin: .5em 0; }
        ul, ol { padding-left: 1.4em; }
        blockquote { border-left: 3px solid var(--line); margin: .8em 0; padding-left: 12px; color: var(--muted); }
        table { border-collapse: collapse; width: 100%; margin: .6em 0; font-size: 14px; }
        th, td { border: 1px solid var(--line); padding: 4px 8px; text-align: left; vertical-align: top; }
        img { max-width: 100%; }
        figure { margin: .8em 0; text-align: center; }
        a { color: inherit; }
        a.lei { font-weight: 700; text-decoration: none; }
        mark.grifo { padding: 0 1px; }
        mark.amarelo { background: #fff0a3; } mark.verde { background: #c8f2d4; } mark.azul { background: #cfe3ff; } mark.rosa { background: #ffd3e4; }
        .caixa { border-left: 4px solid var(--line); padding: 6px 12px; margin: .8em 0; break-inside: avoid; background: #f7f8fa; }
        .caixa-rotulo { font: 700 11px system-ui, sans-serif; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .caixa.pegadinha { border-color: var(--no); } .caixa.pegadinha .caixa-rotulo { color: var(--no); }
        .caixa.regra { border-color: var(--ok); } .caixa.regra .caixa-rotulo { color: var(--ok); }
        .caixa.atencao { border-color: var(--warn); } .caixa.exemplo { border-color: var(--accent); } .caixa.macete { border-color: #8a5cf6; }
        .ponto-erro { border: 1px solid var(--line); border-radius: 8px; padding: 8px 12px; margin: 1em 0; break-inside: avoid-page; }
        .ponto-erro header { font-size: 16px; }
        .tag { font: 700 11px system-ui, sans-serif; border: 1px solid currentColor; border-radius: 99px; padding: 0 6px; }
        .tag.errando { color: var(--no); } .tag.revisando { color: var(--warn); } .tag.dominado { color: var(--ok); } .tag.vezes { color: var(--muted); }
        .questao-livro { border: 1px dashed var(--line); padding: 8px 12px; margin: 1em 0; break-inside: avoid; font-size: 14px; }
        .questao-livro .alts { margin: .3em 0; }
        .questao-livro .gab { color: var(--muted); font-size: 12px; }
        .notas { margin-top: 14px; border-top: 1px solid var(--line); padding-top: 6px; font-size: 13px; }
        .notas h4 { font: 700 12px system-ui, sans-serif; text-transform: uppercase; color: var(--muted); margin: 0 0 4px; }
        .notas p { margin: 2px 0; }
        .notas q { color: var(--muted); }
        @media print { .acoes { display: none; } .folha { padding: 0; } }
    </style>
</head>
<body>
<div class="folha">
    <div class="acoes">
        <a href="{{ route('concursos.material.show', $material) }}">← Voltar ao livro</a>
        <button type="button" onclick="window.print()">Imprimir / salvar PDF</button>
    </div>

    <section class="capa">
        <h1>{{ $material->titulo }}</h1>
        @if ($material->descricao)<p>{{ $material->descricao }}</p>@endif
        <p>Livro de resumo · {{ now()->format('d/m/Y') }}</p>
    </section>

    @if ($plano->isNotEmpty())
        <nav class="indice">
            <h2>Sumário</h2>
            <ol>
                @foreach ($plano as $c)
                    <li style="padding-left: {{ $c['nivel'] * 18 }}px">{{ $c['num'] }} {{ $c['titulo'] }}</li>
                @endforeach
            </ol>
        </nav>
    @endif

    @foreach ($plano as $c)
        <section class="cap n{{ min(3, $c['nivel']) }}">
            <h1>{{ $c['num'] }} {{ $c['titulo'] }}</h1>
            {!! $c['html'] !!}
            @if ($c['notas']->isNotEmpty())
                <div class="notas">
                    <h4>Minhas notas</h4>
                    @foreach ($c['notas'] as $n)
                        <p>@if ($n->trecho)<q>{{ \Illuminate\Support\Str::limit($n->trecho, 120) }}</q> — @endif{{ $n->nota }}</p>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</div>
</body>
</html>
