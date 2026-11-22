@extends('concursos.layout')

@section('title', 'Curadoria')

@section('css')
    h2 { font-size: 15px; margin: 0 0 8px; color: var(--muted); }
    .linha { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-top: 1px solid var(--line); text-decoration: none; color: var(--ink); }
    .linha:first-of-type { border-top: 0; }
    .linha .nome { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .tag { font-size: 12px; padding: 2px 8px; border-radius: 99px; font-weight: 600; white-space: nowrap; }
    .tag.pend { background: var(--warn-soft); color: var(--warn); }
    .tag.ok { background: var(--ok-soft); color: var(--ok); }
@endsection

@section('content')
    <div class="top">
        <h1>Curadoria dos subtópicos</h1>
    </div>

    <div class="card">
        <b>{{ $pendentes }}</b> subtópico(s) esperando revisão.
        <span class="muted small">Aprovados entram na lista fechada dos próximos pacotes: o Gemini passa a usar o id em vez de inventar nomes novos.
        Juntar parecidos evita que o mesmo conteúdo fique dividido.</span>
    </div>

    @forelse ($grupos as $disciplina => $assuntos)
        <div class="card">
            <h2>{{ $disciplina }}</h2>
            @foreach ($assuntos as $a)
                @php($c = $contagens[$a->id])
                <a class="linha" href="{{ route('concursos.curadoria.assunto', $a) }}">
                    <span class="nome">{{ $a->nome }}</span>
                    @if ($c->pendentes)<span class="tag pend">{{ $c->pendentes }} pendente(s)</span>@endif
                    @if ($c->total - $c->pendentes)<span class="tag ok">{{ $c->total - $c->pendentes }} aprovado(s)</span>@endif
                </a>
            @endforeach
        </div>
    @empty
        <div class="card">Nenhum subtópico ainda. Eles chegam com o ledger do Gemini (<code>php artisan concursos:importar-gemini</code>).</div>
    @endforelse
@endsection
