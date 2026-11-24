@extends('concursos.layout')

@section('title', 'Uso')

@php
    $h = fn (int $min) => $min >= 60 ? intdiv($min, 60) . 'h' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) : $min . ' min';
    $pct = fn (int $a, int $n) => $n ? round(100 * $a / $n) . '%' : '—';
    $quando = fn ($c) => $c?->isToday() ? 'hoje ' . $c->format('H:i') : ($c?->isYesterday() ? 'ontem ' . $c->format('H:i') : $c?->format('d/m H:i'));
    $link = fn (array $q) => route('concursos.uso', array_filter(array_merge(['dias' => $dias, 'aluno' => $aluno?->id], $q)));
@endphp

@section('css')
    h2 { font-size: 17px; margin: 0 0 12px; }
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .kpi { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 14px; }
    .kpi .v { font-size: 26px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .kpi .r { font-size: 13px; color: var(--muted); }
    .tabela { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 8px 6px; border-top: 1px solid var(--line); vertical-align: top; }
    th { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    tr.sel td { background: var(--accent-soft); }
    .periodos { display: flex; gap: 6px; flex-wrap: wrap; }
    .periodos a { padding: 6px 10px; border-radius: 99px; border: 1px solid var(--line); text-decoration: none; color: var(--muted); font-size: 14px; font-weight: 600; }
    .periodos a[aria-current=true] { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
    .hbar { display: grid; grid-template-columns: minmax(110px, 200px) 1fr auto; gap: 8px; align-items: center; font-size: 14px; padding: 4px 0; }
    .hbar .t { height: 10px; background: var(--line); border-radius: 99px; overflow: hidden; }
    .hbar .t > span { display: block; height: 100%; background: var(--accent); }
    .ev { font-size: 14px; padding: 6px 0; border-top: 1px solid var(--line); display: grid; grid-template-columns: 92px 1fr auto; gap: 8px; }
    .ev .q { color: var(--muted); font-size: 12px; word-break: break-all; }
    .tag { font-size: 12px; color: var(--muted); }
    .erro { color: var(--no); font-weight: 600; }
    @media (max-width: 560px) { .hide-sm { display: none; } .hbar { grid-template-columns: 1fr auto; } .hbar .t { grid-column: 1 / -1; grid-row: 2; } .ev { grid-template-columns: 70px 1fr; } .ev .n { grid-column: 2; } }
@endsection

@section('content')
    <div class="top">
        <h1>Uso do módulo{{ $aluno ? ' · ' . $aluno->name : '' }}</h1>
        <nav class="periodos" aria-label="Período">
            @foreach (\App\Http\Controllers\Concursos\UsoController::PERIODOS as $p)
                <a href="{{ $link(['dias' => $p]) }}" @if ($p === $dias) aria-current="true" @endif>{{ $p }} dias</a>
            @endforeach
        </nav>
    </div>

    <div class="card">
        <h2>Alunos nos últimos {{ $dias }} dias</h2>
        @if (empty($geral))
            <p class="muted">Nenhum uso registrado no período. A telemetria começa a contar a partir de agora.</p>
        @else
            <div class="tabela">
                <table>
                    <thead><tr><th>Aluno</th><th>Último acesso</th><th class="n">Dias</th><th class="n hide-sm">Sessões</th><th class="n">Tempo</th><th class="n">Questões</th><th class="n hide-sm">Acerto</th></tr></thead>
                    <tbody>
                    @foreach ($geral as $l)
                        <tr @class(['sel' => $aluno?->id === $l['user_id']])>
                            <td><a href="{{ $link(['aluno' => $l['user_id']]) }}">{{ $nomes[$l['user_id']] ?? '#' . $l['user_id'] }}</a> <span class="tag">{{ $l['dispositivo'] }}</span></td>
                            <td>{{ $quando($l['ultimo']) }}</td>
                            <td class="n">{{ $l['dias'] }}</td>
                            <td class="n hide-sm">{{ $l['sessoes'] }}</td>
                            <td class="n">{{ $h($l['minutos']) }}</td>
                            <td class="n">{{ $l['questoes'] }}</td>
                            <td class="n hide-sm">{{ $pct($l['acertos'], $l['questoes']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @unless ($aluno)<p class="muted small" style="margin:12px 0 0">Toque no nome para ver o detalhe. Tempo = tela à vista no navegador.</p>@endunless
        @endif
    </div>

    @if ($aluno && $d)
        @php $k = $d['kpis']; @endphp
        <div class="kpis">
            <div class="kpi"><div class="v">{{ $k['dias'] }}</div><div class="r">dias com acesso</div></div>
            <div class="kpi"><div class="v">{{ $h($k['minutos']) }}</div><div class="r">tempo de tela, em {{ $k['sessoes'] }} sessões</div></div>
            <div class="kpi"><div class="v">{{ $k['questoes'] }}</div><div class="r">questões, {{ $pct($k['acertos'], $k['questoes']) }} de acerto</div></div>
            <div class="kpi"><div class="v">{{ $k['tempo_medio_s'] !== null ? $k['tempo_medio_s'] . 's' : '—' }}</div><div class="r">tempo médio por questão</div></div>
            <div class="kpi"><div class="v" style="font-size:18px">{{ $quando($k['ultimo']) ?? '—' }}</div><div class="r">último acesso @if ($k['erros'])· <span class="erro">{{ $k['erros'] }} erro(s) do sistema</span>@endif</div></div>
        </div>

        <div class="card">
            <h2>Por dia</h2>
            <div class="tabela">
                <table>
                    <thead><tr><th>Dia</th><th class="n">Tempo</th><th class="n">Ações</th><th class="n">Questões</th><th class="n">Acerto</th></tr></thead>
                    <tbody>
                    @forelse ($d['dias'] as $dia)
                        @php $c = \Illuminate\Support\Carbon::parse($dia['data']); @endphp
                        <tr><td>{{ $c->translatedFormat('D d/m') }}</td><td class="n">{{ $h($dia['minutos']) }}</td><td class="n">{{ $dia['eventos'] }}</td><td class="n">{{ $dia['questoes'] }}</td><td class="n">{{ $pct($dia['acertos'], $dia['questoes']) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="muted">Sem uso no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($d['origens'])
                <p class="muted small" style="margin:12px 0 0">Questões por origem: @foreach ($d['origens'] as $o => $n){{ $o }} {{ $n }}@if (!$loop->last) · @endif @endforeach</p>
            @endif
        </div>

        <div class="card">
            <h2>Telas mais usadas</h2>
            @php $maxT = max(1, collect($d['telas'])->max('minutos') ?? 1); $maxV = max(1, collect($d['telas'])->max('vezes') ?? 1); @endphp
            @forelse (array_slice($d['telas'], 0, 15) as $t)
                <div class="hbar">
                    <span>{{ $t['tela'] }}</span>
                    <span class="t"><span style="width: {{ $t['minutos'] ? round(100 * $t['minutos'] / $maxT) : round(100 * $t['vezes'] / $maxV) }}%"></span></span>
                    <span class="small muted" style="font-variant-numeric:tabular-nums">{{ $h($t['minutos']) }} · {{ $t['vezes'] }}×</span>
                </div>
            @empty
                <p class="muted">Nada ainda.</p>
            @endforelse
        </div>

        <div class="card">
            <h2>Sessões</h2>
            <div class="tabela">
                <table>
                    <thead><tr><th>Início</th><th class="n">Duração</th><th class="n hide-sm">Ações</th><th>Telas</th></tr></thead>
                    <tbody>
                    @foreach ($d['sessoes'] as $s)
                        <tr><td>{{ $quando($s['inicio']) }} <span class="tag">{{ $s['dispositivo'] }}</span></td><td class="n">{{ $h($s['minutos']) }}</td><td class="n hide-sm">{{ $s['eventos'] }}</td><td class="small">{{ $s['telas'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2>Últimas ações</h2>
            @foreach ($d['recentes'] as $e)
                <div class="ev">
                    <span class="small muted">{{ $quando($e->created_at) }}</span>
                    <span>{{ \App\Models\Concursos\UsoEvento::tela($e->rota) }}
                        @if ($e->status >= 400)<span class="erro">({{ $e->status }})</span>@endif
                        @if ($e->parametros)<div class="q">{{ collect($e->parametros)->flatMap(fn ($v, $k) => collect((array) $v)->map(fn ($x, $kk) => $kk . '=' . (is_array($x) ? json_encode($x, JSON_UNESCAPED_UNICODE) : $x)))->implode(' ') }}</div>@endif
                    </span>
                    <span class="n small muted">{{ $e->visivel_s === null ? '' : ($e->visivel_s < 60 ? $e->visivel_s . 's' : $h((int) round($e->visivel_s / 60))) }}</span>
                </div>
            @endforeach
        </div>
    @endif
@endsection
