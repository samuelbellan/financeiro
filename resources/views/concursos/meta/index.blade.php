@extends('concursos.layout')

@section('title', 'Meta do dia')

@php
    $diasNome = ['', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'];
    $hm = fn (int $m) => $m >= 60 ? intdiv($m, 60) . 'h' . ($m % 60 ? str_pad($m % 60, 2, '0', STR_PAD_LEFT) : '') : $m . ' min';
    $pct = fn ($v) => $v === null ? '—' : round(100 * $v) . '%';
    $hoje = collect($jornada['semana'])->firstWhere('hoje', true);
    $total = $hoje['planejado'];
    $feitos = $hoje['feito'];
    $pctHoje = $total ? min(1, $feitos / $total) : 0;
    $prova = $perfil->cargo->edital->data_prova;
    $faltam = $prova ? (int) today()->diffInDays($prova, false) : null;
    $edital = $perfil->cargo->edital;
    $semana = $jornada['semana_total'];
    $trilha = $jornada['trilha'];
    $jorn = $jornada['jornada'];
    $restantes = $atividades->filter(fn ($x) => $x['a']->status === 'pendente')->count();
    // Link para o cronômetro do /estudos (na nuvem): matéria, tipo, atividade e a prova (vira D-Day).
    $cronometro = fn ($a) => $estudosUrl . '/estudos#concursos=' . rawurlencode(json_encode([
        'd' => $a->topico ? mb_substr($a->topico->disciplina->nome, 0, 60) : 'Revisão de erros',
        't' => $a->tipo, 'r' => "concursos:{$a->id}",
        'p' => $prova?->toDateString(), 'pt' => mb_substr("Prova {$edital->orgao}", 0, 80),
    ], JSON_UNESCAPED_UNICODE));
    $anel = fn (float $p, int $r = 42) => [2 * M_PI * $r, 2 * M_PI * $r * (1 - max(0, min(1, $p)))];
    [$circ, $resto] = $anel($pctHoje);
@endphp

@section('css')
    h2.sec { font-size: 17px; margin: 0 0 4px; }
    .sub { font-size: 14px; color: var(--muted); margin: 0 0 14px; }

    /* Topo: anel do dia + números */
    .hero { display: grid; grid-template-columns: auto 1fr; gap: 20px; align-items: center; }
    .anel { position: relative; width: 108px; height: 108px; }
    .anel svg { transform: rotate(-90deg); }
    .anel .fundo { stroke: var(--line); }
    .anel .frente { stroke: var(--ok); stroke-linecap: round; transition: stroke-dashoffset .6s ease; }
    .anel .centro { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
    .anel .centro b { font-size: 26px; line-height: 1; font-variant-numeric: tabular-nums; }
    .anel .centro span { font-size: 12px; color: var(--muted); }
    .hero h1 { font-size: 22px; margin: 0 0 2px; }
    .hero .frase { margin: 0 0 12px; color: var(--muted); font-size: 15px; }
    .numeros { display: flex; flex-wrap: wrap; gap: 8px; }
    .num { background: var(--bg); border-radius: 10px; padding: 8px 12px; min-width: 104px; }
    .num b { display: block; font-size: 18px; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .num span { font-size: 12px; color: var(--muted); }

    /* Calendário da semana */
    .semana { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
    .dia { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 4px 8px; border-radius: 12px;
           border: 1px solid var(--line); text-decoration: none; color: var(--ink); background: var(--card); min-width: 0; }
    a.dia:hover { border-color: var(--accent); }
    .dia .nome { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
    .dia .numdia { font-size: 18px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .dia .barra { width: 70%; height: 6px; background: var(--line); border-radius: 99px; overflow: hidden; }
    .dia .barra > span { display: block; height: 100%; background: var(--accent); }
    .dia .min.curto { display: none; }
    .dia .min { font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .dia .pontos { display: flex; gap: 3px; flex-wrap: wrap; justify-content: center; min-height: 8px; }
    .dia .pontos i { width: 8px; height: 8px; border-radius: 50%; background: var(--line); }
    .dia .pontos i.feita { background: var(--ok); }
    .dia .pontos i.parcial { background: var(--accent); }
    .dia .pontos i.adiada { background: var(--warn); }
    .dia.hoje { border: 2px solid var(--accent); }
    .dia.completo { background: var(--ok-soft); border-color: transparent; }
    .dia.completo .barra > span { background: var(--ok); }
    .dia.perdido { background: var(--no-soft); border-color: transparent; }
    .dia.folga { border-style: dashed; color: var(--muted); }
    .dia.futuro { opacity: .75; }
    .dia.sel { outline: 2px solid var(--ink); outline-offset: 1px; }
    .legenda { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 12px; color: var(--muted); margin-top: 10px; }
    .legenda i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }
    .totalsemana { display: flex; align-items: center; gap: 12px; margin-top: 12px; font-size: 14px; }
    .totalsemana .bar { flex: 1; }

    /* Atividades */
    .ativ { display: grid; grid-template-columns: auto 1fr auto; gap: 4px 14px; align-items: start; }
    .ativ .n { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 14px; background: var(--accent-soft); color: var(--accent); }
    .ativ.feita .n { background: var(--ok); color: var(--card); }
    .ativ.feita h3, .ativ.adiada h3 { color: var(--muted); }
    .ativ h3 { font-size: 16px; margin: 4px 0 2px; }
    .ativ .tipo { display: inline-block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 2px 8px; border-radius: 99px; }
    .tipo.teoria { background: var(--warn-soft); color: var(--warn); }
    .tipo.questoes { background: var(--accent-soft); color: var(--accent); }
    .tipo.revisao { background: var(--no-soft); color: var(--no); }
    .ativ .motivo { grid-column: 2 / -1; font-size: 14px; color: var(--muted); margin: 0; }
    .ativ .prog { grid-column: 2 / -1; display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--muted); }
    .ativ .prog .bar { flex: 1; max-width: 240px; }
    .ativ .botoes { grid-column: 2 / -1; display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .ativ .botoes form { margin: 0; }
    .ativ .min { font-size: 14px; color: var(--muted); white-space: nowrap; padding-top: 6px; text-align: right; }
    .tag { display: inline-block; font-size: 12px; padding: 2px 8px; border-radius: 99px; background: var(--ok-soft); color: var(--ok); font-weight: 600; }
    .tag.warn { background: var(--warn-soft); color: var(--warn); }

    /* Jornada até a prova */
    .jornada { position: relative; display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0, 1fr); gap: 4px; align-items: end; margin-top: 4px; }
    .jornada .sem { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 11px; color: var(--muted); min-width: 0; }
    .jornada .bloco { width: 100%; height: 28px; border-radius: 6px; background: var(--line); position: relative; overflow: hidden; }
    .jornada .bloco > span { position: absolute; left: 0; right: 0; bottom: 0; background: var(--ok); }
    .jornada .sem.atual .bloco { outline: 2px solid var(--accent); outline-offset: 1px; }
    .jornada .sem.prova .bloco { background: var(--warn-soft); border: 2px solid var(--warn); }
    .jornada .sem.prova b { color: var(--warn); }
    .jornada .sem.atual b { color: var(--accent); }
    .jornada .rot { white-space: nowrap; }

    /* Trilha do edital */
    .trilhatopo { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
    .trilhatopo .big { font-size: 30px; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
    .area { margin-bottom: 14px; }
    .area .cab { display: flex; justify-content: space-between; gap: 8px; font-size: 14px; margin-bottom: 6px; }
    .area .cab b { font-weight: 600; }
    .area .cab span { color: var(--muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .trechos { display: flex; gap: 2px; height: 14px; }
    .trechos span { flex-basis: 0; min-width: 3px; border-radius: 3px; background: var(--line); }
    .trechos span.andamento { background: var(--accent); }
    .trechos span.firme { background: var(--ok); }
    .trechos span:first-child { border-radius: 99px 3px 3px 99px; }
    .trechos span:last-child { border-radius: 3px 99px 99px 3px; }

    @media (max-width: 560px) {
        .hero { grid-template-columns: 1fr; justify-items: center; text-align: center; }
        .numeros { justify-content: center; }
        .semana { gap: 4px; }
        .dia { padding: 8px 2px 6px; }
        .dia .min { font-size: 11px; }
        .dia .min.longo { display: none; }
        .dia .min.curto { display: block; }
        .dia .nome { font-size: 11px; letter-spacing: 0; }
        .dia .pontos i { width: 6px; height: 6px; }
        .jornada .rot { display: none; }
        .jornada .sem.atual .rot, .jornada .sem.prova .rot { display: block; }
    }
@endsection

@section('content')
    {{-- Topo: o dia --}}
    <div class="card hero">
        <div class="anel" role="img" aria-label="Hoje: {{ $pct($pctHoje) }} da meta feita">
            <svg width="108" height="108" viewBox="0 0 108 108">
                <circle class="fundo" cx="54" cy="54" r="42" fill="none" stroke-width="10"/>
                <circle class="frente" cx="54" cy="54" r="42" fill="none" stroke-width="10" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $resto }}"/>
            </svg>
            <div class="centro"><b>{{ round(100 * $pctHoje) }}%</b><span>de hoje</span></div>
        </div>
        <div>
            <h1>Meta de {{ $diasNome[$meta->data->format('N')] }}, {{ $meta->data->format('d/m') }}</h1>
            <p class="frase">
                @if ($atividades->isEmpty()) Dia livre na sua agenda.
                @elseif ($restantes === 0) Meta de hoje concluída. Bom trabalho!
                @elseif ($feitos === 0) {{ $restantes }} {{ $restantes === 1 ? 'atividade' : 'atividades' }} para hoje, cerca de {{ $hm($total) }}.
                @else Faltam {{ $restantes }} {{ $restantes === 1 ? 'atividade' : 'atividades' }}, cerca de {{ $hm(max(0, $total - $feitos)) }}.
                @endif
            </p>
            <div class="numeros">
                <div class="num"><b>{{ $hm($feitos) }}</b><span>de {{ $hm($total) }} hoje</span></div>
                <div class="num"><b>{{ $pct($semana['pct']) }}</b><span>da semana ({{ $hm($semana['feito']) }})</span></div>
                <div class="num"><b>{{ $jornada['sequencia'] }} {{ $jornada['sequencia'] === 1 ? 'dia' : 'dias' }}</b><span>seguidos estudando</span></div>
                @if ($faltam !== null)<div class="num"><b>{{ $faltam }} dias</b><span>até a prova ({{ $prova->format('d/m') }})</span></div>@endif
            </div>
        </div>
    </div>

    {{-- Calendário da semana --}}
    <div class="card">
        <h2 class="sec">Sua semana</h2>
        <p class="sub">Toque num dia que já passou para ver o que foi feito.</p>
        <div class="semana">
            @foreach ($jornada['semana'] as $d)
                @php
                    $clicavel = !$d['hoje'] && $d['data']->isPast() && $d['estado'] !== 'folga';
                    $tag = $clicavel || $d['hoje'] ? 'a' : 'div';
                    $href = $d['hoje'] ? route('concursos.meta') : route('concursos.meta', ['dia' => $d['data']->toDateString()]);
                    $rotulo = match ($d['estado']) {
                        'folga' => 'folga', 'futuro' => 'previsto ' . $hm($d['planejado']), 'completo' => 'meta completa',
                        'perdido' => $d['tem_meta'] ? 'nada feito' : 'não abriu a meta', default => $pct($d['pct']) . ' feito',
                    };
                    $sel = ($diaSel && $diaSel['data']->eq($d['data'])) || (!$diaSel && $d['hoje']);
                @endphp
                <{{ $tag }} class="dia {{ $d['estado'] }} {{ $d['hoje'] ? 'hoje' : '' }} {{ $sel ? 'sel' : '' }}" @if ($tag === 'a') href="{{ $href }}" @endif
                    title="{{ ucfirst($diasNome[$d['data']->format('N')]) }}, {{ $d['data']->format('d/m') }}: {{ $rotulo }}{{ $d['planejado'] ? ' (' . $hm($d['feito']) . ' de ' . $hm($d['planejado']) . ')' : '' }}"
                    @if ($sel) aria-current="date" @endif>
                    <span class="nome">{{ $d['hoje'] ? 'Hoje' : \App\Models\Concursos\Perfil::DIAS[$d['data']->format('N')] }}</span>
                    <span class="numdia">{{ $d['data']->format('d') }}</span>
                    @if ($d['estado'] === 'folga')
                        <span class="min">folga</span>
                    @else
                        <span class="barra"><span style="width: {{ round(100 * ($d['pct'] ?? 0)) }}%"></span></span>
                        <span class="min longo">{{ $d['estado'] === 'futuro' ? $hm($d['planejado']) : $hm($d['feito']) . '/' . $hm($d['planejado']) }}</span>
                        <span class="min curto">{{ $d['estado'] === 'futuro' ? $hm($d['planejado']) : $pct($d['pct']) }}</span>
                    @endif
                    <span class="pontos">@foreach ($d['atividades'] as $x)<i class="{{ $x['estado'] }}"></i>@endforeach</span>
                </{{ $tag }}>
            @endforeach
        </div>
        <div class="legenda">
            <span><i style="background:var(--ok)"></i>atividade feita</span>
            <span><i style="background:var(--accent)"></i>começada</span>
            <span><i style="background:var(--warn)"></i>adiada</span>
            <span><i style="background:var(--line)"></i>a fazer</span>
        </div>
        <div class="totalsemana">
            <span>Semana: <b>{{ $hm($semana['feito']) }}</b> de {{ $hm($semana['planejado']) }}</span>
            <span class="bar"><span style="width: {{ round(100 * ($semana['pct'] ?? 0)) }}%"></span></span>
            <b>{{ $pct($semana['pct']) }}</b>
        </div>
    </div>

    {{-- Atividades: as de hoje, ou as de um dia passado (só leitura) --}}
    @if ($diaSel)
        <div class="top" style="margin-bottom:12px">
            <h2 class="sec" style="flex:1">{{ ucfirst($diasNome[$diaSel['data']->format('N')]) }}, {{ $diaSel['data']->format('d/m') }}: {{ $hm($diaSel['feito']) }} de {{ $hm($diaSel['planejado']) }}</h2>
            <a class="btn" href="{{ route('concursos.meta') }}">Voltar para hoje</a>
        </div>
        @forelse ($diaSel['atividades'] as $x)
            @php($a = $x['a'])
            <div class="card ativ {{ $x['estado'] }}">
                <span class="n">{{ $x['estado'] === 'feita' ? '✓' : $loop->iteration }}</span>
                <div>
                    <span class="tipo {{ $a->tipo }}">{{ $tipos[$a->tipo] }}</span>
                    @if ($x['estado'] === 'adiada') <span class="tag warn">adiada</span>@endif
                    <h3>{{ $a->topico ? $a->topico->ordem . ' ' . \Illuminate\Support\Str::limit($a->topico->texto, 140) : 'Questões que você errou' }}</h3>
                </div>
                <span class="min">{{ $a->minutos }} min</span>
                @if ($x['total'])
                    <div class="prog"><span class="bar"><span style="width: {{ round(100 * $x['respondidas'] / $x['total']) }}%"></span></span>{{ $x['respondidas'] }} de {{ $x['total'] }} questões</div>
                @endif
            </div>
        @empty
            <div class="card muted">A meta deste dia não chegou a ser aberta.</div>
        @endforelse
    @else
        <h2 class="sec" style="margin:4px 0 12px">Atividades de hoje</h2>

        @if ($atividades->isEmpty())
            <div class="card">
                @if ($meta->minutos_orcamento === 0)
                    Hoje é seu dia de folga no perfil. Descanse, ou <a href="{{ route('concursos.questoes.index') }}">resolva questões avulsas</a>.
                @else
                    Não achei nada para hoje: confira se o edital tem tópicos ligados a assuntos com questões.
                @endif
            </div>
        @endif

        @foreach ($atividades as $x)
            @php($a = $x['a'])
            <div class="card ativ {{ $a->status }}">
                <span class="n">{{ $a->status === 'feita' ? '✓' : $loop->iteration }}</span>
                <div>
                    <span class="tipo {{ $a->tipo }}">{{ $tipos[$a->tipo] }}</span>
                    @if ($a->status === 'adiada') <span class="tag warn">adiada para amanhã</span>@endif
                    @if ($a->adiamentos >= 3 && $a->status === 'pendente') <span class="tag warn">adiada {{ $a->adiamentos }}×: que tal fazer hoje?</span>@endif
                    <h3>
                        @if ($a->topico)
                            {{ $a->topico->ordem }} {{ \Illuminate\Support\Str::limit($a->topico->texto, 140) }}
                        @elseif ($a->tipo === 'revisao')
                            Questões que você errou
                        @endif
                    </h3>
                </div>
                <span class="min">{{ $a->minutos }} min
                    @if ($a->minutos_reais)<br><span title="cronometrado no /estudos">⏱ {{ (int) round($a->minutos_reais) }} min reais</span>@endif</span>
                <p class="motivo">{{ $a->motivo }}</p>
                @if ($x['feitas'] !== null)
                    <div class="prog"><span class="bar"><span style="width: {{ round(100 * min(1, $x['feitas'] / max(1, count($a->questao_ids)))) }}%"></span></span>{{ $x['feitas'] }} de {{ count($a->questao_ids) }} questões</div>
                @endif
                @if ($a->status === 'pendente')
                    <div class="botoes">
                        @if ($a->questao_ids)
                            <a class="btn main" href="{{ route('concursos.questoes.resolver', ['atividade' => $a->id]) }}">{{ $x['feitas'] ? 'Continuar' : 'Começar' }}</a>
                        @else
                            <form method="POST" action="{{ route('concursos.meta.feita', $a) }}">@csrf<button class="btn main">Estudei a teoria</button></form>
                        @endif
                        <a class="btn" href="{{ $cronometro($a) }}" target="estudos" rel="noopener" title="Abre o cronômetro do /estudos já nesta matéria e tipo">⏱ Cronometrar</a>
                        <form method="POST" action="{{ route('concursos.meta.adiar', $a) }}">@csrf<button class="btn">Adiar</button></form>
                    </div>
                @endif
            </div>
        @endforeach
    @endif

    {{-- Jornada até a prova --}}
    @if ($jorn)
        <div class="card">
            <h2 class="sec">Caminho até a prova</h2>
            <p class="sub">
                @if ($jorn['semana_atual'])Semana {{ $jorn['semana_atual'] }} de {{ count($jorn['semanas']) }} · @endif
                {{ $pct($jorn['pct_tempo']) }} do tempo já passou · você cumpriu {{ $pct($jorn['estudado']['pct']) }} do planejado até aqui ({{ $hm($jorn['estudado']['feito']) }})
            </p>
            <div class="jornada" role="img" aria-label="Semanas de {{ $jorn['inicio']->format('d/m') }} até a prova em {{ $jorn['prova']->format('d/m') }}">
                @foreach ($jorn['semanas'] as $i => $w)
                    <div class="sem {{ $w['atual'] ? 'atual' : '' }} {{ $w['tem_prova'] ? 'prova' : '' }}"
                         title="Semana de {{ $w['inicio']->format('d/m') }}{{ $w['tem_prova'] ? ' (semana da prova)' : '' }}{{ ($w['passada'] || $w['atual']) && $w['planejado'] ? ': ' . $pct($w['pct']) . ' da meta' : '' }}">
                        <span class="bloco"><span style="height: {{ round(100 * ($w['pct'] ?? 0)) }}%"></span></span>
                        <span class="rot">{!! $w['tem_prova'] ? '<b>Prova</b>' : ($w['atual'] ? '<b>Agora</b>' : e($w['inicio']->format('d/m'))) !!}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Trilha do edital --}}
    <div class="card">
        <h2 class="sec">Trilha do edital</h2>
        <div class="trilhatopo">
            <span class="big">{{ $pct($trilha['trabalhado']) }}</span>
            <span class="muted">do edital já trabalhado, pesando quanto cada tópico vale · <b style="color:var(--ok)">{{ $pct($trilha['firme']) }} firme</b> ({{ $trilha['n_firme'] }} de {{ $trilha['topicos'] }} tópicos)</span>
        </div>
        @foreach ($trilha['areas'] as $ar)
            <div class="area">
                <div class="cab"><b>{{ $ar['nome'] }}</b><span>{{ $pct($ar['firme'] + $ar['andamento']) }} · {{ $ar['n_firme'] + $ar['n_andamento'] }}/{{ $ar['topicos'] }} tópicos</span></div>
                <div class="trechos" role="img" aria-label="{{ $ar['nome'] }}: {{ $ar['n_firme'] }} tópicos firmes, {{ $ar['n_andamento'] }} em andamento, de {{ $ar['topicos'] }}">
                    @foreach ($ar['trechos'] as $t)
                        <span class="{{ $t['estado'] }}" style="flex-grow: {{ max(0.01, $t['valor']) }}"
                              title="{{ $t['topico']->ordem }} {{ \Illuminate\Support\Str::limit($t['topico']->texto, 80) }} · {{ ['firme' => 'firme', 'andamento' => 'em andamento', 'novo' => 'ainda não começado'][$t['estado']] }}{{ $t['tentativas'] ? ' · ' . $t['tentativas'] . ' questões, domínio ' . $pct($t['dominio']) : '' }}"></span>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="legenda">
            <span><i style="background:var(--ok)"></i>firme (5+ questões, domínio 75%+)</span>
            <span><i style="background:var(--accent)"></i>em andamento</span>
            <span><i style="background:var(--line)"></i>ainda não começado</span>
            <span>cada trecho tem o tamanho do que o tópico vale na prova</span>
        </div>
    </div>

    <p class="muted small">
        @if ($ritmo['calibrado'])
            Ritmo usado na meta: {{ number_format($ritmo['valor'], 1, ',', '') }} min por questão, medido pelo seu cronômetro em {{ $ritmo['amostras'] }} atividades.
        @else
            Ritmo usado na meta: {{ number_format($ritmo['valor'], 1, ',', '') }} min por questão (padrão). Use ⏱ Cronometrar: depois de 3 atividades a meta se ajusta ao seu ritmo.
        @endif
        @unless ($tempoLigado) <br>Para o tempo voltar do /estudos, defina CONCURSOS_ESTUDOS_TOKEN no .env deste PC. @endunless
    </p>
@endsection
