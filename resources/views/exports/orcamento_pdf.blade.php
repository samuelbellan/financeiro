<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento Mensal</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #6366f1; }
        h2 { border-bottom: 2px solid #6366f1; padding-bottom: 5px; margin-top: 20px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f3f4f6; padding: 8px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .receita { color: #059669; font-weight: bold; }
        .despesa { color: #dc2626; font-weight: bold; }
        
        /* Estilo de Cards */
        .cards-container { width: 100%; text-align: center; margin-bottom: 20px; }
        .card { display: inline-block; width: 30%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 0 1%; box-sizing: border-box; }
        .card-title { font-size: 10px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; font-weight: bold; }
        .card-value { font-size: 16px; font-weight: bold; color: #1e293b; }
        .card-saldo { border-left: 4px solid #6366f1; }
        .card-receita { border-left: 4px solid #10b981; }
        .card-despesa { border-left: 4px solid #ef4444; }

        /* Gráficos */
        .charts-container { text-align: center; margin-bottom: 30px; }
        .chart-box { display: inline-block; width: 45%; vertical-align: top; margin: 0 2%; }
        .chart-box img { max-width: 100%; height: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Orçamento Mensal</h1>
        <p>{{ Carbon\Carbon::create(null, $mes)->translatedFormat('F') }} / {{ $ano }}</p>
    </div>

    @php
        $totalReceitas = $transacoes->where('tipo', 'receita')->sum('valor');
        $totalDespesas = $transacoes->where('tipo', 'despesa')->sum('valor');
        $saldo = $totalReceitas - $totalDespesas;
    @endphp

    <div class="cards-container">
        <div class="card card-receita">
            <div class="card-title">Total Receitas</div>
            <div class="card-value">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</div>
        </div>
        <div class="card card-despesa">
            <div class="card-title">Total Despesas</div>
            <div class="card-value">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</div>
        </div>
        <div class="card card-saldo">
            <div class="card-title">Saldo Final</div>
            <div class="card-value">R$ {{ number_format($saldo, 2, ',', '.') }}</div>
        </div>
    </div>

    @if(isset($chart1) || isset($chart2))
    <div class="charts-container">
        @if(isset($chart1))
        <div class="chart-box">
            <h2>Planejado vs Realizado</h2>
            <img src="{{ $chart1 }}" alt="Gráfico 1">
        </div>
        @endif
        @if(isset($chart2))
        <div class="chart-box">
            <h2>Distribuição de Gastos</h2>
            <img src="{{ $chart2 }}" alt="Gráfico 2">
        </div>
        @endif
    </div>
    @endif

    <h2>Lançamentos Realizados</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transacoes as $t)
                <tr>
                    <td>{{ Carbon\Carbon::parse($t->data)->format('d/m/Y') }}</td>
                    <td>{{ $t->descricao }}</td>
                    <td>{{ $t->categoria }}</td>
                    <td><span class="{{ $t->tipo }}">{{ ucfirst($t->tipo) }}</span></td>
                    <td style="text-align: right;">R$ {{ number_format($t->valor, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Planejamento (Previsões)</h2>
    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Tipo</th>
                <th style="text-align: right;">Meta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($previsoes as $p)
                <tr>
                    <td>{{ $p->categoria }}</td>
                    <td>{{ ucfirst($p->tipo) }}</td>
                    <td style="text-align: right;">R$ {{ number_format($p->valor_previsto, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
