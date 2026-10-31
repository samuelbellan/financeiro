<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório Financeiro - {{ ucfirst(Carbon\Carbon::create($ano, $mes, 1)->translatedFormat('F')) }}/{{ $ano }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #4338ca;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }
        .header-meta {
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            background: #f1f5f9;
            padding: 6px 10px;
            border-left: 4px solid #6366f1;
            margin-top: 18px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .summary-boxes-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .box-label {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 4px;
        }
        .box-val {
            font-size: 13px;
            font-weight: bold;
        }
        .val-positive { color: #15803d; }
        .val-negative { color: #b91c1c; }
        .val-neutral { color: #4338ca; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10px;
        }
        table.data-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 6px 8px;
            border-bottom: 1.5px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background: #fafafa;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #e0e7ff; color: #3730a3; }

        .tips-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #10b981;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 15px;
        }
        .tip-item {
            font-size: 9.5px;
            color: #334155;
            margin-bottom: 4px;
        }
        .page-break {
            page-break-after: always;
        }
        .footer-note {
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- CABEÇALHO -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: middle;">
                <h1 class="header-title">Relatório Financeiro Consolidado</h1>
                <div class="header-subtitle">
                    Período: <strong>{{ ucfirst(Carbon\Carbon::create($ano, $mes, 1)->translatedFormat('F')) }} / {{ $ano }}</strong>
                    &bull; Titular: <strong>{{ $user->name }}</strong>
                </div>
            </td>
            <td class="header-meta" style="vertical-align: middle;">
                Gerado em: <strong>{{ now()->format('d/m/Y H:i') }}</strong><br>
                Ambiente de Controle Financeiro
            </td>
        </tr>
    </table>

    <!-- SÍNTESE EXECUTIVA -->
    <table class="summary-boxes-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="summary-box" style="border-left: 3px solid #6366f1;">
                <div class="box-label">Saldo em Conta</div>
                <div class="box-val {{ $summary['saldo_cc'] >= 0 ? 'val-positive' : 'val-negative' }}">
                    R$ {{ number_format($summary['saldo_cc'], 2, ',', '.') }}
                </div>
            </td>
            <td class="summary-box" style="border-left: 3px solid #10b981;">
                <div class="box-label">Entradas (Real + Prev)</div>
                <div class="box-val val-positive">
                    R$ {{ number_format($summary['total_entradas'], 2, ',', '.') }}
                </div>
            </td>
            <td class="summary-box" style="border-left: 3px solid #ef4444;">
                <div class="box-label">Saídas (Real + Prev)</div>
                <div class="box-val val-negative">
                    R$ {{ number_format($summary['total_saidas'], 2, ',', '.') }}
                </div>
            </td>
            <td class="summary-box" style="border-left: 3px solid #0ea5e9;">
                <div class="box-label">Resultado Líquido Mês</div>
                <div class="box-val {{ $summary['resultado_liquido_mes'] >= 0 ? 'val-positive' : 'val-negative' }}">
                    R$ {{ number_format($summary['resultado_liquido_mes'], 2, ',', '.') }}
                </div>
            </td>
            <td class="summary-box" style="border-left: 3px solid #8b5cf6;">
                <div class="box-label">Saldo Final Previsto</div>
                <div class="box-val {{ $summary['saldo_final_conta'] >= 0 ? 'val-positive' : 'val-negative' }}">
                    R$ {{ number_format($summary['saldo_final_conta'], 2, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. CONTA CORRENTE -->
    <div class="section-title">1. Conta Corrente — Planejamento & Extrato</div>

    <!-- Metas / Previsões de Conta Corrente -->
    <strong style="font-size: 10px; color: #475569; display: block; margin-bottom: 4px;">Planejamento do Mês (Contas Fixas & Previsões)</strong>
    <table class="data-table">
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Subcategoria</th>
                <th class="text-center">Tipo</th>
                <th class="text-right">Meta Prevista</th>
                <th class="text-right">Consumo Realizado</th>
                <th class="text-right">Restante a Pagar / Receber</th>
            </tr>
        </thead>
        <tbody>
            @forelse($previsoesCC as $p)
                @php
                    $consumo = $p->consumo_real ?? 0;
                    $restante = max(0, $p->valor_previsto - $consumo);
                @endphp
                <tr>
                    <td><strong>{{ $p->categoria }}</strong></td>
                    <td>{{ $p->subcategoria ?: '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $p->tipo == 'receita' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($p->tipo) }}</span>
                    </td>
                    <td class="text-right">R$ {{ number_format($p->valor_previsto, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($consumo, 2, ',', '.') }}</td>
                    <td class="text-right"><strong>R$ {{ number_format($restante, 2, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8; padding: 10px;">Nenhuma previsão cadastrada para este mês.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Extrato Realizado do Mês -->
    <strong style="font-size: 10px; color: #475569; display: block; margin-top: 10px; margin-bottom: 4px;">Transações Realizadas na Conta</strong>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 65px;">Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th class="text-center">Tipo</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transacoes as $t)
                <tr>
                    <td>{{ Carbon\Carbon::parse($t->data)->format('d/m/Y') }}</td>
                    <td><strong>{{ $t->descricao }}</strong></td>
                    <td>{{ $t->categoria ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $t->tipo == 'receita' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($t->tipo) }}</span>
                    </td>
                    <td class="text-right {{ $t->tipo == 'receita' ? 'val-positive' : 'val-negative' }}">
                        {{ $t->tipo == 'receita' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="color: #94a3b8; padding: 10px;">Nenhuma transação realizada neste mês.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="4" class="text-right">Total Realizado do Mês (Receitas - Despesas):</td>
                <td class="text-right {{ ($summary['entradas_realizadas'] - $summary['saidas_realizadas']) >= 0 ? 'val-positive' : 'val-negative' }}">
                    R$ {{ number_format($summary['entradas_realizadas'] - $summary['saidas_realizadas'], 2, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- QUEBRA DE PÁGINA PARA CARTÕES -->
    <div class="page-break"></div>

    <!-- 2. CARTÃO DE CRÉDITO -->
    <div class="section-title">2. Cartão de Crédito — Faturas & Limites de Gastos</div>

    <!-- Resumo dos Cartões -->
    <strong style="font-size: 10px; color: #475569; display: block; margin-bottom: 4px;">Resumo por Cartão Ativo</strong>
    <table class="data-table">
        <thead>
            <tr>
                <th>Cartão</th>
                <th>Vencimento</th>
                <th class="text-right">Fatura Atual Comprometida</th>
                <th class="text-right">Margem Prevista Restante</th>
                <th class="text-right">Impacto Total Previsto</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGeralFaturas = 0; $totalGeralMargem = 0; @endphp
            @foreach($cartoes as $c)
                @php
                    $faturaCartao = $faturasParcelas->where('compra.cartao_id', $c->id)->sum('valor_parcela');
                    $previsoesCartao = $cartaoPrevisoes->where('cartao_id', $c->id);
                    $margemCartao = $previsoesCartao->sum('restante');
                    $impactoTotal = $faturaCartao + $margemCartao;
                    $totalGeralFaturas += $faturaCartao;
                    $totalGeralMargem += $margemCartao;
                @endphp
                <tr>
                    <td><strong>{{ $c->nome }}</strong> ({{ $c->bandeira ?? 'Crédito' }})</td>
                    <td>Dia {{ $c->dia_vencimento }}</td>
                    <td class="text-right val-negative">R$ {{ number_format($faturaCartao, 2, ',', '.') }}</td>
                    <td class="text-right" style="color: #64748b;">R$ {{ number_format($margemCartao, 2, ',', '.') }}</td>
                    <td class="text-right"><strong>R$ {{ number_format($impactoTotal, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold;">
                <td colspan="2" class="text-right">Totais de Cartão:</td>
                <td class="text-right val-negative">R$ {{ number_format($totalGeralFaturas, 2, ',', '.') }}</td>
                <td class="text-right" style="color: #64748b;">R$ {{ number_format($totalGeralMargem, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($totalGeralFaturas + $totalGeralMargem, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Limites e Previsões por Categoria -->
    <strong style="font-size: 10px; color: #475569; display: block; margin-top: 10px; margin-bottom: 4px;">Limites de Gastos por Categoria nos Cartões</strong>
    <table class="data-table">
        <thead>
            <tr>
                <th>Cartão</th>
                <th>Categoria</th>
                <th class="text-right">Limite Definido</th>
                <th class="text-right">Gasto Realizado na Fatura</th>
                <th class="text-right">Margem Restante</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['limites_categoria'] as $lim)
                @php
                    $status = 'Dentro da Meta';
                    $badgeClass = 'badge-success';
                    if ($lim['ultrapassou'] > 0) {
                        $status = 'Estourou (+R$ ' . number_format($lim['ultrapassou'], 2, ',', '.') . ')';
                        $badgeClass = 'badge-danger';
                    } elseif ($lim['limite'] > 0 && ($lim['gasto'] / $lim['limite']) >= 0.8) {
                        $status = 'Alerta (>80%)';
                        $badgeClass = 'badge-warning';
                    }
                @endphp
                <tr>
                    <td>{{ $lim['cartao'] ?: '-' }}</td>
                    <td><strong>{{ $lim['categoria'] }}</strong></td>
                    <td class="text-right">R$ {{ number_format($lim['limite'], 2, ',', '.') }}</td>
                    <td class="text-right val-negative">R$ {{ number_format($lim['gasto'], 2, ',', '.') }}</td>
                    <td class="text-right"><strong>R$ {{ number_format($lim['falta'], 2, ',', '.') }}</strong></td>
                    <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8; padding: 10px;">Nenhum limite de categoria definido para os cartões neste mês.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Detalhamento de Compras da Fatura do Mês -->
    <strong style="font-size: 10px; color: #475569; display: block; margin-top: 10px; margin-bottom: 4px;">Detalhamento das Parcelas & Compras da Fatura do Mês</strong>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 60px;">Vencimento</th>
                <th>Cartão</th>
                <th>Descrição</th>
                <th class="text-center">Parcela</th>
                <th class="text-center">Tipo</th>
                <th>Categoria</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($faturasParcelas as $fp)
                @php
                    $compra = $fp->compra;
                @endphp
                <tr>
                    <td>{{ Carbon\Carbon::parse($fp->data_vencimento)->format('d/m/Y') }}</td>
                    <td>{{ $compra->cartao->nome ?? 'Cartão' }}</td>
                    <td><strong>{{ $compra->descricao }}</strong></td>
                    <td class="text-center">
                        @if($compra->tipo == 'parcelada')
                            {{ $fp->numero_parcela }}/{{ $compra->numero_parcelas }}
                        @else
                            1/1
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $compra->tipo == 'avista' ? 'badge-info' : ($compra->tipo == 'recorrente' ? 'badge-warning' : 'badge-danger') }}">
                            {{ ucfirst($compra->tipo) }}
                        </span>
                    </td>
                    <td>{{ $compra->categoria ?? '-' }}</td>
                    <td class="text-right val-negative">R$ {{ number_format($fp->valor_parcela, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8; padding: 10px;">Nenhuma compra registrada para a fatura deste mês.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- DICAS PARA POUPAR -->
    @if(!empty($summary['dicas']))
        <div class="tips-container">
            <strong style="font-size: 10.5px; color: #065f46; display: block; margin-bottom: 6px;">💡 Dicas & Recomendações Financeiras</strong>
            @foreach($summary['dicas'] as $dica)
                <div class="tip-item">{!! strip_tags(str_replace(['*', '_'], '', $dica)) !!}</div>
            @endforeach
        </div>
    @endif

    <div class="footer-note">
        Relatório Financeiro gerado automaticamente pelo Sistema de Gestão Financeira Pessoal &bull; Documento Confidencial
    </div>

</body>
</html>
