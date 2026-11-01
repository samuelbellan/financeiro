<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Convites | Administrador</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <style>
        .convites-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-inline-convite {
            display: grid;
            grid-template-columns: 2fr 1.5fr auto;
            gap: 1rem;
            align-items: flex-end;
        }
        @media (max-width: 640px) {
            .form-inline-convite {
                grid-template-columns: 1fr;
            }
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 0.6rem;
            color: var(--text-main);
            font: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
        }
        .btn-add {
            padding: 0.75rem 1.5rem;
            background: #6366f1;
            color: #fff;
            border: 0;
            border-radius: 0.6rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            height: 44px;
        }
        .btn-add:hover {
            background: #4f46e5;
        }
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }
        .table th, .table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        .table th {
            color: var(--text-muted);
            font-weight: 600;
            background: rgba(0, 0, 0, 0.15);
        }
        .table tr:last-child td {
            border-bottom: 0;
        }
        .btn-remove {
            padding: 0.4rem 0.8rem;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-remove:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 0.85rem 1.2rem;
            border-radius: 0.6rem;
            margin-bottom: 1.5rem;
        }
        .alert-info {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 0.85rem 1.2rem;
            border-radius: 0.6rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Financeiro</h2>
                <button type="button" class="sidebar-toggle-btn js-toggle-sidebar" title="Ocultar barra lateral">
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
                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>

                    <a href="{{ route('admin.convites') }}" class="nav-item active" style="color: #60a5fa;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M19 8v6"></path>
                            <path d="M22 11h-6"></path>
                        </svg>
                        <span>Convites Google</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-item" style="width: 100%; border: 0; background: transparent; cursor: pointer; color: #ef4444;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Sair da Conta</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle-btn js-toggle-sidebar" title="Alternar barra lateral">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <h1>Convites & Usuários Google</h1>
                </div>
            </header>

            <div class="content-body">
                <div class="convites-container">
                    @if(session('success'))
                        <div class="alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('info'))
                        <div class="alert-info">{{ session('info') }}</div>
                    @endif

                    <div class="form-card">
                        <h3 style="margin-bottom: 0.5rem;">Convidar Novo Usuário</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
                            Cadastre o endereço do Google (Gmail) da pessoa que você deseja autorizar. Ao clicar em "Continuar com o Google" na tela de login, o usuário terá acesso imediato ao sistema com seus dados próprios.
                        </p>

                        <form action="{{ route('admin.convites.store') }}" method="POST" class="form-inline-convite">
                            @csrf
                            <div class="form-group">
                                <label for="email">E-mail do Google (Gmail) *</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="amigo@gmail.com" required>
                            </div>
                            <div class="form-group">
                                <label for="nome">Nome / Identificação (opcional)</label>
                                <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex.: Lucas (Amigo)">
                            </div>
                            <button type="submit" class="btn-add">+ Convidar</button>
                        </form>
                    </div>

                    <div class="table-card">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>E-mail Autorizado</th>
                                    <th>Nome / Observação</th>
                                    <th>Data de Cadastro</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($emails as $item)
                                    <tr>
                                        <td>
                                            <b>{{ $item->email }}</b>
                                            @if($item->email === strtolower(auth()->user()->email))
                                                <span style="font-size: 0.75rem; background: #3b82f6; color: #fff; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">Você (Admin)</span>
                                            @endif
                                        </td>
                                        <td style="color: var(--text-muted);">{{ $item->nome ?: '—' }}</td>
                                        <td style="color: var(--text-muted);">{{ $item->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td style="text-align: right;">
                                            @if($item->email !== strtolower(auth()->user()->email))
                                                <form action="{{ route('admin.convites.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover o acesso deste e-mail?');" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-remove">Revogar Acesso</button>
                                                </form>
                                            @else
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">Admin principal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                            Nenhum e-mail convidado ainda. Cadastre o primeiro acima!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="form-card" style="margin-top: 2rem;">
                        <h3 style="margin-bottom: 0.5rem;">Gestores</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
                            Gestores podem convidar e remover usuários e criar e gerenciar salas de estudo.
                            @if(auth()->user()->is_owner)
                                Só você, dono do sistema, delega ou retira essa função.
                            @else
                                Só o dono do sistema delega ou retira essa função.
                            @endif
                        </p>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Função</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td><b>{{ $user->name }}</b><br><span style="color: var(--text-muted); font-size: 0.8rem;">{{ $user->email }}</span></td>
                                        <td style="color: var(--text-muted);">{{ $user->is_owner ? 'Dono' : ($user->is_admin ? 'Gestor' : 'Usuário') }}</td>
                                        <td style="text-align: right;">
                                            @if(auth()->user()->is_owner && !$user->is_owner)
                                                <form action="{{ route('admin.usuarios.gestor', $user->id) }}" method="POST" style="display: inline;"
                                                      onsubmit="return confirm(@js(($user->is_admin ? 'Retirar a função de gestor de ' : 'Tornar gestor: ') . $user->name . '?'));">
                                                    @csrf
                                                    <button type="submit" class="{{ $user->is_admin ? 'btn-remove' : 'btn-add' }}" style="height: auto; padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                        {{ $user->is_admin ? 'Retirar gestão' : 'Tornar gestor' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                            @endif
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
</body>
</html>
