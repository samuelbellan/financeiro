// Sincronização com o servidor: fila local → POST /estudos/sync → aplica mudanças do servidor.
// Gatilhos: abrir o app, voltar a conexão, 5 s após uma alteração e a cada 5 min
// (a cada 60 s com o widget flutuante aberto, mesmo com o app minimizado).

import { applyServerChanges, dropFromQueue, getMeta, pendingQueue, setMeta } from './db.js';

const BACKOFF_MS = [10_000, 30_000, 120_000, 300_000];
const PERIODIC_MS = 5 * 60_000;
const FAST_PERIODIC_MS = 60_000;
const DEBOUNCE_MS = 5_000;
// Itens por envio (o servidor aceita até 1000 por tipo). Matérias vão primeiro: o servidor valida subject_id.
// Após uma falha o lote cai pela metade (até MIN_BATCH_SIZE), para um lote pesado não travar a fila para sempre.
const BATCH_SIZE = 100;
const MIN_BATCH_SIZE = 10;
const TYPE_ORDER = { subjects: 0, settings: 1, ddays: 2, sessions: 3 };

export function createSync({ syncUrl, tokenUrl, initialToken, onStatus, onApplied }) {
    let csrfToken = initialToken;
    let running = false;
    let again = false;
    let failures = 0;
    let batchSize = BATCH_SIZE;
    let retryTimer = null;
    let debounceTimer = null;
    let periodicTimer = null;
    let fast = false;

    const status = { state: 'idle', pending: 0, lastSyncAt: null, rejected: [], conflicts: [], message: '' };

    function emit(patch) {
        Object.assign(status, patch);
        onStatus({ ...status });
    }

    async function refreshToken() {
        const response = await fetch(tokenUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (response.status === 401) throw Object.assign(new Error('login'), { status: 401 });
        if (!response.ok) throw Object.assign(new Error(`HTTP ${response.status}`), { status: response.status });
        csrfToken = (await response.json()).token;
    }

    async function post(body) {
        return fetch(syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
    }

    async function runOnce() {
        const pending = await pendingQueue();
        emit({ state: 'syncing', pending: pending.length });

        const queue = pending
            .sort((a, b) => TYPE_ORDER[a.type] - TYPE_ORDER[b.type])
            .slice(0, batchSize);
        const changes = { subjects: [], sessions: [], ddays: [], settings: null };
        for (const entry of queue) {
            if (entry.type === 'settings') changes.settings = entry.record;
            else changes[entry.type].push(entry.record);
        }

        const body = {
            device_id: await getMeta('device_id'),
            cursor: await getMeta('cursor'),
            changes,
        };

        let response = await post(body);
        if (response.status === 419) {
            await refreshToken();
            response = await post(body);
        }
        if (response.status === 401) throw Object.assign(new Error('login'), { status: 401 });
        if (!response.ok) throw Object.assign(new Error(`HTTP ${response.status}`), { status: response.status });

        const result = await response.json();
        await applyServerChanges(queue, result.changes);
        await setMeta('cursor', result.cursor);

        const rejectedKeys = (result.rejected ?? [])
            .map((r) => (r.type === 'settings' ? 'settings' : `${r.type}:${r.id}`));
        if (rejectedKeys.length) await dropFromQueue(rejectedKeys);

        const remaining = (await pendingQueue()).length;
        emit({
            state: 'ok',
            pending: remaining,
            lastSyncAt: Date.now(),
            rejected: result.rejected ?? [],
            conflicts: result.conflicts ?? [],
            message: '',
        });
        onApplied(result);
        return { remaining, sent: queue.length };
    }

    async function syncNow() {
        if (running) { again = true; return; }
        if (!navigator.onLine) {
            emit({ state: 'offline', pending: (await pendingQueue()).length });
            return;
        }
        running = true;
        clearTimeout(retryTimer);
        try {
            do {
                again = false;
                const { remaining, sent } = await runOnce();
                // Fila grande (ex.: importação): continua enviando em lotes enquanto houver progresso.
                if (remaining > 0 && sent > 0) again = true;
            } while (again);
            failures = 0;
            batchSize = BATCH_SIZE;
        } catch (error) {
            if (error.status === 401) {
                emit({ state: 'login', message: 'Entre novamente para sincronizar.' });
            } else {
                const wait = BACKOFF_MS[Math.min(failures, BACKOFF_MS.length - 1)];
                failures++;
                batchSize = Math.max(MIN_BATCH_SIZE, Math.floor(batchSize / 2));
                const cause = error.status ? `HTTP ${error.status}` : 'sem resposta do servidor';
                emit({ state: 'error', message: `Falha ao sincronizar (${cause}). Nova tentativa em ${Math.round(wait / 1000)} s.` });
                retryTimer = setTimeout(syncNow, wait);
            }
        } finally {
            running = false;
        }
    }

    function schedule() {
        clearTimeout(debounceTimer);
        pendingQueue().then((q) => emit({ pending: q.length, state: status.state === 'ok' ? 'pending' : status.state }));
        debounceTimer = setTimeout(syncNow, DEBOUNCE_MS);
    }

    function start() {
        window.addEventListener('online', syncNow);
        window.addEventListener('offline', () => emit({ state: 'offline' }));
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') syncNow();
        });
        setFast(false);
        syncNow();
    }

    // Modo rápido (widget aberto): sincroniza a cada 60 s mesmo com a página escondida.
    function setFast(on) {
        fast = on;
        clearInterval(periodicTimer);
        periodicTimer = setInterval(
            () => (fast || document.visibilityState === 'visible') && syncNow(),
            fast ? FAST_PERIODIC_MS : PERIODIC_MS,
        );
    }

    return { start, syncNow, schedule, setFast, status: () => ({ ...status }) };
}
