// Motor de métricas do módulo de estudos (funções puras, sem DOM nem IndexedDB).
//
// Regras:
// - O "dia de estudo" vai de virada_hora (local, no fuso das configurações) até virada_hora do dia seguinte.
// - Sessões são gravadas inteiras; o corte na virada acontece aqui, na leitura.
// - Só contam sessões não excluídas e com status 'ok'. Sessão em andamento conta até "agora".
// - Sessões manuais contam no total, matérias, heatmap e dias estudados,
//   mas não no máximo de foco nem nos horários de início/término.
// - Sessões importadas (histórico do YPT, horários fictícios) contam como as manuais e também
//   ficam fora da linha do tempo e da checagem de sobreposição.

const MIN = 60_000;
const HOUR = 60 * MIN;
const DAY = 24 * HOUR;

export const DEFAULT_SETTINGS = Object.freeze({
    virada_hora: 1,
    limite_suspeita_min: 180,
    meta_diaria_min: null,
    timezone: 'America/Sao_Paulo',
    avatar: 'bichinho',
    retro_sound: false,
    descanso_min: null,
});

/** Tipos de estudo da sessão (mesma lista de StudySyncService::SESSION_TYPES). Sessão sem tipo conta como NO_TYPE. */
export const STUDY_TYPES = Object.freeze([
    { id: 'teoria', nome: 'Teoria / leitura' },
    { id: 'questoes', nome: 'Questões' },
    { id: 'revisao', nome: 'Revisão' },
    { id: 'lei_seca', nome: 'Lei seca' },
    { id: 'videoaula', nome: 'Videoaula' },
    { id: 'resumo', nome: 'Resumo / mapas' },
    { id: 'simulado', nome: 'Simulado' },
    { id: 'outro', nome: 'Outro' },
]);
export const NO_TYPE = 'sem_tipo';

export function studyTypeName(id) {
    return STUDY_TYPES.find((t) => t.id === id)?.nome ?? 'Sem tipo';
}

