@extends('concursos.layout')

@section('title', 'Leis')

@php
    $local = app(\App\Services\Concursos\Leis\AcervoLeis::class)->raizLocal();
@endphp

@section('css')
    .leis { display: grid; gap: 8px; }
    .lei { display: flex; gap: 12px; align-items: center; text-decoration: none; color: inherit; padding: 14px 16px; margin: 0; }
    .lei .s { font-weight: 700; min-width: 110px; color: var(--accent); }
    .lei .n { flex: 1; }
    .lei .b { font-size: 12px; color: var(--muted); white-space: nowrap; }
    .busca { display: flex; gap: 8px; flex-wrap: wrap; }
    .busca select, .busca input { border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 9px 10px; font: inherit; font-size: 15px; }
    .busca input { width: 130px; }
    h2 { font-size: 16px; margin: 20px 0 8px; }
@endsection

@section('content')
<div class="top"><h1>Lei seca e jurisprudência</h1></div>
<p class="muted small" style="margin-top:-8px">Textos do projeto <a href="https://github.com/ctresb/leis" target="_blank" rel="noopener">ctresb/leis</a>@if ($local), lidos da cópia em <code>{{ $local }}</code>@endif. Conversão automática: na dúvida, confira a fonte oficial indicada na página.</p>

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

<h2>Leis do material</h2>
<div class="leis">
    @foreach ($leis as $l)
        <a class="card lei" href="{{ route('concursos.leis.show', $l['chave']) }}">
            <span class="s">{{ $l['sigla'] }}</span>
            <span class="n">{{ $l['nome'] }}</span>
            <span class="b">{{ $l['baixada'] ? 'no computador' : 'baixa ao abrir' }}</span>
        </a>
    @endforeach
</div>
@endsection
