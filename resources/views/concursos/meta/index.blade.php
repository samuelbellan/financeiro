@extends('concursos.layout')

@section('title', 'Meta do dia')

@php
    $total = $atividades->sum(fn ($x) => $x['a']->minutos);
    $feitos = $atividades->filter(fn ($x) => $x['a']->status === 'feita')->sum(fn ($x) => $x['a']->minutos);
    $prova = $perfil->cargo->edital->data_prova;
    $faltam = $prova ? (int) today()->diffInDays($prova, false) : null;
    $edital = $perfil->cargo->edital;
    // Link para o cronômetro do /estudos (na nuvem): matéria, tipo, atividade e a prova (vira D-Day).
    $cronometro = fn ($a) => $estudosUrl . '/estudos#concursos=' . rawurlencode(json_encode([
        'd' => $a->topico ? mb_substr($a->topico->disciplina->nome, 0, 60) : 'Revisão de erros',
        't' => $a->tipo, 'r' => "concursos:{$a->id}",
        'p' => $prova?->toDateString(), 'pt' => mb_substr("Prova {$edital->orgao}", 0, 80),
    ], JSON_UNESCAPED_UNICODE));
@endphp

@section('css')
    .resumo { display: flex; flex-wrap: wrap; gap: 12px 24px; align-items: center; }
    .resumo .big { font-size: 28px; font-weight: 700; line-height: 1; }
    .resumo .bar { flex: 1; min-width: 160px; }
    .ativ { display: grid; grid-template-columns: auto 1fr auto; gap: 4px 14px; align-items: start; }
    .ativ .n { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 14px; background: var(--accent-soft); color: var(--accent); }
    .ativ.feita { opacity: .6; }
    .ativ.feita .n { background: var(--ok-soft); color: var(--ok); }
    .ativ.adiada { opacity: .5; }
    .ativ h2 { font-size: 16px; margin: 4px 0 2px; }
    .ativ .tipo { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
    .ativ .motivo { grid-column: 2 / -1; font-size: 14px; color: var(--muted); margin: 0; }
    .ativ .botoes { grid-column: 2 / -1; display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .ativ .botoes form { margin: 0; }
    .ativ .min { font-size: 14px; color: var(--muted); white-space: nowrap; padding-top: 6px; }
    .tag { display: inline-block; font-size: 12px; padding: 2px 8px; border-radius: 99px; background: var(--ok-soft); color: var(--ok); font-weight: 600; }
    .tag.warn { background: var(--warn-soft); color: var(--warn); }
@endsection

@section('content')
    <div class="top">
        <h1>Meta de {{ ['', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'][$meta->data->format('N')] }}, {{ $meta->data->format('d/m') }}</h1>
    </div>

    <div class="card resumo">
        <div><div class="big">{{ intdiv($feitos, 60) }}h{{ str_pad($feitos % 60, 2, '0', STR_PAD_LEFT) }}</div><span class="muted small">de {{ intdiv($total, 60) }}h{{ str_pad($total % 60, 2, '0', STR_PAD_LEFT) }} planejadas</span></div>
        <span class="bar"><span style="width: {{ $total ? round(100 * $feitos / $total) : 0 }}%"></span></span>
        @if ($faltam !== null)
            <div><div class="big">{{ $faltam }}</div><span class="muted small">dias até a prova ({{ $prova->format('d/m/Y') }})</span></div>
        @endif
    </div>

    <p class="muted small" style="margin:-4px 0 16px">
        @if ($ritmo['calibrado'])
            Ritmo usado na meta: {{ number_format($ritmo['valor'], 1, ',', '') }} min por questão, medido pelo seu cronômetro em {{ $ritmo['amostras'] }} atividades.
        @else
            Ritmo usado na meta: {{ number_format($ritmo['valor'], 1, ',', '') }} min por questão (padrão). Use ⏱ Cronometrar: depois de 3 atividades a meta se ajusta ao seu ritmo.
        @endif
        @unless ($tempoLigado) <br>Para o tempo voltar do /estudos, defina CONCURSOS_ESTUDOS_TOKEN no .env deste PC. @endunless
    </p>

    @if ($atividades->isEmpty())
        <div class="card">
            @if ($meta->minutos_orcamento === 0)
                Hoje é seu dia de folga no perfil. Descanse — ou <a href="{{ route('concursos.questoes.index') }}">resolva questões avulsas</a>.
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
                <div class="tipo">{{ $tipos[$a->tipo] }}
                    @if ($a->status === 'adiada') · <span class="tag warn">adiada para amanhã</span>@endif
                    @if ($a->adiamentos >= 3 && $a->status === 'pendente') · <span class="tag warn">adiada {{ $a->adiamentos }}×: que tal fazer hoje?</span>@endif
                </div>
                <h2>
                    @if ($a->topico)
                        {{ $a->topico->ordem }} {{ \Illuminate\Support\Str::limit($a->topico->texto, 140) }}
                    @elseif ($a->tipo === 'revisao')
                        Questões que você errou
                    @endif
                </h2>
            </div>
            <span class="min">{{ $a->minutos }} min @if ($x['feitas'] !== null)· {{ $x['feitas'] }}/{{ count($a->questao_ids) }}@endif
                @if ($a->minutos_reais)<br><span title="cronometrado no /estudos">⏱ {{ (int) round($a->minutos_reais) }} min reais</span>@endif</span>
            <p class="motivo">{{ $a->motivo }}</p>
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
@endsection
