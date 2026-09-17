/**
 * Sidebar Toggle & Multi-Tab Synchronization Controller
 */

// Unique ID for current tab session
window.TAB_ID = window.TAB_ID || (Math.random().toString(36).substring(2) + Date.now().toString(36));

(function () {
    // --- 1. SIDEBAR CONTROLLER ---
    var stored = localStorage.getItem('sidebar_collapsed');
    if (stored === 'true' && window.innerWidth > 768) {
        document.documentElement.classList.add('sidebar-collapsed-init');
    }

    function initSidebar() {
        var layout = document.querySelector('.layout');
        if (!layout) return;

        var isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (isCollapsed && window.innerWidth > 768) {
            layout.classList.add('sidebar-collapsed');
        }
        
        document.documentElement.classList.remove('sidebar-collapsed-init');

        var toggleBtns = document.querySelectorAll('.js-toggle-sidebar');
        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    layout.classList.toggle('sidebar-mobile-open');
                } else {
                    layout.classList.toggle('sidebar-collapsed');
                    var nowCollapsed = layout.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', nowCollapsed ? 'true' : 'false');
                }
            });
        });

        var backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }
        backdrop.addEventListener('click', function () {
            layout.classList.remove('sidebar-mobile-open');
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey && e.key === '\\') || (e.altKey && (e.key === 's' || e.key === 'S'))) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    layout.classList.toggle('sidebar-mobile-open');
                } else {
                    layout.classList.toggle('sidebar-collapsed');
                    var nowCollapsed = layout.classList.contains('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', nowCollapsed ? 'true' : 'false');
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }

    // --- 2. MULTI-TAB SYNCHRONIZATION CONTROLLER ---
    var dataSyncChannel = (typeof BroadcastChannel !== 'undefined') ? new BroadcastChannel('financeiro_sync_channel') : null;
    var lastHandledSignalTime = 0;
    var isReloadingCurrentTab = false;

    window.notifyDataUpdated = function () {
        var now = Date.now();
        var payload = JSON.stringify({ sender: window.TAB_ID, timestamp: now });
        try {
            localStorage.setItem('financeiro_data_updated', payload);
        } catch (e) {}

        if (dataSyncChannel) {
            try {
                dataSyncChannel.postMessage({ action: 'DATA_UPDATED', sender: window.TAB_ID, timestamp: now });
            } catch (err) {}
        }
    };

    function isAnyModalOpen() {
        return Array.from(document.querySelectorAll('.modal-overlay')).some(function (overlay) {
            var style = window.getComputedStyle(overlay);
            return style.display !== 'none' && style.visibility !== 'hidden';
        });
    }

    function getScrollContainer() {
        return document.querySelector('.content-body');
    }

    function getCurrentScrollPosition() {
        var container = getScrollContainer();
        if (container && container.scrollTop > 0) {
            return container.scrollTop;
        }
        return window.scrollY || window.pageYOffset || 0;
    }

    function saveScrollPosition() {
        var SCROLL_KEY = 'financeiro_scroll_pos_' + window.location.pathname;
        var pos = getCurrentScrollPosition();
        try {
            sessionStorage.setItem(SCROLL_KEY, pos.toString());
        } catch (e) {}
    }

    window.handleDataUpdatedSignal = function (senderId, timestamp) {
        // Ignore signals created by THIS tab
        if (senderId && senderId === window.TAB_ID) return;

        var now = Date.now();
        // Debounce: ignore repeated signals within 2.5 seconds or if currently reloading
        if (now - lastHandledSignalTime < 2500 || isReloadingCurrentTab) return;

        var isEditingInput = document.activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName) && document.activeElement.value !== '';

        if (!isAnyModalOpen() && !isEditingInput) {
            isReloadingCurrentTab = true;
            lastHandledSignalTime = now;
            saveScrollPosition();
            window.location.reload();
        } else {
            window.needsReloadOnModalClose = true;
        }
    };

    if (dataSyncChannel) {
        dataSyncChannel.onmessage = function (e) {
            if (e.data && e.data.action === 'DATA_UPDATED') {
                window.handleDataUpdatedSignal(e.data.sender, e.data.timestamp);
            }
        };
    }

    window.addEventListener('storage', function (e) {
        if (e.key === 'financeiro_data_updated' && e.newValue) {
            try {
                var parsed = JSON.parse(e.newValue);
                window.handleDataUpdatedSignal(parsed.sender, parsed.timestamp);
            } catch (err) {
                window.handleDataUpdatedSignal(null, null);
            }
        }
    });

    // --- 3. CLOUD DATABASE SYNC CONTROLLER ---
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        var input = document.querySelector('input[name="_token"]');
        if (input && input.value) return input.value;
        var match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function initCloudSync() {
        var footer = document.querySelector('.sidebar-footer');
        if (!footer) return;

        // Injeta CSS caso ainda não exista
        if (!document.getElementById('syncWidgetStyles')) {
            var style = document.createElement('style');
            style.id = 'syncWidgetStyles';
            style.textContent = `
                .btn-sync-sidebar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    width: 100%;
                    padding: 0.55rem 0.75rem;
                    margin-bottom: 0.85rem;
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
                    display: inline-block;
                }
                .sync-indicator-dot.offline {
                    background: #ef4444;
                    box-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
                }
                .sync-indicator-dot.syncing {
                    background: #f59e0b;
                    box-shadow: 0 0 6px rgba(245, 158, 11, 0.6);
                    animation: syncPulseDot 1s infinite alternate;
                }
                @keyframes syncPulseDot {
                    from { opacity: 0.4; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1.1); }
                }
                .sync-modal-backdrop {
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
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
                    animation: syncFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                }
                @keyframes syncFade {
                    from { opacity: 0; transform: scale(0.96) translateY(8px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
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
                @keyframes syncSpin { to { transform: rotate(360deg); } }
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
            `;
            document.head.appendChild(style);
        }

        // Injeta botão na barra lateral se não existir
        if (!footer.querySelector('.btn-sync-sidebar')) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-sync-sidebar js-btn-sync';
            btn.title = 'Sincronizar com a Nuvem Neon';
            btn.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                    </svg>
                    <span>Sincronizar Nuvem</span>
                </div>
                <span class="sync-indicator-dot js-sync-dot" title="Status da Nuvem"></span>
            `;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openSyncModalGlobal();
            });

            var userInfo = footer.querySelector('.user-info');
            if (userInfo) {
                footer.insertBefore(btn, userInfo);
            } else {
                footer.prepend(btn);
            }
        }

        // Injeta modal no body se não existir
        if (!document.getElementById('syncModalBackdropGlobal')) {
            var modalDiv = document.createElement('div');
            modalDiv.id = 'syncModalBackdropGlobal';
            modalDiv.className = 'sync-modal-backdrop';
            modalDiv.style.display = 'none';
            modalDiv.innerHTML = `
                <div class="sync-modal-card" role="dialog" aria-modal="true">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #1e293b;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #fff;">Sincronização Nuvem</h3>
                                <p id="syncStatusDescGlobal" style="margin: 0; font-size: 0.8rem; color: #94a3b8;">Verificando conexão com a nuvem Neon...</p>
                            </div>
                        </div>
                        <button type="button" class="js-close-sync-modal" style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                    <div style="padding: 1.25rem 1.5rem;">
                        <div id="syncAlertGlobal" class="sync-status-alert" style="display: none;"></div>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button type="button" class="js-sync-action" data-direction="both" style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border-radius: 10px; border: 1px solid rgba(56, 189, 248, 0.4); background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(99, 102, 241, 0.18)); color: #f8fafc; cursor: pointer; text-align: left; transition: all 0.15s ease;">
                                <div style="color: #38bdf8;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="m21 8-4-4-4 4"/><path d="M17 4v16"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="font-size: 0.875rem; font-weight: 600;">Sincronização Completa (2 Vias)</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Puxa novidades do Bot/Nuvem e envia alterações locais</div>
                                </div>
                            </button>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <button type="button" class="js-sync-action" data-direction="pull" style="display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem; border-radius: 10px; border: 1px solid #1e293b; background: #1e293b; color: #f8fafc; cursor: pointer; text-align: left;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <div>
                                        <div style="font-size: 0.8rem; font-weight: 600;">Puxar (Pull)</div>
                                        <div style="font-size: 0.7rem; color: #94a3b8;">Nuvem ➔ Local</div>
                                    </div>
                                </button>
                                <button type="button" class="js-sync-action" data-direction="push" style="display: flex; align-items: center; gap: 0.65rem; padding: 0.75rem; border-radius: 10px; border: 1px solid #1e293b; background: #1e293b; color: #f8fafc; cursor: pointer; text-align: left;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <div>
                                        <div style="font-size: 0.8rem; font-weight: 600;">Enviar (Push)</div>
                                        <div style="font-size: 0.7rem; color: #94a3b8;">Local ➔ Nuvem</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <div id="syncProgressGlobal" style="display: none; margin-top: 1rem; text-align: center; padding: 1rem; background: rgba(255,255,255,0.04); border-radius: 8px;">
                            <div class="sync-spinner"></div>
                            <p id="syncProgressMsgGlobal" style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #cbd5e1;">Sincronizando com a nuvem Neon...</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1.5rem; background: rgba(15, 23, 42, 0.7); border-top: 1px solid #1e293b;">
                        <span id="syncLastTimeGlobal" style="font-size: 0.75rem; color: #64748b;">Último sync: nunca</span>
                        <button type="button" class="js-close-sync-modal" style="background: #1e293b; color: #cbd5e1; border: 1px solid #334155; padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; cursor: pointer;">Fechar</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modalDiv);

            modalDiv.addEventListener('click', function (e) {
                if (e.target === modalDiv) closeSyncModalGlobal();
            });

            modalDiv.querySelectorAll('.js-close-sync-modal').forEach(function (btn) {
                btn.addEventListener('click', closeSyncModalGlobal);
            });

            modalDiv.querySelectorAll('.js-sync-action').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var dir = btn.getAttribute('data-direction');
                    executeSyncAction(dir);
                });
            });
        }

        // Checagem de status inicial silenciosa
        fetchSyncStatusGlobal();
    }

    function openSyncModalGlobal() {
        var modal = document.getElementById('syncModalBackdropGlobal');
        if (modal) {
            modal.style.display = 'flex';
            fetchSyncStatusGlobal();
        }
    }

    function closeSyncModalGlobal() {
        var modal = document.getElementById('syncModalBackdropGlobal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function fetchSyncStatusGlobal() {
        fetch('/sync/status', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var dots = document.querySelectorAll('.js-sync-dot');
            var desc = document.getElementById('syncStatusDescGlobal');
            var lastTime = document.getElementById('syncLastTimeGlobal');

            if (res.success && res.data) {
                var d = res.data;
                if (d.cloud_online) {
                    dots.forEach(function (dot) { dot.className = 'sync-indicator-dot js-sync-dot'; });
                    if (desc) desc.innerHTML = '🟢 Neon Online (AWS sa-east-1) · Local: ' + d.local_driver;
                } else {
                    dots.forEach(function (dot) { dot.className = 'sync-indicator-dot offline js-sync-dot'; });
                    if (desc) desc.innerHTML = '🔴 Neon Offline · ' + (d.cloud_error || 'Sem conexão');
                }

                if (d.last_sync_at && lastTime) {
                    var dt = new Date(d.last_sync_at);
                    lastTime.innerText = 'Último sync: ' + dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' (' + dt.toLocaleDateString() + ')';
                }
            }
        })
        .catch(function () {});
    }

    function executeSyncAction(direction) {
        var progress = document.getElementById('syncProgressGlobal');
        var progressMsg = document.getElementById('syncProgressMsgGlobal');
        var alertBox = document.getElementById('syncAlertGlobal');
        var actionBtns = document.querySelectorAll('.js-sync-action');
        var dots = document.querySelectorAll('.js-sync-dot');

        if (progress) progress.style.display = 'block';
        if (progressMsg) {
            if (direction === 'pull') progressMsg.innerText = 'Baixando novidades da nuvem para o banco local...';
            else if (direction === 'push') progressMsg.innerText = 'Enviando alterações do banco local para a nuvem...';
            else progressMsg.innerText = 'Executando sincronização bidirecional completa...';
        }
        if (alertBox) alertBox.style.display = 'none';
        dots.forEach(function (dot) { dot.className = 'sync-indicator-dot syncing js-sync-dot'; });
        actionBtns.forEach(function (b) { b.disabled = true; b.style.opacity = '0.5'; });

        var token = getCsrfToken();

        fetch('/sync/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
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
            if (progress) progress.style.display = 'none';
            actionBtns.forEach(function (b) { b.disabled = false; b.style.opacity = '1'; });

            if (result.ok && result.data.success) {
                if (alertBox) {
                    alertBox.className = 'sync-status-alert success';
                    alertBox.innerText = result.data.message || 'Sincronização realizada com sucesso!';
                    alertBox.style.display = 'block';
                }
                dots.forEach(function (dot) { dot.className = 'sync-indicator-dot js-sync-dot'; });

                if (typeof window.notifyDataUpdated === 'function') {
                    window.notifyDataUpdated();
                }

                setTimeout(function () {
                    window.location.reload();
                }, 1600);
            } else {
                if (alertBox) {
                    alertBox.className = 'sync-status-alert error';
                    alertBox.innerText = (result.data && result.data.message) ? result.data.message : 'Falha na sincronização.';
                    alertBox.style.display = 'block';
                }
                dots.forEach(function (dot) { dot.className = 'sync-indicator-dot offline js-sync-dot'; });
            }
        })
        .catch(function (err) {
            if (progress) progress.style.display = 'none';
            actionBtns.forEach(function (b) { b.disabled = false; b.style.opacity = '1'; });
            if (alertBox) {
                alertBox.className = 'sync-status-alert error';
                alertBox.innerText = 'Erro na requisição: ' + err.message;
                alertBox.style.display = 'block';
            }
            dots.forEach(function (dot) { dot.className = 'sync-indicator-dot offline js-sync-dot'; });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCloudSync);
    } else {
        initCloudSync();
    }
})();
