<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito | Galeria de Fotos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --bg-dark: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-light: #f8fafc;
            --text-sub: #94a3b8;
            --danger: #f87171;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top left, #1e1b4b 0%, #0f172a 100%);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Decorative glowing orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            opacity: 0.15;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: var(--primary);
            top: -10%;
            left: -10%;
        }
        .orb-2 {
            width: 300px;
            height: 300px;
            background: #3b82f6;
            bottom: -5%;
            right: -5%;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 2rem;
            z-index: 10;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            background: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--primary);
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: var(--text-sub);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
            background: rgba(15, 23, 42, 0.8);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, #9333ea 0%, #6d28d9 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.825rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--text-sub);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
            </div>
            
            <div class="login-header">
                <h1>Galeria de Fotos</h1>
                <p>Módulo restrito de visualização</p>
            </div>

            <form action="{{ route('photos.login.post') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="input-field" placeholder="Digite o usuário" required value="{{ old('username') }}" autocomplete="username" autofocus>
                    </div>
                    @error('username')
                        <div class="error-message">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="input-field" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login">Verificar Acesso</button>
            </form>

            <a href="{{ route('home') }}" class="back-link">← Voltar para o Dashboard</a>
        </div>
    </div>
</body>
</html>
