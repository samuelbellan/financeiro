import { describe, expect, it } from 'vitest';
import {
    DEFAULT_SETTINGS, buildDaily, compareWithPrevious, comparisonSeries, dayGaps, dayStats, findOverlaps, heatLevel,
    hourProfile, lastStopped, quarterWeeks, studyVsOther, timelineSlots, trendBuckets, weekdayAverages, yearMonths,
} from '../../../resources/js/estudos/metrics.js';

const H = 3_600_000;
const M = 60_000;
const settings = { ...DEFAULT_SETTINGS, timezone: 'America/Sao_Paulo' }; // virada padrão (1h), UTC-3

let seq = 0;
function session(inicioLocal, fimLocal, extra = {}) {
    const toUtc = (local) => (local ? new Date(local + '-03:00').toISOString() : null);
    return { id: `t${++seq}`, subject_id: 'dir', inicio: toUtc(inicioLocal), fim: toUtc(fimLocal), origem: 'timer', status: 'ok', deleted_at: null, ...extra };
}
const at = (local) => Date.parse(local + '-03:00');

it('a virada padrão é 1h', () => {
    expect(DEFAULT_SETTINGS.virada_hora).toBe(1);
});

describe('sessões importadas', () => {
    const imported = session('2026-10-30T01:00', '2026-10-30T04:00', { origem: 'importado', subject_id: 'port' });

    it('contam no total e na matéria, mas não no foco, início/término, linha do tempo nem sobreposição', () => {
        const daily = buildDaily([imported, session('2026-10-30T09:00', '2026-10-30T10:00')], settings, at('2026-10-31T12:00'));
        const stats = dayStats(daily, '2026-10-30');
        expect(stats.totalMs).toBe(4 * H);
        expect(stats.bySubject.port).toBe(3 * H);
        expect(stats.maxFocusMs).toBe(1 * H);
        expect(stats.timerMs).toBe(1 * H);
        expect(timelineSlots(stats, settings).filter(Boolean)).toHaveLength(6);
        expect(findOverlaps(session('2026-10-30T02:00', '2026-10-30T02:30', { origem: 'manual' }), [imported], at('2026-10-31T12:00'))).toHaveLength(0);
        expect(findOverlaps(imported, [session('2026-10-30T02:00', '2026-10-30T02:30')], at('2026-10-31T12:00'))).toHaveLength(0);
    });
});

describe('comparação com o período anterior', () => {
    const sessions = [
        session('2026-10-30T04:00', '2026-10-30T06:00'), // ontem: 2h até as 6h
        session('2026-10-30T20:00', '2026-10-30T21:00'), // ontem: +1h à noite
        session('2026-10-31T04:00', '2026-10-31T05:00'), // hoje: 1h até agora
    ];
    const now = at('2026-10-31T07:00');
    const daily = buildDaily(sessions, settings, now);

    it('hoje × ontem até o mesmo horário', () => {
        const result = compareWithPrevious(daily, 'day', '2026-10-31', settings, now);
        expect(result.currentMs).toBe(1 * H);
        expect(result.previousMs).toBe(2 * H);
        expect(result.diffMs).toBe(-1 * H);
    });

    it('semana × semana passada até o mesmo ponto', () => {
        const weekly = buildDaily([
            session('2026-10-19T10:00', '2026-10-19T13:00'), // seg passada
            session('2026-10-24T10:00', '2026-10-24T11:00'), // sáb passado
            session('2026-10-26T10:00', '2026-10-26T12:00'), // seg atual
        ], settings, at('2026-10-27T12:00'));
        const result = compareWithPrevious(weekly, 'week', '2026-10-27', settings, at('2026-10-27T12:00'));
        expect(result.currentMs).toBe(2 * H);
        expect(result.previousMs).toBe(3 * H);
    });

    it('mês × mês passado até o mesmo dia', () => {
        const monthly = buildDaily([
            session('2026-09-02T10:00', '2026-09-02T12:00'),
            session('2026-09-20T10:00', '2026-09-20T12:00'),
            session('2026-10-01T10:00', '2026-10-01T15:00'),
        ], settings, at('2026-10-05T12:00'));
        const result = compareWithPrevious(monthly, 'month', '2026-10-05', settings, at('2026-10-05T12:00'));
        expect(result.currentMs).toBe(5 * H);
        expect(result.previousMs).toBe(2 * H);
    });

    it('a curva do dia tem 25 pontos para ontem e para em agora para hoje', () => {
        const series = comparisonSeries(daily, 'day', '2026-10-31', settings, now);
        expect(series.labels[0]).toBe('01');
        expect(series.previous).toHaveLength(25);
        expect(series.previous.at(-1)).toBe(3 * H);
        expect(series.current.at(-1)).toBe(1 * H);
        expect(series.current.length).toBeLessThan(25);
    });
});

