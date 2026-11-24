<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar senha | Sistema</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>{{ $user->must_change_password ? 'Defina sua senha' : 'Alterar senha' }}</h1>
                <p>{{ $user->password === null ? 'Crie uma senha para também entrar com e-mail e senha.' : 'Use no mínimo 8 caracteres.' }}</p>
            </div>

            @if(session('info'))
                <div class="alert-error" style="background: rgba(59,130,246,.15); color:#60a5fa; border-color: rgba(59,130,246,.3);">{{ session('info') }}</div>
            @endif

            <form action="{{ route('conta.senha.salvar') }}" method="POST">
                @csrf

                @if($user->password !== null)
                    <div class="form-group">
                        <label for="senha_atual">Senha atual</label>
                        <div class="input-wrapper">
                            <input type="password" id="senha_atual" name="senha_atual" class="input-field" required autocomplete="current-password">
                        </div>
                        @error('senha_atual')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="form-group">
                    <label for="password">Nova senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="input-field" required minlength="8" autocomplete="new-password">
                    </div>
                    @error('password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar nova senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input-field" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">Salvar senha</button>
            </form>

            <div class="footer-links">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" style="background:none;border:0;color:var(--text-muted);cursor:pointer;font:inherit;font-size:.82rem;">Sair</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
