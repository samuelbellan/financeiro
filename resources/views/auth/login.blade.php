<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Financeiro</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Bem-vindo</h1>
                <p>Acesse sua conta para continuar</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="input-field" placeholder="exemplo@email.com" required value="{{ old('email') }}" autocomplete="username">
                    </div>
                    @error('email')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    @error('password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        Lembrar de mim
                    </label>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <div class="footer-links">
                <p>Não tem uma conta? <a href="#">Cadastre-se</a></p>
            </div>
        </div>
    </div>
</body>
</html>
