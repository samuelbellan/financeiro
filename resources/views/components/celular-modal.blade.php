@php
    $localIp = \App\Services\NetworkService::getLocalIp();
    $localUrl = \App\Services\NetworkService::getLocalUrl();
    $onlineQrUrl = \App\Services\NetworkService::getOnlineQrCodeUrl($localUrl);
@endphp

<div id="celularModalBackdrop" class="celular-modal-backdrop" style="display: none;">
    <div class="celular-modal-card" role="dialog" aria-modal="true" aria-labelledby="celularModalTitle">
        <div class="celular-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="celular-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                </div>
                <div>
                    <h3 id="celularModalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff;">Acessar no Celular</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;">Acesso direto pela rede Wi-Fi local sem limites</p>
                </div>
            </div>
            <button type="button" class="celular-modal-close" onclick="closeCelularModal()">&times;</button>
        </div>

        <div class="celular-modal-body">
            <!-- QR Code Box -->
            <div class="celular-qr-wrapper">
                <div id="celularQrContainer" class="celular-qr-box" title="Aponte a câmera do seu celular">
                    <!-- Gerado dinamicamente via QRCode.js offline ou imagem online -->
                    <noscript>
                        <img src="{{ $onlineQrUrl }}" alt="QR Code Local" width="200" height="200" style="border-radius: 8px;">
                    </noscript>
                </div>
                <p class="celular-qr-caption">Aponte a <strong>Câmera do Celular</strong> para conectar</p>
            </div>

            <!-- URL Box com botão de copiar -->
            <div class="celular-url-box">
                <div class="celular-url-display">
                    <span class="celular-url-badge">URL LOCAL</span>
                    <input type="text" id="celularLocalUrlInput" value="{{ $localUrl }}" readonly class="celular-url-input">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="celular-btn-action" id="btnCopiarLocalUrl" onclick="copiarUrlCelular()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span id="btnCopiarTexto">Copiar Link</span>
                    </button>
                    <a href="{{ $localUrl }}" target="_blank" class="celular-btn-action secondary" title="Testar link em nova aba">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span>Testar</span>
                    </a>
                </div>
            </div>

            <!-- Instruções Rápidas -->
            <div class="celular-steps-list">
                <div class="celular-step-item">
                    <div class="celular-step-num">1</div>
                    <div>
                        <strong>Mesmo Wi-Fi:</strong> O celular deve estar conectado na mesma rede Wi-Fi deste computador.
                    </div>
                </div>
                <div class="celular-step-item">
                    <div class="celular-step-num">2</div>
                    <div>
                        <strong>Aponte a Câmera:</strong> Abra a câmera do celular e toque na notificação para abrir.
                    </div>
                </div>
                <div class="celular-step-item">
                    <div class="celular-step-num">3</div>
                    <div>
                        <strong>Usar como App:</strong> No Chrome/Safari do celular, toque nos 3 pontinhos e escolha <em>"Adicionar à tela inicial"</em>.
                    </div>
                </div>
            </div>

            <!-- Dúvidas / Diagnóstico -->
            <details class="celular-troubleshoot">
                <summary>Não conseguiu conectar? Toque para ver dicas rápidas</summary>
                <div class="celular-troubleshoot-content">
                    <p>• <strong>Desative o 4G/5G:</strong> Certifique-se de que o celular está navegando pelo Wi-Fi e não pelos dados móveis.</p>
                    <p>• <strong>Rede de Visitantes:</strong> Alguns roteadores têm redes "Guest" que isolam dispositivos. Conecte na rede principal.</p>
                    <p>• <strong>Firewall do Windows:</strong> A porta {{ \App\Services\NetworkService::getPort() }} precisa estar liberada no Firewall caso haja bloqueio.</p>
                </div>
            </details>
        </div>

        <div class="celular-modal-footer">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #10b981;">
                <span class="celular-status-dot"></span>
                <span>Servidor escutando na porta {{ \App\Services\NetworkService::getPort() }}</span>
            </div>
            <button type="button" class="celular-btn-cancel" onclick="closeCelularModal()">Fechar</button>
        </div>
    </div>
</div>

<style>
/* Celular Modal Styles */
.celular-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    padding: 1rem;
    box-sizing: border-box;
    animation: celularFadeIn 0.2s ease-out;
}

