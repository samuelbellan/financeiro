<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador Salarial TJMS | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --bg-glass: rgba(15, 23, 42, 0.6);
            --border-glass: rgba(255, 255, 255, 0.08);
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --accent-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --font-outfit: 'Outfit', 'Inter', sans-serif;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }

        h1, h2, h3, .brand-title, .outfit-font {
            font-family: var(--font-outfit);
        }

        .simulator-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .simulator-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Glassmorphic Cards */
        .glass-card {
            background: var(--bg-glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            margin-bottom: 1.5rem;
        }

        .glass-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #f1f5f9;
            border-bottom: 1px solid var(--border-glass);
            padding-bottom: 0.75rem;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 0.375rem;
        }

        .form-control {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            color: #f8fafc;
            font-size: 0.875rem;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .row-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #6366f1;
        }

        /* Dynamic Table Lists */
        .dynamic-list {
            margin-top: 1rem;
        }

        .dynamic-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 30px;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .dynamic-row-events {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 30px;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .btn-icon {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .btn-icon:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .btn-add {
            background: rgba(99, 102, 241, 0.1);
            border: 1px dashed rgba(99, 102, 241, 0.4);
            color: #818cf8;
            padding: 0.5rem;
            border-radius: 8px;
            width: 100%;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-add:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
            color: #fff;
        }

        /* Action Buttons */
        .btn-submit {
            background: var(--primary-gradient);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-family: var(--font-outfit);
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        /* Tabs System (Arrastável) */
        .tabs-header {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border-glass);
            margin-bottom: 1.5rem;
            overflow-x: auto;
            white-space: nowrap;
            cursor: grab;
            user-select: none;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.3) transparent;
        }
        .tabs-header::-webkit-scrollbar {
            height: 3px;
        }
        .tabs-header::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 3px;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            padding: 0.75rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: var(--font-outfit);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: #f1f5f9;
        }

        .tab-btn.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Chart Section (Arrastável Horizontalmente) */
        .chart-scroll-container {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            cursor: grab;
            user-select: none;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.4) rgba(255, 255, 255, 0.05);
        }
        .chart-scroll-container:active {
            cursor: grabbing;
        }
        .chart-scroll-container::-webkit-scrollbar {
            height: 6px;
        }
        .chart-scroll-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
        }
        .chart-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 4px;
        }
        .chart-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.7);
        }
        .chart-canvas-wrapper {
            position: relative;
            height: 320px;
            min-width: 100%;
        }
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        /* ── Sub-Abas de Análise Avançada (Arrastáveis) ── */
        .subtabs-header {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 0.6rem;
            margin-bottom: 1.25rem;
            cursor: grab;
            user-select: none;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.3) transparent;
        }
        .subtabs-header::-webkit-scrollbar {
            height: 4px;
        }
        .subtabs-header::-webkit-scrollbar-track {
            background: transparent;
        }
        .subtabs-header::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 4px;
        }
        .subtab-btn {
            flex: 0 0 auto;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-glass);
            color: var(--text-muted);
            padding: 0.45rem 0.95rem;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .subtab-btn:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #f8fafc;
            border-color: rgba(99, 102, 241, 0.4);
        }
        .subtab-btn.active {
            background: var(--primary-gradient);
            color: #ffffff;
            border-color: transparent;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }
        .subtab-content {
            display: none;
        }
        .subtab-content.active {
            display: block;
        }

        /* Data Tables */
        .table-responsive {
            overflow-x: auto;
            max-height: 500px;
            border-radius: 12px;
            border: 1px solid var(--border-glass);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        .data-table th, .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-glass);
        }

        .data-table th {
            background: rgba(15, 23, 42, 0.8);
            color: #cbd5e1;
            font-weight: 600;
            font-family: var(--font-outfit);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr {
            transition: background 0.2s;
            cursor: pointer;
        }

        .data-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.08);
        }

        .badge-info {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* Interactive PayslipFacsimile CSS (Exact TJMS facsimile) */
        .facsimile-wrapper {
            background: #fff;
            color: #000;
            padding: 1.5rem;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            line-height: 1.3;
            border: 2px solid #000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .facsimile-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .facsimile-logo {
            width: 50px;
            height: 50px;
            margin-right: 1rem;
        }

        .facsimile-title-block {
            flex-grow: 1;
        }

        .facsimile-title-block h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: bold;
        }

        .facsimile-title-block p {
            margin: 0;
            font-size: 0.7rem;
        }

        .facsimile-credit-notice {
            border: 2px solid #000;
            padding: 0.5rem 1rem;
            font-weight: bold;
            font-size: 0.95rem;
            text-align: center;
        }

        .facsimile-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid #000;
            padding-bottom: 0.25rem;
            margin-bottom: 0.25rem;
            gap: 0.5rem;
        }

        .facsimile-grid div p {
            margin: 0;
        }

        .facsimile-grid div p.label {
            font-size: 0.6rem;
            color: #555;
            text-transform: uppercase;
        }

        .facsimile-grid div p.value {
            font-weight: bold;
            font-size: 0.75rem;
        }

        .facsimile-table-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #000;
            margin-bottom: 0.5rem;
        }

        .facsimile-panel {
            border-right: 1px solid #000;
            padding: 0.25rem;
        }

        .facsimile-panel:last-child {
            border-right: none;
        }

        .facsimile-panel h4 {
            margin: 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 0.25rem;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .facsimile-item-header {
            display: grid;
            grid-template-columns: 40px 1fr 80px;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            padding-bottom: 0.15rem;
            margin-bottom: 0.15rem;
            font-size: 0.65rem;
        }

        .facsimile-item-row {
            display: grid;
            grid-template-columns: 40px 1fr 80px;
            font-size: 0.6875rem;
            margin-bottom: 0.15rem;
        }

        .facsimile-item-row .val {
            text-align: right;
        }

        .facsimile-totals {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1.2fr;
            border: 2px solid #000;
            margin-bottom: 0.5rem;
            background: #f1f5f9;
        }

        .facsimile-totals div {
            border-right: 1px solid #000;
            padding: 0.5rem;
            text-align: center;
        }

        .facsimile-totals div:last-child {
            border-right: none;
        }

        .facsimile-totals div p {
            margin: 0;
        }

        .facsimile-totals div p.lbl {
            font-size: 0.6rem;
            color: #555;
            text-transform: uppercase;
        }

        .facsimile-totals div p.val {
            font-weight: bold;
            font-size: 0.95rem;
        }

        .facsimile-obs {
            border: 1px solid #000;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.65rem;
        }

        .facsimile-obs p {
            margin: 0;
        }

        /* Holerite Multi-sheet Selector */
        .sheet-selector {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 1rem;
            justify-content: center;
        }

        .sheet-tab {
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 0.375rem 0.75rem;
            font-size: 0.7rem;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }

        .sheet-tab.active {
            background: #475569;
            color: #fff;
            border-color: #475569;
        }

        /* Modal Settings */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal-content {
            background: transparent;
            max-width: 820px;
            width: 90%;
            position: relative;
        }

        .btn-close-modal {
            position: absolute;
            top: -40px;
            right: 0;
            background: #fff;
            color: #000;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        /* Classes para Tabelas Dinâmicas de Consignados, Filhos e AQ Permanente */
        .dynamic-row-consignados {
            display: grid;
            grid-template-columns: 2fr 1fr 1.25fr 1.25fr 30px;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .dynamic-row-filhos {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 30px;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .dynamic-row-aq-perm {
            display: grid;
            grid-template-columns: 2fr 1fr 1.25fr 30px;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        /* Badges de Eventos da Evolução Mensal */
        .badge-event {
            padding: 0.15rem 0.35rem;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 500;
            display: inline-block;
            margin: 0.1rem;
            white-space: nowrap;
        }
        .badge-event-purple { background: rgba(139, 92, 246, 0.2); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.3); }
        .badge-event-blue { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-event-indigo { background: rgba(79, 70, 229, 0.2); color: #818cf8; border: 1px solid rgba(79, 70, 229, 0.3); }
        .badge-event-green { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-event-red { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-event-emerald { background: rgba(52, 211, 153, 0.2); color: #6ee7b7; border: 1px solid rgba(52, 211, 153, 0.3); }
        .badge-event-orange { background: rgba(249, 115, 22, 0.2); color: #fdba74; border: 1px solid rgba(249, 115, 22, 0.3); }
        .badge-event-yellow { background: rgba(234, 179, 8, 0.2); color: #fde047; border: 1px solid rgba(234, 179, 8, 0.3); }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Financeiro</h2>
                <button type="button" class="sidebar-toggle-btn js-toggle-sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <path d="M15 9l-3 3 3 3"></path>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('home') }}" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="nav-section">
                    <p class="nav-section-title">Sistemas</p>

                    <a href="{{ route('financas.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>Finanças de Casa</span>
                    </a>

                    <a href="{{ route('estudos.index') }}" class="nav-item">
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

                    <a href="{{ route('salary.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Projetor Salarial</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                    <div class="user-details">
                        <p class="user-name">{{ Auth::user()->name }}</p>
                        <p class="user-email">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Sair
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Body -->
        <main class="main-content">
            <header class="content-header" style="display: flex; align-items: center; gap: 1rem;">
                <button type="button" class="btn-toggle-sidebar js-toggle-sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div>
                    <h1>Simulador & Projetor Salarial (TJMS)</h1>
                    <p>Projete a evolução de proventos e auxílios com o cálculo normativo real</p>
                </div>
            </header>

            <div class="content-body">
                <form id="projector-form" onsubmit="event.preventDefault(); triggerSimulation();">
                    @csrf
                    <div class="simulator-grid">
                        
                        <!-- Left Panel (Inputs) -->
                        <div class="settings-column">

                            <!-- Section 0: Perfis de Simulação -->
                            <div class="glass-card">
                                <h3 class="glass-card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    Perfis de Simulação
                                </h3>
                                
                                <div class="form-group">
                                    <label class="form-label" for="profile_selector">Perfil Ativo</label>
                                    <div style="display:flex; gap:0.5rem;">
                                        <select id="profile_selector" class="form-control" onchange="loadProfile(this.value)" style="flex:1;">
                                            <option value="">-- Usar Valores Padrão --</option>
                                            @foreach ($profiles as $profile)
                                                <option value="{{ $profile->id }}" {{ $activeProfileId == $profile->id ? 'selected' : '' }}>
                                                    {{ $profile->nome }} {{ $profile->is_active ? '(Padrão)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn-submit" onclick="deleteActiveProfile()" style="background:#ef4444; width:auto; padding:0 0.75rem; display:none;" id="btn-delete-profile" title="Excluir Perfil">
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 0.5rem;">
                                    <div style="display:none; margin-bottom:0.75rem;" id="btn-update-container">
                                        <button type="button" class="btn-submit" onclick="updateActiveProfile()" style="background:#10b981; width:100%; display:block; font-size:0.75rem; padding:0.5rem;">
                                            💾 Salvar Alterações no Perfil Selecionado
                                        </button>
                                    </div>
                                    <div style="display:flex; gap:0.5rem;">
                                        <input type="text" id="new_profile_name" class="form-control" placeholder="Nome do novo perfil..." style="flex:1;">
                                        <button type="button" class="btn-submit" onclick="saveProfile()" style="width:auto; padding: 0 1rem; font-size:0.75rem; white-space:nowrap;">
                                            Salvar como Novo Perfil
                                        </button>
                                    </div>
                                    <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                                        <button type="button" class="btn-add" onclick="setProfileDefault()" style="flex:1; font-size:0.7rem; padding:0.4rem; background:#8b5cf6;" id="btn-default-profile">
                                            Definir como Padrão do Sistema
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 1: Dados Funcionais -->
                            <div class="glass-card">
                                <h3 class="glass-card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Dados Funcionais
                                </h3>

                                <div class="form-group">
                                    <label class="form-label" for="dt_exercicio">Data de Entrada em Exercício</label>
                                    <input type="date" id="dt_exercicio" name="servidor[dt_exercicio]" class="form-control" value="{{ $defaultConfig['dt_exercicio'] }}" required>
                                </div>

                                <div class="row-group">
                                    <div class="form-group">
                                        <label class="form-label" for="cargo">Cargo (Grupo)</label>
                                        <select id="cargo" name="servidor[cargo]" class="form-control" required>
                                            <option value="TNSU" {{ $defaultConfig['cargo'] === 'TNSU' ? 'selected' : '' }}>Técnico Nível Superior (TNSU)</option>
                                            <option value="ASSJ" {{ $defaultConfig['cargo'] === 'ASSJ' ? 'selected' : '' }}>Analista Judiciário (ASSJ)</option>
                                            <option value="ESCR" {{ $defaultConfig['cargo'] === 'ESCR' ? 'selected' : '' }}>Escrivão (ESCR)</option>
                                            <option value="ASTI" {{ $defaultConfig['cargo'] === 'ASTI' ? 'selected' : '' }}>Assistente Informática (ASTI)</option>
                                            <option value="AGOP" {{ $defaultConfig['cargo'] === 'AGOP' ? 'selected' : '' }}>Agente de Operações (AGOP)</option>
                                            <option value="TAGE" {{ $defaultConfig['cargo'] === 'TAGE' ? 'selected' : '' }}>Auxiliar Judiciário II (TAGE)</option>
                                            <option value="ARAT" {{ $defaultConfig['cargo'] === 'ARAT' ? 'selected' : '' }}>Artífice (ARAT)</option>
                                            <option value="AGSG" {{ $defaultConfig['cargo'] === 'AGSG' ? 'selected' : '' }}>Serviços Gerais (AGSG)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="referencia_inicial">Ref. Inicial</label>
                                        <select id="referencia_inicial" name="servidor[referencia_inicial]" class="form-control" required>
                                            @for ($i = 1; $i <= 19; $i++)
                                                <option value="{{ $i }}" {{ $defaultConfig['referencia_inicial'] == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                                <div class="row-group">
                                    <div class="form-group">
                                        <label class="form-label" for="aq_permanente_pct">AQ Permanente Base (%)</label>
                                        <input type="number" id="aq_permanente_pct" name="servidor[aq_permanente_pct]" step="0.1" class="form-control" value="{{ $defaultConfig['aq_permanente_pct'] }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="outros_adicionais_pct">Adicional Provimento (%)</label>
                                        <input type="number" id="outros_adicionais_pct" name="servidor[outros_adicionais_pct]" step="0.1" class="form-control" value="{{ $defaultConfig['outros_adicionais_pct'] }}">
                                    </div>
                                </div>

                                <div class="row-group">
                                    <div class="form-group">
                                        <label class="form-label" for="salario_substituicao">Substituição (R$)</label>
                                        <input type="number" id="salario_substituicao" name="servidor[salario_substituicao]" step="0.01" class="form-control" value="{{ $defaultConfig['salario_substituicao'] }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="funcao_comissao_valor">Função/Comissão (R$)</label>
                                        <input type="number" id="funcao_comissao_valor" name="servidor[funcao_comissao_valor]" step="0.01" class="form-control" value="{{ $defaultConfig['funcao_comissao_valor'] }}">
                                    </div>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="regime_integral" name="servidor[regime_integral]" value="1" {{ $defaultConfig['regime_integral'] ? 'checked' : '' }}>
                                    <label for="regime_integral" class="form-label" style="margin:0;">Regime Tempo Integral (20% sobre o base)</label>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="teto_rgps" name="servidor[teto_rgps]" value="1" {{ $defaultConfig['teto_rgps'] ? 'checked' : '' }}>
                                    <label for="teto_rgps" class="form-label" style="margin:0;">Limitar previdência ao Teto do RGPS</label>
                                </div>
                            </div>

                            <!-- Section 2: Deduções e CASSEMS -->
                            <div class="glass-card">
                                <h3 class="glass-card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    Deduções e CASSEMS
                                </h3>

                                <div class="row-group">
                                    <div class="form-group">
                                        <label class="form-label" for="dependentes_irrf">Dep. IRRF</label>
                                        <input type="number" id="dependentes_irrf" name="servidor[dependentes_irrf]" class="form-control" value="{{ $defaultConfig['dependentes_irrf'] }}" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="dependentes_cassems">Dep. CASSEMS (Fixo)</label>
                                        <input type="number" id="dependentes_cassems" name="servidor[dependentes_cassems]" class="form-control" value="{{ $defaultConfig['dependentes_cassems'] }}" min="0">
                                    </div>
                                </div>

                                <div class="form-group" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed var(--border-glass); padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem;">
                                    <p style="font-size: 0.7rem; color: var(--text-muted); margin: 0; display:flex; flex-direction:column; gap:0.25rem;">
                                         <span style="font-weight:600; color:var(--text-light);">🏦 Empréstimos Consignados:</span>
                                         <span>Gerencie e adicione múltiplos consignados na aba <strong>"Consignados"</strong> à direita.</span>
                                    </p>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" id="tem_conjuge" name="servidor[tem_conjuge]" value="1" {{ $defaultConfig['tem_conjuge'] ? 'checked' : '' }}>
                                    <label for="tem_conjuge" class="form-label" style="margin:0;">Incluir Cônjuge na CASSEMS (R$ 450,00 fixo)</label>
                                </div>
                            </div>

                            <!-- Section 3: Projeção Config -->
                            <div class="glass-card">
                                <h3 class="glass-card-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                    </svg>
                                    Configuração da Projeção
                                </h3>

                                <div class="row-group">
                                    <div class="form-group">
                                        <label class="form-label" for="anos_projecao">Anos a Projetar</label>
                                        <input type="number" id="anos_projecao" name="servidor[anos_projecao]" class="form-control" value="{{ $defaultConfig['anos_projecao'] }}" min="1" max="40">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="start_mes_ano">Mês Inicial</label>
                                        <input type="month" id="start_mes_ano" name="servidor[start_mes_ano]" class="form-control" value="{{ $defaultConfig['start_mes_ano'] }}">
                                    </div>
                                </div>

                                <div class="row-group" style="margin-top: 0.5rem; margin-bottom: 0.5rem;">
                                    <div class="form-group">
                                        <label class="form-label" for="reajuste_auxilio_pct">Reajuste Médio Anual dos Auxílios (%)</label>
                                        <input type="number" id="reajuste_auxilio_pct" name="servidor[reajuste_auxilio_pct]" step="0.1" class="form-control" value="{{ $defaultConfig['reajuste_auxilio_pct'] ?? 0.0 }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="reajuste_mes">Mês de Reajuste Geral (Salário)</label>
                                        <select id="reajuste_mes" name="servidor[reajuste_mes]" class="form-control" onchange="updateReajustesInputs()">
                                            <option value="1" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 1 ? 'selected' : '' }}>Janeiro</option>
                                            <option value="2" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 2 ? 'selected' : '' }}>Fevereiro</option>
                                            <option value="3" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 3 ? 'selected' : '' }}>Março</option>
                                            <option value="4" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 4 ? 'selected' : '' }}>Abril</option>
                                            <option value="5" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 5 ? 'selected' : '' }}>Maio</option>
                                            <option value="6" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 6 ? 'selected' : '' }}>Junho</option>
                                            <option value="7" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 7 ? 'selected' : '' }}>Julho</option>
                                            <option value="8" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 8 ? 'selected' : '' }}>Agosto</option>
                                            <option value="9" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 9 ? 'selected' : '' }}>Setembro</option>
                                            <option value="10" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 10 ? 'selected' : '' }}>Outubro</option>
                                            <option value="11" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 11 ? 'selected' : '' }}>Novembro</option>
                                            <option value="12" {{ ($defaultConfig['reajuste_mes'] ?? 5) == 12 ? 'selected' : '' }}>Dezembro</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn-submit" style="margin-top: 1rem;">
                                    Gerar Projeção
                                </button>
                            </div>

                        </div>

                        <!-- Right Panel (Tabs Dashboard & Results) -->
                        <div class="results-column">
                            
                            <div class="tabs-header">
                                <button type="button" class="tab-btn active" onclick="switchTab('tab-charts', this)">Gráficos de Projeção</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-table', this)">Tabela de Projeção</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-aq-reajustes', this)">AQs & Reajustes</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-consignados', this)">Consignados</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-filhos', this)">Filhos (Aux. Creche)</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-timeline', this)">Timeline de Auxílios</button>
                                <button type="button" class="tab-btn" onclick="switchTab('tab-analise', this)">📊 Análise Avançada</button>
                            </div>

                            <!-- Tab 1: Charts -->
                            <div id="tab-charts" class="tab-content active">
                                <div class="glass-card">
                                    <h3 class="glass-card-title">Evolução Salarial Estimada (Líquido x Bruto)</h3>
                                    <div class="chart-scroll-container">
                                        <div class="chart-canvas-wrapper" id="wrapper-salaryChart">
                                            <canvas id="salaryChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="glass-card">
                                    <h3 class="glass-card-title">Distribuição de Descontos e Proventos</h3>
                                    <div class="chart-scroll-container">
                                        <div class="chart-canvas-wrapper" id="wrapper-deductionsChart">
                                            <canvas id="deductionsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Detailed Table -->
                            <div id="tab-table" class="tab-content">
                                <div class="glass-card">
                                    <h3 class="glass-card-title">Evolução Mensal Detalhada</h3>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                        💡 Clique em qualquer linha para abrir o Holerite Simulado (Aviso de Crédito) do mês correspondente.
                                    </p>
                                    <div class="table-responsive">
                                        <table class="data-table" id="projection-table-el">
                                             <thead>
                                                 <tr>
                                                     <th>Mês/Ano</th>
                                                     <th>Nível</th>
                                                     <th>Venc. Base</th>
                                                     <th>Bruto</th>
                                                     <th>MS-PREV</th>
                                                     <th>CASSEMS</th>
                                                     <th>IRRF</th>
                                                     <th>Auxílios</th>
                                                     <th>Eventos / Impactos</th>
                                                     <th>Líquido</th>
                                                 </tr>
                                             </thead>
                                             <tbody>
                                                 <tr>
                                                     <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                                         Nenhum cálculo efetuado. Clique em "Gerar Projeção".
                                                     </td>
                                                 </tr>
                                             </tbody>
                                         </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: AQs Temporários & Reajustes -->
                             <div id="tab-aq-reajustes" class="tab-content">
                                 <div class="glass-card">
                                     <h3 class="glass-card-title">Cursos de Qualificação Temporária</h3>
                                     <div class="dynamic-list" id="aq-temp-list">
                                         <!-- Row Header -->
                                         <div class="dynamic-row" style="font-weight: bold; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border-glass); padding-bottom: 0.25rem; margin-bottom: 0.5rem;">
                                             <div>Nome do Curso</div>
                                             <div>Adicional (%)</div>
                                             <div>Período (Início a Fim)</div>
                                             <div></div>
                                         </div>
                                         
                                         @foreach ($defaultAqTemp as $index => $aq)
                                             <div class="dynamic-row" id="aq-row-{{ $index }}">
                                                 <input type="text" name="aq_temporario[{{ $index }}][nome]" class="form-control" value="{{ $aq['nome'] }}" required>
                                                 <input type="number" name="aq_temporario[{{ $index }}][percentual]" step="0.1" class="form-control" value="{{ $aq['percentual'] }}" required>
                                                 <div style="display:flex; gap:0.25rem;">
                                                     <input type="month" name="aq_temporario[{{ $index }}][mes_inicio]" class="form-control" value="{{ $aq['mes_inicio'] }}" required>
                                                     <input type="month" name="aq_temporario[{{ $index }}][mes_fim]" class="form-control" value="{{ $aq['mes_fim'] }}" required>
                                                 </div>
                                                 <button type="button" class="btn-icon" onclick="removeRow('aq-row-{{ $index }}')">
                                                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                 </button>
                                             </div>
                                         @endforeach
                                     </div>
                                     <button type="button" class="btn-add" onclick="addAqRow()" style="margin-top: 0.5rem;">
                                         + Adicionar Curso de Qualificação
                                     </button>
                                 </div>

                                 <div class="glass-card" style="margin-top: 1.5rem;">
                                     <h3 class="glass-card-title">Cursos de Qualificação Permanente</h3>
                                     <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                         Indique cursos permanentes que adicionam +2% ao Adicional de Qualificação, com teto de 20% (soma do AQ Permanente Base + cursos).
                                     </p>
                                     <div class="dynamic-list" id="aq-perm-list">
                                         <!-- Row Header -->
                                         <div class="dynamic-row-aq-perm" style="font-weight: bold; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border-glass); padding-bottom: 0.25rem; margin-bottom: 0.5rem;">
                                             <div>Nome do Curso</div>
                                             <div>Adicional (%)</div>
                                             <div>Mês de Inclusão</div>
                                             <div></div>
                                         </div>
                                         
                                         @foreach ($defaultAqPermanenteCursos as $index => $aq)
                                             <div class="dynamic-row-aq-perm" id="aq-perm-row-{{ $index }}">
                                                 <input type="text" name="aq_permanente_cursos[{{ $index }}][nome]" class="form-control" value="{{ $aq['nome'] }}" required>
                                                  <input type="number" name="aq_permanente_cursos[{{ $index }}][percentual]" step="0.1" class="form-control" value="{{ $aq['percentual'] }}" required>
                                                  <input type="month" name="aq_permanente_cursos[{{ $index }}][mes_inicio]" class="form-control" value="{{ $aq['mes_inicio'] }}" required>
                                                  <button type="button" class="btn-icon" onclick="removeRow('aq-perm-row-{{ $index }}')">
                                                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                  </button>
                                              </div>
                                          @endforeach
                                      </div>
                                      <button type="button" class="btn-add" onclick="addAqPermRow()" style="margin-top: 0.5rem;">
                                         + Adicionar Curso Permanente
                                     </button>
                                 </div>

                                 <div class="glass-card" style="margin-top: 1.5rem;">
                                     <h3 class="glass-card-title">Reajustes Salariais Gerais Projetados</h3>
                                     <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                         Indique as taxas de reajuste geral / aumento real previstas para os anos futuros (incidência sobre a remuneração básica).
                                     </p>
                                     <div class="row-group" id="reajustes-inputs-container" style="grid-template-columns: repeat(3, 1fr);">
                                         <!-- populated dynamically by JS -->
                                     </div>
                                 </div>
                             </div>

                            <!-- Tab: Consignados -->
                            <div id="tab-consignados" class="tab-content">
                                <div class="glass-card">
                                    <h3 class="glass-card-title">Empréstimos Consignados</h3>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                        Adicione seus empréstimos consignados indicando valor, mês de início e término da cobrança.
                                    </p>
                                    <div class="dynamic-list" id="consignados-list-el">
                                        <!-- Row Header -->
                                        <div class="dynamic-row-consignados" style="font-weight: bold; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border-glass); padding-bottom: 0.25rem; margin-bottom: 0.5rem;">
                                            <div>Descrição / Banco</div>
                                            <div>Valor (R$)</div>
                                            <div>Mês Início</div>
                                            <div>Mês Fim (Opcional)</div>
                                            <div></div>
                                        </div>
                                        
                                        @foreach ($defaultConsignados as $index => $cons)
                                            <div class="dynamic-row-consignados" id="cons-row-{{ $index }}">
                                                <input type="text" name="consignados_list[{{ $index }}][descricao]" class="form-control" placeholder="Ex: C.E.F. EMPRESTIMO" value="{{ $cons['descricao'] ?? 'C.E.F. EMPRESTIMO' }}" required>
                                                <input type="number" name="consignados_list[{{ $index }}][valor]" step="0.01" class="form-control" value="{{ $cons['valor'] }}" required>
                                                <input type="month" name="consignados_list[{{ $index }}][mes_inicio]" class="form-control" value="{{ $cons['mes_inicio'] }}" required>
                                                <input type="month" name="consignados_list[{{ $index }}][mes_fim]" class="form-control" value="{{ $cons['mes_fim'] }}">
                                                <button type="button" class="btn-icon" onclick="removeRow('cons-row-{{ $index }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn-add" onclick="addConsignadoRow()" style="margin-top: 0.5rem;">
                                        + Adicionar Empréstimo
                                    </button>
                                </div>
                            </div>

                            <!-- Tab: Filhos (Aux. Creche) -->
                            <div id="tab-filhos" class="tab-content">
                                <div class="glass-card">
                                    <h3 class="glass-card-title">Filhos e Dependentes (Auxílio Creche Automático)</h3>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                        Cadastre seus filhos. O Auxílio Creche será incluído automaticamente nos meses em que a criança estiver matriculada na escola (idade mínima de início) e até completar 6 anos (5 anos e 11 meses).
                                    </p>
                                    
                                    <div class="form-group" style="max-width: 300px; margin-bottom: 1.5rem;">
                                        <label class="form-label" for="valor_unitario_creche">Valor Unitário do Auxílio Creche (R$)</label>
                                        <input type="number" id="valor_unitario_creche" name="valor_unitario_creche" step="0.01" class="form-control" value="558.78">
                                    </div>

                                    <div class="dynamic-list" id="filhos-list-el">
                                        <!-- Row Header -->
                                        <div class="dynamic-row-filhos" style="font-weight: bold; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border-glass); padding-bottom: 0.25rem; margin-bottom: 0.5rem;">
                                            <div>Nome do Filho</div>
                                            <div>Data de Nascimento</div>
                                            <div>Início Escola (Idade em Anos)</div>
                                            <div></div>
                                        </div>
                                        
                                        @foreach ($defaultFilhos as $index => $filho)
                                            <div class="dynamic-row-filhos" id="filho-row-{{ $index }}">
                                                <input type="text" name="filhos[{{ $index }}][nome]" class="form-control" value="{{ $filho['nome'] }}" required>
                                                <input type="date" name="filhos[{{ $index }}][dt_nascimento]" class="form-control" value="{{ $filho['dt_nascimento'] }}" required>
                                                <select name="filhos[{{ $index }}][idade_escola]" class="form-control" required>
                                                    <option value="0" {{ $filho['idade_escola'] == 0 ? 'selected' : '' }}>0 anos (Imediato)</option>
                                                    <option value="1" {{ $filho['idade_escola'] == 1 ? 'selected' : '' }}>1 ano</option>
                                                    <option value="2" {{ $filho['idade_escola'] == 2 ? 'selected' : '' }}>2 anos (Padrão)</option>
                                                    <option value="3" {{ $filho['idade_escola'] == 3 ? 'selected' : '' }}>3 anos</option>
                                                    <option value="4" {{ $filho['idade_escola'] == 4 ? 'selected' : '' }}>4 anos</option>
                                                    <option value="5" {{ $filho['idade_escola'] == 5 ? 'selected' : '' }}>5 anos</option>
                                                </select>
                                                <button type="button" class="btn-icon" onclick="removeRow('filho-row-{{ $index }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn-add" onclick="addFilhoRow()" style="margin-top: 0.5rem;">
                                        + Adicionar Filho
                                    </button>
                                </div>
                            </div>

                            <!-- Tab 4: Timeline Auxílios -->
                            <div id="tab-timeline" class="tab-content">
                                <div class="glass-card">
                                    <h3 class="glass-card-title">Timeline do Ciclo de Vida de Auxílios</h3>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                        Programe a criação, modificação de valor ou cancelamento de auxílios e verbas indenizatórias na linha do tempo.
                                    </p>
                                    <div class="dynamic-list" id="events-list">
                                        <!-- Row Header -->
                                        <div class="dynamic-row-events" style="font-weight: bold; font-size: 0.75rem; color: var(--text-muted); border-bottom: 1px solid var(--border-glass); padding-bottom: 0.25rem; margin-bottom: 0.5rem;">
                                            <div>Tipo de Verba</div>
                                            <div>Ação no Tempo</div>
                                            <div>Valor (R$)</div>
                                            <div></div>
                                        </div>

                                        @foreach ($defaultEvents as $index => $event)
                                            <div class="dynamic-row-events" id="event-row-{{ $index }}">
                                                <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                                    <select name="eventos_auxilios[{{ $index }}][tipo_auxilio]" class="form-control auxilio-type-select" onchange="handleAuxilioTypeChange(this)" required>
                                                        <option value="AUXILIO_ALIMENTACAO" {{ $event['tipo_auxilio'] === 'AUXILIO_ALIMENTACAO' ? 'selected' : '' }}>Auxílio Alimentação</option>
                                                        <option value="AUXILIO_CRECHE" {{ $event['tipo_auxilio'] === 'AUXILIO_CRECHE' ? 'selected' : '' }}>Auxílio Creche</option>
                                                        <option value="AUXILIO_TRANSPORTE" {{ $event['tipo_auxilio'] === 'AUXILIO_TRANSPORTE' ? 'selected' : '' }}>Auxílio Transporte</option>
                                                        <option value="OUTRO_AUXILIO" {{ !in_array($event['tipo_auxilio'], ['AUXILIO_ALIMENTACAO', 'AUXILIO_CRECHE', 'AUXILIO_TRANSPORTE']) ? 'selected' : '' }}>Outro Auxílio (Custom)</option>
                                                    </select>
                                                    <input type="text" class="form-control custom-auxilio-name" placeholder="Nome do auxílio..." value="{{ !in_array($event['tipo_auxilio'], ['AUXILIO_ALIMENTACAO', 'AUXILIO_CRECHE', 'AUXILIO_TRANSPORTE']) ? $event['tipo_auxilio'] : '' }}" style="display: {{ !in_array($event['tipo_auxilio'], ['AUXILIO_ALIMENTACAO', 'AUXILIO_CRECHE', 'AUXILIO_TRANSPORTE']) ? 'block' : 'none' }}; margin-top: 0.25rem;">
                                                </div>
                                                <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                                    <select name="eventos_auxilios[{{ $index }}][acao]" class="form-control" onchange="toggleEventEnd(this)" required>
                                                        <option value="CRIAR" {{ $event['acao'] === 'CRIAR' ? 'selected' : '' }}>CRIAR (Início em)</option>
                                                        <option value="ALTERAR_VALOR" {{ $event['acao'] === 'ALTERAR_VALOR' ? 'selected' : '' }}>ALTERAR VALOR em</option>
                                                        <option value="CANCELAR" {{ $event['acao'] === 'CANCELAR' ? 'selected' : '' }}>CANCELAR em</option>
                                                    </select>
                                                    <div style="display:flex; gap:0.25rem; align-items:center;">
                                                        <input type="month" name="eventos_auxilios[{{ $index }}][mes_ano_inicio]" class="form-control" value="{{ $event['mes_ano_inicio'] }}" required>
                                                        <span class="end-label" style="font-size:0.65rem; color:var(--text-muted); display:none;">até</span>
                                                        <input type="month" name="eventos_auxilios[{{ $index }}][mes_ano_fim]" class="form-control end-date-input" style="display:none;" value="{{ $event['mes_ano_fim'] }}">
                                                    </div>
                                                </div>
                                                <input type="number" name="eventos_auxilios[{{ $index }}][valor]" step="0.01" class="form-control" value="{{ $event['valor'] }}" required>
                                                <button type="button" class="btn-icon" onclick="removeRow('event-row-{{ $index }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn-add" onclick="addEventRow()" style="margin-top: 0.5rem;">
                                        + Agendar Evento de Auxílio
                                    </button>
                                </div>
                            </div>

                            <!-- Tab: Análise Avançada (Sub-Abas) -->
                            <div id="tab-analise" class="tab-content">

                                <!-- Sub-tab buttons -->
                                <div class="subtabs-header">
                                    <button type="button" class="subtab-btn active" onclick="switchSubTab('subtab-composicao', this)">📊 Composição da Remuneração</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-liquido-descontos', this)">💸 Líquido × Descontos</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-acumulado', this)">💰 Patrimônio Acumulado</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-irrf', this)">🧾 Alíquota IRRF</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-auxilios', this)">🎁 Auxílios Isentos</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-timeline', this)">🗓️ Timeline de Eventos</button>
                                    <button type="button" class="subtab-btn" onclick="switchSubTab('subtab-todos', this)">📋 Visualizar Todos</button>
                                </div>

                                <!-- Subtab 1: Composição -->
                                <div id="subtab-composicao" class="subtab-content active">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Composição da Remuneração</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Evolução de cada componente do salário bruto ao longo do tempo (vencimento base, AQ, ATS, substituição e função).
                                        </p>
                                        <div class="chart-scroll-container">
                                            <div class="chart-canvas-wrapper" id="wrapper-stackedAreaChart">
                                                <canvas id="stackedAreaChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtab 2: Líquido vs Descontos -->
                                <div id="subtab-liquido-descontos" class="subtab-content">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Salário Líquido × Descontos</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Barras de MS-PREV, CASSEMS, IRRF e Consignados empilhados. A linha verde mostra o líquido efetivo recebido.
                                        </p>
                                        <div class="chart-scroll-container">
                                            <div class="chart-canvas-wrapper" id="wrapper-liquidoDescontosChart">
                                                <canvas id="liquidoDescontosChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtab 3: Patrimônio Acumulado -->
                                <div id="subtab-acumulado" class="subtab-content">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Patrimônio Acumulado (Líquido)</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Soma acumulada do salário líquido recebido desde o início do exercício até o fim da projeção.
                                        </p>
                                        <div class="chart-scroll-container">
                                            <div class="chart-canvas-wrapper" id="wrapper-acumuladoChart">
                                                <canvas id="acumuladoChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtab 4: Alíquota IRRF -->
                                <div id="subtab-irrf" class="subtab-content">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Alíquota Efetiva de IRRF (%)</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Percentual real de imposto de renda retido sobre o salário bruto mês a mês.
                                        </p>
                                        <div class="chart-scroll-container">
                                            <div class="chart-canvas-wrapper" id="wrapper-irrfAliquotaChart">
                                                <canvas id="irrfAliquotaChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtab 5: Auxílios Isentos -->
                                <div id="subtab-auxilios" class="subtab-content">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Evolução dos Auxílios Isentos</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Crescimento mensal dos auxílios isentos de IR (alimentação, creche, transporte e outros).
                                        </p>
                                        <div class="chart-scroll-container">
                                            <div class="chart-canvas-wrapper" id="wrapper-auxiliosStackChart">
                                                <canvas id="auxiliosStackChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtab 6: Timeline de Eventos -->
                                <div id="subtab-timeline" class="subtab-content">
                                    <div class="glass-card">
                                        <h3 class="glass-card-title">Timeline de Eventos na Carreira</h3>
                                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: -0.75rem; margin-bottom: 1rem;">
                                            Progressões de padrão, reajustes, início/fim de auxílios, ATS e outros marcos da carreira.
                                        </p>
                                        <div id="analise-timeline-container" class="chart-scroll-container">
                                            <div id="analise-timeline-inner" style="position:relative; min-height:160px; padding:1rem 0;"></div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- /tab-analise -->

                        </div>

                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Realistic Holerite Facsimile Modal -->
    <div class="modal" id="holerite-modal">
        <div class="modal-content">
            <button class="btn-close-modal" onclick="closeHoleriteModal()">✕ Fechar</button>
            
            <!-- Sheet Tabs (For separate credit notices, matching the exact PDF pages) -->
            <div class="sheet-selector" id="holerite-tabs-container">
                <button type="button" class="sheet-tab active" id="sheet-btn-main" onclick="switchHoleriteSheet('main')">FOLHA PRINCIPAL</button>
            </div>

            <!-- Page 1: Main Payslip -->
            <div class="facsimile-wrapper" id="holerite-sheet-main">
                <div class="facsimile-header">
                    <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230f172a'><path d='M12 2L2 22h20L12 2zm0 3.99L18.8 19H5.2L12 5.99zM11 16h2v2h-2zm0-6h2v4h-2z'/></svg>" class="facsimile-logo">
                    <div class="facsimile-title-block">
                        <h3>PODER JUDICIÁRIO</h3>
                        <p>Tribunal de Justiça de Mato Grosso do Sul</p>
                        <p>CNPJ: 03.979.663/0001-98</p>
                    </div>
                    <div class="facsimile-credit-notice">
                        AVISO DE CRÉDITO
                    </div>
                </div>

                <div class="facsimile-grid">
                    <div>
                        <p class="label">Matrícula</p>
                        <p class="value">27336</p>
                    </div>
                    <div>
                        <p class="label">Nome</p>
                        <p class="value" id="h-nome">SAMUEL BELLAN</p>
                    </div>
                    <div>
                        <p class="label">Mês/Ano</p>
                        <p class="value" id="h-mesano">07/2026</p>
                    </div>
                    <div>
                        <p class="label">Código Folha</p>
                        <p class="value">15220</p>
                    </div>
                </div>

                <div class="facsimile-grid">
                    <div>
                        <p class="label">Nível/Ref.</p>
                        <p class="value" id="h-nivel">TNSU-1</p>
                    </div>
                    <div>
                        <p class="label">Cargo</p>
                        <p class="value" id="h-cargo-title">Técnico de Nível Superior</p>
                    </div>
                    <div>
                        <p class="label">Vínculo</p>
                        <p class="value">Efetivo</p>
                    </div>
                    <div>
                        <p class="label">Data Exercício</p>
                        <p class="value" id="h-dtexercicio">05/05/2025</p>
                    </div>
                </div>

                <!-- Proventos e Descontos Table -->
                <div class="facsimile-table-container">
                    
                    <!-- Vantagens -->
                    <div class="facsimile-panel">
                        <h4>VANTAGENS</h4>
                        <div class="facsimile-item-header">
                            <div>Cód.</div>
                            <div>Rubrica</div>
                            <div style="text-align:right;">Valor (R$)</div>
                        </div>
                        <div id="h-vantagens-rows">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Descontos -->
                    <div class="facsimile-panel">
                        <h4>DESCONTOS</h4>
                        <div class="facsimile-item-header">
                            <div>Cód.</div>
                            <div>Rubrica</div>
                            <div style="text-align:right;">Valor (R$)</div>
                        </div>
                        <div id="h-descontos-rows">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                </div>

                <!-- Totais -->
                <div class="facsimile-totals">
                    <div>
                        <p class="lbl">Salário Bruto</p>
                        <p class="val" id="h-total-bruto">10.904,56</p>
                    </div>
                    <div>
                        <p class="lbl">Descontos</p>
                        <p class="val" id="h-total-descontos">4.369,57</p>
                    </div>
                    <div>
                        <p class="lbl">Salário Líquido</p>
                        <p class="val" id="h-total-liquido">6.534,99</p>
                    </div>
                </div>

                <!-- Bases -->
                <div class="facsimile-obs" style="margin-bottom:0.25rem;">
                    <span style="font-weight:bold;">Bases de Cálculo:</span> &nbsp; 
                    Previdência: <span id="h-base-prev">8.475,55</span> &nbsp;|&nbsp; 
                    IRRF: <span id="h-base-irrf">9.528,39</span>
                </div>

                <!-- Observações -->
                <div class="facsimile-obs" id="h-observacoes-box">
                    <p style="font-weight:bold;">Observações:</p>
                    <div id="h-obs-list">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <div style="text-align:center; font-size:0.6rem; color:#666; margin-top:0.25rem;">
                    Código de validação do Holerite: <span style="font-family:monospace; font-weight:bold;">100a.de99.aa21.0a32</span>
                </div>
            </div>

            <!-- Container for dynamic auxilio sheets -->
            <div id="dynamic-auxilio-sheets-container"></div>

        </div>
    </div>

    <!-- Scripting and Dynamic logic -->
    <script>
        let salaryChartObj = null;
        let deductionsChartObj = null;
        let stackedAreaChartObj = null;
        let liquidoDescontosChartObj = null;
        let acumuladoChartObj = null;
        let irrfAliquotaChartObj = null;
        let auxiliosStackChartObj = null;
        let projectionData = [];
        const initialReajustes = @json($defaultReajustes);

        function updateReajustesInputs() {
            const startInput = document.getElementById('start_mes_ano');
            const anosInput = document.getElementById('anos_projecao');
            if (!startInput || !anosInput) return;

            const startYear = parseInt(startInput.value.substring(0, 4)) || new Date().getFullYear();
            const anos = parseInt(anosInput.value) || 10;

            const container = document.getElementById('reajustes-inputs-container');
            if (!container) return;

            // Capture current typed values in form to not lose them
            const currentValues = {};
            container.querySelectorAll('input[type="number"]').forEach(input => {
                const yearMatch = input.getAttribute('name').match(/reajustes\[(\d+)\]/);
                if (yearMatch) {
                    currentValues[yearMatch[1]] = input.value;
                }
            });

            container.innerHTML = '';
            
            // Generate inputs from startYear + 1 to startYear + anos
            for (let year = startYear + 1; year <= startYear + anos; year++) {
                if (year < 2027) continue;
                let val = currentValues[year] !== undefined ? currentValues[year] : '';
                if (val === '' && initialReajustes && initialReajustes[year] !== undefined) {
                    val = initialReajustes[year];
                }

                const div = document.createElement('div');
                div.className = 'form-group';
                div.innerHTML = `
                    <label class="form-label">${year} (%)</label>
                    <input type="number" name="reajustes[${year}]" step="0.1" class="form-control" value="${val}">
                `;
                container.appendChild(div);
            }
        }

        // Tab Switching
        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(item => item.classList.remove('active'));
            const targetTab = document.getElementById(tabId);
            if (targetTab) targetTab.classList.add('active');
            if (el) el.classList.add('active');

            // Trigger chart render/resize when switching to chart tabs
            if (projectionData && projectionData.length > 0) {
                if (tabId === 'tab-charts') {
                    renderCharts(projectionData);
                } else if (tabId === 'tab-analise') {
                    renderAdvancedCharts(projectionData);
                }
            }
        }

        // Add Dynamic Courses (AQs)
        let aqIndex = {{ count($defaultAqTemp) }};
        function addAqRow() {
            const html = `
                <div class="dynamic-row" id="aq-row-${aqIndex}">
                    <input type="text" name="aq_temporario[${aqIndex}][nome]" class="form-control" placeholder="Curso/Capacitação" required>
                    <input type="number" name="aq_temporario[${aqIndex}][percentual]" step="0.1" class="form-control" value="1.0" required>
                    <div style="display:flex; gap:0.25rem;">
                        <input type="month" name="aq_temporario[${aqIndex}][mes_inicio]" class="form-control" required>
                        <input type="month" name="aq_temporario[${aqIndex}][mes_fim]" class="form-control" required>
                    </div>
                    <button type="button" class="btn-icon" onclick="removeRow('aq-row-${aqIndex}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            document.getElementById('aq-temp-list').insertAdjacentHTML('beforeend', html);
            aqIndex++;
        }

        // Add Dynamic Permanent Courses
        let aqPermIndex = {{ count($defaultAqPermanenteCursos) }};
        function addAqPermRow() {
            const html = `
                <div class="dynamic-row-aq-perm" id="aq-perm-row-${aqPermIndex}">
                    <input type="text" name="aq_permanente_cursos[${aqPermIndex}][nome]" class="form-control" placeholder="Curso Permanente" required>
                    <input type="number" name="aq_permanente_cursos[${aqPermIndex}][percentual]" step="0.1" class="form-control" value="2.0" required>
                    <input type="month" name="aq_permanente_cursos[${aqPermIndex}][mes_inicio]" class="form-control" required>
                    <button type="button" class="btn-icon" onclick="removeRow('aq-perm-row-${aqPermIndex}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            document.getElementById('aq-perm-list').insertAdjacentHTML('beforeend', html);
            aqPermIndex++;
        }

        // Add Dynamic Consignados
        let consIndex = {{ count($defaultConsignados) }};
        function addConsignadoRow() {
            const html = `
                <div class="dynamic-row-consignados" id="cons-row-${consIndex}">
                    <input type="text" name="consignados_list[${consIndex}][descricao]" class="form-control" placeholder="Ex: C.E.F. EMPRESTIMO" required>
                    <input type="number" name="consignados_list[${consIndex}][valor]" step="0.01" class="form-control" placeholder="Valor" required>
                    <input type="month" name="consignados_list[${consIndex}][mes_inicio]" class="form-control" required>
                    <input type="month" name="consignados_list[${consIndex}][mes_fim]" class="form-control">
                    <button type="button" class="btn-icon" onclick="removeRow('cons-row-${consIndex}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            document.getElementById('consignados-list-el').insertAdjacentHTML('beforeend', html);
            consIndex++;
        }

        // Add Dynamic Filhos
        let filhoIndex = {{ count($defaultFilhos) }};
        function addFilhoRow() {
            const html = `
                <div class="dynamic-row-filhos" id="filho-row-${filhoIndex}">
                    <input type="text" name="filhos[${filhoIndex}][nome]" class="form-control" placeholder="Nome" required>
                    <input type="date" name="filhos[${filhoIndex}][dt_nascimento]" class="form-control" required>
                    <select name="filhos[${filhoIndex}][idade_escola]" class="form-control" required>
                        <option value="0">0 anos (Imediato)</option>
                        <option value="1">1 ano</option>
                        <option value="2" selected>2 anos (Padrão)</option>
                        <option value="3">3 anos</option>
                        <option value="4">4 anos</option>
                        <option value="5">5 anos</option>
                    </select>
                    <button type="button" class="btn-icon" onclick="removeRow('filho-row-${filhoIndex}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            document.getElementById('filhos-list-el').insertAdjacentHTML('beforeend', html);
            filhoIndex++;
        }

        // Add Dynamic Events
        let eventIndex = {{ count($defaultEvents) }};
        function addEventRow() {
            const html = `
                <div class="dynamic-row-events" id="event-row-${eventIndex}">
                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                        <select name="eventos_auxilios[${eventIndex}][tipo_auxilio]" class="form-control auxilio-type-select" onchange="handleAuxilioTypeChange(this)" required>
                            <option value="AUXILIO_ALIMENTACAO">Auxílio Alimentação</option>
                            <option value="AUXILIO_CRECHE">Auxílio Creche</option>
                            <option value="AUXILIO_TRANSPORTE">Auxílio Transporte</option>
                            <option value="OUTRO_AUXILIO">Outro Auxílio (Custom)</option>
                        </select>
                        <input type="text" class="form-control custom-auxilio-name" placeholder="Nome do auxílio..." style="display: none; margin-top: 0.25rem;">
                    </div>
                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                        <select name="eventos_auxilios[${eventIndex}][acao]" class="form-control" onchange="toggleEventEnd(this)" required>
                            <option value="CRIAR">CRIAR (Início em)</option>
                            <option value="ALTERAR_VALOR">ALTERAR VALOR em</option>
                            <option value="CANCELAR">CANCELAR em</option>
                        </select>
                        <div style="display:flex; gap:0.25rem; align-items:center;">
                            <input type="month" name="eventos_auxilios[${eventIndex}][mes_ano_inicio]" class="form-control" required>
                            <span class="end-label" style="font-size:0.65rem; color:var(--text-muted); display:none;">até</span>
                            <input type="month" name="eventos_auxilios[${eventIndex}][mes_ano_fim]" class="form-control end-date-input" style="display:none;">
                        </div>
                    </div>
                    <input type="number" name="eventos_auxilios[${eventIndex}][valor]" step="0.01" class="form-control" value="0.00" required>
                    <button type="button" class="btn-icon" onclick="removeRow('event-row-${eventIndex}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            `;
            document.getElementById('events-list').insertAdjacentHTML('beforeend', html);
            eventIndex++;
        }

        function removeRow(id) {
            document.getElementById(id).remove();
        }

        function toggleEventEnd(el) {
            const row = el.closest('.dynamic-row-events');
            const endLabel = row.querySelector('.end-label');
            const endDate = row.querySelector('.end-date-input');
            if (el.value === 'CRIAR') {
                endLabel.style.display = 'none';
                endDate.style.display = 'none';
                endDate.removeAttribute('required');
            } else {
                // Keep optional or required depending on design. Let's make end date optional.
                endLabel.style.display = 'none';
                endDate.style.display = 'none';
            }
        }

        // AJAX Request
        function triggerSimulation() {
            const form = document.getElementById('projector-form');
            const formData = new FormData(form);

            fetch('{{ route("salary.project") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    projectionData = data.projection;
                    updateProjectionTable(projectionData);
                    renderCharts(projectionData);
                    renderAdvancedCharts(projectionData);
                }
            })
            .catch(err => console.error("Erro na simulação:", err));
        }

        // Formata Moeda Brasileira
        function formatBrl(val) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
        }

        // Render Table
        function updateProjectionTable(data) {
            const tbody = document.querySelector('#projection-table-el tbody');
            tbody.innerHTML = '';
            
            data.forEach((month, idx) => {
                const tr = document.createElement('tr');
                tr.onclick = () => showHoleriteModal(idx);
                
                // Render event badges
                let eventBadgesHtml = '';
                if (month.eventos && month.eventos.length > 0) {
                    eventBadgesHtml = month.eventos.map(ev => 
                        `<span class="badge-event badge-event-${ev.cor}">${ev.descricao}</span>`
                    ).join(' ');
                }

                tr.innerHTML = `
                    <td>${month.mesAno}</td>
                    <td><span class="badge-info">${month.level}</span></td>
                    <td>${formatBrl(month.baseSalary)}</td>
                    <td style="font-weight:600;">${formatBrl(month.proventos.total_tributavel)}</td>
                    <td style="color:#f87171;">${formatBrl(month.descontos.msprev)}</td>
                    <td style="color:#f87171;">${formatBrl(month.descontos.cassems_total)}</td>
                    <td style="color:#f87171;">${formatBrl(month.descontos.irrf)}</td>
                    <td style="color:#34d399;">${formatBrl(month.total_isento)}</td>
                    <td style="text-align:left;">${eventBadgesHtml}</td>
                    <td style="font-weight:bold; color:#10b981;">${formatBrl(month.salario_liquido)}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // Render Charts
        function renderCharts(data) {
            if (!data || data.length === 0) return;

            const labels = data.map(m => m.mesAno);
            const netSalaries = data.map(m => m.salario_liquido);
            const grossSalaries = data.map(m => m.proventos.total_tributavel);
            const discounts = data.map(m => m.descontos.total_descontos);
            const auxilios = data.map(m => m.total_isento);

            // Dynamic width for scrollable canvas
            const minChartW = Math.max(750, labels.length * 28);
            ['wrapper-salaryChart', 'wrapper-deductionsChart'].forEach(id => {
                const w = document.getElementById(id);
                if (w) w.style.width = minChartW + 'px';
            });

            // Chart 1: Salary Evolution
            if (salaryChartObj) salaryChartObj.destroy();
            const ctx1 = document.getElementById('salaryChart').getContext('2d');
            salaryChartObj = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Salário Líquido',
                            data: netSalaries,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            tension: 0.3,
                            fill: true,
                            borderWidth: 3
                        },
                        {
                            label: 'Salário Bruto',
                            data: grossSalaries,
                            borderColor: '#6366f1',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2,
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#cbd5e1' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#cbd5e1', maxTicksLimit: 24 }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: '#f1f5f9' } }
                    }
                }
            });

            // Chart 2: Deductions and Provolutions Breakdown
            if (deductionsChartObj) deductionsChartObj.destroy();
            const ctx2 = document.getElementById('deductionsChart').getContext('2d');
            deductionsChartObj = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Descontos (MS-PREV + IR + CASSEMS)',
                            data: discounts,
                            backgroundColor: '#f87171',
                            stack: 'Stack 0'
                        },
                        {
                            label: 'Auxílios Isentos',
                            data: auxilios,
                            backgroundColor: '#34d399',
                            stack: 'Stack 1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#cbd5e1' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#cbd5e1', maxTicksLimit: 24 }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: '#f1f5f9' } }
                    }
                }
            });
        }

        // ── Análise Avançada Charts ──────────────────────────────────────────────
        function renderAdvancedCharts(data) {
            if (!data || data.length === 0) return;

            const labels = data.map(m => m.mesAno);
            const CHART_DEFAULTS = {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#94a3b8', callback: v => 'R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits:0, maximumFractionDigits:0}) } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 14 } }
                },
                plugins: { legend: { labels: { color: '#e2e8f0', boxWidth: 12, font: { size: 11 } } }, tooltip: { callbacks: { label: ctx => ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2}) } } }
            };

            // ── 1. Composição Empilhada (Stacked Area) ──
            try {
                if (stackedAreaChartObj) stackedAreaChartObj.destroy();
                const el = document.getElementById('stackedAreaChart');
                if (el) {
                    const vencimentos = data.map(m => m.proventos.vencimento || m.baseSalary);
                    const aqPerms = data.map(m => (m.proventos.aq_permanente || 0) + (m.proventos.aq_temporario || 0));
                    const atsVals = data.map(m => m.proventos.ats || 0);
                    const subVals = data.map(m => m.proventos.substituicao || 0);
                    const funcVals = data.map(m => m.proventos.funcao_comissao || 0);
                    stackedAreaChartObj = new Chart(el.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Vencimento Base', data: vencimentos, backgroundColor: 'rgba(99,102,241,0.55)', borderColor: '#6366f1', fill: true, tension: 0.3, borderWidth: 1.5 },
                                { label: 'AQ Perm+Temp', data: aqPerms, backgroundColor: 'rgba(168,85,247,0.45)', borderColor: '#a855f7', fill: true, tension: 0.3, borderWidth: 1.5 },
                                { label: 'ATS (Quinquênio)', data: atsVals, backgroundColor: 'rgba(251,191,36,0.4)', borderColor: '#fbbf24', fill: true, tension: 0.3, borderWidth: 1.5 },
                                { label: 'Substituição', data: subVals, backgroundColor: 'rgba(20,184,166,0.35)', borderColor: '#14b8a6', fill: true, tension: 0.3, borderWidth: 1.5 },
                                { label: 'Função/Comissão', data: funcVals, backgroundColor: 'rgba(249,115,22,0.35)', borderColor: '#f97316', fill: true, tension: 0.3, borderWidth: 1.5 },
                            ]
                        },
                        options: { ...CHART_DEFAULTS, scales: { ...CHART_DEFAULTS.scales, y: { ...CHART_DEFAULTS.scales.y, stacked: false } } }
                    });
                }
            } catch (err) { console.error("Erro StackedAreaChart:", err); }

            // ── 2. Líquido × Descontos (Dual Axis Bar + Line) ──
            try {
                if (liquidoDescontosChartObj) liquidoDescontosChartObj.destroy();
                const el = document.getElementById('liquidoDescontosChart');
                if (el) {
                    const liquidos = data.map(m => m.salario_liquido);
                    const msprevVals = data.map(m => m.descontos.msprev || 0);
                    const cassemsVals = data.map(m => m.descontos.cassems_total || 0);
                    const irrfVals = data.map(m => m.descontos.irrf || 0);
                    const consigVals = data.map(m => m.descontos.consignados || 0);
                    liquidoDescontosChartObj = new Chart(el.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'MS-PREV', data: msprevVals, backgroundColor: 'rgba(248,113,113,0.75)', stack: 'desc' },
                                { label: 'CASSEMS', data: cassemsVals, backgroundColor: 'rgba(251,146,60,0.75)', stack: 'desc' },
                                { label: 'IRRF', data: irrfVals, backgroundColor: 'rgba(253,224,71,0.75)', stack: 'desc' },
                                { label: 'Consignados', data: consigVals, backgroundColor: 'rgba(192,132,252,0.75)', stack: 'desc' },
                                { type: 'line', label: 'Salário Líquido', data: liquidos, borderColor: '#10b981', backgroundColor: 'transparent', borderWidth: 2.5, tension: 0.3, yAxisID: 'y', pointRadius: 0 },
                            ]
                        },
                        options: { ...CHART_DEFAULTS, scales: { ...CHART_DEFAULTS.scales, x: { ...CHART_DEFAULTS.scales.x, stacked: true } } }
                    });
                }
            } catch (err) { console.error("Erro LiquidoDescontosChart:", err); }

            // ── 3. Patrimônio Acumulado ──
            try {
                if (acumuladoChartObj) acumuladoChartObj.destroy();
                const el = document.getElementById('acumuladoChart');
                if (el) {
                    let running = 0;
                    const acumulado = data.map(m => { running += m.salario_liquido; return Math.round(running); });
                    acumuladoChartObj = new Chart(el.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Total Acumulado (Líquido)',
                                data: acumulado,
                                borderColor: '#34d399',
                                backgroundColor: 'rgba(52, 211, 153, 0.15)',
                                fill: true, tension: 0.35, borderWidth: 2.5, pointRadius: 0
                            }]
                        },
                        options: { ...CHART_DEFAULTS }
                    });
                }
            } catch (err) { console.error("Erro AcumuladoChart:", err); }

            // ── 4. Alíquota Efetiva IRRF ──
            try {
                if (irrfAliquotaChartObj) irrfAliquotaChartObj.destroy();
                const el = document.getElementById('irrfAliquotaChart');
                if (el) {
                    const aliquotas = data.map(m => {
                        const bruto = m.proventos.total_tributavel || 0;
                        if (bruto <= 0) return 0;
                        return parseFloat(((m.descontos.irrf || 0) / bruto * 100).toFixed(2));
                    });
                    irrfAliquotaChartObj = new Chart(el.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Alíquota Efetiva IRRF (%)',
                                data: aliquotas,
                                borderColor: '#fb923c',
                                backgroundColor: 'rgba(251,146,60,0.12)',
                                fill: true, tension: 0.3, borderWidth: 2, pointRadius: 0
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#94a3b8', callback: v => v.toFixed(1) + '%' }, min: 0 },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 14 } }
                            },
                            plugins: { legend: { labels: { color: '#e2e8f0', boxWidth: 12, font: { size: 11 } } }, tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y.toFixed(2) + '%' } } }
                        }
                    });
                }
            } catch (err) { console.error("Erro IrrfAliquotaChart:", err); }

            // ── 5. Auxílios Isentos Empilhados ──
            try {
                if (auxiliosStackChartObj) auxiliosStackChartObj.destroy();
                const el = document.getElementById('auxiliosStackChart');
                if (el) {
                    const auxKeys = new Set();
                    data.forEach(m => { if (m.auxilios) Object.keys(m.auxilios).forEach(k => auxKeys.add(k)); });
                    const AUXILIO_COLORS = ['#10b981','#06b6d4','#8b5cf6','#f59e0b','#ec4899','#14b8a6','#6366f1'];
                    const auxDatasets = Array.from(auxKeys).map((key, i) => ({
                        label: formatAuxilioName(key),
                        data: data.map(m => (m.auxilios && m.auxilios[key]) ? m.auxilios[key] : 0),
                        borderColor: AUXILIO_COLORS[i % AUXILIO_COLORS.length],
                        backgroundColor: AUXILIO_COLORS[i % AUXILIO_COLORS.length] + 'BF',
                        stack: 'aux'
                    }));
                    auxiliosStackChartObj = new Chart(el.getContext('2d'), {
                        type: 'bar',
                        data: { labels, datasets: auxDatasets.length > 0 ? auxDatasets : [{ label: 'Sem dados', data: labels.map(() => 0) }] },
                        options: {
                            ...CHART_DEFAULTS,
                            scales: {
                                y: { ...CHART_DEFAULTS.scales.y, stacked: true },
                                x: { ...CHART_DEFAULTS.scales.x, stacked: true }
                            }
                        }
                    });
                }
            } catch (err) { console.error("Erro AuxiliosStackChart:", err); }

            // ── 6. Timeline de Eventos (visual) ──
            try {
                renderEventsTimeline(data);
            } catch (err) { console.error("Erro RenderEventsTimeline:", err); }
        }

        function renderEventsTimeline(data) {
            const container = document.getElementById('analise-timeline-inner');
            if (!container) return;
            container.innerHTML = '';

            const EVENT_COLORS = {
                'progressao': { bg: '#4f46e5', label: '⬆️' },
                'reajuste': { bg: '#eab308', label: '💵' },
                'reajuste_auxilio': { bg: '#0284c7', label: '📈' },
                'aq_perm': { bg: '#7c3aed', label: '🎓' },
                'ats': { bg: '#059669', label: '⏱️' },
                'auxilio_novo': { bg: '#16a34a', label: '➕' },
                'auxilio_cancelado': { bg: '#dc2626', label: '🚫' },
                'auxilio_alterado': { bg: '#d97706', label: '✏️' },
                'aq_temp_inicio': { bg: '#2563eb', label: '📚' },
                'aq_temp_fim': { bg: '#9333ea', label: '📉' },
                'filho_nasce': { bg: '#db2777', label: '👶' },
                'filho_sai': { bg: '#991b1b', label: '🏫' },
            };

            const allEvents = [];
            data.forEach((m, idx) => {
                if (m.eventos && m.eventos.length > 0) {
                    m.eventos.forEach(ev => allEvents.push({ mesAno: m.mesAno, idx, ...ev }));
                }
            });

            if (allEvents.length === 0) {
                container.innerHTML = '<p style="color:var(--text-muted);font-size:0.8rem;padding:1rem;">Nenhum evento registrado na projeção.</p>';
                return;
            }

            // Build a horizontal scrollable pill timeline
            const LINE_Y = 48;
            const PILL_H = 32;
            const PILL_W = 170;
            const GAP = 12;
            const totalW = allEvents.length * (PILL_W + GAP) + GAP;

            container.style.minWidth = totalW + 'px';

            // Draw SVG line
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('width', totalW);
            svg.setAttribute('height', 140);
            svg.style.position = 'absolute';
            svg.style.top = '0';
            svg.style.left = '0';

            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', 0);
            line.setAttribute('y1', LINE_Y);
            line.setAttribute('x2', totalW);
            line.setAttribute('y2', LINE_Y);
            line.setAttribute('stroke', 'rgba(255,255,255,0.15)');
            line.setAttribute('stroke-width', '2');
            svg.appendChild(line);

            allEvents.forEach((ev, i) => {
                const cx = GAP + i * (PILL_W + GAP) + PILL_W / 2;
                const colorInfo = EVENT_COLORS[ev.tipo] || { bg: '#64748b', label: '•' };

                // Dot on line
                const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                dot.setAttribute('cx', cx);
                dot.setAttribute('cy', LINE_Y);
                dot.setAttribute('r', 6);
                dot.setAttribute('fill', colorInfo.bg);
                svg.appendChild(dot);

                // Connector
                const alternate = i % 2 === 0;
                const pillY = alternate ? LINE_Y - PILL_H - 20 : LINE_Y + 20;
                const connY2 = alternate ? pillY + PILL_H : pillY;
                const conn = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                conn.setAttribute('x1', cx); conn.setAttribute('y1', LINE_Y + (alternate ? -6 : 6));
                conn.setAttribute('x2', cx); conn.setAttribute('y2', connY2);
                conn.setAttribute('stroke', colorInfo.bg);
                conn.setAttribute('stroke-width', '1.5');
                conn.setAttribute('stroke-dasharray', '3 2');
                svg.appendChild(conn);

                // Pill background
                const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rect.setAttribute('x', GAP + i * (PILL_W + GAP));
                rect.setAttribute('y', pillY);
                rect.setAttribute('width', PILL_W);
                rect.setAttribute('height', PILL_H);
                rect.setAttribute('rx', 6);
                rect.setAttribute('fill', colorInfo.bg + '33');
                rect.setAttribute('stroke', colorInfo.bg);
                rect.setAttribute('stroke-width', '1');
                svg.appendChild(rect);

                // Month label
                const tMonth = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tMonth.setAttribute('x', cx);
                tMonth.setAttribute('y', pillY + 11);
                tMonth.setAttribute('text-anchor', 'middle');
                tMonth.setAttribute('fill', '#94a3b8');
                tMonth.setAttribute('font-size', '8');
                tMonth.textContent = ev.mesAno;
                svg.appendChild(tMonth);

                // Event description (truncated)
                const tDesc = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tDesc.setAttribute('x', cx);
                tDesc.setAttribute('y', pillY + 23);
                tDesc.setAttribute('text-anchor', 'middle');
                tDesc.setAttribute('fill', '#e2e8f0');
                tDesc.setAttribute('font-size', '9');
                const descText = ev.descricao && ev.descricao.length > 22 ? ev.descricao.substring(0, 22) + '…' : ev.descricao;
                tDesc.textContent = descText;
                svg.appendChild(tDesc);
            });

            container.appendChild(svg);
            container.style.height = '150px';

            const outerTimelineContainer = document.getElementById('analise-timeline-container');
            if (outerTimelineContainer) {
                makeElementDraggable(outerTimelineContainer);
            }
        }

        // ── Sub-Abas Controller (Análise Avançada) ────────────────────────────────
        function switchSubTab(subtabId, el) {
            document.querySelectorAll('.subtab-content').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.subtab-btn').forEach(item => item.classList.remove('active'));
            
            if (subtabId === 'subtab-todos') {
                document.querySelectorAll('.subtab-content').forEach(item => item.classList.add('active'));
            } else {
                const target = document.getElementById(subtabId);
                if (target) target.classList.add('active');
            }
            
            if (el) el.classList.add('active');

            // Re-render advanced charts for proper sizing
            if (projectionData && projectionData.length > 0) {
                renderAdvancedCharts(projectionData);
            }
        }

        // ── Drag-to-scroll Helper for Tab Headers ──────────────────────────
        function makeElementDraggable(container) {
            if (!container) return;
            let isDown = false;
            let startX, scrollLeft;

            container.addEventListener('mousedown', (e) => {
                isDown = true;
                container.style.cursor = 'grabbing';
                startX = e.pageX - container.offsetLeft;
                scrollLeft = container.scrollLeft;
            });
            container.addEventListener('mouseleave', () => {
                isDown = false;
                container.style.cursor = 'grab';
            });
            container.addEventListener('mouseup', () => {
                isDown = false;
                container.style.cursor = 'grab';
            });
            container.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - container.offsetLeft;
                const walk = (x - startX) * 1.5;
                container.scrollLeft = scrollLeft - walk;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.tabs-header, .subtabs-header, .table-responsive').forEach(makeElementDraggable);
        });

        function handleAuxilioTypeChange(selectEl) {
            const container = selectEl.closest('div');
            const textInput = container.querySelector('.custom-auxilio-name');
            if (!textInput) return;

            let nameAttr = selectEl.getAttribute('name') || textInput.getAttribute('name');
            if (!nameAttr) {
                const row = selectEl.closest('.dynamic-row-events');
                const match = row.id.match(/event-row-(\d+)/);
                if (match) {
                    nameAttr = `eventos_auxilios[${match[1]}][tipo_auxilio]`;
                }
            }

            if (selectEl.value === 'OUTRO_AUXILIO') {
                textInput.style.display = 'block';
                textInput.setAttribute('required', 'required');
                if (nameAttr) {
                    textInput.setAttribute('name', nameAttr);
                }
                selectEl.removeAttribute('name');
            } else {
                textInput.style.display = 'none';
                textInput.removeAttribute('required');
                if (nameAttr) {
                    selectEl.setAttribute('name', nameAttr);
                }
                textInput.removeAttribute('name');
            }
        }

        function formatAuxilioName(key) {
            if (key === 'AUXILIO_ALIMENTACAO') return 'Auxílio Alimentação';
            if (key === 'AUXILIO_CRECHE') return 'Auxílio Educação Infantil';
            if (key === 'AUXILIO_TRANSPORTE') return 'Auxílio Transporte';
            
            let clean = key;
            if (clean.startsWith('AUXILIO_')) {
                clean = clean.replace('AUXILIO_', '');
            }
            clean = clean.replace(/_/g, ' ');
            return clean.charAt(0).toUpperCase() + clean.slice(1);
        }

        function getAuxilioCode(key) {
            if (key === 'AUXILIO_ALIMENTACAO') return '200';
            if (key === 'AUXILIO_CRECHE') return '324';
            if (key === 'AUXILIO_TRANSPORTE') return '112';
            return '800';
        }

        // Realistic Holerite Facsimile logic
        let currentMonthData = null;
        function showHoleriteModal(index) {
            const month = projectionData[index];
            currentMonthData = month;
            
            // Format headers
            document.getElementById('h-mesano').innerText = formatMonthYear(month.mesAno);
            document.getElementById('h-nivel').innerText = `${document.getElementById('cargo').value}-${month.level}`;
            
            const cargoSelect = document.getElementById('cargo');
            const cargoText = cargoSelect.options[cargoSelect.selectedIndex].text.split('(')[0].trim();
            document.getElementById('h-cargo-title').innerText = cargoText;
            document.getElementById('h-dtexercicio').innerText = formatDate(document.getElementById('dt_exercicio').value);

            // Populate Vantagens (Folha Principal)
            const vRows = document.getElementById('h-vantagens-rows');
            vRows.innerHTML = '';
            
            // 3: Vencimento Efetivo
            addFacsimileRow(vRows, '3', 'VENCIMENTO EFETIVO', month.proventos.vencimento);
            
            // 22: Salario p/ Substituição
            if (month.proventos.substituicao > 0) {
                addFacsimileRow(vRows, '22', 'SALARIO P/SUBSTITUICAO', month.proventos.substituicao);
            }

            // 93: Adicional Tempo Integral
            if (month.proventos.tempo_integral > 0) {
                addFacsimileRow(vRows, '93', 'ADICIONAL TEMPO INTEGRAL', month.proventos.tempo_integral);
            }

            // 107-A: Adicional Dificil Provimento
            if (month.proventos.outros_adicionais > 0) {
                addFacsimileRow(vRows, '107', 'ADICIONAL DIF. PROVIMENTO', month.proventos.outros_adicionais);
            }

            // 277: Adicional de Qualificação
            if (month.proventos.aq_permanente > 0) {
                addFacsimileRow(vRows, '277', 'ADICIONAL DE QUALIFICAÇÃO', month.proventos.aq_permanente);
            }

            // 108: Função comissao
            if (month.proventos.funcao_comissao > 0) {
                addFacsimileRow(vRows, '108', 'GRAT. CARGO EM COMISSÃO', month.proventos.funcao_comissao);
            }

            // 489: Adicional de Qualificação Temporário rows
            month.aq_temp_details.forEach(aq => {
                addFacsimileRow(vRows, '489', `AQ TEMPORÁRIO (${aq.percentual}%)`, aq.valor);
            });

            // Populate Descontos (Folha Principal)
            const dRows = document.getElementById('h-descontos-rows');
            dRows.innerHTML = '';

            // 940: MS-PREV
            addFacsimileRow(dRows, '940', 'MS-PREV', month.descontos.msprev);

            // 941: IRRF
            addFacsimileRow(dRows, '941', 'IRRF', month.descontos.irrf);

            // 944: CASSEMS
            addFacsimileRow(dRows, '944', 'CASSEMS', month.descontos.cassems_pct);

            // 974: CASSEMS - CONTRIBUIÇÃO FIXA
            if (month.descontos.cassems_fixo > 0) {
                addFacsimileRow(dRows, '974', 'CASSEMS - CONTRIBUIÇÃO FIXA', month.descontos.cassems_fixo);
            }

            // 361: Empréstimo
            if (month.descontos.consignados > 0) {
                addFacsimileRow(dRows, '361', 'C.E.F. EMPRESTIMO - DIA 15', month.descontos.consignados);
            }

            // Update Totals
            document.getElementById('h-total-bruto').innerText = formatPlainBrl(month.proventos.total_tributavel);
            document.getElementById('h-total-descontos').innerText = formatPlainBrl(month.descontos.total_descontos);
            document.getElementById('h-total-liquido').innerText = formatPlainBrl(month.proventos.total_tributavel - month.descontos.total_descontos);

            // Bases
            document.getElementById('h-base-prev').innerText = formatPlainBrl(month.msprev_base);
            document.getElementById('h-base-irrf').innerText = formatPlainBrl(month.irrf_base);

            // Observações Box
            const obsList = document.getElementById('h-obs-list');
            obsList.innerHTML = '';
            let hasObs = false;
            
            // Add temporary course annotations
            month.aq_temp_details.forEach(aq => {
                hasObs = true;
                const p = document.createElement('p');
                p.innerText = `- CONCEDIDO ${aq.percentual}% DE ADICIONAL DE QUALIFICAÇÃO - ${aq.nome.toUpperCase()}.`;
                obsList.appendChild(p);
            });

            if (month.proventos.tempo_integral > 0) {
                hasObs = true;
                const p = document.createElement('p');
                p.innerText = `- CONCEDIDO 20% DE ADICIONAL DE TEMPO INTEGRAL (REGIME DEDICAÇÃO EXCLUSIVA).`;
                obsList.appendChild(p);
            }

            if (month.proventos.outros_adicionais > 0) {
                hasObs = true;
                const p = document.createElement('p');
                p.innerText = `- ADICIONAL DE DIFÍCIL PROVIMENTO ATIVO (10% SOBRE O BÁSICO).`;
                obsList.appendChild(p);
            }

            document.getElementById('h-observacoes-box').style.display = hasObs ? 'block' : 'none';

            // Clear previous dynamic tabs and sheets
            const tabContainer = document.getElementById('holerite-tabs-container');
            const tabs = tabContainer.querySelectorAll('.sheet-tab');
            tabs.forEach(tab => {
                if (tab.id !== 'sheet-btn-main') {
                    tab.remove();
                }
            });

            const sheetsContainer = document.getElementById('dynamic-auxilio-sheets-container');
            sheetsContainer.innerHTML = '';

            // Render dynamic auxílio tabs and sheets
            if (month.auxilios) {
                Object.keys(month.auxilios).forEach(key => {
                    const value = month.auxilios[key];
                    if (value > 0) {
                        const typeName = formatAuxilioName(key);
                        const typeCode = getAuxilioCode(key);
                        const typeSlug = key.toLowerCase().replace(/[^a-z0-9]/g, '-');

                        // Create tab
                        const tabBtn = document.createElement('button');
                        tabBtn.type = 'button';
                        tabBtn.className = 'sheet-tab';
                        tabBtn.id = `sheet-btn-${typeSlug}`;
                        tabBtn.innerText = typeName.toUpperCase();
                        tabBtn.onclick = () => switchHoleriteSheet(typeSlug);
                        tabContainer.appendChild(tabBtn);

                        // Create sheet
                        const sheetDiv = document.createElement('div');
                        sheetDiv.className = 'facsimile-wrapper';
                        sheetDiv.id = `holerite-sheet-${typeSlug}`;
                        sheetDiv.style.display = 'none';
                        sheetDiv.innerHTML = `
                            <div class="facsimile-header">
                                <div class="facsimile-title-block">
                                    <h3>PODER JUDICIÁRIO</h3>
                                    <p>Tribunal de Justiça de Mato Grosso do Sul</p>
                                </div>
                                <div class="facsimile-credit-notice">${typeName.toUpperCase()}</div>
                            </div>
                            <div class="facsimile-grid" style="grid-template-columns: 2fr 1fr 1fr;">
                                <div><p class="label">Nome</p><p class="value">SAMUEL BELLAN</p></div>
                                <div><p class="label">Mês/Ano</p><p class="value">${formatMonthYear(month.mesAno)}</p></div>
                                <div><p class="label">Nível/Ref.</p><p class="value">${cargoSelect.value}-${month.level}</p></div>
                            </div>
                            <div class="facsimile-table-container" style="grid-template-columns: 1fr;">
                                <div class="facsimile-panel">
                                    <h4>PROVENTOS INDENIZATÓRIOS</h4>
                                    <div class="facsimile-item-header" style="grid-template-columns: 80px 1fr 120px;">
                                        <div>Cód.</div>
                                        <div>Rubrica</div>
                                        <div style="text-align:right;">Valor (R$)</div>
                                    </div>
                                    <div class="facsimile-item-row" style="grid-template-columns: 80px 1fr 120px;">
                                        <div>${typeCode}</div>
                                        <div>${typeName.toUpperCase()}</div>
                                        <div class="val">${formatPlainBrl(value)}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="facsimile-totals" style="grid-template-columns: 1fr 1fr;">
                                <div><p class="lbl">Salário Bruto</p><p class="val">${formatPlainBrl(value)}</p></div>
                                <div><p class="lbl">Salário Líquido</p><p class="val">${formatPlainBrl(value)}</p></div>
                            </div>
                        `;
                        sheetsContainer.appendChild(sheetDiv);
                    }
                });
            }

            // Reset tabs
            switchHoleriteSheet('main');

            document.getElementById('holerite-modal').style.display = 'flex';
        }

        function addFacsimileRow(container, code, rubrica, val) {
            const row = document.createElement('div');
            row.className = 'facsimile-item-row';
            row.innerHTML = `
                <div>${code}</div>
                <div>${rubrica}</div>
                <div class="val">${formatPlainBrl(val)}</div>
            `;
            container.appendChild(row);
        }

        function switchHoleriteSheet(sheet) {
            document.querySelectorAll('.facsimile-wrapper').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.sheet-tab').forEach(el => el.classList.remove('active'));
            
            document.getElementById(`holerite-sheet-${sheet}`).style.display = 'block';
            document.getElementById(`sheet-btn-${sheet}`).classList.add('active');
        }

        function closeHoleriteModal() {
            document.getElementById('holerite-modal').style.display = 'none';
        }

        function formatPlainBrl(val) {
            return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
        }

        function formatMonthYear(mesAno) {
            const parts = mesAno.split('-');
            return `${parts[1]}/${parts[0]}`;
        }

        function formatDate(dateStr) {
            const parts = dateStr.split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function updateActiveProfile() {
            const selector = document.getElementById('profile_selector');
            const id = selector.value;
            if (!id) return;

            const form = document.getElementById('projector-form');
            const formData = new FormData(form);
            formData.append('_method', 'PUT');

            fetch(`{{ route("salary.index") }}/perfis/${id}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Alterações salvas com sucesso neste perfil!");
                    triggerSimulation();
                } else {
                    alert("Erro ao salvar alterações no perfil.");
                }
            })
            .catch(err => console.error("Erro:", err));
        }

        function saveProfile() {
            const nameInput = document.getElementById('new_profile_name');
            const nome = nameInput.value.trim();
            if (!nome) {
                alert("Por favor, digite um nome para o perfil.");
                return;
            }

            const form = document.getElementById('projector-form');
            const formData = new FormData(form);
            formData.append('nome', nome);

            fetch('{{ route("salary.profiles.save") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(`Perfil "${nome}" salvo com sucesso!`);
                    window.location.href = `{{ route("salary.index") }}?profile_id=${data.profile.id}`;
                } else {
                    alert("Erro ao salvar o perfil.");
                }
            })
            .catch(err => console.error("Erro:", err));
        }

        function loadProfile(id) {
            if (!id) {
                window.location.href = '{{ route("salary.index") }}';
                return;
            }
            window.location.href = `{{ route("salary.index") }}?profile_id=${id}`;
        }

        function deleteActiveProfile() {
            const selector = document.getElementById('profile_selector');
            const id = selector.value;
            if (!id) return;

            if (!confirm("Tem certeza que deseja excluir este perfil?")) return;

            fetch(`{{ route("salary.index") }}/perfis/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Perfil excluído com sucesso!");
                    window.location.href = '{{ route("salary.index") }}';
                }
            })
            .catch(err => console.error("Erro:", err));
        }

        function setProfileDefault() {
            const selector = document.getElementById('profile_selector');
            const id = selector.value || 'clear';

            fetch(`{{ route("salary.index") }}/perfis/${id}/ativar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (id === 'clear') {
                        alert("Padrão do sistema limpo (usando dados de simulação padrão do TJMS).");
                    } else {
                        alert("Perfil definido como padrão com sucesso! Ele será carregado automaticamente nas próximas visitas.");
                    }
                    window.location.reload();
                }
            })
            .catch(err => console.error("Erro:", err));
        }

        // Trigger simulation on startup
        window.addEventListener('DOMContentLoaded', () => {
            // Initial call to generate reajustes inputs
            updateReajustesInputs();

            triggerSimulation();

            // Date of Exercise and Project start sync
            const dtExercicioInput = document.getElementById('dt_exercicio');
            const startMesAnoInput = document.getElementById('start_mes_ano');
            const anosProjecaoInput = document.getElementById('anos_projecao');

            if (dtExercicioInput && startMesAnoInput) {
                dtExercicioInput.addEventListener('change', () => {
                    if (dtExercicioInput.value) {
                        startMesAnoInput.value = dtExercicioInput.value.substring(0, 7);
                        updateReajustesInputs();
                    }
                });
            }

            if (startMesAnoInput) {
                startMesAnoInput.addEventListener('change', updateReajustesInputs);
            }
            if (anosProjecaoInput) {
                anosProjecaoInput.addEventListener('input', updateReajustesInputs);
                anosProjecaoInput.addEventListener('change', updateReajustesInputs);
            }

            // Sync profile manager buttons visibility
            const selector = document.getElementById('profile_selector');
            const deleteBtn = document.getElementById('btn-delete-profile');
            const defaultBtn = document.getElementById('btn-default-profile');
            const updateContainer = document.getElementById('btn-update-container');
            
            function updateProfileButtons() {
                if (selector && selector.value) {
                    if (deleteBtn) deleteBtn.style.display = 'block';
                    if (defaultBtn) defaultBtn.innerText = 'Definir como Padrão do Sistema';
                    if (updateContainer) updateContainer.style.display = 'block';
                } else {
                    if (deleteBtn) deleteBtn.style.display = 'none';
                    if (defaultBtn) defaultBtn.innerText = 'Limpar Padrão (Usar padrão do TJMS)';
                    if (updateContainer) updateContainer.style.display = 'none';
                }
            }

            if (selector) {
                selector.addEventListener('change', updateProfileButtons);
                updateProfileButtons();
            }

            // Initial call to sync custom auxílio name fields
            document.querySelectorAll('.auxilio-type-select').forEach(select => {
                handleAuxilioTypeChange(select);
            });
        });
    </script>
</body>
</html>
