import { describe, expect, it } from 'vitest';
import { memberSessionMs, memberTodayMs } from '../../../resources/js/estudos/rooms.js';

const M = 60_000;

describe('tempo ao vivo dos membros da sala', () => {
    const fetchedAt = 1_000_000;
    const serverTime = Date.parse('2026-11-01T15:00:00Z');

    it('quem está estudando soma o tempo desde a busca', () => {
        const member = { hoje_ms: 90 * M, estudando_desde: '2026-11-01T14:30:00.000Z' };

        expect(memberTodayMs(member, fetchedAt, fetchedAt + 2 * M)).toBe(92 * M);
        expect(memberSessionMs(member, serverTime, fetchedAt, fetchedAt + 2 * M)).toBe(32 * M);
    });

    it('quem não está estudando fica parado', () => {
        const member = { hoje_ms: 45 * M, estudando_desde: null };

        expect(memberTodayMs(member, fetchedAt, fetchedAt + 10 * M)).toBe(45 * M);
        expect(memberSessionMs(member, serverTime, fetchedAt, fetchedAt + 10 * M)).toBeNull();
    });
});
