import { describe, expect, it } from 'vitest';
import { DEFAULT_SETTINGS } from '../../../resources/js/estudos/metrics.js';
import { widgetView } from '../../../resources/js/estudos/widget.js';

const H = 3_600_000;
const M = 60_000;
const settings = { ...DEFAULT_SETTINGS, virada_hora: 3, timezone: 'America/Sao_Paulo' }; // UTC-3

const subjects = [
    { id: 'dir', nome: 'Direito', cor: '#4F86F7', arquivada_em: null, deleted_at: null },
    { id: 'cont', nome: 'Contabilidade', cor: '#F25F5C', arquivada_em: null, deleted_at: null },
    { id: 'velha', nome: 'Arquivada', cor: '#7D8597', arquivada_em: '2026-10-01T00:00:00.000Z', deleted_at: null },
];

let seq = 0;
function session(subjectId, inicioLocal, fimLocal, extra = {}) {
    const toUtc = (local) => (local ? new Date(local + '-03:00').toISOString() : null);
    return { id: `s${++seq}`, subject_id: subjectId, inicio: toUtc(inicioLocal), fim: toUtc(fimLocal), origem: 'timer', status: 'ok', deleted_at: null, ...extra };
}

const at = (local) => Date.parse(local + '-03:00');

describe('widgetView', () => {
    it('sessão rodando: matéria, tempo da sessão e total do dia', () => {
        const sessions = [
            session('dir', '2026-10-31T08:00:00', '2026-10-31T09:00:00'),
            session('cont', '2026-10-31T14:00:00', null),
        ];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T14:30:00'));

        expect(view.mode).toBe('running');
        expect(view.subject.nome).toBe('Contabilidade');
        expect(view.subjectId).toBe('cont');
        expect(view.sessionMs).toBe(30 * M);
        expect(view.todayMs).toBe(H + 30 * M);
    });

    it('descanso: tempo desde a última sessão e retomar a mesma matéria', () => {
        const sessions = [session('dir', '2026-10-31T08:00:00', '2026-10-31T09:00:00')];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T09:20:00'));

        expect(view.mode).toBe('rest');
        expect(view.restMs).toBe(20 * M);
        expect(view.resumeId).toBe('dir');
        expect(view.todayMs).toBe(H);
    });

    it('total do dia respeita a virada (02h conta para o dia anterior)', () => {
        const sessions = [
            session('dir', '2026-10-31T01:00:00', '2026-10-31T02:00:00'), // dia 30
            session('dir', '2026-10-31T10:00:00', '2026-10-31T10:45:00'), // dia 31
        ];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T11:00:00'));

        expect(view.todayMs).toBe(45 * M);
    });

    it('depois de 6h sem estudar fica "idle", mas ainda permite retomar', () => {
        const sessions = [session('dir', '2026-10-31T08:00:00', '2026-10-31T09:00:00')];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T16:00:00'));

        expect(view.mode).toBe('idle');
        expect(view.restMs).toBeNull();
        expect(view.resumeId).toBe('dir');
    });

    it('última matéria arquivada ou excluída: sem alvo para retomar', () => {
        const archived = widgetView({ sessions: [session('velha', '2026-10-31T08:00:00', '2026-10-31T09:00:00')], subjects, settings }, at('2026-10-31T09:10:00'));
        expect(archived.mode).toBe('rest');
        expect(archived.resumeId).toBeNull();

        const gone = widgetView({ sessions: [session('apagada', '2026-10-31T08:00:00', '2026-10-31T09:00:00')], subjects, settings }, at('2026-10-31T09:10:00'));
        expect(gone.subject).toBeNull();
        expect(gone.resumeId).toBeNull();
    });

    it('ignora sessões excluídas e manuais/importadas ao escolher o que retomar', () => {
        const sessions = [
            session('dir', '2026-10-31T08:00:00', '2026-10-31T09:00:00'),
            session('cont', '2026-10-31T09:30:00', '2026-10-31T10:00:00', { deleted_at: '2026-10-31T13:00:00.000Z' }),
            session('cont', '2026-10-31T10:00:00', '2026-10-31T10:30:00', { origem: 'manual' }),
        ];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T11:00:00'));

        expect(view.resumeId).toBe('dir');
        expect(view.todayMs).toBe(H + 30 * M);
    });

    it('sessão aberta vinda de outro aparelho pela sincronização aparece como rodando', () => {
        const sessions = [
            session('dir', '2026-10-31T08:00:00', '2026-10-31T09:00:00'),
            session('cont', '2026-10-31T09:05:00', null, { device_id: 'celular' }),
        ];
        const view = widgetView({ sessions, subjects, settings }, at('2026-10-31T09:15:00'));

        expect(view.mode).toBe('running');
        expect(view.subjectId).toBe('cont');
        expect(view.sessionMs).toBe(10 * M);
    });

    it('sem nenhuma sessão: idle sem alvo', () => {
        const view = widgetView({ sessions: [], subjects, settings }, at('2026-10-31T09:00:00'));

        expect(view).toMatchObject({ mode: 'idle', subject: null, resumeId: null, todayMs: 0 });
    });
});
