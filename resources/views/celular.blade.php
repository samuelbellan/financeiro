@php
    $localIp = \App\Services\NetworkService::getLocalIp();
    $localUrl = \App\Services\NetworkService::getLocalUrl();
    $onlineQrUrl = \App\Services\NetworkService::getOnlineQrCodeUrl($localUrl);
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessar no Celular | Sistema Financeiro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .container {
            width: 100%;
            max-width: 520px;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            padding: 2rem 2rem 1.5rem 2rem;
            text-align: center;
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.4) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .badge-wifi {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.4rem;
        }

        .header p {
            color: #94a3b8;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .body {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        .qr-card {
            background: #ffffff;
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 210px;
            min-height: 210px;
        }

        .qr-card canvas, .qr-card img {
            display: block;
            border-radius: 6px;
        }

        .url-box {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .url-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .url-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .url-input {
            flex: 1;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #38bdf8;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.95rem;
            font-weight: 700;
            outline: none;
        }

        .btn-copy {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.65rem 1.1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-copy:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .instructions {
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .instruction-item {
            display: flex;
            gap: 0.85rem;
            font-size: 0.85rem;
            color: #cbd5e1;
            line-height: 1.4;
        }

        .step-num {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        .footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.4);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-back {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: color 0.15s ease;
        }

        .btn-back:hover {
            color: #ffffff;
        }

        .btn-dashboard {
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .btn-dashboard:hover {
            background: rgba(255, 255, 255, 0.16);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="badge-wifi">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
                REDE WI-FI LOCAL
            </div>
            <h1>Acessar no Celular</h1>
            <p>Conecte seu celular na mesma rede Wi-Fi deste computador e aponte a câmera para o QR Code abaixo</p>
        </div>

        <div class="body">
            <div class="qr-card" id="qrContainer" title="Aponte a câmera do seu celular">
                <noscript>
                    <img src="{{ $onlineQrUrl }}" alt="QR Code Local" width="200" height="200">
                </noscript>
            </div>

            <div class="url-box">
                <span class="url-label">Endereço Direto no Navegador</span>
                <div class="url-input-group">
                    <input type="text" id="urlInput" class="url-input" value="{{ $localUrl }}" readonly>
                    <button type="button" class="btn-copy" id="btnCopy" onclick="copiarLink()">
                        <span id="copyText">Copiar</span>
                    </button>
                </div>
            </div>

            <div class="instructions">
                <div class="instruction-item">
                    <div class="step-num">1</div>
                    <div><strong>Wi-Fi Conectado:</strong> Certifique-se de que seu celular está no mesmo Wi-Fi de casa (não no 4G/5G).</div>
                </div>
                <div class="instruction-item">
                    <div class="step-num">2</div>
                    <div><strong>Aponte a Câmera:</strong> Abra a câmera nativa do iPhone ou Android e toque no link do navegador.</div>
                </div>
                <div class="instruction-item">
                    <div class="step-num">3</div>
                    <div><strong>Adicionar Atalho:</strong> No navegador do celular, toque em Compartilhar / Menu e escolha <strong>"Adicionar à tela de início"</strong> para usar em tela cheia como um App!</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <a href="{{ route('home') }}" class="btn-back">
                &larr; Voltar ao Sistema
            </a>
            <a href="{{ route('home') }}" class="btn-dashboard">
                Ir para o Dashboard
            </a>
        </div>
    </div>

    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script>
        var qrUrl = "{{ $localUrl }}";
        var container = document.getElementById('qrContainer');
        if (container) {
            if (typeof QRCode !== 'undefined') {
                try {
                    new QRCode(container, {
                        text: qrUrl,
                        width: 200,
                        height: 200,
                        colorDark: "#0f172a",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (e) {
                    container.innerHTML = '<img src="{{ $onlineQrUrl }}" alt="QR Code" width="200" height="200">';
                }
            } else {
                container.innerHTML = '<img src="{{ $onlineQrUrl }}" alt="QR Code" width="200" height="200">';
            }
        }

        function copiarLink() {
            var input = document.getElementById('urlInput');
            var copyText = document.getElementById('copyText');
            input.select();
            input.setSelectionRange(0, 99999);

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(sucesso).catch(function() {
                    document.execCommand('copy');
                    sucesso();
                });
            } else {
                document.execCommand('copy');
                sucesso();
            }

            function sucesso() {
                copyText.innerText = 'Copiado! ✓';
                setTimeout(function() { copyText.innerText = 'Copiar'; }, 2000);
            }
        }
    </script>
</body>
</html>
