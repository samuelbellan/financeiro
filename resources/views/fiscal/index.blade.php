<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Radar de Concursos Fiscais & Salários | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --bg-glass: rgba(15, 23, 42, 0.75);
            --bg-glass-card: rgba(30, 41, 59, 0.7);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glass-glow: rgba(99, 102, 241, 0.3);
            --primary-grad: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --emerald-grad: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --amber-grad: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --sky-grad: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            --purple-grad: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --gold-grad: linear-gradient(135deg, #fbbf24 0%, #b45309 100%);
            --rose-grad: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
            --text-muted: #94a3b8;
            --text-light: #f8fafc;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #0b0f19;
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .layout {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow-y: auto !important;
            overflow-x: hidden;
            padding: 2rem 2.5rem 4rem 2.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(99, 102, 241, 0.4) rgba(15, 23, 42, 0.8);
        }

        .main-content::-webkit-scrollbar {
            width: 10px;
        }

        .main-content::-webkit-scrollbar-track {
            background: rgba(11, 15, 25, 0.8);
        }

        .main-content::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 6px;
            border: 2px solid rgba(11, 15, 25, 0.8);
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: rgba(99, 102, 241, 0.7);
        }

        .content-header {
            background: transparent !important;
            border-bottom: none !important;
            padding: 0 0 1.5rem 0 !important;
        }

        h1, h2, h3, h4, .outfit-font {
            font-family: 'Outfit', sans-serif;
        }

        .glass-panel {
            background: var(--bg-glass-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            box-shadow: 0 10px 35px -5px rgba(0, 0, 0, 0.4);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .glass-panel:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        /* Tabs Navigation Bar */
        .fiscal-tabs-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--border-glass);
            padding: 0.4rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .fiscal-tabs-nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .fiscal-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.92rem;
            color: #94a3b8;
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .fiscal-tab-btn:hover {
            color: #f1f5f9;
            background: rgba(255, 255, 255, 0.04);
        }

        .fiscal-tab-btn.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(79, 70, 229, 0.4) 100%);
            border: 1px solid rgba(99, 102, 241, 0.5);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
        }

        .tab-badge {
            font-size: 0.72rem;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
        }

        .fiscal-tab-btn.active .tab-badge {
            background: #6366f1;
            color: #fff;
        }

        .tab-badge-opportunity {
            background: rgba(244, 63, 94, 0.25);
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.4);
        }

        .tab-content-pane {
            display: none;
            animation: fadeIn 0.25s ease forwards;
        }

        .tab-content-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Top Banner & Header */
        .fiscal-hero {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.2), transparent 50%),
                        radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.15), transparent 50%),
                        var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 50%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .hero-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            max-width: 720px;
            line-height: 1.55;
        }

        .hero-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
        }

        .btn-primary-gradient {
            background: var(--primary-grad);
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-primary-gradient:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-telegram {
            background: linear-gradient(135deg, #229ED9 0%, #0088cc 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(34, 158, 217, 0.4);
        }

        .btn-telegram:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f1f5f9;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* KPI Cards Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .kpi-card.purple::before { background: var(--purple-grad); }
        .kpi-card.emerald::before { background: var(--emerald-grad); }
        .kpi-card.amber::before { background: var(--amber-grad); }
        .kpi-card.sky::before { background: var(--sky-grad); }
        .kpi-card.rose::before { background: var(--rose-grad); }

        .kpi-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kpi-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 0.25rem;
        }

        .kpi-sub {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Main 2-Column Grid for Tab 1 */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        /* News Feed */
        .news-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .news-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.15s ease;
        }

        .news-item:last-child {
            border-bottom: none;
        }

        .news-item:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .news-meta {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .badge-esfera {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-federal { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.4); }
        .badge-estadual { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.4); }
        .badge-municipal { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.4); }

        .badge-status {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
        }

        .news-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 0.4rem;
            line-height: 1.4;
        }

        .news-desc {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.45;
            margin-bottom: 0.75rem;
        }

        .news-salary-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.82rem;
            color: #34d399;
            font-weight: 600;
        }

        .news-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .news-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm-action {
            font-size: 0.75rem;
            padding: 0.35rem 0.7rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
        }

        .btn-sm-action:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .btn-sm-tg {
            background: rgba(34, 158, 217, 0.15);
            border-color: rgba(34, 158, 217, 0.3);
            color: #38bdf8;
        }

        .btn-sm-tg:hover {
            background: rgba(34, 158, 217, 0.3);
            color: #fff;
        }

        /* Widgets */
        .widget-title {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .telegram-widget-body {
            padding: 1.5rem;
        }

        .bot-command-box {
            background: rgba(0, 0, 0, 0.3);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .bot-command-box code {
            color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            padding: 0.15rem 0.35rem;
            border-radius: 4px;
            font-family: monospace;
        }

        /* Tables & Contests Section */
        .contests-section {
            margin-top: 2rem;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-glass);
        }

        .search-input {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 0.6rem 1rem;
            color: #fff;
            font-size: 0.88rem;
            min-width: 260px;
        }

        .search-input:focus {
            outline: none;
            border-color: #6366f1;
        }

        .filter-select {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 0.6rem 1rem;
            color: #fff;
            font-size: 0.88rem;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #6366f1;
        }

        .fiscal-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .fiscal-table th {
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-glass);
        }

        .fiscal-table td {
            padding: 1.15rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        .fiscal-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .orgao-badge-name {
            font-weight: 700;
            color: #fff;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .salary-highlight {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: #34d399;
            font-size: 1.05rem;
        }

        .salary-sub {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        /* ─────────────────────────────────────────────────────────────
           TAB 2: PANORAMA FISCOS ESTADUAIS (27 SEFAZ) STYLES
           ───────────────────────────────────────────────────────────── */
        .state-toolbar {
            background: var(--bg-glass-card);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .region-filter-bar {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .region-btn {
            padding: 0.45rem 0.9rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #94a3b8;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .region-btn:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .region-btn.active {
            background: rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.5);
            color: #a5b4fc;
        }

        /* Grid de Cards dos 27 Fiscos Estaduais */
        .state-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .state-card {
            background: var(--bg-glass-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .state-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .state-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .state-uf-badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            flex-shrink: 0;
        }

        .uf-sudeste { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); }
        .uf-sul { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }
        .uf-nordeste { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .uf-centro-oeste { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .uf-norte { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }

        .state-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }

        .state-card-orgao {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.3;
            margin-top: 0.15rem;
        }

        /* Box de Vigência / Validade */
        .state-vigencia-box {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 1.15rem;
        }

        .vigencia-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .vigencia-vencido {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.35);
        }

        .vigencia-vigente {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        .vigencia-prorrogado {
            background: rgba(14, 165, 233, 0.15);
            color: #7dd3fc;
            border: 1px solid rgba(14, 165, 233, 0.35);
        }

        .vigencia-desc {
            font-size: 0.8rem;
            color: #cbd5e1;
            line-height: 1.4;
            margin-top: 0.5rem;
        }

        /* Salary Box Inside State Card */
        .state-salary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.15rem;
        }

        .salary-label-sm {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .salary-value-lg {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #34d399;
            margin-top: 0.1rem;
        }

        .salary-value-real {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: #6ee7b7;
            margin-top: 0.1rem;
        }

        /* Ultimo Concurso Meta */
        .state-concurso-history {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
            padding: 0.5rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 0.75rem;
        }

        /* Mini News Feed inside state card */
        .state-news-mini {
            background: rgba(0, 0, 0, 0.18);
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1.15rem;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .state-news-mini-title {
            font-size: 0.74rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .state-news-link {
            color: #e2e8f0;
            font-size: 0.82rem;
            line-height: 1.35;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .state-news-link:hover {
            color: #818cf8;
        }

        .state-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .state-card-actions .btn-sm-action {
            flex: 1;
            justify-content: center;
            padding: 0.55rem;
            font-size: 0.8rem;
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1.5rem;
        }

        .modal-card {
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7);
            position: relative;
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            background: #0f172a;
            z-index: 10;
        }

        .modal-body {
            padding: 2rem;
        }

        .salary-box-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .salary-box-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 1.25rem;
        }

        .edit-form-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .edit-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 0.85rem;
        }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-control-dark {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            padding: 0.6rem 0.8rem;
            color: #fff;
            font-size: 0.88rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-control-dark:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        textarea.form-control-dark {
            resize: vertical;
            min-height: 65px;
        }

        .ai-url-extractor-card {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.75) 0%, rgba(15, 23, 42, 0.85) 100%);
            border: 1px solid rgba(168, 85, 247, 0.35);
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px -10px rgba(168, 85, 247, 0.25);
            position: relative;
            overflow: hidden;
        }

        .ai-url-extractor-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .ai-badge {
            background: rgba(168, 85, 247, 0.18);
            color: #d8b4fe;
            border: 1px solid rgba(168, 85, 247, 0.4);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .toast-notify {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: #1e293b;
            color: #fff;
            border: 1px solid #6366f1;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 10000;
            font-weight: 500;
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

                    <a href="{{ route('fiscal.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                        <span>Concursos Fiscais</span>
                    </a>

                    <a href="{{ route('salary.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Projetor Salarial</span>
                    </a>

                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
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
                    <button type="submit" class="btn-logout">Sair</button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <button type="button" class="btn-toggle-sidebar js-toggle-sidebar" title="Alternar barra lateral (Ctrl + \)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0;">Radar de Concursos Fiscais & Remunerações</h1>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Monitoramento aprofundado da carreira fiscal: Federal (RFB), 27 Fiscos Estaduais (SEFAZ) e 30+ Municipais (ISS)</p>
                    </div>
                </div>
            </header>

            <!-- Navigation Tabs Bar -->
            <div class="fiscal-tabs-container">
                <div class="fiscal-tabs-nav">
                    <button class="fiscal-tab-btn {{ $tab !== 'estaduais' ? 'active' : '' }}" onclick="switchFiscalTab('radar', this)">
                        <span>📡</span>
                        <span>Radar Geral & Notícias</span>
                        <span class="tab-badge">{{ $totalNoticias }} Notícias</span>
                    </button>

                    <button class="fiscal-tab-btn {{ $tab === 'estaduais' ? 'active' : '' }}" onclick="switchFiscalTab('estaduais', this)">
                        <span>🏛️</span>
                        <span>Panorama Fiscos Estaduais (27 SEFAZ)</span>
                        <span class="tab-badge tab-badge-opportunity">{{ $estaduaisVencidos }} Vencidos / Oportunidades</span>
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; padding-right: 0.5rem;">
                    <button id="btnCrawlHeader" class="btn-sm-action" onclick="executarCrawler()" title="Atualizar Notícias e Feeds">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        <span>Rastrear Agora</span>
                    </button>
                    <button id="btnTestTelegramHeader" class="btn-sm-action btn-sm-tg" onclick="testarTelegram()" title="Disparar teste para o Bot">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        <span>Testar Bot</span>
                    </button>
                </div>
            </div>

            <!-- Smart AI News Extractor Card (OmniRoute AI Integration) -->
            <div class="ai-url-extractor-card" style="margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 0 15px rgba(168, 85, 247, 0.4); flex-shrink: 0;">
                            ⚡
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <h3 style="font-size: 1.08rem; font-weight: 700; color: #fff; margin: 0;">Incluir Notícia ou Novo Concurso Fiscal via Link (IA)</h3>
                                <span class="ai-badge">Motor IA Fiscal</span>
                            </div>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
                                Cole a URL de qualquer notícia de concurso fiscal (SEFAZ, Receita Federal, ISS de capitais ou cidades do interior). A IA interpreta a matéria, extrai status, banca, vagas e salários, atualizando os cards existentes ou <strong>adicionando o novo concurso ao Radar Geral</strong> automaticamente!
                            </p>
                        </div>
                    </div>
                </div>

                <form onsubmit="analisarNoticiaComIa(event, 'inputAiUrlGlobal', 'checkNotifyTelegramGlobal')" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 280px; position: relative;">
                        <input type="url" id="inputAiUrlGlobal" placeholder="🔗 Cole a URL da notícia aqui (ex: https://www.direcaoconcursos.com.br/... ou Estratégia / Gran)" class="form-control-dark" style="padding-left: 2.6rem; height: 46px; border-radius: 12px; font-size: 0.9rem;" required>
                        <span style="position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: #a855f7; font-size: 1.1rem;">🔗</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                        <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: #cbd5e1; cursor: pointer; user-select: none; margin: 0; background: rgba(0,0,0,0.25); padding: 0.65rem 0.85rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08);">
                            <input type="checkbox" id="checkNotifyTelegramGlobal" style="cursor: pointer;">
                            <span>🔔 Enviar Alerta ao Telegram</span>
                        </label>

                        <button type="submit" id="btnAiExtractGlobal" class="btn-action" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: #fff; border: none; height: 46px; padding: 0 1.35rem; font-weight: 600; border-radius: 12px; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35); cursor: pointer; transition: transform 0.2s;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            <span>Analisar & Atualizar com IA</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
                 ABA 1: RADAR GERAL & CONCURSOS QUENTES
                 ══════════════════════════════════════════════════════════════════════ -->
            <div id="pane-radar" class="tab-content-pane {{ $tab !== 'estaduais' ? 'active' : '' }}">
                <!-- Hero Banner -->
                <div class="fiscal-hero">
                    <div>
                        <div class="hero-title">
                            <span>🏛️</span> Radar Fiscal Inteligente
                        </div>
                        <p class="hero-desc">
                            Acompanhe notícias, autorizações, bancas e editais da carreira fiscal no Brasil inteiro. Cada concurso é acompanhado de uma <strong>pesquisa minuciosa de remuneração</strong> (vencimento básico, produtividade/quotas reais, benefícios e teto constitucional).
                        </p>
                    </div>
                    <div class="hero-actions">
                        <button class="btn-action" style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #fff; border: none; font-weight: 600; box-shadow: 0 4px 15px rgba(168, 85, 247, 0.35);" onclick="abrirModalExtratorUrl()">
                            <span>⚡ + Adicionar Notícia / Novo Concurso</span>
                        </button>
                        <button id="btnCrawl" class="btn-action btn-primary-gradient" onclick="executarCrawler()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                            <span>Atualizar Notícias Agora</span>
                        </button>
                        <button id="btnTestTelegram" class="btn-action btn-telegram" onclick="testarTelegram()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            <span>Testar Alerta Telegram</span>
                        </button>
                    </div>
                </div>

                <!-- KPIs Globais -->
                <div class="kpi-grid">
                    <div class="glass-panel kpi-card purple">
                        <div class="kpi-label">
                            <span>Órgãos Mapeados</span>
                            <span>🏛️</span>
                        </div>
                        <div class="kpi-value">{{ $totalConcursos }}</div>
                        <div class="kpi-sub">RFB + 27 SEFAZ + 30+ ISS</div>
                    </div>

                    <div class="glass-panel kpi-card emerald">
                        <div class="kpi-label">
                            <span>Editais & Comissões</span>
                            <span>🔥</span>
                        </div>
                        <div class="kpi-value">{{ $comissaoOuBanca + $editaisAbertos }}</div>
                        <div class="kpi-sub">{{ $comissaoOuBanca }} comissões/bancas ativas</div>
                    </div>

                    <div class="glass-panel kpi-card amber">
                        <div class="kpi-label">
                            <span>Maior Salário Inicial</span>
                            <span>💎</span>
                        </div>
                        <div class="kpi-value">R$ {{ number_format($maiorSalarioInicial, 0, ',', '.') }}</div>
                        <div class="kpi-sub">Teto Bruto Inicial</div>
                    </div>

                    <div class="glass-panel kpi-card blue">
                        <div class="kpi-label">
                            <span>Média Salarial Inicial</span>
                            <span>📊</span>
                        </div>
                        <div class="kpi-value">R$ {{ number_format($mediaSalarioInicial, 0, ',', '.') }}</div>
                        <div class="kpi-sub">Base + Produtividade Fiscal</div>
                    </div>
                </div>

                <!-- Main 2-Column Grid: News Feed + Telegram & Top Salários -->
                <div class="main-grid">
                    <!-- Feed de Notícias Fiscais -->
                    <div class="glass-panel">
                        <div class="news-header">
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <span style="font-size: 1.25rem;">📰</span>
                                <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff;">Feed de Notícias & Editais em Tempo Real</h2>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.6rem;">
                                <button class="btn-sm-action" style="background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.4); color: #c084fc;" onclick="abrirModalExtratorUrl()">
                                    <span>⚡ + Incluir Link</span>
                                </button>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $totalNoticias }} matérias rastreadas</span>
                            </div>
                        </div>

                        <div class="news-list" id="newsFeedContainer">
                            @forelse($noticiasRecentes as $noticia)
                                <div class="news-item">
                                    <div class="news-meta">
                                        <span class="badge-esfera badge-{{ $noticia->esfera }}">{{ $noticia->esfera }}</span>
                                        @if($noticia->uf)
                                            <span class="badge-status">{{ $noticia->uf }}</span>
                                        @endif
                                        @if($noticia->status_detectado)
                                            <span class="badge-status" style="background: rgba(99, 102, 241, 0.15); color: #a5b4fc;">{{ $noticia->status_detectado }}</span>
                                        @endif
                                        <span style="font-size: 0.75rem; color: #64748b; margin-left: auto;">
                                            {{ $noticia->publicado_em ? $noticia->publicado_em->diffForHumans() : 'recente' }}
                                        </span>
                                    </div>

                                    <div class="news-title">{{ $noticia->titulo }}</div>
                                    <div class="news-desc">{{ Str::limit($noticia->resumo, 180) }}</div>

                                    @if($noticia->concurso)
                                        <div style="margin-bottom: 0.75rem;">
                                            <span class="news-salary-pill">
                                                <span>💰 Inicial: {{ $noticia->concurso->remuneracao_inicial_formatada }}</span>
                                                <span style="opacity: 0.6;">|</span>
                                                <span>Real: {{ $noticia->concurso->remuneracao_real_formatada }}</span>
                                            </span>
                                        </div>
                                    @endif

                                    <div class="news-footer">
                                        <span>Fonte: <strong>{{ $noticia->fonte }}</strong></span>
                                        <div class="news-actions">
                                            @if($noticia->concurso_id || $noticia->fiscal_concurso_id)
                                                <button class="btn-sm-action" onclick="abrirModalConcurso({{ $noticia->fiscal_concurso_id ?: $noticia->concurso_id }})">
                                                    <span>📊 Raio-X Salarial</span>
                                                </button>
                                            @endif
                                            <button class="btn-sm-action btn-sm-tg" onclick="enviarNoticiaTelegram({{ $noticia->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                                <span>Enviar Telegram</span>
                                            </button>
                                            <a href="{{ $noticia->url }}" target="_blank" class="btn-sm-action" style="text-decoration: none;">
                                                <span>🔗 Ver Fonte</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">Nenhuma notícia encontrada no momento.</p>
                                    <button class="btn-action btn-primary-gradient" onclick="executarCrawler()">Executar Rastreamento Agora</button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Coluna da Direita: Central Telegram & Top Salários -->
                    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                        <!-- Central do Telegram -->
                        <div class="glass-panel">
                            <div class="widget-title">
                                <span style="color: #38bdf8;">✈️</span>
                                <span>Central do Bot no Telegram</span>
                            </div>
                            <div class="telegram-widget-body">
                                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 1rem;">
                                    Você pode conversar diretamente com o seu Bot do Telegram e solicitar remunerações detalhadas de qualquer fisco do país!
                                </p>

                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 0.25rem;">Chat ID Autorizado:</div>
                                    <div style="font-family: monospace; font-size: 0.88rem; background: rgba(0,0,0,0.3); padding: 0.4rem 0.75rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #38bdf8;">
                                        {{ config('telegram.allowed_chat_id') ?: 'Configurado no .env' }}
                                    </div>
                                </div>

                                <div class="bot-command-box">
                                    <strong style="color: #fff; display: block; margin-bottom: 0.5rem;">Comandos no Telegram:</strong>
                                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                        <div><code>/fiscal</code> — Concursos mais quentes</div>
                                        <div><code>/sefaz sp</code> — Raio-x da SEFAZ SP (ou qualquer UF)</div>
                                        <div><code>/iss sp</code> — Raio-x do ISS SP (ou cidade)</div>
                                        <div><code>/receita</code> — Salários Receita Federal</div>
                                        <div><code>/noticias_fiscal</code> — 5 últimas notícias</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ranking Top Remunerações Reais -->
                        <div class="glass-panel">
                            <div class="widget-title">
                                <span style="color: #fbbf24;">🏆</span>
                                <span>Top Remunerações Reais (Transparência)</span>
                            </div>
                            <div style="padding: 1rem 1.25rem;">
                                @foreach($topSalarios as $idx => $top)
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0; border-bottom: 1px solid rgba(255,255,255,0.04);">
                                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                                            <span style="font-family: 'Outfit', sans-serif; font-weight: 700; color: #64748b; font-size: 0.85rem;">#{{ $idx + 1 }}</span>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.9rem; color: #fff;">{{ $top->sigla }}</div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ Str::limit($top->cargo_principal, 24) }}</div>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-family: 'Outfit', sans-serif; font-weight: 700; color: #34d399; font-size: 0.95rem;">
                                                {{ $top->remuneracao_real_formatada }}
                                            </div>
                                            <div style="font-size: 0.72rem; color: #64748b;">Inicial: {{ $top->remuneracao_inicial_formatada }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela Comparativa Geral de Concursos Fiscais -->
                <div class="glass-panel contests-section">
                    <div class="table-controls">
                        <div>
                            <h2 style="font-size: 1.2rem; font-weight: 700; color: #fff; margin: 0 0 0.25rem 0;">Acervo Completo de Concursos Fiscais</h2>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">Pesquisa com composição salarial, produtividade, benefícios e teto</p>
                        </div>

                        <form method="GET" action="{{ route('fiscal.index') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                            <input type="hidden" name="tab" value="radar">
                            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar órgão, UF, cidade..." class="search-input">

                            <select name="esfera" class="filter-select" onchange="this.form.submit()">
                                <option value="todas" {{ $esfera === 'todas' ? 'selected' : '' }}>Todas as Esferas</option>
                                <option value="federal" {{ $esfera === 'federal' ? 'selected' : '' }}>Federal (RFB)</option>
                                <option value="estadual" {{ $esfera === 'estadual' ? 'selected' : '' }}>Estaduais (27 SEFAZ)</option>
                                <option value="municipal" {{ $esfera === 'municipal' ? 'selected' : '' }}>Municipais (ISS)</option>
                            </select>

                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="todos" {{ $status === 'todos' ? 'selected' : '' }}>Todos os Status</option>
                                <option value="edital_publicado" {{ $status === 'edital_publicado' ? 'selected' : '' }}>Edital Publicado</option>
                                <option value="banca_definida" {{ $status === 'banca_definida' ? 'selected' : '' }}>Banca Definida</option>
                                <option value="comissao_formada" {{ $status === 'comissao_formada' ? 'selected' : '' }}>Comissão Formada</option>
                                <option value="autorizado" {{ $status === 'autorizado' ? 'selected' : '' }}>Autorizado</option>
                                <option value="solicitado" {{ $status === 'solicitado' ? 'selected' : '' }}>Solicitado</option>
                                <option value="previsto" {{ $status === 'previsto' ? 'selected' : '' }}>Previsto</option>
                            </select>

                            <select name="ordenar" class="filter-select" onchange="this.form.submit()">
                                <option value="salario_desc" {{ $ordenar === 'salario_desc' ? 'selected' : '' }}>Maior Inicial Bruto</option>
                                <option value="real_desc" {{ $ordenar === 'real_desc' ? 'selected' : '' }}>Maior Real (Transparência)</option>
                                <option value="nome" {{ $ordenar === 'nome' ? 'selected' : '' }}>Nome / Sigla (A-Z)</option>
                            </select>
                        </form>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="fiscal-table">
                            <thead>
                                <tr>
                                    <th>Órgão & Sigla</th>
                                    <th>Esfera / UF</th>
                                    <th>Cargo Principal</th>
                                    <th>Status</th>
                                    <th>Inicial Bruto</th>
                                    <th>Média Real (Transp.)</th>
                                    <th>Teto / Final</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($concursos as $c)
                                    <tr>
                                        <td>
                                            <div class="orgao-badge-name">
                                                <span>{{ $c->sigla }}</span>
                                                @if($c->editado_manualmente)
                                                    <span class="badge-status" style="background: rgba(234, 179, 8, 0.2); color: #fde047; font-size: 0.68rem; border: 1px dashed rgba(234, 179, 8, 0.5); padding: 0.1rem 0.35rem;">✏️ Manual</span>
                                                @endif
                                            </div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted);">{{ Str::limit($c->nome_orgao, 36) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge-esfera badge-{{ $c->esfera }}">{{ $c->esfera }}</span>
                                            @if($c->uf)
                                                <span style="font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-left: 0.25rem;">{{ $c->uf }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: #e2e8f0;">{{ $c->cargo_principal }}</div>
                                            <div style="font-size: 0.75rem; color: #64748b;">{{ Str::limit($c->requisito_escolaridade, 35) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge-status">{{ $c->status_formatado }}</span>
                                        </td>
                                        <td>
                                            <div class="salary-highlight">{{ $c->remuneracao_inicial_formatada }}</div>
                                            <div class="salary-sub">Base: R$ {{ number_format($c->vencimento_basico, 0, ',', '.') }}</div>
                                        </td>
                                        <td>
                                            <div style="font-family: 'Outfit', sans-serif; font-weight: 600; color: #6ee7b7; font-size: 0.98rem;">
                                                {{ $c->remuneracao_real_formatada }}
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-family: 'Outfit', sans-serif; font-weight: 600; color: #cbd5e1; font-size: 0.95rem;">
                                                {{ $c->remuneracao_teto_formatada }}
                                            </div>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                                                <button class="btn-sm-action" onclick="abrirModalConcurso({{ $c->id }})" title="Ver Raio-X Aprofundado">
                                                    <span>📊 Raio-X</span>
                                                </button>
                                                <button class="btn-sm-action" style="background: rgba(168, 85, 247, 0.15); color: #d8b4fe; border-color: rgba(168, 85, 247, 0.35);" onclick="abrirModalExtratorUrl({{ $c->id }}, '{{ $c->sigla }}')" title="Atualizar dados deste concurso colando link de notícia">
                                                    <span>⚡ IA</span>
                                                </button>
                                                <button class="btn-sm-action" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3);" onclick="abrirModalEdicao({{ $c->id }})" title="Editar Manualmente este Concurso">
                                                    <span>✏️</span>
                                                </button>
                                                <button class="btn-sm-action btn-sm-tg" onclick="enviarConcursoTelegram({{ $c->id }})" title="Enviar para Telegram">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                            Nenhum concurso fiscal encontrado para os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($concursos->hasPages())
                        <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--border-glass);">
                            {{ $concursos->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════
                 ABA 2: PANORAMA DOS FISCOS ESTADUAIS (27 SEFAZ)
                 ══════════════════════════════════════════════════════════════════════ -->
            <div id="pane-estaduais" class="tab-content-pane {{ $tab === 'estaduais' ? 'active' : '' }}">
                <!-- Hero Banner dos Fiscos Estaduais -->
                <div class="fiscal-hero" style="background: radial-gradient(circle at top right, rgba(14, 165, 233, 0.2), transparent 50%), radial-gradient(circle at bottom left, rgba(244, 63, 94, 0.15), transparent 50%), var(--bg-glass);">
                    <div>
                        <div class="hero-title">
                            <span>🏛️</span> Panorama dos 27 Fiscos Estaduais (SEFAZ)
                        </div>
                        <p class="hero-desc">
                            Mapeamento detalhado de <strong>todas as 27 Secretarias de Fazenda Estaduais do Brasil (26 Estados + Distrito Federal)</strong>. Analise o prazo de vigência e validade dos últimos concursos, certames vencidos (com alta probabilidade de novo edital), remunerações médias reais da transparência e últimas notícias por estado.
                        </p>
                    </div>
                    <div class="hero-actions">
                        <div style="display: flex; gap: 0.5rem;">
                            <button id="viewBtnCards" class="btn-action btn-primary-gradient" onclick="toggleStateView('cards')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                <span>Visualizar em Cards</span>
                            </button>
                            <button id="viewBtnTable" class="btn-action btn-outline" onclick="toggleStateView('table')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                                <span>Visualizar em Tabela</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPIs Exclusivos dos Fiscos Estaduais -->
                <div class="kpi-grid">
                    <div class="glass-panel kpi-card purple">
                        <div class="kpi-label">
                            <span>Fiscos Estaduais Mapeados</span>
                            <span>🏛️</span>
                        </div>
                        <div class="kpi-value">{{ $totalEstaduais }}/27</div>
                        <div class="kpi-sub">100% dos Estados + DF cobertos</div>
                    </div>

                    <div class="glass-panel kpi-card rose">
                        <div class="kpi-label">
                            <span>Concursos Vencidos (Sem Validade)</span>
                            <span>🚨</span>
                        </div>
                        <div class="kpi-value">{{ $estaduaisVencidos }}</div>
                        <div class="kpi-sub">Altíssima chance de novos editais</div>
                    </div>

                    <div class="glass-panel kpi-card emerald">
                        <div class="kpi-label">
                            <span>Concursos Vigentes / Prorrogados</span>
                            <span>🟢</span>
                        </div>
                        <div class="kpi-value">{{ $estaduaisVigentes }}</div>
                        <div class="kpi-sub">Concurso válido ou em prorrogação</div>
                    </div>

                    <div class="glass-panel kpi-card amber">
                        <div class="kpi-label">
                            <span>Média Real Transparência</span>
                            <span>💰</span>
                        </div>
                        <div class="kpi-value">R$ {{ number_format($mediaRealEstadual, 0, ',', '.') }}</div>
                        <div class="kpi-sub">Inicial Médio: R$ {{ number_format($mediaSalarioEstadual, 0, ',', '.') }}</div>
                    </div>
                </div>

                <!-- Toolbar de Filtros para Fiscos Estaduais -->
                <div class="state-toolbar">
                    <form method="GET" action="{{ route('fiscal.index') }}" id="stateFilterForm" style="display: flex; gap: 1rem; flex-wrap: wrap; width: 100%; align-items: center; justify-content: space-between;">
                        <input type="hidden" name="tab" value="estaduais">

                        <!-- Filtro por Região -->
                        <div class="region-filter-bar">
                            <button type="submit" name="regiao_estadual" value="todas" class="region-btn {{ $regiaoEstadual === 'todas' ? 'active' : '' }}">Todas Regiões</button>
                            <button type="submit" name="regiao_estadual" value="Sudeste" class="region-btn {{ $regiaoEstadual === 'Sudeste' ? 'active' : '' }}">Sudeste</button>
                            <button type="submit" name="regiao_estadual" value="Sul" class="region-btn {{ $regiaoEstadual === 'Sul' ? 'active' : '' }}">Sul</button>
                            <button type="submit" name="regiao_estadual" value="Nordeste" class="region-btn {{ $regiaoEstadual === 'Nordeste' ? 'active' : '' }}">Nordeste</button>
                            <button type="submit" name="regiao_estadual" value="Centro-Oeste" class="region-btn {{ $regiaoEstadual === 'Centro-Oeste' ? 'active' : '' }}">Centro-Oeste</button>
                            <button type="submit" name="regiao_estadual" value="Norte" class="region-btn {{ $regiaoEstadual === 'Norte' ? 'active' : '' }}">Norte</button>
                        </div>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                            <!-- Busca por UF ou Órgão -->
                            <input type="text" name="busca_estadual" value="{{ $buscaEstadual }}" placeholder="Buscar UF (ex: SP, MG, RJ, CE)..." class="search-input" style="min-width: 220px;">

                            <!-- Filtro de Vigência -->
                            <select name="vigencia_estadual" class="filter-select" onchange="document.getElementById('stateFilterForm').submit()">
                                <option value="todas" {{ $vigenciaEstadual === 'todas' ? 'selected' : '' }}>Todas as Vigências</option>
                                <option value="vencidos" {{ $vigenciaEstadual === 'vencidos' ? 'selected' : '' }}>🚨 Vencidos (Sem Concurso Válido)</option>
                                <option value="vigentes" {{ $vigenciaEstadual === 'vigentes' ? 'selected' : '' }}>🟢 Vigentes / Prorrogados</option>
                                <option value="novos_editais" {{ $vigenciaEstadual === 'novos_editais' ? 'selected' : '' }}>⚡ Editais & Comissões Iminentes</option>
                            </select>

                            <!-- Ordenação -->
                            <select name="ordenar_estadual" class="filter-select" onchange="document.getElementById('stateFilterForm').submit()">
                                <option value="salario_desc" {{ $ordenarEstadual === 'salario_desc' ? 'selected' : '' }}>Maior Inicial Bruto</option>
                                <option value="real_desc" {{ $ordenarEstadual === 'real_desc' ? 'selected' : '' }}>Maior Real Transparência</option>
                                <option value="antigos" {{ $ordenarEstadual === 'antigos' ? 'selected' : '' }}>Mais Tempo sem Concurso</option>
                                <option value="recentes" {{ $ordenarEstadual === 'recentes' ? 'selected' : '' }}>Concursos Mais Recentes</option>
                                <option value="uf" {{ $ordenarEstadual === 'uf' ? 'selected' : '' }}>Ordem por UF (A-Z)</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- 1. Grid View de Cards dos 27 Fiscos Estaduais -->
                <div id="stateCardsContainer" class="state-cards-grid">
                    @forelse($fiscosEstaduais as $fisco)
                        @php
                            $regClass = match($fisco->regiao_formatada) {
                                'Sudeste' => 'uf-sudeste',
                                'Sul' => 'uf-sul',
                                'Nordeste' => 'uf-nordeste',
                                'Centro-Oeste' => 'uf-centro-oeste',
                                'Norte' => 'uf-norte',
                                default => 'uf-sudeste',
                            };
                            $isVencido = in_array($fisco->ultimo_concurso_status_vigencia, ['vencido', 'sem_concurso_valido']);
                        @endphp
                        <div class="state-card">
                            <div>
                                <!-- Header do Card -->
                                <div class="state-card-header">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="state-uf-badge {{ $regClass }}">
                                            {{ $fisco->uf }}
                                        </div>
                                        <div>
                                            <h3 class="state-card-title">{{ $fisco->sigla }}</h3>
                                            <div class="state-card-orgao">{{ Str::limit($fisco->nome_orgao, 36) }}</div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        @if($fisco->editado_manualmente)
                                            <span class="badge-status" style="background: rgba(234, 179, 8, 0.2); color: #fde047; font-size: 0.68rem; border: 1px dashed rgba(234, 179, 8, 0.5); padding: 0.15rem 0.4rem;">✏️ Manual</span>
                                        @endif
                                        <span class="badge-status" style="background: rgba(99, 102, 241, 0.12); color: #a5b4fc; font-weight: 600;">
                                            {{ $fisco->regiao_formatada }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Status do Concurso Atual -->
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Status do Novo Concurso:</span>
                                    <span class="badge-status" style="background: rgba(255,255,255,0.08); font-weight: 600; color: #fff;">
                                        {{ $fisco->status_formatado }}
                                    </span>
                                </div>

                                <!-- Box de Vigência & Validade -->
                                <div class="state-vigencia-box">
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.4rem;">
                                        @if($isVencido)
                                            <span class="vigencia-pill vigencia-vencido">
                                                <span>🚨 Vencido</span>
                                                @if($fisco->anos_sem_concurso)
                                                    <span>• {{ $fisco->anos_sem_concurso }} anos sem edital</span>
                                                @endif
                                            </span>
                                        @elseif($fisco->ultimo_concurso_status_vigencia === 'prorrogado')
                                            <span class="vigencia-pill vigencia-prorrogado">
                                                <span>🔄 Prorrogado</span>
                                            </span>
                                        @else
                                            <span class="vigencia-pill vigencia-vigente">
                                                <span>🟢 Vigente</span>
                                            </span>
                                        @endif

                                        <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600;">
                                            Validade: {{ $fisco->ultimo_concurso_validade_fim ?: 'Consultar' }}
                                        </span>
                                    </div>

                                    @if($fisco->ultimo_concurso_vigencia_detalhes)
                                        <div class="vigencia-desc">
                                            {{ $fisco->ultimo_concurso_vigencia_detalhes }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Dados Salariais -->
                                <div class="state-salary-grid">
                                    <div>
                                        <div class="salary-label-sm">Inicial Bruto:</div>
                                        <div class="salary-value-lg">{{ $fisco->remuneracao_inicial_formatada }}</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Base: R$ {{ number_format($fisco->vencimento_basico, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <div class="salary-label-sm">Média Real Transp.:</div>
                                        <div class="salary-value-real">{{ $fisco->remuneracao_real_formatada }}</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Teto: {{ $fisco->remuneracao_teto_formatada }}</div>
                                    </div>
                                </div>

                                <!-- Ficha do Último Concurso -->
                                <div class="state-concurso-history">
                                    <span>Último Concurso: <strong>{{ $fisco->ultimo_concurso_ano ?: 'N/D' }}</strong> ({{ $fisco->ultimo_concurso_banca ?: 'Banca N/D' }})</span>
                                    <span>{{ $fisco->ultimo_concurso_vagas ?: 'Vagas N/D' }}</span>
                                </div>

                                <!-- Mini Notícias do Estado -->
                                @if($fisco->noticias->count() > 0)
                                    <div class="state-news-mini">
                                        <div class="state-news-mini-title">
                                            <span>📰</span> Última Notícia Rastreata
                                        </div>
                                        @php $lastNews = $fisco->noticias->first(); @endphp
                                        <a href="{{ $lastNews->url }}" target="_blank" class="state-news-link" title="{{ $lastNews->titulo }}">
                                            {{ $lastNews->titulo }}
                                        </a>
                                        <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.3rem; display: flex; justify-content: space-between; align-items: center;">
                                            <span>{{ $lastNews->fonte }} • {{ $lastNews->publicado_em ? $lastNews->publicado_em->diffForHumans() : 'recente' }}</span>
                                            <button class="btn-sm-action btn-sm-tg" style="padding: 0.15rem 0.4rem; font-size: 0.68rem;" onclick="event.preventDefault(); enviarNoticiaTelegram({{ $lastNews->id }})">
                                                <span>Telegram</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Ações do Card -->
                            <div class="state-card-actions">
                                <button class="btn-sm-action" onclick="abrirModalConcurso({{ $fisco->id }})">
                                    <span>📊 Raio-X</span>
                                </button>
                                <button class="btn-sm-action" style="background: rgba(168, 85, 247, 0.15); color: #d8b4fe; border-color: rgba(168, 85, 247, 0.35);" onclick="abrirModalExtratorUrl({{ $fisco->id }}, '{{ $fisco->sigla }}')" title="Atualizar dados deste estado colando link de notícia com IA">
                                    <span>⚡ Atualizar IA</span>
                                </button>
                                <button class="btn-sm-action" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3);" onclick="abrirModalEdicao({{ $fisco->id }})" title="Editar Manualmente este Concurso">
                                    <span>✏️</span>
                                </button>
                                <button class="btn-sm-action btn-sm-tg" onclick="enviarConcursoTelegram({{ $fisco->id }})" title="Enviar Raio-X ao Telegram">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                    <span>Telegram</span>
                                </button>
                                @if($fisco->link_portal_transparencia)
                                    <a href="{{ $fisco->link_portal_transparencia }}" target="_blank" class="btn-sm-action" style="flex: 0 0 auto;" title="Abrir Portal da Transparência">
                                        <span>🏛️</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: var(--text-muted); background: var(--bg-glass-card); border-radius: 16px;">
                            <p style="font-size: 1.1rem;">Nenhum fisco estadual encontrado para os filtros selecionados.</p>
                            <a href="{{ route('fiscal.index', ['tab' => 'estaduais']) }}" class="btn-action btn-primary-gradient" style="margin-top: 1rem;">Limpar Filtros</a>
                        </div>
                    @endforelse
                </div>

                <!-- 2. Table View Completa dos 27 Fiscos Estaduais -->
                <div id="stateTableContainer" class="glass-panel" style="display: none; overflow-x: auto; margin-bottom: 3rem;">
                    <table class="fiscal-table">
                        <thead>
                            <tr>
                                <th>UF & Órgão</th>
                                <th>Região</th>
                                <th>Status Concurso</th>
                                <th>Vigência do Último Edital</th>
                                <th>Último Concurso</th>
                                <th>Inicial Bruto</th>
                                <th>Média Real (Transp.)</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fiscosEstaduais as $f)
                                @php
                                    $isVenc = in_array($f->ultimo_concurso_status_vigencia, ['vencido', 'sem_concurso_valido']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="orgao-badge-name">
                                            <span class="badge-status" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc; font-weight: 700;">{{ $f->uf }}</span>
                                            <span>{{ $f->sigla }}</span>
                                            @if($f->editado_manualmente)
                                                <span class="badge-status" style="background: rgba(234, 179, 8, 0.2); color: #fde047; font-size: 0.68rem; border: 1px dashed rgba(234, 179, 8, 0.5); padding: 0.1rem 0.35rem;">✏️ Manual</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">{{ Str::limit($f->nome_orgao, 36) }}</div>
                                    </td>
                                    <td>
                                        <span class="badge-status">{{ $f->regiao_formatada }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-status" style="background: rgba(255, 255, 255, 0.06); color: #fff;">{{ $f->status_formatado }}</span>
                                    </td>
                                    <td>
                                        @if($isVenc)
                                            <div style="font-weight: 700; color: #fb7185; font-size: 0.85rem;">🚨 Vencido</div>
                                            <div style="font-size: 0.72rem; color: #94a3b8;">{{ $f->ultimo_concurso_validade_fim ?: 'Expirado' }}</div>
                                        @else
                                            <div style="font-weight: 700; color: #34d399; font-size: 0.85rem;">🟢 Vigente</div>
                                            <div style="font-size: 0.72rem; color: #94a3b8;">Até {{ $f->ultimo_concurso_validade_fim }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #e2e8f0;">{{ $f->ultimo_concurso_ano ?: 'N/D' }} ({{ $f->ultimo_concurso_banca ?: 'Banca N/D' }})</div>
                                        <div style="font-size: 0.72rem; color: #64748b;">{{ $f->ultimo_concurso_vagas ?: 'Vagas N/D' }}</div>
                                    </td>
                                    <td>
                                        <div class="salary-highlight">{{ $f->remuneracao_inicial_formatada }}</div>
                                        <div class="salary-sub">Base: R$ {{ number_format($f->vencimento_basico, 0, ',', '.') }}</div>
                                    </td>
                                    <td>
                                        <div style="font-family: 'Outfit', sans-serif; font-weight: 600; color: #6ee7b7; font-size: 0.98rem;">
                                            {{ $f->remuneracao_real_formatada }}
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                                            <button class="btn-sm-action" onclick="abrirModalConcurso({{ $f->id }})" title="Ver Raio-X Completo">
                                                <span>📊 Raio-X</span>
                                            </button>
                                            <button class="btn-sm-action" style="background: rgba(168, 85, 247, 0.15); color: #d8b4fe; border-color: rgba(168, 85, 247, 0.35);" onclick="abrirModalExtratorUrl({{ $f->id }}, '{{ $f->sigla }}')" title="Atualizar com Notícia IA">
                                                <span>⚡ IA</span>
                                            </button>
                                            <button class="btn-sm-action" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border-color: rgba(234, 179, 8, 0.3);" onclick="abrirModalEdicao({{ $f->id }})" title="Editar Manualmente">
                                                <span>✏️</span>
                                            </button>
                                            <button class="btn-sm-action btn-sm-tg" onclick="enviarConcursoTelegram({{ $f->id }})" title="Enviar para Telegram">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Raio-X de Remuneração Aprofundada -->
    <div id="modalConcurso" class="modal-backdrop">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="modalSigla" style="font-size: 1.35rem; font-weight: 800; color: #fff; margin: 0;"></h3>
                    <p id="modalOrgao" style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"></p>
                </div>
                <button onclick="fecharModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Salary Breakdown Grid -->
                <div class="salary-box-breakdown">
                    <div class="salary-box-item" style="border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.05);">
                        <div style="font-size: 0.78rem; color: #6ee7b7; font-weight: 600; text-transform: uppercase;">Remuneração Inicial Bruta</div>
                        <div id="modalSalarioInicial" style="font-size: 1.6rem; font-weight: 800; font-family: 'Outfit'; color: #34d399;"></div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem;">Vencimento + Produtividade</div>
                    </div>

                    <div class="salary-box-item">
                        <div style="font-size: 0.78rem; color: #a5b4fc; font-weight: 600; text-transform: uppercase;">Vencimento Básico</div>
                        <div id="modalVencimentoBase" style="font-size: 1.4rem; font-weight: 700; font-family: 'Outfit'; color: #fff;"></div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem;">Parcela Fixa Inicial</div>
                    </div>

                    <div class="salary-box-item">
                        <div style="font-size: 0.78rem; color: #fcd34d; font-weight: 600; text-transform: uppercase;">Produtividade / Quotas</div>
                        <div id="modalProdutividade" style="font-size: 1.4rem; font-weight: 700; font-family: 'Outfit'; color: #fbbf24;"></div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem;">Parcela Variável Estimada</div>
                    </div>

                    <div class="salary-box-item">
                        <div style="font-size: 0.78rem; color: #38bdf8; font-weight: 600; text-transform: uppercase;">Média Transparência / Teto</div>
                        <div id="modalRealTransp" style="font-size: 1.4rem; font-weight: 700; font-family: 'Outfit'; color: #38bdf8;"></div>
                        <div id="modalTeto" style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem;"></div>
                    </div>
                </div>

                <!-- Detalhes de Vigência / Histórico -->
                <div id="modalVigenciaSection" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: #fff; margin: 0;">📅 Validade & Vigência do Concurso:</h4>
                        <span id="modalStatusVigencia" class="vigencia-pill"></span>
                    </div>
                    <p id="modalVigenciaDetalhes" style="font-size: 0.85rem; color: #cbd5e1; line-height: 1.45; margin: 0;"></p>
                </div>

                <!-- Detalhes de Produtividade & Benefícios -->
                <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>💡</span> Como Funciona a Produtividade & Gratificações:
                    </h4>
                    <p id="modalProdutividadeDetalhes" style="font-size: 0.88rem; color: #cbd5e1; line-height: 1.5; margin: 0;"></p>

                    <div id="modalBeneficiosContainer" style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06);">
                        <strong style="font-size: 0.85rem; color: #fff;">Auxílios & Benefícios:</strong>
                        <p id="modalBeneficiosDetalhes" style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;"></p>
                    </div>
                </div>

                <!-- Informações Estratégicas do Concurso -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: rgba(0,0,0,0.25); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                        <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Requisito de Escolaridade:</span>
                        <div id="modalRequisito" style="font-size: 0.9rem; font-weight: 600; color: #fff; margin-top: 0.2rem;"></div>
                    </div>
                    <div style="background: rgba(0,0,0,0.25); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                        <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Banca & Vagas Previstas:</span>
                        <div id="modalBancaVagas" style="font-size: 0.9rem; font-weight: 600; color: #fff; margin-top: 0.2rem;"></div>
                    </div>
                </div>

                <!-- Disciplinas Chave -->
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Disciplinas Mais Cobradas:</h4>
                    <div id="modalDisciplinas" style="display: flex; gap: 0.4rem; flex-wrap: wrap;"></div>
                </div>

                <!-- Ações do Modal -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-glass); padding-top: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button id="modalBtnTelegram" class="btn-action btn-telegram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            <span>Enviar ao Telegram</span>
                        </button>
                        <button type="button" class="btn-action" style="background: rgba(168, 85, 247, 0.18); color: #d8b4fe; border: 1px solid rgba(168, 85, 247, 0.35);" onclick="fecharModal(); abrirModalExtratorUrl(currentModalConcursoId)">
                            <span>⚡ Atualizar com Notícia IA</span>
                        </button>
                        <button type="button" class="btn-action" style="background: rgba(234, 179, 8, 0.18); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.35);" onclick="fecharModal(); abrirModalEdicao(currentModalConcursoId)">
                            <span>✏️ Editar Dados</span>
                        </button>
                    </div>
                    <button onclick="fecharModal()" class="btn-action btn-outline">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição Manual de Concurso -->
    <div id="modalEditConcurso" class="modal-backdrop">
        <div class="modal-card" style="max-width: 850px;">
            <div class="modal-header">
                <div>
                    <h3 id="editModalTitle" style="font-size: 1.3rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span>✏️ Editar Dados do Concurso</span>
                        <span id="editManualBadge" class="badge-status" style="display: none; background: rgba(234, 179, 8, 0.2); color: #fde047; font-size: 0.75rem; border: 1px dashed rgba(234, 179, 8, 0.5);">✏️ Editado Manualmente</span>
                    </h3>
                    <p id="editModalSubtitle" style="font-size: 0.82rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                        As informações editadas manualmente são protegidas e <strong>não serão sobrescritas</strong> por sincronizações automáticas ou pelo crawler.
                    </p>
                </div>
                <button onclick="fecharModalEdicao()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formEditConcurso" onsubmit="salvarEdicaoConcurso(event)">
                    <input type="hidden" id="editConcursoId">

                    <!-- Seção 1: Dados Gerais -->
                    <div class="edit-form-section">
                        <div class="edit-section-title">
                            <span>🏛️</span> Informações Gerais do Órgão e Cargo
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="editNomeOrgao">Nome do Órgão / Secretaria *</label>
                                <input type="text" id="editNomeOrgao" name="nome_orgao" class="form-control-dark" required>
                            </div>
                            <div class="form-group">
                                <label for="editCargoPrincipal">Cargo Principal *</label>
                                <input type="text" id="editCargoPrincipal" name="cargo_principal" class="form-control-dark" required>
                            </div>
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label for="editRegiao">Região</label>
                                <select id="editRegiao" name="regiao" class="form-control-dark">
                                    <option value="Nacional">Nacional</option>
                                    <option value="Sudeste">Sudeste</option>
                                    <option value="Sul">Sul</option>
                                    <option value="Nordeste">Nordeste</option>
                                    <option value="Centro-Oeste">Centro-Oeste</option>
                                    <option value="Norte">Norte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editRequisito">Requisito de Escolaridade</label>
                                <input type="text" id="editRequisito" name="requisito_escolaridade" class="form-control-dark" placeholder="Ex: Nível Superior em qualquer área">
                            </div>
                            <div class="form-group">
                                <label for="editJornada">Jornada de Trabalho</label>
                                <input type="text" id="editJornada" name="jornada" class="form-control-dark" placeholder="Ex: 40h semanais">
                            </div>
                        </div>
                    </div>

                    <!-- Seção 2: Status do Novo Concurso & Banca -->
                    <div class="edit-form-section">
                        <div class="edit-section-title">
                            <span>⚡</span> Status do Novo Concurso & Banca Organizadora
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label for="editStatus">Status Atual do Certame *</label>
                                <select id="editStatus" name="status" class="form-control-dark" required>
                                    <option value="edital_publicado">Edital Publicado / Inscrições Abertas</option>
                                    <option value="banca_definida">Banca Definida / Contratada</option>
                                    <option value="comissao_formada">Comissão Formada</option>
                                    <option value="autorizado">Concurso Autorizado</option>
                                    <option value="solicitado">Solicitado / Em Estudo</option>
                                    <option value="previsto">Previsto</option>
                                    <option value="em_andamento">Em Andamento</option>
                                    <option value="concluido">Concluído</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editBanca">Banca Organizadora</label>
                                <input type="text" id="editBanca" name="banca" class="form-control-dark" placeholder="Ex: FGV, Cebraspe, FCC, Vunesp...">
                            </div>
                            <div class="form-group">
                                <label for="editVagasPrevistas">Vagas Previstas</label>
                                <input type="text" id="editVagasPrevistas" name="vagas_previstas" class="form-control-dark" placeholder="Ex: 250 vagas + CR">
                            </div>
                        </div>
                    </div>

                    <!-- Seção 3: Vigência & Validade do Último Concurso -->
                    <div class="edit-form-section">
                        <div class="edit-section-title">
                            <span>📅</span> Vigência e Validade do Último Concurso
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="editVigenciaStatus">Status da Vigência do Último Concurso</label>
                                <select id="editVigenciaStatus" name="ultimo_concurso_status_vigencia" class="form-control-dark">
                                    <option value="vencido">🚨 Vencido (Sem concurso válido ativo)</option>
                                    <option value="vigente">🟢 Vigente (Concurso válido)</option>
                                    <option value="prorrogado">🔄 Prorrogado (Válido em prorrogação)</option>
                                    <option value="edital_aberto">⚡ Edital Aberto / Em Andamento</option>
                                    <option value="sem_concurso_valido">⚠️ Sem Concurso Válido</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editValidadeFim">Data / Prazo de Término da Vigência</label>
                                <input type="text" id="editValidadeFim" name="ultimo_concurso_validade_fim" class="form-control-dark" placeholder="Ex: 15/07/2026 ou Expirado em 2018">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="editVigenciaDetalhes">Detalhes e Fundamento Legal da Vigência</label>
                            <textarea id="editVigenciaDetalhes" name="ultimo_concurso_vigencia_detalhes" class="form-control-dark" placeholder="Ex: Concurso de 2023 homologado em 07/2023, prorrogado até 07/2027 por Resolução SEF..."></textarea>
                        </div>
                    </div>

                    <!-- Seção 4: Estrutura Remuneratória -->
                    <div class="edit-form-section">
                        <div class="edit-section-title">
                            <span>💰</span> Estrutura de Remuneração e Transparência (R$)
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label for="editRemuneracaoInicial">Inicial Bruto Estimado (R$)</label>
                                <input type="number" step="0.01" id="editRemuneracaoInicial" name="remuneracao_inicial_bruta" class="form-control-dark" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="editVencimentoBasico">Vencimento Básico / Fixo (R$)</label>
                                <input type="number" step="0.01" id="editVencimentoBasico" name="vencimento_basico" class="form-control-dark" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="editProdutividade">Produtividade / Quotas (R$)</label>
                                <input type="number" step="0.01" id="editProdutividade" name="produtividade_estimada" class="form-control-dark" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label for="editRealTransp">Média Real Transparência (R$)</label>
                                <input type="number" step="0.01" id="editRealTransp" name="remuneracao_real_transparencia" class="form-control-dark" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="editTeto">Teto Constitucional do Estado (R$)</label>
                                <input type="number" step="0.01" id="editTeto" name="remuneracao_teto" class="form-control-dark" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label for="editBeneficios">Benefícios / Auxílios (R$)</label>
                                <input type="number" step="0.01" id="editBeneficios" name="beneficios_estimados" class="form-control-dark" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="editProdutividadeDetalhes">Como Funciona a Produtividade / Gratificações</label>
                                <textarea id="editProdutividadeDetalhes" name="produtividade_detalhes" class="form-control-dark" placeholder="Ex: Vinculada a quotas fiscais individuais e cumprimento de metas de arrecadação..."></textarea>
                            </div>
                            <div class="form-group">
                                <label for="editBeneficiosDetalhes">Auxílios e Adicionais Operacionais</label>
                                <textarea id="editBeneficiosDetalhes" name="beneficios_detalhes" class="form-control-dark" placeholder="Ex: Auxílio Alimentação R$ 1.500, Adicional de Fronteira..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Seção 5: Histórico do Último Certame & Links -->
                    <div class="edit-form-section">
                        <div class="edit-section-title">
                            <span>📜</span> Histórico do Último Concurso & Links Oficiais
                        </div>
                        <div class="form-grid-3">
                            <div class="form-group">
                                <label for="editUltimoAno">Ano do Último Concurso</label>
                                <input type="number" id="editUltimoAno" name="ultimo_concurso_ano" class="form-control-dark" placeholder="Ex: 2013">
                            </div>
                            <div class="form-group">
                                <label for="editUltimaBanca">Banca do Último Concurso</label>
                                <input type="text" id="editUltimaBanca" name="ultimo_concurso_banca" class="form-control-dark" placeholder="Ex: FCC">
                            </div>
                            <div class="form-group">
                                <label for="editUltimasVagas">Vagas do Último Concurso</label>
                                <input type="text" id="editUltimasVagas" name="ultimo_concurso_vagas" class="form-control-dark" placeholder="Ex: 885 vagas">
                            </div>
                        </div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="editLinkPortal">Link Portal da Transparência / Remunerações</label>
                                <input type="url" id="editLinkPortal" name="link_portal_transparencia" class="form-control-dark" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label for="editUltimoLink">Link do Edital Anterior</label>
                                <input type="url" id="editUltimoLink" name="ultimo_concurso_link" class="form-control-dark" placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="editObservacoes">Observações Estratégicas</label>
                            <textarea id="editObservacoes" name="observacoes_estrategicas" class="form-control-dark" placeholder="Anotações de estudo, leis de carreira, etc."></textarea>
                        </div>
                    </div>

                    <!-- Rodapé de Ações do Modal de Edição -->
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-top: 1px solid var(--border-glass); padding-top: 1.5rem; margin-top: 1rem; flex-wrap: wrap;">
                        <button type="button" id="btnResetMaster" class="btn-action" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);" onclick="resetarConcurso()">
                            <span>🔄 Restaurar Padrão do Sistema</span>
                        </button>
                        <div style="display: flex; gap: 0.75rem;">
                            <button type="button" onclick="fecharModalEdicao()" class="btn-action btn-outline">Cancelar</button>
                            <button type="submit" id="btnSalvarEdicao" class="btn-action btn-primary-gradient">
                                <span>💾 Salvar Alterações Manuais</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Extração Rápida de Notícia com IA -->
    <div id="modalQuickAiExtract" class="modal-backdrop">
        <div class="modal-card" style="max-width: 650px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 0 12px rgba(168, 85, 247, 0.4);">
                        ⚡
                    </div>
                    <div>
                        <h3 id="quickAiModalTitle" style="font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0;">Atualizar Concurso com IA OmniRoute</h3>
                        <p id="quickAiModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
                            Cole a URL de uma notícia ou o texto da matéria para atualizar automaticamente este certame.
                        </p>
                    </div>
                </div>
                <button onclick="fecharModalExtratorUrl()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formQuickAiExtract" onsubmit="analisarNoticiaModal(event)">
                    <input type="hidden" id="quickAiConcursoId">

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="inputQuickAiUrl">🔗 URL da Notícia / Artigo</label>
                        <input type="url" id="inputQuickAiUrl" placeholder="https://www.estrategiaconcursos.com.br/blog/..." class="form-control-dark" style="height: 44px; border-radius: 10px;">
                        <span style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.3rem; display: block;">
                            Suporta Estratégia Concursos, Gran, Direção, Folha Dirigida, Diários Oficiais e portais de notícias.
                        </span>
                    </div>

                    <div style="text-align: center; margin: 0.75rem 0; color: #64748b; font-size: 0.78rem; font-weight: 600; text-transform: uppercase;">
                        — OU COLE O TEXTO DA MATÉRIA ABAIXO —
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="textareaQuickAiText">📝 Texto da Notícia / Publicação</label>
                        <textarea id="textareaQuickAiText" class="form-control-dark" style="min-height: 100px; border-radius: 10px;" placeholder="Ex: O concurso da Sefaz SP teve suas provas aplicadas no início de 2026 e o resultado final divulgado em junho de 2026, com organização da Fundação Carlos Chagas (FCC)..."></textarea>
                    </div>

                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); padding: 0.85rem 1rem; border-radius: 10px; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: #cbd5e1; cursor: pointer; margin: 0;">
                            <input type="checkbox" id="checkQuickNotifyTelegram" style="cursor: pointer;">
                            <span>🔔 Enviar Alerta do Resultado ao Telegram</span>
                        </label>
                        <span class="ai-badge">Motor OmniRoute</span>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--border-glass); padding-top: 1.25rem;">
                        <button type="button" onclick="fecharModalExtratorUrl()" class="btn-action btn-outline">Cancelar</button>
                        <button type="submit" id="btnQuickAiSubmit" class="btn-action" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: #fff; border: none; font-weight: 600; border-radius: 10px; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.35);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            <span>Analisar e Atualizar com IA</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Resultado & Insights da Análise por IA -->
    <div id="modalAiNewsResult" class="modal-backdrop">
        <div class="modal-card" style="max-width: 700px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);">
                        ✨
                    </div>
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin: 0;">Análise Concluída com Sucesso!</h3>
                        <p style="font-size: 0.8rem; color: #34d399; margin: 0.2rem 0 0 0;">
                            Os dados foram interpretados pela IA e o card foi atualizado na hora.
                        </p>
                    </div>
                </div>
                <button onclick="fecharModalAiResult()" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.5rem;">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Destaque do Concurso -->
                <div id="aiResultCardWrapper" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.15) 100%); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 14px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    <div id="aiResultNovoBadgeContainer" style="display: none; margin-bottom: 0.75rem;">
                        <span style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; font-size: 0.75rem; font-weight: 800; padding: 0.3rem 0.75rem; border-radius: 20px; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                            ✨ NOVO CONCURSO FISCAL ADICIONADO AO RADAR
                        </span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span id="aiResultSigla" style="font-size: 1.3rem; font-weight: 800; color: #fff; font-family: 'Outfit', sans-serif;">SEFAZ-SP</span>
                        <span id="aiResultEsfera" class="badge-esfera badge-estadual">Estadual</span>
                    </div>
                    <div id="aiResultOrgao" style="font-size: 0.9rem; color: #cbd5e1; font-weight: 500;">Secretaria da Fazenda de São Paulo</div>
                </div>

                <!-- Grid de Fatos Atualizados -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 1.25rem;">
                    <div style="background: rgba(0,0,0,0.3); padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Status do Certame:</span>
                        <div id="aiResultStatus" style="font-size: 0.92rem; font-weight: 700; color: #facc15; margin-top: 0.2rem;"></div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Banca Organizadora:</span>
                        <div id="aiResultBanca" style="font-size: 0.92rem; font-weight: 700; color: #38bdf8; margin-top: 0.2rem;"></div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Vigência / Validade:</span>
                        <div id="aiResultVigencia" style="font-size: 0.92rem; font-weight: 700; color: #34d399; margin-top: 0.2rem;"></div>
                    </div>
                    <div style="background: rgba(0,0,0,0.3); padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <span style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">Vagas / Salário:</span>
                        <div id="aiResultVagasSalario" style="font-size: 0.92rem; font-weight: 700; color: #a7f3d0; margin-top: 0.2rem;"></div>
                    </div>
                </div>

                <!-- Resumo Jornalístico -->
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 0.35rem;">
                        📰 Resumo da Matéria Criada no Feed:
                    </div>
                    <h4 id="aiResultNewsTitle" style="font-size: 0.95rem; font-weight: 700; color: #fff; margin: 0 0 0.4rem 0;"></h4>
                    <p id="aiResultNewsSummary" style="font-size: 0.84rem; color: #94a3b8; line-height: 1.45; margin: 0;"></p>
                </div>

                <!-- Alterações Aplicadas -->
                <div id="aiResultAlteracoesBox" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1.25rem;">
                    <span style="font-size: 0.75rem; color: #34d399; font-weight: 700; text-transform: uppercase;">Campos Estruturados no Banco:</span>
                    <ul id="aiResultAlteracoesList" style="margin: 0.4rem 0 0 1.2rem; padding: 0; font-size: 0.82rem; color: #cbd5e1;"></ul>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--border-glass); padding-top: 1.25rem;">
                    <button type="button" onclick="fecharModalAiResult(); window.location.reload();" class="btn-action btn-primary-gradient">
                        <span>Concluir & Visualizar no Radar Geral</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notify"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Alternância de Abas
        function switchFiscalTab(tabId, btnElement) {
            document.querySelectorAll('.fiscal-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content-pane').forEach(pane => pane.classList.remove('active'));

            if (btnElement) {
                btnElement.classList.add('active');
            }

            const targetPane = document.getElementById(`pane-${tabId}`);
            if (targetPane) {
                targetPane.classList.add('active');
            }

            // Atualiza a URL sem recarregar
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        }

        // Alternância de Visão em Cards vs Tabela (Aba Estaduais)
        function toggleStateView(viewMode) {
            const cards = document.getElementById('stateCardsContainer');
            const table = document.getElementById('stateTableContainer');
            const btnCards = document.getElementById('viewBtnCards');
            const btnTable = document.getElementById('viewBtnTable');

            if (viewMode === 'table') {
                cards.style.display = 'none';
                table.style.display = 'block';
                btnTable.classList.remove('btn-outline');
                btnTable.classList.add('btn-primary-gradient');
                btnCards.classList.remove('btn-primary-gradient');
                btnCards.classList.add('btn-outline');
            } else {
                cards.style.display = 'grid';
                table.style.display = 'none';
                btnCards.classList.remove('btn-outline');
                btnCards.classList.add('btn-primary-gradient');
                btnTable.classList.remove('btn-primary-gradient');
                btnTable.classList.add('btn-outline');
            }
        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.innerText = message;
            toast.style.borderColor = isError ? '#ef4444' : '#6366f1';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4500);
        }

        // Funções de Extração e Análise com IA (OmniRoute)
        function abrirModalExtratorUrl(concursoId = null, sigla = '') {
            document.getElementById('quickAiConcursoId').value = concursoId || '';
            document.getElementById('inputQuickAiUrl').value = '';
            document.getElementById('textareaQuickAiText').value = '';
            document.getElementById('checkQuickNotifyTelegram').checked = false;

            const title = document.getElementById('quickAiModalTitle');
            const subtitle = document.getElementById('quickAiModalSubtitle');

            if (concursoId && sigla) {
                title.innerText = `⚡ Atualizar ${sigla} via Notícia (IA)`;
                subtitle.innerText = `Cole a URL ou o texto da notícia para atualizar os dados de ${sigla} com inteligência artificial.`;
            } else {
                title.innerText = '⚡ Incluir Notícia ou Novo Concurso Fiscal via IA';
                subtitle.innerText = 'Cole a URL ou texto de qualquer matéria (SEFAZ, RFB ou ISS). A IA identifica o concurso, atualiza o card ou cria um novo concurso no Radar Geral!';
            }

            document.getElementById('modalQuickAiExtract').style.display = 'flex';
        }

        function fecharModalExtratorUrl() {
            document.getElementById('modalQuickAiExtract').style.display = 'none';
        }

        function fecharModalAiResult() {
            document.getElementById('modalAiNewsResult').style.display = 'none';
        }

        async function analisarNoticiaModal(event) {
            event.preventDefault();
            const concursoId = document.getElementById('quickAiConcursoId').value;
            const url = document.getElementById('inputQuickAiUrl').value.trim();
            const rawText = document.getElementById('textareaQuickAiText').value.trim();
            const notifyTelegram = document.getElementById('checkQuickNotifyTelegram').checked;
            const btn = document.getElementById('btnQuickAiSubmit');

            if (!url && !rawText) {
                showToast('Informe a URL ou cole o texto da notícia.', true);
                return;
            }

            await executarAnaliseIa({
                url: url,
                raw_text: rawText,
                notify_telegram: notifyTelegram,
                concurso_id: concursoId ? parseInt(concursoId) : null,
                btnElement: btn,
                onSuccess: () => {
                    fecharModalExtratorUrl();
                }
            });
        }

        async function analisarNoticiaComIa(event, inputUrlId, checkNotifyId) {
            event.preventDefault();
            const inputUrl = document.getElementById(inputUrlId);
            const checkNotify = document.getElementById(checkNotifyId);
            const btn = document.getElementById('btnAiExtractGlobal');

            const url = inputUrl ? inputUrl.value.trim() : '';
            const notifyTelegram = checkNotify ? checkNotify.checked : false;

            if (!url) {
                showToast('Cole a URL de uma notícia para analisar.', true);
                return;
            }

            await executarAnaliseIa({
                url: url,
                notify_telegram: notifyTelegram,
                btnElement: btn,
                onSuccess: () => {
                    if (inputUrl) inputUrl.value = '';
                }
            });
        }

        async function executarAnaliseIa({ url, raw_text, notify_telegram, concurso_id, btnElement, onSuccess }) {
            const originalText = btnElement ? btnElement.innerHTML : '';
            if (btnElement) {
                btnElement.innerHTML = '<span>⚡ Processando com IA...</span>';
                btnElement.disabled = true;
            }

            try {
                const response = await fetch("{{ route('fiscal.ai-extract-url') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        url: url,
                        raw_text: raw_text,
                        notify_telegram: notify_telegram,
                        concurso_id: concurso_id,
                        auto_update: true
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message);
                    if (typeof onSuccess === 'function') onSuccess();
                    exibirModalResultadoIa(data);
                } else {
                    showToast(data.message || 'Erro ao processar matéria com IA', true);
                }
            } catch (err) {
                showToast('Erro de conexão com o servidor / IA', true);
            } finally {
                if (btnElement) {
                    btnElement.innerHTML = originalText;
                    btnElement.disabled = false;
                }
            }
        }

        function exibirModalResultadoIa(data) {
            const c = data.concurso;
            const n = data.noticia;
            const ai = data.ai_analysis || {};

            const badgeNovo = document.getElementById('aiResultNovoBadgeContainer');
            if (badgeNovo) {
                badgeNovo.style.display = data.novo_concurso ? 'block' : 'none';
            }

            document.getElementById('aiResultSigla').innerText = c ? c.sigla : (ai.sigla || 'Fisco Identificado');
            document.getElementById('aiResultEsfera').innerText = c ? c.esfera : (ai.esfera || 'Geral');
            document.getElementById('aiResultOrgao').innerText = c ? c.nome_orgao : (ai.nome_orgao || 'Órgão Fiscal');
            
            document.getElementById('aiResultStatus').innerText = c ? c.status_formatado : (ai.status_descricao || ai.status || 'Atualizado');
            document.getElementById('aiResultBanca').innerText = c ? (c.banca || 'A definir') : (ai.banca || 'A definir');
            document.getElementById('aiResultVigencia').innerText = c ? (c.ultimo_concurso_status_vigencia ? c.ultimo_concurso_status_vigencia.toUpperCase() : 'N/D') + (c.ultimo_concurso_validade_fim ? ` (${c.ultimo_concurso_validade_fim})` : '') : (ai.ultimo_concurso_status_vigencia || 'N/D');
            document.getElementById('aiResultVagasSalario').innerText = c ? `${c.vagas_previstas || 'Vagas N/D'} • Inicial: ${c.remuneracao_inicial_formatada}` : `${ai.vagas_previstas || 'Vagas N/D'}`;

            document.getElementById('aiResultNewsTitle').innerText = n ? n.titulo : (ai.titulo_noticia || 'Notícia Fiscal');
            document.getElementById('aiResultNewsSummary').innerText = n ? n.resumo : (ai.resumo_noticia || '');

            const listAlteracoes = document.getElementById('aiResultAlteracoesList');
            listAlteracoes.innerHTML = '';
            const alteracoes = data.campos_atualizados || [];
            if (alteracoes.length > 0) {
                alteracoes.forEach(alt => {
                    const li = document.createElement('li');
                    li.innerText = alt;
                    listAlteracoes.appendChild(li);
                });
                document.getElementById('aiResultAlteracoesBox').style.display = 'block';
            } else {
                document.getElementById('aiResultAlteracoesBox').style.display = 'none';
            }

            document.getElementById('modalAiNewsResult').style.display = 'flex';
        }

        async function executarCrawler() {
            const btn = document.getElementById('btnCrawl') || document.getElementById('btnCrawlHeader');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>⏳ Rastreando Notícias...</span>';
            btn.disabled = true;

            try {
                const response = await fetch("{{ route('fiscal.crawl') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ notify: true })
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(data.message || 'Erro ao rastrear', true);
                }
            } catch (err) {
                showToast('Erro de conexão ao buscar notícias', true);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function testarTelegram() {
            const btn = document.getElementById('btnTestTelegram') || document.getElementById('btnTestTelegramHeader');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>⏳ Enviando...</span>';
            btn.disabled = true;

            try {
                const response = await fetch("{{ route('fiscal.test-telegram') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message || 'Erro ao enviar para o Telegram', true);
                }
            } catch (err) {
                showToast('Erro de conexão com o Telegram', true);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function enviarNoticiaTelegram(id) {
            try {
                const response = await fetch(`/concursos-fiscais/send-news-telegram/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message || 'Falha ao enviar', true);
                }
            } catch (e) {
                showToast('Erro de rede ao disparar notícia', true);
            }
        }

        async function enviarConcursoTelegram(id) {
            try {
                const response = await fetch(`/concursos-fiscais/send-concurso-telegram/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                } else {
                    showToast(data.message || 'Falha ao enviar', true);
                }
            } catch (e) {
                showToast('Erro de rede ao disparar dados do concurso', true);
            }
        }

        let currentModalConcursoId = null;
        let currentEditConcursoId = null;

        async function abrirModalConcurso(id) {
            currentModalConcursoId = id;
            try {
                const response = await fetch(`/concursos-fiscais/concurso/${id}`);
                const data = await response.json();
                if (!data.success || !data.concurso) return;

                const c = data.concurso;
                document.getElementById('modalSigla').innerText = c.sigla + (c.uf ? ' (' + c.uf + ')' : '');
                document.getElementById('modalOrgao').innerText = c.nome_orgao + ' • Cargo: ' + c.cargo_principal;
                document.getElementById('modalSalarioInicial').innerText = 'R$ ' + Number(c.remuneracao_inicial_bruta).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('modalVencimentoBase').innerText = 'R$ ' + Number(c.vencimento_basico).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('modalProdutividade').innerText = 'R$ ' + Number(c.produtividade_estimada).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('modalRealTransp').innerText = 'R$ ' + Number(c.remuneracao_real_transparencia).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('modalTeto').innerText = 'Teto Constitucional: R$ ' + Number(c.remuneracao_teto).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                
                // Vigência
                const vigSection = document.getElementById('modalVigenciaSection');
                if (c.ultimo_concurso_status_vigencia || c.ultimo_concurso_vigencia_detalhes) {
                    vigSection.style.display = 'block';
                    const pill = document.getElementById('modalStatusVigencia');
                    if (c.ultimo_concurso_status_vigencia === 'vigente' || c.ultimo_concurso_status_vigencia === 'prorrogado') {
                        pill.className = 'vigencia-pill vigencia-vigente';
                        pill.innerText = '🟢 Vigente (' + (c.ultimo_concurso_validade_fim || 'Válido') + ')';
                    } else {
                        pill.className = 'vigencia-pill vigencia-vencido';
                        pill.innerText = '🚨 Vencido (' + (c.ultimo_concurso_validade_fim || 'Expirado') + ')';
                    }
                    document.getElementById('modalVigenciaDetalhes').innerText = c.ultimo_concurso_vigencia_detalhes || 'Sem concurso válido ativo.';
                } else {
                    vigSection.style.display = 'none';
                }

                document.getElementById('modalProdutividadeDetalhes').innerText = c.produtividade_detalhes || 'Gratificações vinculadas a quotas fiscais e arrecadação tributária.';
                document.getElementById('modalBeneficiosDetalhes').innerText = c.beneficios_detalhes || 'Auxílio Alimentação, Saúde e Adicionais Operacionais.';
                
                document.getElementById('modalRequisito').innerText = c.requisito_escolaridade || 'Nível Superior em qualquer área';
                document.getElementById('modalBancaVagas').innerText = (c.banca || 'A definir') + ' | ' + (c.vagas_previstas || 'A definir');

                // Disciplinas
                const discContainer = document.getElementById('modalDisciplinas');
                discContainer.innerHTML = '';
                if (c.disciplinas_chave && Array.isArray(c.disciplinas_chave)) {
                    c.disciplinas_chave.forEach(d => {
                        const tag = document.createElement('span');
                        tag.className = 'badge-status';
                        tag.style.background = 'rgba(99, 102, 241, 0.15)';
                        tag.style.color = '#a5b4fc';
                        tag.innerText = d;
                        discContainer.appendChild(tag);
                    });
                }

                // Configurar botão de envio do modal
                document.getElementById('modalBtnTelegram').onclick = () => enviarConcursoTelegram(id);

                document.getElementById('modalConcurso').style.display = 'flex';
            } catch (e) {
                showToast('Erro ao carregar detalhes do concurso', true);
            }
        }

        function fecharModal() {
            document.getElementById('modalConcurso').style.display = 'none';
        }

        // Modal de Edição Manual
        async function abrirModalEdicao(id) {
            currentEditConcursoId = id;
            try {
                const response = await fetch(`/concursos-fiscais/concurso/${id}`);
                const data = await response.json();
                if (!data.success || !data.concurso) return;

                const c = data.concurso;
                document.getElementById('editConcursoId').value = c.id;
                document.getElementById('editModalTitle').querySelector('span').innerText = `✏️ Editar: ${c.sigla}` + (c.uf ? ` (${c.uf})` : '');
                
                const badgeManual = document.getElementById('editManualBadge');
                badgeManual.style.display = c.editado_manualmente ? 'inline-flex' : 'none';

                // Preencher campos
                document.getElementById('editNomeOrgao').value = c.nome_orgao || '';
                document.getElementById('editCargoPrincipal').value = c.cargo_principal || '';
                document.getElementById('editRegiao').value = c.regiao || 'Nacional';
                document.getElementById('editRequisito').value = c.requisito_escolaridade || '';
                document.getElementById('editJornada').value = c.jornada || '';
                document.getElementById('editStatus').value = c.status || 'previsto';
                document.getElementById('editBanca').value = c.banca || '';
                document.getElementById('editVagasPrevistas').value = c.vagas_previstas || '';
                document.getElementById('editVigenciaStatus').value = c.ultimo_concurso_status_vigencia || 'vencido';
                document.getElementById('editValidadeFim').value = c.ultimo_concurso_validade_fim || '';
                document.getElementById('editVigenciaDetalhes').value = c.ultimo_concurso_vigencia_detalhes || '';
                
                document.getElementById('editRemuneracaoInicial').value = c.remuneracao_inicial_bruta ? parseFloat(c.remuneracao_inicial_bruta) : '';
                document.getElementById('editVencimentoBasico').value = c.vencimento_basico ? parseFloat(c.vencimento_basico) : '';
                document.getElementById('editProdutividade').value = c.produtividade_estimada ? parseFloat(c.produtividade_estimada) : '';
                document.getElementById('editRealTransp').value = c.remuneracao_real_transparencia ? parseFloat(c.remuneracao_real_transparencia) : '';
                document.getElementById('editTeto').value = c.remuneracao_teto ? parseFloat(c.remuneracao_teto) : '';
                document.getElementById('editBeneficios').value = c.beneficios_estimados ? parseFloat(c.beneficios_estimados) : '';
                document.getElementById('editProdutividadeDetalhes').value = c.produtividade_detalhes || '';
                document.getElementById('editBeneficiosDetalhes').value = c.beneficios_detalhes || '';

                document.getElementById('editUltimoAno').value = c.ultimo_concurso_ano || '';
                document.getElementById('editUltimaBanca').value = c.ultimo_concurso_banca || '';
                document.getElementById('editUltimasVagas').value = c.ultimo_concurso_vagas || '';
                document.getElementById('editLinkPortal').value = c.link_portal_transparencia || '';
                document.getElementById('editUltimoLink').value = c.ultimo_concurso_link || '';
                document.getElementById('editObservacoes').value = c.observacoes_estrategicas || '';

                document.getElementById('modalEditConcurso').style.display = 'flex';
            } catch (e) {
                showToast('Erro ao abrir formulário de edição', true);
            }
        }

        function fecharModalEdicao() {
            document.getElementById('modalEditConcurso').style.display = 'none';
        }

        async function salvarEdicaoConcurso(event) {
            event.preventDefault();
            const id = document.getElementById('editConcursoId').value;
            const btn = document.getElementById('btnSalvarEdicao');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>⏳ Salvando...</span>';
            btn.disabled = true;

            const payload = {
                nome_orgao: document.getElementById('editNomeOrgao').value,
                cargo_principal: document.getElementById('editCargoPrincipal').value,
                regiao: document.getElementById('editRegiao').value,
                requisito_escolaridade: document.getElementById('editRequisito').value,
                jornada: document.getElementById('editJornada').value,
                status: document.getElementById('editStatus').value,
                banca: document.getElementById('editBanca').value,
                vagas_previstas: document.getElementById('editVagasPrevistas').value,
                ultimo_concurso_status_vigencia: document.getElementById('editVigenciaStatus').value,
                ultimo_concurso_validade_fim: document.getElementById('editValidadeFim').value,
                ultimo_concurso_vigencia_detalhes: document.getElementById('editVigenciaDetalhes').value,
                remuneracao_inicial_bruta: document.getElementById('editRemuneracaoInicial').value || null,
                vencimento_basico: document.getElementById('editVencimentoBasico').value || null,
                produtividade_estimada: document.getElementById('editProdutividade').value || null,
                remuneracao_real_transparencia: document.getElementById('editRealTransp').value || null,
                remuneracao_teto: document.getElementById('editTeto').value || null,
                beneficios_estimados: document.getElementById('editBeneficios').value || null,
                produtividade_detalhes: document.getElementById('editProdutividadeDetalhes').value,
                beneficios_detalhes: document.getElementById('editBeneficiosDetalhes').value,
                ultimo_concurso_ano: document.getElementById('editUltimoAno').value || null,
                ultimo_concurso_banca: document.getElementById('editUltimaBanca').value,
                ultimo_concurso_vagas: document.getElementById('editUltimasVagas').value,
                link_portal_transparencia: document.getElementById('editLinkPortal').value,
                ultimo_concurso_link: document.getElementById('editUltimoLink').value,
                observacoes_estrategicas: document.getElementById('editObservacoes').value,
            };

            try {
                const response = await fetch(`/concursos-fiscais/concurso/${id}`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                    fecharModalEdicao();
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'Erro ao salvar alterações', true);
                }
            } catch (err) {
                showToast('Erro de conexão ao salvar edição', true);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function resetarConcurso() {
            const id = document.getElementById('editConcursoId').value;
            if (!confirm('Deseja realmente restaurar os dados deste concurso para o padrão do catálogo mestre? Quaisquer edições manuais feitas serão substituídas pelas informações originais.')) {
                return;
            }

            const btn = document.getElementById('btnResetMaster');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>⏳ Restaurando...</span>';
            btn.disabled = true;

            try {
                const response = await fetch(`/concursos-fiscais/concurso/${id}/reset`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                    fecharModalEdicao();
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'Erro ao restaurar concurso', true);
                }
            } catch (e) {
                showToast('Erro de conexão ao resetar concurso', true);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // Fechar modais ao clicar fora
        document.getElementById('modalConcurso').addEventListener('click', (e) => {
            if (e.target.id === 'modalConcurso') {
                fecharModal();
            }
        });

        document.getElementById('modalEditConcurso').addEventListener('click', (e) => {
            if (e.target.id === 'modalEditConcurso') {
                fecharModalEdicao();
            }
        });

        document.getElementById('modalQuickAiExtract').addEventListener('click', (e) => {
            if (e.target.id === 'modalQuickAiExtract') {
                fecharModalExtratorUrl();
            }
        });

        document.getElementById('modalAiNewsResult').addEventListener('click', (e) => {
            if (e.target.id === 'modalAiNewsResult') {
                fecharModalAiResult();
            }
        });
    </script>
</body>
</html>
