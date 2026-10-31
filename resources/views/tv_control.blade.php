<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle da TV Philco | Painel Local</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090a0f;
            --card-bg: rgba(22, 27, 34, 0.75);
            --border: rgba(255, 255, 255, 0.12);
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, sans-serif;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(2, 132, 199, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%);
            min-height: 100vh;
            color: var(--text-main);
            padding: 24px 16px;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 680px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }

        .header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-online {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            padding: 4px 10px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 9999px;
            font-weight: 500;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            background: #34d399;
            border-radius: 50%;
            box-shadow: 0 0 8px #34d399;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            flex: 1;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="text"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.25);
        }

        button {
            cursor: pointer;
            border: none;
            outline: none;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary {
            background: var(--accent);
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            white-space: nowrap;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .quick-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .chip:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        /* D-Pad Virtual Remote */
        .remote-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 10px 0;
        }

        .dpad-container {
            display: grid;
            grid-template-columns: repeat(3, 72px);
            grid-template-rows: repeat(3, 72px);
            gap: 10px;
            justify-content: center;
        }

        .dpad-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            color: #ffffff;
            border-radius: 12px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .dpad-btn:hover {
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .dpad-btn:active {
            transform: scale(0.95);
            background: var(--accent);
        }

        .dpad-ok {
            background: var(--accent);
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .dpad-ok:hover {
            background: var(--accent-hover);
        }

        .actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            width: 100%;
        }

        .btn-action {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border);
            color: #e5e7eb;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .btn-action:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
        }

        .btn-app {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            color: #ffffff;
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .btn-app:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #10b981;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            z-index: 1000;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📺 Controle da TV Philco</h1>
            <div class="badge-online">
                <span class="badge-dot"></span>
                192.168.15.7:5555
            </div>
        </div>

        <!-- Section: URL Sender -->
        <div class="card">
            <div class="card-title">🌐 Enviar Link / URL para a TV (TV Bro)</div>
            <div class="input-group">
                <input type="text" id="urlInput" placeholder="Cole ou digite a URL (ex: https://youtube.com)" />
                <button class="btn-primary" onclick="sendUrl()">Abrir na TV 🚀</button>
            </div>
            <div class="quick-chips">
                <span class="chip" onclick="fillUrl('https://www.google.com')">Google</span>
                <span class="chip" onclick="fillUrl('https://www.youtube.com')">YouTube</span>
                <span class="chip" onclick="fillUrl('https://web.whatsapp.com')">WhatsApp Web</span>
                <span class="chip" onclick="fillUrl('https://ge.globo.com')">Globo Esporte</span>
                <span class="chip" onclick="fillUrl('https://g1.globo.com')">G1 Notícias</span>
            </div>
        </div>

        <!-- Section: Text Typing -->
        <div class="card">
            <div class="card-title">⌨️ Digitar Texto na TV (Pesquisas / Campos)</div>
            <div class="input-group">
                <input type="text" id="textInput" placeholder="Digite o texto que deseja enviar para o campo focado na TV" />
                <button class="btn-primary" onclick="sendText()">Digitar ⌨️</button>
            </div>
        </div>

        <!-- Section: Virtual Remote D-Pad -->
        <div class="card">
            <div class="card-title">🎮 Controle Remoto Virtual</div>
            <div class="remote-layout">
                <div class="dpad-container">
                    <div></div>
                    <button class="dpad-btn" onclick="sendKey('up')" title="Cima">▲</button>
                    <div></div>

                    <button class="dpad-btn" onclick="sendKey('left')" title="Esquerda">◀</button>
                    <button class="dpad-btn dpad-ok" onclick="sendKey('enter')" title="OK">OK</button>
                    <button class="dpad-btn" onclick="sendKey('right')" title="Direita">▶</button>

                    <div></div>
                    <button class="dpad-btn" onclick="sendKey('down')" title="Baixo">▼</button>
                    <div></div>
                </div>

                <div class="actions-row">
                    <button class="btn-action" onclick="sendKey('back')">↩️ Voltar</button>
                    <button class="btn-action" onclick="sendKey('home')">🏠 Início</button>
                    <button class="btn-action" onclick="sendKey('voldown')">🔉 Vol -</button>
                    <button class="btn-action" onclick="sendKey('volup')">🔊 Vol +</button>
                    <button class="btn-action" onclick="sendKey('mute')">🔇 Mudo</button>
                </div>
            </div>
        </div>

        <!-- Section: App Shortcuts -->
        <div class="card">
            <div class="card-title">🚀 Abrir Aplicativos</div>
            <div class="app-grid">
                <button class="btn-app" onclick="openApp('browser')">
                    <span style="font-size: 1.5rem;">🌐</span>
                    <span>TV Bro</span>
                </button>
                <button class="btn-app" onclick="openApp('youtube')">
                    <span style="font-size: 1.5rem;">▶️</span>
                    <span>YouTube</span>
                </button>
                <button class="btn-app" onclick="openApp('netflix')">
                    <span style="font-size: 1.5rem;">🎬</span>
                    <span>Netflix</span>
                </button>
                <button class="btn-app" onclick="openApp('jellyfin')">
                    <span style="font-size: 1.5rem;">💜</span>
                    <span>Jellyfin</span>
                </button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">Comando enviado com sucesso!</div>

    <script>
        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }

        async function sendRequest(payload) {
            try {
                const response = await fetch('/api/tv/action', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.status === 'success') {
                    showToast(data.message || 'Comando executado!');
                } else {
                    alert('Erro: ' + (data.message || 'Falha ao executar comando'));
                }
            } catch (err) {
                console.error(err);
                alert('Erro de comunicação com o servidor.');
            }
        }

        function fillUrl(url) {
            document.getElementById('urlInput').value = url;
        }

        function sendUrl() {
            const url = document.getElementById('urlInput').value.trim();
            if (!url) {
                alert('Digite ou cole uma URL!');
                return;
            }
            sendRequest({ type: 'open_url', url: url });
        }

        function sendText() {
            const text = document.getElementById('textInput').value;
            if (!text) return;
            sendRequest({ type: 'text', text: text });
            document.getElementById('textInput').value = '';
        }

        function sendKey(key) {
            sendRequest({ type: 'key', key: key });
        }

        function openApp(app) {
            sendRequest({ type: 'app', app: app });
        }

        document.getElementById('urlInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') sendUrl();
        });
        document.getElementById('textInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') sendText();
        });
    </script>
</body>
</html>