/** Último tipo usado numa sessão encerrada da matéria (sugestão ao registrar a próxima). */
export function lastTypeFor(sessions, subjectId) {
    let last = null;
    for (const s of sessions) {
        if (s.deleted_at || !s.fim || !s.tipo || s.subject_id !== subjectId) continue;
        if (!last || toMs(s.fim) > toMs(last.fim)) last = s;
    }
    return last?.tipo ?? null;
}

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
    return { day, totalMs: 0, timerMs: 0, maxFocusMs: 0, startMs: null, endMs: null, bySubject: {}, byType: {}, bySubjectType: {}, pieces: [] };
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
            const type = session.tipo || NO_TYPE;
            stats.byType[type] = (stats.byType[type] ?? 0) + duration;
            const subjectTypes = (stats.bySubjectType[session.subject_id] ??= {});
            subjectTypes[type] = (subjectTypes[type] ?? 0) + duration;
            stats.pieces.push(piece);
            if (session.origem === 'timer') {
                stats.timerMs += duration;
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
    const result = { fromDay, toDay, totalMs: 0, bySubject: {}, byType: {}, bySubjectType: {}, daysStudied: 0, maxFocusMs: 0, avgPerDayMs: 0, days: [] };
    for (let day = fromDay; day <= toDay; day = addDays(day, 1)) {
        const stats = dayStats(daily, day);
        result.days.push(stats);
        result.totalMs += stats.totalMs;
        result.maxFocusMs = Math.max(result.maxFocusMs, stats.maxFocusMs);
        if (stats.totalMs > 0) result.daysStudied++;
        for (const [subjectId, ms] of Object.entries(stats.bySubject)) {
            result.bySubject[subjectId] = (result.bySubject[subjectId] ?? 0) + ms;
        }
        for (const [type, ms] of Object.entries(stats.byType)) {
            result.byType[type] = (result.byType[type] ?? 0) + ms;
        }
        for (const [subjectId, types] of Object.entries(stats.bySubjectType)) {
            const target = (result.bySubjectType[subjectId] ??= {});
            for (const [type, ms] of Object.entries(types)) target[type] = (target[type] ?? 0) + ms;
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
        if (piece.session.origem === 'importado') continue;
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

/**
 * Grade estilo YPT: 24 linhas (uma por hora, a partir da virada), cada uma com os trechos estudados
 * na precisão do minuto: { subjectId, manual, start, end } em % da hora (0–100).
 */
export function timelineRows(stats, settings) {
    const [dayStart] = dayBounds(stats.day, settings);
    const rows = Array.from({ length: 24 }, () => []);
    for (const piece of stats.pieces) {
        if (piece.session.origem === 'importado') continue;
        const firstHour = Math.max(0, Math.floor((piece.start - dayStart) / HOUR));
        const lastHour = Math.min(23, Math.floor((piece.end - 1 - dayStart) / HOUR));
        for (let h = firstHour; h <= lastHour; h++) {
            const hourStart = dayStart + h * HOUR;
            const from = Math.max(piece.start, hourStart);
            const to = Math.min(piece.end, hourStart + HOUR);
            if (to <= from) continue;
            rows[h].push({
                subjectId: piece.session.subject_id,
                manual: piece.session.origem === 'manual',
                start: ((from - hourStart) / HOUR) * 100,
                end: ((to - hourStart) / HOUR) * 100,
            });
        }
    }
    return rows;
}

/** Sessões (não excluídas) que se sobrepõem ao intervalo [inicio, fim). */
export function findOverlaps(candidate, sessions, nowMs) {
    const start = toMs(candidate.inicio);
    const end = sessionEnd(candidate, nowMs);
    if (candidate.origem === 'importado') return [];
    return sessions.filter((other) =>
        other.id !== candidate.id
        && !other.deleted_at
        && other.origem !== 'importado'
        && toMs(other.inicio) < end
        && sessionEnd(other, nowMs) > start
    );
}

/** Nível de cor do calendário (0–5), nas faixas do YPT: 0+, 4+, 7+, 10+, 12+ horas. */
export function heatLevel(totalMs) {
    if (totalMs <= 0) return 0;
    if (totalMs < 4 * HOUR) return 1;
    if (totalMs < 7 * HOUR) return 2;
    if (totalMs < 10 * HOUR) return 3;
    if (totalMs < 12 * HOUR) return 4;
    return 5;
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

// ── Métricas de comparação e tendência ────────────────────────────────────────

function daysOf(fromDay, toDay) {
    const days = [];
    for (let day = fromDay; day <= toDay; day = addDays(day, 1)) days.push(day);
    return days;
}

/** Tempo estudado entre dois instantes, somando os pedaços dos dias envolvidos. */
export function amountBetween(daily, fromMs, toMs, settings) {
    if (toMs <= fromMs) return 0;
    let total = 0;
    const lastDay = studyDayOf(toMs - 1, settings);
    for (let day = studyDayOf(fromMs, settings); day <= lastDay; day = addDays(day, 1)) {
        for (const piece of daily.get(day)?.pieces ?? []) {
            total += Math.max(0, Math.min(piece.end, toMs) - Math.max(piece.start, fromMs));
        }
    }
    return total;
}

function comparisonRanges(mode, today) {
    if (mode === 'day') return { curFrom: today, curTo: today, prevFrom: addDays(today, -1), prevTo: addDays(today, -1) };
    if (mode === 'week') {
        const [from, to] = weekRange(today);
        return { curFrom: from, curTo: to, prevFrom: addDays(from, -7), prevTo: addDays(from, -1) };
    }
    const [from, to] = monthRange(today);
    const [prevFrom, prevTo] = monthRange(addDays(from, -1));
    return { curFrom: from, curTo: to, prevFrom, prevTo };
}

/**
 * Período atual × anterior até o ponto equivalente ("Vs ontem", "Até sábado", "Vs dia 31").
 * mode: 'day' | 'week' | 'month'. Retorna { currentMs, previousMs, diffMs }.
 */
export function compareWithPrevious(daily, mode, today, settings, nowMs) {
    const { curFrom, prevFrom, prevTo } = comparisonRanges(mode, today);
    const curStart = dayBounds(curFrom, settings)[0];
    const prevStart = dayBounds(prevFrom, settings)[0];
    const prevEnd = dayBounds(prevTo, settings)[1];
    const currentMs = amountBetween(daily, curStart, nowMs, settings);
    const previousMs = amountBetween(daily, prevStart, Math.min(prevStart + (nowMs - curStart), prevEnd), settings);
    return { currentMs, previousMs, diffMs: currentMs - previousMs };
}

/**
 * Curvas acumuladas do período atual e do anterior, para o gráfico da comparação.
 * Dia: um ponto por hora a partir da virada; semana e mês: um ponto no fim de cada dia.
 * A curva atual para em "agora".
 */
export function comparisonSeries(daily, mode, today, settings, nowMs) {
    const { curFrom, curTo, prevFrom, prevTo } = comparisonRanges(mode, today);
    const marksFor = (fromDay, toDay) => {
        const start = dayBounds(fromDay, settings)[0];
        if (mode === 'day') return Array.from({ length: 25 }, (_, i) => start + i * HOUR);
        return [start, ...daysOf(fromDay, toDay).map((d) => dayBounds(d, settings)[1])];
    };
    const build = (fromDay, toDay, stopMs) => {
        const start = dayBounds(fromDay, settings)[0];
        const points = [];
        for (const mark of marksFor(fromDay, toDay)) {
            if (stopMs !== null && mark > stopMs) {
                points.push(amountBetween(daily, start, stopMs, settings));
                break;
            }
            points.push(amountBetween(daily, start, mark, settings));
        }
        return points;
    };
    const labels = mode === 'day'
        ? Array.from({ length: 25 }, (_, i) => String((settings.virada_hora + i) % 24).padStart(2, '0'))
        : ['', ...daysOf(curFrom, curTo)];
    return { labels, current: build(curFrom, curTo, nowMs), previous: build(prevFrom, prevTo, null) };
}

/**
 * Estudo × Outros: tempo cronometrado ÷ janela entre o início e o término de cada dia.
 * Sessões manuais e importadas ficam de fora porque não têm horários confiáveis.
 */
export function studyVsOther(daily, fromDay, toDay) {
    let studyMs = 0;
    let windowMs = 0;
    for (const day of daysOf(fromDay, toDay)) {
        const stats = daily.get(day);
        if (!stats || stats.startMs === null) continue;
        studyMs += stats.timerMs;
        windowMs += stats.endMs - stats.startMs;
    }
    return { studyMs, otherMs: Math.max(0, windowMs - studyMs), ratio: windowMs > 0 ? studyMs / windowMs : 0 };
}

/** Intervalos entre sessões consecutivas do dia (para a linha do tempo). */
export function dayGaps(stats, minMs = MIN) {
    const pieces = stats.pieces
        .filter((p) => p.session.origem !== 'importado')
        .sort((a, b) => a.start - b.start);
    const gaps = [];
    let lastEnd = null;
    for (const piece of pieces) {
        if (lastEnd !== null && piece.start - lastEnd >= minMs) {
            gaps.push({ start: lastEnd, end: piece.start });
        }
        lastEnd = lastEnd === null ? piece.end : Math.max(lastEnd, piece.end);
    }
    return gaps;
}

/** Média por dia da semana (seg = 0 … dom = 6), considerando só os dias já decorridos do intervalo. */
export function weekdayAverages(daily, fromDay, toDay, today) {
    const totals = Array(7).fill(0);
    const counts = Array(7).fill(0);
    for (const day of daysOf(fromDay, toDay < today ? toDay : today)) {
        const weekday = (new Date(day + 'T00:00:00Z').getUTCDay() + 6) % 7;
        totals[weekday] += daily.get(day)?.totalMs ?? 0;
        counts[weekday]++;
    }
    return totals.map((total, i) => (counts[i] ? total / counts[i] : 0));
}

/**
 * Perfil por hora do relógio (0–23): minutos médios cronometrados em cada hora,
 * considerando os dias já decorridos do intervalo.
 */
export function hourProfile(daily, fromDay, toDay, today, settings) {
    const minutes = Array(24).fill(0);
    const days = daysOf(fromDay, toDay < today ? toDay : today);
    for (const day of days) {
        for (const piece of daily.get(day)?.pieces ?? []) {
            if (piece.session.origem !== 'timer') continue;
            let cursor = piece.start;
            while (cursor < piece.end) {
                const wall = wallClock(cursor, settings.timezone);
                const hour = new Date(wall).getUTCHours();
                const nextHour = cursor + (HOUR - (((wall % HOUR) + HOUR) % HOUR));
                const sliceEnd = Math.min(piece.end, nextHour);
                minutes[hour] += (sliceEnd - cursor) / MIN;
                cursor = sliceEnd;
            }
        }
    }
    return minutes.map((m) => (days.length ? m / days.length : 0));
}

/** Semanas (segunda a domingo) que tocam o trimestre do dia: [{ from, to, totalMs }]. */
export function quarterWeeks(daily, day) {
    const [y, m] = day.split('-').map(Number);
    const firstMonth = Math.floor((m - 1) / 3) * 3 + 1;
    const qFrom = `${y}-${String(firstMonth).padStart(2, '0')}-01`;
    const qTo = monthRange(`${y}-${String(firstMonth + 2).padStart(2, '0')}-01`)[1];
    const weeks = [];
    for (let from = weekRange(qFrom)[0]; from <= qTo; from = addDays(from, 7)) {
        const to = addDays(from, 6);
        weeks.push({ from, to, totalMs: sumDays(daily, from, to) });
    }
    return weeks;
}

/** Os 12 meses do ano do dia: [{ month: 'YYYY-MM', from, to, totalMs }]. */
export function yearMonths(daily, day) {
    const year = day.slice(0, 4);
    return Array.from({ length: 12 }, (_, i) => {
        const [from, to] = monthRange(`${year}-${String(i + 1).padStart(2, '0')}-01`);
        return { month: from.slice(0, 7), from, to, totalMs: sumDays(daily, from, to) };
    });
}

function sumDays(daily, fromDay, toDay) {
    return daysOf(fromDay, toDay).reduce((sum, d) => sum + (daily.get(d)?.totalMs ?? 0), 0);
}

/**
 * Tendência: os últimos `count` dias, semanas ou meses até hoje (do mais antigo ao mais recente),
 * com total e divisão por matéria. unit: 'day' | 'week' | 'month'.
 */
export function trendBuckets(daily, unit, count, today) {
    const buckets = [];
    let [from, to] = unit === 'day' ? [today, today] : unit === 'week' ? weekRange(today) : monthRange(today);
    for (let i = 0; i < count; i++) {
        const bucket = { from, to, totalMs: 0, bySubject: {} };
        for (const d of daysOf(from, to)) {
            const stats = daily.get(d);
            if (!stats) continue;
            bucket.totalMs += stats.totalMs;
            for (const [subjectId, ms] of Object.entries(stats.bySubject)) {
                bucket.bySubject[subjectId] = (bucket.bySubject[subjectId] ?? 0) + ms;
            }
        }
        buckets.unshift(bucket);
        if (unit === 'day') {
            from = to = addDays(from, -1);
        } else if (unit === 'week') {
            from = addDays(from, -7);
            to = addDays(from, 6);
        } else {
            [from, to] = monthRange(addDays(from, -1));
        }
    }
    return buckets;
}

/**
 * Descanso: última sessão cronometrada encerrada (sem nenhuma em andamento), se terminou há menos de maxGapMs.
 * Retorna { session, sinceMs } ou null.
 */
/** Antecedência do primeiro aviso de fim do descanso. */
export const REST_WARN_MS = 60_000;

/**
 * Estágio do aviso de descanso: 'none', 'soon' (falta até 1 min; só para descansos de 2 min ou mais)
 * ou 'due' (o tempo de descanso configurado foi atingido).
 */
export function restAlertStage(restMs, descansoMin) {
    if (!descansoMin || restMs === null || restMs === undefined) return 'none';
    const limit = descansoMin * MIN;
    if (restMs >= limit) return 'due';
    if (descansoMin >= 2 && restMs >= limit - REST_WARN_MS) return 'soon';
    return 'none';
}

export function lastStopped(sessions, nowMs, maxGapMs = 6 * HOUR) {
    let last = null;
    for (const s of sessions) {
        if (s.deleted_at || s.origem !== 'timer') continue;
        if (!s.fim) return null;
        if (!last || toMs(s.fim) > toMs(last.fim)) last = s;
    }
    if (!last) return null;
    const sinceMs = nowMs - toMs(last.fim);
    return sinceMs >= 0 && sinceMs <= maxGapMs ? { session: last, sinceMs } : null;
}
