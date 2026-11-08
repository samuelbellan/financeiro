import { describe, expect, it } from 'vitest';
import {
    averagePace, manualPace, projectDate, reachedOn, requiredPerDay, totalMs, weekdayOf, weekdayPace,
} from '../../../resources/js/estudos/projection.js';
import { addDays } from '../../../resources/js/estudos/metrics.js';

const HOUR = 3_600_000;

function dailyOf(entries) {
    return new Map(Object.entries(entries).map(([day, hours]) => [day, { day, totalMs: hours * HOUR }]));
}

// 2026-11-09 é uma segunda-feira.
const MONDAY = '2026-11-09';

describe('histórico', () => {
    it('soma o total e encontra o dia em que o alvo foi cruzado', () => {
        const daily = dailyOf({ '2026-11-03': 2, '2026-11-01': 3, '2026-11-02': 4 });
        expect(totalMs(daily)).toBe(9 * HOUR);
        expect(reachedOn(daily, 7 * HOUR)).toBe('2026-11-02');
        expect(reachedOn(daily, 9 * HOUR)).toBe('2026-11-03');
        expect(reachedOn(daily, 10 * HOUR)).toBeNull();
    });
});

describe('ritmos', () => {
    it('weekdayOf usa seg = 0 … dom = 6', () => {
        expect(weekdayOf(MONDAY)).toBe(0);
        expect(weekdayOf('2026-11-15')).toBe(6);
    });

    it('perfil por dia da semana ignora hoje', () => {
        const entries = {};
        for (let i = 1; i <= 28; i++) {
            const day = addDays(MONDAY, -i);
            entries[day] = weekdayOf(day) === 6 ? 1 : 4;
        }
        entries[MONDAY] = 20; // hoje, em andamento: não entra
        const pace = weekdayPace(dailyOf(entries), MONDAY, 28);
        expect(pace.map((ms) => ms / HOUR)).toEqual([4, 4, 4, 4, 4, 4, 1]);
    });

    it('média simples divide pela janela inteira, inclusive dias sem estudo', () => {
        const daily = dailyOf({ [addDays(MONDAY, -1)]: 7, [addDays(MONDAY, -2)]: 7 });
        expect(averagePace(daily, MONDAY, 7)[0]).toBe(2 * HOUR);
    });

    it('ritmo manual zera os dias de folga', () => {
        expect(manualPace(5, [6]).map((ms) => ms / HOUR)).toEqual([5, 5, 5, 5, 5, 5, 0]);
    });
});

describe('projectDate', () => {
    const pace = manualPace(4); // 4h todos os dias

    it('alvo já atingido', () => {
        expect(projectDate({ startMs: 10 * HOUR, targetMs: 8 * HOUR, pace, today: MONDAY }).status).toBe('reached');
    });

    it('conta só o que falta do ritmo de hoje', () => {
        // faltam 6h; hoje já fiz 3h, então hoje rende mais 1h; amanhã +4h; depois +4h
        const r = projectDate({ startMs: 0, targetMs: 6 * HOUR, pace, today: MONDAY, todayDoneMs: 3 * HOUR });
        expect(r).toEqual({ status: 'projected', day: addDays(MONDAY, 2), days: 2 });
    });

    it('termina hoje se o restante do dia basta', () => {
        expect(projectDate({ startMs: 0, targetMs: 2 * HOUR, pace, today: MONDAY }).day).toBe(MONDAY);
    });

    it('pula semanas inteiras e respeita as folgas', () => {
        const withSunday = manualPace(2, [6]); // 12h por semana, domingo de folga
        // hoje (seg) rende 2h; faltam 120h - 2h = 118h → 9 semanas (108h) até a seg 9 semanas depois... conferido dia a dia:
        const r = projectDate({ startMs: 0, targetMs: 120 * HOUR, pace: withSunday, today: MONDAY });
        let remaining = 120 * HOUR - 2 * HOUR;
        let day = MONDAY;
        while (remaining > 0) {
            day = addDays(day, 1);
            remaining -= withSunday[weekdayOf(day)];
        }
        expect(r.day).toBe(day);
        expect(weekdayOf(r.day)).not.toBe(6);
    });

    it('ritmo zero não tem previsão', () => {
        expect(projectDate({ startMs: 0, targetMs: HOUR, pace: manualPace(0), today: MONDAY }).status).toBe('never');
    });

    it('previsão absurda (décadas) vira "sem previsão"', () => {
        const tiny = manualPace(0.001);
        expect(projectDate({ startMs: 0, targetMs: 10_000 * HOUR, pace: tiny, today: MONDAY }).status).toBe('never');
    });
});

describe('requiredPerDay', () => {
    it('divide o que falta pelos dias de estudo até a data, inclusive hoje', () => {
        const r = requiredPerDay({ startMs: 0, targetMs: 14 * HOUR, today: MONDAY, targetDay: addDays(MONDAY, 6) });
        expect(r).toEqual({ status: 'ok', perDayMs: 2 * HOUR, studyDays: 7 });
    });

    it('desconta as folgas', () => {
        const r = requiredPerDay({ startMs: 0, targetMs: 12 * HOUR, today: MONDAY, targetDay: addDays(MONDAY, 6), offWeekdays: [6] });
        expect(r.perDayMs).toBe(2 * HOUR);
    });

    it('data no passado ou só dias de folga é inválido', () => {
        expect(requiredPerDay({ startMs: 0, targetMs: HOUR, today: MONDAY, targetDay: addDays(MONDAY, -1) }).status).toBe('invalid');
        expect(requiredPerDay({ startMs: 0, targetMs: HOUR, today: MONDAY, targetDay: MONDAY, offWeekdays: [0] }).status).toBe('invalid');
    });

    it('alvo já atingido', () => {
        expect(requiredPerDay({ startMs: 5 * HOUR, targetMs: HOUR, today: MONDAY, targetDay: MONDAY }).status).toBe('reached');
    });
});
