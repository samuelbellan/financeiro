// Importação de backup/histórico (mesmo formato do "Exportar backup").
// Função pura: decide o que gravar; quem chama grava os registros e a sincronização envia ao servidor.
//
// Regras:
// - Matérias com o mesmo nome (sem diferenciar maiúsculas/acentos) de uma matéria local são reaproveitadas,
//   e as sessões importadas passam a apontar para a matéria local.
// - Registros cujo id já existe no aparelho são ignorados (reimportar o mesmo arquivo não duplica nada).
// - Registros excluídos no arquivo não são importados. Preferências do arquivo não são aplicadas.
// - Sessão excluída no arquivo cujo id existe (ativo) no aparelho é excluída também: assim um arquivo de
//   correção consegue trocar sessões importadas antes (ex.: estimadas) pelas reais.
// - Arquivo de substituição: com `substituir: { desde, ate }` (instantes ISO), toda sessão encerrada do aparelho,
//   de qualquer origem (YPT, cronômetro, manual), com início em [desde, ate) e que não esteja no arquivo é
//   excluída. Assim um histórico vindo de outra fonte (ex.: Guruja) toma o lugar do período sem somar horas
//   em dobro. Sessões do arquivo que já existem no aparelho ficam (reimportar não apaga nada).

const MAX_SESSION_MS = 24 * 3_600_000;

const normalize = (name) => String(name ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').trim().toLowerCase();

const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/**
 * O servidor só aceita UUIDs. Ids de outro formato viram um UUID determinístico (mesmo id → mesmo UUID),
 * para que reimportar o mesmo arquivo continue sem duplicar registros.
 */
export function toUuid(id) {
    const text = String(id);
    if (UUID_RE.test(text)) return text.toLowerCase();
    let hex = '';
    for (let seed = 0; hex.length < 32; seed++) {
        let h = 0x811c9dc5 ^ seed;
        for (let i = 0; i < text.length; i++) {
            h = Math.imul(h ^ text.charCodeAt(i), 0x01000193);
        }
        hex += (h >>> 0).toString(16).padStart(8, '0');
    }
    const variant = ((parseInt(hex[16], 16) & 0x3) | 0x8).toString(16);
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-4${hex.slice(13, 16)}-${variant}${hex.slice(17, 20)}-${hex.slice(20, 32)}`;
}

export function planImport(backup, local) {
    if (!backup || backup.app !== 'estudos' || !Array.isArray(backup.subjects) || !Array.isArray(backup.sessions)) {
        throw new Error('Arquivo inválido: não é um backup do módulo de estudos.');
    }

    const localIds = {
        subjects: new Set(local.subjects.map((s) => s.id)),
        sessions: new Set(local.sessions.map((s) => s.id)),
        ddays: new Set(local.ddays.map((d) => d.id)),
    };
    const localByName = new Map(
        local.subjects.filter((s) => !s.deleted_at).map((s) => [normalize(s.nome), s.id]),
    );
    let nextOrder = Math.max(-1, ...local.subjects.map((s) => s.ordem ?? 0)) + 1;

    const plan = { subjects: [], sessions: [], ddays: [], removals: [], skipped: 0, invalid: 0, replace: null };
    const localSessions = new Map(local.sessions.map((s) => [s.id, s]));
    const subjectMap = new Map();

    for (const subject of backup.subjects) {
        if (!subject?.id || subject.deleted_at) continue;
        const id = toUuid(subject.id);
        const existing = localByName.get(normalize(subject.nome));
        if (existing) {
            subjectMap.set(subject.id, existing);
            continue;
        }
        subjectMap.set(subject.id, id);
        if (localIds.subjects.has(id)) continue;
        if (!/^#[0-9a-fA-F]{6}$/.test(subject.cor ?? '') || !String(subject.nome ?? '').trim()) {
            plan.invalid++;
            continue;
        }
        plan.subjects.push({
            id,
            nome: String(subject.nome).trim().slice(0, 60),
            cor: subject.cor.toUpperCase(),
            ordem: nextOrder++,
            arquivada_em: subject.arquivada_em ?? null,
            deleted_at: null,
            created_at: subject.created_at ?? new Date().toISOString(),
        });
        localByName.set(normalize(subject.nome), id);
    }

    for (const session of backup.sessions) {
        if (!session?.id) continue;
        const id = toUuid(session.id);
        if (session.deleted_at) {
            const existing = localSessions.get(id);
            if (existing && !existing.deleted_at) plan.removals.push(existing);
            continue;
        }
        if (localIds.sessions.has(id)) {
            plan.skipped++;
            continue;
        }
        const subjectId = subjectMap.get(session.subject_id);
        const inicio = Date.parse(session.inicio);
        const fim = Date.parse(session.fim);
        if (!subjectId || !Number.isFinite(inicio) || !Number.isFinite(fim) || fim <= inicio || fim - inicio > MAX_SESSION_MS) {
            plan.invalid++;
            continue;
        }
        plan.sessions.push({
            id,
            subject_id: subjectId,
            inicio: new Date(inicio).toISOString(),
            fim: new Date(fim).toISOString(),
            origem: ['timer', 'manual', 'importado'].includes(session.origem) ? session.origem : 'importado',
            status: 'ok',
            observacao: session.observacao ?? null,
            tipo: session.tipo ?? null,
            device_id: session.device_id ?? null,
            deleted_at: null,
            created_at: session.created_at ?? new Date().toISOString(),
        });
    }

    if (backup.substituir) {
        const desde = Date.parse(backup.substituir.desde);
        const ate = Date.parse(backup.substituir.ate);
        if (!Number.isFinite(desde) || !Number.isFinite(ate) || ate <= desde) {
            throw new Error('Arquivo inválido: período de substituição (substituir.desde/ate) incorreto.');
        }
        plan.replace = { desde: new Date(desde).toISOString(), ate: new Date(ate).toISOString() };
        const inFile = new Set(backup.sessions.filter((s) => s?.id).map((s) => toUuid(s.id)));
        const removing = new Set(plan.removals.map((s) => s.id));
        for (const session of local.sessions) {
            if (session.deleted_at || !session.fim || inFile.has(session.id) || removing.has(session.id)) continue;
            const inicio = Date.parse(session.inicio);
            if (inicio >= desde && inicio < ate) plan.removals.push(session);
        }
    }

    for (const dday of backup.ddays ?? []) {
        if (!dday?.id || dday.deleted_at || localIds.ddays.has(toUuid(dday.id))) continue;
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dday.data ?? '')) {
            plan.invalid++;
            continue;
        }
        plan.ddays.push({ id: toUuid(dday.id), titulo: String(dday.titulo ?? '').slice(0, 80), data: dday.data, deleted_at: null, created_at: dday.created_at ?? new Date().toISOString() });
    }

    return plan;
}
