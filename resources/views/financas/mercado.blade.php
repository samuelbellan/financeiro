<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supermercado & Notas Fiscais | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-light: #ecfdf5;
            --accent: #6366f1;
            --danger: #ef4444;
            --bg-card: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        /* ── KPIs & Cards ────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .kpi-card {
            background: white;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            border-left: 5px solid var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .kpi-card.total { border-left-color: #10b981; }
        .kpi-card.notas { border-left-color: #3b82f6; }
        .kpi-card.ticket { border-left-color: #f59e0b; }
        .kpi-card.campeao { border-left-color: #ec4899; }

        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0.5rem 0 0.25rem;
        }
        .kpi-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* ── Charts Grid ─────────────────────────────── */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .chart-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chart-container {
            height: 260px;
            position: relative;
        }

        /* ── Navigation & Filter Bar ─────────────────── */
        .period-nav {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .preset-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            background: #f3f4f6;
            transition: all 0.2s;
        }
        .preset-badge:hover, .preset-badge.active {
            background: var(--primary);
            color: white;
        }

        /* ── Category Badges ─────────────────────────── */
        .cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.725rem;
            font-weight: 700;
        }
        .cat-carnes     { background: #fee2e2; color: #b91c1c; }
        .cat-hortifruti { background: #dcfce7; color: #15803d; }
        .cat-laticínios, .cat-laticinios { background: #dbeafe; color: #1d4ed8; }
        .cat-padaria    { background: #fef3c7; color: #b45309; }
        .cat-limpeza    { background: #f3e8ff; color: #7e22ce; }
        .cat-higiene    { background: #fce7f3; color: #be185d; }
        .cat-bebidas    { background: #cffafe; color: #0e7490; }
        .cat-mercearia  { background: #ffedd5; color: #c2410c; }
        .cat-outros     { background: #f3f4f6; color: #4b5563; }

        /* ── Category Accordion & Cards ──────────────── */
        .category-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .category-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
        }
        .category-header {
            padding: 1.15rem 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s;
        }
        .category-header:hover {
            background: #f8fafc;
        }
        .category-body {
            padding: 0;
            display: block;
        }
        .category-pill {
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #f1f5f9;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s;
        }
        .category-pill:hover, .category-pill.active {
            background: #1e293b;
            color: white;
        }

        /* ── Receipt Cards (Accordion) ───────────────── */
        .receipt-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .receipt-card:hover {
            border-color: #cbd5e1;
        }
        .receipt-header {
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }
        .receipt-header:hover {
            background: #f1f5f9;
        }
        .receipt-body {
            padding: 1rem 1.5rem;
            display: none;
        }
        .receipt-body.open {
            display: block;
        }

        /* ── Tables ─────────────────────────────────── */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .table-custom th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.725rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }
        .table-custom td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
        }
        .table-custom tr:last-child td {
            border-bottom: none;
        }
        .table-custom tr:hover td {
            background: #fafafa;
        }

        .progress-bar-container {
            width: 100%;
            max-width: 100px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        /* ── Action Buttons ─────────────────────────── */
        .btn-main {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-main:hover { background: var(--primary-hover); }

        .btn-photo {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.775rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s;
        }
        .btn-photo:hover {
            background: #2563eb;
            color: white;
        }

        /* ── Modal Visualizador de Imagem ────────────── */
        .photo-modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .photo-modal-container {
            background: #0f172a;
            width: 95%;
            max-width: 900px;
            height: 90vh;
            border-radius: 1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }
        .photo-modal-header {
            padding: 1rem 1.5rem;
            background: #1e293b;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        .photo-modal-body {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #020617;
            cursor: grab;
        }
        .photo-modal-body:active {
            cursor: grabbing;
        }
        .photo-viewer-img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            transition: transform 0.2s ease-out;
            transform-origin: center center;
            user-select: none;
        }
        .photo-toolbar {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(4px);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid #475569;
            z-index: 10;
        }
        .photo-tool-btn {
            background: none;
            border: none;
            color: #e2e8f0;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }
        .photo-tool-btn:hover {
            background: #334155;
            color: white;
        }

        /* ── Tabs ────────────────────────────────────── */
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid var(--border);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tab-item {
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .tab-item:hover { color: var(--text-main); }
        .tab-item.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Financeiro</h2>
                <button type="button" class="sidebar-toggle-btn js-toggle-sidebar" title="Ocultar barra lateral (Ctrl + \)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line><path d="M15 9l-3 3 3 3"></path></svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">
                    <p class="nav-section-title">Finanças & Gastos</p>

                    <a href="{{ route('financas.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Finanças de Casa</span>
                    </a>

                    <a href="{{ route('financas.mercado.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>Supermercado & NFs</span>
                    </a>

                    <a href="{{ route('cartoes.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Meus Cartões</span>
                    </a>

                    <a href="{{ route('categorias.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Categorias</span>
                    </a>
                </div>

                <div class="nav-section">
                    <p class="nav-section-title">Outros Módulos</p>
                    <a href="{{ route('fiscal.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Concursos Fiscais</span>
                    </a>
                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Horas de Estudo</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button type="button" class="btn-toggle-sidebar js-toggle-sidebar" title="Alternar barra lateral (Ctrl + \)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <div>
                        <h1 style="display: flex; align-items: center; gap: 0.5rem;">🛒 Gastos de Supermercado</h1>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">Detalhamento analítico por categorias, itens e notas fiscais ({{ $periodoLabel }})</p>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem;">
                    <button onclick="openUploadModal()" class="btn-main">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Enviar Nota Fiscal
                    </button>
                </div>
            </header>

            <div class="content-body" style="padding: 1.5rem 0;">
                @if(session('success'))
                    <div style="background: #dcfce7; color: #166534; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-weight: 600; border: 1px solid #bbf7d0;">
                        ✅ {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div style="background: #fee2e2; color: #991b1b; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; font-weight: 600; border: 1px solid #fecaca;">
                        ⚠️ {{ session('error') }}
                    </div>
                @endif

                <!-- Barra de Navegação de Período & Filtros -->
                <div class="period-nav">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="{{ route('financas.mercado.index', ['preset' => 'mes_atual']) }}" class="preset-badge {{ $preset === 'mes_atual' ? 'active' : '' }}">Mês Atual</a>
                        <a href="{{ route('financas.mercado.index', ['preset' => 'mes_anterior']) }}" class="preset-badge {{ $preset === 'mes_anterior' ? 'active' : '' }}">Mês Anterior</a>
                        <a href="{{ route('financas.mercado.index', ['preset' => 'ultimos_3_meses']) }}" class="preset-badge {{ $preset === 'ultimos_3_meses' ? 'active' : '' }}">Últimos 3 Meses</a>
                        <a href="{{ route('financas.mercado.index', ['preset' => 'ano_atual']) }}" class="preset-badge {{ $preset === 'ano_atual' ? 'active' : '' }}">Ano {{ now()->year }}</a>
                        <a href="{{ route('financas.mercado.index', ['preset' => 'todos']) }}" class="preset-badge {{ $preset === 'todos' ? 'active' : '' }}">Todo Histórico</a>
                    </div>

                    <!-- Filtro Personalizado por Datas & Busca -->
                    <form method="GET" action="{{ route('financas.mercado.index') }}" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <input type="hidden" name="preset" value="personalizado">
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-input" style="padding: 0.35rem 0.5rem; font-size: 0.8rem; width: auto;" title="Data Inicial">
                        <span style="color: var(--text-muted); font-size: 0.8rem;">até</span>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="form-input" style="padding: 0.35rem 0.5rem; font-size: 0.8rem; width: auto;" title="Data Final">
                        
                        <input type="text" name="busca" value="{{ $filtroTexto }}" placeholder="Buscar produto ou mercado..." class="form-input" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; width: 180px;">
                        
                        <button type="submit" class="btn-main" style="padding: 0.4rem 0.85rem; font-size: 0.8rem;">Filtrar</button>
                        @if($filtroTexto || $filtroCategoria || $filtroEstabelecimento || $preset === 'personalizado')
                            <a href="{{ route('financas.mercado.index') }}" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">Limpar</a>
                        @endif
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card total">
                        <div class="kpi-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Total Gasto no Período
                        </div>
                        <div class="kpi-value" style="color: #059669;">
                            R$ {{ number_format($totalGasto, 2, ',', '.') }}
                        </div>
                        <div class="kpi-sub">{{ number_format($totalItensQtd, 0, ',', '.') }} itens comprados</div>
                    </div>

                    <div class="kpi-card notas">
                        <div class="kpi-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Notas Fiscais
                        </div>
                        <div class="kpi-value" style="color: #2563eb;">
                            {{ $qtdNotas }}
                        </div>
                        <div class="kpi-sub">cupons fiscais registrados</div>
                    </div>

                    <div class="kpi-card ticket">
                        <div class="kpi-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            Ticket Médio / Compra
                        </div>
                        <div class="kpi-value" style="color: #d97706;">
                            R$ {{ number_format($ticketMedio, 2, ',', '.') }}
                        </div>
                        <div class="kpi-sub">média gasta por ida ao mercado</div>
                    </div>

                    <div class="kpi-card campeao">
                        <div class="kpi-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ec4899" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            Maior Categoria de Gasto
                        </div>
                        <div class="kpi-value" style="color: #db2777; font-size: 1.35rem;">
                            {{ $categoriaCampeao['categoria'] ?? 'Nenhum' }}
                        </div>
                        <div class="kpi-sub">
                            @if($categoriaCampeao)
                                R$ {{ number_format($categoriaCampeao['total'], 2, ',', '.') }} ({{ $categoriaCampeaoPercent }}% do total)
                            @else
                                Sem dados no período
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Gráficos Analíticos -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-title">
                            <span>🥩 Distribuição por Categoria</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Clique para filtrar</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoriesDoughnutChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-title">
                            <span>📈 Histórico de Gastos (Últimos 6 Meses)</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">R$ mensal</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyHistoryBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Abas de Visualização -->
                <div class="tab-nav">
                    <div class="tab-item active" onclick="switchTab('tab-categorias', this)">
                        🥬 Por Categorias ({{ count($itensPorCategoria) }})
                    </div>
                    <div class="tab-item" onclick="switchTab('tab-notas', this)">
                        🧾 Por Notas Fiscais ({{ $qtdNotas }})
                    </div>
                    <div class="tab-item" onclick="switchTab('tab-itens', this)">
                        🛒 Todos os Itens ({{ count($todosItens) }})
                    </div>
                    <div class="tab-item" onclick="switchTab('tab-ranking', this)">
                        🏆 Top 10 Produtos Mais Caros
                    </div>
                </div>

                <!-- ABA 1: Agrupado por Categorias (Hortifruti, Laticínios, Carnes, Bebidas, etc.) -->
                <div id="tab-categorias" class="tab-content">
                    <!-- Atalhos Rápidos para Categorias -->
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                        <button type="button" class="category-pill active" onclick="filterCategoryCards('all', this)">
                            ✨ Todas as Categorias ({{ count($itensPorCategoria) }})
                        </button>
                        @php
                            $emojis = [
                                'carnes' => '🥩',
                                'hortifruti' => '🥬',
                                'laticínios' => '🥛',
                                'laticinios' => '🥛',
                                'padaria' => '🍞',
                                'limpeza' => '🧹',
                                'higiene' => '🧴',
                                'bebidas' => '🥤',
                                'mercearia' => '🌾',
                                'outros' => '📦'
                            ];
                        @endphp
                        @foreach($itensPorCategoria as $catKey => $catData)
                            @php
                                $emoji = $emojis[strtolower($catKey)] ?? '📦';
                            @endphp
                            <button type="button" class="category-pill" onclick="filterCategoryCards('{{ Str::slug($catKey) }}', this)">
                                {{ $emoji }} {{ $catKey }} <span style="opacity: 0.75; font-size: 0.75rem;">(R$ {{ number_format($catData['total'], 2, ',', '.') }})</span>
                            </button>
                        @endforeach
                    </div>

                    @if($itensPorCategoria->isEmpty())
                        <div style="background: white; border-radius: 1rem; padding: 3rem; text-align: center; border: 1px dashed var(--border);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🥬</div>
                            <h3 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.5rem;">Nenhum item registrado no período</h3>
                            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 450px; margin: 0 auto 1.5rem;">
                                Envie fotos de notas fiscais pelo Telegram ou clique no botão acima para enviar via navegador.
                            </p>
                            <button onclick="openUploadModal()" class="btn-main">Enviar Foto de Nota Fiscal</button>
                        </div>
                    @else
                        @foreach($itensPorCategoria as $catKey => $catData)
                            @php
                                $catSlug = Str::slug($catKey);
                                $emoji = $emojis[strtolower($catKey)] ?? '📦';
                            @endphp
                            <div class="category-card cat-card-item" id="cat-group-{{ $catSlug }}">
                                <div class="category-header" onclick="toggleCategoryGroup('{{ $catSlug }}')">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 48px; height: 48px; border-radius: 0.75rem; background: {{ $catData['cor'] }}15; color: {{ $catData['cor'] }}; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; border: 1px solid {{ $catData['cor'] }}30;">
                                            {{ $emoji }}
                                        </div>
                                        <div>
                                            <div style="font-weight: 800; font-size: 1.15rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                                                <span>{{ $catKey }}</span>
                                                <span class="cat-badge cat-{{ strtolower($catKey) }}">{{ count($catData['produtos']) }} produtos distintos</span>
                                            </div>
                                            <div style="color: var(--text-muted); font-size: 0.775rem; margin-top: 3px;">
                                                <span>{{ (float)$catData['qtd_itens'] }} itens comprados</span>
                                                <span> • </span>
                                                <span>{{ $catData['compras_count'] }} registros</span>
                                                <span> • </span>
                                                <span style="font-weight: 700; color: {{ $catData['cor'] }};">{{ $catData['pct_total'] }}% do total de mercado</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                                        <div style="text-align: right;">
                                            <div style="font-size: 1.35rem; font-weight: 800; color: {{ $catData['cor'] }};">
                                                R$ {{ number_format($catData['total'], 2, ',', '.') }}
                                            </div>
                                            <div style="font-size: 0.725rem; color: var(--text-muted);">Clique para expandir/recolher</div>
                                        </div>
                                        <div style="font-size: 1.2rem; color: var(--text-muted);">
                                            ▾
                                        </div>
                                    </div>
                                </div>

                                <div class="category-body" id="cat-body-{{ $catSlug }}">
                                    <table class="table-custom">
                                        <thead>
                                            <tr>
                                                <th style="width: 35%;">Item / Descrição do Produto</th>
                                                <th style="text-align: center; width: 12%;">Qtd Total</th>
                                                <th style="text-align: right; width: 15%;">Preço Médio</th>
                                                <th style="text-align: right; width: 15%;">Total Gasto</th>
                                                <th style="width: 15%;">% da Categoria</th>
                                                <th style="text-align: center; width: 8%;">Histórico</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($catData['produtos'] as $prodIdx => $prod)
                                                @php
                                                    $prodId = $catSlug . '-' . $prodIdx;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;">
                                                            {{ $prod['nome'] }}
                                                        </div>
                                                        <div style="font-size: 0.725rem; color: var(--text-muted);">
                                                            Comprado {{ $prod['compras_count'] }}x no período
                                                        </div>
                                                    </td>
                                                    <td style="text-align: center; font-weight: 600; color: var(--text-main);">
                                                        {{ (float)$prod['quantidade'] }}
                                                    </td>
                                                    <td style="text-align: right; color: var(--text-muted);">
                                                        R$ {{ number_format($prod['preco_medio'], 2, ',', '.') }}
                                                    </td>
                                                    <td style="text-align: right; font-weight: 800; color: {{ $catData['cor'] }}; font-size: 0.95rem;">
                                                        R$ {{ number_format($prod['valor_total'], 2, ',', '.') }}
                                                    </td>
                                                    <td>
                                                        <div class="progress-bar-container">
                                                            <div class="progress-bar-fill" style="width: {{ $prod['pct_categoria'] }}%; background: {{ $catData['cor'] }};"></div>
                                                        </div>
                                                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">{{ $prod['pct_categoria'] }}%</span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <button type="button" onclick="toggleProductHistory('{{ $prodId }}')" class="btn-photo" style="padding: 3px 8px; font-size: 0.725rem;" title="Ver detalhes de cada compra">
                                                            🔍 Ver ({{ $prod['compras_count'] }})
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Linha de Detalhamento das Compras Individuais do Produto -->
                                                <tr id="prod-history-{{ $prodId }}" style="display: none; background: #f8fafc;">
                                                    <td colspan="6" style="padding: 1rem 1.5rem;">
                                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                                            <span>📅 Detalhamento das compras de: <strong>{{ $prod['nome'] }}</strong></span>
                                                        </div>
                                                        <div style="background: white; border-radius: 0.5rem; border: 1px solid var(--border); overflow: hidden;">
                                                            <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                                                                <thead>
                                                                    <tr style="background: #f1f5f9;">
                                                                        <th style="padding: 6px 12px; text-align: left;">Data</th>
                                                                        <th style="padding: 6px 12px; text-align: left;">Supermercado</th>
                                                                        <th style="padding: 6px 12px; text-align: center;">Qtd</th>
                                                                        <th style="padding: 6px 12px; text-align: right;">Preço Unit.</th>
                                                                        <th style="padding: 6px 12px; text-align: right;">Total Pago</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($prod['registros'] as $reg)
                                                                        <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                            <td style="padding: 6px 12px; color: var(--text-muted);">{{ $reg->data_compra ? $reg->data_compra->format('d/m/Y') : '-' }}</td>
                                                                            <td style="padding: 6px 12px; font-weight: 600;">{{ $reg->estabelecimento ?: 'Supermercado' }}</td>
                                                                            <td style="padding: 6px 12px; text-align: center;">{{ (float)$reg->quantidade }}</td>
                                                                            <td style="padding: 6px 12px; text-align: right; color: var(--text-muted);">R$ {{ number_format($reg->valor_unitario, 2, ',', '.') }}</td>
                                                                            <td style="padding: 6px 12px; text-align: right; font-weight: 700; color: #059669;">R$ {{ number_format($reg->valor_total, 2, ',', '.') }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- ABA 2: Agrupado por Notas Fiscais -->
                <div id="tab-notas" class="tab-content" style="display: none;">
                    @if($notasFiscais->isEmpty())
                        <div style="background: white; border-radius: 1rem; padding: 3rem; text-align: center; border: 1px dashed var(--border);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🧾</div>
                            <h3 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.5rem;">Nenhuma nota fiscal encontrada no período</h3>
                            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 450px; margin: 0 auto 1.5rem;">
                                Envie fotos de notas fiscais pelo Telegram ou clique no botão acima para carregar uma imagem diretamente do seu computador ou celular.
                            </p>
                            <button onclick="openUploadModal()" class="btn-main">Enviar Foto de Nota Fiscal</button>
                        </div>
                    @else
                        @foreach($notasFiscais as $nf)
                            <div class="receipt-card">
                                <div class="receipt-header" onclick="toggleReceipt({{ $nf->id }})">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="width: 44px; height: 44px; border-radius: 0.75rem; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; font-weight: 700;">
                                            🏬
                                        </div>
                                        <div>
                                            <div style="font-weight: 800; font-size: 1.05rem; color: var(--text-main);">
                                                {{ $nf->estabelecimento ?: 'Supermercado' }}
                                            </div>
                                            <div style="color: var(--text-muted); font-size: 0.775rem; display: flex; align-items: center; gap: 0.5rem; margin-top: 2px;">
                                                <span>📅 {{ $nf->data_compra ? $nf->data_compra->format('d/m/Y H:i') : $nf->created_at->format('d/m/Y H:i') }}</span>
                                                <span>•</span>
                                                <span>{{ $nf->itens->count() }} itens</span>
                                                <span>•</span>
                                                @if($nf->forma_pagamento === 'cartao')
                                                    <span style="background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.7rem;">💳 {{ $nf->cartao_nome ?: 'Cartão' }}</span>
                                                @else
                                                    <span style="background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.7rem;">💵 Caixa / Conta</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="text-align: right;">
                                            <div style="font-size: 1.25rem; font-weight: 800; color: #059669;">
                                                R$ {{ number_format($nf->valor_total, 2, ',', '.') }}
                                            </div>
                                            <div style="font-size: 0.725rem; color: var(--text-muted);">Clique para ver itens</div>
                                        </div>

                                        @if($nf->foto_url)
                                            <button type="button" onclick="event.stopPropagation(); viewPhoto('{{ $nf->foto_url }}', '{{ $nf->estabelecimento }}', '{{ $nf->data_compra ? $nf->data_compra->format('d/m/Y') : '' }}', '{{ number_format($nf->valor_total, 2, ',', '.') }}')" class="btn-photo">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                                Ver Foto da NF
                                            </button>
                                        @endif

                                        <form method="POST" action="{{ route('financas.mercado.notas.destroy', $nf->id) }}" onsubmit="event.stopPropagation(); return confirm('Deseja excluir esta Nota Fiscal e seus itens?');" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="background: none; border: none; color: #ef4444; padding: 6px; cursor: pointer; border-radius: 6px;" title="Excluir Nota Fiscal">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div id="receipt-body-{{ $nf->id }}" class="receipt-body">
                                    <table class="table-custom">
                                        <thead>
                                            <tr>
                                                <th>Produto / Item</th>
                                                <th>Categoria</th>
                                                <th style="text-align: center;">Qtd</th>
                                                <th style="text-align: right;">Preço Unit.</th>
                                                <th style="text-align: right;">Total</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($nf->itens as $item)
                                                @php
                                                    $catLower = strtolower($item->categoria_item ?: 'outros');
                                                    $emoji = $emojis[$catLower] ?? '📦';
                                                @endphp
                                                <tr id="item-row-{{ $item->id }}">
                                                    <td style="font-weight: 600;">{{ $item->nome_item }}</td>
                                                    <td>
                                                        <span class="cat-badge cat-{{ $catLower }}">
                                                            {{ $emoji }} {{ $item->categoria_item }}
                                                        </span>
                                                    </td>
                                                    <td style="text-align: center; color: var(--text-muted);">{{ (float)$item->quantidade }}</td>
                                                    <td style="text-align: right; color: var(--text-muted);">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                                    <td style="text-align: right; font-weight: 700; color: var(--text-main);">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                                    <td style="text-align: center;">
                                                        <button type="button" onclick="deleteItem({{ $item->id }})" style="background: none; border: none; color: #9ca3af; cursor: pointer;" title="Excluir item">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Nenhum item detalhado nesta nota.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- ABA 3: Todos os Itens Consolidados -->
                <div id="tab-itens" class="tab-content" style="display: none;">
                    <div style="background: white; border-radius: 1rem; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Estabelecimento</th>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th style="text-align: center;">Qtd</th>
                                    <th style="text-align: right;">Preço Unit.</th>
                                    <th style="text-align: right;">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todosItens as $it)
                                    @php
                                        $catLower = strtolower($it->categoria_item ?: 'outros');
                                        $emoji = $emojis[$catLower] ?? '📦';
                                    @endphp
                                    <tr>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $it->data_compra ? $it->data_compra->format('d/m/Y') : '-' }}</td>
                                        <td style="font-weight: 600; color: var(--text-main);">{{ $it->estabelecimento ?: 'Supermercado' }}</td>
                                        <td style="font-weight: 700;">{{ $it->nome_item }}</td>
                                        <td>
                                            <span class="cat-badge cat-{{ $catLower }}">
                                                {{ $emoji }} {{ $it->categoria_item }}
                                            </span>
                                        </td>
                                        <td style="text-align: center; color: var(--text-muted);">{{ (float)$it->quantidade }}</td>
                                        <td style="text-align: right; color: var(--text-muted);">R$ {{ number_format($it->valor_unitario, 2, ',', '.') }}</td>
                                        <td style="text-align: right; font-weight: 800; color: #059669;">R$ {{ number_format($it->valor_total, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Nenhum item encontrado no filtro atual.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ABA 4: Ranking Top 10 Produtos Mais Caros -->
                <div id="tab-ranking" class="tab-content" style="display: none;">
                    <div style="background: white; border-radius: 1rem; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">Posição</th>
                                    <th>Nome do Produto</th>
                                    <th>Categoria</th>
                                    <th style="text-align: center;">Vezes Comprado</th>
                                    <th style="text-align: center;">Qtd Total</th>
                                    <th style="text-align: right;">Total Acumulado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topItens as $idx => $top)
                                    @php
                                        $catLower = strtolower($top['categoria'] ?: 'outros');
                                    @endphp
                                    <tr>
                                        <td style="font-weight: 800; font-size: 1.1rem; color: {{ $idx === 0 ? '#eab308' : ($idx === 1 ? '#94a3b8' : ($idx === 2 ? '#b45309' : 'var(--text-muted)')) }};">
                                            #{{ $idx + 1 }}
                                        </td>
                                        <td style="font-weight: 700; font-size: 0.95rem;">{{ $top['nome'] }}</td>
                                        <td>
                                            <span class="cat-badge cat-{{ $catLower }}">{{ $top['categoria'] }}</span>
                                        </td>
                                        <td style="text-align: center; color: var(--text-muted);">{{ $top['compras_count'] }}x</td>
                                        <td style="text-align: center; font-weight: 600;">{{ (float)$top['quantidade_total'] }}</td>
                                        <td style="text-align: right; font-weight: 800; color: #059669; font-size: 1rem;">
                                            R$ {{ number_format($top['valor_total'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Sem dados para gerar ranking no período.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL DE VISUALIZAÇÃO DE FOTO DA NOTA FISCAL -->
    <div id="photoViewerModal" class="photo-modal-overlay" onclick="closePhotoViewer()">
        <div class="photo-modal-container" onclick="event.stopPropagation()">
            <div class="photo-modal-header">
                <div>
                    <h3 id="photoModalTitle" style="font-size: 1.1rem; font-weight: 700; margin: 0;">Nota Fiscal</h3>
                    <p id="photoModalSubtitle" style="font-size: 0.8rem; color: #94a3b8; margin: 2px 0 0;"></p>
                </div>
                <button type="button" onclick="closePhotoViewer()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

            <div class="photo-modal-body" id="photoViewerBody">
                <img id="photoViewerImg" src="" alt="Nota Fiscal" class="photo-viewer-img">
                
                <!-- Barra de Ferramentas Flutuante -->
                <div class="photo-toolbar">
                    <button type="button" class="photo-tool-btn" onclick="zoomIn()" title="Aumentar Zoom">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                        Zoom +
                    </button>
                    <button type="button" class="photo-tool-btn" onclick="zoomOut()" title="Diminuir Zoom">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                        Zoom -
                    </button>
                    <button type="button" class="photo-tool-btn" onclick="rotatePhoto()" title="Girar 90°">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                        Girar
                    </button>
                    <button type="button" class="photo-tool-btn" onclick="resetPhoto()" title="Resetar Visualização">
                        Reset
                    </button>
                    <a id="photoDownloadBtn" href="#" download class="photo-tool-btn" style="text-decoration: none;" title="Baixar Arquivo Original">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Baixar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE UPLOAD MANUAL DE NOTA FISCAL -->
    <div id="uploadModal" class="photo-modal-overlay" onclick="closeUploadModal()">
        <div style="background: white; width: 100%; max-width: 520px; border-radius: 1rem; padding: 1.75rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);" onclick="event.stopPropagation()">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    📸 Enviar Foto de Nota Fiscal
                </h3>
                <button type="button" onclick="closeUploadModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form method="POST" action="{{ route('financas.mercado.upload') }}" enctype="multipart/form-data" id="uploadForm" onsubmit="handleUploadSubmit()">
                @csrf
                
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Foto do Cupom / Nota Fiscal *
                    </label>
                    <input type="file" name="foto_nf" accept="image/*" required class="form-input" style="padding: 0.5rem;" onchange="previewUpload(event)">
                    <div id="uploadPreviewContainer" style="margin-top: 0.75rem; display: none; text-align: center;">
                        <img id="uploadPreviewImg" src="" style="max-height: 180px; border-radius: 0.5rem; border: 1px solid var(--border);">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Forma de Pagamento
                    </label>
                    <select name="transacao_destino" id="destSelect" class="form-input" onchange="toggleCartaoSelect(this.value)">
                        <option value="casa">Contas da Casa / Caixa (Débito, Pix ou Dinheiro)</option>
                        <option value="cartao">Cartão de Crédito</option>
                    </select>
                </div>

                <div id="cartaoSelectGroup" style="margin-bottom: 1.25rem; display: none;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Selecione o Cartão de Crédito
                    </label>
                    <select name="cartao_id" class="form-input">
                        @foreach($cartoes as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }} ({{ $c->bandeira }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Legenda / Observação Extra (Opcional)
                    </label>
                    <input type="text" name="legenda" placeholder="Ex: Mercado mensal em 2x..." class="form-input">
                </div>

                <div id="uploadLoading" style="display: none; text-align: center; margin-bottom: 1.25rem; color: var(--primary); font-weight: 700;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⏳</div>
                    Lendo nota fiscal e categorizando produtos com IA... Por favor, aguarde alguns segundos.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" onclick="closeUploadModal()" class="preset-badge" style="border: none; cursor: pointer; padding: 0.6rem 1.25rem;">Cancelar</button>
                    <button type="submit" id="btnSubmitUpload" class="btn-main" style="padding: 0.6rem 1.5rem;">Processar com IA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts de Interatividade e Gráficos -->
    <script>
        // ── Tabs Navigation ──────────────────────────────────────────────────
        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.tab-item').forEach(i => i.classList.remove('active'));
            document.getElementById(tabId).style.display = 'block';
            if (el) el.classList.add('active');
        }

        // ── Toggle Category Accordion ────────────────────────────────────────
        function toggleCategoryGroup(slug) {
            const body = document.getElementById('cat-body-' + slug);
            if (body) {
                body.style.display = body.style.display === 'none' ? 'block' : 'none';
            }
        }

        // ── Filter Category Cards ────────────────────────────────────────────
        function filterCategoryCards(slug, btn) {
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');

            if (slug === 'all') {
                document.querySelectorAll('.cat-card-item').forEach(card => card.style.display = 'block');
            } else {
                document.querySelectorAll('.cat-card-item').forEach(card => {
                    if (card.id === 'cat-group-' + slug) {
                        card.style.display = 'block';
                        const body = document.getElementById('cat-body-' + slug);
                        if (body) body.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        }

        // ── Toggle Individual Product Purchase History ───────────────────────
        function toggleProductHistory(prodId) {
            const row = document.getElementById('prod-history-' + prodId);
            if (row) {
                row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
            }
        }

        // ── Toggle Receipt Items Accordion ───────────────────────────────────
        function toggleReceipt(id) {
            const body = document.getElementById('receipt-body-' + id);
            if (body) {
                body.classList.toggle('open');
            }
        }

        // ── Delete Item via Fetch ────────────────────────────────────────────
        function deleteItem(itemId) {
            if (!confirm('Deseja excluir este item da nota?')) return;

            fetch(`/financas/mercado/itens/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('item-row-' + itemId);
                    if (row) row.remove();
                }
            });
        }

        // ── Photo Viewer Modal Controls ──────────────────────────────────────
        let currentZoom = 1;
        let currentRotation = 0;

        function viewPhoto(url, title, date, total) {
            currentZoom = 1;
            currentRotation = 0;
            const img = document.getElementById('photoViewerImg');
            img.src = url;
            img.style.transform = `scale(1) rotate(0deg)`;

            document.getElementById('photoModalTitle').innerText = title || 'Nota Fiscal';
            document.getElementById('photoModalSubtitle').innerText = `${date} • R$ ${total}`;
            document.getElementById('photoDownloadBtn').href = url;
            document.getElementById('photoViewerModal').style.display = 'flex';
        }

        function closePhotoViewer() {
            document.getElementById('photoViewerModal').style.display = 'none';
        }

        function zoomIn() {
            currentZoom = Math.min(currentZoom + 0.3, 3);
            updatePhotoTransform();
        }

        function zoomOut() {
            currentZoom = Math.max(currentZoom - 0.3, 0.5);
            updatePhotoTransform();
        }

        function rotatePhoto() {
            currentRotation = (currentRotation + 90) % 360;
            updatePhotoTransform();
        }

        function resetPhoto() {
            currentZoom = 1;
            currentRotation = 0;
            updatePhotoTransform();
        }

        function updatePhotoTransform() {
            const img = document.getElementById('photoViewerImg');
            img.style.transform = `scale(${currentZoom}) rotate(${currentRotation}deg)`;
        }

        // ── Upload Modal Controls ────────────────────────────────────────────
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }
        function toggleCartaoSelect(val) {
            document.getElementById('cartaoSelectGroup').style.display = val === 'cartao' ? 'block' : 'none';
        }
        function previewUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('uploadPreviewImg').src = e.target.result;
                    document.getElementById('uploadPreviewContainer').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
        function handleUploadSubmit() {
            document.getElementById('btnSubmitUpload').disabled = true;
            document.getElementById('uploadLoading').style.display = 'block';
        }

        // ── Chart.js Initialization ──────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Doughnut Chart de Categorias
            const catData = @json($gastosPorCategoria);
            const catLabels = Object.keys(catData);
            const catValues = catLabels.map(k => catData[k].total);
            const catColors = catLabels.map(k => catData[k].cor);

            if (catLabels.length > 0) {
                new Chart(document.getElementById('categoriesDoughnutChart'), {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{
                            data: catValues,
                            backgroundColor: catColors,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        onClick: (event, elements) => {
                            if (elements.length > 0) {
                                const idx = elements[0].index;
                                const label = catLabels[idx];
                                switchTab('tab-categorias', document.querySelector('.tab-item:nth-child(1)'));
                                const slug = label.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                                const pill = Array.from(document.querySelectorAll('.category-pill')).find(p => p.innerText.toLowerCase().includes(label.toLowerCase()));
                                if (pill) filterCategoryCards(slug, pill);
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 12,
                                    font: { family: 'Inter', size: 11, weight: '600' },
                                    color: '#334155'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        let val = ctx.raw || 0;
                                        let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        let pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return ` R$ ${val.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '68%'
                    }
                });
            }

            // 2. Bar Chart de Histórico Mensal
            const histLabels = @json($historicoLabels);
            const histValues = @json($historicoValores);

            new Chart(document.getElementById('monthlyHistoryBarChart'), {
                type: 'bar',
                data: {
                    labels: histLabels,
                    datasets: [{
                        label: 'Gasto em Mercado (R$)',
                        data: histValues,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        hoverBackgroundColor: '#059669'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                callback: function (value) { return 'R$ ' + value; },
                                font: { family: 'Inter', size: 11 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Inter', size: 11 } }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ` R$ ${ctx.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>