@keyframes celularFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.celular-modal-card {
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: celularSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes celularSlideUp {
    from { transform: translateY(20px) scale(0.97); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.celular-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(30, 41, 59, 0.6);
}

.celular-icon-circle {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.celular-modal-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.15s ease;
}

.celular-modal-close:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}

.celular-modal-body {
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
    max-height: 75vh;
    overflow-y: auto;
}

.celular-qr-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.03);
    border: 1px dashed rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 1.25rem;
}

.celular-qr-box {
    background: #ffffff;
    padding: 12px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
    min-width: 180px;
    min-height: 180px;
}

.celular-qr-box canvas, .celular-qr-box img {
    display: block;
    border-radius: 4px;
}

.celular-qr-caption {
    margin: 0.75rem 0 0 0;
    font-size: 0.85rem;
    color: #cbd5e1;
    text-align: center;
}

.celular-url-box {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.celular-url-display {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.celular-url-badge {
    font-size: 0.65rem;
    font-weight: 700;
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.celular-url-input {
    background: transparent;
    border: none;
    color: #f1f5f9;
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.95rem;
    font-weight: 600;
    width: 100%;
    outline: none;
}

.celular-btn-action {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.55rem 1rem;
    background: #10b981;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}

.celular-btn-action:hover {
    background: #059669;
    transform: translateY(-1px);
}

.celular-btn-action.secondary {
    background: rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
}

.celular-btn-action.secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

.celular-steps-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 10px;
    padding: 0.75rem 1rem;
}

.celular-step-item {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    font-size: 0.8rem;
    color: #cbd5e1;
    line-height: 1.4;
}

.celular-step-num {
    background: rgba(255, 255, 255, 0.1);
    color: #38bdf8;
    font-weight: 700;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    flex-shrink: 0;
    margin-top: 1px;
}

.celular-troubleshoot {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.78rem;
    color: #94a3b8;
}

.celular-troubleshoot summary {
    cursor: pointer;
    font-weight: 600;
    color: #94a3b8;
    user-select: none;
}

.celular-troubleshoot summary:hover {
    color: #cbd5e1;
}

.celular-troubleshoot-content {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    line-height: 1.4;
}

.celular-troubleshoot-content p {
    margin: 0;
}

.celular-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(30, 41, 59, 0.4);
}

.celular-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
}

.celular-btn-cancel {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #cbd5e1;
    padding: 0.45rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.celular-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}
</style>

<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script>
window.LOCAL_NETWORK_URL = "{{ $localUrl }}";

function openCelularModal() {
    var modal = document.getElementById('celularModalBackdrop');
    if (!modal) return;
    modal.style.display = 'flex';

    // Gerar QR code local
    var qrContainer = document.getElementById('celularQrContainer');
    if (qrContainer && !qrContainer.hasAttribute('data-rendered')) {
        qrContainer.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            try {
                new QRCode(qrContainer, {
                    text: window.LOCAL_NETWORK_URL,
                    width: 180,
                    height: 180,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
                qrContainer.setAttribute('data-rendered', 'true');
            } catch (err) {
                renderQrFallback(qrContainer);
            }
        } else {
            renderQrFallback(qrContainer);
        }
    }
}

function renderQrFallback(container) {
    container.innerHTML = '<img src="{{ $onlineQrUrl }}" alt="QR Code Local" width="180" height="180" style="border-radius: 6px; display: block;">';
    container.setAttribute('data-rendered', 'true');
}

function closeCelularModal() {
    var modal = document.getElementById('celularModalBackdrop');
    if (modal) modal.style.display = 'none';
}

function copiarUrlCelular() {
    var input = document.getElementById('celularLocalUrlInput');
    var btnText = document.getElementById('btnCopiarTexto');
    if (!input) return;

    input.select();
    input.setSelectionRange(0, 99999);

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(function() {
            mostrarCopiado();
        }).catch(function() {
            document.execCommand('copy');
            mostrarCopiado();
        });
    } else {
        document.execCommand('copy');
        mostrarCopiado();
    }

    function mostrarCopiado() {
        if (btnText) {
            var original = btnText.innerText;
            btnText.innerText = 'Copiado! ✓';
            setTimeout(function() {
                btnText.innerText = original;
            }, 2000);
        }
    }
}

// Fechar com ESC ou clique fora
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCelularModal();
    }
});

document.addEventListener('click', function(e) {
    var backdrop = document.getElementById('celularModalBackdrop');
    if (backdrop && e.target === backdrop) {
        closeCelularModal();
    }
});
</script>
