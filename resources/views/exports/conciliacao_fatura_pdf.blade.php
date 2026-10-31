<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Conciliação de Fatura - {{ $cartao['nome'] ?? 'Cartão' }} - {{ $periodo['nome_mes'] ?? '' }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #4338ca;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header .subtitle {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }

        /* Grid de Totais */
        .summary-boxes {
            width: 100%;
            margin-bottom: 18px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-cell {
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            width: 25%;
        }
        .summary-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .val-primary { color: #4338ca; }
        .val-danger { color: #ef4444; }
        .val-success { color: #10b981; }
        .val-warning { color: #f59e0b; }

        /* Seções */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            padding: 6px 10px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .sec-danger { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .sec-warning { background: #fef3c7; color: #92400e; border-left: 4px solid #f59e0b; }
        .sec-orange { background: #ffedd5; color: #9a3412; border-left: 4px solid #f97316; }
        .sec-success { background: #dcfce7; color: #166534; border-left: 4px solid #10b981; }
        .sec-info { background: #e0e7ff; color: #3730a3; border-left: 4px solid #6366f1; }

        /* Tabelas */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 9px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }

        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            text-align: right;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Conciliação de Fatura</h1>
        <div class="subtitle">
            Cartão: <strong>{{ $cartao['nome'] ?? '' }}</strong> &nbsp;|&nbsp; 
            Referência: <strong>{{ $periodo['nome_mes'] ?? '' }}</strong> &nbsp;|&nbsp; 
            Gerado em: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Resumo dos Totais -->
    <div class="summary-boxes">
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-title">Total Fatura (Banco)</div>
                    <div class="summary-value val-primary">R$ {{ number_format($totais['fatura_despesas'] ?? 0, 2, ',', '.') }}</div>
                    <small style="color: #64748b; font-size: 8px;">{{ $contadores['total_fatura'] ?? 0 }} compras</small>
                </td>
                <td class="summary-cell">
                    <div class="summary-title">Total Lançado (Sistema)</div>
                    <div class="summary-value">R$ {{ number_format($totais['sistema'] ?? 0, 2, ',', '.') }}</div>
                    <small style="color: #64748b; font-size: 8px;">{{ $contadores['total_sistema'] ?? 0 }} parcelas</small>
                </td>
                <td class="summary-cell">
                    <div class="summary-title">Diferença Total</div>
                    <div class="summary-value {{ ($totais['diferenca'] ?? 0) == 0 ? 'val-success' : 'val-danger' }}">
                        R$ {{ number_format($totais['diferenca'] ?? 0, 2, ',', '.') }}
                    </div>
                    <small style="font-size: 8px; color: {{ ($totais['diferenca'] ?? 0) == 0 ? '#10b981' : '#ef4444' }};">
                        {{ ($totais['diferenca'] ?? 0) == 0 ? 'Alinhado 100%' : 'Divergência detectada' }}
                    </small>
                </td>
                <td class="summary-cell">
                    <div class="summary-title">Status Itens</div>
                    <div class="summary-value val-success">{{ $contadores['conciliados'] ?? 0 }} OK</div>
                    <small style="color: #64748b; font-size: 8px;">
                        {{ $contadores['faltando_no_sistema'] ?? 0 }} faltam | {{ $contadores['sobrando_no_sistema'] ?? 0 }} sobram
                    </small>
                </td>
            </tr>
        </table>
    </div>

    <!-- 1. FALTANDO NO SISTEMA -->
    @if(!empty($grupos['faltando_no_sistema']))
        <div class="section-title sec-danger">
            🔴 Faltando no Sistema ({{ count($grupos['faltando_no_sistema']) }}) - Compras na fatura que você não cadastrou
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 75px;">Data Compra</th>
                    <th>Estabelecimento na Fatura</th>
                    <th style="width: 70px;" class="text-center">Parcela</th>
                    <th style="width: 90px;" class="text-right">Valor Fatura</th>
                </tr>
            </thead>
            <tbody>
                @php $somaFaltando = 0; @endphp
                @foreach($grupos['faltando_no_sistema'] as $item)
                    <tr>
                        <td>{{ !empty($item['data']) ? \Carbon\Carbon::parse($item['data'])->format('d/m/Y') : '-' }}</td>
                        <td><strong>{{ $item['descricao'] }}</strong></td>
                        <td class="text-center">{{ $item['parcela_info'] ?? '1/1' }}</td>
                        <td class="text-right val-danger font-bold">R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                    </tr>
                    @php $somaFaltando += $item['valor']; @endphp
                @endforeach
                <tr style="background: #fee2e2; font-weight: bold;">
                    <td colspan="3" class="text-right">Total Faltando a Cadastrar:</td>
                    <td class="text-right val-danger">R$ {{ number_format($somaFaltando, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- 2. SOBRANDO NO SISTEMA -->
    @if(!empty($grupos['sobrando_no_sistema']))
        <div class="section-title sec-warning">
            🟡 Sobrando no Sistema ({{ count($grupos['sobrando_no_sistema']) }}) - Lançamentos no sistema que não vieram cobrados nesta fatura
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 75px;">Data Venc.</th>
                    <th style="width: 75px;">Data Compra</th>
                    <th>Descrição no Sistema</th>
                    <th>Categoria</th>
                    <th style="width: 65px;" class="text-center">Parcela</th>
                    <th style="width: 90px;" class="text-right">Valor Sistema</th>
                </tr>
            </thead>
            <tbody>
                @php $somaSobrando = 0; @endphp
                @foreach($grupos['sobrando_no_sistema'] as $p)
                    <tr>
                        <td>{{ !empty($p['data_vencimento']) ? \Carbon\Carbon::parse($p['data_vencimento'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ !empty($p['data_compra']) ? \Carbon\Carbon::parse($p['data_compra'])->format('d/m/Y') : '-' }}</td>
                        <td><strong>{{ $p['descricao'] }}</strong></td>
                        <td>{{ $p['categoria'] }}</td>
                        <td class="text-center">{{ $p['parcela_texto'] }}</td>
                        <td class="text-right val-warning">R$ {{ number_format($p['valor_parcela'], 2, ',', '.') }}</td>
                    </tr>
                    @php $somaSobrando += $p['valor_parcela']; @endphp
                @endforeach
                <tr style="background: #fef3c7; font-weight: bold;">
                    <td colspan="5" class="text-right">Total Sobrando no Sistema:</td>
                    <td class="text-right val-warning">R$ {{ number_format($somaSobrando, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- 3. DIVERGÊNCIAS DE VALOR -->
    @if(!empty($grupos['divergencias_valor']))
        <div class="section-title sec-orange">
            🟠 Divergências de Valor ({{ count($grupos['divergencias_valor']) }}) - Estabelecimentos correspondentes com valores diferentes
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 75px;">Data</th>
                    <th>Estabelecimento</th>
                    <th style="width: 90px;" class="text-right">Valor Fatura</th>
                    <th style="width: 90px;" class="text-right">Valor Sistema</th>
                    <th style="width: 90px;" class="text-right">Diferença</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupos['divergencias_valor'] as $div)
                    <tr>
                        <td>{{ !empty($div['fatura_item']['data']) ? \Carbon\Carbon::parse($div['fatura_item']['data'])->format('d/m/Y') : '-' }}</td>
                        <td>
                            <strong>{{ $div['fatura_item']['descricao'] }}</strong><br>
                            <small style="color: #64748b;">Sistema: {{ $div['sistema_item']['descricao'] }}</small>
                        </td>
                        <td class="text-right">R$ {{ number_format($div['valor_fatura'], 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($div['valor_sistema'], 2, ',', '.') }}</td>
                        <td class="text-right font-bold {{ $div['diferenca'] > 0 ? 'val-danger' : 'val-warning' }}">
                            {{ $div['diferenca'] > 0 ? '+' : '' }}R$ {{ number_format($div['diferenca'], 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 4. CONCILIADOS / BATENDO 100% -->
    @if(!empty($grupos['conciliados']))
        <div class="section-title sec-success">
            🟢 Conciliados ({{ count($grupos['conciliados']) }}) - Lançamentos perfeitamente conferidos
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 75px;">Data Compra</th>
                    <th>Estabelecimento na Fatura</th>
                    <th>Lançamento no Sistema</th>
                    <th style="width: 60px;" class="text-center">Parcela</th>
                    <th style="width: 85px;" class="text-right">Valor</th>
                    <th style="width: 65px;" class="text-center">Certeza</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupos['conciliados'] as $c)
                    <tr>
                        <td>{{ !empty($c['fatura_item']['data']) ? \Carbon\Carbon::parse($c['fatura_item']['data'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ $c['fatura_item']['descricao'] }}</td>
                        <td style="color: #475569;">{{ $c['sistema_item']['descricao'] }}</td>
                        <td class="text-center">{{ $c['sistema_item']['parcela_texto'] }}</td>
                        <td class="text-right font-bold">R$ {{ number_format($c['fatura_item']['valor'], 2, ',', '.') }}</td>
                        <td class="text-center">
                            @if(($c['status'] ?? '') === 'certeiro')
                                <span class="badge badge-success">100%</span>
                            @else
                                <span class="badge badge-warning">{{ $c['confianca'] ?? 80 }}%</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 5. PAGAMENTOS E ESTORNOS -->
    @if(!empty($grupos['pagamentos_estornos']))
        <div class="section-title sec-info">
            ℹ️ Pagamentos de Fatura Anterior & Créditos ({{ count($grupos['pagamentos_estornos']) }}) - Informativo
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Data</th>
                    <th>Descrição</th>
                    <th style="width: 100px;" class="text-right">Valor Creditado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupos['pagamentos_estornos'] as $p)
                    <tr>
                        <td>{{ !empty($p['data']) ? \Carbon\Carbon::parse($p['data'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ $p['descricao'] }}</td>
                        <td class="text-right val-primary font-bold">R$ {{ number_format($p['valor'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Sistema Financeiro Doméstico - Relatório de Auditoria e Conciliação de Cartão de Crédito
    </div>
</body>
</html>
