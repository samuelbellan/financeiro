@extends('concursos.layout')

@section('title', 'Resultado do simulado')

@php
    $num = fn ($v, $c = 1) => number_format($v, $c, ',', '.');
    $respostas = $s->respostas ?? [];
    $faltaram = collect($s->resultado)->sum(fn ($r) => $r['previstas'] - $r['feitas']);
    $n = 0;
@endphp

@section('css')
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .kpi { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
    .kpi .v { font-size: 30px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .kpi .r { font-size: 13px; color: var(--muted); }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 8px 6px; border-top: 1px solid var(--line); vertical-align: top; }
    th { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
    td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ok { color: var(--ok); font-weight: 700; }
    .no { color: var(--no); font-weight: 700; }
    .gab { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 6px; }
    .gab a { display: flex; gap: 6px; align-items: center; padding: 6px 10px; border: 1px solid var(--line); border-radius: 8px; text-decoration: none; color: var(--ink); font-size: 14px; font-variant-numeric: tabular-nums; }
    .gab a.errou { border-color: var(--no); background: var(--no-soft); }
    .gab a.acertou { border-color: var(--ok); background: var(--ok-soft); }
    h2 { font-size: 17px; margin: 0 0 8px; }
@endsection

@section('content')
    <div class="top">
        <h1>Simulado de {{ $s->iniciado_em->format('d/m/Y') }}</h1>
        <a class="btn" href="{{ route('concursos.simulados') }}">Novo simulado</a>
    </div>

    <div class="kpis">
        <div class="kpi"><div class="v">{{ $num($s->nota_total) }}</div><div class="r">pontos, de {{ $num($s->pontos_total, 0) }} possíveis</div></div>
        <div class="kpi"><div class="v">{{ $s->acertos }}/{{ count($s->questao_ids) }}</div><div class="r">acertos</div></div>
        <div class="kpi"><div class="v">{{ $s->iniciado_em->diffInMinutes($s->finalizado_em) < 1 ? '<1' : (int) $s->iniciado_em->diffInMinutes($s->finalizado_em) }}</div><div class="r">minutos, de {{ $s->duracao_min }}</div></div>
    </div>
    @if ($faltaram > 0)
        <div class="alerta">Faltaram {{ $faltaram }} questão(ões) em áreas sem questões no banco: a nota é sobre {{ $num($s->pontos_total, 0) }} pontos, não sobre a prova inteira.</div>
    @endif

    <div class="card">
        <h2>Por área</h2>
        <table>
            <thead><tr><th>Área</th><th class="n">Acertos</th><th class="n">Pontos</th></tr></thead>
            <tbody>
            @foreach ($s->resultado as $r)
                <tr><td>{{ $r['area'] }}</td><td class="n">{{ $r['acertos'] }}/{{ $r['feitas'] }}{{ $r['feitas'] < $r['previstas'] ? " (de {$r['previstas']})" : '' }}</td>
                    <td class="n">{{ $num($r['pontos']) }} / {{ $num($r['pontos_max'], 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Gabarito</h2>
        <p class="muted small" style="margin-top:0">Clique para ver a questão com a resolução. Os erros já entraram na sua revisão de amanhã.</p>
        @foreach ($s->composicao as $c)
            @continue(!$c['questao_ids'])
            <h3 class="muted small" style="margin:12px 0 6px">{{ $c['area'] }}</h3>
            <div class="gab">
                @foreach ($c['questao_ids'] as $qid)
                    @php($q = $questoes[$qid] ?? null)
                    @continue(!$q)
                    @php($t = $tentativas[$qid] ?? null)
                    @php($n++)
                    <a class="{{ $t ? ($t->correta ? 'acertou' : 'errou') : '' }}" href="{{ route('concursos.questoes.show', ['questao' => $qid] + ($t ? ['t' => $t->id] : [])) }}">
                        <b>{{ $n }}</b>
                        @if ($t)
                            <span class="{{ $t->correta ? 'ok' : 'no' }}">{{ $t->correta ? '✓' : '✗' }}</span>
                            <span>{{ $t->resposta }}{{ $t->correta ? '' : ' → ' . $q->gabarito }}</span>
                            @if ($t->certeza === 'chute')<span class="muted small">chute</span>@endif
                        @else
                            <span class="muted">em branco → {{ $q->gabarito }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
