@extends('concursos.layout')

@section('title', 'Revisões')

@section('css')
    @include('concursos.material._estilo')
    .intro { margin: 0 0 20px; }

    /* fila de revisão */
    .fila { padding: 8px; }
    .fila .secao-titulo { padding: 10px 10px 0; }
    .fila-item { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 10px; text-decoration: none; color: var(--ink); }
    .fila-item:hover, .fila-item:focus-visible { background: var(--accent-soft); outline: none; }
    .fila-item .etq { min-width: 34px; justify-content: center; }
    .fila-texto { flex: 1; min-width: 0; }
    .fila-texto b { display: block; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fila-texto span { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--muted); }
    .ponto-cor { width: 8px; height: 8px; border-radius: 50%; flex: none; }
    .fila-ir { font-size: 13px; font-weight: 600; color: var(--accent); white-space: nowrap; transition: transform .2s var(--mola); }
    .fila-item { transition: background-color .15s; }
    .fila-item:hover .fila-ir { transform: translateX(4px); }
    .seg > i { transform-origin: left; animation: crescer .7s cubic-bezier(.2,.7,.2,1) both .1s; }
    @keyframes crescer { from { transform: scaleX(0); } }
    .livro-card { animation: surgir .4s var(--mola) both; transition: border-color .15s, box-shadow .2s, transform .2s; }
    .livro-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(16,24,40,.08); }
    .fila-vazia { display: flex; align-items: center; gap: 10px; padding: 6px 10px 10px; color: var(--muted); font-size: 14px; }

    /* estante */
    .estante { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .livro-card { display: flex; flex-direction: column; gap: 10px; margin: 0; text-decoration: none; color: inherit; border-left: 4px solid var(--cor); padding: 18px 20px; }
    .livro-card h3 { font-size: 16px; margin: 0; color: var(--ink); line-height: 1.35; }
    .livro-card .desc { font-size: 13px; color: var(--muted); margin: -6px 0 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .livro-card .etqs { display: flex; gap: 6px; flex-wrap: wrap; }
    .livro-card .rodape { display: flex; justify-content: space-between; font-size: 13px; color: var(--muted); margin-top: auto; }
    .livro-vazio { grid-column: 1 / -1; }
    .livro-card .acoes-card { display: flex; gap: 8px; }
    .livro-card .acoes-card .btn { flex: 1; text-align: center; }

    @media (max-width: 680px) { .estante { grid-template-columns: minmax(0, 1fr); } .fila-ir { display: none; } }
@endsection

@section('content')
<div class="cabeca"><h1>Revisões</h1></div>
<p class="muted small intro">A fila do dia e a situação das <b>questões</b> de cada matéria. Errou, volta amanhã; acertou, o intervalo cresce.</p>

@if ($materiais->isNotEmpty())
    <section class="card fila" aria-labelledby="fila-titulo">
        <div class="secao-titulo">
            <h2 id="fila-titulo">Para revisar hoje</h2>
            @if ($fila->isNotEmpty())
                <span class="muted small">{{ $fila->sum(fn ($m) => $contagens[$m->id]['hoje']) }} questões na fila</span>
            @endif
        </div>
        @forelse ($fila as $m)
            <a class="fila-item" href="{{ route('concursos.material.refazer', [$m, 'situacao' => 'fila', 'iniciar' => 1]) }}">
                <span class="etq err" title="questões vencidas na fila de revisão">{{ $contagens[$m->id]['hoje'] }}</span>
                <span class="fila-texto">
                    <b>{{ $m->titulo }}</b>
                    <span><i class="ponto-cor" style="background: {{ $m->cor() }}"></i>{{ $contagens[$m->id]['errando'] ?? 0 }} ainda erro · {{ $contagens[$m->id]['revisando'] ?? 0 }} revisando</span>
                </span>
                <span class="fila-ir">Revisar →</span>
            </a>
        @empty
            <div class="fila-vazia">Nada vencido hoje. Quando errar uma questão, ela volta aqui amanhã.</div>
        @endforelse
    </section>
@endif

<div class="secao-titulo"><h2>Matérias</h2>@if ($materiais->isNotEmpty())<span class="muted small">{{ $materiais->count() }}</span>@endif</div>
<div class="estante">
    @forelse ($materiais as $m)
        @php
            $c = $contagens[$m->id] ?? [];
            $err = $c['errando'] ?? 0; $rev = $c['revisando'] ?? 0; $dom = $c['dominado'] ?? 0;
            $total = $err + $rev + $dom;
        @endphp
        <div class="card livro-card" style="--cor: {{ $m->cor() }}">
            <h3><a href="{{ route('concursos.material.refazer', $m) }}" style="color:inherit;text-decoration:none">{{ $m->titulo }}</a></h3>
            @if ($m->descricao)<p class="desc">{{ $m->descricao }}</p>@endif
            <div class="seg" role="img" aria-label="{{ $err }} ainda erro, {{ $rev }} revisando, {{ $dom }} dominados">
                @if ($total)
                    <i class="err" style="width: {{ $err / $total * 100 }}%"></i><i class="rev" style="width: {{ $rev / $total * 100 }}%"></i><i class="dom" style="width: {{ $dom / $total * 100 }}%"></i>
                @endif
            </div>
            <div class="etqs">
                <span class="etq err">{{ $err }} ainda erro</span>
                <span class="etq rev">{{ $rev }} revisando</span>
                <span class="etq dom">{{ $dom }} dominadas</span>
            </div>
            <div class="rodape">
                <span>{{ $total }} questões · {{ $total ? round($dom / $total * 100) : 0 }}% dominadas</span>
                @if ($c['hoje'] ?? 0)<span>{{ $c['hoje'] }} para hoje</span>@endif
            </div>
            <div class="acoes-card">
                <a class="btn" href="{{ route('concursos.material.refazer', $m) }}">Questões</a>
                @if ($c['hoje'] ?? 0)<a class="btn main" href="{{ route('concursos.material.refazer', [$m, 'situacao' => 'fila', 'iniciar' => 1]) }}">Revisar {{ $c['hoje'] }}</a>@endif
            </div>
        </div>
    @empty
        <div class="card muted livro-vazio">Nenhuma matéria ainda. Crie um livro na aba <a href="{{ route('concursos.material.index') }}">Resumos</a>.</div>
    @endforelse
</div>

@endsection
