// Motor de métricas do módulo de estudos (funções puras, sem DOM nem IndexedDB).
//
// Regras:
// - O "dia de estudo" vai de virada_hora (local, no fuso das configurações) até virada_hora do dia seguinte.
// - Sessões são gravadas inteiras; o corte na virada acontece aqui, na leitura.
// - Só contam sessões não excluídas e com status 'ok'. Sessão em andamento conta até "agora".
// - Sessões manuais contam no total, matérias, heatmap e dias estudados,
//   mas não no máximo de foco nem nos horários de início/término.

const MIN = 60_000;
const HOUR = 60 * MIN;
const DAY = 24 * HOUR;

export const DEFAULT_SETTINGS = Object.freeze({
    virada_hora: 3,
    limite_suspeita_min: 180,
    meta_diaria_min: null,
    timezone: 'America/Sao_Paulo',
});

const partsFormatters = new Map();

function partsFormatter(timezone) {
    if (!partsFormatters.has(timezone)) {
        partsFormatters.set(timezone, new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            hourCycle: 'h23',
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        }));
    }
    return partsFormatters.get(timezone);
}

/** Relógio de parede (no fuso) do instante, expresso como ms "UTC ingênuo". */
function wallClock(ms, timezone) {
    const p = {};
    for (const { type, value } of partsFormatter(timezone).formatToParts(new Date(ms))) {
        p[type] = value;
    }
    return Date.UTC(+p.year, +p.month - 1, +p.day, +p.hour, +p.minute, +p.second);
}

/** Converte um relógio de parede no fuso para o instante UTC correspondente. */
function wallToInstant(wallMs, timezone) {
    let guess = wallMs;
    for (let i = 0; i < 3; i++) {
        guess = wallMs - (wallClock(guess, timezone) - guess);
    }
    return guess;
}

/** "YYYY-MM-DDTHH:MM" (relógio do fuso) → ms UTC. Usado pelos campos datetime-local. */
export function localInputToMs(value, timezone) {
    const [date, time] = value.split('T');
    const [y, m, d] = date.split('-').map(Number);
    const [hh, mm] = time.split(':').map(Number);
    return wallToInstant(Date.UTC(y, m - 1, d, hh, mm), timezone);
}

/** ms UTC → "YYYY-MM-DDTHH:MM" no relógio do fuso. */
export function msToLocalInput(ms, timezone) {
    return new Date(wallClock(ms, timezone)).toISOString().slice(0, 16);
}

export function toMs(value) {
    return typeof value === 'number' ? value : Date.parse(value);
}

export function addDays(day, n) {
    const [y, m, d] = day.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d + n)).toISOString().slice(0, 10);
}

export function daysBetween(fromDay, toDay) {
    return Math.round((Date.parse(toDay + 'T00:00:00Z') - Date.parse(fromDay + 'T00:00:00Z')) / DAY);
}

/** Dia de estudo (YYYY-MM-DD) ao qual o instante pertence. */
export function studyDayOf(ms, settings = DEFAULT_SETTINGS) {
    const wall = wallClock(toMs(ms), settings.timezone) - settings.virada_hora * HOUR;
    return new Date(wall).toISOString().slice(0, 10);
}

/** [início, fim) do dia de estudo, em ms UTC. */
export function dayBounds(day, settings = DEFAULT_SETTINGS) {
    const [y, m, d] = day.split('-').map(Number);
    const start = wallToInstant(Date.UTC(y, m - 1, d, settings.virada_hora), settings.timezone);
    const end = wallToInstant(Date.UTC(y, m - 1, d + 1, settings.virada_hora), settings.timezone);
    return [start, end];
}

export function isCountable(session) {
    return !session.deleted_at && session.status === 'ok';
}

export function sessionEnd(session, nowMs) {
    return session.fim ? toMs(session.fim) : nowMs;
}

/** Sessão em andamento há mais que o limite configurado. */
export function isSuspicious(session, settings, nowMs) {
    return !session.fim && !session.deleted_at && nowMs - toMs(session.inicio) > settings.limite_suspeita_min * MIN;
}

/** Corta a sessão nas viradas de dia. Retorna pedaços { day, start, end, session }. */
export function splitSession(session, settings, nowMs) {
    const pieces = [];
    const end = sessionEnd(session, nowMs);
    let cursor = toMs(session.inicio);
    while (cursor < end) {
        const day = studyDayOf(cursor, settings);
        const [, dayEnd] = dayBounds(day, settings);
        const pieceEnd = Math.min(end, dayEnd);
        pieces.push({ day, start: cursor, end: pieceEnd, session });
        cursor = pieceEnd;
    }
    return pieces;
}

function emptyDay(day) {
    return { day, totalMs: 0, maxFocusMs: 0, startMs: null, endMs: null, bySubject: {}, pieces: [] };
}

/** Estatísticas por dia de estudo. Retorna Map<day, stats>. */
export function buildDaily(sessions, settings, nowMs) {
    const daily = new Map();
    for (const session of sessions) {
        if (!isCountable(session)) continue;
        for (const piece of splitSession(session, settings, nowMs)) {
            const stats = daily.get(piece.day) ?? emptyDay(piece.day);
            const duration = piece.end - piece.start;
            stats.totalMs += duration;
            stats.bySubject[session.subject_id] = (stats.bySubject[session.subject_id] ?? 0) + duration;
            stats.pieces.push(piece);
            if (session.origem === 'timer') {
                stats.maxFocusMs = Math.max(stats.maxFocusMs, duration);
                stats.startMs = stats.startMs === null ? piece.start : Math.min(stats.startMs, piece.start);
                stats.endMs = stats.endMs === null ? piece.end : Math.max(stats.endMs, piece.end);
            }
            daily.set(piece.day, stats);
        }
    }
    return daily;
}

