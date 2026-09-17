<div id="syncModalBackdrop" class="sync-modal-backdrop" style="display: none;">
    <div class="sync-modal-card" role="dialog" aria-modal="true" aria-labelledby="syncModalTitle">
        <div class="sync-modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="sync-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="syncModalTitle" style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #fff;">Sincronização Nuvem</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;" id="syncCloudStatusText">Verificando status da conexão...</p>
                </div>
            </div>
            <button type="button" class="sync-modal-close" onclick="closeSyncModal()">&times;</button>
        </div>

        <div class="sync-modal-body">
            <div id="syncStatusAlert" class="sync-status-alert" style="display: none;"></div>

            <div class="sync-options-grid">
                <button type="button" class="sync-option-btn primary" onclick="runSyncOperation('both')">
                    <div class="sync-option-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m3 16 4 4 4-4"/>
                            <path d="M7 20V4"/>
                            <path d="m21 8-4-4-4 4"/>
                            <path d="M17 4v16"/>
                        </svg>
                    </div>
                    <div class="sync-option-info">
                        <span class="sync-option-title">Sincronização Completa (2 Vias)</span>
                        <span class="sync-option-desc">Puxa novidades do Bot/Nuvem e envia alterações locais</span>
                    </div>
                </button>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <button type="button" class="sync-option-btn secondary" onclick="runSyncOperation('pull')">
                        <div class="sync-option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                        </div>
                        <div class="sync-option-info">
                            <span class="sync-option-title">Puxar (Pull)</span>
                            <span class="sync-option-desc">Nuvem ➔ Local</span>
                        </div>
                    </button>

                    <button type="button" class="sync-option-btn secondary" onclick="runSyncOperation('push')">
                        <div class="sync-option-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                        </div>
                        <div class="sync-option-info">
                            <span class="sync-option-title">Enviar (Push)</span>
                            <span class="sync-option-desc">Local ➔ Nuvem</span>
                        </div>
                    </button>
                </div>
            </div>

            <div id="syncProgressArea" style="display: none; margin-top: 1rem; text-align: center; padding: 1rem; background: rgba(255,255,255,0.04); border-radius: 8px;">
                <div class="sync-spinner"></div>
                <p id="syncProgressText" style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #cbd5e1;">Processando sincronização com a nuvem...</p>
            </div>
        </div>

        <div class="sync-modal-footer">
            <span id="syncLastTimeText" style="font-size: 0.75rem; color: #64748b;">Último sync: nunca</span>
            <button type="button" class="sync-btn-cancel" onclick="closeSyncModal()">Fechar</button>
        </div>
    </div>
</div>

<style>
/* Sync Modal Styles */
.sync-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.sync-modal-card {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 14px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
    overflow: hidden;
    color: #f8fafc;
    font-family: inherit;
    animation: syncModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes syncModalFadeIn {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.sync-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #1e293b;
}
.sync-icon-circle {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sync-modal-close {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
    padding: 0.25rem;
    border-radius: 6px;
}
.sync-modal-close:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
}
.sync-modal-body {
    padding: 1.25rem 1.5rem;
}
.sync-options-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.sync-option-btn {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid #1e293b;
    background: #1e293b;
    color: #f8fafc;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
    width: 100%;
}
.sync-option-btn:hover {
    background: #334155;
    border-color: #475569;
    transform: translateY(-1px);
}
.sync-option-btn.primary {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(99, 102, 241, 0.18));
    border-color: rgba(56, 189, 248, 0.4);
}
.sync-option-btn.primary:hover {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.28), rgba(99, 102, 241, 0.28));
    border-color: #38bdf8;
}
.sync-option-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #38bdf8;
}
.sync-option-info {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.sync-option-title {
    font-size: 0.875rem;
    font-weight: 600;
}
.sync-option-desc {
    font-size: 0.75rem;
    color: #94a3b8;
}
.sync-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.5rem;
    background: rgba(15, 23, 42, 0.7);
    border-top: 1px solid #1e293b;
}
.sync-btn-cancel {
    background: #1e293b;
    color: #cbd5e1;
    border: 1px solid #334155;
    padding: 0.4rem 0.85rem;
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
}
.sync-btn-cancel:hover {
    background: #334155;
    color: #fff;
}
.sync-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(56, 189, 248, 0.2);
    border-top-color: #38bdf8;
    border-radius: 50%;
    animation: syncSpin 0.8s linear infinite;
    margin: 0 auto;
}
@keyframes syncSpin {
    to { transform: rotate(360deg); }
}
.sync-status-alert {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.825rem;
    margin-bottom: 1rem;
}
.sync-status-alert.success {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
}
.sync-status-alert.error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}

/* Sidebar Button Style */
.btn-sync-sidebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.55rem 0.75rem;
    margin-bottom: 0.75rem;
    background: rgba(56, 189, 248, 0.08);
    border: 1px solid rgba(56, 189, 248, 0.25);
    border-radius: 8px;
    color: #38bdf8;
    font-size: 0.825rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-sync-sidebar:hover {
    background: rgba(56, 189, 248, 0.18);
    border-color: #38bdf8;
    color: #fff;
}
.sync-indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 6px rgba(34, 197, 94, 0.6);
}
.sync-indicator-dot.offline {
    background: #ef4444;
    box-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
}
.sync-indicator-dot.syncing {
    background: #f59e0b;
    box-shadow: 0 0 6px rgba(245, 158, 11, 0.6);
    animation: syncPulse 1s infinite alternate;
}
@keyframes syncPulse {
    from { opacity: 0.4; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1.1); }
}
</style>

