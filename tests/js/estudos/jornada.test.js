import { describe, expect, it } from 'vitest';
import { BOSSES, bossSvg } from '../../../resources/js/estudos/bosses.js';
import { BOSS_HP, bossMaxHp, jornadaState, normalizeJornada, objectiveProgressMs } from '../../../resources/js/estudos/jornada.js';
import { addDays } from '../../../resources/js/estudos/metrics.js';

const H = 3_600_000;

/** Daily falso: { 'YYYY-MM-DD': horas } ou { dia: { totalMs, byType, bySubject } }. */
function dailyOf(days) {
    const map = new Map();
    for (const [day, value] of Object.entries(days)) {
        const stats = typeof value === 'number' ? { totalMs: value * H } : value;
        map.set(day, { day, totalMs: 0, byType: {}, bySubject: {}, ...stats });
    }
    return map;
}

const jornada = (extra = {}) => ({ inicio: '2026-11-01', objetivo: 'total', minutos: 300, ...extra });

describe('jornadaState', () => {
    it('sem jornada configurada não há estado', () => {
        expect(jornadaState(dailyOf({}), null, '2026-11-04')).toBeNull();
        expect(normalizeJornada({ objetivo: 'total' })).toBeNull();
    });

    it('cada dia com 5h é um golpe; dias abaixo da meta não contam', () => {
        const state = jornadaState(dailyOf({ '2026-11-01': 5, '2026-11-02': 4.9, '2026-11-03': 6 }), jornada(), '2026-11-04');
        expect(state.totalHits).toBe(2);
        expect(state.current).toMatchObject({ index: 0, maxHp: BOSS_HP[0], hp: BOSS_HP[0] - 2, hits: ['2026-11-01', '2026-11-03'] });
        expect(state.today).toMatchObject({ met: false, progressMs: 0, goalMs: 5 * H });
    });

    it('dias antes do início não contam', () => {
        const state = jornadaState(dailyOf({ '2026-10-31': 8, '2026-11-01': 5 }), jornada(), '2026-11-01');
        expect(state.totalHits).toBe(1);
        expect(state.today.met).toBe(true);
    });

    it('derruba o boss quando a vida zera e passa para o próximo', () => {
        const days = {};
        for (let i = 0; i < 4; i++) days[addDays('2026-11-01', i)] = 5;
        const state = jornadaState(dailyOf(days), jornada(), '2026-11-04');
        expect(state.defeated).toEqual([{ index: 0, cycle: 0, day: '2026-11-03', hits: ['2026-11-01', '2026-11-02', '2026-11-03'] }]);
        expect(state.current).toMatchObject({ index: 1, hp: BOSS_HP[1] - 1, since: '2026-11-04' });
    });

    it('depois do último boss começa um ciclo mais forte', () => {
        const total = BOSS_HP.reduce((a, b) => a + b, 0);
        const days = {};
        for (let i = 0; i < total + 1; i++) days[addDays('2026-01-01', i)] = 6;
        const state = jornadaState(dailyOf(days), jornada({ inicio: '2026-01-01' }), addDays('2026-01-01', total));
        expect(state.defeated).toHaveLength(BOSS_HP.length);
        expect(state.current).toMatchObject({ index: 0, cycle: 1, maxHp: bossMaxHp(0, 1) });
        expect(bossMaxHp(0, 1)).toBeGreaterThan(BOSS_HP[0]);
    });

    it('combo: dia perdido quebra, hoje ainda em andamento não', () => {
        const daily = dailyOf({ '2026-11-01': 5, '2026-11-02': 1, '2026-11-03': 5, '2026-11-04': 5, '2026-11-05': 2 });
        const state = jornadaState(daily, jornada(), '2026-11-05');
        expect(state.combo).toBe(2);
        expect(state.bestCombo).toBe(2);
        expect(state.today.pct).toBeCloseTo(40);
    });

    it('objetivo por tipo de estudo ou por matéria', () => {
        const stats = { totalMs: 6 * H, byType: { questoes: 2 * H }, bySubject: { abc: 1 * H } };
        expect(objectiveProgressMs(stats, 'total')).toBe(6 * H);
        expect(objectiveProgressMs(stats, 'tipo:questoes')).toBe(2 * H);
        expect(objectiveProgressMs(stats, 'materia:abc')).toBe(1 * H);
        expect(objectiveProgressMs(stats, 'materia:xyz')).toBe(0);

        const state = jornadaState(dailyOf({ '2026-11-01': stats }), jornada({ objetivo: 'tipo:questoes', minutos: 120 }), '2026-11-01');
        expect(state.totalHits).toBe(1);
    });
});

describe('bosses', () => {
    it('há um desenho para cada boss da jornada', () => {
        expect(BOSSES).toHaveLength(BOSS_HP.length);
        for (const boss of BOSSES) {
            expect(boss.svg).toContain('__ID__');
            expect(boss.core).toHaveLength(2);
        }
    });

    it('cada desenho ganha ids próprios e mostra dano, nocaute e silhueta', () => {
        const a = bossSvg(0);
        const b = bossSvg(0);
        expect(a).not.toContain('__ID__');
        expect(a.match(/id="(bs\d+-)/)[1]).not.toBe(b.match(/id="(bs\d+-)/)[1]);
        expect(bossSvg(2, { hp: 1, maxHp: 7 })).toContain('b-slash');
        expect(bossSvg(2, { hp: 7, maxHp: 7 })).not.toContain('b-slash');
        expect(bossSvg(3, { defeated: true })).toContain('is-defeated');
        expect(bossSvg(4, { locked: true })).toContain('Boss desconhecido');
    });
});
