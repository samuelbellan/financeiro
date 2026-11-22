@extends('concursos.layout')

@section('title', 'Simulados')

@php
    $num = fn ($v, $c = 1) => number_format($v, $c, ',', '.');
    $duracaoProva = $perfil->cargo->edital->duracao_min ?: 240;
    $areas = $perfil->cargo->areas;
@endphp

@section('css')
    .tipos { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
    .tipo { border: 1px solid var(--line); border-radius: 12px; padding: 16px; display: grid; gap: 8px; }
    .tipo h3 { margin: 0; font-size: 17px; }
    .tipo ul { margin: 0; padding-left: 18px; font-size: 14px; color: var(--muted); }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 8px 6px; border-top: 1px solid var(--line); }
    th { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
    td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; }
@endsection

@section('content')
    <div class="top"><h1>Simulados</h1></div>

    <div class="card">
        <p style="margin-top:0">Prova no formato do edital: questões por área como no quadro de provas, cronômetro, sem gabarito até entregar. As vagas de cada área vão para os tópicos que mais caem; questões que você nunca viu vêm primeiro.</p>
        <div class="tipos">
            @foreach ($tipos as $chave => $t)
                <form class="tipo" method="POST" action="{{ route('concursos.simulados.store') }}">@csrf
                    <input type="hidden" name="tipo" value="{{ $chave }}">
                    <h3>{{ $t['nome'] }}</h3>
                    <ul>
                        @foreach ($areas as $a)<li>{{ $a->nome }}: {{ max(1, (int) round($a->n_questoes * $t['fator'])) }}</li>@endforeach
                    </ul>
                    <span class="muted small">{{ (int) round($duracaoProva * $t['fator']) }} min · {{ $areas->sum(fn ($a) => max(1, (int) round($a->n_questoes * $t['fator']))) }} questões</span>
                    <button class="btn main">Começar agora</button>
                </form>
            @endforeach
        </div>
        <p class="muted small" style="margin-bottom:0">Área sem questões no banco (caderno ainda não importado) sai com menos questões, e o tempo diminui na mesma proporção. O resultado mostra quanto ficou faltando.</p>
    </div>

    @if ($simulados->isNotEmpty())
        <div class="card">
            <h2 style="font-size:17px;margin:0 0 8px">Feitos</h2>
            <table>
                <thead><tr><th>Data</th><th>Tipo</th><th class="n">Nota</th><th class="n">Acertos</th><th></th></tr></thead>
                <tbody>
                @foreach ($simulados as $s)
                    <tr>
                        <td>{{ $s->iniciado_em->format('d/m/Y H:i') }}</td>
                        <td>{{ $tipos[$s->tipo]['nome'] ?? $s->tipo }}</td>
                        <td class="n">{{ $s->finalizado_em ? $num($s->nota_total) . ' / ' . $num($s->pontos_total, 0) : '—' }}</td>
                        <td class="n">{{ $s->finalizado_em ? $s->acertos . ' / ' . count($s->questao_ids) : '—' }}</td>
                        <td class="n"><a href="{{ route('concursos.simulados.show', $s) }}">{{ $s->finalizado_em ? 'ver' : 'continuar' }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
