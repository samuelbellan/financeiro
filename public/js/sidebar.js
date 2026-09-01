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
})();
