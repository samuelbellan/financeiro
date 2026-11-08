// Calculadora de projeção de horas (ver docs/sabatina-2026-11-08-projecao-e-midias.md).
//
// Um "ritmo" é um perfil de 7 posições (seg = 0 … dom = 6) com os ms estudados esperados em cada dia da semana.
// A projeção parte do total atual e soma o perfil dia a dia, a partir de hoje (descontando o que já foi feito hoje).

import { addDays, daysBetween, weekdayAverages } from './metrics.js';

const HOUR = 3_600_000;

/** Horizonte máximo da projeção: além disso, a previsão não tem utilidade prática. */
export const MAX_PROJECTION_DAYS = 365 * 30;

/** Janela usada pelos ritmos calculados (dias completos, sem contar hoje). */
export const DEFAULT_WINDOW_DAYS = 30;

export function weekdayOf(day) {
    return (new Date(day + 'T00:00:00Z').getUTCDay() + 6) % 7;
}

/** Total estudado em todo o histórico (inclui sessões importadas). */
export function totalMs(daily) {
    let total = 0;
    for (const stats of daily.values()) total += stats.totalMs;
    return total;
}

/** Primeiro dia em que o acumulado do histórico chegou ao alvo, ou null. */
export function reachedOn(daily, targetMs) {
    if (targetMs <= 0) return null;
    let cumulative = 0;
    for (const day of [...daily.keys()].sort()) {
        cumulative += daily.get(day).totalMs;
        if (cumulative >= targetMs) return day;
    }
    return null;
}

/** Perfil por dia da semana dos últimos `windowDays` dias completos (hoje fica de fora: ainda está em andamento). */
export function weekdayPace(daily, today, windowDays = DEFAULT_WINDOW_DAYS) {
    const to = addDays(today, -1);
    return weekdayAverages(daily, addDays(today, -windowDays), to, to);
}

/** Média simples dos últimos `windowDays` dias completos, repetida nos 7 dias da semana. */
export function averagePace(daily, today, windowDays = DEFAULT_WINDOW_DAYS) {
    let sum = 0;
    for (let i = 1; i <= windowDays; i++) sum += daily.get(addDays(today, -i))?.totalMs ?? 0;
    return Array(7).fill(sum / windowDays);
}

/** Ritmo manual: `hoursPerDay` em todos os dias, exceto nos dias de folga (índices seg = 0 … dom = 6). */
export function manualPace(hoursPerDay, offWeekdays = []) {
    const off = new Set(offWeekdays);
    return Array.from({ length: 7 }, (_, i) => (off.has(i) ? 0 : Math.max(0, hoursPerDay) * HOUR));
}

export function paceWeeklyMs(pace) {
    return pace.reduce((sum, ms) => sum + ms, 0);
}

/**
 * Dia em que o alvo será atingido seguindo o ritmo.
 * Hoje conta só o que falta do ritmo do dia (`todayDoneMs` já está dentro de `startMs`).
 * Retorna { status: 'reached' | 'projected' | 'never', day, days }.
 */
export function projectDate({ startMs, targetMs, pace, today, todayDoneMs = 0 }) {
    let remaining = targetMs - startMs;
    if (remaining <= 0) return { status: 'reached', day: today, days: 0 };

    remaining -= Math.max(0, pace[weekdayOf(today)] - todayDoneMs);
    if (remaining <= 0) return { status: 'projected', day: today, days: 0 };

    const weekly = paceWeeklyMs(pace);
    if (weekly <= 0 || (remaining / weekly) * 7 > MAX_PROJECTION_DAYS) return { status: 'never', day: null, days: null };

    // Pula semanas inteiras de uma vez e percorre só a última, dia a dia.
    const fullWeeks = Math.max(0, Math.floor(remaining / weekly) - 1);
    let day = addDays(today, fullWeeks * 7);
    remaining -= fullWeeks * weekly;
    while (remaining > 0) {
        day = addDays(day, 1);
        remaining -= pace[weekdayOf(day)];
    }
    const days = daysBetween(today, day);
    if (days > MAX_PROJECTION_DAYS) return { status: 'never', day: null, days: null };
    return { status: 'projected', day, days };
}

/**
 * Quanto estudar por dia, de hoje até `targetDay` (inclusive), para chegar ao alvo.
 * Dias de folga não contam. Retorna { status: 'reached' | 'ok' | 'invalid', perDayMs, studyDays }.
 */
export function requiredPerDay({ startMs, targetMs, today, targetDay, offWeekdays = [] }) {
    const remaining = targetMs - startMs;
    if (remaining <= 0) return { status: 'reached', perDayMs: 0, studyDays: 0 };
    if (!targetDay || targetDay < today) return { status: 'invalid', perDayMs: null, studyDays: 0 };
    const off = new Set(offWeekdays);
    let studyDays = 0;
    for (let day = today; day <= targetDay; day = addDays(day, 1)) {
        if (!off.has(weekdayOf(day))) studyDays++;
    }
    if (studyDays === 0) return { status: 'invalid', perDayMs: null, studyDays: 0 };
    return { status: 'ok', perDayMs: remaining / studyDays, studyDays };
}
