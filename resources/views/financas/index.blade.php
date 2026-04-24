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

        .action-icon {
            background: none; border: none; cursor: pointer; font-weight: 600; font-size: 0.8rem; padding: 4px 8px; border-radius: 4px;
        }
        .edit-icon { color: var(--primary); }
        .edit-icon:hover { background: #eef2ff; }
        .delete-icon { color: var(--danger); font-size: 1.2rem; }
        .delete-icon:hover { background: #fef2f2; }
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
                    <a href="{{ route('export.orcamento', ['format' => 'pdf', 'mes' => $mes, 'ano' => $ano]) }}" class="btn-add" style="background: #ef4444; font-size: 0.75rem;">PDF</a>
                    <a href="{{ route('export.orcamento', ['format' => 'csv', 'mes' => $mes, 'ano' => $ano]) }}" class="btn-add" style="background: #10b981; font-size: 0.75rem;">CSV</a>
                    <a href="{{ route('export.orcamento', ['format' => 'sql', 'mes' => $mes, 'ano' => $ano]) }}" class="btn-add" style="background: #3b82f6; font-size: 0.75rem;">DB (SQL)</a>
                </div>
            </header>

            <div class="content-body">
                @php
                    $prevMonth = $mes == 1 ? 12 : $mes - 1;
                    $prevYear = $mes == 1 ? $ano - 1 : $ano;
                    $nextMonth = $mes == 12 ? 1 : $mes + 1;
                    $nextYear = $mes == 12 ? $ano + 1 : $ano;
                @endphp

                <div class="period-nav">
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
                    
                    <a href="{{ route('financas.index', ['mes' => date('n'), 'ano' => date('Y')]) }}" class="btn-today">Ir para Hoje</a>
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
                            <div style="text-align: right; display: flex; gap: 5px;">
                                <button onclick="editPrevisao({{ json_encode($p) }})" class="action-icon edit-icon">Editar</button>
                                <form action="{{ route('financas.previsoes.destroy', $p->id) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-icon delete-icon" onclick="return confirm('Excluir?')">&times;</button>
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
                                <a href="{{ route('cartoes.index') }}" class="action-icon edit-icon" style="text-decoration: none;">Ver Detalhes</a>
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
                    <table>
                        <thead>
                            <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th style="text-align: right;">Ações</th></tr>
                        </thead>
                        <tbody>
                            @foreach($transacoes as $t)
                                <tr>
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
                                        <button onclick="editTransacao({{ json_encode($t) }})" class="action-icon edit-icon">Editar</button>
                                        <form action="{{ route('financas.destroy', $t->id) }}" method="POST" style="display:inline;">@csrf @method('DELETE')<button type="submit" class="action-icon delete-icon">&times;</button></form>
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
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal">
            <div class="modal-header"><h3 id="modalTitle">Lançamento</h3><button onclick="closeModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button></div>
            <form id="transactionForm" method="POST" action="{{ route('financas.store') }}">
                @csrf<div id="methodField"></div>
                <div class="modal-body">
                    <div class="form-group"><label>Descrição</label><input type="text" name="descricao" id="inputDescricao" class="form-input" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group"><label>Valor (R$)</label><input type="number" step="0.01" name="valor" id="inputValor" class="form-input" required></div>
                        <div class="form-group"><label>Tipo</label><select name="tipo" id="inputTipo" class="form-input" onchange="filterCategoriesByTipo(this.value)"><option value="receita">Receita</option><option value="despesa" selected>Despesa</option></select></div>
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
                        <div class="form-group"><label>Valor Meta</label><input type="number" step="0.01" name="valor_previsto" id="previsaoValor" class="form-input" required></div>
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

    <script>
        const categoriesData = @json($categorias);
        function filterCategoriesByTipo(tipo) {
            const sel = document.getElementById('selectCategoria');
            for (let opt of sel.options) { if (opt.value) opt.style.display = opt.getAttribute('data-tipo') === tipo ? 'block' : 'none'; }
            sel.value = ""; document.getElementById('selectSubcategoria').innerHTML = '<option value="">Selecione...</option>';
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
        function closeModal() { document.getElementById('modalOverlay').style.display = 'none'; }
        function openModal() { document.getElementById('modalTitle').innerText = 'Novo Lançamento'; document.getElementById('modalOverlay').style.display = 'flex'; }
        
        function openPrevisaoModal() { 
            document.getElementById('previsaoModalTitle').innerText = 'Nova Previsão';
            document.getElementById('previsaoForm').action = "{{ route('financas.previsoes.store') }}";
            document.getElementById('previsaoMethodField').innerHTML = '';
            document.getElementById('recorrenciaFields').style.display = 'block';
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
            document.getElementById('previsaoObs').value = p.observacao || '';
            
            document.getElementById('modalPrevisao').style.display = 'flex';
        }

        function editTransacao(t) {
            document.getElementById('modalTitle').innerText = 'Editar Lançamento';
            document.getElementById('transactionForm').action = `/financas/${t.id}`;
            document.getElementById('methodField').innerHTML = '@method("PUT")';
            document.getElementById('inputDescricao').value = t.descricao;
            document.getElementById('inputValor').value = t.valor;
            document.getElementById('inputTipo').value = t.tipo;
            document.getElementById('inputData').value = t.data;
            filterCategoriesByTipo(t.tipo);
            document.getElementById('selectCategoria').value = t.categoria || '';
            updateSubcategories(t.categoria, 'selectSubcategoria');
            document.getElementById('selectSubcategoria').value = t.subcategoria || '';
            document.getElementById('modalOverlay').style.display = 'flex';
        }

        function toggleSection(id) {
            const content = document.getElementById(id);
            const btn = document.getElementById('btn-' + id);
            content.classList.toggle('collapsed');
            btn.innerText = content.classList.contains('collapsed') ? 'Expandir' : 'Recolher';
        }

        function submitMonthForm(val) {
            if (!val) return;
            const parts = val.split('-');
            const url = new URL(window.location.href);
            url.searchParams.set('ano', parts[0]);
            url.searchParams.set('mes', parseInt(parts[1]));
            window.location.href = url.toString();
        }

        // Charts Initialization
        document.addEventListener('DOMContentLoaded', function() {
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
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
                }
            });
        });

        window.onclick = function(e) { if(e.target.className == 'modal-overlay') e.target.style.display = 'none'; }
    </script>
</body>
</html>
