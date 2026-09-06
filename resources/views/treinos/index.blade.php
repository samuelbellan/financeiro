<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treinos & Exercícios Físicos | Financeiro</title>
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
        }

        .fitness-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .fitness-kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .kpi-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.2;
            margin-top: 0.2rem;
        }

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

        .session-item:hover {
            background: #f3f4f6;
        }

        .gear-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.75rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
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

        .empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--text-muted);
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
                        <span>Treinos & Exercícios</span>
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
            <header class="content-header" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
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
                        <p>Acompanhamento de Musculação, Corrida, Alongamento e Evolução Corporal</p>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- KPI CARDS -->
                <div class="fitness-kpi-grid">
                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Sessões nesta Semana</div>
                            <div class="kpi-value">{{ $sessoesEstaSemana }} <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">treinos</span></div>
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
                            <div class="kpi-value">{{ number_format($kmCorridaSemana, 1, ',', '.') }} <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">km</span></div>
                        </div>
                    </div>

                    <div class="fitness-kpi-card">
                        <div class="kpi-icon-box" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 12h8"></path>
                                <path d="M12 8v8"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="kpi-label">Catálogo de Exercícios</div>
                            <div class="kpi-value">{{ $exercisesStats['total'] }} <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">ativos</span></div>
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
                            <div class="kpi-label">Último Peso / IMC</div>
                            <div class="kpi-value">
                                @if($latestMetric)
                                    {{ number_format($latestMetric->peso_kg, 1, ',', '.') }} kg
                                    <span style="font-size: 0.85rem; font-weight: 600; color: #10b981;">(IMC {{ $latestMetric->imc ?? '--' }})</span>
                                @else
                                    <span style="font-size: 1rem; color: var(--text-muted);">Não registrado</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTIONS GRID -->
                <div class="fitness-sections-grid">
                    <!-- Fichas & Musculação -->
                    <div class="fitness-box">
                        <div class="fitness-box-header">
                            <div class="fitness-box-title">
                                <span class="badge-modality badge-musculacao">Musculação</span>
                                Fichas de Treino
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $workoutPlans->count() }} fichas</span>
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
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                Nenhuma ficha cadastrada ainda.
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
                            <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $activeGears->count() }} tênis</span>
                        </div>

                        @forelse($activeGears as $gear)
                            <div style="padding: 0.85rem; border: 1px solid #f3f4f6; border-radius: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-weight: 700; color: var(--text-dark);">{{ $gear->marca }} {{ $gear->modelo }}</span>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: {{ $gear->alerta_desgaste === 'alerta' ? '#f59e0b' : ($gear->alerta_desgaste === 'esgotado' ? '#ef4444' : '#10b981') }};">
                                        {{ number_format($gear->quilometragem_total, 1, ',', '.') }} km
                                        @if($gear->vida_util_limite_km)
                                            / {{ number_format($gear->vida_util_limite_km, 0) }} km
                                        @endif
                                    </span>
                                </div>
                                @if($gear->percentual_uso)
                                    <div class="gear-progress-bar">
                                        <div class="gear-progress-fill" style="width: {{ min(100, $gear->percentual_uso) }}%; background: {{ $gear->alerta_desgaste === 'esgotado' ? '#ef4444' : ($gear->alerta_desgaste === 'alerta' ? '#f59e0b' : '#10b981') }};"></div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state">
                                Nenhum tênis ou equipamento cadastrado.
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
                            <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $personalRecords->count() }} marcas</span>
                        </div>

                        @forelse($personalRecords as $pr)
                            <div class="session-item">
                                <div>
                                    <div style="font-weight: 700; color: var(--text-dark);">
                                        {{ $pr->exercise ? $pr->exercise->nome : ucfirst(str_replace('_', ' ', $pr->tipo_recorde)) }}
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        {{ \Carbon\Carbon::parse($pr->data_recorde)->format('d/m/Y') }}
                                    </div>
                                </div>
                                <div style="font-weight: 800; font-size: 1.1rem; color: #b45309; background: #fef3c7; padding: 0.25rem 0.6rem; border-radius: 0.5rem;">
                                    {{ $pr->valor_formatado }}
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                Nenhum recorde pessoal registrado ainda.
                            </div>
                        @endforelse
                    </div>

                    <!-- Últimas Sessões Realizadas -->
                    <div class="fitness-box" style="grid-column: 1 / -1;">
                        <div class="fitness-box-header">
                            <div class="fitness-box-title">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                Histórico Recente de Treinos
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">Últimas 10 sessões</span>
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

                                <div>
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
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                Nenhuma sessão de treino realizada ainda. Seus treinos de musculação, corridas e alongamentos aparecerão listados aqui.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
