<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Treinos & Exercícios Físicos | Recomposição Corporal</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --primary: #f97316;
            --primary-hover: #ea580c;
            --musculacao: #6366f1;
            --corrida: #10b981;
            --alongamento: #06b6d4;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        /* Top Nav Tabs */
        .module-nav-tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.6rem 1.2rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-muted);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            color: var(--text-dark);
            background: #f3f4f6;
        }

        .tab-btn.active {
            color: var(--primary);
            background: #fff7ed;
            border-bottom: 2px solid var(--primary);
        }

        /* KPI Cards */
        .fitness-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .fitness-kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .fitness-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.08);
        }

        .kpi-icon-box {
            width: 52px;
            height: 52px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #fff;
        }

        .kpi-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
            margin-top: 0.2rem;
        }

        /* Daily Tracker Quick-Box (< 60s) */
        .daily-tracker-card {
            background: linear-gradient(135deg, #ffffff 0%, #fffbf7 100%);
            border: 1.5px solid #fed7aa;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.06);
            position: relative;
        }

        .daily-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px dashed #fdba74;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .daily-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #9a3412;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auto-save-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            background: #dcfce7;
            color: #166534;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: opacity 0.3s ease;
        }

        .daily-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .habit-checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .habit-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #ffffff;
            border: 1px solid #fed7aa;
            padding: 0.6rem 0.85rem;
            border-radius: 0.6rem;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
        }

        .habit-item:hover {
            background: #fff7ed;
            border-color: var(--primary);
            transform: translateX(2px);
        }

        .habit-item input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .habit-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            user-select: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .key-badge {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 0.1rem 0.35rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
        }

        .habit-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }

        /* Water Section */
        .water-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .water-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .water-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .water-progress-bar {
            height: 12px;
            background: #dbeafe;
            border-radius: 9999px;
            overflow: hidden;
            margin-bottom: 0.75rem;
            position: relative;
        }

        .water-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);
            border-radius: 9999px;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .water-btn-group {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .water-btn {
            background: #ffffff;
            border: 1px solid #93c5fd;
            color: #1d4ed8;
            padding: 0.35rem 0.65rem;
            border-radius: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .water-btn:hover {
            background: #3b82f6;
            color: #ffffff;
            transform: scale(1.04);
        }

        /* Mannequin & Heatmap */
        .mannequin-container {
            display: grid;
            grid-template-columns: minmax(320px, 440px) 1fr;
            gap: 2rem;
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-bottom: 2rem;
        }

        @media (max-width: 960px) {
            .mannequin-container {
                grid-template-columns: 1fr;
            }
        }

        .svg-mannequin-wrapper {
            position: relative;
            background: radial-gradient(circle at center, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 1rem;
            border: 1px solid #cbd5e1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 520px;
        }

        .heat-zone {
            transition: fill 0.4s ease, filter 0.4s ease, opacity 0.3s ease;
            cursor: pointer;
        }

        .heat-zone:hover {
            filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.6));
            opacity: 0.95;
        }

        .body-pin {
            cursor: pointer;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .body-pin:hover {
            transform: scale(1.3);
            filter: drop-shadow(0 0 8px rgba(249, 115, 22, 0.9));
        }

        .body-pin.active {
            transform: scale(1.35);
            filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.95));
        }

        .pin-pulse {
            animation: pulse-ring 2s infinite cubic-bezier(0.215, 0.61, 0.355, 1);
            transform-origin: center;
        }

        @keyframes pulse-ring {
            0% { r: 10; opacity: 0.8; }
            50% { r: 18; opacity: 0.2; }
            100% { r: 24; opacity: 0; }
        }

        /* Time-Lapse Slider */
        .time-lapse-card {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 0.85rem 1.1rem;
            margin-top: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }

        .timeline-slider-input {
            width: 100%;
            accent-color: var(--primary);
            cursor: pointer;
            margin: 0.5rem 0;
        }

        .mannequin-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .point-detail-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 0.85rem;
            padding: 1.25rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .point-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .point-metric-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .mini-stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.6rem;
            padding: 0.6rem 0.75rem;
            text-align: center;
        }

        .mini-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        .mini-stat-val {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-top: 0.2rem;
        }

        .delta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .delta-positive {
            background: #dcfce7;
            color: #15803d;
        }

        .delta-negative {
            background: #fee2e2;
            color: #b91c1c;
        }

        .delta-neutral {
            background: #f1f5f9;
            color: #475569;
        }

        /* RCQ & Radar Card Grid */
        .analytics-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 680px) {
            .analytics-row {
                grid-template-columns: 1fr;
            }
        }

        .rcq-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0.85rem;
            padding: 1.25rem;
        }

        .radar-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Sparkline mini-graph */
        .sparkline-svg {
            width: 100%;
            height: 48px;
            overflow: visible;
        }

        /* Photos & Split / Ghost Comparator */
        .photos-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .comparator-box {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .mode-toggle-group {
            display: flex;
            gap: 0.4rem;
            background: #f1f5f9;
            padding: 0.25rem;
            border-radius: 0.5rem;
        }

        .mode-btn {
            background: transparent;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.35rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .mode-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* Slider Containers */
        .slider-comparison-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            height: 480px;
            margin: 1.5rem auto;
            overflow: hidden;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            background: #0f172a;
            user-select: none;
        }

        .slider-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .slider-img-before {
            clip-path: polygon(0 0, var(--split-pos, 50%) 0, var(--split-pos, 50%) 100%, 0 100%);
        }

        .slider-divider {
            position: absolute;
            top: 0;
            bottom: 0;
            left: var(--split-pos, 50%);
            width: 3px;
            background: #ffffff;
            cursor: ew-resize;
            box-shadow: 0 0 8px rgba(0,0,0,0.5);
            z-index: 10;
        }

        .slider-handle {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 38px;
            height: 38px;
            background: #ffffff;
            color: #0f172a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            font-weight: 800;
        }

        /* Ghost Fade Container */
        .ghost-fade-container {
            position: relative;
            width: 100%;
            max-width: 600px;
            height: 480px;
            margin: 1.5rem auto;
            overflow: hidden;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            background: #0f172a;
            display: none;
        }

        .ghost-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .ghost-img-overlay {
            opacity: var(--ghost-opacity, 0.5);
            transition: opacity 0.1s ease;
        }

        /* Side-by-Side Synchronized Zoom */
        .side-by-side-container {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            max-width: 720px;
            margin: 1.5rem auto;
        }

        .zoom-frame {
            position: relative;
            height: 420px;
            background: #0f172a;
            border-radius: 0.75rem;
            overflow: hidden;
            cursor: crosshair;
        }

        .zoom-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transform-origin: var(--zoom-x, 50%) var(--zoom-y, 50%);
            transition: transform 0.1s ease-out;
        }

        .zoom-frame:hover .zoom-img {
            transform: scale(2);
        }

        .photo-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .photo-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .photo-card:hover {
            transform: translateY(-2px);
        }

        .photo-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .photo-info {
            padding: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: #ffffff;
            border-radius: 1rem;
            max-width: 580px;
            width: 100%;
            padding: 1.75rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.65rem 1.25rem;
            border-radius: 0.6rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.1s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: scale(1.02);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            padding: 0.65rem 1.25rem;
            border-radius: 0.6rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-outline:hover {
            background: #f3f4f6;
        }

        /* Toast Feedback */
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: #ffffff;
            padding: 0.85rem 1.5rem;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
            z-index: 9999;
            display: none;
            align-items: center;
            gap: 0.65rem;
            transition: all 0.3s ease;
        }

        .toast-notification.show {
            display: flex;
            animation: slideUp 0.3s ease forwards;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Confetti Canvas */
        #confetti-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 99999;
        }

        /* Existing Workout Sections */
        .fitness-sections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .fitness-box {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .fitness-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .fitness-box-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-modality {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            text-transform: uppercase;
        }

        .badge-musculacao {
            background: rgba(99, 102, 241, 0.12);
            color: #4f46e5;
        }

        .badge-corrida {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .badge-alongamento {
            background: rgba(6, 182, 212, 0.12);
            color: #0891b2;
        }

        .session-item {
            padding: 1rem;
            border-radius: 0.75rem;
            background: #f9fafb;
            border: 1px solid #f3f4f6;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.15s ease;
        }

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-muted);
        }

        .gear-progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 0.35rem;
        }

        .gear-progress-fill {
            height: 100%;
            border-radius: 9999px;
            background: #10b981;
        }
    </style>
