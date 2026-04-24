<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orçamento Mensal</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #6366f1; }
        h2 { border-bottom: 2px solid #6366f1; padding-bottom: 5px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .receita { color: #059669; font-weight: bold; }
        .despesa { color: #dc2626; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; }
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
    @endphp

    <div class="summary">
        <strong>Total de Receitas:</strong> R$ {{ number_format($totalReceitas, 2, ',', '.') }}<br>
        <strong>Total de Despesas:</strong> R$ {{ number_format($totalDespesas, 2, ',', '.') }}<br>
        <strong>Saldo Final:</strong> R$ {{ number_format($totalReceitas - $totalDespesas, 2, ',', '.') }}
    </div>

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