describe('estudo × outros e intervalos', () => {
    const daily = buildDaily([
        session('2026-10-31T07:43', '2026-10-31T07:48'),
        session('2026-10-31T08:00', '2026-10-31T08:10'),
        session('2026-10-31T09:00', '2026-10-31T10:00', { origem: 'manual' }),
        session('2026-10-31T13:09', '2026-10-31T13:19'),
    ], settings, at('2026-10-31T20:00'));
    const stats = dayStats(daily, '2026-10-31');

    it('usa só o tempo cronometrado dentro da janela início–término', () => {
        const result = studyVsOther(daily, '2026-10-31', '2026-10-31');
        expect(result.studyMs).toBe(25 * M);
        expect(result.otherMs).toBe((5 * 60 + 36) * M - 25 * M);
    });

    it('lista os intervalos entre sessões consecutivas', () => {
        const gaps = dayGaps(stats);
        expect(gaps.map((g) => (g.end - g.start) / M)).toEqual([12, 50, 189]);
    });
});

describe('agregados', () => {
    it('média por dia da semana considera só dias decorridos', () => {
        const daily = buildDaily([
            session('2026-10-26T10:00', '2026-10-26T12:00'), // seg
            session('2026-10-19T10:00', '2026-10-19T14:00'), // seg
        ], settings, at('2026-10-31T12:00'));
        const averages = weekdayAverages(daily, '2026-10-19', '2026-11-01', '2026-10-31');
        expect(averages[0]).toBe(3 * H);
        expect(averages[6]).toBe(0);
    });

    it('perfil por hora distribui o tempo nas horas do relógio', () => {
        const daily = buildDaily([session('2026-10-31T03:30', '2026-10-31T05:15')], settings, at('2026-10-31T12:00'));
        const profile = hourProfile(daily, '2026-10-31', '2026-10-31', '2026-10-31', settings);
        expect(profile[3]).toBeCloseTo(30);
        expect(profile[4]).toBeCloseTo(60);
        expect(profile[5]).toBeCloseTo(15);
        expect(profile[6]).toBe(0);
    });

    it('grade do trimestre e do ano', () => {
        const daily = buildDaily([session('2026-10-26T10:00', '2026-10-26T12:00')], settings, at('2026-10-31T12:00'));
        const weeks = quarterWeeks(daily, '2026-10-31');
        expect(weeks[0].from).toBe('2026-09-28');
        expect(weeks.at(-1).to >= '2026-12-31').toBe(true);
        expect(weeks.find((w) => w.from === '2026-10-26').totalMs).toBe(2 * H);
        const months = yearMonths(daily, '2026-10-31');
        expect(months).toHaveLength(12);
        expect(months[9].totalMs).toBe(2 * H);
    });

    it('tendência devolve os últimos N períodos em ordem', () => {
        const daily = buildDaily([
            session('2026-09-10T10:00', '2026-09-10T11:00', { subject_id: 'port' }),
            session('2026-10-30T10:00', '2026-10-30T12:00'),
        ], settings, at('2026-10-31T12:00'));
        const months = trendBuckets(daily, 'month', 3, '2026-10-31');
        expect(months.map((b) => b.from)).toEqual(['2026-08-01', '2026-09-01', '2026-10-01']);
        expect(months[1].bySubject).toEqual({ port: 1 * H });
        const days = trendBuckets(daily, 'day', 2, '2026-10-31');
        expect(days.map((b) => b.totalMs)).toEqual([2 * H, 0]);
    });

    it('faixas de cor do YPT', () => {
        expect([0, 3.9, 4, 7, 10, 12].map((h) => heatLevel(h * H))).toEqual([0, 1, 2, 3, 4, 5]);
    });
});

describe('descanso', () => {
    const now = at('2026-10-31T16:30');

    it('mede o tempo desde a última sessão cronometrada', () => {
        const result = lastStopped([
            session('2026-10-31T14:00', '2026-10-31T15:00', { subject_id: 'aud' }),
            session('2026-10-31T15:10', '2026-10-31T16:25'),
            session('2026-10-31T06:00', '2026-10-31T09:00', { origem: 'manual' }),
        ], now);
        expect(result.session.subject_id).toBe('dir');
        expect(result.sinceMs).toBe(5 * M);
    });

    it('não há descanso com sessão rodando, sem sessões ou após o limite', () => {
        expect(lastStopped([session('2026-10-31T16:00', null)], now)).toBeNull();
        expect(lastStopped([], now)).toBeNull();
        expect(lastStopped([session('2026-10-31T06:00', '2026-10-31T07:00')], now)).toBeNull();
        expect(lastStopped([session('2026-10-31T16:00', '2026-10-31T16:20', { deleted_at: '2026-10-31T16:21:00Z' })], now)).toBeNull();
    });
});
