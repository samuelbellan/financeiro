// Jornada: gamificação por objetivo diário (funções puras, sem DOM).
//
// Cada dia de estudo em que o objetivo é cumprido vira um golpe (1 de dano) no boss atual. Quando a vida
// do boss chega a zero, ele é derrotado naquele dia e o próximo boss entra na arena. Depois do último boss
// a jornada recomeça num novo ciclo ("+", "++"...) com bosses 50% mais resistentes.
//
// Tudo é calculado a partir das sessões (nada de placar gravado): vale em qualquer aparelho, e editar ou
// excluir uma sessão corrige a jornada automaticamente.

import { addDays, dayStats, NO_TYPE } from './metrics.js';

export const JORNADA_PADRAO = Object.freeze({ objetivo: 'total', minutos: 300 });

/** Vida de cada boss, na ordem da jornada (o desenho fica em bosses.js, com o mesmo índice). */
export const BOSS_HP = Object.freeze([3, 5, 7, 10, 12, 15, 20, 30]);

/** Ciclo 0 = vida normal; cada ciclo seguinte soma 50%. */
export function bossMaxHp(index, cycle = 0) {
    return Math.round(BOSS_HP[index] * (1 + cycle * 0.5));
}

/**
 * Objetivo normalizado: { objetivo: 'total' | 'tipo:<id>' | 'materia:<id>', minutos, inicio }.
 * `null`/indefinido = jornada ainda não começou.
 */
export function normalizeJornada(jornada) {
    if (!jornada || !jornada.inicio) return null;
    const minutos = Number(jornada.minutos);
    return {
        inicio: jornada.inicio,
        objetivo: typeof jornada.objetivo === 'string' && jornada.objetivo ? jornada.objetivo : JORNADA_PADRAO.objetivo,
        minutos: Number.isFinite(minutos) && minutos > 0 ? minutos : JORNADA_PADRAO.minutos,
    };
}

/** Quanto do dia conta para o objetivo (ms). */
export function objectiveProgressMs(stats, objetivo) {
    const [kind, id] = objetivo.split(':');
    if (kind === 'tipo') return stats.byType[id || NO_TYPE] ?? 0;
    if (kind === 'materia') return stats.bySubject[id] ?? 0;
    return stats.totalMs;
}

/**
 * Estado da jornada até `today` (inclusive). Hoje só conta como golpe quando o objetivo já foi cumprido.
 *
 * Retorna:
 *  - current: { index, cycle, maxHp, hp, hits: [dias], since } — boss na arena
 *  - defeated: [{ index, cycle, day, hits }] — em ordem
 *  - today: { day, progressMs, goalMs, pct, met }
 *  - combo: dias seguidos cumprindo o objetivo (hoje ainda não cumprido não quebra)
 *  - bestCombo, totalHits
 */
export function jornadaState(daily, jornada, today) {
    const j = normalizeJornada(jornada);
    if (!j) return null;
    const goalMs = j.minutos * 60_000;
    const defeated = [];
    let index = 0;
    let cycle = 0;
    let hits = [];
    let since = j.inicio;
    let totalHits = 0;
    let run = 0;
    let bestCombo = 0;

    for (let day = j.inicio; day <= today; day = addDays(day, 1)) {
        const met = objectiveProgressMs(dayStats(daily, day), j.objetivo) >= goalMs;
        if (!met) {
            if (day !== today) run = 0;
            continue;
        }
        run++;
        bestCombo = Math.max(bestCombo, run);
        totalHits++;
        hits.push(day);
        if (hits.length >= bossMaxHp(index, cycle)) {
            defeated.push({ index, cycle, day, hits });
            hits = [];
            since = addDays(day, 1);
            index++;
            if (index >= BOSS_HP.length) {
                index = 0;
                cycle++;
            }
        }
    }

    const progressMs = today >= j.inicio ? objectiveProgressMs(dayStats(daily, today), j.objetivo) : 0;
    const maxHp = bossMaxHp(index, cycle);
    return {
        jornada: j,
        current: { index, cycle, maxHp, hp: maxHp - hits.length, hits, since },
        defeated,
        today: { day: today, progressMs, goalMs, pct: Math.min(100, (progressMs / goalMs) * 100), met: progressMs >= goalMs },
        combo: run,
        bestCombo,
        totalHits,
    };
}

/** Rótulo do ciclo: "", "+", "++"... */
export function cycleMark(cycle) {
    return '+'.repeat(cycle);
}
