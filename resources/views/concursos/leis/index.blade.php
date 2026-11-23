@extends('concursos.layout')

@section('title', 'Leis')

@section('css')
    .leis { display: grid; gap: 8px; }
    .lei { display: flex; gap: 12px; align-items: center; text-decoration: none; color: inherit; padding: 14px 16px; margin: 0; }
    .lei .s { font-weight: 700; min-width: 110px; color: var(--accent); }
    .lei .n { flex: 1; }
    .lei .b { font-size: 12px; color: var(--muted); white-space: nowrap; }
@endsection

@section('content')
<div class="top"><h1>Lei seca</h1></div>
<p class="muted small" style="margin-top:-8px">Textos do projeto <a href="https://github.com/ctresb/leis" target="_blank" rel="noopener">ctresb/leis</a>, baixados na primeira vez que você abre cada lei. Conversão automática: na dúvida, confira a fonte oficial indicada na página.</p>
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
