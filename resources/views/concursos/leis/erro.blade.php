@extends('concursos.layout')

@section('title', $lei['sigla'])

@section('content')
<div class="card">
    <h1 style="font-size:20px;margin:0 0 8px">{{ $lei['nome'] }}</h1>
    <p>Não consegui baixar o texto desta lei agora.</p>
    <p class="muted small">{{ $erro }}</p>
    <p><a href="{{ $fonte }}" target="_blank" rel="noopener">Abrir no GitHub</a> · <a href="{{ url()->current() }}">Tentar de novo</a></p>
</div>
@endsection
