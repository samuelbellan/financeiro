<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Cartões | Finanças de Casa</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .dashboard-analysis { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 1rem; border: 1px solid #e2e8f0; }
        .analysis-card { background: white; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .analysis-label { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; }
        .analysis-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }

        .cards-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .credit-card-ui { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 1rem; padding: 1.5rem; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); transition: transform 0.2s; cursor: pointer; min-height: 180px; display: flex; flex-direction: column; justify-content: space-between; }
        .credit-card-ui:hover { transform: scale(1.02); }
        .card-chip { width: 42px; height: 32px; background: linear-gradient(135deg, #fcd34d 0%, #fbbf24 100%); border-radius: 6px; margin-bottom: 1rem; }
        .card-name { font-size: 1.25rem; font-weight: 700; letter-spacing: 0.025em; }
        .card-limit { font-size: 0.8rem; opacity: 0.8; }
        .card-brand { font-weight: 800; font-style: italic; font-size: 1.1rem; text-transform: uppercase; }

        .invoice-card { background: white; border-radius: 1rem; border: 1px solid #e2e8f0; margin-bottom: 2rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .invoice-header { padding: 1rem 1.5rem; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .invoice-title { font-weight: 700; color: #334155; display: flex; align-items: center; gap: 0.75rem; }
        .invoice-total { font-weight: 800; color: #1e293b; }

        .tag-parcela { font-size: 0.7rem; background: #e0e7ff; color: #4338ca; padding: 0.125rem 0.375rem; border-radius: 4px; }
        .btn-action { background: #6366f1; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .modal { background: white; width: 100%; max-width: 500px; border-radius: 1rem; padding: 1.5rem; }
        .form-input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.625rem; font-size: 0.875rem; }
        .action-btn-small { background: none; border: none; padding: 4px; border-radius: 4px; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; }
        .action-btn-small:hover { opacity: 1; background: #f1f5f9; }

        .period-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .period-nav-group { display: flex; align-items: center; gap: 0.5rem; }
        .nav-arrow {
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0; border-radius: 0.5rem; background: white; cursor: pointer;
            color: #1e293b; transition: all 0.2s; text-decoration: none;
        }
        .nav-arrow:hover { background: #f3f4f6; border-color: #6366f1; color: #6366f1; }
        .btn-today {
            background: #f3f4f6; color: #1e293b; padding: 0.5rem 1rem; border-radius: 0.5rem;
            font-size: 0.75rem; font-weight: 600; text-decoration: none; border: 1px solid #e2e8f0;
        }
        .btn-today:hover { background: #e5e7eb; }
        .input-month {
            border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.4rem 0.75rem;
            font-family: inherit; font-size: 0.875rem; font-weight: 600; color: #1e293b;
        }
        .invoice-row { cursor: pointer; transition: background-color 0.2s; }
        .invoice-row:hover { background-color: #f8fafc; }
        .row-checked { background-color: #dcfce7 !important; }

        /* Floating Action Button */
        .fab-container { position: fixed; bottom: 2rem; right: 2rem; z-index: 1000; display: flex; flex-direction: column-reverse; align-items: center; gap: 1rem; }
        .fab-main { width: 56px; height: 56px; background: #6366f1; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer; border: none; transition: all 0.3s; }
        .fab-main:hover { transform: scale(1.1); background: #4f46e5; }
        .fab-menu { display: none; flex-direction: column; gap: 0.75rem; align-items: flex-end; margin-bottom: 0.5rem; }
        .fab-menu.active { display: flex; animation: slideUp 0.3s ease-out; }
        .fab-item { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; cursor: pointer; }
        .fab-item span { background: white; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; color: #1e293b; box-shadow: 0 2px 5px rgba(0,0,0,0.1); white-space: nowrap; }
        .fab-item-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: none; }
        .bg-card-blue { background: #6366f1; }
        .bg-card-green { background: #10b981; }

        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header"><h2>Financeiro</h2></div>
            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Dashboard</span>
                </a>
                <div class="nav-section">
                    <p class="nav-section-title">Sistemas</p>
                    <a href="{{ route('financas.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Finanças de Casa</span>
                    </a>
                    <a href="{{ route('cartoes.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Meus Cartões</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <h1>Cartões de Crédito</h1>
                <p>Gerencie seus gastos e faturas de forma independente</p>
            </header>

            <div class="content-body">
                @if(session('success'))<div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">{{ session('success') }}</div>@endif

                @php
                    $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
                    $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
                    $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
                    $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
                @endphp

                <div class="period-nav">
                    <div class="period-nav-group">
                        <a href="{{ route('cartoes.index', ['mes' => $prevMonth, 'ano' => $prevYear]) }}" class="nav-arrow" title="Mês Anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </a>
                        <form action="{{ route('cartoes.index') }}" method="GET" id="monthForm" style="display: flex; gap: 0.5rem;">
                            <input type="month" name="period" value="{{ $currentYear }}-{{ str_pad($currentMonth, 2, '0', STR_PAD_LEFT) }}" class="input-month" onchange="submitMonthForm(this.value)">
                        </form>
                        <a href="{{ route('cartoes.index', ['mes' => $nextMonth, 'ano' => $nextYear]) }}" class="nav-arrow" title="Próximo Mês">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                    </div>
                    
                    <a href="{{ route('cartoes.index', ['mes' => date('n'), 'ano' => date('Y')]) }}" class="btn-today">Ir para Hoje</a>
                </div>

                <!-- Spending Analysis Panel -->
                <div class="section-header"><h2>Análise de Compras ({{ \Carbon\Carbon::create(null, $currentMonth)->translatedFormat('F') }})</h2></div>
                <div class="dashboard-analysis">
                    @forelse($gastosPorCategoria as $cat => $total)
                        <div class="analysis-card">
                            <span class="analysis-label">{{ $cat ?? 'Sem Categoria' }}</span>
                            <span class="analysis-value">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                    @empty
                        <p style="grid-column: 1/-1; color: #64748b; font-size: 0.875rem;">Nenhuma compra realizada neste período.</p>
                    @endforelse
                </div>

                <div class="section-header">
                    <h2>Meus Cartões</h2>
                    <button onclick="openCardModal()" class="btn-action">+ Novo Cartão</button>
                </div>

                <div class="cards-container">
                    @foreach($cartoes as $cartao)
                        <div class="credit-card-ui" style="background: linear-gradient(135deg, {{ $cartao->cor ?? '#1e293b' }} 0%, #0f172a 100%);">
                            <div class="card-chip"></div>
                            <div class="card-name">{{ $cartao->nome }}</div>
                            <div class="card-limit">Limite: R$ {{ number_format($cartao->limite, 2, ',', '.') }}</div>
                            <div style="position: absolute; top: 1rem; right: 1rem; display: flex; gap: 5px;">
                                <button onclick="editCard({{ json_encode($cartao) }})" style="background:rgba(255,255,255,0.1); border:none; color:white; padding:4px; border-radius:4px; cursor:pointer;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg></button>
                            </div>
                            <div class="card-details" onclick="openPurchaseModal({{ $cartao->id }}, '{{ $cartao->nome }}')">
                                <div class="card-dates">Fecha: dia {{ $cartao->dia_fechamento }}<br>Vence: dia {{ $cartao->dia_vencimento }}</div>
                                <div class="card-brand">{{ $cartao->bandeira ?? 'VISA' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Individual Invoices Section -->
                <div class="section-header"><h2>Detalhamento de Faturas (Vencimento em {{ \Carbon\Carbon::create(null, $currentMonth)->translatedFormat('F') }})</h2></div>
                @foreach($cartoes as $cartao)
                    <div class="invoice-card">
                        <div class="invoice-header">
                            <div class="invoice-title">
                                <div style="width: 12px; height: 12px; border-radius: 50%; background: {{ $cartao->cor }}"></div>
                                {{ $cartao->nome }}
                            </div>
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <div class="invoice-total">
                                    Total: R$ {{ number_format($faturasPorCartao->get($cartao->id)?->sum('valor_parcela') ?? 0, 2, ',', '.') }}
                                </div>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="{{ route('export.fatura', ['cartao' => $cartao->id, 'format' => 'pdf', 'mes' => $currentMonth, 'ano' => $currentYear]) }}" class="btn-action" style="background:#ef4444; padding:0.3rem 0.6rem; font-size:0.7rem;">PDF</a>
                                    <a href="{{ route('export.fatura', ['cartao' => $cartao->id, 'format' => 'csv', 'mes' => $currentMonth, 'ano' => $currentYear]) }}" class="btn-action" style="background:#10b981; padding:0.3rem 0.6rem; font-size:0.7rem;">CSV</a>
                                </div>
                            </div>
                        </div>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; text-align: left; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">
                                    <th style="padding: 0.75rem 1.5rem;">Vencimento</th>
                                    <th style="padding: 0.75rem 1.5rem;">Descrição</th>
                                    <th style="padding: 0.75rem 1.5rem;">Categoria</th>
                                    <th style="padding: 0.75rem 1.5rem; text-align: right;">Valor</th>
                                    <th style="padding: 0.75rem 1.5rem; text-align: center;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $parcelas = $faturasPorCartao->get($cartao->id) ?? collect(); 
                                    $avista = $parcelas->filter(fn($p) => in_array($p->compra->tipo, ['avista', 'recorrente']));
                                    $parceladas = $parcelas->filter(fn($p) => $p->compra->tipo == 'parcelada');
                                @endphp
                                
                                @if($avista->isNotEmpty())
                                    <tr style="background: #f1f5f9;"><td colspan="5" style="padding: 0.5rem 1.5rem; font-weight: 700; font-size: 0.75rem; color: #475569; text-transform: uppercase;">Compras à Vista / Recorrentes</td></tr>
                                    @foreach($avista as $f)
                                        <tr class="invoice-row" onclick="toggleRow(event, this)" style="border-bottom: 1px solid #f1f5f9; font-size: 0.875rem;">
                                            <td style="padding: 0.75rem 1.5rem;">{{ \Carbon\Carbon::parse($f->data_vencimento)->format('d/m/Y') }}</td>
                                            <td style="padding: 0.75rem 1.5rem;">
                                                {{ $f->compra->descricao }}
                                                @if($f->valor_parcela < 0) <span style="background: #dcfce7; color: #166534; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 4px; margin-left: 5px; font-weight: 700; text-transform: uppercase;">Estorno</span> @endif
                                                @if($f->status === 'paga') <span style="background: #dcfce7; color: #166534; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 4px; margin-left: 5px; font-weight: 700; text-transform: uppercase;">Paga</span> @endif
                                            </td>
                                            <td style="padding: 0.75rem 1.5rem;"><span style="color: #64748b;">{{ $f->compra->categoria ?? '-' }}</span></td>
                                            <td style="padding: 0.75rem 1.5rem; text-align: right; font-weight: 700; color: {{ $f->valor_parcela < 0 ? '#10b981' : 'inherit' }};">
                                                {{ $f->valor_parcela < 0 ? '-' : '' }} R$ {{ number_format(abs($f->valor_parcela), 2, ',', '.') }}
                                            </td>
                                            <td style="padding: 0.75rem 1.5rem; text-align: center;">
                                                <div style="display: flex; gap: 5px; justify-content: center;">
                                                    <button onclick="editCompra({{ json_encode($f->compra) }})" class="action-btn-small" title="Editar Lançamento">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                                    </button>
                                                    <form action="{{ route('cartoes.compras.destroy', $f->compra->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Excluir toda a compra?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="action-btn-small" title="Excluir Lançamento">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if($parceladas->isNotEmpty())
                                    <tr style="background: #f1f5f9;"><td colspan="5" style="padding: 0.5rem 1.5rem; font-weight: 700; font-size: 0.75rem; color: #475569; text-transform: uppercase;">Compras Parceladas</td></tr>
                                    @foreach($parceladas as $f)
                                        <tr class="invoice-row" onclick="toggleRow(event, this)" style="border-bottom: 1px solid #f1f5f9; font-size: 0.875rem;">
                                            <td style="padding: 0.75rem 1.5rem;">{{ \Carbon\Carbon::parse($f->data_vencimento)->format('d/m/Y') }}</td>
                                            <td style="padding: 0.75rem 1.5rem;">
                                                {{ $f->compra->descricao }}
                                                <span class="tag-parcela">{{ $f->numero_parcela }}/{{ $f->compra->numero_parcelas }}</span>
                                                @if($f->valor_parcela < 0) <span style="background: #dcfce7; color: #166534; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 4px; margin-left: 5px; font-weight: 700; text-transform: uppercase;">Estorno</span> @endif
                                                @if($f->status === 'paga') <span style="background: #dcfce7; color: #166534; font-size: 0.65rem; padding: 0.125rem 0.375rem; border-radius: 4px; margin-left: 5px; font-weight: 700; text-transform: uppercase;">Paga</span> @endif
                                            </td>
                                            <td style="padding: 0.75rem 1.5rem;"><span style="color: #64748b;">{{ $f->compra->categoria ?? '-' }}</span></td>
                                            <td style="padding: 0.75rem 1.5rem; text-align: right; font-weight: 700; color: {{ $f->valor_parcela < 0 ? '#10b981' : 'inherit' }};">
                                                {{ $f->valor_parcela < 0 ? '-' : '' }} R$ {{ number_format(abs($f->valor_parcela), 2, ',', '.') }}
                                            </td>
                                            <td style="padding: 0.75rem 1.5rem; text-align: center;">
                                                <div style="display: flex; gap: 5px; justify-content: center;">
                                                    <button onclick="editCompra({{ json_encode($f->compra) }})" class="action-btn-small" title="Editar Lançamento">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                                    </button>
                                                    <form action="{{ route('cartoes.compras.destroy', $f->compra->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Excluir toda a compra?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="action-btn-small" title="Excluir Lançamento">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                
                                @if($parcelas->isEmpty())
                                    <tr><td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">Vazio.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modalCard" class="modal-overlay"><div class="modal"><h3 id="cardModalTitle">Novo Cartão</h3><br><form id="cardForm" action="{{ route('cartoes.store') }}" method="POST">@csrf<div id="cardMethod"></div><div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;"><div class="form-group"><label>Nome</label><input type="text" name="nome" id="cardName" class="form-input" required></div><div class="form-group"><label>Cor</label><input type="color" name="cor" id="cardColor" class="color-input" value="#6366f1"></div></div><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;"><div class="form-group"><label>Bandeira</label><input type="text" name="bandeira" id="cardBrand" class="form-input"></div><div class="form-group"><label>Limite</label><input type="number" step="0.01" name="limite" id="cardLimit" class="form-input" required></div></div><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;"><div class="form-group"><label>Fechamento</label><input type="number" name="dia_fechamento" id="cardClose" class="form-input" required min="1" max="31"></div><div class="form-group"><label>Vencimento</label><input type="number" name="dia_vencimento" id="cardDue" class="form-input" required min="1" max="31"></div></div><div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;"><button type="button" onclick="document.getElementById('modalCard').style.display='none'" class="btn-action" style="background: #94a3b8;">Cancelar</button><button type="submit" class="btn-action">Salvar</button></div></form></div></div>

    <div id="modalPurchase" class="modal-overlay">
        <div class="modal">
            <h3 id="purchaseModalTitle">Lançamento</h3><br>
            <form id="purchaseForm" action="{{ route('cartoes.compras.store') }}" method="POST">
                @csrf<div id="purchaseMethod"></div>
                <div class="form-group"><label>Cartão</label><select name="cartao_id" id="purchaseCardId" class="form-input">@foreach($cartoes as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach</select></div>
                <div class="form-group"><label>Descrição</label><input type="text" name="descricao" id="purchaseDesc" class="form-input" required></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group"><label>Valor Total</label><input type="number" step="0.01" name="valor_total" id="purchaseVal" class="form-input" required></div>
                    <div class="form-group"><label>Tipo</label><select name="tipo" id="purchaseType" class="form-input" onchange="toggleInstallments()"><option value="avista">À vista</option><option value="parcelada">Parcelada</option><option value="recorrente">Recorrente</option></select></div>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem;">
                    <input type="checkbox" name="is_estorno" id="purchaseEstorno" value="1" style="width: 1rem; height: 1rem; accent-color: var(--primary);">
                    <label for="purchaseEstorno" style="margin: 0; font-weight: 600; color: #b91c1c;">Lançar como Estorno (Crédito na fatura)</label>
                </div>
                <div id="installmentsGroup" class="form-group" style="display: none;"><label>Parcelas</label><input type="number" name="numero_parcelas" id="purchaseInstallments" class="form-input" value="1" min="1"></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group"><label>Data</label><input type="date" name="data_compra" id="purchaseDate" class="form-input" required></div>
                    <div class="form-group"><label>Categoria</label><input type="text" name="categoria" id="purchaseCat" class="form-input"></div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button type="button" onclick="document.getElementById('modalPurchase').style.display='none'" class="btn-action" style="background: #94a3b8;">Cancelar</button>
                    <button type="submit" class="btn-action">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCardModal() { document.getElementById('cardModalTitle').innerText = 'Novo Cartão'; document.getElementById('cardForm').action = "{{ route('cartoes.store') }}"; document.getElementById('cardMethod').innerHTML = ''; document.getElementById('modalCard').style.display = 'flex'; }
        function editCard(card) { document.getElementById('cardModalTitle').innerText = 'Editar Cartão'; document.getElementById('cardForm').action = `/financas/cartoes/${card.id}`; document.getElementById('cardMethod').innerHTML = '@method("PUT")'; document.getElementById('cardName').value = card.nome; document.getElementById('cardColor').value = card.cor || '#6366f1'; document.getElementById('cardBrand').value = card.bandeira || ''; document.getElementById('cardLimit').value = card.limite; document.getElementById('cardClose').value = card.dia_fechamento; document.getElementById('cardDue').value = card.dia_vencimento; document.getElementById('modalCard').style.display = 'flex'; }
        
        function openPurchaseModal(id, name) { 
            document.getElementById('purchaseModalTitle').innerText = 'Nova Compra - ' + name;
            document.getElementById('purchaseForm').action = "{{ route('cartoes.compras.store') }}";
            document.getElementById('purchaseMethod').innerHTML = '';
            document.getElementById('purchaseCardId').value = id;
            document.getElementById('purchaseDesc').value = '';
            document.getElementById('purchaseVal').value = '';
            document.getElementById('purchaseEstorno').checked = false;
            document.getElementById('purchaseType').value = 'avista';
            document.getElementById('purchaseDate').value = "{{ date('Y-m-d') }}";
            document.getElementById('purchaseCat').value = '';
            toggleInstallments();
            document.getElementById('modalPurchase').style.display = 'flex'; 
        }

        function editCompra(compra) {
            document.getElementById('purchaseModalTitle').innerText = 'Editar Lançamento';
            document.getElementById('purchaseForm').action = `/financas/cartoes/compras/${compra.id}`;
            document.getElementById('purchaseMethod').innerHTML = '@method("PUT")';
            document.getElementById('purchaseCardId').value = compra.cartao_id;
            document.getElementById('purchaseDesc').value = compra.descricao;
            document.getElementById('purchaseVal').value = Math.abs(compra.valor_total);
            document.getElementById('purchaseEstorno').checked = compra.valor_total < 0;
            document.getElementById('purchaseType').value = compra.tipo;
            document.getElementById('purchaseDate').value = compra.data_compra;
            document.getElementById('purchaseCat').value = compra.categoria || '';
            document.getElementById('purchaseInstallments').value = compra.numero_parcelas || 1;
            toggleInstallments();
            document.getElementById('modalPurchase').style.display = 'flex';
        }

        function toggleInstallments() { const type = document.getElementById('purchaseType').value; document.getElementById('installmentsGroup').style.display = (type === 'parcelada') ? 'block' : 'none'; }
        
        function toggleRow(event, row) {
            // Evita marcar a linha se clicar em botões ou formulários
            if (event.target.closest('button') || event.target.closest('form')) return;
            row.classList.toggle('row-checked');
        }

        function submitMonthForm(val) {
            if (!val) return;
            const parts = val.split('-');
            const url = new URL(window.location.href);
            url.searchParams.set('ano', parts[0]);
            url.searchParams.set('mes', parseInt(parts[1]));
            window.location.href = url.toString();
        }

        window.onclick = function(e) { 
            if(e.target.className == 'modal-overlay') e.target.style.display = 'none';
            if(!e.target.closest('.fab-container')) {
                const fabMenu = document.getElementById('fabMenu');
                if(fabMenu) fabMenu.classList.remove('active');
            }
        }
    </script>

    @php $firstCard = $cartoes->first(); @endphp
    <div class="fab-container">
        <button class="fab-main" onclick="toggleFab()" title="Novo Lançamento">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </button>
        <div class="fab-menu" id="fabMenu">
            <div class="fab-item" onclick="{{ $firstCard ? "openPurchaseModal('$firstCard->id', '$firstCard->nome')" : "openCardModal()" }}; toggleFab()">
                <span>Nova Compra</span>
                <button class="fab-item-icon bg-card-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                </button>
            </div>
            <div class="fab-item" onclick="openCardModal(); toggleFab()">
                <span>Novo Cartão</span>
                <button class="fab-item-icon bg-card-green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleFab() {
            document.getElementById('fabMenu').classList.toggle('active');
        }
    </script>
</body>
</html>
