<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $album['album_name'] }} | Galeria de Fotos</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --bg-dark: #0a0a0c;
            --bg-card: #141417;
            --card-border: #1f1f23;
            --text-light: #f8fafc;
            --text-sub: #94a3b8;
            --bg-glass: rgba(20, 20, 23, 0.95);
            --text-dark: #f8fafc;
        }

        /* Dark Layout overrides */
        body {
            background-color: #0a0a0c !important;
            color: #f8fafc !important;
        }

        .layout {
            background-color: #0a0a0c;
        }

        .main-content {
            background-color: #0a0a0c;
            color: #f8fafc;
        }

        .content-header {
            background-color: #121215 !important;
            border-bottom: 1px solid #1f1f23 !important;
            padding: 1.5rem 2rem !important;
        }

        .content-header h1 {
            color: #ffffff !important;
        }

        .content-header p {
            color: #94a3b8 !important;
        }

        .btn-toggle-sidebar {
            background: #1c1c21 !important;
            border: 1px solid #2c2c35 !important;
            color: #94a3b8 !important;
        }

        .btn-toggle-sidebar:hover {
            background: #272730 !important;
            color: #ffffff !important;
            border-color: #3f3f4e !important;
        }

        .album-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .album-header {
            background: var(--bg-glass);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--text-sub);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: var(--primary);
        }

        .album-header-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .album-header-title-row h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .album-info-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.25rem;
        }

        .site-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* Site-specific badge colors */
        .site-femjoy { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
        .site-metart { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .site-mplstudios { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .site-watch4beauty { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .site-wowgirls { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .site-ultrafilms { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .site-local { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
        .site-default { background: linear-gradient(135deg, #4b5563 0%, #1f2937 100%); }

        .meta-divider {
            color: #334155;
        }

        .album-meta-text {
            font-size: 0.875rem;
            color: var(--text-sub);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .album-meta-text strong {
            color: var(--text-dark);
        }

        /* Photo Grid */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .photo-card {
            background: #141417;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            aspect-ratio: 1/1;
            cursor: pointer;
            position: relative;
            transition: all 0.25s ease;
        }

        .photo-card:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            z-index: 5;
        }

        .photo-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .photo-card:hover .photo-card-overlay {
            opacity: 1;
        }

        /* Lightbox CSS */
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(8, 10, 20, 0.96);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 1000;
            display: none; /* Controlled by JS */
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            color: white;
            user-select: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            box-sizing: border-box;
            padding: 5rem 1.5rem 1.5rem 1.5rem;
        }

        .lightbox.active {
            display: flex;
            opacity: 1;
        }

        .lightbox-close-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.6rem;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 1010;
        }

        .lightbox-close-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }

        .lightbox-controls {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            display: flex;
            gap: 0.75rem;
            z-index: 1010;
        }

        .lightbox-control-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s;
        }

        .lightbox-control-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .lightbox-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 1005;
        }

        .lightbox-nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-50%) scale(1.05);
        }

        .lightbox-nav-prev { left: 2rem; }
        .lightbox-nav-next { right: 2rem; }

        @media (max-width: 768px) {
            .lightbox-nav-btn {
                width: 44px;
                height: 44px;
            }
            .lightbox-nav-prev { left: 0.5rem; }
            .lightbox-nav-next { right: 0.5rem; }
        }

        .lightbox-image-container {
            flex: 1;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            min-height: 0;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            cursor: zoom-in;
            transform-origin: center center;
        }

        .lightbox-image.zoomed {
            max-width: none;
            max-height: none;
            cursor: zoom-out;
        }

        /* Full Window Zoom Mode (sem limitações de container) */
        .lightbox.is-zoomed .lightbox-image-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1020;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .lightbox.is-zoomed .lightbox-image {
            max-width: 100vw;
            max-height: 100vh;
            border-radius: 0;
            box-shadow: none;
        }

        .lightbox.is-zoomed .lightbox-controls,
        .lightbox.is-zoomed .lightbox-close-btn {
            z-index: 1060;
            background: rgba(10, 10, 15, 0.85);
            backdrop-filter: blur(12px);
            border-color: rgba(255, 255, 255, 0.25);
        }

        .lightbox.is-zoomed .lightbox-nav-btn {
            z-index: 1055;
            opacity: 0.3;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .lightbox.is-zoomed .lightbox-nav-btn:hover {
            opacity: 1;
        }

        .lightbox.is-zoomed .lightbox-meta,
        .lightbox.is-zoomed .lightbox-thumbnails-container {
            opacity: 0.15;
            transition: opacity 0.25s ease;
        }
        .lightbox.is-zoomed .lightbox-meta:hover,
        .lightbox.is-zoomed .lightbox-thumbnails-container:hover {
            opacity: 1;
        }

        .lightbox-image.loaded {
            opacity: 1;
        }

        .lightbox-meta {
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            text-align: center;
            z-index: 1010;
            flex-shrink: 0;
        }

        /* Thumbnails Carousel */
        .lightbox-thumbnails-container {
            width: 100%;
            max-width: 800px;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            overflow-x: auto;
            display: flex;
            justify-content: flex-start;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
            -webkit-overflow-scrolling: touch;
            z-index: 1010;
            padding: 0.5rem;
            flex-shrink: 0;
            cursor: grab;
        }

        .lightbox-thumbnails-container:active {
            cursor: grabbing;
        }

        .lightbox-thumbnails-container::-webkit-scrollbar {
            height: 6px;
        }
        .lightbox-thumbnails-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .lightbox-thumbnails-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        .lightbox-thumbnails-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        .lightbox-thumbnails-track {
            display: flex;
            gap: 8px;
            margin: 0 auto;
        }

        .lightbox-thumb-item {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.4;
            user-select: none;
            -webkit-user-drag: none;
        }

        .lightbox-thumb-item:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .lightbox-thumb-item.active {
            opacity: 1;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
            transform: scale(1.08);
        }

        .lightbox-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
            -webkit-user-drag: none;
        }

        .lightbox-filename {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .lightbox-counter {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .lightbox-loader {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Actions Toolbar */
        .album-actions-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--card-border);
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .toolbar-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-sub);
            letter-spacing: 0.05em;
        }

        /* Rating Stars */
        .rating-stars-container {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .star-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
            color: #334155;
            transition: color 0.15s, transform 0.15s;
        }

        .star-btn:hover {
            transform: scale(1.15);
        }

        .star-btn.active {
            color: #eab308;
        }

        .star-btn.hover-active {
            color: #facc15;
        }

        /* Favorite Button */
        .favorite-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-sub);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .favorite-btn:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.4);
            color: #ef4444;
            transform: scale(1.05);
        }

        .favorite-btn.is-favorite {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
            color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
        }

        .favorite-btn svg {
            fill: currentColor;
            stroke: currentColor;
            stroke-width: 2px;
            transition: transform 0.2s;
        }

        .favorite-btn:active svg {
            transform: scale(0.8);
        }

        /* Tags Manager */
        .tags-manager-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .tag-pill {
            background: rgba(139, 92, 246, 0.1);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.2);
            padding: 0.25rem 0.6rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }

        .tag-pill:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.4);
        }

        .tag-remove-btn {
            background: none;
            border: none;
            color: #c084fc;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            transition: background 0.15s, color 0.15s;
        }

        .tag-remove-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .add-tag-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .btn-add-tag-trigger {
            background: rgba(255, 255, 255, 0.05);
            border: 1px dashed var(--card-border);
            color: var(--text-sub);
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-add-tag-trigger:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--primary);
            color: white;
        }

        .add-tag-input-form {
            display: none;
            align-items: center;
            gap: 4px;
        }

        .tag-input-field {
            background: #18181b;
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 30px;
            font-size: 0.75rem;
            outline: none;
            width: 100px;
            transition: all 0.2s;
        }

        .tag-input-field:focus {
            border-color: var(--primary);
            width: 140px;
        }

        .tag-save-btn {
            background: var(--primary);
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Optimization Progress Bar */
        .album-optimize-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .optimize-progress-bar {
            width: 120px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            display: none;
        }

        .optimize-progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, #10b981 100%);
            transition: width 0.2s ease;
        }

        /* Delete Album Button */
        .btn-danger-outline {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            transition: all 0.2s;
        }

        .btn-danger-outline:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        /* Confirmation Modal */
        .custom-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 12, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .custom-modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }

        .custom-modal {
            background: #141417;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .custom-modal-backdrop.show .custom-modal {
            transform: scale(1);
        }

        .custom-modal-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ef4444;
        }

        .custom-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .custom-modal-body p {
            margin: 0;
            color: var(--text-sub);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .custom-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
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

                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>



                    <a href="{{ route('photos.index') }}" class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Galeria Fotos</span>
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
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <form action="{{ route('photos.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout" style="background: rgba(139, 92, 246, 0.2); border-color: rgba(139, 92, 246, 0.4);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Bloquear Fotos
                        </button>
                    </form>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Sair Geral
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header" style="display: flex; align-items: center; gap: 1rem;">
                <button type="button" class="btn-toggle-sidebar js-toggle-sidebar" title="Alternar barra lateral (Ctrl + \)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div>
                    <h1>Galeria de Fotos</h1>
                    <p>Visualização de ensaios fotográficos</p>
                </div>
            </header>

            <div class="content-body">
                <div class="album-container">
                    
                    <!-- Album Header -->
                    <div class="album-header">
                        <a href="{{ route('photos.index') }}" class="btn-back">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Voltar para a Galeria
                        </a>
                        
                        <div class="album-header-title-row">
                            <div>
                                <h1>{{ $album['album_name'] }}</h1>
                                <div class="album-info-meta">
                                    <span class="site-badge site-{{ strtolower(str_replace('.', '', $album['site'])) }} {{ !in_array(strtolower($album['site']), ['femjoy', 'metart', 'mplstudios', 'watch4beauty', 'wowgirls', 'ultrafilms', 'local storage']) ? 'site-default' : '' }}">
                                        {{ $album['site'] }}
                                    </span>
                                    
                                    <span class="meta-divider">•</span>

                                    @if ($album['model'])
                                        <span class="album-meta-text">
                                            Modelo: <strong>{{ $album['model'] }}</strong>
                                        </span>
                                        <span class="meta-divider">•</span>
                                    @endif

                                    <span class="album-meta-text">
                                        Data: <strong>{{ date('d/m/Y', strtotime($album['date'])) }}</strong>
                                    </span>

                                    <span class="meta-divider">•</span>

                                    <span class="album-meta-text">
                                        Total: <strong>{{ count($photos) }} fotos</strong>
                                    </span>
                                    
                                    @if ($album['photographer'])
                                        <span class="meta-divider">•</span>
                                        <span class="album-meta-text">
                                            Fotógrafo: <strong>{{ $album['photographer'] }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions Toolbar -->
                        <div class="album-actions-toolbar">
                            <!-- Left: Rating & Favorite -->
                            <div class="action-group">
                                <div class="rating-stars-container">
                                    <span class="toolbar-label">Nota:</span>
                                    @php
                                        $currentRating = $album['rating'] ?? 0;
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button class="star-btn {{ $i <= $currentRating ? 'active' : '' }}" data-value="{{ $i }}" onclick="rateAlbum({{ $i }})" title="Dar nota {{ $i }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>

                                <button class="favorite-btn {{ !empty($album['favorite']) ? 'is-favorite' : '' }}" id="favorite-btn" onclick="toggleFavorite()" title="{{ !empty($album['favorite']) ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                                
                                <div class="tags-manager-container">
                                    <span class="toolbar-label">Tags:</span>
                                    <div id="tags-list" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        @if (!empty($album['tags']))
                                            @foreach ($album['tags'] as $tag)
                                                <span class="tag-pill" data-tag="{{ $tag }}">
                                                    {{ $tag }}
                                                    <button class="tag-remove-btn" onclick="removeTag('{{ $tag }}')">&times;</button>
                                                </span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="add-tag-wrapper">
                                        <button class="btn-add-tag-trigger" id="btn-add-tag-trigger" onclick="showAddTagInput()">+ Add Tag</button>
                                        <div class="add-tag-input-form" id="add-tag-input-form">
                                            <input type="text" class="tag-input-field" id="tag-input-field" placeholder="Tag..." onkeydown="if(event.key === 'Enter') saveTag()">
                                            <button class="tag-save-btn" onclick="saveTag()">OK</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Optimize & Delete Actions -->
                            <div class="action-group">
                                <div class="album-optimize-wrapper">
                                    <div class="optimize-progress-bar" id="optimize-progress-bar">
                                        <div class="optimize-progress-fill" id="optimize-progress-fill"></div>
                                    </div>
                                    <button class="btn-action btn-secondary" id="optimize-album-btn" onclick="optimizeAlbum()" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                                        </svg>
                                        Otimizar Álbum
                                    </button>
                                </div>

                                <button class="btn-action btn-danger-outline" onclick="openDeleteModal()" style="padding: 0.5rem 1rem; font-size: 0.8rem; border-radius: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Excluir Álbum
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Grid -->
                    <div class="photo-grid" id="photo-grid">
                        @foreach ($photos as $index => $photo)
                            @php
                                $imgSrc = route('photos.serve', ['path' => base64_encode($album['path'] . DIRECTORY_SEPARATOR . $photo), 'type' => 'thumb']);
                            @endphp
                            <div class="photo-card {{ $index >= 24 ? 'hidden-photo' : '' }}" data-index="{{ $index }}" onclick="openLightbox({{ $index }})" {!! $index >= 24 ? 'style="display: none;"' : '' !!}>
                                <img src="{{ $imgSrc }}" alt="Foto {{ $index + 1 }}" class="photo-thumbnail" loading="lazy">
                                <div class="photo-card-overlay">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 3h6v6"></path>
                                        <path d="M9 21H3v-6"></path>
                                        <line x1="21" y1="3" x2="14" y2="10"></line>
                                        <line x1="3" y1="21" x2="10" y2="14"></line>
                                    </svg>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if (count($photos) > 24)
                        <div style="display: flex; justify-content: center; margin-top: 2.5rem; margin-bottom: 1.5rem;">
                            <button id="load-more-btn" class="btn-action btn-primary" onclick="loadMorePhotos()" style="padding: 0.8rem 2rem; border: none;">
                                Carregar Mais Fotos (Exibindo <span id="displayed-count">24</span> de {{ count($photos) }})
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </main>
    </div>

    <!-- Lightbox Overlay -->
    <div id="lightbox" class="lightbox">
        <!-- Close Button -->
        <button class="lightbox-close-btn" onclick="closeLightbox()" title="Fechar (Esc)">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Top Control Bar -->
        <div class="lightbox-controls">
            <button id="slideshow-btn" class="lightbox-control-btn" onclick="toggleSlideshow()" title="Iniciar/Pausar Slideshow (Espaço)">
                <svg id="slideshow-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span id="slideshow-text">Slideshow</span>
            </button>
            <button id="zoom-btn" class="lightbox-control-btn" onclick="toggleZoom()" title="Dar Zoom (Z ou Clique)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="14"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                </svg>
                <span id="zoom-text">Zoom</span>
            </button>
        </div>

        <!-- Navigation Buttons -->
        <button class="lightbox-nav-btn lightbox-nav-prev" onclick="prevImage()" title="Anterior (Seta Esquerda)">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <button class="lightbox-nav-btn lightbox-nav-next" onclick="nextImage()" title="Próxima (Seta Direita)">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>

        <!-- Large Image -->
        <div class="lightbox-image-container">
            <div id="lightbox-loader" class="lightbox-loader"></div>
            <img id="lightbox-img" class="lightbox-image" src="" alt="Visualização ampliada">
        </div>

        <!-- Meta Text -->
        <div class="lightbox-meta">
            <div id="lightbox-counter" class="lightbox-counter">Imagem 1 de 1</div>
            <div id="lightbox-filename" class="lightbox-filename">filename.jpg</div>
        </div>

        <!-- Thumbnails Carousel -->
        <div class="lightbox-thumbnails-container" id="lightbox-thumbnails-container">
            <div id="lightbox-thumbnails-track" class="lightbox-thumbnails-track"></div>
        </div>
    </div>

    <!-- Deletion Confirmation Modal -->
    <div class="custom-modal-backdrop" id="delete-modal-backdrop">
        <div class="custom-modal">
            <div class="custom-modal-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <h3>Excluir Álbum Permanentemente</h3>
            </div>
            <div class="custom-modal-body">
                <p>Tem certeza absoluta de que deseja excluir o álbum <strong>{{ $album['album_name'] }}</strong>?</p>
                <p style="margin-top: 0.5rem; color: #f87171; font-weight: 600;">Esta ação irá deletar a pasta inteira e todos os seus arquivos do disco rígido de forma irreversível!</p>
            </div>
            <div class="custom-modal-footer">
                <button class="btn-action btn-secondary" onclick="closeDeleteModal()" style="padding: 0.5rem 1rem;">Cancelar</button>
                <button class="btn-action btn-danger" onclick="confirmDeleteAlbum()" style="padding: 0.5rem 1rem;">Sim, Excluir</button>
            </div>
        </div>
    </div>

    <!-- Lightbox Controller Script -->
    <script>
        // Array of photo objects
        const photos = [
            @foreach ($photos as $photo)
                {
                    filename: "{{ $photo }}",
                    url: "{{ route('photos.serve', ['path' => base64_encode($album['path'] . DIRECTORY_SEPARATOR . $photo)]) }}",
                    thumbUrl: "{{ route('photos.serve', ['path' => base64_encode($album['path'] . DIRECTORY_SEPARATOR . $photo), 'type' => 'thumb']) }}"
                },
            @endforeach
        ];

        let currentIndex = 0;
        let slideshowInterval = null;
        let isSlideshowActive = false;

        // Progressive Zoom and Drag/Pan Variables
        let scale = 1.0;
        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let mouseMoved = false;
        let dragStartX = 0;
        let dragStartY = 0;

        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxLoader = document.getElementById('lightbox-loader');
        const lightboxCounter = document.getElementById('lightbox-counter');
        const lightboxFilename = document.getElementById('lightbox-filename');
        const slideshowBtn = document.getElementById('slideshow-btn');
        const slideshowIcon = document.getElementById('slideshow-icon');
        const slideshowText = document.getElementById('slideshow-text');
        const zoomBtn = document.getElementById('zoom-btn');
        const zoomText = document.getElementById('zoom-text');

        // Thumbnails elements and state variables
        const thumbnailsContainer = document.getElementById('lightbox-thumbnails-container');
        const thumbnailsTrack = document.getElementById('lightbox-thumbnails-track');
        let isThumbnailsInitialized = false;

        // Lazy load more photos pagination variables
        let displayedCount = 24;
        const totalPhotos = photos.length;

        function initThumbnails() {
            if (isThumbnailsInitialized) return;
            
            thumbnailsTrack.innerHTML = '';
            photos.forEach((photo, index) => {
                const thumbItem = document.createElement('div');
                thumbItem.className = 'lightbox-thumb-item';
                thumbItem.setAttribute('data-index', index);
                thumbItem.onclick = (e) => {
                    e.stopPropagation();
                    stopSlideshow();
                    currentIndex = index;
                    loadImage(currentIndex);
                };
                
                const img = document.createElement('img');
                img.className = 'lightbox-thumb-img';
                img.src = photo.thumbUrl;
                img.alt = `Miniatura ${index + 1}`;
                img.loading = 'lazy';
                
                thumbItem.appendChild(img);
                thumbnailsTrack.appendChild(thumbItem);
            });
            
            isThumbnailsInitialized = true;
            setupThumbnailsDragScroll();
        }

        function setupThumbnailsDragScroll() {
            let isDown = false;
            let startX;
            let scrollLeft;

            thumbnailsContainer.addEventListener('mousedown', (e) => {
                isDown = true;
                thumbnailsContainer.classList.add('active');
                startX = e.pageX - thumbnailsContainer.offsetLeft;
                scrollLeft = thumbnailsContainer.scrollLeft;
            });

            thumbnailsContainer.addEventListener('mouseleave', () => {
                isDown = false;
                thumbnailsContainer.classList.remove('active');
            });

            thumbnailsContainer.addEventListener('mouseup', () => {
                isDown = false;
                thumbnailsContainer.classList.remove('active');
            });

            thumbnailsContainer.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - thumbnailsContainer.offsetLeft;
                const walk = (x - startX) * 1.5; // scroll speed multiplier
                thumbnailsContainer.scrollLeft = scrollLeft - walk;
            });
        }

        function updateActiveThumbnail(index) {
            if (!isThumbnailsInitialized) return;
            
            const items = thumbnailsTrack.querySelectorAll('.lightbox-thumb-item');
            items.forEach(item => item.classList.remove('active'));
            
            const activeItem = thumbnailsTrack.querySelector(`.lightbox-thumb-item[data-index="${index}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
                
                // Center the active thumbnail in the view area
                activeItem.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        }

        function setupTouchGestures() {
            const imageContainer = document.querySelector('.lightbox-image-container');
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;

            imageContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });

            imageContainer.addEventListener('touchend', (e) => {
                if (scale > 1.0) return; // ignore swipe when zoomed in

                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                
                handleSwipeGesture();
            }, { passive: true });

            function handleSwipeGesture() {
                const diffX = touchEndX - touchStartX;
                const diffY = touchEndY - touchStartY;
                
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                    stopSlideshow();
                    if (diffX > 0) {
                        prevImage();
                    } else {
                        nextImage();
                    }
                }
            }
        }

        // Initialize touch gestures immediately
        setupTouchGestures();

        function loadMorePhotos() {
            const hiddenPhotos = document.querySelectorAll('.photo-card.hidden-photo');
            let loaded = 0;
            
            for (let i = 0; i < hiddenPhotos.length && loaded < 24; i++) {
                const photo = hiddenPhotos[i];
                photo.style.display = '';
                photo.classList.remove('hidden-photo');
                loaded++;
            }
            
            displayedCount = Math.min(displayedCount + loaded, totalPhotos);
            const displayedEl = document.getElementById('displayed-count');
            if (displayedEl) {
                displayedEl.textContent = displayedCount;
            }
            
            if (displayedCount >= totalPhotos) {
                const loadMoreBtn = document.getElementById('load-more-btn');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'none';
                }
            }
        }

        function openLightbox(index) {
            currentIndex = index;
            lightbox.classList.add('active');
            initThumbnails();
            loadImage(currentIndex);
            document.body.style.overflow = 'hidden'; // Stop background scrolling
        }

        function updateImageTransform() {
            lightboxImg.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
            if (scale > 1.0) {
                lightbox.classList.add('is-zoomed');
                lightboxImg.classList.add('zoomed');
            } else {
                lightbox.classList.remove('is-zoomed');
                lightboxImg.classList.remove('zoomed');
            }
        }

        function updateZoomButtonUI() {
            if (scale > 1.0) {
                zoomText.textContent = `Zoom (${Math.round(scale * 100)}%)`;
                zoomBtn.querySelector('svg').innerHTML = `
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                `;
            } else {
                zoomText.textContent = 'Zoom';
                zoomBtn.querySelector('svg').innerHTML = `
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="14"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                `;
            }
        }

        function closeLightbox() {
            stopSlideshow();
            scale = 1.0;
            panX = 0;
            panY = 0;
            updateImageTransform();
            updateZoomButtonUI();
            lightbox.classList.remove('active');
            lightboxImg.src = '';
            lightboxImg.classList.remove('loaded');
            document.body.style.overflow = '';
        }

        function loadImage(index) {
            lightboxLoader.style.display = 'block';
            lightboxImg.classList.remove('loaded');
            
            // Reset zoom & pan state when loading a new image
            scale = 1.0;
            panX = 0;
            panY = 0;
            updateImageTransform();
            updateZoomButtonUI();
            lightboxImg.style.cursor = 'zoom-in';
            
            // Set source
            const photo = photos[index];
            lightboxImg.src = photo.url;

            lightboxImg.onload = function() {
                lightboxLoader.style.display = 'none';
                lightboxImg.classList.add('loaded');
            };

            // Update details
            lightboxCounter.textContent = `Imagem ${index + 1} de ${photos.length}`;
            lightboxFilename.textContent = photo.filename;

            // Update active thumbnail in the track
            updateActiveThumbnail(index);
        }

        function toggleZoom() {
            if (scale > 1.0) {
                scale = 1.0;
                panX = 0;
                panY = 0;
                lightboxImg.style.cursor = 'zoom-in';
            } else {
                scale = 2.0;
                panX = 0;
                panY = 0;
                lightboxImg.style.cursor = 'grab';
            }
            updateImageTransform();
            updateZoomButtonUI();
        }

        // Wheel listener for progressive zooming
        lightboxImg.addEventListener('wheel', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Evita que o evento de scroll propague para o background do lightbox (que passaria a foto)
            
            const zoomFactor = 0.15;
            if (e.deltaY < 0) {
                // Zoom in
                scale = Math.min(scale + zoomFactor, 5.0);
            } else {
                // Zoom out
                scale = Math.max(scale - zoomFactor, 1.0);
            }
            
            if (scale <= 1.0) {
                scale = 1.0;
                panX = 0;
                panY = 0;
                lightboxImg.style.cursor = 'zoom-in';
            } else {
                lightboxImg.style.cursor = 'grab';
            }
            
            updateImageTransform();
            updateZoomButtonUI();
        });

        // Wheel listener para o overlay do lightbox (scroll fora da foto navega pelas imagens)
        lightbox.addEventListener('wheel', function(e) {
            e.preventDefault();
            stopSlideshow();
            if (e.deltaY > 0) {
                nextImage();
            } else if (e.deltaY < 0) {
                prevImage();
            }
        });

        // Mouse drag panning handlers
        lightboxImg.addEventListener('mousedown', function(e) {
            if (scale <= 1.0) return;
            e.preventDefault();
            isDragging = true;
            mouseMoved = false;
            dragStartX = e.clientX;
            dragStartY = e.clientY;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
            lightboxImg.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            
            if (Math.abs(e.clientX - dragStartX) > 5 || Math.abs(e.clientY - dragStartY) > 5) {
                mouseMoved = true;
            }
            
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            updateImageTransform();
        });

        document.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                lightboxImg.style.cursor = 'grab';
            }
        });

        // Click image inside lightbox to toggle zoom
        lightboxImg.addEventListener('click', function(e) {
            e.stopPropagation();
            if (mouseMoved) return; // Skip if it was a drag gesture
            toggleZoom();
        });

        function nextImage() {
            currentIndex = (currentIndex + 1) % photos.length;
            loadImage(currentIndex);
        }

        function prevImage() {
            currentIndex = (currentIndex - 1 + photos.length) % photos.length;
            loadImage(currentIndex);
        }

        function toggleSlideshow() {
            if (isSlideshowActive) {
                stopSlideshow();
            } else {
                startSlideshow();
            }
        }

        function startSlideshow() {
            isSlideshowActive = true;
            slideshowText.textContent = 'Pausar';
            // Set Pause SVG Icon
            slideshowIcon.innerHTML = `
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
            `;
            slideshowInterval = setInterval(nextImage, 3500); // changes every 3.5 seconds
        }

        function stopSlideshow() {
            isSlideshowActive = false;
            slideshowText.textContent = 'Slideshow';
            // Set Play SVG Icon
            slideshowIcon.innerHTML = `
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
            `;
            if (slideshowInterval) {
                clearInterval(slideshowInterval);
                slideshowInterval = null;
            }
        }

        // Keyboard Event Handlers
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;

            const key = e.key.toLowerCase();

            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowRight' || key === 'd') {
                stopSlideshow();
                nextImage();
            } else if (e.key === 'ArrowLeft' || key === 'a') {
                stopSlideshow();
                prevImage();
            } else if (e.key === ' ') {
                e.preventDefault(); // Stop page scrolling
                toggleSlideshow();
            } else if (key === 'z') {
                toggleZoom();
            }
        });

        // Setup Rating Hover States
        document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('.star-btn');
            stars.forEach(star => {
                star.addEventListener('mouseenter', function() {
                    const hoverValue = parseInt(this.getAttribute('data-value'));
                    stars.forEach(s => {
                        const val = parseInt(s.getAttribute('data-value'));
                        if (val <= hoverValue) {
                            s.classList.add('hover-active');
                        } else {
                            s.classList.remove('hover-active');
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    stars.forEach(s => s.classList.remove('hover-active'));
                });
            });
        });

        async function rateAlbum(rating) {
            try {
                const response = await fetch("{{ route('photos.album.rate', $album['id']) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ rating: rating })
                });
                const data = await response.json();
                if (data.success) {
                    const stars = document.querySelectorAll('.star-btn');
                    stars.forEach(s => {
                        const val = parseInt(s.getAttribute('data-value'));
                        if (val <= rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                } else {
                    alert(data.message || 'Erro ao dar nota.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao dar nota.');
            }
        }

        async function toggleFavorite() {
            const btn = document.getElementById('favorite-btn');
            try {
                const response = await fetch("{{ route('photos.album.favorite', $album['id']) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (data.favorite) {
                        btn.classList.add('is-favorite');
                        btn.setAttribute('title', 'Remover dos favoritos');
                    } else {
                        btn.classList.remove('is-favorite');
                        btn.setAttribute('title', 'Adicionar aos favoritos');
                    }
                } else {
                    alert(data.message || 'Erro ao favoritar.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao favoritar.');
            }
        }

        // Tags Logic
        let albumTags = {!! json_encode($album['tags'] ?? []) !!};

        function showAddTagInput() {
            document.getElementById('btn-add-tag-trigger').style.display = 'none';
            const form = document.getElementById('add-tag-input-form');
            form.style.display = 'flex';
            const input = document.getElementById('tag-input-field');
            input.value = '';
            input.focus();
        }

        function hideAddTagInput() {
            document.getElementById('btn-add-tag-trigger').style.display = 'flex';
            document.getElementById('add-tag-input-form').style.display = 'none';
        }

        async function saveTag() {
            const input = document.getElementById('tag-input-field');
            const newTag = input.value.trim();
            if (!newTag) {
                hideAddTagInput();
                return;
            }

            if (!albumTags.includes(newTag)) {
                albumTags.push(newTag);
                await sendTagsUpdate();
            } else {
                hideAddTagInput();
            }
        }

        async function removeTag(tagToRemove) {
            albumTags = albumTags.filter(t => t !== tagToRemove);
            await sendTagsUpdate();
        }

        async function sendTagsUpdate() {
            try {
                const response = await fetch("{{ route('photos.album.tags', $album['id']) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ tags: albumTags.join(',') })
                });
                const data = await response.json();
                if (data.success) {
                    renderTagsList(data.tags);
                } else {
                    alert(data.message || 'Erro ao atualizar tags.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao atualizar tags.');
            } finally {
                hideAddTagInput();
            }
        }

        function renderTagsList(tags) {
            const listContainer = document.getElementById('tags-list');
            listContainer.innerHTML = '';
            tags.forEach(tag => {
                const span = document.createElement('span');
                span.className = 'tag-pill';
                span.setAttribute('data-tag', tag);
                span.innerHTML = `
                    ${tag}
                    <button class="tag-remove-btn" onclick="removeTag('${tag}')">&times;</button>
                `;
                listContainer.appendChild(span);
            });
        }

        // Delete Modal Logic
        const deleteModal = document.getElementById('delete-modal-backdrop');

        function openDeleteModal() {
            deleteModal.classList.add('show');
        }

        function closeDeleteModal() {
            deleteModal.classList.remove('show');
        }

        async function confirmDeleteAlbum() {
            try {
                const response = await fetch("{{ route('photos.album.delete', $album['id']) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert('Álbum excluído com sucesso!');
                    window.location.href = "{{ route('photos.index') }}";
                } else {
                    alert(data.message || 'Erro ao excluir o álbum.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro ao excluir o álbum.');
            } finally {
                closeDeleteModal();
            }
        }

        async function optimizeAlbum() {
            const btn = document.getElementById('optimize-album-btn');
            const progressBar = document.getElementById('optimize-progress-bar');
            const progressFill = document.getElementById('optimize-progress-fill');
            
            // Disable button and show progress bar
            btn.disabled = true;
            btn.innerHTML = 'Otimizando...';
            progressBar.style.display = 'block';
            progressFill.style.width = '0%';
            
            try {
                const response = await fetch("{{ route('photos.pregenerate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ id: "{{ $album['id'] }}" })
                });
                
                if (!response.ok) {
                    throw new Error('Falha na otimização');
                }
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // keep last incomplete line in buffer
                    
                    for (const line of lines) {
                        if (line.trim()) {
                            try {
                                const data = JSON.parse(line);
                                if (data.success && data.total > 0) {
                                    const percent = (data.current / data.total) * 100;
                                    progressFill.style.width = `${percent}%`;
                                }
                            } catch (e) {
                                // Ignore json parsing errors for partial stream chunks
                            }
                        }
                    }
                }
                
                // Done
                progressFill.style.width = '100%';
                btn.innerHTML = 'Concluído!';
                alert('Álbum otimizado com sucesso!');
                setTimeout(() => {
                    progressBar.style.display = 'none';
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                        </svg>
                        Otimizar Álbum
                    `;
                }, 3000);
                
            } catch (err) {
                console.error(err);
                alert('Erro ao otimizar álbum.');
                btn.disabled = false;
                btn.innerHTML = 'Otimizar Álbum';
                progressBar.style.display = 'none';
            }
        }
    </script>
</body>
</html>
