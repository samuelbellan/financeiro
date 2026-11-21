@extends('concursos.layout')

@section('title', 'Questões')

@section('css')
    .disc { padding: 0; overflow: hidden; }
    .disc > summary { list-style: none; cursor: pointer; padding: 16px 20px; display: flex; gap: 12px; align-items: center; font-weight: 700; }
    .disc > summary::-webkit-details-marker { display: none; }
    .disc > summary::before { content: '▸'; color: var(--muted); transition: transform .15s; }
    .disc[open] > summary::before { transform: rotate(90deg); }
    .row { display: grid; grid-template-columns: 1fr auto; gap: 4px 16px; align-items: center; padding: 10px 20px; border-top: 1px solid var(--line); }
    .row.sub { padding-left: 40px; font-size: 15px; }
    .row .nome { min-width: 0; overflow-wrap: anywhere; }
    .row .acoes { display: flex; gap: 6px; }
    .row .acoes a { font-size: 13px; padding: 4px 10px; border-radius: 8px; border: 1px solid var(--line); text-decoration: none; white-space: nowrap; }
    .row .num { grid-column: 1 / -1; display: flex; gap: 10px; align-items: center; font-size: 13px; color: var(--muted); }
    .row .num .bar { width: 90px; }
    .fill { flex: 1; }
    .chips { display: flex; gap: 8px; flex-wrap: wrap; }
    @media (max-width: 560px) { .row { grid-template-columns: 1fr; } .row.sub { padding-left: 28px; } }
@endsection

@section('content')
    <div class="top">
        <h1>Resolver questões</h1>
        <a class="btn" href="{{ route('concursos.meta') }}">Meta do dia</a>
        @if (Route::has('concursos.page'))
            <a class="btn" href="{{ route('concursos.page', ['page' => 'inicio.html']) }}">App antigo</a>
        @endif
        <a class="btn main" href="{{ route('concursos.questoes.resolver', ['filtro' => 'nao']) }}">Começar (todas não respondidas)</a>
    </div>

    <div class="card">
        @php($acertosHoje = $hoje->where('correta', true)->count())
        <b>Hoje:</b> {{ $hoje->count() }} respondida(s){{ $hoje->count() ? ", {$acertosHoje} certa(s) (" . round(100 * $acertosHoje / $hoje->count()) . '%)' : '' }}.
        <span class="muted small">Escolha um assunto abaixo. "Não resp." pega só as que você ainda não fez; "Erradas" pega as que errou ou chutou.</span>
    </div>

    @forelse ($arvore as $d)
        <details class="card disc" @if ($loop->first) open @endif>
            <summary><span class="fill">{{ $d['disciplina']->nome }}</span><span class="muted small">{{ $d['total'] }} questões</span></summary>
            @foreach ($d['raizes'] as $r)
                @include('concursos.questoes._linha', ['a' => $r['assunto'], 's' => $r['s'], 'sub' => false])
                @foreach ($r['filhos'] as $f)
                    @include('concursos.questoes._linha', ['a' => $f['assunto'], 's' => $f['s'], 'sub' => true])
                @endforeach
            @endforeach
        </details>
    @empty
        <div class="card">Nenhuma questão no banco ainda. Importe um caderno do TEC com <code>php artisan concursos:importar-pdf</code>.</div>
    @endforelse
@endsection