<script>
window.openSyncModal = function () {
    var modal = document.getElementById('syncModalBackdrop');
    if (modal) {
        modal.style.display = 'flex';
        checkCloudSyncStatus();
    }
};

window.closeSyncModal = function () {
    var modal = document.getElementById('syncModalBackdrop');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.checkCloudSyncStatus = function () {
    var statusText = document.getElementById('syncCloudStatusText');
    var lastTimeText = document.getElementById('syncLastTimeText');
    var dot = document.getElementById('syncSidebarDot');

    fetch('{{ route('sync.status') }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success && data.data) {
            var info = data.data;
            if (info.cloud_online) {
                if (statusText) statusText.innerHTML = '🟢 Nuvem Neon conectada (' + info.local_driver + ' local)';
                if (dot) dot.className = 'sync-indicator-dot';
            } else {
                if (statusText) statusText.innerHTML = '🔴 Nuvem offline: ' + (info.cloud_error || 'Indisponível');
                if (dot) dot.className = 'sync-indicator-dot offline';
            }

            if (info.last_sync_at && lastTimeText) {
                var d = new Date(info.last_sync_at);
                lastTimeText.innerText = 'Último sync: ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' (' + d.toLocaleDateString() + ')';
            }
        }
    })
    .catch(function () {
        if (statusText) statusText.innerHTML = '⚠️ Não foi possível checar a nuvem';
    });
};

window.runSyncOperation = function (direction) {
    var progressArea = document.getElementById('syncProgressArea');
    var progressText = document.getElementById('syncProgressText');
    var alertBox = document.getElementById('syncStatusAlert');
    var dot = document.getElementById('syncSidebarDot');
    var buttons = document.querySelectorAll('.sync-option-btn');

    if (progressArea) progressArea.style.display = 'block';
    if (progressText) {
        if (direction === 'pull') progressText.innerText = 'Baixando dados da nuvem para o banco local...';
        else if (direction === 'push') progressText.innerText = 'Enviando dados do banco local para a nuvem...';
        else progressText.innerText = 'Executando sincronização bidirecional completa...';
    }
    if (alertBox) alertBox.style.display = 'none';
    if (dot) dot.className = 'sync-indicator-dot syncing';

    buttons.forEach(function (btn) { btn.disabled = true; btn.style.opacity = '0.5'; });

    fetch('{{ route('sync.run') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ direction: direction })
    })
    .then(function (res) {
        return res.json().then(function (data) {
            return { ok: res.ok, data: data };
        });
    })
    .then(function (result) {
        if (progressArea) progressArea.style.display = 'none';
        buttons.forEach(function (btn) { btn.disabled = false; btn.style.opacity = '1'; });

        if (result.ok && result.data.success) {
            if (alertBox) {
                alertBox.className = 'sync-status-alert success';
                alertBox.innerText = result.data.message || 'Sincronização concluída com sucesso!';
                alertBox.style.display = 'block';
            }
            if (dot) dot.className = 'sync-indicator-dot';

            // Notifica abas e atualiza carimbo
            if (typeof window.notifyDataUpdated === 'function') {
                window.notifyDataUpdated();
            }

            var lastTimeText = document.getElementById('syncLastTimeText');
            if (lastTimeText) {
                var now = new Date();
                lastTimeText.innerText = 'Último sync: ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            // Recarrega página após 1.5s para refletir novos dados
            setTimeout(function () {
                window.location.reload();
            }, 1500);
        } else {
            if (alertBox) {
                alertBox.className = 'sync-status-alert error';
                alertBox.innerText = (result.data && result.data.message) ? result.data.message : 'Falha na sincronização.';
                alertBox.style.display = 'block';
            }
            if (dot) dot.className = 'sync-indicator-dot offline';
        }
    })
    .catch(function (err) {
        if (progressArea) progressArea.style.display = 'none';
        buttons.forEach(function (btn) { btn.disabled = false; btn.style.opacity = '1'; });
        if (alertBox) {
            alertBox.className = 'sync-status-alert error';
            alertBox.innerText = 'Erro de comunicação: ' + err.message;
            alertBox.style.display = 'block';
        }
        if (dot) dot.className = 'sync-indicator-dot offline';
    });
};

document.addEventListener('DOMContentLoaded', function () {
    // Escuta clique fora do modal para fechar
    var backdrop = document.getElementById('syncModalBackdrop');
    if (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) {
                closeSyncModal();
            }
        });
    }

    // Escuta tecla ESC para fechar modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSyncModal();
        }
    });

    // Atualiza status do dot silenciosamente na inicialização
    var dot = document.getElementById('syncSidebarDot');
    if (dot) {
        fetch('{{ route('sync.status') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success && res.data && res.data.cloud_online) {
                dot.className = 'sync-indicator-dot';
            } else {
                dot.className = 'sync-indicator-dot offline';
            }
        })
        .catch(function () {});
    }
});
</script>
