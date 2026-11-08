@php
    /** @var array $d dados.json   @var string $slide   @var int $w   @var int $h */
    $app = $d['app'];
    $g = $d['guruja'] ?? null;
    $fmt = function (int $min): string {
        if ($min >= 6000) {
            return number_format(intdiv($min, 60), 0, ',', '.') . 'h';
        }
        return intdiv($min, 60) . 'h' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT);
    };
    $delta = $app['total_min'] - $app['anterior_total_min'];
    $deltaText = ($delta >= 0 ? '+' : '−') . $fmt(abs($delta));
    $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $dm = fn (string $day) => substr($day, 8, 2) . '/' . substr($day, 5, 2);
    $maxDay = max(1, ...array_values($app['por_dia']));
    $maxSubject = max(1, ...array_column($app['por_materia'], 'min'));
    $colorByName = collect($app['por_materia'])->pluck('cor', 'nome');
    $alvo = $app['alvo_horas'];
    $histH = intdiv($app['total_historico_min'], 60);
    $dday = $app['ddays'][0] ?? null;
    $story = $h > 1500;
    $title = $d['titulo'] ?? '';
    $subtitle = $d['subtitulo'] ?? '';
    $selo = $d['selo'] ?? null;
    $gPct = $g && $g['questoes'] > 0 ? round($g['acertos'] / $g['questoes'] * 100) : null;
    $accent = $app['por_materia'][0]['cor'] ?? '#7C9CFF';
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=block" rel="stylesheet">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { width: {{ $w }}px; height: {{ $h }}px; overflow: hidden; }
    body {
        font-family: Inter, 'Segoe UI', system-ui, sans-serif; color: #F3F5F9; -webkit-font-smoothing: antialiased;
        background:
            radial-gradient(900px 700px at 100% 0%, {{ $accent }}33, transparent 60%),
            radial-gradient(800px 800px at 0% 100%, #5B6CFF22, transparent 60%),
            #0B0E14;
        display: flex; flex-direction: column; padding: {{ $story ? '150px 80px 120px' : '84px 80px 64px' }};
    }
    .top { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .kicker { font-size: 30px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #9AA4B5; }
    .badge { font-size: 28px; font-weight: 700; padding: 12px 24px; border-radius: 99px; background: #1F2937; color: #E5E7EB; white-space: nowrap; }
    .badge.ok { background: #10301F; color: #5EE6A0; }
    .badge.run { background: #33270F; color: #F5C451; }
    h1 { font-size: 64px; font-weight: 800; letter-spacing: -.02em; margin-top: 18px; line-height: 1.05; }
    .sub { font-size: 32px; color: #9AA4B5; margin-top: 10px; font-weight: 500; }
    .hero { margin-top: {{ $story ? '120px' : '64px' }}; }
    .hero .big { font-size: {{ $story ? '260px' : '220px' }}; font-weight: 900; letter-spacing: -.05em; line-height: .9; font-variant-numeric: tabular-nums; }
    .hero .label { font-size: 34px; color: #9AA4B5; margin-top: 18px; font-weight: 500; }
    .delta { display: inline-block; margin-top: 22px; font-size: 32px; font-weight: 700; padding: 10px 22px; border-radius: 14px; }
    .delta.up { background: #10301F; color: #5EE6A0; }
    .delta.down { background: #1F2937; color: #C7CDD8; }
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: {{ $story ? '90px' : '60px' }}; }
    .stat { background: #ffffff0d; border: 1px solid #ffffff14; border-radius: 26px; padding: 28px 28px; }
    .stat small { display: block; font-size: 25px; color: #9AA4B5; font-weight: 500; }
    .stat b { display: block; font-size: 52px; font-weight: 800; margin-top: 8px; font-variant-numeric: tabular-nums; letter-spacing: -.02em; }
    .bars { display: flex; align-items: flex-end; gap: 14px; height: {{ $story ? '300px' : '230px' }}; margin-top: 56px; }
    .bars .col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 12px; }
    .bars .bar { width: 100%; border-radius: 14px 14px 6px 6px; background: linear-gradient(180deg, {{ $accent }}, {{ $accent }}99); min-height: 6px; }
    .bars .bar.zero { background: #ffffff1a; }
    .bars small { font-size: 22px; color: #9AA4B5; font-weight: 600; }
    .bars em { font-style: normal; font-size: 21px; color: #C7CDD8; font-weight: 600; }
    .progress { margin-top: 48px; }
    .progress .track { height: 22px; border-radius: 99px; background: #ffffff14; overflow: hidden; }
    .progress .fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #5B6CFF, {{ $accent }}); }
    .progress p { display: flex; justify-content: space-between; font-size: 28px; color: #C7CDD8; margin-top: 14px; font-weight: 600; }
    .section { font-size: 30px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #9AA4B5; margin: 56px 0 26px; }
    .rows { display: grid; gap: 22px; }
    .row { display: grid; grid-template-columns: 1fr auto; gap: 10px 20px; align-items: center; }
    .row .name { font-size: 34px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .row .val { font-size: 34px; font-weight: 800; font-variant-numeric: tabular-nums; }
    .row .val small { font-size: 24px; color: #9AA4B5; font-weight: 600; margin-left: 8px; }
    .row .track { grid-column: 1 / -1; height: 16px; border-radius: 99px; background: #ffffff10; overflow: hidden; }
    .row .fill { height: 100%; border-radius: 99px; }
    .stack { display: flex; height: 44px; border-radius: 14px; overflow: hidden; gap: 4px; }
    .legend { display: flex; flex-wrap: wrap; gap: 14px 30px; margin-top: 22px; font-size: 27px; color: #C7CDD8; font-weight: 500; }
    .legend i { display: inline-block; width: 22px; height: 22px; border-radius: 6px; margin-right: 10px; vertical-align: -3px; }
    .qhero { display: flex; gap: 24px; margin-top: 40px; }
    .qhero .stat { flex: 1; }
    .qhero .stat b { font-size: 80px; }
    .compact .section { margin: 40px 0 20px; }
    .compact .rows { gap: 16px; }
    .compact .row .name, .compact .row .val { font-size: 31px; }
    .compact .cards { margin-top: 30px; gap: 16px; }
    .compact .card { padding: 22px 28px; }
    .compact .card p { font-size: 30px; }
    .cards { display: grid; gap: 20px; margin-top: 44px; }
    .card { border-radius: 26px; padding: 28px 32px; background: #ffffff0d; border: 1px solid #ffffff14; }
    .card small { font-size: 25px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9AA4B5; }
    .card p { font-size: 34px; font-weight: 600; margin-top: 10px; line-height: 1.3; }
    .card.hi { border-color: #5EE6A055; background: #5EE6A00f; }
    .card.hi small { color: #5EE6A0; }
    .card.focus small { color: #F5C451; }
    .card.focus { border-color: #F5C45155; background: #F5C4510f; }
    .spacer { flex: 1; }
    .foot { display: flex; justify-content: space-between; align-items: center; font-size: 30px; color: #9AA4B5; font-weight: 600; padding-top: 28px; }
    .foot b { color: #F3F5F9; font-weight: 800; }
    .dots { display: flex; gap: 10px; }
    .dots i { width: 14px; height: 14px; border-radius: 99px; background: #ffffff33; }
    .dots i.on { background: #F3F5F9; width: 36px; }
    .dday { font-size: 30px; font-weight: 700; color: #FF8A8A; }
</style>
</head>
<body>
@if ($slide === 'capa' || $slide === 'story-semanal' || $slide === 'story-diario')
    <div class="top">
        <span class="kicker">{{ $d['tipo'] === 'diario' ? 'Diário de estudos' : 'Resumo da meta' }}</span>
        @if ($selo)<span class="badge {{ $selo === 'concluída' ? 'ok' : 'run' }}">{{ $selo === 'concluída' ? '✓ concluída' : '● em andamento' }}</span>@endif
    </div>
    <h1>{{ $title }}</h1>
    <p class="sub">{{ $subtitle }}</p>

    <div class="hero">
        <div class="big">{{ $fmt($app['total_min']) }}</div>
        <p class="label">de estudo {{ $d['tipo'] === 'diario' ? 'hoje' : 'nesta meta' }}</p>
        @if ($app['anterior_total_min'] > 0 && $delta > 0)
            <span class="delta {{ $delta >= 0 ? 'up' : 'down' }}">{{ $deltaText }} {{ $d['tipo'] === 'diario' ? 'vs ontem' : 'vs meta anterior' }}</span>
        @endif
    </div>

    <div class="stats">
        @if ($d['tipo'] === 'diario')
            <div class="stat"><small>Sequência</small><b>{{ $app['sequencia_dias'] }} dias</b></div>
            <div class="stat"><small>Matérias</small><b>{{ count($app['por_materia']) }}</b></div>
            <div class="stat"><small>Total geral</small><b>{{ number_format($histH, 0, ',', '.') }}h</b></div>
        @else
            <div class="stat"><small>Dias estudados</small><b>{{ $app['dias_estudados'] }}/{{ $app['periodo']['dias'] }}</b></div>
            <div class="stat"><small>Média por dia</small><b>{{ $fmt($app['media_dia_min']) }}</b></div>
            <div class="stat"><small>Sequência</small><b>{{ $app['sequencia_dias'] }} dias</b></div>
        @endif
    </div>

    @if ($d['tipo'] === 'diario')
        <p class="section">Por matéria</p>
        <div class="rows">
            @foreach (array_slice($app['por_materia'], 0, 5) as $s)
                <div class="row"><span class="name">{{ $s['nome'] }}</span><span class="val">{{ $fmt($s['min']) }}</span>
                    <div class="track"><div class="fill" style="width:{{ $s['min'] / $maxSubject * 100 }}%;background:{{ $s['cor'] }}"></div></div></div>
            @endforeach
        </div>
        @if ($g)
            <div class="cards">
                <div class="card"><small>Meta atual · acumulado</small><p>{{ $g['questoes'] }} questões · {{ $gPct }}% de acerto</p></div>
            </div>
        @endif
    @else
        <div class="bars">
            @foreach ($app['por_dia'] as $day => $min)
                <div class="col">
                    <em>{{ $min ? $fmt($min) : '' }}</em>
                    <div class="bar {{ $min ? '' : 'zero' }}" style="height:{{ max(2, $min / $maxDay * 100) }}%"></div>
                    <small>{{ $weekdays[\Carbon\Carbon::parse($day)->dayOfWeek] }}</small>
                </div>
            @endforeach
        </div>
        @if ($slide === 'story-semanal' && $g)
            <div class="cards">
                <div class="card"><small>Questões na meta</small><p>{{ $g['questoes'] }} resolvidas · {{ $gPct }}% de acerto</p></div>
                @if (!empty($d['destaques']['ponto_alto']))<div class="card hi"><small>Ponto alto</small><p>{{ $d['destaques']['ponto_alto'] }}</p></div>@endif
            </div>
        @endif
    @endif

    @if ($alvo)
        <div class="progress">
            <div class="track"><div class="fill" style="width:{{ min(100, $histH / $alvo * 100) }}%"></div></div>
            <p><span>Rumo às {{ number_format($alvo, 0, ',', '.') }}h</span><span>faltam {{ number_format(max(0, $alvo - $histH), 0, ',', '.') }}h</span></p>
        </div>
    @endif

    <div class="spacer"></div>
    <div class="foot">
        <span><b>{{ $d['handle'] }}</b>@if ($dday) &nbsp;·&nbsp; <span class="dday">{{ $dday['titulo'] }} D-{{ $dday['dias'] }}</span>@endif</span>
        @if ($slide === 'capa')<span class="dots"><i class="on"></i><i></i><i></i></span>@elseif ($slide === 'story-semanal')<span>arrasta pro post ↗</span>@endif
    </div>

@elseif ($slide === 'materias')
    <div class="top"><span class="kicker">{{ $title }} · {{ $subtitle }}</span></div>
    <h1>Onde foi o tempo</h1>
    <p class="section">Por matéria</p>
    <div class="rows">
        @foreach (array_slice($app['por_materia'], 0, 7) as $s)
            <div class="row"><span class="name">{{ $s['nome'] }}</span>
                <span class="val">{{ $fmt($s['min']) }}<small>{{ round($s['min'] / max(1, $app['total_min']) * 100) }}%</small></span>
                <div class="track"><div class="fill" style="width:{{ $s['min'] / $maxSubject * 100 }}%;background:{{ $s['cor'] }}"></div></div></div>
        @endforeach
    </div>
    @php
        $typeColors = ['#5B6CFF', '#F5C451', '#5EE6A0', '#FF8A8A', '#C084FC', '#38BDF8', '#F472B6', '#94A3B8'];
        $types = array_slice($app['por_tipo'], 0, 8);
    @endphp
    <p class="section">Por tipo de estudo</p>
    <div class="stack">
        @foreach ($types as $i => $t)<div style="flex:{{ $t['min'] }};background:{{ $typeColors[$i] }}"></div>@endforeach
    </div>
    <div class="legend">
        @foreach ($types as $i => $t)<span><i style="background:{{ $typeColors[$i] }}"></i>{{ $t['nome'] }} {{ $fmt($t['min']) }}</span>@endforeach
    </div>
    <div class="spacer"></div>
    <div class="foot"><b>{{ $d['handle'] }}</b><span class="dots"><i></i><i class="on"></i><i></i></span></div>

@elseif ($slide === 'questoes')
<div class="compact" style="display:flex;flex-direction:column;flex:1">
    <div class="top"><span class="kicker">{{ $title }} · {{ $subtitle }}</span></div>
    <h1>Questões</h1>
    <div class="qhero">
        <div class="stat"><small>Resolvidas</small><b>{{ $g['questoes'] }}</b></div>
        <div class="stat"><small>Acerto geral</small><b>{{ $gPct }}%</b></div>
    </div>
    <p class="section">Acerto por disciplina</p>
    <div class="rows">
        @foreach (collect($g['disciplinas'])->sortByDesc('percentual')->take(7) as $disc)
            <div class="row"><span class="name">{{ $disc['nome'] }}</span>
                <span class="val">{{ round($disc['percentual']) }}%<small>{{ $disc['acertos'] }}/{{ $disc['questoes'] }}</small></span>
                <div class="track"><div class="fill" style="width:{{ $disc['percentual'] }}%;background:{{ $colorByName[$disc['nome']] ?? '#5B6CFF' }}"></div></div></div>
        @endforeach
    </div>
    <div class="cards">
        @if (!empty($d['destaques']['ponto_alto']))<div class="card hi"><small>Ponto alto</small><p>{{ $d['destaques']['ponto_alto'] }}</p></div>@endif
        @if (!empty($d['destaques']['foco']))<div class="card focus"><small>Foco da próxima meta</small><p>{{ $d['destaques']['foco'] }}</p></div>@endif
    </div>
    <div class="spacer"></div>
    <div class="foot"><b>{{ $d['handle'] }}</b><span class="dots"><i></i><i></i><i class="on"></i></span></div>
</div>
@endif
</body>
</html>