export function dayStats(daily, day) {
    return daily.get(day) ?? emptyDay(day);
}

/** Dias consecutivos com estudo. Hoje ainda sem estudo não quebra a sequência de ontem. */
export function streak(daily, today) {
    let day = (daily.get(today)?.totalMs ?? 0) > 0 ? today : addDays(today, -1);
    let count = 0;
    while ((daily.get(day)?.totalMs ?? 0) > 0) {
        count++;
        day = addDays(day, -1);
    }
    return count;
}

/**
 * Agregado de um intervalo [fromDay, toDay]. A média diária considera só os dias já decorridos
 * (até `today`), para a semana atual não ser diluída por dias que ainda não chegaram.
 */
export function rangeStats(daily, fromDay, toDay, today) {
    const lastDay = today < toDay ? today : toDay;
    const elapsedDays = Math.max(0, daysBetween(fromDay, lastDay) + 1);
    const result = { fromDay, toDay, totalMs: 0, bySubject: {}, daysStudied: 0, maxFocusMs: 0, avgPerDayMs: 0, days: [] };
    for (let day = fromDay; day <= toDay; day = addDays(day, 1)) {
        const stats = dayStats(daily, day);
        result.days.push(stats);
        result.totalMs += stats.totalMs;
        result.maxFocusMs = Math.max(result.maxFocusMs, stats.maxFocusMs);
        if (stats.totalMs > 0) result.daysStudied++;
        for (const [subjectId, ms] of Object.entries(stats.bySubject)) {
            result.bySubject[subjectId] = (result.bySubject[subjectId] ?? 0) + ms;
        }
    }
    result.avgPerDayMs = elapsedDays > 0 ? result.totalMs / elapsedDays : 0;
    return result;
}

/** Semana (segunda a domingo) que contém o dia. */
export function weekRange(day) {
    const weekday = (new Date(day + 'T00:00:00Z').getUTCDay() + 6) % 7;
    const from = addDays(day, -weekday);
    return [from, addDays(from, 6)];
}

export function monthRange(day) {
    const from = day.slice(0, 8) + '01';
    const [y, m] = day.split('-').map(Number);
    const to = new Date(Date.UTC(y, m, 0)).toISOString().slice(0, 10);
    return [from, to];
}

/**
 * Grade de 10 minutos do dia (144 blocos a partir da virada).
 * Cada bloco recebe a matéria com mais tempo dentro dele: { subjectId, manual } ou null.
 */
export function timelineSlots(stats, settings, slotMinutes = 10) {
    const [dayStart, dayEnd] = dayBounds(stats.day, settings);
    const slotMs = slotMinutes * MIN;
    const count = Math.round((dayEnd - dayStart) / slotMs);
    const occupancy = Array.from({ length: count }, () => new Map());
    for (const piece of stats.pieces) {
        const first = Math.max(0, Math.floor((piece.start - dayStart) / slotMs));
        const last = Math.min(count - 1, Math.floor((piece.end - 1 - dayStart) / slotMs));
        for (let i = first; i <= last; i++) {
            const slotStart = dayStart + i * slotMs;
            const overlap = Math.min(piece.end, slotStart + slotMs) - Math.max(piece.start, slotStart);
            if (overlap <= 0) continue;
            const key = piece.session.subject_id + (piece.session.origem === 'manual' ? '|m' : '');
            occupancy[i].set(key, (occupancy[i].get(key) ?? 0) + overlap);
        }
    }
    return occupancy.map((slot) => {
        let best = null;
        let bestMs = 0;
        for (const [key, ms] of slot) {
            if (ms > bestMs) { best = key; bestMs = ms; }
        }
        if (!best) return null;
        const [subjectId, flag] = best.split('|');
        return { subjectId, manual: flag === 'm' };
    });
}

/** Sessões (não excluídas) que se sobrepõem ao intervalo [inicio, fim). */
export function findOverlaps(candidate, sessions, nowMs) {
    const start = toMs(candidate.inicio);
    const end = sessionEnd(candidate, nowMs);
    return sessions.filter((other) =>
        other.id !== candidate.id
        && !other.deleted_at
        && toMs(other.inicio) < end
        && sessionEnd(other, nowMs) > start
    );
}

/** Nível de cor do heatmap (0–4). */
export function heatLevel(totalMs) {
    if (totalMs <= 0) return 0;
    if (totalMs < 2 * HOUR) return 1;
    if (totalMs < 4 * HOUR) return 2;
    if (totalMs < 6 * HOUR) return 3;
    return 4;
}

export function daysUntil(targetDay, today) {
    return daysBetween(today, targetDay);
}

export function formatDuration(ms, { seconds = false } = {}) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return seconds ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${h}h${pad(m)}`;
}

/** Hora local (HH:MM) do instante no fuso; marca "(+1)" se cair no dia civil seguinte ao dia de estudo. */
export function formatClock(ms, day, settings) {
    const wall = new Date(wallClock(ms, settings.timezone));
    const hh = String(wall.getUTCHours()).padStart(2, '0');
    const mm = String(wall.getUTCMinutes()).padStart(2, '0');
    const nextDay = wall.toISOString().slice(0, 10) > day;
    return `${hh}:${mm}${nextDay ? ' (+1)' : ''}`;
}
