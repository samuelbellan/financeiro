@extends('concursos.layout')

@section('title', 'Resumos')

@section('css')
    .lista { display: grid; gap: 12px; }
    .mat { display: block; text-decoration: none; color: inherit; }
    .mat h2 { font-size: 18px; margin: 0 0 4px; color: var(--ink); }
    .mat .nums { display: flex; gap: 12px; flex-wrap: wrap; font-size: 14px; color: var(--muted); margin-top: 8px; }
    .mat .nums b { font-variant-numeric: tabular-nums; }
    .mat .err b { color: var(--no); }
    .mat .dom b { color: var(--ok); }
    .form { display: flex; gap: 8px; flex-wrap: wrap; }
    .form input { flex: 1; min-width: 200px; border: 1px solid var(--line); background: var(--bg); color: var(--ink); border-radius: 10px; padding: 10px 12px; font: inherit; }
@endsection

@section('content')
<div class="top"><h1>Resumos</h1></div>
<p class="muted small" style="margin-top:-8px">Um livro por matéria, com capítulos por assunto. Escreva, grife, anote na margem e acompanhe os pontos que você ainda erra.</p>

<div class="lista">
    @forelse ($materiais as $m)
        @php $c = $contagens[$m->id] ?? collect(); @endphp
        <a class="card mat" href="{{ route('concursos.material.show', $m) }}">
            <h2>{{ $m->titulo }}</h2>
            @if ($m->descricao)<div class="muted small">{{ $m->descricao }}</div>@endif
            <div class="nums">
                <span><b>{{ $capitulos[$m->id] ?? 0 }}</b> capítulos</span>
                <span class="err"><b>{{ $c['errando'] ?? 0 }}</b> ainda erro</span>
                <span><b>{{ $c['revisando'] ?? 0 }}</b> revisando</span>
                <span class="dom"><b>{{ $c['dominado'] ?? 0 }}</b> dominados</span>
            </div>
        </a>
    @empty
        <div class="card muted">Nenhum livro ainda. Crie um abaixo ou importe os cadernos com <code>php artisan concursos:importar-material</code>.</div>
    @endforelse
</div>

<div class="card" style="margin-top:16px">
    <form method="POST" action="{{ route('concursos.material.store') }}" class="form">
        @csrf
        <input name="titulo" required maxlength="160" placeholder="Novo livro (ex.: Direito Administrativo)" aria-label="Título do novo livro">
        <button class="btn main" type="submit">Criar livro</button>
    </form>
</div>
@endsection
