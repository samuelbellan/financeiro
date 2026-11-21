@extends('concursos.layout')

@section('title', 'Curadoria · ' . \Illuminate\Support\Str::limit($assunto->nome, 40))

@section('css')
    .sub { display: grid; gap: 10px; }
    .sub.aprovado { opacity: .75; }
    .sub .cab { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .sub input[type=text], .sub textarea, .sub select { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--ink); font: inherit; }
    .sub textarea { min-height: 54px; font-size: 14px; resize: vertical; }
    .sub .nome { font-weight: 600; }
    .tag { font-size: 12px; padding: 2px 8px; border-radius: 99px; font-weight: 600; white-space: nowrap; }
    .tag.pend { background: var(--warn-soft); color: var(--warn); }
    .tag.ok { background: var(--ok-soft); color: var(--ok); }
    .ex { margin: 0; padding-left: 18px; font-size: 14px; color: var(--muted); }
    .parecido { background: var(--accent-soft); border-radius: 10px; padding: 8px 12px; font-size: 14px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .parecido form { margin: 0; }
    .parecido button { font-size: 13px; padding: 4px 10px; }
    .acoes { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .acoes .juntar { display: flex; gap: 6px; flex: 1; min-width: 240px; }
    .acoes .juntar select { flex: 1; min-width: 0; }
    .btn.perigo { color: var(--no); }
    .btn.sm { padding: 6px 12px; font-size: 14px; }
@endsection

@section('content')
    <div class="top">
        <a class="btn" href="{{ route('concursos.curadoria') }}" aria-label="Voltar à lista">←</a>
        <h1>{{ $assunto->nome }} <span class="muted small" style="font-weight:400">· {{ $assunto->disciplina->nome }}</span></h1>
        @if ($subs->where('status', 'pendente')->count())
            <form method="POST" action="{{ route('concursos.curadoria.aprovar-todos', $assunto) }}" onsubmit="return confirm('Aprovar todos os pendentes deste assunto com os nomes atuais?')">@csrf<button class="btn">Aprovar todos</button></form>
        @endif
        @if ($proximo)<a class="btn main" href="{{ route('concursos.curadoria.assunto', $proximo) }}">Próximo assunto →</a>@endif
    </div>

    @if ($errors->any())<div class="alerta" role="alert">{{ $errors->first() }}</div>@endif

    <div id="lista"></div>
    @forelse ($subs as $s)
        @php($n = $contagem[$s->id]->n ?? 0)
        <div class="card sub {{ $s->status }}">
            <div class="cab">
                <span class="tag {{ $s->status === 'pendente' ? 'pend' : 'ok' }}">{{ $s->status }}</span>
                <span class="muted small">{{ $n }} questão(ões) · id <code>{{ $s->slug }}</code></span>
            </div>

            @if (($exemplos[$s->id] ?? collect())->isNotEmpty())
                <ul class="ex">@foreach ($exemplos[$s->id] as $p)<li>{{ $p }}</li>@endforeach</ul>
            @endif

            @foreach ($parecidos[$s->id] ?? [] as $o)
                <div class="parecido">
                    <span>Parece com <b>{{ $o->nome }}</b>@if ($o->assunto_id !== $assunto->id) <span class="muted">({{ $o->assunto->nome }})</span>@endif · {{ $o->status }}</span>
                    <form method="POST" action="{{ route('concursos.curadoria.acao', $s) }}">@csrf
                        <input type="hidden" name="acao" value="juntar"><input type="hidden" name="destino" value="{{ $o->id }}">
                        <button class="btn sm">Juntar neste</button>
                    </form>
                </div>
            @endforeach

            <form method="POST" action="{{ route('concursos.curadoria.acao', $s) }}" class="sub" style="gap:8px">@csrf
                <label class="visually-hidden" for="nome-{{ $s->id }}">Nome</label>
                <input type="text" id="nome-{{ $s->id }}" name="nome" class="nome" value="{{ $s->nome }}" maxlength="150">
                <textarea name="descricao" aria-label="Descrição" placeholder="Descrição (o que cai aqui)">{{ $s->descricao }}</textarea>
                <div class="acoes">
                    <button class="btn main sm" name="acao" value="aprovar">{{ $s->status === 'pendente' ? 'Aprovar' : 'Salvar' }}</button>
                    <span class="juntar">
                        <select name="destino" aria-label="Juntar em">
                            <option value="">Juntar em…</option>
                            @foreach ($daDisciplina->where('id', '!=', $s->id)->groupBy(fn ($o) => $o->assunto->nome) as $nomeAssunto => $lista)
                                <optgroup label="{{ \Illuminate\Support\Str::limit($nomeAssunto, 60) }}">
                                    @foreach ($lista as $o)<option value="{{ $o->id }}">{{ $o->nome }}{{ $o->status === 'pendente' ? ' (pendente)' : '' }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button class="btn sm" name="acao" value="juntar">Juntar</button>
                    </span>
                    <button class="btn sm perigo" name="acao" value="excluir" onclick="return confirm('Excluir este subtópico? As questões continuam no assunto, só perdem este vínculo.')">Excluir</button>
                </div>
            </form>
        </div>
    @empty
        <div class="card">Este assunto não tem subtópicos.</div>
    @endforelse
@endsection
