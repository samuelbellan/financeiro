import { describe, expect, it } from 'vitest';
import { planImport, toUuid } from '../../../resources/js/estudos/importer.js';

const backup = (overrides = {}) => ({
    app: 'estudos',
    versao: 1,
    subjects: [
        { id: 's-cont', nome: 'Contabilidade', cor: '#00a8f0', ordem: 0 },
        { id: 's-port', nome: 'Português', cor: '#E57373', ordem: 1 },
    ],
    sessions: [
        { id: 'x1', subject_id: 's-cont', inicio: '2026-10-01T04:00:00.000Z', fim: '2026-10-01T06:00:00.000Z', origem: 'importado' },
        { id: 'x2', subject_id: 's-port', inicio: '2026-10-01T06:00:00.000Z', fim: '2026-10-01T07:00:00.000Z', origem: 'importado' },
    ],
    ddays: [],
    ...overrides,
});

const empty = { subjects: [], sessions: [], ddays: [] };

describe('planImport', () => {
    it('importa matérias e sessões novas', () => {
        const plan = planImport(backup(), empty);
        expect(plan.subjects.map((s) => s.nome)).toEqual(['Contabilidade', 'Português']);
        expect(plan.subjects[0].cor).toBe('#00A8F0');
        expect(plan.sessions).toHaveLength(2);
        expect(plan.sessions[0].origem).toBe('importado');
    });

    it('reaproveita matéria local com o mesmo nome (ignorando acentos e caixa)', () => {
        const local = { ...empty, subjects: [{ id: 'local-port', nome: 'portugues', cor: '#111111', ordem: 0 }] };
        const plan = planImport(backup(), local);
        expect(plan.subjects.map((s) => s.nome)).toEqual(['Contabilidade']);
        expect(plan.sessions.find((s) => s.id === toUuid('x2')).subject_id).toBe('local-port');
        expect(plan.subjects[0].ordem).toBe(1);
    });

    it('reimportar o mesmo arquivo não duplica', () => {
        const first = planImport(backup(), empty);
        const local = { subjects: first.subjects, sessions: first.sessions, ddays: [] };
        const second = planImport(backup(), local);
        expect(second.subjects).toHaveLength(0);
        expect(second.sessions).toHaveLength(0);
        expect(second.skipped).toBe(2);
    });

    it('descarta sessões inválidas e excluídas', () => {
        const plan = planImport(backup({
            sessions: [
                { id: 'a', subject_id: 's-cont', inicio: '2026-10-01T06:00:00Z', fim: '2026-10-01T05:00:00Z' },
                { id: 'b', subject_id: 'nao-existe', inicio: '2026-10-01T04:00:00Z', fim: '2026-10-01T05:00:00Z' },
                { id: 'c', subject_id: 's-cont', inicio: '2026-10-01T04:00:00Z', fim: '2026-10-01T05:00:00Z', deleted_at: '2026-10-02T00:00:00Z' },
                { id: 'd', subject_id: 's-cont', inicio: '2026-10-01T04:00:00Z', fim: '2026-10-01T05:00:00Z', origem: 'timer' },
            ],
        }), empty);
        expect(plan.sessions.map((s) => s.id)).toEqual([toUuid('d')]);
        expect(plan.invalid).toBe(2);
    });

    it('recusa arquivo que não é backup de estudos', () => {
        expect(() => planImport({ app: 'outro' }, empty)).toThrow('Arquivo inválido');
    });

    it('converte ids que não são UUID em UUIDs estáveis', () => {
        const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;
        expect(toUuid('imp-1')).toMatch(uuid);
        expect(toUuid('imp-1')).toBe(toUuid('imp-1'));
        expect(toUuid('imp-1')).not.toBe(toUuid('imp-2'));
        expect(toUuid('9DBDA53F-8CF4-4DBB-B646-9DD8002CD77C')).toBe('9dbda53f-8cf4-4dbb-b646-9dd8002cd77c');
        const plan = planImport(backup(), empty);
        expect(plan.subjects.every((s) => uuid.test(s.id))).toBe(true);
        expect(plan.sessions.every((s) => uuid.test(s.id) && plan.subjects.some((x) => x.id === s.subject_id))).toBe(true);
    });
});
