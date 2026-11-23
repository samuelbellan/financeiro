@extends('concursos.layout')

@section('title', $titulo)

@section('css')
    .doc h1 { font-size: 21px; margin: 0 0 4px; }
    .doc .sub { font-size: 13px; color: var(--muted); margin-bottom: 12px; word-break: break-all; }
    .texto { font-size: 16px; line-height: 1.65; }
    .texto h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 20px 0 6px; }
    .texto h3, .texto h4 { font-size: 15px; margin: 16px 0 6px; }
    .texto table { border-collapse: collapse; font-size: 14px; display: block; overflow-x: auto; }
    .texto th, .texto td { border: 1px solid var(--line); padding: 4px 8px; text-align: left; vertical-align: top; }
    .fonte { font-size: 13px; color: var(--muted); }
@endsection

@section('content')
<article class="card doc">
    <h1>{{ $titulo }}</h1>
    <div class="sub">{{ $subtitulo }}</div>
    <div class="texto">{!! $html !!}</div>
</article>
<p class="fonte">Texto de <a href="{{ $fonte }}" target="_blank" rel="noopener">ctresb/leis</a>@if ($origem), a partir de <a href="{{ $origem }}" target="_blank" rel="noopener">{{ parse_url($origem, PHP_URL_HOST) }}</a>@endif. Conversão automática: confira a fonte oficial antes de citar.</p>
@endsection
