// Armazenamento local (IndexedDB) do módulo de estudos.
// Cada alteração local grava o registro e o coloca na fila de sincronização (uma entrada por registro).

import { openDB } from 'idb';
import { DEFAULT_SETTINGS } from './metrics.js';

const DB_NAME = 'estudos';
const STORES = ['subjects', 'sessions', 'ddays'];

let dbPromise;

function db() {
    dbPromise ??= openDB(DB_NAME, 1, {
        upgrade(database) {
            for (const store of STORES) {
                database.createObjectStore(store, { keyPath: 'id' });
            }
            database.createObjectStore('meta');
            database.createObjectStore('queue');
        },
    });
    return dbPromise;
}

export function uuid() {
    if (crypto.randomUUID) return crypto.randomUUID();
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export async function getMeta(key, fallback = null) {
    return (await (await db()).get('meta', key)) ?? fallback;
}

export async function setMeta(key, value) {
    await (await db()).put('meta', value, key);
}

/** Garante que os dados locais pertencem ao usuário logado; se outro usuário entrar no aparelho, limpa tudo. */
export async function ensureUser(userId) {
    const current = await getMeta('user_id');
    if (current !== null && current !== userId) {
        const database = await db();
        const tx = database.transaction([...STORES, 'meta', 'queue'], 'readwrite');
        await Promise.all([...STORES, 'meta', 'queue'].map((store) => tx.objectStore(store).clear()));
        await tx.done;
    }
    await setMeta('user_id', userId);
    if (!(await getMeta('device_id'))) {
        await setMeta('device_id', uuid());
    }
}

export async function loadAll() {
    const database = await db();
    const [subjects, sessions, ddays, settings] = await Promise.all([
        database.getAll('subjects'),
        database.getAll('sessions'),
        database.getAll('ddays'),
        getMeta('settings'),
    ]);
    // Sem configurações salvas: usa o fuso do aparelho (não é gravado até o usuário salvar as preferências,
    // para não sobrescrever as configurações que vierem de outro aparelho).
    const deviceTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || DEFAULT_SETTINGS.timezone;
    return { subjects, sessions, ddays, settings: { ...DEFAULT_SETTINGS, timezone: deviceTimezone, ...(settings ?? {}) } };
}

/** Grava uma alteração local e a enfileira para sincronizar. */
export async function saveLocal(type, record) {
    const database = await db();
    if (type === 'settings') {
        const tx = database.transaction(['meta', 'queue'], 'readwrite');
        tx.objectStore('meta').put(record, 'settings');
        tx.objectStore('queue').put({ type, record }, 'settings');
        await tx.done;
        return;
    }
    const tx = database.transaction([type, 'queue'], 'readwrite');
    tx.objectStore(type).put(record);
    tx.objectStore('queue').put({ type, record }, `${type}:${record.id}`);
    await tx.done;
}

/**
 * Grava vários registros numa única transação (tudo ou nada) e os enfileira.
 * Usado na importação: milhares de gravações separadas demoravam e, se a página fosse fechada no meio,
 * parte do arquivo ficava de fora (as exclusões já tinham sido feitas).
 */
export async function saveLocalMany(entries) {
    if (!entries.length) return;
    const database = await db();
    const types = [...new Set(entries.map((e) => e.type))];
    const tx = database.transaction([...types, 'queue'], 'readwrite');
    for (const { type, record } of entries) {
        tx.objectStore(type).put(record);
        tx.objectStore('queue').put({ type, record }, `${type}:${record.id}`);
    }
    await tx.done;
}

export async function pendingQueue() {
    const database = await db();
    const [keys, values] = await Promise.all([database.getAllKeys('queue'), database.getAll('queue')]);
    return keys.map((key, i) => ({ key, ...values[i] }));
}

/**
 * Aplica a resposta do servidor: remove da fila o que foi enviado (se não mudou de novo enquanto
 * o sync estava em voo) e grava as versões do servidor, exceto as que têm alteração local pendente.
 */
export async function applyServerChanges(sent, changes) {
    const database = await db();
    const tx = database.transaction([...STORES, 'meta', 'queue'], 'readwrite');
    const queue = tx.objectStore('queue');

    for (const entry of sent) {
        const current = await queue.get(entry.key);
        if (current && JSON.stringify(current.record) === JSON.stringify(entry.record)) {
            await queue.delete(entry.key);
        }
    }

    for (const type of STORES) {
        for (const record of changes[type] ?? []) {
            if (await queue.get(`${type}:${record.id}`)) continue;
            await tx.objectStore(type).put(record);
        }
    }
    if (changes.settings && !(await queue.get('settings'))) {
        const { user_id, ...settings } = changes.settings;
        await tx.objectStore('meta').put(settings, 'settings');
    }
    await tx.done;
}

/** Descarta da fila itens rejeitados pelo servidor (não adianta reenviar). */
export async function dropFromQueue(keys) {
    const database = await db();
    const tx = database.transaction('queue', 'readwrite');
    await Promise.all(keys.map((key) => tx.store.delete(key)));
    await tx.done;
}