</head>
<body>
    <canvas id="confetti-canvas"></canvas>

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

                    <a href="{{ route('financas.mercado.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span>Supermercado & NFs</span>
                    </a>

                    <a href="{{ route('fiscal.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                        <span>Concursos Fiscais</span>
                    </a>

                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>

                    <a href="{{ route('salary.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Projetor Salarial</span>
                    </a>

                    <a href="{{ route('treinos.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12"></path>
                        </svg>
                        <span>Treinos & Recomposição</span>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button type="button" class="btn-toggle-sidebar js-toggle-sidebar" title="Alternar barra lateral (Ctrl + \)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div>
                        <h1>Treinos & Exercícios Físicos</h1>
                        <p>Treinos & Recomposição Corporal · Rotina Diária, Manequim Anatômico e Fotos</p>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div style="background: #fef3c7; border: 1px solid #fde68a; padding: 0.45rem 0.85rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 800; color: #92400e; display: flex; align-items: center; gap: 0.35rem;">
                        🔥 {{ $consistencyStreak ?? 0 }} {{ ($consistencyStreak ?? 0) === 1 ? 'dia' : 'dias' }} de Foco
                    </div>
                    <button type="button" class="btn-primary" onclick="openMeasurementModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="16"></line>
                            <line x1="8" y1="12" x2="16" y2="12"></line>
                        </svg>
                        Check-in de Medidas
                    </button>
                    <button type="button" class="btn-outline" onclick="openPhotoModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        Enviar Foto
                    </button>
                </div>
            </header>

            <div class="content-body">
                <!-- TOP KPI CARDS -->
                <div class="fitness-kpi-grid">
                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Sessões nesta Semana</div>
                            <div class="kpi-value">{{ $sessoesEstaSemana }} <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">treinos</span></div>
                        </div>
                    </div>

                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Corrida na Semana</div>
                            <div class="kpi-value">{{ number_format($kmCorridaSemana, 1, ',', '.') }} <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">km</span></div>
                        </div>
                    </div>

                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Último Peso / Medição</div>
                            <div class="kpi-value" id="kpi-weight-val">
                                @if($latestMeasurement)
                                    {{ number_format($latestMeasurement->weight_kg, 1, ',', '.') }} kg
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #10b981;">(RCQ {{ $latestMeasurement->rcq ?? '--' }})</span>
                                @elseif($latestMetric)
                                    {{ number_format($latestMetric->peso_kg, 1, ',', '.') }} kg
                                    <span style="font-size: 0.8rem; font-weight: 600; color: #10b981;">(IMC {{ $latestMetric->imc ?? '--' }})</span>
                                @else
                                    <span style="font-size: 0.95rem; color: var(--text-muted);">Aguardando 1º check-in</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Adesão de Hábitos Hoje</div>
                            <div class="kpi-value" id="kpi-adherence-val">
                                {{ $todayDailyLog ? $todayDailyLog->adherence_score : 0 }}%
                                <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">cumprido</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAST DAILY LOG BAR (< 60s) WITH AUTO-SAVE & SHORTCUTS -->
                <div class="daily-tracker-card">
                    <form id="daily-log-form" onchange="triggerAutoSave()">
                        @csrf
                        <div class="daily-header">
                            <div class="daily-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Registro Diário de Hábitos & Dieta (< 60s)
                                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-left: 0.5rem;">Hoje, {{ now()->format('d/m/Y') }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span class="auto-save-pill" id="auto-save-status">✓ Sincronizado</span>
                                <span style="font-size: 0.75rem; color: #94a3b8;" title="Teclas 1 a 5 alternam hábitos; + e - controlam a água">Atalhos: [1-5], [+/-]</span>
                            </div>
                        </div>

                        <div class="daily-grid">
                            <!-- Hábitos Alimentares -->
                            <div class="habit-checkbox-group">
                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Alimentação Limpa</span>

                                <label class="habit-item">
                                    <input type="checkbox" name="breakfast_clean" id="hk-breakfast" value="1" {{ ($todayDailyLog && $todayDailyLog->breakfast_clean) ? 'checked' : '' }}>
                                    <div>
                                        <div class="habit-label"><span class="key-badge">1</span> Café Matinal Sem Açúcar/Mel</div>
                                        <div class="habit-desc">Equilibrado, sem pico glicêmico matinal</div>
                                    </div>
                                </label>

                                <label class="habit-item">
                                    <input type="checkbox" name="lunch_clean" id="hk-lunch" value="1" {{ (!$todayDailyLog || $todayDailyLog->lunch_clean) ? 'checked' : '' }}>
                                    <div>
                                        <div class="habit-label"><span class="key-badge">2</span> Almoço Equilibrado</div>
                                        <div class="habit-desc">Proteína + salada + carboidrato moderado</div>
                                    </div>
                                </label>

                                <label class="habit-item">
                                    <input type="checkbox" name="snack_done" id="hk-snack" value="1" {{ ($todayDailyLog && $todayDailyLog->snack_done) ? 'checked' : '' }}>
                                    <div>
                                        <div class="habit-label"><span class="key-badge">3</span> Lanche da Tarde Proteico</div>
                                        <div class="habit-desc">Evita hipoglicemia e compulsão noturna</div>
                                    </div>
                                </label>

                                <label class="habit-item">
                                    <input type="checkbox" name="dinner_clean" id="hk-dinner" value="1" {{ (!$todayDailyLog || $todayDailyLog->dinner_clean) ? 'checked' : '' }}>
                                    <div>
                                        <div class="habit-label"><span class="key-badge">4</span> Jantar Leve Reparador</div>
                                        <div class="habit-desc">Fácil digestão e proteína para regeneração</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Treino & Hidratação -->
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <!-- Treino Realizado -->
                                <div>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Exercício do Dia</span>
                                    <div style="background: #ffffff; border: 1px solid #fed7aa; padding: 0.85rem; border-radius: 0.6rem; margin-top: 0.4rem;">
                                        <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; margin-bottom: 0.65rem;">
                                            <input type="checkbox" name="workout_done" id="hk-workout" value="1" style="width: 1.25rem; height: 1.25rem; accent-color: var(--primary);" {{ ($todayDailyLog && $todayDailyLog->workout_done) ? 'checked' : '' }}>
                                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-dark);"><span class="key-badge">5</span> Treino Realizado Hoje</span>
                                        </label>

                                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem;">
                                            <select name="workout_type" class="form-control" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 0.4rem; font-size: 0.85rem;">
                                                <option value="STRENGTH_CIRCUIT" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'STRENGTH_CIRCUIT') ? 'selected' : '' }}>Circuito com Halteres (Terça)</option>
                                                <option value="STREET_RUN" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'STREET_RUN') ? 'selected' : '' }}>Corrida de Rua (Quinta / Domingo)</option>
                                                <option value="CHEST_UPPER" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'CHEST_UPPER') ? 'selected' : '' }}>Peitoral & Membros Superiores (Sábado)</option>
                                                <option value="LEGS_RUN" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'LEGS_RUN') ? 'selected' : '' }}>Pernas & Corrida</option>
                                                <option value="EXTRA_CARDIO" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'EXTRA_CARDIO') ? 'selected' : '' }}>Cardio Extra / Regenerativo</option>
                                                <option value="REST" {{ ($todayDailyLog && $todayDailyLog->workout_type == 'REST') ? 'selected' : '' }}>Descanso Ativo</option>
                                            </select>

                                            <div style="display: flex; align-items: center; gap: 0.3rem;">
                                                <input type="number" name="workout_duration_min" placeholder="30" value="{{ $todayDailyLog ? $todayDailyLog->workout_duration_min : 30 }}" style="width: 100%; padding: 0.4rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.4rem; font-size: 0.85rem;">
                                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">min</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidratação (3000ml) -->
                                <div class="water-card">
                                    <div class="water-header">
                                        <div class="water-title">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                                            </svg>
                                            Meta de Água (3.000 ml)
                                        </div>
                                        <div style="font-size: 0.85rem; font-weight: 800; color: #1e40af;" id="water-display-val">
                                            {{ $todayDailyLog ? $todayDailyLog->water_volume_ml : 0 }} / 3000 ml
                                        </div>
                                    </div>

                                    <div class="water-progress-bar">
                                        <div class="water-progress-fill" id="water-progress-fill" style="width: {{ $todayDailyLog ? $todayDailyLog->water_progress_percent : 0 }}%;"></div>
                                    </div>

                                    <input type="hidden" name="water_volume_ml" id="water-volume-input" value="{{ $todayDailyLog ? $todayDailyLog->water_volume_ml : 0 }}">

                                    <div class="water-btn-group">
                                        <button type="button" class="water-btn" onclick="addWater(250)">+250 ml</button>
                                        <button type="button" class="water-btn" onclick="addWater(500)">+500 ml</button>
                                        <button type="button" class="water-btn" onclick="addWater(1000)">+1 L</button>
                                        <button type="button" class="water-btn" style="color: #ef4444; border-color: #fca5a5;" onclick="resetWater()">Zerar</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Notas e Sensação -->
                            <div>
                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Sensação & Energia</span>
                                <textarea name="notes" placeholder="Como foi sua energia, disposição ou sono hoje? Sentiu fome ou cansaço?" style="width: 100%; height: 110px; margin-top: 0.4rem; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 0.6rem; font-size: 0.85rem; resize: none; font-family: inherit;">{{ $todayDailyLog ? $todayDailyLog->notes : '' }}</textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABS NAVIGATION -->
                <div class="module-nav-tabs">
                    <button type="button" class="tab-btn active" onclick="switchTab('mannequin-tab', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="5" r="3"></circle>
                            <line x1="12" y1="8" x2="12" y2="14"></line>
                            <line x1="12" y1="14" x2="9" y2="21"></line>
                            <line x1="12" y1="14" x2="15" y2="21"></line>
                            <line x1="7" y1="10" x2="17" y2="10"></line>
                        </svg>
                        Manequim & Antropometria
                    </button>

                    <button type="button" class="tab-btn" onclick="switchTab('photos-tab', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        Fotos de Evolução & Comparador
                    </button>

                    <button type="button" class="tab-btn" onclick="switchTab('workout-tab', this)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12"></path>
                        </svg>
                        Fichas, Equipamentos & PRs
                    </button>
                </div>

                <!-- TAB 1: MANNEQUIN & ANTHROPOMETRY -->
                <div id="mannequin-tab" class="tab-content">
                    <div class="mannequin-container">
                        <!-- Left: SVG Mannequin with Heatmap & Time-Lapse -->
                        <div class="svg-mannequin-wrapper">
                            <div style="display: flex; justify-content: space-between; width: 100%; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Termografia & Silhueta Anatômica
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 700; color: #10b981; background: #ecfdf5; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                                    Heatmap Ativo
                                </span>
                            </div>

                            <svg id="body-mapper-svg" viewBox="0 0 300 480" width="280" height="420" style="filter: drop-shadow(0 4px 8px rgba(0,0,0,0.08));">
                                <defs>
                                    <!-- Gradiente Queima de Gordura (Ciano / Esmeralda) -->
                                    <linearGradient id="grad-fat-reduction" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.8" />
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.75" />
                                    </linearGradient>

                                    <!-- Gradiente Hipertrofia Muscular (Índigo / Violeta) -->
                                    <linearGradient id="grad-muscle-gain" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#818cf8" stop-opacity="0.8" />
                                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.75" />
                                    </linearGradient>

                                    <!-- Filtro de Brilho Dinâmico -->
                                    <filter id="glow-heat" x="-20%" y="-20%" width="140%" height="140%">
                                        <feGaussianBlur stdDeviation="3" result="blur" />
                                        <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                    </filter>
                                </defs>

                                <!-- Silhueta com Regiões Anatômicas Independentes (Heatmap) -->
                                <g id="mannequin-body-silhouette" stroke="#94a3b8" stroke-width="1.5" stroke-linejoin="round">
                                    <!-- Cabeça & Pescoço -->
                                    <ellipse cx="150" cy="45" rx="22" ry="28" fill="#f1f5f9" />
                                    <path d="M142,72 L142,88 L158,88 L158,72" fill="#e2e8f0" />

                                    <!-- Tórax / Peitoral (Heat Zone) -->
                                    <path id="heat-chest" class="heat-zone" d="M142,88 Q115,92 90,110 L85,155 Q115,168 150,168 Q185,168 215,155 L210,110 Q185,92 158,88 Z" fill="#e2e8f0" onclick="selectMannequinPoint('chest')" />

                                    <!-- Braços (Heat Zone) -->
                                    <!-- Braço Direito -->
                                    <path id="heat-arm-right" class="heat-zone" d="M90,110 L75,185 L65,250 Q64,258 70,260 Q76,258 82,245 L88,185 L98,185 L85,155 Z" fill="#e2e8f0" onclick="selectMannequinPoint('arm_right')" />
                                    <!-- Braço Esquerdo -->
                                    <path id="heat-arm-left" class="heat-zone" d="M210,110 L225,185 L235,250 Q236,258 230,260 Q224,258 218,245 L212,185 L202,185 L215,155 Z" fill="#e2e8f0" />

                                    <!-- Cintura Alta / Estreita (Heat Zone) -->
                                    <path id="heat-waist" class="heat-zone" d="M98,185 Q115,195 125,195 L175,195 Q185,195 202,185 L198,215 Q175,225 150,225 Q125,225 102,215 Z" fill="#e2e8f0" onclick="selectMannequinPoint('waist_narrow')" />

                                    <!-- Abdômen Umbilical (Heat Zone) -->
                                    <path id="heat-abdomen" class="heat-zone" d="M102,215 Q125,225 150,225 Q175,225 198,215 L190,265 Q170,275 150,275 Q130,275 110,265 Z" fill="#e2e8f0" onclick="selectMannequinPoint('abdomen_umbilical')" />

                                    <!-- Quadril (Heat Zone) -->
                                    <path id="heat-hips" class="heat-zone" d="M110,265 Q130,275 150,275 Q170,275 190,265 L188,300 Q170,310 150,310 Q130,310 112,300 Z" fill="#e2e8f0" onclick="selectMannequinPoint('hips')" />

                                    <!-- Coxas (Heat Zone) -->
                                    <!-- Coxa Direita -->
                                    <path id="heat-thigh-right" class="heat-zone" d="M112,300 Q130,310 148,310 L144,390 Q144,440 134,440 Q124,440 120,390 L110,300 Z" fill="#e2e8f0" onclick="selectMannequinPoint('thigh_right')" />
                                    <!-- Coxa Esquerda -->
                                    <path id="heat-thigh-left" class="heat-zone" d="M152,310 Q170,310 188,300 L190,300 L180,390 Q176,440 166,440 Q156,440 156,390 Z" fill="#e2e8f0" />
                                </g>

                                <!-- HOTSPOTS / PINS ANATÔMICOS -->
                                <!-- 1. Tórax (150, 140) -->
                                <g class="body-pin active" data-point="chest" onclick="selectMannequinPoint('chest')">
                                    <circle cx="150" cy="140" r="14" fill="rgba(99, 102, 241, 0.25)" class="pin-pulse" />
                                    <circle cx="150" cy="140" r="8" fill="#6366f1" stroke="#ffffff" stroke-width="2" />
                                </g>

                                <!-- 2. Braço Direito (82, 175) -->
                                <g class="body-pin" data-point="arm_right" onclick="selectMannequinPoint('arm_right')">
                                    <circle cx="82" cy="175" r="14" fill="rgba(99, 102, 241, 0.25)" class="pin-pulse" />
                                    <circle cx="82" cy="175" r="7" fill="#6366f1" stroke="#ffffff" stroke-width="2" />
                                </g>

                                <!-- 3. Cintura Alta (150, 205) -->
                                <g class="body-pin" data-point="waist_narrow" onclick="selectMannequinPoint('waist_narrow')">
                                    <circle cx="150" cy="205" r="14" fill="rgba(249, 115, 22, 0.25)" class="pin-pulse" />
                                    <circle cx="150" cy="205" r="8" fill="#f97316" stroke="#ffffff" stroke-width="2" />
                                </g>

                                <!-- 4. Abdômen Umbilical (150, 245) -->
                                <g class="body-pin" data-point="abdomen_umbilical" onclick="selectMannequinPoint('abdomen_umbilical')">
                                    <circle cx="150" cy="245" r="15" fill="rgba(239, 68, 68, 0.25)" class="pin-pulse" />
                                    <circle cx="150" cy="245" r="9" fill="#ef4444" stroke="#ffffff" stroke-width="2" />
                                </g>

                                <!-- 5. Quadril (150, 288) -->
                                <g class="body-pin" data-point="hips" onclick="selectMannequinPoint('hips')">
                                    <circle cx="150" cy="288" r="14" fill="rgba(16, 185, 129, 0.25)" class="pin-pulse" />
                                    <circle cx="150" cy="288" r="8" fill="#10b981" stroke="#ffffff" stroke-width="2" />
                                </g>

                                <!-- 6. Coxa Direita (130, 365) -->
                                <g class="body-pin" data-point="thigh_right" onclick="selectMannequinPoint('thigh_right')">
                                    <circle cx="130" cy="365" r="14" fill="rgba(99, 102, 241, 0.25)" class="pin-pulse" />
                                    <circle cx="130" cy="365" r="7" fill="#6366f1" stroke="#ffffff" stroke-width="2" />
                                </g>
                            </svg>

                            <!-- TIME-LAPSE SLIDER -->
                            <div class="time-lapse-card">
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; font-weight: 700; color: #475569;">
                                    <span>⏳ Linha do Tempo Animada</span>
                                    <span id="timeline-current-label">Medição Atual</span>
                                </div>
                                <input type="range" class="timeline-slider-input" id="time-lapse-slider" min="0" max="0" value="0" oninput="handleTimelineScrub(this.value)">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #94a3b8;">
                                    <span id="timeline-start-date">Marco Zero</span>
                                    <button type="button" id="btn-play-timelapse" onclick="toggleTimeLapsePlay()" style="background: none; border: none; font-size: 0.8rem; font-weight: 700; color: var(--primary); cursor: pointer;">▶ Play Time-Lapse</button>
                                    <span id="timeline-end-date">Atual</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Point Detail Card, RCQ & Spider Radar Chart -->
                        <div class="mannequin-sidebar">
                            <!-- Detalhes do Ponto Selecionado -->
                            <div class="point-detail-card" id="point-detail-box">
                                <div class="point-title">
                                    <span id="detail-point-name">Tórax / Peitoral</span>
                                    <span class="delta-badge delta-positive" id="detail-point-badge">
                                        Δ 0.0 cm (0.0%)
                                    </span>
                                </div>

                                <div class="point-metric-row">
                                    <div class="mini-stat-box">
                                        <div class="mini-stat-label">Atual</div>
                                        <div class="mini-stat-val" id="detail-current-val">-- cm</div>
                                    </div>
                                    <div class="mini-stat-box">
                                        <div class="mini-stat-label">Marco Zero (Base)</div>
                                        <div class="mini-stat-val" id="detail-baseline-val">-- cm</div>
                                    </div>
                                    <div class="mini-stat-box">
                                        <div class="mini-stat-label">Delta Acumulado</div>
                                        <div class="mini-stat-val" id="detail-delta-abs">-- cm</div>
                                    </div>
                                </div>

                                <div style="margin-top: 0.75rem;">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase;">
                                        Evolução Temporal (Sparkline)
                                    </div>
                                    <div id="sparkline-container" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.5rem;">
                                        <svg class="sparkline-svg" id="sparkline-svg" viewBox="0 0 240 40">
                                            <polyline points="10,20 60,20 120,20 180,20 230,20" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                </div>

                                <div id="detail-recomp-feedback" style="margin-top: 1rem; font-size: 0.85rem; line-height: 1.4; color: #475569; background: #f1f5f9; padding: 0.75rem; border-radius: 0.5rem;">
                                    Clique nos pontos da silhueta para analisar o comportamento da gordura localizada e da densidade muscular.
                                </div>
                            </div>

                            <!-- Row: RCQ & Spider Radar Chart -->
                            <div class="analytics-row">
                                <!-- RCQ Card -->
                                <div class="rcq-card {{ ($trackerSummary['rcq']['status'] ?? '') === 'baixo_risco' ? '' : 'warning' }}" id="rcq-card-box">
                                    <div style="font-weight: 800; font-size: 0.95rem; color: #1e3a8a; margin-bottom: 0.35rem;">
                                        Cintura-Quadril (RCQ)
                                    </div>
                                    <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.35rem;">
                                        <div style="font-size: 1.6rem; font-weight: 800; color: #0f172a;" id="rcq-current-val">
                                            {{ $trackerSummary['rcq']['current'] ?? '--' }}
                                        </div>
                                        <span style="font-size: 0.75rem; font-weight: 700; background: #ffffff; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #cbd5e1;">
                                            Meta: ≤ 0,90
                                        </span>
                                    </div>
                                    <p style="font-size: 0.78rem; color: #475569; line-height: 1.35;" id="rcq-status-val">
                                        {{ $trackerSummary['rcq']['label'] ?? 'Aguardando medições' }}
                                    </p>
                                </div>

                                <!-- Spider / Radar Chart (Teia Anatômica) -->
                                <div class="radar-card">
                                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                        <span style="font-size: 0.85rem; font-weight: 800; color: #1e293b;">Teia de Recomposição</span>
                                        <div style="display: flex; gap: 0.5rem; font-size: 0.7rem; font-weight: 700;">
                                            <span style="color: #f59e0b;">● Base</span>
                                            <span style="color: #10b981;">● Atual</span>
                                        </div>
                                    </div>
                                    <svg id="spider-radar-svg" viewBox="0 0 200 170" width="180" height="150">
                                        <!-- Injected via JS -->
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PHOTOS & ADVANCED COMPARATOR (SPLIT, GHOST FADE, ZOOM) -->
                <div id="photos-tab" class="tab-content" style="display: none;">
                    <div class="photos-layout">
                        <!-- Advanced Comparator Box -->
                        <div class="comparator-box">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                                <div>
                                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-dark);">
                                        Comparador de Evolução Anatômica
                                    </h3>
                                    <p style="font-size: 0.85rem; color: var(--text-muted);">
                                        Compare a retração abdominal, tônus muscular e postura entre o marco zero e os check-ins recentes
                                    </p>
                                </div>

                                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                    <!-- Mode Toggle -->
                                    <div class="mode-toggle-group">
                                        <button type="button" class="mode-btn active" onclick="setComparatorMode('split', this)">Divisor Deslizante</button>
                                        <button type="button" class="mode-btn" onclick="setComparatorMode('ghost', this)">Sobreposição Fantasma</button>
                                        <button type="button" class="mode-btn" onclick="setComparatorMode('zoom', this)">Lupa Lado a Lado</button>
                                    </div>

                                    <!-- Angle Filter -->
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Ângulo:</label>
                                        <select id="comparator-angle-select" onchange="filterComparatorAngle()" style="padding: 0.35rem 0.65rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.85rem;">
                                            <option value="FRONT">Frente</option>
                                            <option value="BACK">Costas</option>
                                            <option value="SIDE_RIGHT">Lateral Direita</option>
                                            <option value="SIDE_LEFT">Lateral Esquerda</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            @if($recentPhotos->count() >= 2)
                                <!-- Mode 1: Split Slider -->
                                <div class="slider-comparison-container" id="slider-box" onmousemove="handleSliderMove(event)" ontouchmove="handleSliderTouch(event)">
                                    <img src="{{ $recentPhotos->first()->url }}" class="slider-img" id="img-after" alt="Foto Recente">
                                    <img src="{{ $recentPhotos->last()->url }}" class="slider-img slider-img-before" id="img-before" alt="Foto Marco Zero">
                                    <div class="slider-divider" id="slider-divider">
                                        <div class="slider-handle">↔</div>
                                    </div>
                                </div>

                                <!-- Mode 2: Ghost Fade Overlay -->
                                <div class="ghost-fade-container" id="ghost-box">
                                    <img src="{{ $recentPhotos->last()->url }}" class="ghost-img" alt="Foto Marco Zero">
                                    <img src="{{ $recentPhotos->first()->url }}" class="ghost-img ghost-img-overlay" id="ghost-overlay-img" alt="Foto Recente">
                                </div>

                                <div id="ghost-controls" style="display: none; max-width: 600px; margin: 0.75rem auto 1.5rem; align-items: center; gap: 1rem;">
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #64748b;">Marco Zero</span>
                                    <input type="range" min="0" max="100" value="50" oninput="handleGhostOpacity(this.value)" style="flex: 1; accent-color: var(--primary);">
                                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary);">Atual (100%)</span>
                                </div>

                                <!-- Mode 3: Side-by-Side Synchronized Zoom -->
                                <div class="side-by-side-container" id="zoom-box" onmousemove="handleSynchronizedZoom(event)">
                                    <div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 0.35rem; text-align: center;">Marco Zero (Antes)</div>
                                        <div class="zoom-frame">
                                            <img src="{{ $recentPhotos->last()->url }}" class="zoom-img" id="zoom-img-left" alt="Antes">
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; text-align: center;">Atual (Depois)</div>
                                        <div class="zoom-frame">
                                            <img src="{{ $recentPhotos->first()->url }}" class="zoom-img" id="zoom-img-right" alt="Depois">
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; max-width: 600px; margin: 0 auto; font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">
                                    <span>◀ Antes ({{ $recentPhotos->last()->date ? $recentPhotos->last()->date->format('d/m/Y') : '' }})</span>
                                    <span>Depois ({{ $recentPhotos->first()->date ? $recentPhotos->first()->date->format('d/m/Y') : '' }}) ▶</span>
                                </div>
                            @else
                                <div class="empty-state" style="background: #f8fafc; border-radius: 0.75rem; border: 1px dashed #cbd5e1;">
                                    <p style="font-weight: 600; margin-bottom: 0.5rem;">Fotos insuficientes para o comparador split-view</p>
                                    <p style="font-size: 0.85rem;">Cadastre pelo menos 2 fotos do mesmo ângulo (ex: Semana 1 e Semana 4) para habilitar a comparação com slider e fade.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Galeria de Fotos -->
                        <div class="fitness-box">
                            <div class="fitness-box-header">
                                <div class="fitness-box-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    Galeria Histórica de Registros
                                </div>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $recentPhotos->count() }} fotos</span>
                            </div>

                            <div class="photo-gallery-grid" id="photo-gallery-container">
                                @forelse($recentPhotos as $photo)
                                    <div class="photo-card" data-angle="{{ $photo->angle }}">
                                        <img src="{{ $photo->url }}" alt="{{ $photo->angle_label }}" class="photo-img" loading="lazy">
                                        <div class="photo-info">
                                            <div>
                                                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-dark);">
                                                    {{ $photo->angle_label }}
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                                    {{ $photo->date ? $photo->date->format('d/m/Y') : '' }}
                                                </div>
                                            </div>
                                            <button type="button" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.2rem;" title="Excluir foto" onclick="deletePhoto({{ $photo->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state" style="grid-column: 1 / -1;">
                                        Nenhuma foto de evolução cadastrada ainda. Clique no botão "Enviar Foto" no topo para começar.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: WORKOUT PLANS, PRs & GEARS -->
                <div id="workout-tab" class="tab-content" style="display: none;">
                    <div class="fitness-sections-grid">
                        <!-- Fichas & Musculação -->
                        <div class="fitness-box">
                            <div class="fitness-box-header">
                                <div class="fitness-box-title">
                                    <span class="badge-modality badge-musculacao">Musculação</span>
                                    Fichas de Treino
                                </div>
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <button type="button" class="btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="generateDefaultABC()" title="Gera automaticamente 3 fichas prontas: Treino A (Push), Treino B (Pull) e Treino C (Legs)">⚡ Fichas Prontas ABC</button>
                                    <button type="button" class="btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="openPlanModal()">+ Nova Ficha</button>
                                </div>
                            </div>

                            @forelse($workoutPlans as $plan)
                                <div class="session-item">
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-dark);">
                                            @if($plan->identificador_letra)
                                                <span style="display: inline-block; padding: 0.1rem 0.45rem; background: #e0e7ff; color: #4338ca; border-radius: 4px; font-size: 0.75rem; margin-right: 0.35rem;">{{ $plan->identificador_letra }}</span>
                                            @endif
                                            {{ $plan->nome }}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                                            {{ $plan->items_count }} exercícios programados
                                            @if($plan->frequencia_semanal_sugerida)
                                                · {{ $plan->frequencia_semanal_sugerida }}x/semana
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" style="background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 0.25rem 0.4rem;" title="Excluir ficha" onclick="deletePlan({{ $plan->id }})">&times;</button>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <p style="margin-bottom: 0.75rem;">Nenhuma ficha cadastrada ainda.</p>
                                    <button type="button" class="btn-primary" style="font-size: 0.8rem;" onclick="generateDefaultABC()">⚡ Gerar Fichas Prontas ABC Agora</button>
                                </div>
                            @endforelse
                        </div>

                        <!-- Corrida & Equipamentos -->
                        <div class="fitness-box">
                            <div class="fitness-box-header">
                                <div class="fitness-box-title">
                                    <span class="badge-modality badge-corrida">Corrida</span>
                                    Tênis & Equipamentos
                                </div>
                                <button type="button" class="btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="openGearModal()">+ Novo Tênis</button>
                            </div>

                            @forelse($activeGears as $gear)
                                <div style="padding: 0.85rem; border: 1px solid #f3f4f6; border-radius: 0.75rem; margin-bottom: 0.75rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 700; color: var(--text-dark);">{{ $gear->marca }} {{ $gear->modelo }}</span>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 0.8rem; font-weight: 700; color: {{ $gear->alerta_desgaste === 'alerta' ? '#f59e0b' : ($gear->alerta_desgaste === 'esgotado' ? '#ef4444' : '#10b981') }};">
                                                {{ number_format($gear->quilometragem_total, 1, ',', '.') }} km
                                                @if($gear->vida_util_limite_km)
                                                    / {{ number_format($gear->vida_util_limite_km, 0) }} km
                                                @endif
                                            </span>
                                            <button type="button" style="background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 0.1rem;" title="Excluir equipamento" onclick="deleteGear({{ $gear->id }})">&times;</button>
                                        </div>
                                    </div>
                                    @if($gear->percentual_uso)
                                        <div class="gear-progress-bar">
                                            <div class="gear-progress-fill" style="width: {{ min(100, $gear->percentual_uso) }}%; background: {{ $gear->alerta_desgaste === 'esgotado' ? '#ef4444' : ($gear->alerta_desgaste === 'alerta' ? '#f59e0b' : '#10b981') }};"></div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="empty-state">
                                    <p style="margin-bottom: 0.75rem;">Nenhum tênis cadastrado.</p>
                                    <button type="button" class="btn-outline" style="font-size: 0.8rem;" onclick="openGearModal()">+ Cadastrar Tênis</button>
                                </div>
                            @endforelse
                        </div>

                        <!-- Recordes Pessoais (PRs) -->
                        <div class="fitness-box">
                            <div class="fitness-box-header">
                                <div class="fitness-box-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="7"></circle>
                                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                    </svg>
                                    Recordes Pessoais (PRs)
                                </div>
                                <button type="button" class="btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="openPrModal()">+ Novo PR</button>
                            </div>

                            @forelse($personalRecords as $pr)
                                <div class="session-item">
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-dark);">
                                            {{ $pr->exercise ? $pr->exercise->nome : ucfirst(str_replace('_', ' ', $pr->tipo_recorde)) }}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            {{ \Carbon\Carbon::parse($pr->data_recorde)->format('d/m/Y') }}
                                            @if($pr->notas)
                                                · {{ $pr->notas }}
                                            @endif
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="font-weight: 800; font-size: 1.05rem; color: #b45309; background: #fef3c7; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">
                                            {{ $pr->valor_formatado }}
                                        </div>
                                        <button type="button" style="background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 0.1rem;" title="Excluir PR" onclick="deletePr({{ $pr->id }})">&times;</button>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <p style="margin-bottom: 0.75rem;">Nenhum recorde pessoal registrado ainda.</p>
                                    <button type="button" class="btn-outline" style="font-size: 0.8rem;" onclick="openPrModal()">+ Registrar PR</button>
                                </div>
                            @endforelse
                        </div>

                        <!-- Histórico de Sessões de Treino -->
                        <div class="fitness-box" style="grid-column: 1 / -1;">
                            <div class="fitness-box-header">
                                <div class="fitness-box-title">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    Histórico de Sessões de Treino
                                </div>
                                <button type="button" class="btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" onclick="openSessionModal()">+ Registrar Treino</button>
                            </div>

                            @forelse($recentSessions as $sess)
                                <div class="session-item">
                                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                                        <span class="badge-modality {{ $sess->modalidade === 'musculacao' ? 'badge-musculacao' : ($sess->modalidade === 'corrida' ? 'badge-corrida' : 'badge-alongamento') }}">
                                            {{ ucfirst($sess->modalidade) }}
                                        </span>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-dark);">
                                                {{ $sess->nome_sessao }}
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                                                {{ \Carbon\Carbon::parse($sess->data_hora_inicio)->format('d/m/Y H:i') }}
                                                @if($sess->duracao_minutos)
                                                    · {{ $sess->duracao_minutos }} min
                                                @endif
                                                @if($sess->esforco_percebido_rpe)
                                                    · RPE {{ $sess->esforco_percebido_rpe }}/10
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        @if($sess->runningLog)
                                            <div style="text-align: right;">
                                                <div style="font-weight: 700; color: #059669;">{{ number_format($sess->runningLog->distancia_km, 2, ',', '.') }} km</div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">Pace {{ $sess->runningLog->pace_formatado }}</div>
                                            </div>
                                        @elseif($sess->stretchingLog)
                                            <div style="text-align: right;">
                                                <div style="font-weight: 700; color: #0891b2;">Alívio: +{{ $sess->stretchingLog->delta_alivio }} pts</div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">Rigidez {{ $sess->stretchingLog->nivel_rigidez_inicial }} → {{ $sess->stretchingLog->nivel_rigidez_final }}</div>
                                            </div>
                                        @elseif($sess->volume_total_kg > 0)
                                            <div style="text-align: right;">
                                                <div style="font-weight: 700; color: #4f46e5;">{{ number_format($sess->volume_total_kg, 0, ',', '.') }} kg</div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">Volume de treino</div>
                                            </div>
                                        @endif
                                        <button type="button" style="background: none; border: none; color: #ef4444; font-size: 1.1rem; cursor: pointer; padding: 0.2rem;" title="Excluir sessão" onclick="deleteSession({{ $sess->id }})">&times;</button>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">
                                    <p style="margin-bottom: 0.75rem;">Nenhuma sessão de treino realizada ainda.</p>
                                    <button type="button" class="btn-outline" style="font-size: 0.8rem;" onclick="openSessionModal()">+ Registrar Primeiro Treino</button>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL: CHECK-IN ANTROPOMÉTRICO (MEDIDAS) -->
    <div class="modal-overlay" id="measurement-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Novo Check-in de Medidas Corporais
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closeMeasurementModal()">&times;</button>
            </div>

            <form id="measurement-form" onsubmit="submitMeasurement(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Data da Medição</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Peso Corporal (kg)</label>
                        <input type="number" step="0.1" name="weight_kg" placeholder="ex: 86.0" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="font-size: 0.85rem; font-weight: 800; color: var(--primary); margin: 1rem 0 0.5rem; text-transform: uppercase;">
                    Circunferências (cm)
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Tórax (ponto mamilar)</label>
                        <input type="number" step="0.1" name="chest_cm" placeholder="ex: 104.5" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Cintura Alta (ponto mais estreito)</label>
                        <input type="number" step="0.1" name="waist_narrow_cm" placeholder="ex: 94.0" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Abdômen (linha umbilical)</label>
                        <input type="number" step="0.1" name="abdomen_umbilical_cm" placeholder="ex: 99.5" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Quadril (maior projeção glútea)</label>
                        <input type="number" step="0.1" name="hips_cm" placeholder="ex: 102.0" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Braço Direito (cm)</label>
                        <input type="number" step="0.1" name="arm_right_cm" placeholder="ex: 36.5" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.25rem;">Coxa Direita (cm)</label>
                        <input type="number" step="0.1" name="thigh_right_cm" placeholder="ex: 59.0" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Observações / Horário</label>
                    <input type="text" name="notes" placeholder="ex: Domingo pela manhã em jejum" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closeMeasurementModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Check-in</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: UPLOAD DE FOTO -->
    <div class="modal-overlay" id="photo-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Enviar Foto de Evolução
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closePhotoModal()">&times;</button>
            </div>

            <form id="photo-form" onsubmit="submitPhoto(event)">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Data da Foto</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Ângulo da Foto</label>
                    <select name="angle" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                        <option value="FRONT">Frente</option>
                        <option value="BACK">Costas</option>
                        <option value="SIDE_RIGHT">Lateral Direita</option>
                        <option value="SIDE_LEFT">Lateral Esquerda</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Arquivo de Imagem (JPG, PNG)</label>
                    <input type="file" name="photo" accept="image/*" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closePhotoModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Fazer Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: FICHA DE TREINO -->
    <div class="modal-overlay" id="plan-modal">
        <div class="modal-box" style="max-width: 580px;">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Nova Ficha de Treino
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closePlanModal()">&times;</button>
            </div>

            <form id="plan-form" onsubmit="submitPlan(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Nome da Ficha</label>
                        <input type="text" name="nome" placeholder="ex: Treino A - Peitoral e Tríceps" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Letra (opcional)</label>
                        <input type="text" name="identificador_letra" placeholder="ex: A" maxlength="5" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Modalidade</label>
                        <select name="modalidade" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                            <option value="musculacao">Musculação</option>
                            <option value="corrida">Corrida</option>
                            <option value="alongamento">Alongamento</option>
                            <option value="misto">Misto / Funcional</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Freq. Semanal Sugerida</label>
                        <input type="number" name="frequencia_semanal_sugerida" min="1" max="7" placeholder="ex: 2" value="2" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Exercícios Inclusos (segure Ctrl/Cmd para múltiplos)</label>
                    <select name="exercise_ids[]" multiple style="width: 100%; height: 140px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.85rem;">
                        @foreach($allExercises as $ex)
                            <option value="{{ $ex->id }}">{{ $ex->nome }} ({{ ucfirst($ex->grupo_muscular_principal ?? $ex->modalidade) }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Descrição / Orientações</label>
                    <textarea name="descricao" placeholder="Orientações de descanso, cadência ou bi-sets..." style="width: 100%; height: 60px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.85rem; resize: none;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closePlanModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Ficha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EQUIPAMENTO / TÊNIS -->
    <div class="modal-overlay" id="gear-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Novo Tênis ou Equipamento
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closeGearModal()">&times;</button>
            </div>

            <form id="gear-form" onsubmit="submitGear(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Marca</label>
                        <input type="text" name="marca" placeholder="ex: Nike, Asics, Olympikus" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Modelo</label>
                        <input type="text" name="modelo" placeholder="ex: Pegasus 40, Novablast 4" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Tipo</label>
                        <select name="tipo" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                            <option value="tenis_rodagem">Tênis de Rodagem</option>
                            <option value="tenis_prova">Tênis de Prova / Velocidade</option>
                            <option value="acessorio">Acessório (Cinto, Caneleira, etc.)</option>
                            <option value="outro">Outro Equipamento</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Data de Aquisição</label>
                        <input type="date" name="data_aquisicao" value="{{ now()->toDateString() }}" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Km Inicial (se já usado)</label>
                        <input type="number" step="0.1" name="quilometragem_inicial_km" value="0" placeholder="0" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Vida Útil Estimada (km)</label>
                        <input type="number" name="vida_util_limite_km" value="800" placeholder="800" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closeGearModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Equipamento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: RECORDE PESSOAL (PR) -->
    <div class="modal-overlay" id="pr-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Novo Recorde Pessoal (PR)
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closePrModal()">&times;</button>
            </div>

            <form id="pr-form" onsubmit="submitPr(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Modalidade</label>
                        <select name="modalidade" id="pr-modalidade-select" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                            <option value="musculacao">Musculação</option>
                            <option value="corrida">Corrida</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Exercício Vinculado</label>
                        <select name="exercise_id" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                            <option value="">-- Opcional / Geral --</option>
                            @foreach($allExercises as $ex)
                                <option value="{{ $ex->id }}">{{ $ex->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Tipo de Recorde</label>
                        <input type="text" name="tipo_recorde" placeholder="ex: 1rm_carga, 5k_pace, max_reps" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Data da Conquista</label>
                        <input type="date" name="data_recorde" value="{{ now()->toDateString() }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Valor Numérico</label>
                        <input type="number" step="0.01" name="valor_numerico" placeholder="ex: 80 ou 24.5" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Valor Formatado (exibição)</label>
                        <input type="text" name="valor_formatado" placeholder="ex: 80 kg ou 24:30 min" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Notas / Contexto</label>
                    <input type="text" name="notas" placeholder="ex: Supino reto com barra com forma impecável" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closePrModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Recorde</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: REGISTRAR SESSÃO DE TREINO -->
    <div class="modal-overlay" id="session-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-dark);">
                    Registrar Treino Realizado
                </h3>
                <button type="button" style="background: none; border: none; font-size: 1.4rem; cursor: pointer; color: var(--text-muted);" onclick="closeSessionModal()">&times;</button>
            </div>

            <form id="session-form" onsubmit="submitSession(event)">
                @csrf
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Nome da Sessão</label>
                        <input type="text" name="nome_sessao" placeholder="ex: Treino A - Peitoral e Tríceps" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Modalidade</label>
                        <select name="modalidade" id="session-modalidade-select" onchange="toggleSessionDistancia(this.value)" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                            <option value="musculacao">Musculação</option>
                            <option value="corrida">Corrida</option>
                            <option value="alongamento">Alongamento</option>
                            <option value="misto">Misto</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Início</label>
                        <input type="datetime-local" name="data_hora_inicio" value="{{ now()->format('Y-m-d\TH:i') }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Duração (minutos)</label>
                        <input type="number" name="duracao_minutos" placeholder="45" value="45" min="1" max="720" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Esforço Percebido (RPE 1-10)</label>
                        <input type="number" name="esforco_percebido_rpe" min="1" max="10" placeholder="ex: 8" value="8" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                    <div id="session-distancia-field" style="display: none;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem;">Distância Corrida (km)</label>
                        <input type="number" step="0.01" name="distancia_km" placeholder="ex: 5.0" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem;">Observações</label>
                    <textarea name="observacoes" placeholder="Sensação no treino, séries principais..." style="width: 100%; height: 60px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.85rem; resize: none;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn-outline" onclick="closeSessionModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar Treino</button>
                </div>
            </form>
        </div>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div class="toast-notification" id="toast">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span id="toast-msg">Operação realizada com sucesso!</span>
    </div>

    <script>
        // Dados do backend
        let trackerSummaryData = @json($trackerSummary);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let autoSaveTimer = null;
        let timeLapsePlaying = false;
        let timeLapseInterval = null;

        // Toast feedback
        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Tab Switcher
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).style.display = 'block';
            btn.classList.add('active');
        }

        // Zero-Friction Auto-Save with Debounce
        function triggerAutoSave() {
            const statusPill = document.getElementById('auto-save-status');
            statusPill.style.background = '#fef3c7';
            statusPill.style.color = '#92400e';
            statusPill.innerText = '⟳ Salvando...';

            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(async () => {
                const form = document.getElementById('daily-log-form');
                const formData = new FormData(form);

                const payload = {
                    date: '{{ now()->toDateString() }}',
                    workout_done: formData.get('workout_done') === '1',
                    workout_type: formData.get('workout_type'),
                    workout_duration_min: formData.get('workout_duration_min'),
                    breakfast_clean: formData.get('breakfast_clean') === '1',
                    lunch_clean: formData.get('lunch_clean') === '1',
                    snack_done: formData.get('snack_done') === '1',
                    dinner_clean: formData.get('dinner_clean') === '1',
                    water_volume_ml: formData.get('water_volume_ml'),
                    notes: formData.get('notes'),
                };

                try {
                    const response = await fetch('{{ route("api.tracker.daily-log.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const res = await response.json();
                    if (res.success) {
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        statusPill.style.background = '#dcfce7';
                        statusPill.style.color = '#166534';
                        statusPill.innerText = `✓ Salvo às ${timeStr}`;
                        document.getElementById('kpi-adherence-val').innerHTML = `${res.adherence_score}% <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">cumprido</span>`;

                        if (res.adherence_score === 100 || payload.water_volume_ml >= 3000) {
                            launchConfetti();
                        }
                    }
                } catch (err) {
                    console.error(err);
                    statusPill.style.background = '#fee2e2';
                    statusPill.style.color = '#991b1b';
                    statusPill.innerText = 'Erro ao sincronizar';
                }
            }, 600);
        }

        // Water Counter logic
        function addWater(amount) {
            const input = document.getElementById('water-volume-input');
            let current = parseInt(input.value) || 0;
            const previous = current;
            current += amount;
            input.value = current;
            updateWaterDisplay(current);

            if (previous < 3000 && current >= 3000) {
                launchConfetti();
                showToast('🎉 Meta de 3.000 ml de água atingida hoje!');
            }

            triggerAutoSave();
        }

        function resetWater() {
            const input = document.getElementById('water-volume-input');
            input.value = 0;
            updateWaterDisplay(0);
            triggerAutoSave();
        }

        function updateWaterDisplay(val) {
            document.getElementById('water-display-val').innerText = `${val} / 3000 ml`;
            const pct = Math.min(100, Math.round((val / 3000) * 100));
            document.getElementById('water-progress-fill').style.width = `${pct}%`;
        }

        // Keyboard Shortcuts (1-5, +, -)
        window.addEventListener('keydown', (e) => {
            if (['input', 'textarea', 'select'].includes(document.activeElement.tagName.toLowerCase())) {
                return;
            }

            if (e.key === '1') {
                const el = document.getElementById('hk-breakfast');
                el.checked = !el.checked;
                triggerAutoSave();
            } else if (e.key === '2') {
                const el = document.getElementById('hk-lunch');
                el.checked = !el.checked;
                triggerAutoSave();
            } else if (e.key === '3') {
                const el = document.getElementById('hk-snack');
                el.checked = !el.checked;
                triggerAutoSave();
            } else if (e.key === '4') {
                const el = document.getElementById('hk-dinner');
                el.checked = !el.checked;
                triggerAutoSave();
            } else if (e.key === '5') {
                const el = document.getElementById('hk-workout');
                el.checked = !el.checked;
                triggerAutoSave();
            } else if (e.key === '+' || e.key === '=') {
                addWater(250);
            } else if (e.key === '-') {
                const input = document.getElementById('water-volume-input');
                let current = Math.max(0, (parseInt(input.value) || 0) - 250);
                input.value = current;
                updateWaterDisplay(current);
                triggerAutoSave();
            }
        });

        // Heatmap Styling on Mannequin SVG
        function applyHeatmapColors() {
            if (!trackerSummaryData || !trackerSummaryData.points) return;

            const pts = trackerSummaryData.points;

            // Abdômen: se delta < 0 (redução), ganha gradiente de queima
            const abdoEl = document.getElementById('heat-abdomen');
            if (abdoEl && pts.abdomen_umbilical) {
                if (pts.abdomen_umbilical.delta_pct < 0) {
                    abdoEl.setAttribute('fill', 'url(#grad-fat-reduction)');
                    abdoEl.setAttribute('filter', 'url(#glow-heat)');
                } else {
                    abdoEl.setAttribute('fill', '#e2e8f0');
                    abdoEl.removeAttribute('filter');
                }
            }

            // Cintura: se delta < 0, ganha gradiente de queima
            const waistEl = document.getElementById('heat-waist');
            if (waistEl && pts.waist_narrow) {
                if (pts.waist_narrow.delta_pct < 0) {
                    waistEl.setAttribute('fill', 'url(#grad-fat-reduction)');
                    waistEl.setAttribute('filter', 'url(#glow-heat)');
                } else {
                    waistEl.setAttribute('fill', '#e2e8f0');
                    waistEl.removeAttribute('filter');
                }
            }

            // Tórax: se delta >= 0, ganha gradiente de hipertrofia
            const chestEl = document.getElementById('heat-chest');
            if (chestEl && pts.chest) {
                if (pts.chest.delta_pct >= 0) {
                    chestEl.setAttribute('fill', 'url(#grad-muscle-gain)');
                    chestEl.setAttribute('filter', 'url(#glow-heat)');
                } else {
                    chestEl.setAttribute('fill', '#e2e8f0');
                    chestEl.removeAttribute('filter');
                }
            }

            // Braço Direito
            const armEl = document.getElementById('heat-arm-right');
            if (armEl && pts.arm_right) {
                if (pts.arm_right.delta_pct >= 0) {
                    armEl.setAttribute('fill', 'url(#grad-muscle-gain)');
                } else {
                    armEl.setAttribute('fill', '#e2e8f0');
                }
            }

            // Coxa Direita
            const thighEl = document.getElementById('heat-thigh-right');
            if (thighEl && pts.thigh_right) {
                if (pts.thigh_right.delta_pct >= 0) {
                    thighEl.setAttribute('fill', 'url(#grad-muscle-gain)');
                } else {
                    thighEl.setAttribute('fill', '#e2e8f0');
                }
            }
        }

        // Mannequin interactive point selection
        function selectMannequinPoint(pointKey) {
            document.querySelectorAll('.body-pin').forEach(pin => pin.classList.remove('active'));
            const activePin = document.querySelector(`.body-pin[data-point="${pointKey}"]`);
            if (activePin) activePin.classList.add('active');

            if (!trackerSummaryData || !trackerSummaryData.points || !trackerSummaryData.points[pointKey]) {
                const names = {
                    chest: 'Tórax / Peitoral',
                    waist_narrow: 'Cintura Alta (Estreita)',
                    abdomen_umbilical: 'Abdômen Umbilical',
                    hips: 'Quadril',
                    arm_right: 'Braço Direito',
                    thigh_right: 'Coxa Direita',
                };
                document.getElementById('detail-point-name').innerText = names[pointKey] || pointKey;
                document.getElementById('detail-current-val').innerText = '-- cm';
                document.getElementById('detail-baseline-val').innerText = '-- cm';
                document.getElementById('detail-delta-abs').innerText = '-- cm';
                document.getElementById('detail-point-badge').innerText = 'Aguardando 1º check-in';
                document.getElementById('detail-point-badge').className = 'delta-badge delta-neutral';
                document.getElementById('detail-recomp-feedback').innerText = 'Cadastre seu primeiro check-in para gerar o marco zero (baseline) e calcular os deltas de recomposição.';
                return;
            }

            const p = trackerSummaryData.points[pointKey];
            document.getElementById('detail-point-name').innerText = p.name;
            document.getElementById('detail-current-val').innerText = `${p.current} ${p.unit}`;
            document.getElementById('detail-baseline-val').innerText = `${p.baseline} ${p.unit}`;

            const sign = p.delta_abs > 0 ? '+' : '';
            document.getElementById('detail-delta-abs').innerText = `${sign}${p.delta_abs} ${p.unit}`;

            const badge = document.getElementById('detail-point-badge');
            badge.innerText = `Δ ${sign}${p.delta_abs} ${p.unit} (${sign}${p.delta_pct}%)`;

            if (p.is_positive) {
                badge.className = 'delta-badge delta-positive';
            } else if (p.delta_pct === 0) {
                badge.className = 'delta-badge delta-neutral';
            } else {
                badge.className = 'delta-badge delta-negative';
            }

            // Semantic interpretation
            let feedback = '';
            if (p.category === 'fat') {
                if (p.delta_pct < 0) {
                    feedback = `🎉 Excelente! Redução de ${Math.abs(p.delta_pct)}% na circunferência (${Math.abs(p.delta_abs)} cm a menos). Indica queima ativa de gordura visceral/abdominal.`;
                } else if (p.delta_pct === 0) {
                    feedback = `Medida estável em relação ao marco zero (${p.baseline} cm). Mantenha o foco nos hábitos limpos.`;
                } else {
                    feedback = `Leve aumento (+${p.delta_abs} cm). Pode indicar retenção hídrica ou sobrecarga glicêmica recente.`;
                }
            } else if (p.category === 'muscle') {
                if (p.delta_pct >= 0) {
                    feedback = `💪 Ótimo! Massa muscular preservada ou hipertrofiada (+${p.delta_abs} cm). Sinal positivo de treino de força eficiente.`;
                } else if (Math.abs(p.delta_pct) <= 2) {
                    feedback = `Leve oscilação de ${p.delta_pct}%, compatível com afinamento da camada de gordura subcutânea no local mantendo a densidade.`;
                } else {
                    feedback = `Redução de ${Math.abs(p.delta_abs)} cm. Monitore a ingestão de proteínas diárias para evitar perda de massa magra.`;
                }
            } else {
                feedback = `Circunferência atual: ${p.current} cm. Delta acumulado: ${sign}${p.delta_abs} cm.`;
            }
            document.getElementById('detail-recomp-feedback').innerText = feedback;

            // Render Sparkline
            renderSparkline(p.history);
        }

        function renderSparkline(history) {
            const svg = document.getElementById('sparkline-svg');
            if (!history || history.length < 2) {
                svg.innerHTML = '<text x="120" y="24" text-anchor="middle" fill="#94a3b8" font-size="12">Histórico insuficiente para curva</text>';
                return;
            }

            const values = history.map(h => h.value);
            const min = Math.min(...values);
            const max = Math.max(...values);
            const range = max - min || 1;

            const width = 240;
            const height = 40;
            const step = width / (values.length - 1);

            const points = values.map((val, idx) => {
                const x = idx * step;
                const y = height - 6 - ((val - min) / range) * (height - 12);
                return `${x},${y}`;
            }).join(' ');

            svg.innerHTML = `
                <polyline points="${points}" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            `;
        }

        // Spider / Radar Chart (Teia de Recomposição Anatômica)
        function renderRadarChart(baselineData, currentData) {
            const svg = document.getElementById('spider-radar-svg');
            if (!svg) return;

            const cx = 100, cy = 85, r = 60;
            const axes = [
                { name: 'Tórax', key: 'chest_cm', max: 130 },
                { name: 'Braço', key: 'arm_right_cm', max: 50 },
                { name: 'Cintura', key: 'waist_narrow_cm', max: 120 },
                { name: 'Abdômen', key: 'abdomen_umbilical_cm', max: 130 },
                { name: 'Quadril', key: 'hips_cm', max: 130 },
                { name: 'Coxa', key: 'thigh_right_cm', max: 80 }
            ];

            const total = axes.length;
            let gridSvg = '';

            // Grid concentric polygons
            for (let level = 1; level <= 3; level++) {
                const levelR = (r / 3) * level;
                let polyPoints = [];
                for (let i = 0; i < total; i++) {
                    const angle = (Math.PI * 2 / total) * i - Math.PI / 2;
                    const x = cx + levelR * Math.cos(angle);
                    const y = cy + levelR * Math.sin(angle);
                    polyPoints.push(`${x},${y}`);
                }
                gridSvg += `<polygon points="${polyPoints.join(' ')}" fill="none" stroke="#e2e8f0" stroke-width="1" />`;
            }

            // Axis lines & labels
            let axisLines = '';
            for (let i = 0; i < total; i++) {
                const angle = (Math.PI * 2 / total) * i - Math.PI / 2;
                const x = cx + r * Math.cos(angle);
                const y = cy + r * Math.sin(angle);
                const lx = cx + (r + 14) * Math.cos(angle);
                const ly = cy + (r + 14) * Math.sin(angle) + 4;
                axisLines += `
                    <line x1="${cx}" y1="${cy}" x2="${x}" y2="${y}" stroke="#cbd5e1" stroke-width="1" />
                    <text x="${lx}" y="${ly}" font-size="8" font-weight="700" fill="#64748b" text-anchor="middle">${axes[i].name}</text>
                `;
            }

            // Baseline Polygon
            let basePoints = [];
            if (baselineData) {
                for (let i = 0; i < total; i++) {
                    const angle = (Math.PI * 2 / total) * i - Math.PI / 2;
                    const val = parseFloat(baselineData[axes[i].key]) || (axes[i].max * 0.7);
                    const normR = Math.min(r, Math.max(10, (val / axes[i].max) * r));
                    const x = cx + normR * Math.cos(angle);
                    const y = cy + normR * Math.sin(angle);
                    basePoints.push(`${x},${y}`);
                }
            }

            // Current Polygon
            let curPoints = [];
            if (currentData) {
                for (let i = 0; i < total; i++) {
                    const angle = (Math.PI * 2 / total) * i - Math.PI / 2;
                    const val = parseFloat(currentData[axes[i].key]) || (axes[i].max * 0.65);
                    const normR = Math.min(r, Math.max(10, (val / axes[i].max) * r));
                    const x = cx + normR * Math.cos(angle);
                    const y = cy + normR * Math.sin(angle);
                    curPoints.push(`${x},${y}`);
                }
            }

            const basePolygon = basePoints.length ? `<polygon points="${basePoints.join(' ')}" fill="rgba(245, 158, 11, 0.15)" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="3,2" />` : '';
            const curPolygon = curPoints.length ? `<polygon points="${curPoints.join(' ')}" fill="rgba(16, 185, 129, 0.25)" stroke="#10b981" stroke-width="2" />` : '';

            svg.innerHTML = gridSvg + axisLines + basePolygon + curPolygon;
        }

        // Time-Lapse Slider scrubber
        function initTimelineSlider() {
            if (!trackerSummaryData || !trackerSummaryData.timeline || trackerSummaryData.timeline.length === 0) return;

            const slider = document.getElementById('time-lapse-slider');
            const list = trackerSummaryData.timeline;
            slider.max = list.length - 1;
            slider.value = list.length - 1;

            document.getElementById('timeline-start-date').innerText = list[0].date_formatted || 'Base';
            document.getElementById('timeline-end-date').innerText = list[list.length - 1].date_formatted || 'Atual';
            document.getElementById('timeline-current-label').innerText = `Medição ${list.length}/${list.length} (${list[list.length - 1].date_formatted})`;

            renderRadarChart(list[0], list[list.length - 1]);
        }

        function handleTimelineScrub(index) {
            const list = trackerSummaryData.timeline;
            if (!list || !list[index]) return;

            const item = list[index];
            document.getElementById('timeline-current-label').innerText = `Check-in ${parseInt(index) + 1}/${list.length} (${item.date_formatted})`;

            // Atualiza o ponto selecionado com o valor desta medição
            const base = list[0];
            const activePointKey = document.querySelector('.body-pin.active')?.getAttribute('data-point') || 'chest';
            const colMap = {
                chest: 'chest_cm',
                waist_narrow: 'waist_narrow_cm',
                abdomen_umbilical: 'abdomen_umbilical_cm',
                hips: 'hips_cm',
                arm_right: 'arm_right_cm',
                thigh_right: 'thigh_right_cm'
            };

            const col = colMap[activePointKey];
            if (col) {
                const curVal = item[col];
                const baseVal = base[col];
                const deltaAbs = Math.round((curVal - baseVal) * 100) / 100;
                const deltaPct = baseVal > 0 ? Math.round(((curVal - baseVal) / baseVal) * 1000) / 10 : 0;
                const sign = deltaAbs > 0 ? '+' : '';

                document.getElementById('detail-current-val').innerText = `${curVal} cm`;
                document.getElementById('detail-delta-abs').innerText = `${sign}${deltaAbs} cm`;
                document.getElementById('detail-point-badge').innerText = `Δ ${sign}${deltaAbs} cm (${sign}${deltaPct}%)`;
            }

            renderRadarChart(base, item);
        }

        function toggleTimeLapsePlay() {
            const btn = document.getElementById('btn-play-timelapse');
            const slider = document.getElementById('time-lapse-slider');
            const list = trackerSummaryData.timeline;
            if (!list || list.length <= 1) return;

            if (timeLapsePlaying) {
                clearInterval(timeLapseInterval);
                timeLapsePlaying = false;
                btn.innerText = '▶ Play Time-Lapse';
            } else {
                timeLapsePlaying = true;
                btn.innerText = '⏸ Pausar';
                slider.value = 0;
                handleTimelineScrub(0);

                timeLapseInterval = setInterval(() => {
                    let val = parseInt(slider.value);
                    if (val < list.length - 1) {
                        val++;
                        slider.value = val;
                        handleTimelineScrub(val);
                    } else {
                        clearInterval(timeLapseInterval);
                        timeLapsePlaying = false;
                        btn.innerText = '▶ Play Time-Lapse';
                    }
                }, 800);
            }
        }

        // Photo Comparator Modes: Split, Ghost Fade, Zoom
        function setComparatorMode(mode, btn) {
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const splitBox = document.getElementById('slider-box');
            const ghostBox = document.getElementById('ghost-box');
            const ghostControls = document.getElementById('ghost-controls');
            const zoomBox = document.getElementById('zoom-box');

            if (mode === 'split') {
                if (splitBox) splitBox.style.display = 'block';
                if (ghostBox) ghostBox.style.display = 'none';
                if (ghostControls) ghostControls.style.display = 'none';
                if (zoomBox) zoomBox.style.display = 'none';
            } else if (mode === 'ghost') {
                if (splitBox) splitBox.style.display = 'none';
                if (ghostBox) ghostBox.style.display = 'block';
                if (ghostControls) ghostControls.style.display = 'flex';
                if (zoomBox) zoomBox.style.display = 'none';
            } else if (mode === 'zoom') {
                if (splitBox) splitBox.style.display = 'none';
                if (ghostBox) ghostBox.style.display = 'none';
                if (ghostControls) ghostControls.style.display = 'none';
                if (zoomBox) zoomBox.style.display = 'grid';
            }
        }

        function handleGhostOpacity(val) {
            const overlay = document.getElementById('ghost-overlay-img');
            if (overlay) {
                overlay.style.opacity = val / 100;
            }
        }

        function handleSynchronizedZoom(e) {
            const rect = e.currentTarget.getBoundingClientRect();
            const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
            const y = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
            const xPct = (x / rect.width) * 100;
            const yPct = (y / rect.height) * 100;

            const leftImg = document.getElementById('zoom-img-left');
            const rightImg = document.getElementById('zoom-img-right');
            if (leftImg) {
                leftImg.style.setProperty('--zoom-x', `${xPct}%`);
                leftImg.style.setProperty('--zoom-y', `${yPct}%`);
            }
            if (rightImg) {
                rightImg.style.setProperty('--zoom-x', `${xPct}%`);
                rightImg.style.setProperty('--zoom-y', `${yPct}%`);
            }
        }

        function handleSliderMove(e) {
            const container = document.getElementById('slider-box');
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width));
            const pct = (x / rect.width) * 100;
            container.style.setProperty('--split-pos', `${pct}%`);
        }

        function handleSliderTouch(e) {
            const container = document.getElementById('slider-box');
            if (!container || !e.touches[0]) return;
            const rect = container.getBoundingClientRect();
            const x = Math.max(0, Math.min(e.touches[0].clientX - rect.left, rect.width));
            const pct = (x / rect.width) * 100;
            container.style.setProperty('--split-pos', `${pct}%`);
        }

        function filterComparatorAngle() {
            const angle = document.getElementById('comparator-angle-select').value;
            const cards = document.querySelectorAll('.photo-card');
            cards.forEach(card => {
                if (card.getAttribute('data-angle') === angle || !angle) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Lightweight In-Browser Confetti Celebration (Zero Dependencies)
        function launchConfetti() {
            const canvas = document.getElementById('confetti-canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;

            const pieces = [];
            const colors = ['#f97316', '#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6'];

            for (let i = 0; i < 75; i++) {
                pieces.push({
                    x: canvas.width / 2,
                    y: canvas.height / 2,
                    vx: (Math.random() - 0.5) * 14,
                    vy: (Math.random() - 0.7) * 16,
                    size: Math.random() * 8 + 4,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    rotation: Math.random() * 360,
                    rSpeed: (Math.random() - 0.5) * 10,
                    alpha: 1
                });
            }

            let frame = 0;
            function update() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                frame++;
                let alive = false;

                pieces.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.35; // Gravidade
                    p.rotation += p.rSpeed;
                    p.alpha -= 0.012;

                    if (p.alpha > 0) {
                        alive = true;
                        ctx.save();
                        ctx.globalAlpha = p.alpha;
                        ctx.translate(p.x, p.y);
                        ctx.rotate((p.rotation * Math.PI) / 180);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
                        ctx.restore();
                    }
                });

                if (alive && frame < 120) {
                    requestAnimationFrame(update);
                } else {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            }
            requestAnimationFrame(update);
        }

        // Modals
        function openMeasurementModal() {
            document.getElementById('measurement-modal').classList.add('active');
        }

        function closeMeasurementModal() {
            document.getElementById('measurement-modal').classList.remove('active');
        }

        function openPhotoModal() {
            document.getElementById('photo-modal').classList.add('active');
        }

        function closePhotoModal() {
            document.getElementById('photo-modal').classList.remove('active');
        }

        // Submit Anthropometric Measurement
        async function submitMeasurement(e) {
            e.preventDefault();
            const form = document.getElementById('measurement-form');
            const formData = new FormData(form);

            const payload = {
                date: formData.get('date'),
                weight_kg: formData.get('weight_kg'),
                chest_cm: formData.get('chest_cm'),
                waist_narrow_cm: formData.get('waist_narrow_cm'),
                abdomen_umbilical_cm: formData.get('abdomen_umbilical_cm'),
                hips_cm: formData.get('hips_cm'),
                arm_right_cm: formData.get('arm_right_cm'),
                thigh_right_cm: formData.get('thigh_right_cm'),
                notes: formData.get('notes'),
            };

            try {
                const res = await fetch('{{ route("api.tracker.measurements.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    launchConfetti();
                    showToast('Check-in de medidas registrado!');
                    closeMeasurementModal();
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao salvar medições.');
            }
        }

        // Submit Photo
        async function submitPhoto(e) {
            e.preventDefault();
            const form = document.getElementById('photo-form');
            const formData = new FormData(form);

            try {
                const res = await fetch('{{ route("api.tracker.photos.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Foto cadastrada com sucesso!');
                    closePhotoModal();
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao enviar foto.');
            }
        }

        // Delete Photo
        async function deletePhoto(photoId) {
            if (!confirm('Deseja realmente excluir esta foto de evolução?')) return;

            try {
                const res = await fetch(`/api/tracker/photos/${photoId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Foto excluída com sucesso.');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir foto.');
            }
        }

        // ==========================
        // WORKOUT PLANS (FICHAS)
        // ==========================
        function openPlanModal() {
            document.getElementById('plan-modal').classList.add('active');
        }

        function closePlanModal() {
            document.getElementById('plan-modal').classList.remove('active');
        }

        async function submitPlan(e) {
            e.preventDefault();
            const form = document.getElementById('plan-form');
            const formData = new FormData(form);

            try {
                const res = await fetch('{{ route("treinos.plans.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Ficha de treino cadastrada com sucesso!');
                    closePlanModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Erro ao salvar ficha.');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao salvar ficha.');
            }
        }

        async function deletePlan(planId) {
            if (!confirm('Deseja realmente excluir esta ficha de treino?')) return;

            try {
                const res = await fetch(`/treinos/plans/${planId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Ficha excluída com sucesso.');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir ficha.');
            }
        }

        async function generateDefaultABC() {
            if (!confirm('Deseja gerar automaticamente as fichas prontas ABC (Push, Pull e Legs) baseadas na periodização recomendada?')) return;

            try {
                const res = await fetch('{{ route("treinos.plans.generate-default") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    launchConfetti();
                    showToast('Fichas prontas ABC geradas com sucesso!');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    showToast(data.message || 'Erro ao gerar fichas.');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao gerar fichas.');
            }
        }

        // ==========================
        // GEAR / TÊNIS & EQUIPAMENTOS
        // ==========================
        function openGearModal() {
            document.getElementById('gear-modal').classList.add('active');
        }

        function closeGearModal() {
            document.getElementById('gear-modal').classList.remove('active');
        }

        async function submitGear(e) {
            e.preventDefault();
            const form = document.getElementById('gear-form');
            const formData = new FormData(form);

            try {
                const res = await fetch('{{ route("treinos.gears.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Equipamento cadastrado com sucesso!');
                    closeGearModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Erro ao salvar equipamento.');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao salvar equipamento.');
            }
        }

        async function deleteGear(gearId) {
            if (!confirm('Deseja realmente excluir este equipamento?')) return;

            try {
                const res = await fetch(`/treinos/gears/${gearId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Equipamento excluído com sucesso.');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir equipamento.');
            }
        }

        // ==========================
        // RECORDES PESSOAIS (PRs)
        // ==========================
        function openPrModal() {
            document.getElementById('pr-modal').classList.add('active');
        }

        function closePrModal() {
            document.getElementById('pr-modal').classList.remove('active');
        }

        async function submitPr(e) {
            e.preventDefault();
            const form = document.getElementById('pr-form');
            const formData = new FormData(form);

            try {
                const res = await fetch('{{ route("treinos.prs.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    launchConfetti();
                    showToast('Recorde pessoal registrado!');
                    closePrModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Erro ao salvar recorde.');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao salvar recorde.');
            }
        }

        async function deletePr(prId) {
            if (!confirm('Deseja realmente excluir este recorde?')) return;

            try {
                const res = await fetch(`/treinos/prs/${prId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Recorde excluído.');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir recorde.');
            }
        }

        // ==========================
        // SESSÕES DE TREINO
        // ==========================
        function openSessionModal() {
            document.getElementById('session-modal').classList.add('active');
        }

        function closeSessionModal() {
            document.getElementById('session-modal').classList.remove('active');
        }

        function toggleSessionDistancia(modalidade) {
            const distField = document.getElementById('session-distancia-field');
            if (modalidade === 'corrida') {
                distField.style.display = 'block';
            } else {
                distField.style.display = 'none';
            }
        }

        async function submitSession(e) {
            e.preventDefault();
            const form = document.getElementById('session-form');
            const formData = new FormData(form);

            try {
                const res = await fetch('{{ route("treinos.sessions.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    launchConfetti();
                    showToast('Treino registrado com sucesso!');
                    closeSessionModal();
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast(data.message || 'Erro ao registrar sessão.');
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao registrar sessão.');
            }
        }

        async function deleteSession(sessionId) {
            if (!confirm('Deseja realmente excluir esta sessão de treino?')) return;

            try {
                const res = await fetch(`/treinos/sessions/${sessionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Sessão de treino excluída.');
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (err) {
                console.error(err);
                showToast('Erro ao excluir sessão.');
            }
        }

        // Initialization
        document.addEventListener('DOMContentLoaded', () => {
            selectMannequinPoint('chest');
            applyHeatmapColors();
            initTimelineSlider();
        });
    </script>
</body>
</html>
