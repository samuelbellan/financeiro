@extends('concursos.layout')

@section('title', 'Desempenho')

@php
    $pct = fn ($v) => $v === null ? '—' : round(100 * $v) . '%';
    $num = fn ($v, $c = 1) => number_format($v, $c, ',', '.');
    $prova = $perfil->cargo->edital->data_prova;
    $maxSemana = max(1, max(array_column($d['semanas'], 'n')));
    $maxMotivo = max(1, $d['motivos']->max() ?? 1);
@endphp

@section('css')
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
    .kpi { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
    .kpi .v { font-size: 30px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .kpi .r { font-size: 13px; color: var(--muted); }
    h2 { font-size: 17px; margin: 0 0 12px; }
    .area { margin-bottom: 12px; }
    .area summary { list-style: none; cursor: pointer; display: grid; grid-template-columns: 1fr auto; gap: 6px 16px; align-items: center; }
    .area summary::-webkit-details-marker { display: none; }
    .area .nome { font-weight: 700; }
    .area .nome::before { content: '▸ '; color: var(--muted); }
    .area[open] .nome::before { content: '▾ '; }
    .area .meta { grid-column: 1 / -1; display: flex; gap: 6px 16px; flex-wrap: wrap; font-size: 13px; color: var(--muted); }
    .trilho { grid-column: 1 / -1; height: 10px; background: var(--line); border-radius: 99px; overflow: hidden; }
    .trilho > span { display: block; height: 100%; background: var(--accent); border-radius: 0 4px 4px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 12px; }
    th, td { text-align: left; padding: 8px 6px; border-top: 1px solid var(--line); vertical-align: top; }
    th { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mini { display: inline-block; width: 60px; height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; vertical-align: middle; margin-left: 6px; }
    .mini > span { display: block; height: 100%; background: var(--accent); }
    .semanas { display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; align-items: end; height: 160px; margin-top: 8px; }
    .semana { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; gap: 4px; font-size: 12px; color: var(--muted); }
    .semana .col { width: 100%; max-width: 40px; background: var(--accent); border-radius: 4px 4px 0 0; min-height: 2px; }
    .semana .col.vazia { background: var(--line); }
    .semana b { color: var(--ink); font-variant-numeric: tabular-nums; }
    .hbar { display: grid; grid-template-columns: minmax(120px, 220px) 1fr auto; gap: 8px; align-items: center; font-size: 14px; padding: 4px 0; }
    .hbar .t { height: 10px; background: var(--line); border-radius: 99px; overflow: hidden; }
    .hbar .t > span { display: block; height: 100%; background: var(--accent); border-radius: 0 4px 4px 0; }
    .dois { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
    .aviso { font-size: 13px; color: var(--warn); }
    .seletor { display: flex; gap: 8px; align-items: center; }
    .seletor select { padding: 8px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--ink); font: inherit; }
    @media (max-width: 560px) { .hide-sm { display: none; } .hbar { grid-template-columns: 1fr auto; } .hbar .t { grid-column: 1 / -1; grid-row: 2; } }
@endsection

@section('content')
    <div class="top">
        <h1>Desempenho{{ $proprio ? '' : ' de ' . $aluno?->name }}</h1>
        @if ($alunos->count() > 1)
            <form class="seletor" method="GET" action="{{ route('concursos.painel') }}">
                <label for="aluno" class="muted small">Aluno</label>
                <select id="aluno" name="aluno" onchange="this.form.submit()">
                    @foreach ($alunos as $u)<option value="{{ $u->id }}" @selected($u->id === $aluno?->id)>{{ $u->name }}</option>@endforeach
                </select>
                <noscript><button class="btn">Ver</button></noscript>
            </form>
        @endif
    </div>

    <div class="kpis" style="margin-bottom:16px">
        <div class="kpi"><div class="v">{{ $num($d['total']['estimado']) }}</div><div class="r">nota estimada, de {{ $num($d['total']['pontos'], 0) }} pontos</div></div>
        <div class="kpi"><div class="v">{{ $num($d['tentativas_total'], 0) }}</div><div class="r">questões respondidas</div></div>
        @if ($prova)<div class="kpi"><div class="v">{{ (int) today()->diffInDays($prova, false) }}</div><div class="r">dias até a prova ({{ $prova->format('d/m') }})</div></div>@endif
        @if ($simulados->isNotEmpty())
            <div class="kpi"><div class="v">{{ $num($simulados->first()->nota_total) }}</div><div class="r">último simulado ({{ $simulados->first()->finalizado_em->format('d/m') }}), de {{ $num($simulados->first()->pontos_total, 0) }} pontos</div></div>
        @endif
    </div>
    <p class="muted small" style="margin:-4px 0 16px">A nota estimada soma, tópico a tópico, quanto ele vale na prova × o seu domínio. Tópico em que você ainda não fez questões usa o que você declarou no perfil, então ela fica mais confiável conforme você resolve.</p>

    <div class="card">
        <h2>Por área</h2>
        @foreach ($d['areas'] as $a)
            <details class="area">
                <summary>
                    <span class="nome">{{ $a['nome'] }}</span>
                    <span class="small" style="font-variant-numeric:tabular-nums"><b>{{ $num($a['estimado']) }}</b> de {{ $num($a['pontos'], 0) }} pt</span>
                    <span class="trilho" title="{{ $a['nome'] }}: {{ $num($a['estimado']) }} de {{ $num($a['pontos'], 0) }} pontos estimados"><span style="width: {{ $a['pontos'] ? min(100, round(100 * $a['estimado'] / $a['pontos'])) : 0 }}%"></span></span>
                    <span class="meta">
                        <span>acerto {{ $pct($a['acerto']) }} em {{ $a['tentativas'] }} questões</span>
                        <span>cobertura {{ $pct($a['cobertura']) }} <span class="hide-sm">(pontos em tópicos com {{ \App\Services\Concursos\PainelDesempenho::MIN_COBERTO }}+ questões feitas)</span></span>
                        @if ($a['sem_questoes'])<span class="aviso">sem questões no banco: importe o caderno desta área</span>@endif
                    </span>
                </summary>
                <table>
                    <thead><tr><th>Tópico</th><th class="n">Vale</th><th class="n hide-sm">Feitas</th><th class="n">Acerto</th><th class="n">Domínio</th></tr></thead>
                    <tbody>
                    @foreach ($a['topicos'] as $t)
                        <tr>
                            <td>{{ $t['topico']->ordem }} {{ \Illuminate\Support\Str::limit($t['topico']->texto, 90) }}
                                @if (!$t['questoes'])<br><span class="muted small">sem questões no banco</span>@endif</td>
                            <td class="n">{{ $num($t['valor']) }}</td>
                            <td class="n hide-sm">{{ $t['tentativas'] }}</td>
                            <td class="n">{{ $t['tentativas'] ? $pct($t['acertos'] / $t['tentativas']) : '—' }}</td>
                            <td class="n">{{ $pct($t['dominio']) }}<span class="mini" title="domínio {{ $pct($t['dominio']) }}"><span style="width: {{ round(100 * $t['dominio']) }}%"></span></span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </details>
        @endforeach
    </div>

    <div class="card">
        <h2>Questões por semana</h2>
        <div class="semanas" role="img" aria-label="Questões respondidas nas últimas 8 semanas">
            @foreach ($d['semanas'] as $s)
                <div class="semana" title="Semana de {{ $s['inicio']->format('d/m') }}: {{ $s['n'] }} questões{{ $s['acerto'] !== null ? ', ' . $pct($s['acerto']) . ' de acerto' : '' }}">
                    <b>{{ $s['n'] ?: '' }}</b>
                    <div class="col {{ $s['n'] ? '' : 'vazia' }}" style="height: {{ max(2, round(110 * $s['n'] / $maxSemana)) }}px"></div>
                    <span>{{ $s['inicio']->format('d/m') }}</span>
                    <span>{{ $s['acerto'] !== null ? $pct($s['acerto']) : '' }}</span>
                </div>
            @endforeach
        </div>
        <p class="muted small" style="margin:8px 0 0">Barra = questões respondidas na semana; embaixo, a data de início e o acerto.</p>
    </div>

    <div class="dois">
        <div class="card">
            <h2>Certeza × acerto</h2>
            <table>
                <thead><tr><th>Você disse</th><th class="n">Questões</th><th class="n">Acertou</th></tr></thead>
                <tbody>
                @foreach ($d['certeza'] as $c)
                    <tr><td>{{ $c['rotulo'] }}</td><td class="n">{{ $c['n'] }}</td><td class="n">{{ $pct($c['acerto']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @php($sei = $d['certeza']['sei'])
            @if ($sei['n'] >= 10 && $sei['acerto'] < 0.8)
                <p class="aviso" style="margin:10px 0 0">Quando você diz "Sei", acerta só {{ $pct($sei['acerto']) }}: há confiança demais em algum conteúdo. Veja os motivos de erro.</p>
            @else
                <p class="muted small" style="margin:10px 0 0">O ideal: "Sei" perto de 100%. Acerto no chute não conta como domínio.</p>
            @endif
        </div>

        <div class="card">
            <h2>Por que você erra</h2>
            @forelse ($d['motivos'] as $motivo => $n)
                <div class="hbar" title="{{ $motivo }}: {{ $n }}">
                    <span>{{ $motivo }}</span>
                    <span class="t"><span style="width: {{ round(100 * $n / $maxMotivo) }}%"></span></span>
                    <b style="font-variant-numeric:tabular-nums">{{ $n }}</b>
                </div>
            @empty
                <p class="muted small" style="margin:0">Ainda sem erros com motivo nas últimas 8 semanas.</p>
            @endforelse
        </div>
    </div>

    @if ($simulados->isNotEmpty())
        <div class="card">
            <h2>Simulados</h2>
            <table>
                <thead><tr><th>Data</th><th class="n">Nota</th><th class="n">Acertos</th><th></th></tr></thead>
                <tbody>
                @foreach ($simulados as $s)
                    <tr><td>{{ $s->finalizado_em->format('d/m/Y') }}</td><td class="n">{{ $num($s->nota_total) }} / {{ $num($s->pontos_total, 0) }}</td>
                        <td class="n">{{ $s->acertos }} / {{ count($s->questao_ids) }}</td>
                        <td class="n">@if ($proprio)<a href="{{ route('concursos.simulados.show', $s) }}">ver</a>@endif</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
