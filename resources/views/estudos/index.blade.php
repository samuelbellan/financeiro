<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Estudos | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-card: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        /* Tab Navigation Bar */
        .study-tabs-nav {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
            padding-bottom: 0px;
        }
        .study-tab-btn {
            padding: 0.65rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            margin-bottom: -2px;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .study-tab-btn:hover {
            color: var(--primary);
            background: rgba(139, 92, 246, 0.04);
        }
        .study-tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: rgba(139, 92, 246, 0.08);
        }

        /* Preset Chips for Flexible Time Entry */
        .preset-chip {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.725rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .preset-chip:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .preset-chip.danger:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.25rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            border: 1px solid var(--border);
            border-left-width: 4px;
        }
        .stat-card.progresso { border-left-color: var(--primary); }
        .stat-card.media { border-left-color: #3b82f6; }
        .stat-card.previsao { border-left-color: var(--success); }
        .stat-card.restante { border-left-color: var(--warning); }

        .stat-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }
        .stat-value { font-size: 1.35rem; font-weight: 700; color: var(--text-main); }
        .stat-sub { font-size: 0.75rem; color: var(--text-muted); }

        .progress-container { height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin-top: 6px; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #8b5cf6, #3b82f6); border-radius: 999px; }

        /* Period Filter Bar */
        .period-filter-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 0.5rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
        }
        .period-filter-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .period-pill {
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            background: white;
            border: 1px solid var(--border);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .period-pill:hover {
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-1px);
        }
        .period-pill.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.25);
        }

        /* Simulator Card */
        .simulator-card {
            background: linear-gradient(135deg, #1e1b4b 0%, #311042 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .simulator-header {
            margin-bottom: 1.5rem;
        }
        .simulator-header h2 { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .simulator-header p { font-size: 0.875rem; color: rgba(255, 255, 255, 0.7); }

        .simulator-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 900px) {
            .sim-grid { grid-template-columns: 1fr !important; gap: 1.5rem !important; }
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .control-label {
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            color: rgba(255, 255, 255, 0.9);
        }
        .control-label span.value {
            color: #a78bfa;
            font-family: monospace;
            font-size: 1rem;
            font-weight: 700;
        }

        .slider {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            outline: none;
            transition: background 0.2s;
        }
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #a78bfa;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0,0,0,0.3);
            transition: transform 0.1s;
        }
        .slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }

        .simulator-result {
            background: rgba(255, 255, 255, 0.07);
            border-radius: 0.75rem;
            padding: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .result-text {
            font-size: 1rem;
            line-height: 1.6;
        }
        .result-highlight {
            color: #f59e0b;
            font-weight: 700;
        }
        .result-date {
            color: #10b981;
            font-weight: 700;
            text-decoration: underline;
        }

        /* Double charts layout */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
        }
        .chart-container {
            height: 280px;
            position: relative;
        }

        /* Consistency Heatmap style GitHub */
        .heatmap-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }
        .heatmap-wrapper {
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        .heatmap-container {
            display: grid;
            grid-template-rows: repeat(7, 12px);
            grid-auto-flow: column;
            grid-auto-columns: 12px;
            gap: 3px;
            width: max-content;
            margin-top: 1rem;
        }
        .heatmap-day {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            background-color: #f1f5f9;
            cursor: pointer;
            position: relative;
        }
        .heatmap-day:hover {
            transform: scale(1.15);
            box-shadow: 0 0 4px rgba(0,0,0,0.15);
        }

        /* Heatmap colors */
        .level-0 { background-color: #e2e8f0; }
        .level-1 { background-color: #ddd6fe; } /* violet light */
        .level-2 { background-color: #c084fc; } /* violet medium */
        .level-3 { background-color: #a855f7; } /* violet dark */
        .level-4 { background-color: #6d28d9; } /* violet extra dark */

        .heatmap-legend {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
        }
        .legend-box {
            width: 10px;
            height: 10px;
            border-radius: 2px;
        }

        /* Forms and tables layout */
        .logs-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .logs-section { grid-template-columns: 1fr; }
        }

        .form-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .table-container {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1.25rem; background: #f9fafb; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
        tr:last-child td { border-bottom: none; }

        .btn-add {
            background: var(--primary);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn-add:hover { background: var(--primary-hover); }
        
        .btn-secondary {
            background: #f3f4f6;
            color: var(--text-main);
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-secondary:hover { background: #e5e7eb; }

        .form-input {
            width: 100%;
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: inherit;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }

        .goal-tag {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            background: #eedffc;
            color: var(--primary);
            display: inline-block;
        }
        .goal-tag.avulso {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Tooltip style for heatmap */
        [data-tooltip] { position: relative; }
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 10;
            pointer-events: none;
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center;
            z-index: 1000;
        }
        .modal {
            background: white; border-radius: 1rem; width: 100%; max-width: 550px;
            max-height: 90vh; overflow-y: auto; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .modal-header h3 { font-size: 1.125rem; font-weight: 700; }
        .modal-body { margin-bottom: 1.5rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Financeiro</h2>
                <button type="button" class="sidebar-toggle-btn js-toggle-sidebar" title="Ocultar barra lateral (Ctrl + \)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <path d="M15 9l-3 3 3 3"></path>
                    </svg>
                </button>
            </div>
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
                    <a href="{{ route('estudos.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>

                    <a href="{{ route('photos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Galeria Fotos</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header-container" style="margin-bottom: 1.5rem;">
                <div class="header-left">
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Abrir menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <div>
                        <div class="header-breadcrumb">
                            <a href="{{ route('home') }}">Home</a>
                            <span>/</span>
                            <span>Sistema 2</span>
                            <span>/</span>
                            <span>Horas de Estudo</span>
                        </div>
                        <h1>Calculadora e Gestão de Estudos</h1>
                        <p>Acompanhe seu ritmo, simule prazos e gerencie sua consistência com flexibilidade</p>
                    </div>
                </div>

                <div class="goal-select-container" style="display: flex; gap: 0.75rem; align-items: center;">
                    <select onchange="window.location.href='/estudos?goal=' + this.value" class="goal-select" style="padding: 0.6rem 1rem; border-radius: 0.5rem; border: 1px solid var(--border); font-weight: 600; background: white; color: var(--text-main); font-size: 0.875rem;">
                        <option value="geral" {{ $viewMode == 'geral' ? 'selected' : '' }}>📊 Visão Geral (Todas as Metas)</option>
                        <option value="avulso" {{ $viewMode == 'avulso' ? 'selected' : '' }}>📝 Estudos Avulsos (Sem Meta)</option>
                        @if($goals->isNotEmpty())
                            <optgroup label="Suas Metas de Estudo">
                                @foreach($goals as $g)
                                    <option value="{{ $g->id }}" {{ $viewMode == 'goal' && $activeGoal && $activeGoal->id == $g->id ? 'selected' : '' }}>
                                        🎯 {{ $g->nome }} {{ $g->horas_meta ? '('.round($g->horas_meta).'h)' : '(Sem limite)' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>

                    <button onclick="openGoalModal()" class="btn-add">+ Nova Meta</button>
                </div>
            </header>

            <div class="content-body">
                @if(session('success'))
                    <div style="background: #dcfce7; border: 1px solid #10b981; color: #15803d; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; font-weight: 500;">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Barra de Navegação por Abas -->
                <div class="study-tabs-nav">
                    <button type="button" class="study-tab-btn active" id="btnTabVisaoGeral" onclick="switchStudyTab('visaoGeral')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        📊 Painel & Histórico
                    </button>
                    <button type="button" class="study-tab-btn" id="btnTabSimulador" onclick="switchStudyTab('simulador')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        🔮 Simulador & Cronograma Interativo
                    </button>
                </div>

                <!-- ABA 1: Visão Geral & Histórico -->
                <div id="tabContentVisaoGeral" class="study-tab-content">
                    <!-- Barra de Filtro de Período -->
                    <div class="period-filter-bar">
                        <span class="period-filter-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Filtro de Período:
                        </span>
                        <a href="{{ route('estudos.index', ['periodo' => 'all']) }}" class="period-pill {{ $stats['periodo'] == 'all' ? 'active' : '' }}">Todo o Período</a>
                        <a href="{{ route('estudos.index', ['periodo' => 'month']) }}" class="period-pill {{ $stats['periodo'] == 'month' ? 'active' : '' }}">Este Mês</a>
                        <a href="{{ route('estudos.index', ['periodo' => '30days']) }}" class="period-pill {{ $stats['periodo'] == '30days' ? 'active' : '' }}">Últimos 30 dias</a>
                        <a href="{{ route('estudos.index', ['periodo' => 'year']) }}" class="period-pill {{ $stats['periodo'] == 'year' ? 'active' : '' }}">Este Ano</a>
                    </div>

                    <!-- Dashboard Stat Cards -->
                    <div class="dashboard-grid">
                        <div class="stat-card progresso">
                            <span class="stat-label">
                                @if($viewMode == 'geral') Total Estudado (Geral) @elseif($viewMode == 'avulso') Estudos Avulsos @else Progresso da Meta @endif
                            </span>
                            <span class="stat-value">
                                {{ number_format($stats['total_estudado'], 1, ',', '.') }}h 
                                @if($stats['meta']) / {{ number_format($stats['meta'], 0, ',', '.') }}h @endif
                            </span>
                            @if($stats['meta'])
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: {{ $stats['progresso'] }}%"></div>
                                </div>
                                <span class="stat-sub" style="margin-top: 4px;">{{ $stats['progresso'] }}% concluído</span>
                            @else
                                <span class="stat-sub" style="margin-top: 4px; color: var(--primary); font-weight: 600;">Modo Livre (Sem meta fixa)</span>
                            @endif
                        </div>

                        <div class="stat-card media">
                            <span class="stat-label">Ritmo Diário Real</span>
                            <span class="stat-value">{{ number_format($stats['media_real'], 1, ',', '.') }}h / dia</span>
                            <span class="stat-sub">Média nos {{ $stats['dias_estudados'] }} dias ativos</span>
                            <span class="stat-sub" style="font-size: 0.65rem; color: #9ca3af;">Média geral (com folgas): {{ number_format($stats['media_geral'], 1, ',', '.') }}h/dia</span>
                        </div>

                        <div class="stat-card previsao">
                            <span class="stat-label">Consistência & Sequência</span>
                            <span class="stat-value" style="font-size: 1.2rem; color: #10b981;">🔥 {{ $stats['streak'] }} {{ $stats['streak'] == 1 ? 'dia' : 'dias' }} seguidos</span>
                            <span class="stat-sub">Dias estudados: {{ $stats['dias_estudados'] }} de {{ $stats['dias_desde_inicio'] }} dias totais</span>
                            @if($stats['data_projetada_real'] != 'N/A')
                                <span class="stat-sub" style="font-size: 0.65rem; color: #9ca3af;">Término estimado: {{ $stats['data_projetada_real'] }}</span>
                            @endif
                        </div>

                        <div class="stat-card restante">
                            <span class="stat-label">Status & Projeções</span>
                            <span class="stat-value" style="font-size: 1.15rem;">
                                @if($stats['horas_restantes'] > 0)
                                    ~{{ $stats['dias_restantes_real'] ?: 'N/A' }} dias rest.
                                @else
                                    {{ number_format($stats['total_estudado'], 1, ',', '.') }}h acumuladas
                                @endif
                            </span>
                            @if($activeGoal && $activeGoal->data_limite)
                                <span class="stat-sub">
                                    Prazo final: {{ \Carbon\Carbon::parse($activeGoal->data_limite)->format('d/m/Y') }}
                                    @if(isset($stats['horas_necessarias_dia']))
                                        <br><span style="font-weight: 600; color: var(--primary);">Precisa de {{ $stats['horas_necessarias_dia'] }}h/dia</span>
                                    @endif
                                </span>
                            @else
                                <span class="stat-sub">Livre para manipular metas no simulador</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ABA 2: Simulador & Calculadora Interativa -->
                <div id="tabContentSimulador" class="study-tab-content" style="display: none;">
                    <!-- Simulador Interativo ("What-If") -->
                    <div class="simulator-card">
                        <div class="simulator-header">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                                Simulador e Cronograma de Estudos Interativo
                            </h2>
                            <p>Manipule os parâmetros de data, metas e a distribuição dos dias da semana em tempo real, com ou sem meta definida.</p>
                        </div>

                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem;" class="sim-grid">
                            <!-- Coluna Esquerda: Sliders e Datas -->
                            <div>
                                <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 1rem; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.05em;">1. Parâmetros & Simulação</h3>
                                
                                <div class="simulator-controls" style="grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="control-group">
                                        <label class="control-label" for="sliderGoalHours">
                                            <span>Meta Total de Horas (Simulada)</span>
                                            <span class="value" id="valGoalHours">{{ round($stats['meta'] ?: 100) }}h</span>
                                        </label>
                                        <input type="range" id="sliderGoalHours" class="slider" min="10" max="3000" step="10" value="{{ round($stats['meta'] ?: 100) }}">
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="sliderStudiedHours">
                                            <span>Horas Já Estudadas</span>
                                            <span class="value" id="valStudiedHours">{{ number_format($stats['total_estudado'], 1) }}h</span>
                                        </label>
                                        <input type="range" id="sliderStudiedHours" class="slider" min="0" max="3000" step="5" value="{{ $stats['total_estudado'] }}">
                                    </div>

                                    <div class="control-group">
                                        <label class="control-label" for="sliderWeeklyPercent">
                                            <span>Ajustar Carga Semanal (+/- %)</span>
                                            <span class="value" id="valWeeklyPercent">0% (Sem alteração)</span>
                                        </label>
                                        <input type="range" id="sliderWeeklyPercent" class="slider" min="-100" max="200" step="5" value="0">
                                    </div>
                                </div>

                                <h3 style="font-size: 0.95rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 1rem; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.05em;">2. Intervalo do Cronograma</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                    <div class="control-group">
                                        <label class="control-label" style="font-size: 0.75rem; color: rgba(255,255,255,0.8);">Estudar A Partir De</label>
                                        <input type="date" id="simStartDate" class="form-input" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;" value="{{ date('Y-m-d') }}" onchange="runSimulation()">
                                    </div>
                                    <div class="control-group">
                                        <label class="control-label" style="font-size: 0.75rem; color: rgba(255,255,255,0.8);">Até Quando (Prazo Alvo)</label>
                                        <input type="date" id="simTargetDate" class="form-input" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;" value="{{ ($activeGoal && $activeGoal->data_limite) ? $activeGoal->data_limite->format('Y-m-d') : date('Y-m-d', strtotime('+3 months')) }}" onchange="runSimulation()">
                                    </div>
                                </div>

                                <div class="simulator-result" style="margin-top: 1.5rem;">
                                    <p class="result-text">
                                        Com <span class="result-highlight" id="simStudiedResult">X horas</span> já estudadas, estudando mais <span class="result-highlight" id="simDailyResult">Y horas</span> por dia a partir de hoje, você precisará de 
                                        <span class="result-highlight" id="simDaysResult">Z dias</span> de estudo para atingir o objetivo de 
                                        <span class="result-highlight" id="simGoalResult">W horas</span>.
                                        <br>
                                        Data Estimada de Término: <span class="result-date" id="simDateResult">dd/mm/aaaa</span> 
                                        (<span class="result-highlight" id="simDaysDiffResult">daqui a K dias</span>).
                                        <span id="simPrazoStatus" style="display: block; margin-top: 0.5rem; font-size: 0.85rem;"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Coluna Direita: Distribuição por Dia da Semana -->
                            <div style="background: rgba(255, 255, 255, 0.04); padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.06);">
                                <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.05em;">3. Horas Diárias Base</h3>
                                <p style="font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-bottom: 1.25rem;">Ajuste o plano base de estudos. O ritmo diário acima escala esses valores percentualmente.</p>

                                <div style="display: flex; flex-direction: column; gap: 0.875rem;">
                                    @php
                                        $diasSemana = [
                                            ['key' => 'seg', 'label' => 'Segunda-feira', 'value' => $activeGoal ? $activeGoal->carga_seg : 2.0],
                                            ['key' => 'ter', 'label' => 'Terça-feira', 'value' => $activeGoal ? $activeGoal->carga_ter : 2.0],
                                            ['key' => 'qua', 'label' => 'Quarta-feira', 'value' => $activeGoal ? $activeGoal->carga_qua : 2.0],
                                            ['key' => 'qui', 'label' => 'Quinta-feira', 'value' => $activeGoal ? $activeGoal->carga_qui : 2.0],
                                            ['key' => 'sex', 'label' => 'Sexta-feira', 'value' => $activeGoal ? $activeGoal->carga_sex : 2.0],
                                            ['key' => 'sab', 'label' => 'Sábado', 'value' => $activeGoal ? $activeGoal->carga_sab : 2.0],
                                            ['key' => 'dom', 'label' => 'Domingo', 'value' => $activeGoal ? $activeGoal->carga_dom : 2.0],
                                        ];
                                    @endphp

                                    @foreach($diasSemana as $dia)
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                                            <span style="font-size: 0.85rem; font-weight: 500; min-width: 100px;">{{ $dia['label'] }}</span>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <input type="number" id="base_{{ $dia['key'] }}" class="form-input" 
                                                       style="width: 70px; padding: 0.35rem 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: white; border-radius: 0.375rem; text-align: center;" 
                                                       min="0" max="24" step="0.1" value="{{ number_format($dia['value'], 1, '.', '') }}" 
                                                       oninput="onWeeklyInputChanged()">
                                                <span id="badge_{{ $dia['key'] }}" style="font-size: 0.75rem; color: #a78bfa; min-width: 80px; text-align: right; font-weight: 600;">
                                                    0 min
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.8rem; line-height: 1.4; color: rgba(255,255,255,0.7);">
                                    💡 <strong>Carga Semanal Acumulada:</strong> <span id="simWeeklyTotal" style="font-weight: 700; color: #10b981;">0h</span>.
                                    <div id="cronogramaMetaRequerida" style="margin-top: 0.5rem;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculadora de Meta por Proporção -->
                    <div class="simulator-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); margin-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.15); margin-bottom: 2rem;">
                        <h3 style="font-size: 1rem; font-weight: 700; color: #a78bfa; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                            Calculadora de Meta por Proporção de Rotina
                        </h3>
                        <p style="font-size: 0.8rem; color: rgba(255,255,255,0.7); margin-bottom: 1.25rem;">
                            Calcule exatamente quanto precisa estudar por dia da semana para atingir um volume de horas desejado em um período específico, respeitando a proporção da sua rotina atual.
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                            <div class="control-group">
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.8); font-weight: 600;">Volume de Horas Desejado</label>
                                <input type="number" id="calcTargetHours" class="form-input" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;" min="1" value="100" oninput="runProportionCalculator()">
                            </div>
                            <div class="control-group">
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.8); font-weight: 600;">Data de Início</label>
                                <input type="date" id="calcStartDate" class="form-input" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;" value="{{ date('Y-m-d') }}" onchange="runProportionCalculator()">
                            </div>
                            <div class="control-group">
                                <label style="font-size: 0.75rem; color: rgba(255,255,255,0.8); font-weight: 600;">Data Fim</label>
                                <input type="date" id="calcEndDate" class="form-input" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 0.5rem;" value="{{ date('Y-m-d', strtotime('+2 months')) }}" onchange="runProportionCalculator()">
                            </div>
                        </div>

                        <div id="calcProportionResult" style="background: rgba(255, 255, 255, 0.05); padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(255,255,255,0.08); font-size: 0.85rem; line-height: 1.5;">
                            <!-- Resultado renderizado em tempo real por JS -->
                        </div>
                    </div>
                </div>

                <!-- Charts Grid -->
                <div class="charts-grid">
                    <!-- Line Chart -->
                    <div class="chart-card">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-main);">Progresso Acumulado de Horas Estudadas</h3>
                        @if(empty($chartData['dates']))
                            <div style="flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:0.875rem;">
                                Adicione registros de estudo para visualizar o gráfico.
                            </div>
                        @else
                            <div class="chart-container">
                                <canvas id="chartProgressoEstudo"></canvas>
                            </div>
                        @endif
                    </div>

                    <!-- Consistency Heatmap -->
                    <div class="chart-card">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">Frequência & Consistência</h3>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">Seus estudos nas últimas 15 semanas (linha vertical: Seg a Dom):</p>
                        
                        <div class="heatmap-wrapper">
                            <div class="heatmap-container" id="heatmapContainer">
                                @foreach($heatmapData as $dateStr => $data)
                                    @php
                                        $h = $data['hours'];
                                        if ($h == 0) $level = 0;
                                        elseif ($h <= 1.5) $level = 1;
                                        elseif ($h <= 3.5) $level = 2;
                                        elseif ($h <= 5.5) $level = 3;
                                        else $level = 4;
                                    @endphp
                                    <div class="heatmap-day level-{{ $level }}" 
                                         data-tooltip="{{ $data['date'] }}: {{ number_format($h, 1, ',', '.') }} horas">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="heatmap-legend">
                            <span>Menos</span>
                            <div class="legend-box level-0"></div>
                            <div class="legend-box level-1"></div>
                            <div class="legend-box level-2"></div>
                            <div class="legend-box level-3"></div>
                            <div class="legend-box level-4"></div>
                            <span>Mais</span>
                        </div>
                    </div>
                </div>

                <!-- Logs Section -->
                <div class="logs-section">
                    <!-- Registrar Estudo Form -->
                    <div class="form-card">
                        <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1.25rem; color: var(--text-main);">Registrar Horas Estudadas</h3>
                        
                        <form action="{{ route('estudos.logs.store') }}" method="POST">
                            @csrf
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label class="form-label" for="log_goal_id" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Meta / Categoria</label>
                                <select name="study_goal_id" id="log_goal_id" class="form-input">
                                    <option value="">-- Estudo Avulso (Sem Meta) --</option>
                                    @foreach($goals as $g)
                                        <option value="{{ $g->id }}" {{ ($activeGoal && $activeGoal->id == $g->id) ? 'selected' : '' }}>
                                            {{ $g->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label class="form-label" for="log_data" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Data</label>
                                <input type="date" name="data" id="log_data" class="form-input" required value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-main); margin: 0;">Tempo de Estudo</label>
                                    <span style="font-size: 0.75rem; color: var(--primary); font-weight: 700;" id="logTimePreview">2h 00min</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Horas</label>
                                        <input type="number" name="horas_inteiras" id="log_horas_inteiras" class="form-input" min="0" max="24" value="2" placeholder="0" style="margin-top: 2px;" oninput="updateLogTimePreview()">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Minutos (0-59)</label>
                                        <input type="number" name="minutos" id="log_minutos" class="form-input" min="0" max="59" value="0" placeholder="0" style="margin-top: 2px;" oninput="updateLogTimePreview()">
                                    </div>
                                </div>

                                <!-- Chips de Atalho Rápido -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.6rem; flex-wrap: wrap; align-items: center;">
                                    <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Atalhos:</span>
                                    <button type="button" class="preset-chip" onclick="addStudyTime(0, 15)">+15m</button>
                                    <button type="button" class="preset-chip" onclick="addStudyTime(0, 30)">+30m</button>
                                    <button type="button" class="preset-chip" onclick="addStudyTime(0, 45)">+45m</button>
                                    <button type="button" class="preset-chip" onclick="addStudyTime(1, 0)">+1h</button>
                                    <button type="button" class="preset-chip" onclick="addStudyTime(2, 0)">+2h</button>
                                    <button type="button" class="preset-chip danger" onclick="resetStudyTime()">Zerar</button>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label class="form-label" for="log_obs" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Observações / Assunto</label>
                                <textarea name="observacoes" id="log_obs" rows="3" class="form-input" placeholder="Ex: Capitulo 3, resolução de 20 questões..."></textarea>
                            </div>

                            <button type="submit" class="btn-add" style="width: 100%; justify-content: center; margin-top: 0.5rem;">Salvar Registro</button>
                        </form>

                        @if($activeGoal)
                            <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">

                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <button onclick="editGoalModal({{ json_encode($activeGoal) }})" class="btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                                    Editar Meta Atual
                                </button>
                                <form action="{{ route('estudos.goals.destroy', $activeGoal->id) }}" method="POST" onsubmit="return confirm('Deseja realmente excluir esta meta de estudos e todos os seus registros associados?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: var(--danger); font-size: 0.8rem; cursor: pointer; font-weight: 600;">
                                        Excluir Meta
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <!-- Tabela de Logs Recentes -->
                    <div class="table-container">
                        <div style="padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-main);">Histórico de Estudos</h3>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 999px;">
                                {{ $logs->count() }} registros
                            </span>
                        </div>

                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Meta / Categoria</th>
                                        <th>Tempo</th>
                                        <th>Observações / Assunto</th>
                                        <th style="text-align: right;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($logs as $log)
                                        <tr>
                                            <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($log->data)->format('d/m/Y') }}</td>
                                            <td style="white-space: nowrap;">
                                                @if($log->goal)
                                                    <span class="goal-tag">{{ $log->goal->nome }}</span>
                                                @else
                                                    <span class="goal-tag avulso">Estudo Avulso</span>
                                                @endif
                                            </td>
                                            <td style="font-weight: 700; color: var(--primary); white-space: nowrap;">
                                                @php
                                                    $hInt = floor($log->horas);
                                                    $mInt = round(($log->horas - $hInt) * 60);
                                                @endphp
                                                {{ $hInt }}h{{ $mInt > 0 ? sprintf('%02d', $mInt) : '' }}
                                            </td>
                                            <td style="color: var(--text-main); font-size: 0.8rem; max-width: 300px;">
                                                {{ $log->observacoes ?: '-' }}
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <button type="button" class="action-btn-small" style="background:none; border:none; cursor:pointer; margin-right: 0.25rem;" title="Editar Lançamento" onclick='editLogModal({{ json_encode([
                                                    "id" => $log->id,
                                                    "study_goal_id" => $log->study_goal_id,
                                                    "data" => \Carbon\Carbon::parse($log->data)->format("Y-m-d"),
                                                    "horas_inteiras" => $hInt,
                                                    "minutos" => $mInt,
                                                    "observacoes" => $log->observacoes,
                                                ]) }})'>
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <form action="{{ route('estudos.logs.destroy', $log->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Deseja excluir este registro de estudo?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn-small" style="background:none; border:none; cursor:pointer;" title="Excluir">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 4rem;">
                                                Nenhum registro de estudo encontrado. Comece registrando suas horas no formulário ao lado!
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Metas de Estudo (Criar/Editar) -->
    <div id="modalGoal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalGoalTitle">Nova Meta de Estudo</h3>
                <button onclick="closeGoalModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form action="{{ route('estudos.goals.store') }}" method="POST">
                @csrf
                <input type="hidden" name="goal_id" id="inputGoalId">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="inputGoalNome" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">Nome da Meta / Projeto</label>
                        <input type="text" name="nome" id="inputGoalNome" class="form-input" required placeholder="Ex: Preparação OAB, Inglês, Programação">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="inputGoalHoras" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">
                                Horas Meta <span style="font-weight:400; color:var(--text-muted); font-size:0.75rem;">(Opcional)</span>
                            </label>
                            <input type="number" name="horas_meta" id="inputGoalHoras" class="form-input" min="0" placeholder="Ex: 200 (deixe em branco se livre)">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputGoalIniciais" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">Horas Já Estudadas</label>
                            <input type="number" name="horas_iniciais" id="inputGoalIniciais" class="form-input" min="0" step="0.5" value="0" placeholder="Ex: 50">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="inputGoalDiario" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">Meta Diária (Horas/Dia)</label>
                            <input type="number" name="horas_diarias_padrao" id="inputGoalDiario" class="form-input" min="0.1" max="24" step="0.1" value="2.0">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="inputGoalInicio" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">Data de Início</label>
                            <input type="date" name="data_inicio" id="inputGoalInicio" class="form-input" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="inputGoalLimite" style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.35rem;">Data Limite (Opcional)</label>
                        <input type="date" name="data_limite" id="inputGoalLimite" class="form-input">
                    </div>

                    <!-- Banner de Média Histórica de Estudos em Tempo Real -->
                    <div id="goalModalHistoryStats" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border: 1px solid #bbf7d0; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.25rem; display: none;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <div style="font-size: 0.75rem; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 16 14"></polyline></svg>
                                    Média Diária Histórica Calculada
                                </div>
                                <div style="font-size: 1.25rem; font-weight: 800; color: #0f766e;" id="modalMediaVal">
                                    0,0h / dia <span style="font-size: 0.85rem; font-weight: 600; color: #334155;" id="modalMediaHm">(0 min/dia)</span>
                                </div>
                                <div style="font-size: 0.75rem; color: #475569; margin-top: 0.25rem;" id="modalMediaSub">
                                    --
                                </div>
                                <div style="font-size: 0.75rem; color: #1e40af; margin-top: 0.35rem; font-weight: 500;" id="modalMediaProj">
                                    --
                                </div>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.4rem; min-width: 170px;">
                                <button type="button" class="btn-add" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; background: #0d9488; justify-content: center;" onclick="applyCalculatedMediaToGoal()">
                                    Usar Média como Meta Diária
                                </button>
                                <button type="button" class="btn-secondary" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; background: white; text-align: center;" onclick="applyCalculatedMediaToWeek()">
                                    Aplicar nos Dias da Semana
                                </button>
                            </div>
                        </div>
                    </div>

                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 0.25rem;">
                        Planejamento por Dia da Semana (Horas)
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalSeg">Seg</label>
                            <input type="number" name="carga_seg" id="inputGoalSeg" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalTer">Ter</label>
                            <input type="number" name="carga_ter" id="inputGoalTer" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalQua">Qua</label>
                            <input type="number" name="carga_qua" id="inputGoalQua" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalQui">Qui</label>
                            <input type="number" name="carga_qui" id="inputGoalQui" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalSex">Sex</label>
                            <input type="number" name="carga_sex" id="inputGoalSex" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalSab">Sáb</label>
                            <input type="number" name="carga_sab" id="inputGoalSab" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:0.75rem;" for="inputGoalDom">Dom</label>
                            <input type="number" name="carga_dom" id="inputGoalDom" class="form-input" min="0" max="24" step="0.5" value="2.0">
                        </div>
                        <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                            <button type="button" class="btn-secondary" style="width:100%; padding:0.5rem 0.25rem; font-size:0.7rem;" onclick="replicateDailyHours()">Repetir Meta</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeGoalModal()">Cancelar</button>
                    <button type="submit" class="btn-add">Salvar Meta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Lançamento de Estudo -->
    <div id="modalEditLog" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Editar Lançamento de Estudo</h3>
                <button type="button" onclick="closeEditLogModal()" style="border:none; background:none; font-size:1.5rem; cursor:pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form id="formEditLog" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="edit_log_goal_id" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Meta / Categoria</label>
                        <select name="study_goal_id" id="edit_log_goal_id" class="form-input">
                            <option value="">-- Estudo Avulso (Sem Meta) --</option>
                            @foreach($goals as $g)
                                <option value="{{ $g->id }}">
                                    {{ $g->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="edit_log_data" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Data</label>
                        <input type="date" name="data" id="edit_log_data" class="form-input" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 600; color: var(--text-main); margin: 0;">Tempo de Estudo</label>
                            <span style="font-size: 0.75rem; color: var(--primary); font-weight: 700;" id="editLogTimePreview">0h 00min</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <label style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Horas</label>
                                <input type="number" name="horas_inteiras" id="edit_log_horas_inteiras" class="form-input" min="0" max="24" placeholder="0" style="margin-top: 2px;" oninput="updateEditLogTimePreview()">
                            </div>
                            <div>
                                <label style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Minutos (0-59)</label>
                                <input type="number" name="minutos" id="edit_log_minutos" class="form-input" min="0" max="59" placeholder="0" style="margin-top: 2px;" oninput="updateEditLogTimePreview()">
                            </div>
                        </div>

                        <!-- Chips de Atalho Rápido no Modal -->
                        <div style="display: flex; gap: 0.35rem; margin-top: 0.6rem; flex-wrap: wrap; align-items: center;">
                            <span style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Atalhos:</span>
                            <button type="button" class="preset-chip" onclick="addEditStudyTime(0, 15)">+15m</button>
                            <button type="button" class="preset-chip" onclick="addEditStudyTime(0, 30)">+30m</button>
                            <button type="button" class="preset-chip" onclick="addEditStudyTime(0, 45)">+45m</button>
                            <button type="button" class="preset-chip" onclick="addEditStudyTime(1, 0)">+1h</button>
                            <button type="button" class="preset-chip" onclick="addEditStudyTime(2, 0)">+2h</button>
                            <button type="button" class="preset-chip danger" onclick="resetEditStudyTime()">Zerar</button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" for="edit_log_observacoes" style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Observações / Assunto</label>
                        <textarea name="observacoes" id="edit_log_observacoes" class="form-input" rows="3" placeholder="Ex: Capítulos 1 a 3 do livro, Resolução de 20 questões..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeEditLogModal()">Cancelar</button>
                    <button type="submit" class="btn-add" style="border:none;">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Helper to format decimal hours into "Xh Ymin"
        function formatHoursToHm(hoursDecimal) {
            const totalMinutes = Math.round(hoursDecimal * 60);
            const h = Math.floor(totalMinutes / 60);
            const m = totalMinutes % 60;
            if (h === 0 && m === 0) return "0 min";
            if (h === 0) return `${m} min`;
            if (m === 0) return `${h}h`;
            return `${h}h ${m}min`;
        }

        const sliderGoalHours = document.getElementById('sliderGoalHours');
        const sliderStudiedHours = document.getElementById('sliderStudiedHours');
        const sliderWeeklyPercent = document.getElementById('sliderWeeklyPercent');
        
        const valGoalHours = document.getElementById('valGoalHours');
        const valStudiedHours = document.getElementById('valStudiedHours');
        const valWeeklyPercent = document.getElementById('valWeeklyPercent');

        const simDailyResult = document.getElementById('simDailyResult');
        const simStudiedResult = document.getElementById('simStudiedResult');
        const simDaysResult = document.getElementById('simDaysResult');
        const simGoalResult = document.getElementById('simGoalResult');
        const simDateResult = document.getElementById('simDateResult');
        const simDaysDiffResult = document.getElementById('simDaysDiffResult');
        const simPrazoStatus = document.getElementById('simPrazoStatus');

        const dataLimiteGoalStr = "{{ ($activeGoal && $activeGoal->data_limite) ? $activeGoal->data_limite->format('Y-m-d') : '' }}";

        let baselineHours = {
            seg: parseFloat(document.getElementById('base_seg').value) || 2.0,
            ter: parseFloat(document.getElementById('base_ter').value) || 2.0,
            qua: parseFloat(document.getElementById('base_qua').value) || 2.0,
            qui: parseFloat(document.getElementById('base_qui').value) || 2.0,
            sex: parseFloat(document.getElementById('base_sex').value) || 2.0,
            sab: parseFloat(document.getElementById('base_sab').value) || 2.0,
            dom: parseFloat(document.getElementById('base_dom').value) || 2.0
        };

        window.onWeeklyInputChanged = function() {
            const percent = parseFloat(sliderWeeklyPercent.value);
            const factor = 1 + (percent / 100);

            baselineHours.seg = (parseFloat(document.getElementById('base_seg').value) || 0) / factor;
            baselineHours.ter = (parseFloat(document.getElementById('base_ter').value) || 0) / factor;
            baselineHours.qua = (parseFloat(document.getElementById('base_qua').value) || 0) / factor;
            baselineHours.qui = (parseFloat(document.getElementById('base_qui').value) || 0) / factor;
            baselineHours.sex = (parseFloat(document.getElementById('base_sex').value) || 0) / factor;
            baselineHours.sab = (parseFloat(document.getElementById('base_sab').value) || 0) / factor;
            baselineHours.dom = (parseFloat(document.getElementById('base_dom').value) || 0) / factor;

            runSimulation(false);
        }

        window.applyRequiredRitmo = function(value) {
            const baseWeeklyTotal = baselineHours.seg + baselineHours.ter + baselineHours.qua + baselineHours.qui + baselineHours.sex + baselineHours.sab + baselineHours.dom;
            const targetWeeklyTotal = value * 7;
            
            if (baseWeeklyTotal > 0) {
                const requiredPercent = ((targetWeeklyTotal / baseWeeklyTotal) - 1) * 100;
                const clampedPercent = Math.min(200, Math.max(-100, Math.round(requiredPercent / 5) * 5));
                sliderWeeklyPercent.value = clampedPercent;
            } else {
                sliderWeeklyPercent.value = 0;
            }
            runSimulation(true);
        }

        function runSimulation(updateInputs = true) {
            if (updateInputs && typeof updateInputs === 'object') {
                updateInputs = true;
            }
            
            const goalHours = parseFloat(sliderGoalHours.value);
            const percent = parseFloat(sliderWeeklyPercent.value);
            const factor = 1 + (percent / 100);
            
            sliderStudiedHours.max = Math.max(goalHours, 3000);
            let studiedHours = parseFloat(sliderStudiedHours.value);
            if (studiedHours > goalHours) {
                studiedHours = goalHours;
                sliderStudiedHours.value = goalHours;
            }

            valGoalHours.innerText = goalHours + "h";
            valStudiedHours.innerText = formatHoursToHm(studiedHours);
            
            if (percent > 0) {
                valWeeklyPercent.innerText = `+${percent}% (Aumento)`;
            } else if (percent < 0) {
                valWeeklyPercent.innerText = `${percent}% (Redução)`;
            } else {
                valWeeklyPercent.innerText = `0% (Sem alteração)`;
            }

            const horasRestantesSim = Math.max(0, goalHours - studiedHours);

            if (updateInputs) {
                document.getElementById('base_seg').value = (baselineHours.seg * factor).toFixed(1);
                document.getElementById('base_ter').value = (baselineHours.ter * factor).toFixed(1);
                document.getElementById('base_qua').value = (baselineHours.qua * factor).toFixed(1);
                document.getElementById('base_qui').value = (baselineHours.qui * factor).toFixed(1);
                document.getElementById('base_sex').value = (baselineHours.sex * factor).toFixed(1);
                document.getElementById('base_sab').value = (baselineHours.sab * factor).toFixed(1);
                document.getElementById('base_dom').value = (baselineHours.dom * factor).toFixed(1);
            }

            const currentSeg = parseFloat(document.getElementById('base_seg').value) || 0;
            const currentTer = parseFloat(document.getElementById('base_ter').value) || 0;
            const currentQua = parseFloat(document.getElementById('base_qua').value) || 0;
            const currentQui = parseFloat(document.getElementById('base_qui').value) || 0;
            const currentSex = parseFloat(document.getElementById('base_sex').value) || 0;
            const currentSab = parseFloat(document.getElementById('base_sab').value) || 0;
            const currentDom = parseFloat(document.getElementById('base_dom').value) || 0;

            document.getElementById('badge_seg').innerText = formatHoursToHm(currentSeg);
            document.getElementById('badge_ter').innerText = formatHoursToHm(currentTer);
            document.getElementById('badge_qua').innerText = formatHoursToHm(currentQua);
            document.getElementById('badge_qui').innerText = formatHoursToHm(currentQui);
            document.getElementById('badge_sex').innerText = formatHoursToHm(currentSex);
            document.getElementById('badge_sab').innerText = formatHoursToHm(currentSab);
            document.getElementById('badge_dom').innerText = formatHoursToHm(currentDom);

            const weeklyTotal = currentSeg + currentTer + currentQua + currentQui + currentSex + currentSab + currentDom;
            document.getElementById('simWeeklyTotal').innerText = formatHoursToHm(weeklyTotal) + '/semana';

            const dailyAverage = weeklyTotal / 7;
            const diasNecessariosSim = weeklyTotal > 0 ? Math.ceil((horasRestantesSim / weeklyTotal) * 7) : 99999;

            const startDateStr = document.getElementById('simStartDate').value;
            const targetDateStr = document.getElementById('simTargetDate').value;
            
            const dataHoje = new Date();
            dataHoje.setHours(0,0,0,0);

            let simStartDate = new Date(dataHoje);
            if (startDateStr) {
                simStartDate = new Date(startDateStr + "T00:00:00");
                simStartDate.setHours(0,0,0,0);
            }

            let simTargetDate = null;
            if (targetDateStr) {
                simTargetDate = new Date(targetDateStr + "T00:00:00");
                simTargetDate.setHours(0,0,0,0);
            }
            
            const dataConclusao = new Date(simStartDate);
            dataConclusao.setDate(simStartDate.getDate() + diasNecessariosSim);

            const diaFormatted = String(dataConclusao.getDate()).padStart(2, '0');
            const mesFormatted = String(dataConclusao.getMonth() + 1).padStart(2, '0');
            const anoFormatted = dataConclusao.getFullYear();
            const dateStringFormatted = `${diaFormatted}/${mesFormatted}/${anoFormatted}`;

            simDailyResult.innerText = formatHoursToHm(dailyAverage);
            simStudiedResult.innerText = formatHoursToHm(studiedHours);
            simDaysResult.innerText = diasNecessariosSim + " dias";
            simGoalResult.innerText = goalHours + "h";
            simDateResult.innerText = dateStringFormatted;
            
            const diffFromToday = Math.ceil((dataConclusao.getTime() - dataHoje.getTime()) / (1000 * 3600 * 24));
            simDaysDiffResult.innerText = diffFromToday >= 0 ? `daqui a ${diffFromToday} dias` : `${Math.abs(diffFromToday)} dias atrás`;

            const cronogramaMetaRequerida = document.getElementById('cronogramaMetaRequerida');
            if (simStartDate && simTargetDate) {
                if (simTargetDate > simStartDate) {
                    const timeDiff = simTargetDate.getTime() - simStartDate.getTime();
                    const simDaysAvailable = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    const requiredDailyAvg = simDaysAvailable > 0 ? (horasRestantesSim / simDaysAvailable) : 0;
                    
                    cronogramaMetaRequerida.innerHTML = `<div style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(255,255,255,0.05); border-radius: 4px;">📅 <strong>Cronograma Desejado:</strong> de ${simStartDate.toLocaleDateString('pt-BR')} até ${simTargetDate.toLocaleDateString('pt-BR')} (${simDaysAvailable} dias)<br>
                    • Ritmo Diário Requerido: <strong>${formatHoursToHm(requiredDailyAvg)}/dia</strong> para atingir o objetivo.<br>
                    <button type="button" class="btn-add" style="margin-top:0.4rem; padding: 0.25rem 0.5rem; font-size: 0.7rem; background:#8b5cf6;" onclick="applyRequiredRitmo(${requiredDailyAvg})">Ajustar Simulador para ${formatHoursToHm(requiredDailyAvg)}/dia</button></div>`;
                } else {
                    cronogramaMetaRequerida.innerHTML = `<span style="color:#ef4444; display:block; margin-top:0.5rem;">⚠️ Data limite deve ser posterior à data de início.</span>`;
                }
            } else {
                cronogramaMetaRequerida.innerHTML = '';
            }

            if (dataLimiteGoalStr) {
                const dataLimite = new Date(dataLimiteGoalStr + "T00:00:00");
                dataLimite.setHours(0,0,0,0);
                
                const timeDiffLimite = dataLimite.getTime() - dataConclusao.getTime();
                const diffDays = Math.ceil(timeDiffLimite / (1000 * 3600 * 24));

                if (diffDays >= 0) {
                    simPrazoStatus.innerHTML = `🟢 <strong>Dentro do prazo limite!</strong> Conclusão estimada ${diffDays} dias antes do limite (${dataLimite.toLocaleDateString('pt-BR')}).`;
                    simPrazoStatus.style.color = '#10b981';
                } else {
                    const diasDeAtraso = Math.abs(diffDays);
                    const dataLimiteHojeDiff = dataLimite.getTime() - dataHoje.getTime();
                    const diasRestantesPrazo = Math.ceil(dataLimiteHojeDiff / (1000 * 3600 * 24));
                    
                    let recomendacao = '';
                    if (diasRestantesPrazo > 0) {
                        const ritmoRequerido = (horasRestantesSim / diasRestantesPrazo).toFixed(1);
                        recomendacao = `. Recomendação: estude no mínimo <strong>${formatHoursToHm(ritmoRequerido)}/dia</strong> para cumprir o prazo.`;
                    }
                    
                    simPrazoStatus.innerHTML = `🔴 <strong>Fora do prazo!</strong> Estará atrasado em ${diasDeAtraso} dias em relação ao limite (${dataLimite.toLocaleDateString('pt-BR')})${recomendacao}.`;
                    simPrazoStatus.style.color = '#ef4444';
                }
            } else {
                simPrazoStatus.innerHTML = '';
            }

            if (typeof runProportionCalculator === 'function') {
                runProportionCalculator();
            }
        }

        function runProportionCalculator() {
            const targetHours = parseFloat(document.getElementById('calcTargetHours').value) || 0;
            const startDateStr = document.getElementById('calcStartDate').value;
            const endDateStr = document.getElementById('calcEndDate').value;
            const resultDiv = document.getElementById('calcProportionResult');

            if (!resultDiv) return;

            if (!startDateStr || !endDateStr || targetHours <= 0) {
                resultDiv.innerHTML = '<span style="color: #f59e0b;">⚠️ Insira um volume de horas maior que zero e selecione ambas as datas.</span>';
                return;
            }

            const startDate = new Date(startDateStr + "T00:00:00");
            const endDate = new Date(endDateStr + "T00:00:00");
            startDate.setHours(0,0,0,0);
            endDate.setHours(0,0,0,0);

            if (endDate <= startDate) {
                resultDiv.innerHTML = '<span style="color: #ef4444;">⚠️ A data fim deve ser posterior à data de início.</span>';
                return;
            }

            let counts = { seg: 0, ter: 0, qua: 0, qui: 0, sex: 0, sab: 0, dom: 0 };
            let current = new Date(startDate);
            while (current <= endDate) {
                let day = current.getDay();
                if (day === 0) counts.dom++;
                else if (day === 1) counts.seg++;
                else if (day === 2) counts.ter++;
                else if (day === 3) counts.qua++;
                else if (day === 4) counts.qui++;
                else if (day === 5) counts.sex++;
                else if (day === 6) counts.sab++;
                current.setDate(current.getDate() + 1);
            }

            const baseSeg = parseFloat(document.getElementById('base_seg').value) || 0;
            const baseTer = parseFloat(document.getElementById('base_ter').value) || 0;
            const baseQua = parseFloat(document.getElementById('base_qua').value) || 0;
            const baseQui = parseFloat(document.getElementById('base_qui').value) || 0;
            const baseSex = parseFloat(document.getElementById('base_sex').value) || 0;
            const baseSab = parseFloat(document.getElementById('base_sab').value) || 0;
            const baseDom = parseFloat(document.getElementById('base_dom').value) || 0;

            const weightedTotal = (counts.seg * baseSeg) + 
                                  (counts.ter * baseTer) + 
                                  (counts.qua * baseQua) + 
                                  (counts.qui * baseQui) + 
                                  (counts.sex * baseSex) + 
                                  (counts.sab * baseSab) + 
                                  (counts.dom * baseDom);

            if (weightedTotal === 0) {
                resultDiv.innerHTML = '<span style="color: #f59e0b;">⚠️ Suas horas diárias base na rotina estão zeradas. Preencha pelo menos um dia para calcular a proporção.</span>';
                return;
            }

            const factor = targetHours / weightedTotal;

            const reqSeg = baseSeg * factor;
            const reqTer = baseTer * factor;
            const reqQua = baseQua * factor;
            const reqQui = baseQui * factor;
            const reqSex = baseSex * factor;
            const reqSab = baseSab * factor;
            const reqDom = baseDom * factor;

            const totalDays = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 3600 * 24)) + 1;
            const requiredAvgDaily = targetHours / totalDays;

            resultDiv.innerHTML = `
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem;" class="sim-grid">
                    <div>
                        <p style="margin-bottom: 0.75rem;">Para atingir <strong>${targetHours}h</strong> estudadas em <strong>${totalDays} dias</strong> (de ${startDate.toLocaleDateString('pt-BR')} a ${endDate.toLocaleDateString('pt-BR')}), mantendo a proporção da sua rotina:</p>
                        <p style="font-size: 1rem; color: #10b981; font-weight: 700; margin-bottom: 0.5rem;">Média Necessária: ${formatHoursToHm(requiredAvgDaily)} / dia</p>
                        <button type="button" class="btn-add" style="margin-top: 0.5rem; background: #8b5cf6;" onclick="applyRequiredRitmo(${requiredAvgDaily})">
                            Aplicar Ritmo ao Simulador
                        </button>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.05);">
                        <p style="font-weight: 600; font-size: 0.75rem; text-transform: uppercase; color: #a78bfa; margin-bottom: 0.5rem;">Carga por Dia de Semana:</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem 1rem; font-size: 0.8rem;">
                            <div>Seg: <strong>${formatHoursToHm(reqSeg)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.seg}x)</span></div>
                            <div>Sex: <strong>${formatHoursToHm(reqSex)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.sex}x)</span></div>
                            <div>Ter: <strong>${formatHoursToHm(reqTer)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.ter}x)</span></div>
                            <div>Sáb: <strong>${formatHoursToHm(reqSab)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.sab}x)</span></div>
                            <div>Qua: <strong>${formatHoursToHm(reqQua)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.qua}x)</span></div>
                            <div>Dom: <strong>${formatHoursToHm(reqDom)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.dom}x)</span></div>
                            <div>Qui: <strong>${formatHoursToHm(reqQui)}</strong> <span style="font-size: 0.7rem; color: rgba(255,255,255,0.5)">(${counts.qui}x)</span></div>
                        </div>
                    </div>
                </div>
            `;
        }

        sliderGoalHours.addEventListener('input', () => runSimulation(true));
        sliderWeeklyPercent.addEventListener('input', () => runSimulation(true));
        sliderStudiedHours.addEventListener('input', () => runSimulation(true));
        
        runSimulation(true);

        @if(!empty($chartData['dates']))
            const ctx = document.getElementById('chartProgressoEstudo').getContext('2d');
            const chartData = {
                labels: {!! json_encode($chartData['dates']) !!},
                datasets: [
                    {
                        label: 'Horas Estudadas (Acumulado)',
                        data: {!! json_encode($chartData['real']) !!},
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.05)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.1,
                        pointBackgroundColor: '#8b5cf6',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Ritmo Base Ideal',
                        data: {!! json_encode($chartData['target']) !!},
                        borderColor: 'rgba(156, 163, 175, 0.5)',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        fill: false,
                        tension: 0,
                        pointRadius: 0
                    }
                ]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw.toFixed(1) + 'h';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Horas Acumuladas',
                                font: {
                                    family: "'Inter', sans-serif",
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                font: { family: "'Inter', sans-serif" }
                            }
                        },
                        x: {
                            ticks: {
                                font: { family: "'Inter', sans-serif", size: 10 },
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        @endif

        function replicateDailyHours() {
            const val = parseFloat(document.getElementById('inputGoalDiario').value) || 2.0;
            document.getElementById('inputGoalSeg').value = val;
            document.getElementById('inputGoalTer').value = val;
            document.getElementById('inputGoalQua').value = val;
            document.getElementById('inputGoalQui').value = val;
            document.getElementById('inputGoalSex').value = val;
            document.getElementById('inputGoalSab').value = val;
            document.getElementById('inputGoalDom').value = val;
        }

        function updateGoalModalStats() {
            const horasIniciais = parseFloat(document.getElementById('inputGoalIniciais').value) || 0;
            const dataInicioStr = document.getElementById('inputGoalInicio').value;
            const horasMeta = parseFloat(document.getElementById('inputGoalHoras').value) || 0;
            const dataLimiteStr = document.getElementById('inputGoalLimite').value;
            const container = document.getElementById('goalModalHistoryStats');

            if (!container) return;

            if (horasIniciais <= 0 || !dataInicioStr) {
                container.style.display = 'none';
                return;
            }

            const parts = dataInicioStr.split('-');
            if (parts.length !== 3) {
                container.style.display = 'none';
                return;
            }

            const dataInicio = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            const dataHoje = new Date();
            dataHoje.setHours(0,0,0,0);
            dataInicio.setHours(0,0,0,0);

            // Diferença em dias inclusive (de data_inicio até hoje)
            const diffTime = dataHoje.getTime() - dataInicio.getTime();
            let diasDecorridos = Math.floor(diffTime / (1000 * 3600 * 24)) + 1;
            if (diasDecorridos <= 0) diasDecorridos = 1;

            const mediaDiaria = horasIniciais / diasDecorridos;
            const mediaFormatada = mediaDiaria.toFixed(1).replace('.', ',');
            const hmFormatado = formatHoursToHm(mediaDiaria);

            const inicioFormatted = dataInicio.toLocaleDateString('pt-BR');

            document.getElementById('modalMediaVal').innerHTML = `${mediaFormatada}h / dia <span style="font-size: 0.85rem; font-weight: 600; color: #334155;">(${hmFormatado}/dia)</span>`;
            document.getElementById('modalMediaSub').innerText = `Baseado em ${horasIniciais}h acumuladas em ${diasDecorridos} ${diasDecorridos === 1 ? 'dia' : 'dias'} (${inicioFormatted} até hoje)`;

            let projHtml = '';
            if (horasMeta > 0) {
                const horasRestantes = Math.max(0, horasMeta - horasIniciais);
                if (horasRestantes > 0 && mediaDiaria > 0) {
                    const diasFaltantes = Math.ceil(horasRestantes / mediaDiaria);
                    const dataProj = new Date(dataHoje);
                    dataProj.setDate(dataProj.getDate() + diasFaltantes);
                    
                    projHtml = `🎯 Nesse ritmo, as ${horasMeta}h serão concluídas em <strong>${dataProj.toLocaleDateString('pt-BR')}</strong> (~${diasFaltantes} dias).`;
                    
                    if (dataLimiteStr) {
                        const lParts = dataLimiteStr.split('-');
                        if (lParts.length === 3) {
                            const dataLimite = new Date(parseInt(lParts[0]), parseInt(lParts[1]) - 1, parseInt(lParts[2]));
                            dataLimite.setHours(0,0,0,0);
                            if (dataProj <= dataLimite) {
                                projHtml += ` <span style="color:#166534; font-weight:600;">🟢 (Dentro do prazo limite!)</span>`;
                            } else {
                                const diasAtraso = Math.ceil((dataProj.getTime() - dataLimite.getTime()) / (1000 * 3600 * 24));
                                projHtml += ` <span style="color:#dc2626; font-weight:600;">🔴 (${diasAtraso} dias após o limite)</span>`;
                            }
                        }
                    }
                } else if (horasRestantes === 0) {
                    projHtml = `🎉 Meta de ${horasMeta}h já atingida!`;
                }
            }

            document.getElementById('modalMediaProj').innerHTML = projHtml;
            container.style.display = 'block';
            container.dataset.calculatedMedia = mediaDiaria.toFixed(1);
        }

        function applyCalculatedMediaToGoal() {
            const container = document.getElementById('goalModalHistoryStats');
            if (!container || !container.dataset.calculatedMedia) return;
            const media = parseFloat(container.dataset.calculatedMedia);
            document.getElementById('inputGoalDiario').value = media.toFixed(1);
            replicateDailyHours();
        }

        function applyCalculatedMediaToWeek() {
            const container = document.getElementById('goalModalHistoryStats');
            if (!container || !container.dataset.calculatedMedia) return;
            const media = parseFloat(container.dataset.calculatedMedia);
            document.getElementById('inputGoalDiario').value = media.toFixed(1);
            document.getElementById('inputGoalSeg').value = media.toFixed(1);
            document.getElementById('inputGoalTer').value = media.toFixed(1);
            document.getElementById('inputGoalQua').value = media.toFixed(1);
            document.getElementById('inputGoalQui').value = media.toFixed(1);
            document.getElementById('inputGoalSex').value = media.toFixed(1);
            document.getElementById('inputGoalSab').value = media.toFixed(1);
            document.getElementById('inputGoalDom').value = media.toFixed(1);
        }

        document.getElementById('inputGoalIniciais').addEventListener('input', updateGoalModalStats);
        document.getElementById('inputGoalInicio').addEventListener('change', updateGoalModalStats);
        document.getElementById('inputGoalHoras').addEventListener('input', updateGoalModalStats);
        document.getElementById('inputGoalLimite').addEventListener('change', updateGoalModalStats);

        function openGoalModal() {
            document.getElementById('modalGoalTitle').innerText = 'Nova Meta de Estudo';
            document.getElementById('inputGoalId').value = '';
            document.getElementById('inputGoalNome').value = '';
            document.getElementById('inputGoalHoras').value = '';
            document.getElementById('inputGoalIniciais').value = '0';
            document.getElementById('inputGoalDiario').value = '2.0';
            document.getElementById('inputGoalInicio').value = new Date().toISOString().split('T')[0];
            document.getElementById('inputGoalLimite').value = '';
            document.getElementById('inputGoalSeg').value = '2.0';
            document.getElementById('inputGoalTer').value = '2.0';
            document.getElementById('inputGoalQua').value = '2.0';
            document.getElementById('inputGoalQui').value = '2.0';
            document.getElementById('inputGoalSex').value = '2.0';
            document.getElementById('inputGoalSab').value = '2.0';
            document.getElementById('inputGoalDom').value = '2.0';
            updateGoalModalStats();
            document.getElementById('modalGoal').style.display = 'flex';
        }

        function editGoalModal(goal) {
            document.getElementById('modalGoalTitle').innerText = 'Editar Meta de Estudo';
            document.getElementById('inputGoalId').value = goal.id;
            document.getElementById('inputGoalNome').value = goal.nome;
            document.getElementById('inputGoalHoras').value = goal.horas_meta ? Math.round(goal.horas_meta) : '';
            document.getElementById('inputGoalIniciais').value = goal.horas_iniciais ? parseFloat(goal.horas_iniciais) : 0;
            document.getElementById('inputGoalDiario').value = goal.horas_diarias_padrao;
            document.getElementById('inputGoalSeg').value = goal.carga_seg ? parseFloat(goal.carga_seg) : 2.0;
            document.getElementById('inputGoalTer').value = goal.carga_ter ? parseFloat(goal.carga_ter) : 2.0;
            document.getElementById('inputGoalQua').value = goal.carga_qua ? parseFloat(goal.carga_qua) : 2.0;
            document.getElementById('inputGoalQui').value = goal.carga_qui ? parseFloat(goal.carga_qui) : 2.0;
            document.getElementById('inputGoalSex').value = goal.carga_sex ? parseFloat(goal.carga_sex) : 2.0;
            document.getElementById('inputGoalSab').value = goal.carga_sab ? parseFloat(goal.carga_sab) : 2.0;
            document.getElementById('inputGoalDom').value = goal.carga_dom ? parseFloat(goal.carga_dom) : 2.0;
            
            if (goal.data_inicio) {
                const dateI = new Date(goal.data_inicio);
                document.getElementById('inputGoalInicio').value = dateI.toISOString().split('T')[0];
            }
            if (goal.data_limite) {
                const dateL = new Date(goal.data_limite);
                document.getElementById('inputGoalLimite').value = dateL.toISOString().split('T')[0];
            } else {
                document.getElementById('inputGoalLimite').value = '';
            }
            
            updateGoalModalStats();
            document.getElementById('modalGoal').style.display = 'flex';
        }

        function closeGoalModal() {
            document.getElementById('modalGoal').style.display = 'none';
        }

        function switchStudyTab(tabName) {
            document.querySelectorAll('.study-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.study-tab-content').forEach(content => content.style.display = 'none');

            if (tabName === 'simulador') {
                const btnSim = document.getElementById('btnTabSimulador');
                const contentSim = document.getElementById('tabContentSimulador');
                if (btnSim) btnSim.classList.add('active');
                if (contentSim) contentSim.style.display = 'block';
                if (typeof runSimulation === 'function') runSimulation(false);
            } else {
                const btnVis = document.getElementById('btnTabVisaoGeral');
                const contentVis = document.getElementById('tabContentVisaoGeral');
                if (btnVis) btnVis.classList.add('active');
                if (contentVis) contentVis.style.display = 'block';
            }
            localStorage.setItem('activeStudyTab', tabName);
        }

        function updateLogTimePreview() {
            const hInput = document.getElementById('log_horas_inteiras');
            const mInput = document.getElementById('log_minutos');
            const preview = document.getElementById('logTimePreview');
            if (!hInput || !mInput || !preview) return;

            let h = parseInt(hInput.value) || 0;
            let m = parseInt(mInput.value) || 0;

            if (m >= 60) {
                h += Math.floor(m / 60);
                m = m % 60;
                hInput.value = h;
                mInput.value = m;
            }

            if (h === 0 && m === 0) {
                preview.innerText = '0 min';
            } else if (h === 0) {
                preview.innerText = `${m} min`;
            } else if (m === 0) {
                preview.innerText = `${h}h`;
            } else {
                preview.innerText = `${h}h ${String(m).padStart(2, '0')}min`;
            }
        }

        function addStudyTime(hAdd, mAdd) {
            const hInput = document.getElementById('log_horas_inteiras');
            const mInput = document.getElementById('log_minutos');
            if (!hInput || !mInput) return;

            let curH = parseInt(hInput.value) || 0;
            let curM = parseInt(mInput.value) || 0;

            let totalM = (curH * 60) + curM + (hAdd * 60) + mAdd;
            let newH = Math.floor(totalM / 60);
            let newM = totalM % 60;

            hInput.value = Math.min(24, newH);
            mInput.value = newM;
            updateLogTimePreview();
        }

        function resetStudyTime() {
            const hInput = document.getElementById('log_horas_inteiras');
            const mInput = document.getElementById('log_minutos');
            if (hInput) hInput.value = 0;
            if (mInput) mInput.value = 0;
            updateLogTimePreview();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTab = localStorage.getItem('activeStudyTab');
            if (savedTab === 'simulador') {
                switchStudyTab('simulador');
            }
            updateLogTimePreview();
        });

        function editLogModal(log) {
            const updateUrlPattern = "{{ route('estudos.logs.update', ':id') }}";
            document.getElementById('formEditLog').action = updateUrlPattern.replace(':id', log.id);
            document.getElementById('edit_log_goal_id').value = log.study_goal_id || '';
            document.getElementById('edit_log_data').value = log.data;
            document.getElementById('edit_log_horas_inteiras').value = log.horas_inteiras;
            document.getElementById('edit_log_minutos').value = log.minutos;
            document.getElementById('edit_log_observacoes').value = log.observacoes || '';
            updateEditLogTimePreview();
            document.getElementById('modalEditLog').style.display = 'flex';
        }

        function closeEditLogModal() {
            document.getElementById('modalEditLog').style.display = 'none';
        }

        function updateEditLogTimePreview() {
            const h = parseInt(document.getElementById('edit_log_horas_inteiras').value) || 0;
            const m = parseInt(document.getElementById('edit_log_minutos').value) || 0;
            document.getElementById('editLogTimePreview').innerText = `${h}h ${m < 10 ? '0' + m : m}min`;
        }

        function addEditStudyTime(hAdd, mAdd) {
            let currentH = parseInt(document.getElementById('edit_log_horas_inteiras').value) || 0;
            let currentM = parseInt(document.getElementById('edit_log_minutos').value) || 0;
            currentH += hAdd;
            currentM += mAdd;
            if (currentM >= 60) {
                currentH += Math.floor(currentM / 60);
                currentM = currentM % 60;
            }
            document.getElementById('edit_log_horas_inteiras').value = currentH;
            document.getElementById('edit_log_minutos').value = currentM;
            updateEditLogTimePreview();
        }

        function resetEditStudyTime() {
            document.getElementById('edit_log_horas_inteiras').value = 0;
            document.getElementById('edit_log_minutos').value = 0;
            updateEditLogTimePreview();
        }

        window.onclick = function(event) {
            const modalGoal = document.getElementById('modalGoal');
            const modalEditLog = document.getElementById('modalEditLog');
            if (event.target == modalGoal) {
                modalGoal.style.display = 'none';
            }
            if (event.target == modalEditLog) {
                modalEditLog.style.display = 'none';
            }
        }
    </script>
</body>
</html>
