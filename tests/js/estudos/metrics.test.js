import { describe, expect, it } from 'vitest';
import {
    DEFAULT_SETTINGS, buildDaily, dayBounds, dayStats, findOverlaps, formatClock, isSuspicious,
    monthRange, rangeStats, streak, studyDayOf, timelineSlots, weekRange,
} from '../../../resources/js/estudos/metrics.js';

const H = 3_600_000;
const settings = { ...DEFAULT_SETTINGS, virada_hora: 3, timezone: 'America/Sao_Paulo' }; // UTC-3

let seq = 0;
function session(inicioLocal, fimLocal, extra = {}) {
    // Horários locais de São Paulo (UTC-3) convertidos para UTC.
    const toUtc = (local) => (local ? new Date(local + '-03:00').toISOString() : null);
    return {
        id: `s${++seq}`,
        subject_id: 'dir',
        inicio: toUtc(inicioLocal),
        fim: toUtc(fimLocal),
        origem: 'timer',
        status: 'ok',
        deleted_at: null,
        ...extra,
    };
}

const NOW = Date.parse('2026-10-31T15:00:00-03:00');

describe('virada do dia', () => {
    it('instantes antes das 3h pertencem ao dia anterior', () => {
        expect(studyDayOf(Date.parse('2026-10-31T02:59:00-03:00'), settings)).toBe('2026-10-30');
        expect(studyDayOf(Date.parse('2026-10-31T03:00:00-03:00'), settings)).toBe('2026-10-31');
    });

    it('limites do dia ficam na virada local', () => {
        const [start, end] = dayBounds('2026-10-30', settings);
        expect(new Date(start).toISOString()).toBe('2026-10-30T06:00:00.000Z');
        expect(new Date(end).toISOString()).toBe('2026-10-31T06:00:00.000Z');
    });

    it('sessão 01:00→04:30 é cortada na virada das 3h', () => {
        const daily = buildDaily([session('2026-10-31T01:00', '2026-10-31T04:30')], settings, NOW);
        const before = dayStats(daily, '2026-10-30');
        const after = dayStats(daily, '2026-10-31');
        expect(before.totalMs).toBe(2 * H);
        expect(after.totalMs).toBe(1.5 * H);
        expect(before.maxFocusMs).toBe(2 * H);
        expect(after.maxFocusMs).toBe(1.5 * H);
    });

    it('mudar a virada para 4h recalcula o mesmo histórico', () => {
        const sessions = [session('2026-10-31T01:00', '2026-10-31T04:30')];
        const daily = buildDaily(sessions, { ...settings, virada_hora: 4 }, NOW);
        expect(dayStats(daily, '2026-10-30').totalMs).toBe(3 * H);
        expect(dayStats(daily, '2026-10-31').totalMs).toBe(0.5 * H);
    });

    it('término depois da meia-noite é exibido com (+1)', () => {
        const daily = buildDaily([session('2026-10-30T23:00', '2026-10-31T01:30')], settings, NOW);
        const stats = dayStats(daily, '2026-10-30');
        expect(formatClock(stats.startMs, '2026-10-30', settings)).toBe('23:00');
        expect(formatClock(stats.endMs, '2026-10-30', settings)).toBe('01:30 (+1)');
    });
});

describe('métricas do dia', () => {
    it('total, máximo de foco, início e término', () => {
        const daily = buildDaily([
            session('2026-10-30T08:00', '2026-10-30T09:00'),
            session('2026-10-30T10:00', '2026-10-30T12:30', { subject_id: 'port' }),
            session('2026-10-30T20:00', '2026-10-30T20:45'),
        ], settings, NOW);
        const stats = dayStats(daily, '2026-10-30');
        expect(stats.totalMs).toBe(4.25 * H);
        expect(stats.maxFocusMs).toBe(2.5 * H);
        expect(formatClock(stats.startMs, stats.day, settings)).toBe('08:00');
        expect(formatClock(stats.endMs, stats.day, settings)).toBe('20:45');
        expect(stats.bySubject).toEqual({ dir: 1.75 * H, port: 2.5 * H });
    });

    it('sem tolerância: uma pausa curta quebra o foco', () => {
        const daily = buildDaily([
            session('2026-10-30T08:00', '2026-10-30T09:00'),
            session('2026-10-30T09:00:30', '2026-10-30T10:00'),
        ], settings, NOW);
        expect(dayStats(daily, '2026-10-30').maxFocusMs).toBe(1 * H);
    });

    it('sessão manual conta no total mas não no foco nem no início/término', () => {
        const daily = buildDaily([
            session('2026-10-30T07:00', '2026-10-30T11:00', { origem: 'manual' }),
            session('2026-10-30T14:00', '2026-10-30T15:00'),
        ], settings, NOW);
        const stats = dayStats(daily, '2026-10-30');
        expect(stats.totalMs).toBe(5 * H);
        expect(stats.maxFocusMs).toBe(1 * H);
        expect(formatClock(stats.startMs, stats.day, settings)).toBe('14:00');
        expect(formatClock(stats.endMs, stats.day, settings)).toBe('15:00');
    });

    it('dia só com sessões manuais não tem início/término', () => {
        const daily = buildDaily([session('2026-10-30T07:00', '2026-10-30T08:00', { origem: 'manual' })], settings, NOW);
        const stats = dayStats(daily, '2026-10-30');
        expect(stats.startMs).toBeNull();
        expect(stats.maxFocusMs).toBe(0);
    });

    it('sessão suspeita não conta em nada', () => {
        const daily = buildDaily([session('2026-10-30T08:00', '2026-10-30T19:00', { status: 'suspeita' })], settings, NOW);
        expect(dayStats(daily, '2026-10-30').totalMs).toBe(0);
    });

    it('sessão excluída não conta em nada', () => {
        const daily = buildDaily([session('2026-10-30T08:00', '2026-10-30T09:00', { deleted_at: '2026-10-30T13:00:00Z' })], settings, NOW);
        expect(daily.size).toBe(0);
    });

    it('sessão em andamento conta até agora', () => {
        const daily = buildDaily([session('2026-10-31T14:00', null)], settings, NOW);
        expect(dayStats(daily, '2026-10-31').totalMs).toBe(1 * H);
    });
});

