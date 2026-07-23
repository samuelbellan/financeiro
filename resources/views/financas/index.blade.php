<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças de Casa | Controle Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --bg-card: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .chart-container {
            height: 250px;
            position: relative;
        }

        .toggle-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s;
        }
        .toggle-btn:hover { background: #f3f4f6; color: var(--text-main); }
        .collapsible-content { transition: max-height 0.3s ease-out; overflow: hidden; }
        .collapsible-content.collapsed { max-height: 0 !important; }

        .period-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .period-nav-group { display: flex; align-items: center; gap: 0.5rem; }
        .nav-arrow {
            width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); border-radius: 0.5rem; background: white; cursor: pointer;
            color: var(--text-main); transition: all 0.2s; text-decoration: none;
        }
        .nav-arrow:hover { background: #f3f4f6; border-color: var(--primary); color: var(--primary); }
        .btn-today {
            background: #f3f4f6; color: var(--text-main); padding: 0.5rem 1rem; border-radius: 0.5rem;
            font-size: 0.75rem; font-weight: 600; text-decoration: none; border: 1px solid var(--border);
        }
        .btn-today:hover { background: #e5e7eb; }
        .input-month {
            border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.4rem 0.75rem;
            font-family: inherit; font-size: 0.875rem; font-weight: 600; color: var(--text-main);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            border-left: 4px solid transparent;
        }
        .stat-card.balance { border-left-color: var(--primary); }
        .stat-card.income { border-left-color: var(--success); }
        .stat-card.expense { border-left-color: var(--danger); }
        .stat-card.planned { border-left-color: #f59e0b; }
        .stat-card.consolidado { border-left-color: #8b5cf6; }

        .stat-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
        .stat-value { font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
        .stat-sub { font-size: 0.7rem; color: var(--text-muted); }

        .predictions-section {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .predictions-header {
            padding: 1.25rem 1.5rem;
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .prediction-row {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 2fr 1fr 2fr 1fr auto;
            align-items: center;
            gap: 1rem;
        }
        .prediction-row:last-child { border-bottom: none; }

        .progress-mini-container { height: 6px; background: #f3f4f6; border-radius: 999px; overflow: hidden; margin-top: 4px; }
        .progress-mini-bar { height: 100%; background: var(--primary); }
        .progress-mini-bar.receita { background: var(--success); }
        .progress-mini-bar.danger { background: var(--danger); }

        .btn-add {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-add:hover { background: var(--primary-hover); }

        .table-container { background: white; border-radius: 1rem; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1.5rem; background: #f9fafb; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); font-size: 0.875rem; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: none; justify-content: center; align-items: center; z-index: 2000;
        }
        .modal { background: white; width: 100%; max-width: 550px; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.25rem; max-height: 70vh; overflow-y: auto; }
        .modal-footer { padding: 1rem 1.25rem; background: #f9fafb; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.5rem; }
        .form-input { width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; }
        .tag { padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
        .tag-receita { background: #dcfce7; color: #166534; }
        .tag-despesa { background: #fee2e2; color: #991b1b; }

        .action-btn-small { background: none; border: none; padding: 4px; border-radius: 4px; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; color: var(--text-main); }
        .action-btn-small:hover { opacity: 1; background: #f1f5f9; }

        /* Floating Action Button */
        .fab-container { position: fixed; bottom: 2rem; right: 2rem; z-index: 1000; display: flex; flex-direction: column-reverse; align-items: center; gap: 1rem; }
        .fab-main { width: 56px; height: 56px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer; border: none; transition: all 0.3s; }
        .fab-main:hover { transform: scale(1.1); background: var(--primary-hover); }
        .fab-menu { display: none; flex-direction: column; gap: 0.75rem; align-items: flex-end; margin-bottom: 0.5rem; }
        .fab-menu.active { display: flex; animation: slideUp 0.3s ease-out; }
        .fab-item { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; cursor: pointer; }
        .fab-item span { background: white; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600; color: var(--text-main); box-shadow: 0 2px 5px rgba(0,0,0,0.1); white-space: nowrap; }
        .fab-item-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border: none; }
        .bg-transacao { background: var(--success); }
        .bg-previsao { background: var(--primary); }

        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Estilo para Lançamentos Selecionados para Auditoria */
        #transactionsTable tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }
        #transactionsTable tbody tr:hover {
            background-color: #f8fafc;
        }
        #transactionsTable tbody tr.selected-audit {
            background-color: #dcfce7 !important; /* Soft pastel green highlight */
        }
        #transactionsTable tbody tr.selected-audit td {
            background-color: #dcfce7 !important;
        }
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
                    <a href="{{ route('financas.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Finanças de Casa</span>
                    </a>
                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>
                    <a href="{{ route('categorias.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Categorias</span>
                    </a>
                    <a href="{{ route('cartoes.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Meus Cartões</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1>Finanças de Casa</h1>
                    <p>Planejamento de {{ \Carbon\Carbon::create(null, $mes)->translatedFormat('F') }} / {{ $ano }}</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="downloadPdf()" class="btn-add" style="background: #ef4444; font-size: 0.75rem; border: none; cursor: pointer; color: white;">PDF</button>
                    <a href="{{ route('export.orcamento', ['format' => 'csv', 'mes' => $mes, 'ano' => $ano, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" class="btn-add" style="background: #10b981; font-size: 0.75rem;">CSV</a>
                    <a href="{{ route('export.orcamento', ['format' => 'sql', 'mes' => $mes, 'ano' => $ano, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" class="btn-add" style="background: #3b82f6; font-size: 0.75rem;">DB (SQL)</a>
                </div>
            </header>

            <div class="content-body">
                @if(session('success'))
                    <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-weight: 600;">
                        {{ session('success') }}
                    </div>
                    <script>
                        if (typeof notifyDataUpdated === 'function') { notifyDataUpdated(); } else { localStorage.setItem('financeiro_data_updated', Date.now().toString()); }
                    </script>
                @endif
                @php
                    $prevMonth = $mes == 1 ? 12 : $mes - 1;
                    $prevYear = $mes == 1 ? $ano - 1 : $ano;
                    $nextMonth = $mes == 12 ? 1 : $mes + 1;
                    $nextYear = $mes == 12 ? $ano + 1 : $ano;
                @endphp

                <div class="period-nav" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="period-nav-group">
                            <a href="{{ route('financas.index', ['mes' => $prevMonth, 'ano' => $prevYear]) }}" class="nav-arrow" title="Mês Anterior">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </a>
                            <form action="{{ route('financas.index') }}" method="GET" id="monthForm" style="display: flex; gap: 0.5rem;">
                                <input type="month" name="period" value="{{ $ano }}-{{ str_pad($mes, 2, '0', STR_PAD_LEFT) }}" class="input-month" onchange="submitMonthForm(this.value)">
                            </form>
                            <a href="{{ route('financas.index', ['mes' => $nextMonth, 'ano' => $nextYear]) }}" class="nav-arrow" title="Próximo Mês">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>
                        </div>
                        
                        <a href="{{ route('financas.index', ['mes' => date('n'), 'ano' => date('Y')]) }}" class="btn-today">Mês Atual</a>
                    </div>

                    <form action="{{ route('financas.index') }}" method="GET" id="customPeriodForm" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <label for="data_inicio" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">De:</label>
                            <input type="date" id="data_inicio" name="data_inicio" value="{{ $dataInicio }}" class="input-month" onchange="this.form.submit()">
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <label for="data_fim" style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Até:</label>
                            <input type="date" id="data_fim" name="data_fim" value="{{ $dataFim }}" class="input-month" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>

                <div class="dashboard-grid">
                    <div class="stat-card balance">
                        <span class="stat-label">Saldo em Conta</span>
                        <span class="stat-value" style="color: {{ $saldo >= 0 ? 'var(--success)' : 'var(--danger)' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</span>
                        <span class="stat-sub">Realizado até agora</span>
                    </div>
                    <div class="stat-card planned">
                        <span class="stat-label">Planejado (Meta)</span>
                        <span class="stat-value">R$ {{ number_format($totalPrevistoReceita - $totalPrevistoDespesa, 2, ',', '.') }}</span>
                        <span class="stat-sub">Receita - Despesa Prevista</span>
                    </div>
                    <div class="stat-card consolidado" onclick="openConsolidadoModal()" style="cursor: pointer;" title="Clique para ver detalhes">
                        <span class="stat-label">Consolidado + Previsão</span>
                        <span class="stat-value" style="color: {{ $consolidadoMaisPrevisao >= 0 ? 'var(--success)' : 'var(--danger)' }}">R$ {{ number_format($consolidadoMaisPrevisao, 2, ',', '.') }}</span>
                        <span class="stat-sub">Projeção fim do mês (Ver Detalhes)</span>
                    </div>
                    <div class="stat-card" onclick="openConsolidadoAnoModal()" style="cursor: pointer; border-left-color: #0ea5e9;" title="Clique para ver todos os meses">
                        <span class="stat-label">Consolidado Ano ({{ $ano }})</span>
                        <span class="stat-value" style="color: {{ $totalConsolidadoAno >= 0 ? 'var(--success)' : 'var(--danger)' }}">R$ {{ number_format($totalConsolidadoAno, 2, ',', '.') }}</span>
                        <span class="stat-sub">Soma de todos os meses (Ver Detalhes)</span>
                    </div>
                    <div class="stat-card income">
                        <span class="stat-label">Receitas</span>
                        <span class="stat-value" style="color: var(--success)">+ R$ {{ number_format($receitasMes, 2, ',', '.') }}</span>
                        <span class="stat-sub">Meta: R$ {{ number_format($totalPrevistoReceita, 2, ',', '.') }}</span>
                    </div>
                    <div class="stat-card expense">
                        <span class="stat-label">Despesas</span>
                        <span class="stat-value" style="color: var(--danger)">- R$ {{ number_format($despesasMes, 2, ',', '.') }}</span>
                        <span class="stat-sub">Meta: R$ {{ number_format($totalPrevistoDespesa, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="charts-grid">
                    <div class="chart-card">
                        <h3 style="font-size: 1rem; margin-bottom: 1rem;">Planejado vs Realizado</h3>
                        <div class="chart-container">
                            <canvas id="chartComparison"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3 style="font-size: 1rem; margin-bottom: 1rem;">Distribuição de Gastos</h3>
                        <div class="chart-container">
                            <canvas id="chartExpenses"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Daily Spending Chart for Checking Account -->
                <div class="chart-card" style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">Análise Diária de Despesas da Conta</h3>
                    @if(empty($despesasDiariasValues) || array_sum($despesasDiariasValues) == 0)
                        <p style="color: var(--text-muted); font-size: 0.875rem; padding: 2rem; text-align: center; margin: 0;">Nenhuma despesa registrada no período selecionado.</p>
                    @else
                        <div class="chart-container" style="height: 220px;">
                            <canvas id="chartDiarioDespesas"></canvas>
                        </div>
                    @endif
                </div>

                <div class="predictions-section">
                    <div class="predictions-header">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <h2>Planejamento do Mês</h2>
                            <button onclick="toggleSection('planningContent')" class="toggle-btn" id="btn-planningContent">Recolher</button>
                        </div>
                        <button onclick="openPrevisaoModal()" class="btn-add" style="background: var(--success);">+ Adicionar Previsão</button>
                    </div>
                    <div id="planningContent" class="collapsible-content">
                    @forelse($previsoes->sortByDesc('tipo') as $p)
                        <div class="prediction-row">
                            <div>
                                <span style="font-weight: 700; color: var(--text-main);">{{ $p->categoria }}</span>
                                @if($p->subcategoria)<span style="font-size: 0.75rem; color: var(--text-muted);"> • {{ $p->subcategoria }}</span>@endif
                                @if($p->observacao)<div style="font-size: 0.7rem; color: var(--text-muted); font-style: italic;">"{{ $p->observacao }}"</div>@endif
                            </div>
                            <div><span class="tag tag-{{ $p->tipo }}">{{ ucfirst($p->tipo) }}</span></div>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 2px;">
                                    <span>Real: R$ {{ number_format($p->consumo_real, 2, ',', '.') }}</span>
                                    <span style="font-weight: 600;">Meta: R$ {{ number_format($p->valor_previsto, 2, ',', '.') }}</span>
                                </div>
                                <div class="progress-mini-container">
                                    <div class="progress-mini-bar {{ $p->tipo == 'receita' ? 'receita' : ($p->porcentagem >= 90 ? 'danger' : '') }}" style="width: {{ $p->porcentagem }}%"></div>
                                </div>
                            </div>
                            <div style="text-align: right; font-weight: 700;">
                                <span title="Saldo planejado" style="color: {{ $p->tipo == 'receita' ? 'var(--success)' : 'var(--primary)' }}">R$ {{ number_format($p->restante, 2, ',', '.') }}</span>
                            </div>
                            <div style="text-align: right; display: flex; gap: 5px; justify-content: flex-end;">
                                <button onclick="editPrevisao({{ json_encode($p) }})" class="action-btn-small" title="Editar Previsão">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                </button>
                                <form action="{{ route('financas.previsoes.destroy', $p->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Excluir?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn-small" title="Excluir Previsão">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        @if($faturasPorCartao->isEmpty())
                            <div style="padding: 2rem; text-align: center; color: var(--text-muted);">Nenhum planejamento para este período.</div>
                        @endif
                    @endforelse

                    @foreach($faturasPorCartao as $cartaoId => $parcelas)
                        @php
                            $cartao = $parcelas->first()->compra->cartao;
                            $valorFatura = $parcelas->sum('valor_parcela');
                        @endphp
                        <div class="prediction-row" style="background: #fdf2f2;">
                            <div>
                                <span style="font-weight: 700; color: #b91c1c;">Fatura: {{ $cartao->nome }}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"> • Cartão de Crédito</span>
                            </div>
                            <div><span class="tag tag-despesa">Cartão</span></div>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 2px;">
                                    <span>Vencimento dia {{ $cartao->dia_vencimento }}</span>
                                    <span style="font-weight: 600;">Total: R$ {{ number_format($valorFatura, 2, ',', '.') }}</span>
                                </div>
                                <div class="progress-mini-container">
                                    <div class="progress-mini-bar danger" style="width: 100%"></div>
                                </div>
                            </div>
                            <div style="text-align: right; font-weight: 700;">
                                <span style="color: var(--danger)">R$ {{ number_format($valorFatura, 2, ',', '.') }}</span>
                            </div>
                            <div style="text-align: right;">
                                <a href="{{ route('cartoes.index') }}" class="action-btn-small" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;" title="Ver Detalhes">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <h2 style="font-size: 1.125rem;">Extrato Realizado</h2>
                            <button onclick="toggleSection('transactionsContent')" class="toggle-btn" id="btn-transactionsContent">Recolher</button>
                        </div>
                        <button onclick="openModal()" class="btn-add">Nova Transação</button>
                    </div>
                    <div id="transactionsContent" class="collapsible-content">
                    <table id="transactionsTable">
                        <thead>
                            <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th style="text-align: right;">Ações</th></tr>
                        </thead>
                        <tbody>
                            @foreach($transacoes as $t)
                                <tr data-id="{{ $t->id }}">
                                    <td>{{ \Carbon\Carbon::parse($t->data)->format('d/m/Y') }}</td>
                                    <td style="font-weight: 600;">{{ $t->descricao }}</td>
                                    <td>
                                        <span>{{ $t->categoria ?? '-' }}</span>
                                        @if($t->subcategoria)<br><span style="font-size: 0.7rem; color: var(--text-muted);">{{ $t->subcategoria }}</span>@endif
                                    </td>
                                    <td style="font-weight: 700; color: {{ $t->tipo == 'receita' ? 'var(--success)' : 'var(--danger)' }}">
                                        {{ $t->tipo == 'receita' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                            <button onclick="editTransacao({{ json_encode($t) }})" class="action-btn-small" title="Editar Lançamento">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                            </button>
                                            <form action="{{ route('financas.destroy', $t->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Excluir?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="action-btn-small" title="Excluir Lançamento">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modalConsolidado" class="modal-overlay">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Detalhes: Consolidado + Previsão</h3>
                <button onclick="document.getElementById('modalConsolidado').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem;">
                    <strong>Como é calculado:</strong> Base Planejada (R$ {{ number_format($totalPrevistoReceita - $totalPrevistoDespesa, 2, ',', '.') }}) 
                    + Imprevistos - Excessos de gastos.
                </p>

                <h4 style="margin-bottom: 0.5rem; font-size: 0.9rem;">Transações Imprevistas</h4>
                @if(count($listaImprevistos) > 0)
                    <table style="margin-bottom: 1.5rem; font-size: 0.8rem;">
                        <thead>
                            <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th style="text-align:right;">Valor</th></tr>
                        </thead>
                        <tbody>
                            @foreach($listaImprevistos as $t)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($t->data)->format('d/m') }}</td>
                                    <td>{{ $t->descricao }}</td>
                                    <td>{{ $t->categoria ?? 'Sem Categoria' }}</td>
                                    <td style="text-align:right; font-weight: 600; color: {{ $t->tipo == 'receita' ? 'var(--success)' : 'var(--danger)' }}">
                                        {{ $t->tipo == 'receita' ? '+' : '-' }} R$ {{ number_format($t->valor, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem;">Nenhuma transação imprevista.</p>
                @endif

                <h4 style="margin-bottom: 0.5rem; font-size: 0.9rem;">Excesso de Gastos / Receitas (Fora da Meta)</h4>
                @if(count($listaExcessos) > 0)
                    <table style="font-size: 0.8rem;">
                        <thead>
                            <tr><th>Planejamento (Cat/Sub)</th><th style="text-align:right;">Excedido</th></tr>
                        </thead>
                        <tbody>
                            @foreach($listaExcessos as $e)
                                <tr>
                                    <td>{{ $e->categoria }}</td>
                                    <td style="text-align:right; font-weight: 600; color: {{ $e->tipo == 'receita' ? 'var(--success)' : 'var(--danger)' }}">
                                        {{ $e->tipo == 'receita' ? '+' : '-' }} R$ {{ number_format($e->valor, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Nenhum excesso registrado.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Consolidado Anual -->
    <div id="modalConsolidadoAno" class="modal-overlay">
        <div class="modal" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Consolidado Anual ({{ $ano }})</h3>
                <button onclick="document.getElementById('modalConsolidadoAno').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <table style="font-size: 0.85rem; width: 100%;">
                    <thead>
                        <tr><th style="padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">Mês</th><th style="text-align:right; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border);">Valor</th></tr>
                    </thead>
                    <tbody>
                        @foreach($consolidadoAnoMeses as $m => $valor)
                            <tr>
                                <td style="padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9;">{{ ucfirst(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}</td>
                                <td style="text-align:right; font-weight: 600; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; color: {{ $valor >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                                    R$ {{ number_format($valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="font-weight: 700; padding-top: 1rem; font-size: 0.95rem;">Total do Ano</td>
                            <td style="text-align:right; font-weight: 700; padding-top: 1rem; font-size: 0.95rem; color: {{ $totalConsolidadoAno >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                                R$ {{ number_format($totalConsolidadoAno, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay">
        <div class="modal">
            <div class="modal-header"><h3 id="modalTitle">Lançamento</h3><button onclick="closeModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button></div>
            <form id="transactionForm" method="POST" action="{{ route('financas.store') }}">
                @csrf<div id="methodField"></div>
                <div class="modal-body">
                    <div class="form-group"><label>Descrição</label><input type="text" name="descricao" id="inputDescricao" class="form-input" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group">
                            <label>Valor (R$)</label>
                            <input type="text" id="inputValorDisplay" class="form-input" required placeholder="0,00" inputmode="numeric">
                            <input type="hidden" name="valor" id="inputValor">
                        </div>
                        <div class="form-group"><label>Tipo</label><select name="tipo" id="inputTipo" class="form-input" onchange="filterCategoriesByTipo(this.value)"><option value="receita">Receita</option><option value="despesa" selected>Despesa</option></select></div>
                    </div>
                    <div id="containerPagarFatura" class="form-group" style="margin-top: 1rem; display: block;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" id="checkPagarFatura" name="pagar_fatura" value="1" onchange="toggleFaturaFields(this.checked)"> Pagar fatura do cartão?
                        </label>
                    </div>
                    <div id="faturaFields" style="display: none; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; background: #f8fafc; padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                        <div class="form-group">
                            <label>Cartão</label>
                            <select name="cartao_id" class="form-input" id="selectCartaoFatura">
                                <option value="">Selecione...</option>
                                @foreach($cartoes as $cartao)
                                    <option value="{{ $cartao->id }}">{{ $cartao->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mês/Ano Fatura</label>
                            <input type="month" name="mes_ano_fatura" class="form-input" id="inputMesAnoFatura" value="{{ date('Y-m') }}">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group"><label>Data</label><input type="date" name="data" id="inputData" class="form-input" required value="{{ date('Y-m-d') }}"></div>
                        <div class="form-group"><label>Categoria</label><select name="categoria" id="selectCategoria" class="form-input" onchange="updateSubcategories(this.value, 'selectSubcategoria')"><option value="">Selecione...</option>@foreach($categorias as $cat)<option value="{{ $cat->nome }}" data-tipo="{{ $cat->tipo }}">{{ $cat->nome }}</option>@endforeach</select></div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;"><label>Subcategoria</label><select name="subcategoria" id="selectSubcategoria" class="form-input"><option value="">Selecione...</option></select></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-add">Salvar</button></div>
            </form>
        </div>
    </div>

    <!-- Prediction Modal -->
    <div id="modalPrevisao" class="modal-overlay">
        <div class="modal">
            <div class="modal-header"><h3 id="previsaoModalTitle">Nova Previsão</h3><button onclick="document.getElementById('modalPrevisao').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button></div>
            <form id="previsaoForm" action="{{ route('financas.previsoes.store') }}" method="POST">
                @csrf<div id="previsaoMethodField"></div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group"><label>Tipo</label><select name="tipo" id="previsaoTipo" class="form-input" onchange="filterPrevisaoCats(this.value)"><option value="despesa">Despesa</option><option value="receita">Receita</option></select></div>
                        <div class="form-group"><label>Categoria</label><select name="categoria" id="previsaoCat" class="form-input" onchange="updateSubcategories(this.value, 'previsaoSubcat')"><option value="">Selecione...</option>@foreach($categorias as $cat)<option value="{{ $cat->nome }}" data-tipo="{{ $cat->tipo }}">{{ $cat->nome }}</option>@endforeach</select></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group"><label>Subcategoria</label><select name="subcategoria" id="previsaoSubcat" class="form-input"><option value="">Selecione...</option></select></div>
                        <div class="form-group">
                            <label>Valor Meta</label>
                            <input type="text" id="previsaoValorDisplay" class="form-input" required placeholder="0,00" inputmode="numeric">
                            <input type="hidden" name="valor_previsto" id="previsaoValor">
                        </div>
                    </div>
                    <div id="recorrenciaFields">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                            <div class="form-group"><label>Mês Inicial</label><select name="mes" class="form-input">@for($m=1; $m<=12; $m++) <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option> @endfor</select></div>
                            <div class="form-group"><label>Ano Inicial</label><select name="ano" class="form-input">@for($a=date('Y'); $a<=date('Y')+2; $a++) <option value="{{ $a }}">{{ $a }}</option> @endfor</select></div>
                        </div>
                        <div class="form-group" style="margin-top: 1rem;"><label>Repetir (meses)</label><input type="number" name="repetir_meses" class="form-input" value="1"></div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;"><label>Observação</label><input type="text" name="observacao" id="previsaoObs" class="form-input"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-add">Salvar Previsão</button></div>
            </form>
        </div>
    </div>

    <div id="modalComprasDia" class="modal-overlay">
        <div class="modal" style="max-width: 550px; border-radius: 1rem; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1rem;">
                <h3 id="comprasDiaTitle" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Despesas do Dia</h3>
                <button onclick="document.getElementById('modalComprasDia').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer; line-height: 1; color: var(--text-muted);">&times;</button>
            </div>
            <div id="comprasDiaBody" style="max-height: 350px; overflow-y: auto;">
                <!-- HTML das despesas será gerado por JS -->
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
                <button type="button" onclick="document.getElementById('modalComprasDia').style.display='none'" class="btn-action" style="background: #94a3b8; font-size: 0.8rem; padding: 0.4rem 0.8rem; border-radius: 0.375rem; border: none; color: white; cursor: pointer; font-weight: 600;">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        const categoriesData = @json($categorias);
        function filterCategoriesByTipo(tipo) {
            const sel = document.getElementById('selectCategoria');
            for (let opt of sel.options) { if (opt.value) opt.style.display = opt.getAttribute('data-tipo') === tipo ? 'block' : 'none'; }
            sel.value = ""; document.getElementById('selectSubcategoria').innerHTML = '<option value="">Selecione...</option>';
            const containerFatura = document.getElementById('containerPagarFatura');
            if (containerFatura) containerFatura.style.display = tipo === 'despesa' ? 'block' : 'none';
            if (tipo !== 'despesa') {
                document.getElementById('checkPagarFatura').checked = false;
                toggleFaturaFields(false);
            }
        }
        function updateSubcategories(catNome, targetId) {
            const target = document.getElementById(targetId);
            const cat = categoriesData.find(c => c.nome === catNome);
            target.innerHTML = '<option value="">Selecione...</option>';
            if (cat && cat.subcategorias) { cat.subcategorias.forEach(sub => { const opt = document.createElement('option'); opt.value = sub.nome; opt.innerText = sub.nome; target.appendChild(opt); }); }
        }
        function filterPrevisaoCats(tipo) {
            const sel = document.getElementById('previsaoCat');
            for(let opt of sel.options) { if(opt.value) opt.style.display = opt.getAttribute('data-tipo') === tipo ? 'block' : 'none'; }
            sel.value = ""; document.getElementById('previsaoSubcat').innerHTML = '<option value="">Selecione...</option>';
        }
        let hasAddedTransactions = false;
        function closeModal() { 
            if (hasAddedTransactions || window.needsReloadOnModalClose) {
                window.location.reload();
            } else {
                document.getElementById('modalOverlay').style.display = 'none'; 
            }
        }
        function openConsolidadoModal() { document.getElementById('modalConsolidado').style.display = 'flex'; }
        function openConsolidadoAnoModal() { document.getElementById('modalConsolidadoAno').style.display = 'flex'; }
        function openModal() { 
            document.getElementById('modalTitle').innerText = 'Novo Lançamento'; 
            document.getElementById('transactionForm').action = "{{ route('financas.store') }}";
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('inputDescricao').value = '';
            document.getElementById('inputValor').value = '';
            document.getElementById('inputValorDisplay').value = '';
            document.getElementById('inputData').value = "{{ date('Y-m-d') }}";
            document.getElementById('selectCategoria').value = '';
            document.getElementById('selectSubcategoria').innerHTML = '<option value="">Selecione...</option>';
            document.getElementById('inputTipo').value = 'despesa';
            filterCategoriesByTipo('despesa');
            
            document.getElementById('checkPagarFatura').checked = false;
            document.getElementById('containerPagarFatura').style.display = 'block';
            toggleFaturaFields(false);

            document.getElementById('modalOverlay').style.display = 'flex'; 
        }

        function toggleFaturaFields(show) {
            document.getElementById('faturaFields').style.display = show ? 'grid' : 'none';
            if (!show) {
                document.getElementById('selectCartaoFatura').value = '';
            }
        }
        
        function openPrevisaoModal() { 
            document.getElementById('previsaoModalTitle').innerText = 'Nova Previsão';
            document.getElementById('previsaoForm').action = "{{ route('financas.previsoes.store') }}";
            document.getElementById('previsaoMethodField').innerHTML = '';
            document.getElementById('recorrenciaFields').style.display = 'block';
            document.getElementById('previsaoValor').value = '';
            document.getElementById('previsaoValorDisplay').value = '';
            filterPrevisaoCats('despesa'); 
            document.getElementById('modalPrevisao').style.display = 'flex'; 
        }

        function editPrevisao(p) {
            document.getElementById('previsaoModalTitle').innerText = 'Editar Previsão';
            document.getElementById('previsaoForm').action = `/financas/previsoes/${p.id}`;
            document.getElementById('previsaoMethodField').innerHTML = '@method("PUT")';
            document.getElementById('recorrenciaFields').style.display = 'none'; // Não edita recorrência individualmente aqui
            
            document.getElementById('previsaoTipo').value = p.tipo;
            filterPrevisaoCats(p.tipo);
            document.getElementById('previsaoCat').value = p.categoria;
            updateSubcategories(p.categoria, 'previsaoSubcat');
            document.getElementById('previsaoSubcat').value = p.subcategoria || '';
            document.getElementById('previsaoValor').value = p.valor_previsto;
            const prevVal = parseFloat(p.valor_previsto) || 0;
            document.getElementById('previsaoValorDisplay').value = prevVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('previsaoObs').value = p.observacao || '';
            
            document.getElementById('modalPrevisao').style.display = 'flex';
        }

        function editTransacao(t) {
            document.getElementById('modalTitle').innerText = 'Editar Lançamento';
            document.getElementById('transactionForm').action = `/financas/${t.id}`;
            document.getElementById('methodField').innerHTML = '@method("PUT")';
            document.getElementById('inputDescricao').value = t.descricao;
            document.getElementById('inputValor').value = t.valor;
            const transVal = parseFloat(t.valor) || 0;
            document.getElementById('inputValorDisplay').value = transVal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('inputTipo').value = t.tipo;
            document.getElementById('inputData').value = t.data;
            filterCategoriesByTipo(t.tipo);
            document.getElementById('selectCategoria').value = t.categoria || '';
            updateSubcategories(t.categoria, 'selectSubcategoria');
            document.getElementById('selectSubcategoria').value = t.subcategoria || '';
            
            document.getElementById('containerPagarFatura').style.display = 'none';
            toggleFaturaFields(false);

            document.getElementById('modalOverlay').style.display = 'flex';
        }

        function toggleSection(id) {
            const content = document.getElementById(id);
            const btn = document.getElementById('btn-' + id);
            content.classList.toggle('collapsed');
            const isCollapsed = content.classList.contains('collapsed');
            btn.innerText = isCollapsed ? 'Expandir' : 'Recolher';
            localStorage.setItem('section_state_' + id, isCollapsed ? 'collapsed' : 'expanded');
        }

        function submitMonthForm(val) {
            if (!val) return;
            const parts = val.split('-');
            const url = new URL(window.location.href);
            url.searchParams.set('ano', parts[0]);
            url.searchParams.set('mes', parseInt(parts[1]));
            url.searchParams.delete('data_inicio');
            url.searchParams.delete('data_fim');
            window.location.href = url.toString();
        }

        // Charts Initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Restore collapsible states
            ['planningContent', 'transactionsContent'].forEach(id => {
                const state = localStorage.getItem('section_state_' + id);
                if (state === 'collapsed') {
                    const content = document.getElementById(id);
                    const btn = document.getElementById('btn-' + id);
                    if (content && btn) {
                        content.classList.add('collapsed');
                        btn.innerText = 'Expandir';
                    }
                }
            });

            // Chart 1: Comparison
            const ctxComp = document.getElementById('chartComparison').getContext('2d');
            new Chart(ctxComp, {
                type: 'bar',
                data: {
                    labels: ['Receitas', 'Despesas'],
                    datasets: [
                        {
                            label: 'Planejado',
                            data: [{{ $totalPrevistoReceita }}, {{ $totalPrevistoDespesa }}],
                            backgroundColor: ['rgba(16, 185, 129, 0.2)', 'rgba(239, 68, 68, 0.2)'],
                            borderColor: ['#10b981', '#ef4444'],
                            borderWidth: 1
                        },
                        {
                            label: 'Realizado',
                            data: [{{ $receitasMes }}, {{ $despesasMes }}],
                            backgroundColor: ['#10b981', '#ef4444'],
                            borderWidth: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 }, // Disable animation so we can capture it instantly
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Chart 2: Expenses Distribution
            const ctxExp = document.getElementById('chartExpenses').getContext('2d');
            const expData = @json($previsoes->where('tipo', 'despesa')->mapWithKeys(fn($p) => [$p->categoria => $p->consumo_real]));
            
            // Adicionar faturas no gráfico se houver
            @foreach($faturasPorCartao as $cartaoId => $parcelas)
                @php $cartao = $parcelas->first()->compra->cartao; @endphp
                expData["Fatura: {{ $cartao->nome }}"] = {{ $parcelas->sum('valor_parcela') }};
            @endforeach

            new Chart(ctxExp, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(expData),
                    datasets: [{
                        data: Object.values(expData),
                        backgroundColor: [
                            '#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', 
                            '#8b5cf6', '#ec4899', '#06b6d4', '#f97316', '#14b8a6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 }, // Disable animation so we can capture it instantly
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
                }
            });

            // Chart 3: Daily checking account expenses
            const chartDiarioCtx = document.getElementById('chartDiarioDespesas');
            if (chartDiarioCtx) {
                const labelsDiarios = @json($labelsDiarios ?? []);
                const datesMap = @json($datesMap ?? []);
                const despesasDiarias = @json($despesasDiariasValues ?? []);
                const despesasDetalhado = @json($despesasDetalhado ?? []);
                const mediaDiaria = {{ $mediaDiariaDespesa ?? 0 }};
                const mediaData = Array(labelsDiarios.length).fill(mediaDiaria);

                new Chart(chartDiarioCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labelsDiarios,
                        datasets: [
                            {
                                label: 'Despesas no Dia',
                                data: despesasDiarias,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2,
                                pointBackgroundColor: '#ef4444',
                                pointRadius: 3
                            },
                            {
                                label: 'Média Diária',
                                data: mediaData,
                                borderColor: '#f59e0b',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                fill: false,
                                pointRadius: 0
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onHover: (event, chartElement) => {
                            event.native.target.style.cursor = chartElement.length ? 'pointer' : 'default';
                        },
                        onClick: (event, activeElements) => {
                            if (activeElements.length > 0) {
                                const index = activeElements[0].index;
                                const dateStr = datesMap[index];
                                openDespesasDoDiaModal(dateStr);
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: { size: 10, family: 'Inter' }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(value);
                                    },
                                    font: { size: 9 }
                                }
                            },
                            x: {
                                ticks: {
                                    font: { size: 9 }
                                }
                            }
                        }
                    }
                });

                function openDespesasDoDiaModal(dateStr) {
                    const compras = despesasDetalhado.filter(c => c.data === dateStr);
                    const modalBody = document.getElementById('comprasDiaBody');
                    const modalTitle = document.getElementById('comprasDiaTitle');
                    
                    const dateParts = dateStr.split('-');
                    const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                    modalTitle.innerText = `Despesas da Conta - ${formattedDate}`;
                    
                    if (compras.length === 0) {
                        modalBody.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem 0; margin: 0; font-size: 0.875rem;">Nenhuma despesa de conta corrente registrada neste dia.</p>';
                    } else {
                        let html = `
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.825rem;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left; color: #64748b; text-transform: uppercase; font-size: 0.7rem;">
                                        <th style="padding: 0.5rem 1rem;">Descrição</th>
                                        <th style="padding: 0.5rem 1rem;">Categoria</th>
                                        <th style="padding: 0.5rem 1rem; text-align: right;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        let total = 0;
                        compras.forEach(c => {
                            total += c.valor;
                            const subcatText = c.subcategoria ? ` • ${c.subcategoria}` : '';
                            html += `
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 0.75rem 1rem; font-weight: 600; color: #1e293b;">${c.descricao}</td>
                                    <td style="padding: 0.75rem 1rem;"><span style="font-size: 0.7rem; background: #ffe4e6; color: #9f1239; padding: 2px 6px; border-radius: 4px; font-weight: 500;">${c.categoria}${subcatText}</span></td>
                                    <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #ef4444;">R$ ${c.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight: 800; font-size: 0.9rem; background: #f8fafc;">
                                        <td colspan="2" style="padding: 0.75rem 1rem; text-align: left; color: #1e293b;">Total do Dia</td>
                                        <td style="padding: 0.75rem 1rem; text-align: right; color: #ef4444;">R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;
                        modalBody.innerHTML = html;
                    }
                    
                    document.getElementById('modalComprasDia').style.display = 'flex';
                }
            }

            // Intercept transaction form submission for creation (AJAX)
            const form = document.getElementById('transactionForm');
            
            // Initialize currency masks
            applyCurrencyMask('inputValorDisplay', 'inputValor');
            applyCurrencyMask('previsaoValorDisplay', 'previsaoValor');
            
            if (form) {
                // Ctrl + Enter to submit
                form.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        e.preventDefault();
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            const submitBtn = form.querySelector('[type="submit"]');
                            if (submitBtn) submitBtn.click();
                        }
                    }
                });

                form.addEventListener('submit', function(e) {
                    const methodField = document.getElementById('methodField').innerHTML;
                    // If methodField contains PUT, it is an edit. Allow normal submit.
                    if (methodField.includes('PUT')) {
                        return;
                    }
                    
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; }).catch(() => {
                                throw new Error('Erro ao salvar transação.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showToast(data.transacao);
                            hasAddedTransactions = true;
                            notifyDataUpdated();
                            
                            // Reset description and value
                            document.getElementById('inputDescricao').value = '';
                            document.getElementById('inputValor').value = '';
                            document.getElementById('inputValorDisplay').value = '';
                            
                            // Focus back on description
                            document.getElementById('inputDescricao').focus();
                        } else {
                            alert('Erro: ' + (data.message || 'Ocorreu um erro inesperado.'));
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        const msg = error.message || 'Erro ao salvar transação. Verifique se os dados estão corretos.';
                        alert(msg);
                    });
                });
            }
        });

        function showToast(transacao) {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                `;
                document.body.appendChild(container);
            }
            
            const toast = document.createElement('div');
            const valorFormatado = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(transacao.valor);
            const badgeColor = transacao.tipo === 'receita' ? '#10b981' : '#ef4444';
            const badgeText = transacao.tipo === 'receita' ? 'Receita' : 'Despesa';
            
            toast.style.cssText = `
                background: #ffffff;
                color: #1f2937;
                padding: 1rem 1.25rem;
                border-radius: 0.75rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                border-left: 4px solid ${badgeColor};
                min-width: 280px;
                max-width: 400px;
                font-family: inherit;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            `;
            
            toast.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; font-size: 0.875rem; color: #1f2937;">Transação Salva!</span>
                    <span style="font-size: 0.7rem; font-weight: 700; color: #ffffff; background: ${badgeColor}; padding: 0.15rem 0.4rem; border-radius: 999px; text-transform: uppercase;">${badgeText}</span>
                </div>
                <div style="font-size: 0.85rem; color: #4b5563; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${transacao.descricao}">${transacao.descricao}</div>
                <div style="font-weight: 700; font-size: 0.95rem; color: ${badgeColor};">${valorFormatado}</div>
            `;
            
            container.appendChild(toast);
            
            // Trigger animation
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            }, 10);
            
            // Remove toast after 4 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 4000);
        }

        function downloadPdf() {
            const compCanvas = document.getElementById('chartComparison');
            const expCanvas = document.getElementById('chartExpenses');
            const compImage = compCanvas ? compCanvas.toDataURL('image/png') : '';
            const expImage = expCanvas ? expCanvas.toDataURL('image/png') : '';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('export.orcamento.post', ['format' => 'pdf']) }}";
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const mesInput = document.createElement('input');
            mesInput.type = 'hidden';
            mesInput.name = 'mes';
            mesInput.value = '{{ $mes }}';
            form.appendChild(mesInput);

            const anoInput = document.createElement('input');
            anoInput.type = 'hidden';
            anoInput.name = 'ano';
            anoInput.value = '{{ $ano }}';
            form.appendChild(anoInput);

            @if(isset($dataInicio) && isset($dataFim))
            const startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'data_inicio';
            startInput.value = '{{ $dataInicio }}';
            form.appendChild(startInput);

            const endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'data_fim';
            endInput.value = '{{ $dataFim }}';
            form.appendChild(endInput);
            @endif

            const chart1Input = document.createElement('input');
            chart1Input.type = 'hidden';
            chart1Input.name = 'chart1';
            chart1Input.value = compImage;
            form.appendChild(chart1Input);

            const chart2Input = document.createElement('input');
            chart2Input.type = 'hidden';
            chart2Input.name = 'chart2';
            chart2Input.value = expImage;
            form.appendChild(chart2Input);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function applyCurrencyMask(displayInputId, hiddenInputId) {
            const displayInput = document.getElementById(displayInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!displayInput || !hiddenInput) return;
            
            function formatValue(value) {
                let clean = value.replace(/\D/g, '');
                if (!clean) clean = '0';
                let val = parseFloat(clean) / 100;
                return {
                    formatted: val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                    raw: val.toFixed(2)
                };
            }
            
            displayInput.addEventListener('input', function(e) {
                const res = formatValue(this.value);
                this.value = res.formatted;
                hiddenInput.value = res.raw;
            });
            
            // On focus, put cursor at the end
            displayInput.addEventListener('focus', function() {
                setTimeout(() => {
                    this.setSelectionRange(this.value.length, this.value.length);
                }, 10);
            });
        }

        window.onclick = function(e) { 
            if(e.target.className == 'modal-overlay') {
                if (e.target.id === 'modalOverlay' && hasAddedTransactions) {
                    window.location.reload();
                } else {
                    e.target.style.display = 'none';
                }
            }
            if(!e.target.closest('.fab-container')) {
                const fabMenu = document.getElementById('fabMenu');
                if(fabMenu) fabMenu.classList.remove('active');
            }
        }
    </script>

    <div class="fab-container">
        <button class="fab-main" onclick="toggleFab()" title="Novo Lançamento">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </button>
        <div class="fab-menu" id="fabMenu">
            <div class="fab-item" onclick="openModal(); toggleFab()">
                <span>Nova Transação</span>
                <button class="fab-item-icon bg-transacao">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </button>
            </div>
            <div class="fab-item" onclick="openPrevisaoModal(); toggleFab()">
                <span>Nova Previsão</span>
                <button class="fab-item-icon bg-previsao">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ── Painel Telegram Bot ─────────────────────────────────────────────── --}}
    @php
        $telegramLogs = \App\Models\WhatsappLog::where('numero', 'like', 'tg:%')->latest()->limit(5)->get();
        $telegramOk   = config('telegram.bot_token') && config('telegram.allowed_chat_id');
    @endphp
    <div style="
        position: fixed; bottom: 2rem; left: 2rem; z-index: 900;
        background: white; border-radius: 1rem; border: 1px solid #e5e7eb;
        box-shadow: 0 4px 15px rgba(0,0,0,0.12); width: 280px;
        font-family: inherit; font-size: 0.78rem; overflow: hidden;
    " id="telegramBotPanel">
        <div style="
            background: #0088cc; color: white; padding: 0.65rem 1rem;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer;
        " onclick="toggleTelegramPanel()">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.82rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="white"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-1-.65-.35-1 .22-1.6 1.48-1.52 2.72-2.92 2.72-2.92.08-.07.03-.22-.1-.23-.1-.01-.32.07-.32.07l-3.32 2.1c-.26.17-.5.2-.67.19-.24-.01-.7-.14-1.04-.25-.42-.14-.76-.22-.73-.46.02-.13.2-.26.54-.4 2.11-.92 3.52-1.53 4.22-1.83 2-.85 2.42-1 .27-1.03h.02c.48.01 1.07.13 1.07.7z"/></svg>
                Telegram Bot
            </div>
            <div style="display: flex; align-items: center; gap: 0.4rem;">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $telegramOk ? '#86efac' : '#fca5a5' }};display:inline-block;"></span>
                <span style="font-size:0.7rem;opacity:0.9;">{{ $telegramOk ? 'Ativo' : 'Não configurado' }}</span>
                <svg id="telegramChevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>
        <div id="telegramBody" style="padding:0.75rem 1rem;">
            @if(!$telegramOk)
                <p style="color:#6b7280;font-size:0.75rem;margin:0;line-height:1.5;">
                    Configure <code>TELEGRAM_BOT_TOKEN</code> e <code>TELEGRAM_ALLOWED_CHAT_ID</code> no <code>.env</code>.
                </p>
            @elseif($telegramLogs->isEmpty())
                <p style="color:#6b7280;margin:0;padding:0.5rem 0;">Nenhuma mensagem recebida ainda.</p>
            @else
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-bottom: 0.25rem; border-bottom: 1px solid #f1f5f9;">
                    <span style="font-weight: 700; color: var(--text-muted); font-size: 0.72rem;">LOG DE MENSAGENS</span>
                    <button onclick="clearTelegramLogs()" style="background: none; border: none; color: #ef4444; font-size: 0.68rem; font-weight: 600; cursor: pointer; padding: 0;">Limpar Tudo</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:0.5rem; max-height: 220px; overflow-y: auto;">
                    @foreach($telegramLogs as $log)
                        <div style="
                            padding:0.4rem 0.6rem;border-radius:0.5rem;
                            border-left:3px solid {{ $log->status==='ok' ? '#10b981' : ($log->status==='erro' ? '#ef4444' : '#f59e0b') }};
                            background:{{ $log->status==='ok' ? '#f0fdf4' : ($log->status==='erro' ? '#fef2f2' : '#fffbeb') }};
                            position: relative;
                            transition: opacity 0.3s ease;
                        " id="log-item-{{ $log->id }}">
                            <button onclick="deleteTelegramLog({{ $log->id }})" style="
                                position: absolute; top: 2px; right: 4px;
                                background: none; border: none; color: #9ca3af;
                                font-size: 0.75rem; cursor: pointer; padding: 2px;
                            " title="Excluir mensagem">×</button>
                            <div style="font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; padding-right: 12px;" title="{{ $log->mensagem_original }}">
                                {{ Str::limit($log->mensagem_original, 30) }}
                            </div>
                            <div style="color:#6b7280;font-size:0.7rem;margin-top:1px;">
                                {{ $log->created_at->diffForHumans() }}
                                @if($log->transacao_id) · <span style="color:#10b981;">✓ Lançado</span>
                                @elseif($log->status==='erro') · <span style="color:#ef4444;">Erro</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div style="margin-top:0.75rem;padding-top:0.5rem;border-top:1px solid #f1f5f9;color:#9ca3af;font-size:0.68rem;line-height:1.5;">
                💬 Envie mensagens livres para a IA:<br>
                💡 <code>despesa de 15 reais no cartao visa</code><br>
                📊 <code>saldo</code> · <code>listar</code> · <code>ajuda</code>
            </div>
        </div>
    </div>

    <script>
        function toggleFab() {
            document.getElementById('fabMenu').classList.toggle('active');
        }
        function toggleTelegramPanel() {
            const body = document.getElementById('telegramBody');
            const chevron = document.getElementById('telegramChevron');
            const collapsed = body.style.display === 'none';
            body.style.display = collapsed ? 'block' : 'none';
            chevron.style.transform = collapsed ? '' : 'rotate(180deg)';
        }

        function deleteTelegramLog(logId) {
            if(!confirm('Excluir esta mensagem do log?')) return;
            
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch(`/financas/telegram-logs/${logId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const item = document.getElementById(`log-item-${logId}`);
                    if(item) {
                        item.style.opacity = '0';
                        setTimeout(() => item.remove(), 300);
                    }
                }
            })
            .catch(err => alert('Erro ao excluir log.'));
        }

        function clearTelegramLogs() {
            if(!confirm('Deseja limpar todas as mensagens do log?')) return;
            
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch('/financas/telegram-logs', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    window.location.reload();
                }
            })
            .catch(err => alert('Erro ao limpar logs.'));
        }

        // ── Funções para tabelas interativas (Busca e Ordenação super rápidas) ─────
        function makeTableInteractive(table, hasSubGroups = false) {
            if (!table) return;

            // Adicionar campo de busca
            const container = table.parentElement;
            const searchWrapper = document.createElement('div');
            searchWrapper.style.margin = '0 0 1rem 0';
            searchWrapper.style.display = 'flex';
            searchWrapper.style.justifyContent = 'flex-end';

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = '🔎 Pesquisar lançamentos...';
            searchInput.className = 'input-month';
            searchInput.style.width = '100%';
            searchInput.style.maxWidth = '300px';
            searchInput.style.borderRadius = '0.5rem';
            searchInput.style.fontSize = '0.8rem';
            searchInput.style.padding = '0.4rem 0.75rem';

            searchWrapper.appendChild(searchInput);
            container.insertBefore(searchWrapper, table);

            searchInput.addEventListener('input', function() {
                const query = searchInput.value.toLowerCase().trim();
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr:not(.subheader-row)');

                rows.forEach(row => {
                    const cells = Array.from(row.children);
                    const matches = cells.some(cell => cell.textContent.toLowerCase().includes(query));
                    row.style.display = matches ? '' : 'none';
                });

                if (hasSubGroups) {
                    const allTrs = Array.from(tbody.children);
                    let lastSubheader = null;
                    let subheaderHasVisibleRows = false;

                    allTrs.forEach(tr => {
                        if (tr.classList.contains('subheader-row')) {
                            if (lastSubheader) {
                                lastSubheader.style.display = subheaderHasVisibleRows ? '' : 'none';
                            }
                            lastSubheader = tr;
                            subheaderHasVisibleRows = false;
                        } else {
                            if (tr.style.display !== 'none') {
                                subheaderHasVisibleRows = true;
                            }
                        }
                    });
                    if (lastSubheader) {
                        lastSubheader.style.display = subheaderHasVisibleRows ? '' : 'none';
                    }
                }
            });

            // Adicionar ordenação
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                if (header.textContent.toLowerCase().includes('ações')) return;

                header.style.cursor = 'pointer';
                header.style.position = 'relative';
                header.style.userSelect = 'none';
                header.title = 'Clique para ordenar';

                let direction = 1;

                header.addEventListener('click', function() {
                    headers.forEach(h => {
                        if (h !== header) {
                            const arrow = h.querySelector('.sort-arrow');
                            if (arrow) arrow.remove();
                        }
                    });

                    let arrow = header.querySelector('.sort-arrow');
                    if (!arrow) {
                        arrow = document.createElement('span');
                        arrow.className = 'sort-arrow';
                        arrow.style.marginLeft = '5px';
                        arrow.style.fontSize = '0.75rem';
                        header.appendChild(arrow);
                    }
                    arrow.innerHTML = direction === 1 ? ' ▲' : ' ▼';

                    sortTable(table, index, direction, hasSubGroups);
                    direction = -direction;
                });
            });
        }

        function sortTable(table, colIndex, direction, hasSubGroups = false) {
            const tbody = table.querySelector('tbody');
            const trs = Array.from(tbody.children);

            if (!hasSubGroups) {
                const rows = trs.filter(tr => !tr.classList.contains('subheader-row'));
                rows.sort((a, b) => compareCells(a.children[colIndex], b.children[colIndex], direction));
                rows.forEach(row => tbody.appendChild(row));
            } else {
                let currentGroup = [];
                const groups = [];
                let headerRow = null;

                trs.forEach(tr => {
                    if (tr.classList.contains('subheader-row')) {
                        if (headerRow) {
                            groups.push({ header: headerRow, rows: currentGroup });
                        }
                        headerRow = tr;
                        currentGroup = [];
                    } else {
                        currentGroup.push(tr);
                    }
                });
                if (headerRow) {
                    groups.push({ header: headerRow, rows: currentGroup });
                }

                groups.forEach(group => {
                    group.rows.sort((a, b) => compareCells(a.children[colIndex], b.children[colIndex], direction));
                });

                tbody.innerHTML = '';
                groups.forEach(group => {
                    tbody.appendChild(group.header);
                    group.rows.forEach(row => tbody.appendChild(row));
                });
            }
        }

        function compareCells(cellA, cellB, direction) {
            let valA = cellA ? cellA.textContent.trim() : '';
            let valB = cellB ? cellB.textContent.trim() : '';

            // Valor Monetário
            if (valA.includes('R$') || valB.includes('R$')) {
                const cleanNum = (str) => {
                    let s = str.replace(/[R$\s]/g, '').replace(/\./g, '').replace(',', '.');
                    s = s.replace(/[^\d.-]/g, '');
                    return parseFloat(s) || 0;
                };
                return (cleanNum(valA) - cleanNum(valB)) * direction;
            }

            // Data
            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if (dateRegex.test(valA) && dateRegex.test(valB)) {
                const parseDate = (str) => {
                    const parts = str.match(dateRegex);
                    return new Date(parts[3], parts[2] - 1, parts[1]).getTime();
                };
                return (parseDate(valA) - parseDate(valB)) * direction;
            }

            return valA.localeCompare(valB, 'pt-BR', { numeric: true, sensitivity: 'base' }) * direction;
        }

        // Key de armazenamento local para persisitir os lançamentos selecionados (destacados em verde)
        const STORAGE_KEY_TRANSACTIONS = 'financeiro_selected_transactions';
        const dataSyncChannel = (typeof BroadcastChannel !== 'undefined') ? new BroadcastChannel('financeiro_sync_channel') : null;

        function isAnyModalOpen() {
            return Array.from(document.querySelectorAll('.modal-overlay')).some(overlay => {
                const style = window.getComputedStyle(overlay);
                return style.display !== 'none' && style.visibility !== 'hidden';
            });
        }

        function notifyDataUpdated() {
            localStorage.setItem('financeiro_data_updated', Date.now().toString());
            if (dataSyncChannel) {
                try {
                    dataSyncChannel.postMessage({ action: 'DATA_UPDATED' });
                } catch (err) {}
            }
        }

        function handleDataUpdatedSignal() {
            const isEditingInput = document.activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName) && document.activeElement.value !== '';
            if (!isAnyModalOpen() && !isEditingInput) {
                window.location.reload();
            } else {
                window.needsReloadOnModalClose = true;
            }
        }

        if (dataSyncChannel) {
            dataSyncChannel.onmessage = function(e) {
                if (e.data && e.data.action === 'DATA_UPDATED') {
                    handleDataUpdatedSignal();
                }
            };
        }

        function restoreSelectedTransactions(tbody) {
            if (!tbody) return;
            try {
                const saved = JSON.parse(localStorage.getItem(STORAGE_KEY_TRANSACTIONS) || '[]');
                const set = new Set(saved.map(String));
                tbody.querySelectorAll('tr[data-id]').forEach(row => {
                    const id = row.getAttribute('data-id');
                    if (set.has(id)) {
                        row.classList.add('selected-audit');
                    } else {
                        row.classList.remove('selected-audit');
                    }
                });
            } catch (err) {
                console.error('Erro ao restaurar lançamentos selecionados:', err);
            }
        }

        function updateSelectedTransactionsStorage(tbody) {
            if (!tbody) return;
            const selectedIds = Array.from(tbody.querySelectorAll('tr.selected-audit[data-id]'))
                .map(r => r.getAttribute('data-id'));
            localStorage.setItem(STORAGE_KEY_TRANSACTIONS, JSON.stringify(selectedIds));
        }

        // Desativar restauração de scroll automática do navegador para termos controle total
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const SCROLL_KEY = 'financeiro_scroll_pos_' + window.location.pathname;

        function saveScrollPosition() {
            if (window.scrollY > 0) {
                sessionStorage.setItem(SCROLL_KEY, window.scrollY.toString());
            }
        }

        window.addEventListener('scroll', function() {
            if (window.scrollY > 0) {
                sessionStorage.setItem(SCROLL_KEY, window.scrollY.toString());
            }
        }, { passive: true });

        window.addEventListener('beforeunload', saveScrollPosition);

        function restoreScrollPosition() {
            const saved = sessionStorage.getItem(SCROLL_KEY);
            if (saved !== null && parseInt(saved, 10) > 0) {
                const targetPos = parseInt(saved, 10);
                
                const doScroll = () => {
                    window.scrollTo({
                        top: targetPos,
                        left: 0,
                        behavior: 'instant'
                    });
                };

                doScroll();
                requestAnimationFrame(doScroll);
                setTimeout(doScroll, 50);
                setTimeout(doScroll, 200);
                setTimeout(doScroll, 500);
            }
        }

        window.addEventListener('load', restoreScrollPosition);

        // Inicializar tabela
        document.addEventListener('DOMContentLoaded', function() {
            restoreScrollPosition();
            const table = document.getElementById('transactionsTable');
            makeTableInteractive(table);

            if (table) {
                const tbody = table.querySelector('tbody');
                if (tbody) {
                    restoreSelectedTransactions(tbody);

                    tbody.addEventListener('click', function(e) {
                        // Não seleciona se clicar em botões, links, ícones, SVGs ou formulários de ações
                        if (
                            e.target.closest('.action-btn-small') || 
                            e.target.closest('form') || 
                            e.target.closest('a') || 
                            e.target.closest('button')
                        ) {
                            return;
                        }
                        const row = e.target.closest('tr');
                        if (row && row.parentElement.tagName.toLowerCase() === 'tbody') {
                            row.classList.toggle('selected-audit');
                            updateSelectedTransactionsStorage(tbody);
                        }
                    });

                    // Sincronização em tempo real entre abas duplicadas ou janelas abertas
                    window.addEventListener('storage', function(e) {
                        if (e.key === STORAGE_KEY_TRANSACTIONS) {
                            restoreSelectedTransactions(tbody);
                        }
                        if (e.key === 'financeiro_data_updated') {
                            handleDataUpdatedSignal();
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
