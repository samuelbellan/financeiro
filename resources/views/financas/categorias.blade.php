<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias | Finanças de Casa</title>
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .cat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-top: 4px solid #6366f1;
            display: flex;
            flex-direction: column;
        }

        .cat-card.receita { border-top-color: #10b981; }

        .cat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cat-title {
            font-weight: 700;
            font-size: 1.125rem;
            color: #111827;
        }

        .cat-type {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-weight: 600;
        }
        .type-despesa { background: #fee2e2; color: #991b1b; }
        .type-receita { background: #dcfce7; color: #166534; }

        .sub-list {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }

        .sub-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.625rem 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            transition: background 0.2s;
        }
        .sub-item:hover { background: #f3f4f6; }

        .btn-delete-x {
            color: #9ca3af;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1.125rem;
            line-height: 1;
        }
        .btn-delete-x:hover { color: #ef4444; }

        .btn-add-sub {
            width: 100%;
            margin-top: 1rem;
            padding: 0.5rem;
            border: 2px dashed #e5e7eb;
            background: none;
            border-radius: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-add-sub:hover { border-color: #6366f1; color: #6366f1; background: #f5f3ff; }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        .modal { background: white; width: 100%; max-width: 400px; border-radius: 1rem; padding: 1.5rem; }
        .modal-footer { margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem; }
        .btn-primary { background: #6366f1; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer; font-weight: 600; }
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
                    <a href="{{ route('financas.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <span>Finanças de Casa</span>
                    </a>
                    <a href="{{ route('estudos.index') }}" class="nav-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Horas de Estudo</span>
                    </a>
                    <a href="{{ route('categorias.index') }}" class="nav-item active">
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
            <header class="content-header">
                <div class="header-breadcrumb">
                    <a href="{{ route('home') }}">Home</a>
                    <span>/</span>
                    <span>Categorias</span>
                </div>
                <h1>Categorias & Subcategorias</h1>
                <p>Organize seus lançamentos com precisão</p>
            </header>

            <div class="content-body">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

                <div style="display: flex; justify-content: flex-end;">
                    <button onclick="document.getElementById('modalCat').style.display='flex'" class="btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.875rem;">+ Nova Categoria</button>
                </div>

                <div class="cat-grid">
                    @forelse($categorias as $cat)
                        <div class="cat-card {{ $cat->tipo }}">
                            <div class="cat-header">
                                <div>
                                    <span class="cat-title">{{ $cat->nome }}</span><br>
                                    <span class="cat-type type-{{ $cat->tipo }}">{{ ucfirst($cat->tipo) }}</span>
                                </div>
                                <form action="{{ route('categorias.destroy', $cat->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-delete-x" onclick="return confirm('Excluir categoria e todas subcategorias?')">&times;</button>
                                </form>
                            </div>

                            <ul class="sub-list">
                                @foreach($cat->subcategorias as $sub)
                                    <li class="sub-item">
                                        <span>{{ $sub->nome }}</span>
                                        <form action="{{ route('subcategorias.destroy', $sub->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-delete-x">&times;</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>

                            <button onclick="openSubModal({{ $cat->id }}, '{{ $cat->nome }}')" class="btn-add-sub">+ Subcategoria</button>
                        </div>
                    @empty
                        <p style="grid-column: 1/-1; text-align: center; color: #6b7280; padding: 4rem;">Você ainda não cadastrou nenhuma categoria.</p>
                    @endforelse
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Categoria -->
    <div id="modalCat" class="modal-overlay">
        <div class="modal">
            <h3>Nova Categoria</h3><br>
            <form action="{{ route('categorias.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-input" required placeholder="Ex: Moradia, Alimentação">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-input">
                        <option value="despesa">Despesa</option>
                        <option value="receita">Receita</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('modalCat').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Subcategoria -->
    <div id="modalSub" class="modal-overlay">
        <div class="modal">
            <h3>Nova Subcategoria em <span id="catNameLabel"></span></h3><br>
            <form action="{{ route('subcategorias.store') }}" method="POST">
                @csrf
                <input type="hidden" name="categoria_id" id="catIdInput">
                <div class="form-group">
                    <label class="form-label">Nome da Subcategoria</label>
                    <input type="text" name="nome" class="form-input" required placeholder="Ex: Aluguel, Supermercado">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('modalSub').style.display='none'">Cancelar</button>
                    <button type="submit" class="btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSubModal(id, name) {
            document.getElementById('catIdInput').value = id;
            document.getElementById('catNameLabel').innerText = name;
            document.getElementById('modalSub').style.display = 'flex';
        }

        window.onclick = function(e) {
            if(e.target.className == 'modal-overlay') {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