describe('cronômetro esquecido', () => {
    it('é suspeito depois do limite e só enquanto está aberto', () => {
        expect(isSuspicious(session('2026-10-31T11:59', null), settings, NOW)).toBe(true);
        expect(isSuspicious(session('2026-10-31T12:01', null), settings, NOW)).toBe(false);
        expect(isSuspicious(session('2026-10-31T08:00', '2026-10-31T13:00'), settings, NOW)).toBe(false);
    });
});

describe('sequência de dias', () => {
    const daily = buildDaily([
        session('2026-10-28T10:00', '2026-10-28T11:00'),
        session('2026-10-29T10:00', '2026-10-29T11:00'),
        session('2026-10-30T10:00', '2026-10-30T11:00'),
    ], settings, NOW);

    it('hoje sem estudo ainda não quebra a sequência de ontem', () => {
        expect(streak(daily, '2026-10-31')).toBe(3);
    });

    it('um dia sem estudo zera a sequência', () => {
        expect(streak(daily, '2026-11-01')).toBe(0);
    });

    it('estudo de madrugada antes da virada mantém o dia anterior', () => {
        const night = buildDaily([
            session('2026-10-29T20:00', '2026-10-29T21:00'),
            session('2026-10-31T01:00', '2026-10-31T02:00'), // pertence a 30/10
        ], settings, NOW);
        expect(streak(night, '2026-10-31')).toBe(2);
    });
});

describe('intervalos', () => {
    it('semana começa na segunda e média só considera dias decorridos', () => {
        expect(weekRange('2026-10-31')).toEqual(['2026-10-26', '2026-11-01']);
        const daily = buildDaily([
            session('2026-10-26T10:00', '2026-10-26T13:00'),
            session('2026-10-27T10:00', '2026-10-27T11:00', { subject_id: 'port' }),
        ], settings, NOW);
        const week = rangeStats(daily, '2026-10-26', '2026-11-01', '2026-10-27');
        expect(week.totalMs).toBe(4 * H);
        expect(week.daysStudied).toBe(2);
        expect(week.avgPerDayMs).toBe(2 * H);
        expect(week.bySubject).toEqual({ dir: 3 * H, port: 1 * H });
    });

    it('mês tem o último dia correto', () => {
        expect(monthRange('2026-02-10')).toEqual(['2026-02-01', '2026-02-28']);
    });
});

describe('linha do tempo de 10 minutos', () => {
    it('pinta os blocos com a matéria que mais ocupou cada um', () => {
        const daily = buildDaily([
            session('2026-10-30T03:00', '2026-10-30T03:25'),
            session('2026-10-30T03:25', '2026-10-30T03:40', { subject_id: 'port', origem: 'manual' }),
        ], settings, NOW);
        const slots = timelineSlots(dayStats(daily, '2026-10-30'), settings);
        expect(slots).toHaveLength(144);
        expect(slots[0]).toEqual({ subjectId: 'dir', manual: false });
        expect(slots[1]).toEqual({ subjectId: 'dir', manual: false });
        expect(slots[2]).toEqual({ subjectId: 'dir', manual: false }); // 03:20–03:30: 5 min cada, empate fica com a primeira
        expect(slots[3]).toEqual({ subjectId: 'port', manual: true });
        expect(slots[4]).toBeNull();
    });
});

describe('sobreposição', () => {
    const existing = [
        session('2026-10-30T08:00', '2026-10-30T09:00'),
        session('2026-10-30T10:00', '2026-10-30T11:00', { deleted_at: '2026-10-30T12:00:00Z' }),
    ];

    it('detecta sobreposição e ignora excluídas e sessões adjacentes', () => {
        expect(findOverlaps(session('2026-10-30T08:30', '2026-10-30T08:45'), existing, NOW)).toHaveLength(1);
        expect(findOverlaps(session('2026-10-30T09:00', '2026-10-30T09:30'), existing, NOW)).toHaveLength(0);
        expect(findOverlaps(session('2026-10-30T10:15', '2026-10-30T10:30'), existing, NOW)).toHaveLength(0);
    });

    it('sessão em andamento ocupa até agora', () => {
        const running = [session('2026-10-31T14:00', null)];
        expect(findOverlaps(session('2026-10-31T14:30', '2026-10-31T14:40'), running, NOW)).toHaveLength(1);
    });
});

