// Service worker do módulo de estudos (escopo /estudos).
// - Casca /estudos: rede primeiro com timeout curto (Render pode estar hibernando); sem rede, usa o cache.
// - Arquivos do Vite (/build/assets/*, nomes com hash): cache primeiro.
// - /estudos/sync e /estudos/token: sempre rede (nunca em cache).
// A versão vem da query string (?v=hash do manifest do Vite): um build novo instala um SW novo.

const VERSION = new URL(self.location.href).searchParams.get('v') || 'dev';
const CACHE = `estudos-${VERSION}`;
const SHELL = '/estudos';
const STATIC = ['/estudos.webmanifest', '/img/estudos/icon-192.png', '/img/estudos/icon-512.png'];
const NETWORK_TIMEOUT_MS = 3000;

async function precache() {
    const cache = await caches.open(CACHE);
    const shell = await fetch(SHELL, { credentials: 'same-origin', cache: 'no-store' });
    if (!shell.ok || shell.redirected) {
        // Sem login (ou fora da nuvem): instala mesmo assim; a casca entra no cache na próxima visita.
        await cache.addAll(STATIC);
        return;
    }
    const html = await shell.clone().text();
    const assets = [...new Set([...html.matchAll(/\/build\/assets\/[^"'\s)]+/g)].map((m) => m[0]))];
    await cache.put(SHELL, shell);
    await cache.addAll([...assets, ...STATIC]);
}

self.addEventListener('install', (event) => {
    event.waitUntil(precache().then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => k.startsWith('estudos-') && k !== CACHE).map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

function withTimeout(promise, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), ms);
        promise.then((value) => { clearTimeout(timer); resolve(value); }, (error) => { clearTimeout(timer); reject(error); });
    });
}

async function shellResponse(request) {
    const cache = await caches.open(CACHE);
    const network = fetch(request);
    try {
        const response = await withTimeout(network, NETWORK_TIMEOUT_MS);
        if (response.ok && !response.redirected) {
            await cache.put(SHELL, response.clone());
            return response;
        }
        // Redirecionou para o login: mostra o login (precisa de rede para entrar).
        if (response.redirected) return response;
        return (await cache.match(SHELL)) ?? response;
    } catch {
        const cached = await cache.match(SHELL);
        if (cached) {
            // Atualiza a casca em segundo plano quando a rede responder.
            network.then((r) => r.ok && !r.redirected && cache.put(SHELL, r)).catch(() => {});
            return cached;
        }
        return network;
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    return response;
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate' && (url.pathname === SHELL || url.pathname === SHELL + '/')) {
        event.respondWith(shellResponse(request));
    } else if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/bosses/') || STATIC.includes(url.pathname)) {
        event.respondWith(cacheFirst(request));
    }
});
