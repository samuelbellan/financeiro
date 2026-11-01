// Salas de estudo (estilo YPT): chamadas ao servidor e o tempo "ao vivo" de cada membro.
// Diferente do resto do módulo, precisa de conexão: sem rede a tela mostra o último estado buscado.

/**
 * @param {{ baseUrl: string, tokenUrl: string, initialToken: string }} options
 * Toda chamada devolve a visão atualizada das salas:
 * { server_time, pode_gerenciar, rooms: [{ id, nome, codigo, dono, membros }] }.
 */
export function createRoomsApi({ baseUrl, tokenUrl, initialToken }) {
    let token = initialToken;

    async function call(path = '', { method = 'GET', body } = {}) {
        const send = () => fetch(baseUrl + path, {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: body ? JSON.stringify(body) : undefined,
        });
        let response = await send();
        if (response.status === 419) {
            const fresh = await fetch(tokenUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (fresh.ok) token = (await fresh.json()).token;
            response = await send();
        }
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const first = Object.values(data.errors ?? {})[0]?.[0];
            throw new Error(first || data.message || `Erro ${response.status}`);
        }
        return data;
    }

    return {
        list: () => call(),
        create: (nome) => call('', { method: 'POST', body: { nome } }),
        join: (codigo) => call('/entrar', { method: 'POST', body: { codigo } }),
        leave: (roomId) => call(`/${roomId}/sair`, { method: 'POST' }),
        // Só gestores:
        remove: (roomId, userId) => call(`/${roomId}/remover/${userId}`, { method: 'POST' }),
        destroy: (roomId) => call(`/${roomId}`, { method: 'DELETE' }),
    };
}

/** Tempo de hoje do membro agora: o valor do servidor mais o que passou desde a busca, se estiver estudando. */
export function memberTodayMs(member, fetchedAtMs, nowMs) {
    return member.hoje_ms + (member.estudando_desde ? Math.max(0, nowMs - fetchedAtMs) : 0);
}

/** Sessão em andamento do membro agora (ms), ou null. */
export function memberSessionMs(member, serverTimeMs, fetchedAtMs, nowMs) {
    if (!member.estudando_desde) return null;
    return Math.max(0, serverTimeMs - Date.parse(member.estudando_desde) + (nowMs - fetchedAtMs));
}
