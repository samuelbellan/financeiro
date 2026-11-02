import { describe, expect, it } from 'vitest';
import { AVATARS, AVATAR_LIMITES_MIN, avatarStage, avatarSvg } from '../../../resources/js/estudos/avatars.js';

const MIN = 60_000;

describe('avatarStage', () => {
    it('segue os limites 0 · 30 min · 1h · 2h · 3h · 4h · 5h', () => {
        expect([0, 29, 30, 59, 60, 119, 120, 180, 240, 299, 300, 600].map((m) => avatarStage(m * MIN).stage))
            .toEqual([0, 0, 1, 1, 2, 2, 3, 4, 5, 5, 6, 6]);
    });

    it('informa quanto falta para o próximo estágio', () => {
        expect(avatarStage(20 * MIN).nextInMs).toBe(10 * MIN);
        expect(avatarStage(300 * MIN).nextInMs).toBeNull();
    });
});

describe('AVATARS', () => {
    it('todo avatar tem um desenho para cada limite e id válido', () => {
        for (const [id, avatar] of Object.entries(AVATARS)) {
            expect(id).toMatch(/^[a-z0-9-]+$/);
            expect(avatar.estagios).toHaveLength(AVATAR_LIMITES_MIN.length);
            avatar.estagios.forEach((svg) => expect(svg).not.toMatch(/undefined|NaN/));
        }
    });

    it('todo desenho fecha os grupos que abre', () => {
        for (const avatar of Object.values(AVATARS)) {
            avatar.estagios.forEach((svg) => expect((svg.match(/<g[\s>]/g) ?? []).length).toBe((svg.match(/<\/g>/g) ?? []).length));
        }
    });

    it('avatares vetoriais usam o viewBox 120x120 e os pixel art 48x48', () => {
        expect(avatarSvg('heroi', 0)).toContain('viewBox="0 0 120 120"');
        expect(avatarSvg('bichinho', 0)).toContain('viewBox="0 0 48 48"');
    });

    it('cada desenho com degradê recebe ids próprios', () => {
        const a = avatarSvg('familia', 3);
        const b = avatarSvg('familia', 3);
        expect(a).not.toContain('__ID__');
        expect(a.match(/id="([^"]+)"/)[1]).not.toBe(b.match(/id="([^"]+)"/)[1]);
    });

    it('id desconhecido cai no avatar padrão', () => {
        expect(avatarSvg('nao-existe', 0)).toBe(avatarSvg('bichinho', 0));
    });
});
