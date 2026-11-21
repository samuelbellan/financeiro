@extends('concursos.layout')

@section('title', 'Perfil de estudo')

@section('css')
    .dias { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
    .dias label { display: grid; gap: 4px; font-size: 13px; font-weight: 600; text-align: center; }
    .dias input { width: 100%; padding: 8px 4px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--ink); font: inherit; text-align: center; }
    select { padding: 8px; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--ink); font: inherit; max-width: 100%; }
    h2 { font-size: 17px; margin: 0 0 4px; }
    h3 { font-size: 15px; margin: 16px 0 4px; color: var(--muted); }
    .topico { display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; align-items: center; padding: 10px 0; border-top: 1px solid var(--line); }
    .topico .tx { font-size: 15px; overflow-wrap: anywhere; }
    .topico .ix { grid-column: 1 / -1; font-size: 12px; color: var(--muted); display: flex; gap: 12px; flex-wrap: wrap; }
    .seg { display: inline-flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
    .seg label { position: relative; }
    .seg input { position: absolute; opacity: 0; }
    .seg span { display: block; padding: 6px 10px; font-size: 13px; cursor: pointer; white-space: nowrap; }
    .seg label + label span { border-left: 1px solid var(--line); }
    .seg input:checked + span { background: var(--accent); color: var(--accent-ink); }
    .seg input:focus-visible + span { outline: 2px solid var(--accent); }
    .salvar { position: sticky; bottom: 0; display: flex; justify-content: flex-end; padding: 12px 0; background: linear-gradient(transparent, var(--bg) 40%); }
    .todos { display: flex; gap: 6px; flex-wrap: wrap; font-size: 13px; }
    .todos button { border: 1px solid var(--line); background: var(--card); color: var(--ink); border-radius: 8px; padding: 4px 8px; font: inherit; font-size: 13px; cursor: pointer; }
    @media (max-width: 560px) { .dias { grid-template-columns: repeat(4, minmax(0, 1fr)); } .topico { grid-template-columns: 1fr; } }
@endsection

@section('content')
    <div class="top">
        <h1>Perfil de estudo</h1>
        @if ($perfil)<a class="btn" href="{{ route('concursos.meta') }}">← Meta do dia</a>@endif
    </div>

    @if (!$cargo)
        <div class="card">Nenhum edital importado ainda. Use <code>php artisan concursos:importar-edital</code>.</div>
    @else
    <form method="POST" action="{{ route('concursos.perfil.salvar') }}">
        @csrf
        @if ($errors->any())<div class="alerta" role="alert">{{ $errors->first() }}</div>@endif

        <div class="card">
            <h2>Concurso</h2>
            <select name="cargo_id" aria-label="Cargo">
                @foreach ($cargos as $c)
                    <option value="{{ $c->id }}" @selected($c->id === $cargo->id)>{{ $c->edital->orgao }} {{ $c->edital->numero }} · {{ $c->codigo }} {{ \Illuminate\Support\Str::limit($c->nome, 60) }}</option>
                @endforeach
            </select>
        </div>

        <div class="card">
            <h2>Minutos de estudo por dia</h2>
            <p class="muted small" style="margin-top:0">O motor monta a meta para caber nesse tempo. 0 = folga.</p>
            <div class="dias">
                @foreach ($dias as $n => $nome)
                    <label>{{ $nome }}<input type="number" name="minutos[{{ $n }}]" min="0" max="900" step="10" inputmode="numeric"
                        value="{{ old("minutos.$n", $perfil?->minutos_semana[(string) $n] ?? ($n <= 5 ? 120 : 180)) }}"></label>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2>O que você já estudou?</h2>
            <p class="muted small" style="margin-top:0">"Não vi" põe Teoria antes das questões. Isso só serve de ponto de partida: depois, quem manda é o seu acerto nas questões.</p>
            @foreach ($cargo->areas as $area)
                @foreach ($area->disciplinas as $disc)
                    <h3>{{ $area->nome }} · {{ $disc->nome }}</h3>
                    <div class="todos" data-disc="{{ $disc->id }}">Marcar todos:
                        @foreach ($opcoes as $k => $v)<button type="button" data-valor="{{ $k }}">{{ $v }}</button>@endforeach
                    </div>
                    @foreach ($disc->topicos as $t)
                        @php($ix = $indices[$t->id] ?? null)
                        <div class="topico" data-disc="{{ $disc->id }}">
                            <div class="tx">{{ $t->ordem }} {{ $t->texto }}</div>
                            <div class="seg" role="radiogroup" aria-label="Quanto você viu do tópico {{ $t->ordem }}">
                                @foreach ($opcoes as $k => $v)
                                    <label><input type="radio" name="topicos[{{ $t->id }}]" value="{{ $k }}" @checked(old("topicos.{$t->id}", $declaracoes[$t->id] ?? null) === $k)><span>{{ $v }}</span></label>
                                @endforeach
                            </div>
                            @if ($ix)
                                <div class="ix">
                                    <span>vale ~{{ number_format($ix['valor'], 1, ',', '') }} pt</span>
                                    @if ($ix['questoes_tec'])<span>{{ $ix['questoes_tec'] }} no TEC</span>@endif
                                    <span>{{ count($ix['questoes']) }} no banco</span>
                                    @if ($ix['tentativas'])<span>acerto {{ round(100 * $ix['acertos'] / $ix['tentativas']) }}% em {{ $ix['tentativas'] }}</span>@endif
                                    <span>domínio estimado {{ round(100 * $ix['dominio']) }}%</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            @endforeach
        </div>

        <div class="salvar"><button class="btn main" type="submit">Salvar perfil</button></div>
    </form>
    @endif
@endsection

@section('js')
<script>
document.querySelectorAll('.todos button').forEach((b) => b.addEventListener('click', () => {
    const disc = b.parentElement.dataset.disc;
    document.querySelectorAll(`.topico[data-disc="${disc}"] input[value="${b.dataset.valor}"]`).forEach((i) => { i.checked = true; });
}));
</script>
@endsection
