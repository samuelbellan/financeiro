import { describe, expect, it } from 'vitest';
import { DEFAULT_SETTINGS, allTimeStats, buildDaily, monthsBetween } from '../../../resources/js/estudos/metrics.js';

const H = 3_600_000;
const settings = { ...DEFAULT_SETTINGS, timezone: 'America/Sao_Paulo' };

let seq = 0;
function session(inicioLocal, fimLocal, extra = {}) {
    const toUtc = (local) => new Date(local + '-03:00').toISOString();
    return { id: `a${++seq}`, subject_id: 'dir', inicio: toUtc(inicioLocal), fim: toUtc(fimLocal), origem: 'timer', status: 'ok', deleted_at: null, ...extra };
}
const now = Date.parse('2026-11-05T12:00-03:00');

describe('estatísticas totais', () => {
    it('retorna null sem estudo', () => {
        expect(allTimeStats(new Map(), '2026-11-05')).toBeNull();
    });

    it('soma todo o histórico, recordes e maior sequência', () => {
        const sessions = [
            session('2026-10-01T09:00', '2026-10-01T11:00'),
            session('2026-10-02T09:00', '2026-10-02T10:00', { subject_id: 'port', tipo: 'questoes' }),
            session('2026-10-03T09:00', '2026-10-03T09:30'),
            session('2026-10-20T08:00', '2026-10-20T13:00'),
            session('2026-11-04T09:00', '2026-11-04T10:00', { origem: 'manual' }),
            session('2026-11-05T09:00', '2026-11-05T10:00'),
            session('2026-11-06T09:00', '2026-11-06T10:00', { deleted_at: '2026-11-06' }),
        ];
        const all = allTimeStats(buildDaily(sessions, settings, now), '2026-11-05');
        expect(all.firstDay).toBe('2026-10-01');
        expect(all.totalMs).toBe(10.5 * H);
        expect(all.daysStudied).toBe(6);
        expect(all.elapsedDays).toBe(36);
        expect(all.avgPerStudiedDayMs).toBe(1.75 * H);
        expect(all.longestStreak).toBe(3);
        expect(all.sessions).toBe(6);
        expect(all.maxFocusMs).toBe(5 * H);
        expect(all.bestDay).toEqual({ day: '2026-10-20', totalMs: 5 * H });
        expect(all.bestWeek).toEqual({ from: '2026-10-19', to: '2026-10-25', totalMs: 5 * H });
        expect(all.bestMonth).toEqual({ month: '2026-10', from: '2026-10-01', totalMs: 8.5 * H });
        expect(all.bySubject).toEqual({ dir: 9.5 * H, port: 1 * H });
        expect(all.byType.questoes).toBe(1 * H);
    });

    it('conta meses entre datas', () => {
        expect(monthsBetween('2026-10-15', '2026-11-05')).toBe(2);
        expect(monthsBetween('2025-12-31', '2026-01-01')).toBe(2);
        expect(monthsBetween('2026-11-01', '2026-11-30')).toBe(1);
    });
});
