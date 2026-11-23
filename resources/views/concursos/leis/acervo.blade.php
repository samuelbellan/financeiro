@extends('concursos.layout')

@section('title', 'Acervo de leis')

@php
    $partes = $rel === '' ? [] : explode('/', $rel);
    $url = fn (string $p) => route('concursos.leis.acervo', $p === '' ? [] : ['p' => $p]);
@endphp

@section('css')
    .trilha { font-size: 14px; display: flex; flex-wrap: wrap; gap: 4px; align-items: center; margin-bottom: 12px; }
    .trilha a { text-decoration: none; }
    .itens { list-style: none; margin: 0; padding: 0; columns: 2 260px; gap: 24px; font-size: 14px; }
    .itens li { break-inside: avoid; padding: 4px 0; border-bottom: 1px solid var(--line); }
    .itens a { text-decoration: none; word-break: break-word; }
    .itens .pasta a::before { content: '📁 '; }
    .filtro { width: 100%; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 8px 12px; font: inherit; font-size: 14px; margin-bottom: 12px; }
@endsection

@section('content')
<div class="top"><h1>Acervo de leis</h1></div>
<div class="card">
    <nav class="trilha" aria-label="Pasta atual">
        <a href="{{ $url('') }}">acervo</a>
        @foreach ($partes as $i => $p)
            <span class="muted">/</span>
            @if ($loop->last)<b>{{ $p }}</b>@else<a href="{{ $url(implode('/', array_slice($partes, 0, $i + 1))) }}">{{ $p }}</a>@endif
        @endforeach
    </nav>
    @if (count($pastas) + count($arquivos) > 30)
        <input class="filtro" type="search" placeholder="Filtrar esta pasta" aria-label="Filtrar esta pasta" oninput="const q=this.value.toLowerCase();document.querySelectorAll('.itens li').forEach(li=>li.hidden=q&&!li.textContent.toLowerCase().includes(q))">
    @endif
    <ul class="itens">
        @foreach ($pastas as $p)
            <li class="pasta"><a href="{{ $url(ltrim($rel . '/' . $p, '/')) }}">{{ $p }}</a></li>
        @endforeach
        @foreach ($arquivos as $a)
            <li><a href="{{ $url(ltrim($rel . '/' . $a, '/')) }}">{{ basename($a, '.md') }}</a></li>
        @endforeach
    </ul>
    @if (!$pastas && !$arquivos)<p class="muted">Pasta vazia.</p>@endif
</div>
@endsection
