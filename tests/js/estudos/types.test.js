import { describe, expect, it } from 'vitest';
import {
    DEFAULT_SETTINGS, NO_TYPE, buildDaily, dayStats, lastTypeFor, rangeStats, studyTypeName,
} from '../../../resources/js/estudos/metrics.js';

const H = 3_600_000;
const M = 60_000;
const settings = { ...DEFAULT_SETTINGS, virada_hora: 3, timezone: 'America/Sao_Paulo' }; // UTC-3

let seq = 0;
function session(subjectId, inicioLocal, fimLocal, extra = {}) {
    const toUtc = (local) => (local ? new Date(local + '-03:00').toISOString() : null);
    return { id: `s${++seq}`, subject_id: subjectId, inicio: toUtc(inicioLocal), fim: toUtc(fimLocal), origem: 'timer', status: 'ok', deleted_at: null, ...extra };
}

const NOW = Date.parse('2026-11-01T23:00:00-03:00');

describe('tipos de estudo', () => {
    const sessions = [
        session('cont', '2026-10-31T08:00:00', '2026-10-31T09:00:00', { tipo: 'questoes' }),
        session('cont', '2026-10-31T09:00:00', '2026-10-31T09:30:00', { tipo: 'revisao' }),
        session('dir', '2026-10-31T10:00:00', '2026-10-31T10:45:00', { tipo: 'lei_seca' }),
        session('dir', '2026-11-01T10:00:00', '2026-11-01T10:20:00'),
        session('cont', '2026-11-01T11:00:00', '2026-11-01T12:00:00', { tipo: 'questoes' }),
    ];

    it('soma o tempo por tipo e por matéria × tipo no dia', () => {
        const day = dayStats(buildDaily(sessions, settings, NOW), '2026-10-31');

        expect(day.byType).toEqual({ questoes: H, revisao: 30 * M, lei_seca: 45 * M });
        expect(day.bySubjectType.cont).toEqual({ questoes: H, revisao: 30 * M });
    });

    it('sessão sem tipo entra como "sem tipo" e o período acumula os dias', () => {
        const range = rangeStats(buildDaily(sessions, settings, NOW), '2026-10-31', '2026-11-01', '2026-11-01');

        expect(range.byType).toEqual({ questoes: 2 * H, revisao: 30 * M, lei_seca: 45 * M, [NO_TYPE]: 20 * M });
        expect(range.bySubjectType.cont.questoes).toBe(2 * H);
        expect(range.bySubjectType.dir).toEqual({ lei_seca: 45 * M, [NO_TYPE]: 20 * M });
    });

    it('sugere o último tipo usado na matéria', () => {
        expect(lastTypeFor(sessions, 'cont')).toBe('questoes');
        expect(lastTypeFor(sessions, 'dir')).toBe('lei_seca');
        expect(lastTypeFor(sessions, 'nova')).toBeNull();
    });

    it('nomes dos tipos', () => {
        expect(studyTypeName('lei_seca')).toBe('Lei seca');
        expect(studyTypeName(null)).toBe('Sem tipo');
    });
});
