<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria de Fotos | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --success: #10b981;
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

        .photos-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        /* Header utilities */
        .photos-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            background: var(--bg-glass);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
        }

        .header-title-section h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .header-title-section p {
            color: var(--text-sub);
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sync-info {
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-sub);
        }

        .sync-info span {
            display: block;
            font-weight: 600;
            color: var(--text-light);
        }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.3);
        }

        .btn-secondary {
            background: #1c1c21;
            border-color: #2c2c35;
            color: var(--text-light);
        }

        .btn-secondary:hover {
            background: #272730;
            border-color: #3f3f4e;
        }

        /* Filter Panel */
        .filter-panel {
            background: #121215;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-form .filter-group-wide {
            grid-column: span 2;
        }

        @media (max-width: 1024px) {
            .filter-form .filter-group-wide {
                grid-column: span 1;
            }
        }

        @media (max-width: 640px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .filter-actions {
                grid-column: span 1;
            }
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-sub);
        }

        .filter-input {
            padding: 0.625rem 0.875rem;
            border: 1px solid #2c2c35;
            border-radius: 8px;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.2s;
            background: #1c1c21;
            color: var(--text-light);
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Album Grid */
        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
            gap: 1.25rem;
        }

        .album-card {
            background: #121215;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }

        .album-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.6);
            border-color: #2d2d34;
        }

        .album-cover-wrapper {
            position: relative;
            aspect-ratio: 2/3;
            background: #161619;
            overflow: hidden;
        }

        .album-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 15%;
            transition: transform 0.5s ease;
        }

        .album-card:hover .album-cover {
            transform: scale(1.05);
        }

        .album-cover-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 2;
            pointer-events: none;
        }

        
        /* Badges e Botões de Otimização SSD */
        .opt-badge-ready {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
            color: #ffffff !important;
            border: 1px solid rgba(16, 185, 129, 0.4);
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
        }

        .opt-badge-pending {
            background: rgba(245, 158, 11, 0.2) !important;
            color: #fbbf24 !important;
            border: 1px solid rgba(245, 158, 11, 0.4);
            font-weight: 600 !important;
        }

        .btn-card-optimize {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid rgba(139, 92, 246, 0.4);
            background: rgba(139, 92, 246, 0.2);
            color: #c4b5fd;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-card-optimize:hover {
            background: #8b5cf6;
            color: white;
            border-color: #8b5cf6;
            transform: translateY(-1px);
        }

        .btn-card-optimize.is-done {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
            cursor: pointer;
        }
        .btn-card-optimize.is-done:hover {
            background: rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .stats-overview-bar {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 0.85rem 1.25rem;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--card-border);
            margin-bottom: 1.25rem;
        }

        .stat-badge-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-sub);
        }

        .stat-badge-chip strong {
            font-size: 1rem;
            font-weight: 700;
        }

        .album-badges-container {
            position: absolute;
            bottom: 0.5rem;
            left: 0.5rem;
            right: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 3;
            pointer-events: none;
        }

        .badge-pill {
            background: rgba(0, 0, 0, 0.65);
            color: #ffffff;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .count-badge svg {
            color: #ffffff;
            opacity: 0.9;
        }

        .album-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.15);
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 4;
        }

        .album-card:hover .album-overlay {
            opacity: 1;
        }

        .view-album-badge {
            background: white;
            color: #0f172a;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(10px);
            transition: transform 0.3s;
        }

        .album-card:hover .view-album-badge {
            transform: translateY(0);
        }

        .site-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 5;
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

        .album-details {
            padding: 0.75rem 0.875rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            flex-grow: 1;
        }

        .album-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.1rem;
        }

        .album-model {
            font-size: 0.75rem;
            font-weight: 500;
            color: #94a3b8;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .album-stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.6875rem;
            color: #64748b;
            border-top: 1px solid #1f1f23;
            padding-top: 0.5rem;
            margin-top: 0.25rem;
        }

        .album-date {
            color: #64748b;
        }

        .stats-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-item {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            font-weight: 600;
        }

        .rating-item {
            color: #f59e0b;
        }

        .views-item {
            color: #94a3b8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            background: var(--bg-glass);
            border-radius: 16px;
            padding: 4rem 2rem;
            border: 1px dashed #d1d5db;
            color: var(--text-sub);
            grid-column: 1 / -1;
        }

        .empty-state svg {
            color: #d1d5db;
            margin-bottom: 1rem;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        /* Sync Overlay Spinner */
        .syncing-indicator {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Modal de Otimização */
        .optimize-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .optimize-modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }

        .optimize-modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--card-border);
            transform: scale(0.95);
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .optimize-modal-backdrop.show .optimize-modal {
            transform: scale(1);
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-dark);
            font-weight: 700;
        }

        .btn-close-modal {
            background: none;
            border: none;
            font-size: 1.75rem;
            color: var(--text-sub);
            cursor: pointer;
            line-height: 1;
            padding: 0.25rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-close-modal:hover {
            color: var(--text-dark);
            background: #f1f5f9;
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-desc {
            color: var(--text-sub);
            font-size: 0.875rem;
            margin: 0;
            line-height: 1.5;
        }

        .batch-selection-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px dashed var(--card-border);
        }

        .btn-selection-action {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-selection-action:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .selection-count {
            margin-left: auto;
            font-weight: 600;
            color: var(--primary);
        }

        .albums-checkbox-list {
            border: 1px solid var(--card-border);
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .album-checkbox-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }

        .album-checkbox-item:last-child {
            border-bottom: none;
        }

        .album-checkbox-item:hover {
            background: #f8fafc;
        }

        /* Checkbox Customization */
        .checkbox-container {
            display: flex;
            align-items: flex-start;
            position: relative;
            padding-left: 2rem;
            cursor: pointer;
            font-size: 0.875rem;
            user-select: none;
            text-align: left;
        }

        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 2px;
            left: 0;
            height: 18px;
            width: 18px;
            background-color: #e2e8f0;
            border-radius: 4px;
            transition: background-color 0.2s, transform 0.1s;
        }

        .checkbox-container:hover input ~ .checkmark {
            background-color: #cbd5e1;
        }

        .checkbox-container input:checked ~ .checkmark {
            background-color: var(--primary);
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }

        .checkbox-container .checkmark:after {
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .album-chk-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .album-chk-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .album-chk-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 0.625rem;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-site {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .badge-model {
            background: #fdf2f8;
            color: #db2777;
        }

        .badge-optimized {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-pending {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
            background: #dc2626;
        }

        .file-count-badge {
            font-size: 0.75rem;
            color: var(--text-sub);
        }

        /* Progress Panel */
        .optimize-progress-panel {
            background: #fafafa;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .progress-bar-wrapper {
            background: #e2e8f0;
            height: 10px;
            border-radius: 9999px;
            overflow: hidden;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, var(--primary) 0%, #10b981 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .progress-percent {
            font-weight: 700;
            color: var(--text-dark);
        }

        .progress-status {
            color: var(--text-sub);
            text-align: right;
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .progress-log {
            border: 1px solid #e2e8f0;
            background: #0f172a;
            color: #38bdf8;
            font-family: monospace;
            font-size: 0.75rem;
            padding: 0.75rem;
            border-radius: 6px;
            height: 100px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.4;
            text-align: left;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--card-border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: #f8fafc;
        }
    
        /* Otimizações Responsivas Mobile para Galaxy S23 e Smartphones */
        @media (max-width: 640px) {
            .photos-container {
                padding: 0.5rem !important;
            }

            .photos-header {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 1rem !important;
            }

            .header-actions {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
            }

            #sync-form {
                flex-direction: column !important;
                width: 100% !important;
            }

            #photos_path {
                width: 100% !important;
            }

            #btn-sync, #btn-open-optimize {
                width: 100% !important;
                justify-content: center !important;
            }

            .stats-overview-bar {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 0.5rem !important;
                padding: 0.75rem !important;
            }

            /* Grade de álbuns mobile: 2 colunas perfeitas estilo feed no S23 */
            .album-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.65rem !important;
            }

            .album-details {
                padding: 0.65rem !important;
            }

            .album-title {
                font-size: 0.85rem !important;
            }

            .album-model {
                font-size: 0.75rem !important;
            }

            /* Modal de otimização adaptado ao mobile */
            .optimize-modal {
                width: 95% !important;
                max-height: 92vh !important;
            }
        }

        /* Model metadata chips on album cards */
        .model-meta-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
            margin: 4px 0 6px;
        }

        .model-chip {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            font-size: 0.6875rem;
            padding: 1px 5px;
            border-radius: 4px;
            line-height: 1.4;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .model-chip.chip-country {
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #a5b4fc;
        }

        .model-chip.chip-bio {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
            cursor: help;
        }

        /* Open album folder button on cards */
        .btn-card-folder {
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.35);
            color: #a5b4fc;
            border-radius: 6px;
            padding: 3px 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-card-folder:hover {
            background: #6366f1;
            color: #ffffff;
            border-color: #6366f1;
            transform: scale(1.08);
        }

        /* Delete album button on cards */
        .btn-card-delete {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            border-radius: 6px;
            padding: 3px 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .btn-card-delete:hover {
            background: #ef4444;
            color: #ffffff;
            border-color: #ef4444;
            transform: scale(1.08);
        }

        /* Custom confirmation modal */
        .custom-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 10, 12, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .custom-modal-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }

        .custom-modal {
            background: #141417;
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: 16px;
            width: 90%;
            max-width: 480px;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            transform: scale(0.95);
            transition: transform 0.25s ease;
            color: #f8fafc;
        }

        .custom-modal-backdrop.show .custom-modal {
            transform: scale(1);
        }

        /* Toast notification */
        .photo-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 0.875rem 1.25rem;
            background: #1e1e24;
            color: #f8fafc;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .photo-toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .photo-toast.success {
            border-color: rgba(16, 185, 129, 0.5);
            background: #064e3b;
        }

        .photo-toast.error {
            border-color: rgba(239, 68, 68, 0.5);
            background: #7f1d1d;
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

                    <a href="{{ route('salary.index') }}" class="nav-item">
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
                    <p>Módulo de visualização inteligente de imagens e ensaios</p>
                </div>
            </header>

            <div class="content-body">
                <div class="photos-container">
                    
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Header Utility Bar -->
                    <div class="photos-header">
                        <div class="header-title-section">
                            <h1>Álbuns Disponíveis</h1>
                            <p>Exibindo {{ $allAlbumsCount ?? count($albums) }} álbuns sincronizados a partir de {{ count($photosPaths) }} {{ count($photosPaths) === 1 ? 'local' : 'locais' }} configurados</p>
                        </div>
                        <div class="header-actions" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                            @if ($lastSync)
                                <div class="sync-info">
                                    Última sincronização
                                    <span>{{ date('d/m/Y H:i:s', strtotime($lastSync)) }}</span>
                                </div>
                            @endif

                            <button type="button" class="btn-action btn-secondary" onclick="openLocationsModal()" style="display: flex; align-items: center; gap: 0.5rem; height: 38px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                    <line x1="12" y1="11" x2="12" y2="17"></line>
                                    <line x1="9" y1="14" x2="15" y2="14"></line>
                                </svg>
                                <span>Gerenciar Locais ({{ count($photosPaths) }})</span>
                            </button>

                            <button type="button" class="btn-action btn-secondary" id="btn-open-optimize">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <span>Gerar Miniaturas</span>
                            </button>

                            <form action="{{ route('photos.sync') }}" method="POST" id="sync-form" style="margin: 0; display: flex; gap: 0.5rem; align-items: center;">
                                @csrf
                                <input type="hidden" name="photos_path" id="photos_path" value="{{ $photosPath }}">
                                <button type="submit" class="btn-action btn-primary" id="btn-sync" style="height: 38px;" title="Sincronizar todos os locais de fotos">
                                    <svg id="sync-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 4 23 10 17 10"></polyline>
                                        <polyline points="1 20 1 14 7 14"></polyline>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                    </svg>
                                    <span>Sincronizar Tudo</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Location Chips Bar -->
                    <div class="location-chips-bar" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.25rem;">
                        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-sub); display: flex; align-items: center; gap: 0.35rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            Locais ativos:
                        </span>
                        @forelse ($photosPaths as $p)
                            @php
                                $exists = is_dir($p);
                            @endphp
                            <div class="location-chip" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.75rem; background: rgba(31, 31, 35, 0.8); border: 1px solid {{ $exists ? 'rgba(99, 102, 241, 0.3)' : 'rgba(239, 68, 68, 0.4)' }}; color: {{ $exists ? '#e2e8f0' : '#f87171' }};" title="{{ $p }} ({{ $exists ? 'Acessível' : 'Diretório não encontrado no disco' }})">
                                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: {{ $exists ? '#10b981' : '#ef4444' }};"></span>
                                <span style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $p }}</span>
                            </div>
                        @empty
                            <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Nenhum local cadastrado</span>
                        @endforelse
                        <button type="button" onclick="openLocationsModal()" style="background: none; border: 1px dashed rgba(139, 92, 246, 0.4); color: #a78bfa; padding: 0.3rem 0.65rem; border-radius: 8px; font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                            + Adicionar / Gerenciar
                        </button>
                    </div>

                                        <!-- Stats Overview Bar -->
                    <div class="stats-overview-bar">
                        <div class="stat-badge-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            Total de Álbuns: <strong style="color: var(--text-light);">{{ $allAlbumsCount ?? count($albums) }}</strong>
                        </div>
                        <div class="stat-badge-chip" style="color: #34d399;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            Miniaturas Geradas: <strong>{{ $optimizedCount ?? count(array_filter($albums, fn($a) => !empty($a['optimized']))) }}</strong>
                        </div>
                        <div class="stat-badge-chip" style="color: #fbbf24;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Miniaturas Pendentes: <strong>{{ $pendingCount ?? count(array_filter($albums, fn($a) => empty($a['optimized']))) }}</strong>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div class="filter-panel">
                        <form method="GET" action="{{ route('photos.index') }}" class="filter-form" id="filter-form">
                            <!-- Search -->
                            <div class="filter-group filter-group-wide" style="position: relative;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <label for="search">Pesquisa Combinada Inteligente</label>
                                    <span style="font-size: 0.7rem; color: #818cf8; font-weight: 500;">Combina múltiplos termos (AND)</span>
                                </div>
                                <div style="position: relative; display: flex; align-items: center;">
                                    <input type="text" id="search" name="search" class="filter-input" style="width: 100%; padding-right: 2.2rem;" placeholder="Combine termos: ex: 'Irene Legs', 'Femjoy Loira', 'Rússia 2026', -palavra..." value="{{ $filters['search'] }}">
                                    @if (!empty($filters['search']))
                                        <a href="{{ route('photos.index', array_filter(array_merge(request()->query(), ['search' => null]))) }}" style="position: absolute; right: 8px; color: #94a3b8; font-size: 1.15rem; text-decoration: none; line-height: 1; padding: 2px 6px; cursor: pointer; border-radius: 4px;" title="Limpar pesquisa">&times;</a>
                                    @endif
                                </div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center; margin-top: 0.35rem; font-size: 0.72rem; color: #94a3b8;">
                                    <span style="color: #a78bfa; font-weight: 600;">Dicas de combinação:</span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);">espaço = E (AND)</span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);"><em>"frase exata"</em></span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);"><em>-excluir</em></span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);"><em>site:watch4beauty</em></span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);"><em>pais:russia</em></span>
                                    <span style="background: rgba(255,255,255,0.05); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.08);"><em>cabelo:loiro</em></span>
                                </div>
                            </div>

                            <!-- Model -->
                            <div class="filter-group">
                                <label for="model">Modelo</label>
                                <select id="model" name="model" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todas as Modelos</option>
                                    @foreach ($models as $m)
                                        <option value="{{ $m }}" {{ in_array($m, $selectedModels ?? []) ? 'selected' : '' }}>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Country / Origem -->
                            <div class="filter-group">
                                <label for="country">País / Origem</label>
                                <select id="country" name="country" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todos os Países</option>
                                    @foreach ($countries as $c)
                                        @php
                                            $cFlag = \App\Http\Controllers\PhotoController::getCountryFlag($c);
                                        @endphp
                                        <option value="{{ $c }}" {{ in_array($c, $selectedCountries ?? []) ? 'selected' : '' }}>
                                            {{ $cFlag }} {{ $c }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Hair Color -->
                            <div class="filter-group">
                                <label for="hair">Cor do Cabelo</label>
                                <select id="hair" name="hair" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todas as Cores</option>
                                    @foreach ($hairColors as $h)
                                        <option value="{{ $h }}" {{ in_array($h, $selectedHairs ?? []) ? 'selected' : '' }}>{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Breast Size -->
                            <div class="filter-group">
                                <label for="breast">Tamanho Seios</label>
                                <select id="breast" name="breast" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todos os Tamanhos</option>
                                    @foreach ($breastSizes as $b)
                                        <option value="{{ $b }}" {{ in_array($b, $selectedBreasts ?? []) ? 'selected' : '' }}>{{ $b }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Ethnicity -->
                            <div class="filter-group">
                                <label for="ethnicity">Etnia</label>
                                <select id="ethnicity" name="ethnicity" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todas as Etnias</option>
                                    @foreach ($ethnicities as $eth)
                                        <option value="{{ $eth }}" {{ in_array($eth, $selectedEthnicities ?? []) ? 'selected' : '' }}>{{ $eth }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Age Range -->
                            <div class="filter-group">
                                <label for="age">Faixa Etária</label>
                                <select id="age" name="age" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todas as Idades</option>
                                    @foreach ($ageRanges as $k => $label)
                                        <option value="{{ $k }}" {{ in_array($k, $selectedAges ?? []) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Sort Options -->
                            <div class="filter-group">
                                <label for="sort">Ordenação</label>
                                <select id="sort" name="sort" class="filter-input" onchange="this.form.submit()">
                                    @foreach ($sortOptions as $sKey => $sLabel)
                                        <option value="{{ $sKey }}" {{ ($filters['sort'] ?? 'date_desc') === $sKey ? 'selected' : '' }}>{{ $sLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Site -->
                            <div class="filter-group">
                                <label for="site">Produtor / Site</label>
                                <select id="site" name="site" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todos os Produtores</option>
                                    @foreach ($sites as $s)
                                        <option value="{{ $s }}" {{ in_array($s, $selectedSites ?? []) ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status de Miniaturas -->
                            <div class="filter-group">
                                <label for="status">Miniaturas</label>
                                <select id="status" name="status" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todos os status</option>
                                    <option value="optimized" {{ ($filters['status'] ?? '') === 'optimized' ? 'selected' : '' }}>⚡ Apenas com Miniaturas</option>
                                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>⏳ Apenas Pendentes</option>
                                </select>
                            </div>

                            <!-- Date (Timeline) -->
                            <div class="filter-group">
                                <label for="date">Período</label>
                                <select id="date" name="date" class="filter-input" onchange="this.form.submit()">
                                    <option value="">Todos os meses</option>
                                    @foreach ($yearsMonths as $ymKey => $ym)
                                        <option value="{{ $ymKey }}" {{ in_array($ymKey, $selectedDates ?? []) ? 'selected' : '' }}>{{ $ym['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if (count($photosPaths) > 1 || count($sources) > 1)
                                <div class="filter-group">
                                    <label for="source">Local / Origem</label>
                                    <select id="source" name="source" class="filter-input" onchange="this.form.submit()">
                                        <option value="">Todos os locais</option>
                                        @php
                                            $allKnownSources = array_unique(array_merge($photosPaths, $sources));
                                        @endphp
                                        @foreach ($allKnownSources as $src)
                                            <option value="{{ $src }}" {{ in_array($src, $selectedSources ?? []) ? 'selected' : '' }}>
                                                {{ basename($src) ? basename($src) . ' (' . $src . ')' : $src }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Actions -->
                            <div class="filter-actions" style="grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.06); margin-top: 0.5rem;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <button type="button" class="btn-action" onclick="rescanModelsFromUI()" id="btn-rescan-models" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.4); color: #c4b5fd; display: inline-flex; align-items: center; gap: 6px; padding: 0.5rem 0.85rem; border-radius: 8px; font-size: 0.8rem;" title="Ler arquivos .txt de todos os álbuns e atualizar informações biográficas">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                                        </svg>
                                        <span id="btn-rescan-text">Varredura de Modelos (.txt)</span>
                                    </button>
                                </div>

                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    @if (!empty($activeFilterChips) && count($activeFilterChips) > 0)
                                        <a href="{{ route('photos.index') }}" class="btn-action btn-secondary" style="padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.85rem;">Limpar Filtros</a>
                                    @endif
                                    <button type="submit" class="btn-action" style="border: none; background: #6366f1; color: white; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;">Aplicar Filtros</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Active Filters Bar -->
                    @if (!empty($activeFilterChips) && count($activeFilterChips) > 0)
                        <div class="active-filters-bar" style="background: rgba(20, 20, 23, 0.85); border: 1px solid #272730; border-radius: 12px; padding: 0.875rem 1.25rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.25);">
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 0.8125rem; font-weight: 700; color: #a5b4fc; display: inline-flex; align-items: center; gap: 0.35rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                    Filtros Combinados Ativos:
                                </span>
                                @foreach ($activeFilterChips as $chip)
                                    <span class="active-chip" style="display: inline-flex; align-items: center; gap: 0.35rem; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.35); color: #e2e8f0; font-size: 0.8125rem; padding: 0.3rem 0.65rem; border-radius: 8px;">
                                        <strong style="color: #c7d2fe; font-weight: 600;">{{ $chip['label'] }}:</strong>
                                        <span>{{ $chip['value'] }}</span>
                                        <a href="{{ $chip['url'] }}" style="color: #f87171; text-decoration: none; font-weight: 700; font-size: 1rem; line-height: 1; margin-left: 2px; padding: 0 3px; border-radius: 4px; transition: all 0.15s;" title="Remover filtro {{ $chip['label'] }}" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='transparent'">&times;</a>
                                    </span>
                                @endforeach
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 0.75rem; color: #94a3b8;">
                                    Exibindo <strong style="color: #f8fafc;">{{ count($albums) }}</strong> {{ count($albums) === 1 ? 'álbum' : 'álbuns' }}
                                </span>
                                <a href="{{ route('photos.index') }}" class="btn-clear-filters" style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #f87171; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); padding: 0.3rem 0.65rem; border-radius: 6px; text-decoration: none; transition: all 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    Limpar Todos
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Album Grid -->
                    <div class="album-grid">
                        @forelse ($albums as $album)
                            <a href="{{ route('photos.album', ['id' => $album['id']]) }}" class="album-card">
                                @if (!empty($album['site']))
                                    <div class="site-badge site-{{ strtolower(str_replace('.', '', $album['site'])) }} {{ !in_array(strtolower($album['site']), ['femjoy', 'metart', 'mplstudios', 'watch4beauty', 'wowgirls', 'ultrafilms', 'local storage']) ? 'site-default' : '' }}">
                                        {{ $album['site'] }}
                                    </div>
                                @endif
                                
                                <div class="album-cover-wrapper">
                                    <img src="{{ route('photos.serve', ['path' => base64_encode($album['path'] . DIRECTORY_SEPARATOR . $album['cover_image']), 'type' => 'thumb']) }}" alt="{{ $album['album_name'] }}" class="album-cover" loading="lazy">
                                    
                                    <!-- Gradient bottom overlay -->
                                    <div class="album-cover-overlay"></div>

                                    <!-- Bottom Image Badges (Megapixels & Count) -->
                                    <div class="album-badges-container">
                                        @if (!empty($album['optimized']))
                                            <span class="badge-pill opt-badge-ready" title="Miniaturas geradas (carregamento rápido)">
                                                ⚡ Miniaturas
                                            </span>
                                        @else
                                            <span class="badge-pill opt-badge-pending" title="Miniaturas pendentes">
                                                ⏳ Pendente
                                            </span>
                                        @endif
                                        @if (!empty($album['source_path']) && (count($photosPaths) > 1 || count($sources) > 1))
                                            <span class="badge-pill" style="background: rgba(139, 92, 246, 0.25); border: 1px solid rgba(139, 92, 246, 0.5); color: #ddd6fe; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Origem: {{ $album['source_path'] }}">
                                                📁 {{ basename($album['source_path']) ?: $album['source_path'] }}
                                            </span>
                                        @endif
                                        <span class="badge-pill mp-badge">
                                            {{ $album['megapixels'] ?? 24 }}MP
                                        </span>
                                        <span class="badge-pill count-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                                <circle cx="12" cy="13" r="4"></circle>
                                            </svg>
                                            {{ $album['file_count'] }}
                                        </span>
                                    </div>

                                    <div class="album-overlay">
                                        <span class="view-album-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            Ver Álbum
                                        </span>
                                    </div>
                                </div>

                                <div class="album-details">
                                    <h3 class="album-title" title="{{ $album['album_name'] }}">
                                        {{ $album['album_name'] }}
                                    </h3>
                                    
                                    <p class="album-model" title="{{ $album['model'] ?? 'Várias' }}">
                                        {{ $album['model'] ?? 'Várias' }}
                                    </p>

                                    @php
                                        $flag = !empty($album['model_country']) ? \App\Http\Controllers\PhotoController::getCountryFlag($album['model_country']) : '';
                                        $chips = [];
                                        if (!empty($album['model_age'])) {
                                            $chips[] = $album['model_age'] . 'a';
                                        }
                                        if (!empty($album['model_hair'])) {
                                            $chips[] = $album['model_hair'];
                                        }
                                        if (!empty($album['model_breast'])) {
                                            $chips[] = $album['model_breast'];
                                        }
                                    @endphp
                                    @if (!empty($album['model_country']) || !empty($chips) || !empty($album['model_bio']))
                                        <div class="model-meta-chips">
                                            @if (!empty($album['model_country']) && strtolower($album['model_country']) !== 'unknown')
                                                <span class="model-chip chip-country" title="Origem: {{ $album['model_country'] }}">
                                                    <span>{{ $flag }}</span>
                                                    <span>{{ $album['model_country'] }}</span>
                                                </span>
                                            @endif
                                            @foreach ($chips as $chip)
                                                <span class="model-chip">
                                                    {{ $chip }}
                                                </span>
                                            @endforeach
                                            @if (!empty($album['model_bio']))
                                                <span class="model-chip chip-bio" title="Biografia disponível: {{ Str::limit($album['model_bio'], 100) }}">
                                                    Bio
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    @php
                                        // Deterministic star rating format 9.X based on album ID
                                        $rating = 9.0 + (hexdec(substr($album['id'], 0, 2)) % 10) / 10;
                                        $rating = number_format($rating, 1);
                                        
                                        // Deterministic views count format X.YK based on album ID
                                        $viewsVal = (hexdec(substr($album['id'], 2, 4)) % 90) + 10;
                                        $views = number_format($viewsVal / 10, 1) . 'K';
                                    @endphp

                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; margin-bottom: 0.25rem; gap: 0.5rem;">
                                        <div style="display: flex; gap: 4px; align-items: center;">
                                            @if (!empty($album['optimized']))
                                                <button type="button" class="btn-card-optimize is-done" onclick="event.preventDefault(); event.stopPropagation(); optimizeSingleAlbum('{{ $album['id'] }}', '{{ addslashes($album['album_name']) }}')" title="Miniaturas já geradas. Clique para atualizar/re-gerar">
                                                    ✓ Miniaturas
                                                </button>
                                            @else
                                                <button type="button" class="btn-card-optimize" onclick="event.preventDefault(); event.stopPropagation(); optimizeSingleAlbum('{{ $album['id'] }}', '{{ addslashes($album['album_name']) }}')" title="Gerar miniaturas deste álbum para renderizar as fotos mais rápido">
                                                    ⚡ Miniaturas
                                                </button>
                                            @endif

                                            <button type="button" class="btn-card-folder" onclick="event.preventDefault(); event.stopPropagation(); openAlbumFolder('{{ $album['id'] }}', this)" title="Abrir pasta no Explorador de Arquivos do PC: {{ $album['path'] }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                                </svg>
                                            </button>

                                            <button type="button" class="btn-card-delete" onclick="event.preventDefault(); event.stopPropagation(); openDeleteAlbumModal('{{ $album['id'] }}', '{{ addslashes($album['album_name']) }}', this)" title="Excluir permanentemente do PC">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="album-date">{{ date('M d, Y', strtotime($album['date'])) }}</span>
                                    </div>
                                    <div class="album-stats-row" style="margin-top: 0.25rem;">
                                        <span></span>
                                        <div class="stats-right">
                                            <span class="stat-item rating-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                                </svg>
                                                {{ $rating }}
                                            </span>
                                            <span class="stat-item views-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                {{ $views }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <h3>Nenhum álbum encontrado</h3>
                                <p>Tente ajustar os filtros ou clique em Sincronizar para atualizar a lista.</p>
                            </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Gerenciamento de Locais de Fotos -->
    <div class="optimize-modal-backdrop" id="locations-modal">
        <div class="optimize-modal" style="background: #141417; border: 1px solid #1f1f23; color: #f8fafc; width: 90%; max-width: 650px; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; display: flex; flex-direction: column;">
            <div class="modal-header" style="border-bottom: 1px solid #1f1f23; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; padding: 0.5rem; border-radius: 10px; display: flex; align-items: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            <line x1="12" y1="11" x2="12" y2="17"></line>
                            <line x1="9" y1="14" x2="15" y2="14"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1.2rem; font-weight: 700; margin: 0; color: #fff;">Locais de Fotos & Torrents</h2>
                        <span style="font-size: 0.8rem; color: #94a3b8;">Gerencie as pastas de onde os álbuns são lidos e sincronizados</span>
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeLocationsModal()" style="color: #94a3b8; background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; max-height: 70vh; overflow-y: auto;">
                <!-- Formulário para adicionar novo local -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid #27272a; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #e2e8f0; margin-bottom: 0.5rem;">
                        + Adicionar Novo Local de Fotos:
                    </label>
                    <form action="{{ route('photos.locations.add') }}" method="POST" id="form-add-location" style="display: flex; gap: 0.5rem; margin: 0;">
                        @csrf
                        <input type="text" name="path" id="input-new-location" required placeholder="Ex: D:\Fotos ou E:\Torrents\Fotos" class="filter-input" style="flex: 1; height: 40px; margin: 0; background: #0a0a0c; border: 1px solid #27272a; color: #fff; padding: 0.5rem 0.75rem; border-radius: 8px; font-size: 0.85rem;">
                        <button type="submit" class="btn-action btn-primary" id="btn-add-location" style="height: 40px; padding: 0 1.25rem; white-space: nowrap;">
                            Adicionar
                        </button>
                    </form>
                </div>

                <!-- Lista de locais cadastrados -->
                <div style="font-size: 0.85rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                    <span>Locais Cadastrados ({{ count($photosPaths) }}):</span>
                    <span style="color: #94a3b8; font-weight: normal; font-size: 0.75rem;">Status e álbuns</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    @forelse ($photosPaths as $index => $locPath)
                        @php
                            $exists = is_dir($locPath);
                            $albumCount = count(array_filter($albums, fn($a) => isset($a['source_path']) && strcasecmp(str_replace('/', '\\', $a['source_path']), str_replace('/', '\\', realpath($locPath) ?: $locPath)) === 0));
                        @endphp
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid #27272a; border-radius: 10px; padding: 0.85rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <div style="min-width: 0; flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: {{ $exists ? '#10b981' : '#ef4444' }}; flex-shrink: 0;" title="{{ $exists ? 'Pasta acessível' : 'Pasta não encontrada' }}"></span>
                                    <span style="font-size: 0.875rem; font-weight: 600; color: #fff; word-break: break-all;">{{ $locPath }}</span>
                                </div>
                                <div style="display: flex; gap: 0.75rem; font-size: 0.75rem; color: #94a3b8; margin-left: 1.1rem;">
                                    <span>Status: <strong style="color: {{ $exists ? '#34d399' : '#f87171' }};">{{ $exists ? 'Acessível' : 'Não encontrado' }}</strong></span>
                                    <span>•</span>
                                    <span>Álbuns: <strong style="color: #c084fc;">{{ $albumCount }}</strong></span>
                                </div>
                            </div>
                            <form action="{{ route('photos.locations.remove') }}" method="POST" onsubmit="return confirm('Deseja realmente remover este local de sincronização?');" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="path" value="{{ $locPath }}">
                                <button type="submit" class="btn-action" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 6px; cursor: pointer;" title="Remover local">
                                    Remover
                                </button>
                            </form>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 2rem 1rem; color: #94a3b8; font-size: 0.85rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px dashed #27272a;">
                            Nenhum diretório cadastrado no momento. Adicione um caminho acima para mapear suas fotos.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer" style="background: rgba(0,0,0,0.2); border-top: 1px solid #1f1f23; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.75rem; color: #94a3b8;">Dica: você pode adicionar múltiplos discos locais, externos ou pastas de rede.</span>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn-action btn-secondary" onclick="closeLocationsModal()">Fechar</button>
                    <form action="{{ route('photos.sync') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn-action btn-primary" style="display: flex; align-items: center; gap: 0.4rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                            Sincronizar Todos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Otimização de Miniaturas -->
    <div class="optimize-modal-backdrop" id="optimize-modal">
        <div class="optimize-modal">
            <div class="modal-header">
                <h2>Pré-gerar Miniaturas dos Álbuns</h2>
                <button type="button" class="btn-close-modal" id="btn-close-optimize">&times;</button>
            </div>
            <div class="modal-body">
                <p class="modal-desc">
                    Selecione as pastas de álbuns para pré-gerar as miniaturas no SSD local. Isso faz com que as listas de fotos e capas renderizem instantaneamente ao navegar.
                </p>
                
                <!-- Controles de seleção em lote -->
                <div class="batch-selection-bar">
                    <button type="button" class="btn-selection-action" id="btn-select-all">Selecionar Todos</button>
                    <button type="button" class="btn-selection-action" id="btn-select-pending">Selecionar Pendentes</button>
                    <button type="button" class="btn-selection-action" id="btn-deselect-all">Limpar Seleção</button>
                    <span class="selection-count" id="selection-summary">0 álbuns selecionados</span>
                </div>

                <!-- Busca interna de álbuns no modal -->
                <div class="modal-search-wrapper">
                    <input type="text" id="modal-search-input" class="filter-input" placeholder="Filtrar álbuns nesta lista..." style="width: 100%; box-sizing: border-box;">
                </div>

                <!-- Lista de álbuns com checkbox -->
                <div class="albums-checkbox-list" id="modal-albums-list">
                    @foreach ($albums as $album)
                        <div class="album-checkbox-item" data-name="{{ strtolower($album['album_name'] . ' ' . $album['folder_name'] . ' ' . (isset($album['model']) ? $album['model'] : '') . ' ' . $album['site']) }}">
                            <label class="checkbox-container">
                                <input type="checkbox" class="album-chk" value="{{ $album['id'] }}" data-album-name="{{ $album['album_name'] }}" data-file-count="{{ $album['file_count'] }}" data-optimized="{{ !empty($album['optimized']) && $album['optimized'] ? 'true' : 'false' }}">
                                <span class="checkmark"></span>
                                <div class="album-chk-details">
                                    <span class="album-chk-name">{{ $album['album_name'] }}</span>
                                    <span class="album-chk-meta">
                                        <span class="badge badge-site">{{ $album['site'] }}</span>
                                        @if(!empty($album['model']))
                                            <span class="badge badge-model">{{ $album['model'] }}</span>
                                        @endif
                                        <span class="file-count-badge">{{ $album['file_count'] }} fotos</span>
                                        <span class="badge-status-optimized" style="margin-left: 0.25rem;">
                                            @if(!empty($album['optimized']) && $album['optimized'])
                                                <span class="badge badge-optimized">✓ Miniaturas Prontas</span>
                                            @else
                                                <span class="badge badge-pending">Pendente</span>
                                            @endif
                                        </span>
                                    </span>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                <!-- Painel de progresso -->
                <div class="optimize-progress-panel" id="optimize-progress-panel" style="display: none;">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill" id="progress-bar-fill" style="width: 0%;"></div>
                    </div>
                    <div class="progress-details" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                        <span class="progress-percent" id="progress-percent">0%</span>
                        <span class="progress-remaining" id="progress-remaining" style="font-weight: 600; color: var(--text-dark);">Faltam 0 pastas</span>
                        <span class="progress-status" id="progress-status">Preparando...</span>
                    </div>
                    <div class="progress-log" id="progress-log"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" id="btn-cancel-optimize">Cancelar</button>
                <button type="button" class="btn-action btn-danger" id="btn-stop-optimize" style="display: none;">Parar</button>
                <button type="button" class="btn-action btn-primary" id="btn-start-optimize" disabled>Gerar Miniaturas Selecionadas</button>
            </div>
        </div>
    </div>

    <script>
        function openLocationsModal() {
            const modal = document.getElementById('locations-modal');
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    const input = document.getElementById('input-new-location');
                    if (input) input.focus();
                }, 100);
            }
        }

        function closeLocationsModal() {
            const modal = document.getElementById('locations-modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Close locations modal when clicking backdrop
        const locModalEl = document.getElementById('locations-modal');
        if (locModalEl) {
            locModalEl.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLocationsModal();
                }
            });
        }

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLocationsModal();
            }
        });

        // Add loading state when submitting new location
        const formAddLoc = document.getElementById('form-add-location');
        if (formAddLoc) {
            formAddLoc.addEventListener('submit', function() {
                const btn = document.getElementById('btn-add-location');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Adicionando...';
                    btn.style.opacity = '0.7';
                }
            });
        }

        // Intercept sync form to show animation
        document.getElementById('sync-form').addEventListener('submit', function(e) {
            const btn = document.getElementById('btn-sync');
            const icon = document.getElementById('sync-icon');
            const text = btn.querySelector('span');
            
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
            icon.classList.add('syncing-indicator');
            text.textContent = 'Sincronizando...';
        });

                // Quick optimize single album directly from card
        function optimizeSingleAlbum(albumId, albumName) {
            // Open modal, uncheck all, check this specific album, and start
            const modal = document.getElementById('optimize-modal');
            const searchInput = document.getElementById('modal-search-input');
            const checkboxes = document.querySelectorAll('.album-chk');
            
            checkboxes.forEach(chk => {
                chk.checked = (chk.value === albumId);
            });
            
            updateSelectionSummary();
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Auto start if user clicked optimize button
            setTimeout(() => {
                const startBtn = document.getElementById('btn-start-optimize');
                if (startBtn && !startBtn.disabled) {
                    startBtn.click();
                }
            }, 300);
        }

        // Optimize Modal Logic
        (function() {
            const btnOpenOptimize = document.getElementById('btn-open-optimize');
            const btnCloseOptimize = document.getElementById('btn-close-optimize');
            const btnCancelOptimize = document.getElementById('btn-cancel-optimize');
            const btnStopOptimize = document.getElementById('btn-stop-optimize');
            const btnStartOptimize = document.getElementById('btn-start-optimize');
            const btnSelectAll = document.getElementById('btn-select-all');
            const btnSelectPending = document.getElementById('btn-select-pending');
            const btnDeselectAll = document.getElementById('btn-deselect-all');
            const modal = document.getElementById('optimize-modal');
            const modalSearchInput = document.getElementById('modal-search-input');
            const chks = document.querySelectorAll('.album-chk');
            const selectionSummary = document.getElementById('selection-summary');
            const progressPanel = document.getElementById('optimize-progress-panel');
            const progressBarFill = document.getElementById('progress-bar-fill');
            const progressPercent = document.getElementById('progress-percent');
            const progressRemaining = document.getElementById('progress-remaining');
            const progressStatus = document.getElementById('progress-status');
            const progressLog = document.getElementById('progress-log');
            
            let isOptimizing = false;
            let shouldStop = false;

            // Open modal
            btnOpenOptimize.addEventListener('click', () => {
                modal.classList.add('show');
            });
            
            // Close modal
            function hideModal() {
                if (isOptimizing) return;
                modal.classList.remove('show');
                resetProgress();
            }
            
            btnCloseOptimize.addEventListener('click', hideModal);
            btnCancelOptimize.addEventListener('click', hideModal);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    hideModal();
                }
            });
            
            // Update selection count
            function updateCount() {
                const checkedCount = document.querySelectorAll('.album-chk:checked').length;
                selectionSummary.textContent = `${checkedCount} álbuns selecionados`;
                btnStartOptimize.disabled = (checkedCount === 0);
            }
            
            chks.forEach(chk => {
                chk.addEventListener('change', updateCount);
            });
            
            // Select All (only visible ones)
            btnSelectAll.addEventListener('click', () => {
                chks.forEach(chk => {
                    const item = chk.closest('.album-checkbox-item');
                    if (item.style.display !== 'none') {
                        chk.checked = true;
                    }
                });
                updateCount();
            });

            // Select Pending (only visible ones)
            btnSelectPending.addEventListener('click', () => {
                chks.forEach(chk => {
                    const item = chk.closest('.album-checkbox-item');
                    if (item.style.display !== 'none') {
                        const isOptimized = chk.getAttribute('data-optimized') === 'true';
                        chk.checked = !isOptimized;
                    }
                });
                updateCount();
            });
            
            // Deselect All
            btnDeselectAll.addEventListener('click', () => {
                chks.forEach(chk => {
                    chk.checked = false;
                });
                updateCount();
            });
            
            // Modal Search Filter
            modalSearchInput.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase().trim();
                const items = document.querySelectorAll('.album-checkbox-item');
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    if (name.includes(q)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // Stop Button Logic
            btnStopOptimize.addEventListener('click', () => {
                shouldStop = true;
                btnStopOptimize.disabled = true;
                btnStopOptimize.textContent = 'Parando...';
                progressLog.textContent += `[${new Date().toLocaleTimeString()}] 🛑 Solicitada a interrupção pelo usuário. Finalizando álbum atual...\n`;
                progressLog.scrollTop = progressLog.scrollHeight;
            });

            function resetProgress() {
                progressPanel.style.display = 'none';
                progressBarFill.style.width = '0%';
                progressPercent.textContent = '0%';
                progressRemaining.textContent = 'Faltam 0 pastas';
                progressStatus.textContent = 'Preparando...';
                progressLog.textContent = '';
                
                // Re-enable controls
                btnStartOptimize.style.display = 'flex';
                btnStartOptimize.disabled = false;
                btnStopOptimize.style.display = 'none';
                btnStopOptimize.disabled = false;
                btnStopOptimize.textContent = 'Parar';
                
                btnCancelOptimize.style.display = 'flex';
                btnCancelOptimize.textContent = 'Cancelar';
                btnCancelOptimize.disabled = false;
                btnCloseOptimize.style.display = 'block';
                btnCloseOptimize.disabled = false;
                
                btnSelectAll.disabled = false;
                btnSelectPending.disabled = false;
                btnDeselectAll.disabled = false;
                modalSearchInput.disabled = false;
                modalSearchInput.value = '';
                
                chks.forEach(chk => {
                    chk.disabled = false;
                    chk.checked = false;
                });
                
                // Reset filtering
                const items = document.querySelectorAll('.album-checkbox-item');
                items.forEach(item => item.style.display = 'block');
                
                updateCount();
            }
            
            // Start Optimization Process
            btnStartOptimize.addEventListener('click', async () => {
                const selected = Array.from(document.querySelectorAll('.album-chk:checked')).map(chk => ({
                    id: chk.value,
                    name: chk.getAttribute('data-album-name'),
                    files: parseInt(chk.getAttribute('data-file-count'))
                }));
                
                if (selected.length === 0) return;
                
                shouldStop = false;
                isOptimizing = true;
                
                btnStartOptimize.style.display = 'none';
                btnStopOptimize.style.display = 'flex';
                btnCancelOptimize.style.display = 'none';
                btnCloseOptimize.style.display = 'none';
                
                btnSelectAll.disabled = true;
                btnSelectPending.disabled = true;
                btnDeselectAll.disabled = true;
                modalSearchInput.disabled = true;
                chks.forEach(chk => chk.disabled = true);
                
                progressPanel.style.display = 'flex';
                progressLog.textContent = `[${new Date().toLocaleTimeString()}] Iniciando pré-geração de miniaturas para ${selected.length} álbuns...\n`;
                
                let processed = 0;
                const total = selected.length;
                
                for (let i = 0; i < total; i++) {
                    if (shouldStop) {
                        progressLog.textContent += `[${new Date().toLocaleTimeString()}] 🛑 Geração de miniaturas interrompida pelo usuário.\n`;
                        break;
                    }
                    
                    const album = selected[i];
                    
                    // Update folders remaining text
                    const remaining = total - i;
                    progressRemaining.textContent = `Faltam ${remaining} ${remaining === 1 ? 'pasta' : 'pastas'}`;
                    
                    progressStatus.textContent = `Processando: ${album.name} (${album.files} fotos)...`;
                    progressLog.textContent += `[${new Date().toLocaleTimeString()}] [${i + 1}/${total}] Gerando miniaturas de "${album.name}" (${album.files} fotos)...\n`;
                    progressLog.scrollTop = progressLog.scrollHeight;
                    
                    try {
                        const response = await fetch("{{ route('photos.pregenerate') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ id: album.id })
                        });
                        
                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';
                        let driverShown = false;
                        
                        while (true) {
                            if (shouldStop) {
                                break;
                            }
                            
                            const { value, done } = await reader.read();
                            if (done) break;
                            
                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split("\n");
                            buffer = lines.pop(); // Keep last partial line
                            
                            for (const line of lines) {
                                if (line.trim() === '') continue;
                                try {
                                    const chunk = JSON.parse(line);
                                    if (chunk.success) {
                                        if (!driverShown && chunk.driver) {
                                            progressLog.textContent += `  -> Motor ativo: ${chunk.driver}\n`;
                                            progressLog.scrollTop = progressLog.scrollHeight;
                                            driverShown = true;
                                        }
                                        progressStatus.textContent = `Processando: ${album.name} (${chunk.current}/${chunk.total})...`;
                                        progressLog.textContent += `  └─ [${chunk.current}/${chunk.total}] Miniatura pronta: ${chunk.photo}\n`;
                                        progressLog.scrollTop = progressLog.scrollHeight;
                                    }
                                } catch (e) {
                                    // Ignore partial JSON parsing errors
                                }
                            }
                        }
                        
                        if (!shouldStop) {
                            progressLog.textContent += `[${new Date().toLocaleTimeString()}] ✓ Álbum "${album.name}" concluído!\n`;
                            
                            // Update UI to optimized state dynamically
                            const chkInput = document.querySelector(`.album-chk[value="${album.id}"]`);
                            if (chkInput) {
                                chkInput.setAttribute('data-optimized', 'true');
                                const badgeContainer = chkInput.closest('.album-checkbox-item').querySelector('.badge-status-optimized');
                                if (badgeContainer) {
                                    badgeContainer.innerHTML = '<span class="badge badge-optimized">✓ Miniaturas Prontas</span>';
                                }
                            }
                        }
                    } catch (err) {
                        progressLog.textContent += `[${new Date().toLocaleTimeString()}] ✗ Erro de rede/servidor: ${err.message}\n`;
                    }
                    
                    if (!shouldStop) {
                        processed++;
                    }
                    const pct = Math.round((processed / total) * 100);
                    progressBarFill.style.width = `${pct}%`;
                    progressPercent.textContent = `${pct}%`;
                    progressLog.scrollTop = progressLog.scrollHeight;
                }
                
                progressRemaining.textContent = `Faltam ${total - processed} ${total - processed === 1 ? 'pasta' : 'pastas'}`;
                progressStatus.textContent = shouldStop ? 'Interrompido' : 'Concluído!';
                progressLog.textContent += `[${new Date().toLocaleTimeString()}] === FIM DO PROCESSO ===\n`;
                progressLog.scrollTop = progressLog.scrollHeight;
                
                isOptimizing = false;
                btnStartOptimize.style.display = 'flex';
                btnStopOptimize.style.display = 'none';
                btnStopOptimize.disabled = false;
                btnStopOptimize.textContent = 'Parar';
                
                btnCancelOptimize.style.display = 'flex';
                btnCancelOptimize.textContent = 'Fechar';
                btnCancelOptimize.disabled = false;
                btnCloseOptimize.style.display = 'block';
                btnCloseOptimize.disabled = false;
            });
        })();

        // Toast helper
        function showToast(message, type = 'success') {
            const toast = document.getElementById('photo-toast');
            const toastMsg = document.getElementById('photo-toast-message');
            if (!toast || !toastMsg) return;
            toastMsg.textContent = message;
            toast.className = `photo-toast ${type} show`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Open album folder in Windows Explorer
        async function openAlbumFolder(albumId, btn) {
            if (!albumId) return;
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                const response = await fetch(`/photos/album/${albumId}/open-folder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Pasta aberta no Explorador de Arquivos!', 'success');
                } else {
                    showToast(data.message || 'Não foi possível abrir a pasta.', 'error');
                }
            } catch (err) {
                showToast('Erro ao comunicar com o servidor: ' + err.message, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            }
        }

        // Delete Album Logic
        let albumIdToDelete = null;
        let cardElementToDelete = null;

        function openDeleteAlbumModal(id, name, btn) {
            albumIdToDelete = id;
            cardElementToDelete = btn ? btn.closest('.album-card') : null;
            const nameEl = document.getElementById('delete-modal-album-name');
            if (nameEl) nameEl.textContent = name;
            const modal = document.getElementById('delete-album-modal');
            if (modal) modal.classList.add('show');
        }

        function closeDeleteAlbumModal() {
            const modal = document.getElementById('delete-album-modal');
            if (modal) modal.classList.remove('show');
            albumIdToDelete = null;
            cardElementToDelete = null;
        }

        async function executeDeleteAlbum() {
            if (!albumIdToDelete) return;
            const btn = document.getElementById('confirm-delete-album-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Excluindo arquivos...';
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                const response = await fetch(`/photos/album/${albumIdToDelete}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    closeDeleteAlbumModal();
                    if (cardElementToDelete) {
                        cardElementToDelete.style.transition = 'all 0.4s ease';
                        cardElementToDelete.style.opacity = '0';
                        cardElementToDelete.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            cardElementToDelete.remove();
                        }, 400);
                    }
                    showToast(data.message || 'Álbum excluído permanentemente do computador!', 'success');
                } else {
                    alert(data.message || 'Erro ao excluir o álbum do computador.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na comunicação com o servidor ao excluir o álbum.');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Sim, Excluir do Disco';
                }
            }
        }

        // Rescan Models (.txt)
        async function rescanModelsFromUI() {
            const btn = document.getElementById('btn-rescan-models');
            const text = document.getElementById('btn-rescan-text');
            if (btn) btn.disabled = true;
            if (text) text.textContent = 'Varrendo .txt dos álbuns...';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                const response = await fetch("{{ route('photos.rescan-models') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Varredura concluída com sucesso!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    alert(data.message || 'Erro durante a varredura.');
                }
            } catch (err) {
                console.error(err);
                alert('Erro na requisição de varredura de modelos.');
            } finally {
                if (btn) btn.disabled = false;
                if (text) text.textContent = 'Varredura de Modelos (.txt)';
            }
        }
    </script>

    <!-- Modal de Confirmação para Remoção Permanente do Álbum do PC -->
    <div class="custom-modal-backdrop" id="delete-album-modal">
        <div class="custom-modal">
            <div style="display: flex; align-items: center; gap: 0.75rem; color: #ef4444;">
                <div style="background: rgba(239, 68, 68, 0.15); border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff;">Excluir Permanentemente do PC?</h3>
                    <span style="font-size: 0.8rem; color: #94a3b8;">Remoção definitiva de arquivos do disco</span>
                </div>
            </div>

            <div style="font-size: 0.875rem; color: #cbd5e1; line-height: 1.5;">
                <p style="margin: 0 0 0.5rem 0;">Tem certeza de que deseja excluir permanentemente o álbum <strong id="delete-modal-album-name" style="color: #f87171;"></strong>?</p>
                <p style="margin: 0; color: #94a3b8; font-size: 0.8rem;">
                    ⚠️ A pasta correspondente e todas as imagens/fotos serão <strong>deletadas do armazenamento do computador</strong> e removidas do catálogo. Esta operação não pode ser desfeita.
                </p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 0.5rem;">
                <button type="button" class="btn-action btn-secondary" onclick="closeDeleteAlbumModal()" style="padding: 0.5rem 1rem; border-radius: 8px;">Cancelar</button>
                <button type="button" class="btn-action" id="confirm-delete-album-btn" onclick="executeDeleteAlbum()" style="background: #ef4444; color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer;">Sim, Excluir do Disco</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="photo-toast" class="photo-toast">
        <span id="photo-toast-message"></span>
    </div>
</body>
</html>
