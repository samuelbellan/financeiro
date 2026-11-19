import { describe, expect, it } from 'vitest';
import {
    CAPTION_HASHTAGS,
    buildPostCaption,
    buildSlidePlan,
    formatPercentPtBr,
    formatPostDuration,
    postSlideFilename,
    resolveScheduleTimelineRange,
    splitSubjectsForSlides,
} from '../../../resources/js/estudos/post-meta.js';

function makeSubjects(count) {
    return Array.from({ length: count }, (_, idx) => ({
        disciplina: `Matéria ${idx + 1}`,
        minutos: (count - idx) * 30,
    }));
}

function makePayload(overrides = {}) {
    return {
        meta: {
            ordem: 62,
            concluida: true,
            status: 'ontime',
            status_texto: 'no prazo',
            status_curto: 'no prazo',
            periodo: '09/11 a 15/11',
            atividades_feitas: 24,
            atividades_total: 24,
            questoes_resolvidas: 218,
            acertos: 160,
            pct_acerto: 73.4,
            minutos_estudados: 1539,
            ...(overrides.meta || {}),
        },
        destaques: overrides.destaques ?? [
            {
                titulo: 'Controle de Constitucionalidade',
                rotulo: 'Controle de Constitucionalidade',
                disciplina: 'Direito Constitucional',
                questoes: 25,
                acertos: 23,
                pct_acerto: 92,
            },
        ],
        horas_por_materia: overrides.horas_por_materia ?? makeSubjects(6),
        horarios: overrides.horarios ?? {
            dias_com_estudo: 7,
            dias_total: 7,
            media_dia_min: 243,
            inicio_medio: '03:37',
            pct_antes_7h: 58,
            maior_foco_min: 62,
            dias: [],
        },
        historico: overrides.historico ?? {
            metas_concluidas: 62,
            metas_adiadas: 2,
        },
    };
}

describe('formatação de duração e percentual', () => {
    it('formata horas no padrão 25h39 e abaixo de 1h como 39min', () => {
        expect(formatPostDuration(1539)).toBe('25h39');
        expect(formatPostDuration(39)).toBe('39min');
        expect(formatPostDuration(0)).toBe('0min');
        expect(formatPostDuration(59)).toBe('59min');
        expect(formatPostDuration(60)).toBe('1h00');
        expect(formatPostDuration(62)).toBe('1h02');
        expect(formatPostDuration(243)).toBe('4h03');
    });

    it('formata percentuais com vírgula em pt-BR', () => {
        expect(formatPercentPtBr(73.4)).toBe('73,4%');
        expect(formatPercentPtBr(92, { alwaysDecimal: false })).toBe('92%');
        expect(formatPercentPtBr(92.5, { alwaysDecimal: false })).toBe('92,5%');
        expect(formatPercentPtBr(null)).toBe('—');
    });
});

describe('divisão das matérias entre slides 4 e 5', () => {
    it('com 1 a 4 disciplinas mantém tudo no slide 4 e omite o slide 5', () => {
        for (const n of [1, 2, 3, 4]) {
            const split = splitSubjectsForSlides(makeSubjects(n));
            expect(split.hasSecondSlide).toBe(false);
            expect(split.part1).toHaveLength(n);
            expect(split.part2).toHaveLength(0);
            expect(split.maxMinutes).toBe(n * 30);
        }
    });

    it('com 5 ou mais disciplinas divide arredondando para cima e preserva a mesma escala', () => {
        const split5 = splitSubjectsForSlides(makeSubjects(5));
        expect(split5.hasSecondSlide).toBe(true);
        expect(split5.part1).toHaveLength(3);
        expect(split5.part2).toHaveLength(2);
        expect(split5.maxMinutes).toBe(150);

        const split9 = splitSubjectsForSlides(makeSubjects(9));
        expect(split9.hasSecondSlide).toBe(true);
        expect(split9.part1).toHaveLength(5);
        expect(split9.part2).toHaveLength(4);
        expect(split9.maxMinutes).toBe(270);

        const split8 = splitSubjectsForSlides(makeSubjects(8));
        expect(split8.part1).toHaveLength(4);
        expect(split8.part2).toHaveLength(4);
    });
});

describe('plano de slides e rodapé renumerado', () => {
    it('gera 7 slides quando há 5 ou mais disciplinas', () => {
        const plan = buildSlidePlan(makePayload({ horas_por_materia: makeSubjects(6) }));
        expect(plan).toHaveLength(7);
        expect(plan.map((s) => s.key)).toEqual([
            'capa', 'numeros', 'destaques', 'materias-1', 'materias-2', 'horarios', 'fechamento',
        ]);
        expect(plan.map((s) => s.footerRight)).toEqual([
            'Arraste para o lado', '2/7', '3/7', '4/7', '5/7', '6/7', '7/7',
        ]);
        expect(plan.map((s) => s.filename)).toEqual([
            'meta-62-1.png', 'meta-62-2.png', 'meta-62-3.png', 'meta-62-4.png',
            'meta-62-5.png', 'meta-62-6.png', 'meta-62-7.png',
        ]);
    });

    it('pula o slide 5 e renumera o rodapé para n/6 quando há 1 a 4 disciplinas', () => {
        const plan = buildSlidePlan(makePayload({ horas_por_materia: makeSubjects(4) }));
        expect(plan).toHaveLength(6);
        expect(plan.map((s) => s.key)).toEqual([
            'capa', 'numeros', 'destaques', 'materias-1', 'horarios', 'fechamento',
        ]);
        expect(plan.map((s) => s.footerRight)).toEqual([
            'Arraste para o lado', '2/6', '3/6', '4/6', '5/6', '6/6',
        ]);
        expect(postSlideFilename(62, 6)).toBe('meta-62-6.png');
    });
});

describe('faixa de horários do slide 6', () => {
    it('usa 03h–21h com passo 3h por padrão', () => {
        const range = resolveScheduleTimelineRange([
            { rotulo: 'seg 09', sessoes: [{ inicio_min: 240, fim_min: 1200 }] },
        ]);
        expect(range).toEqual({
            startHour: 3,
            endHour: 21,
            stepHour: 3,
            ticks: [3, 6, 9, 12, 15, 18, 21],
        });
    });

    it('expande para 03h–23h com passo 4h se houver sessão após as 21h', () => {
        const range = resolveScheduleTimelineRange([
            { rotulo: 'seg 09', sessoes: [{ inicio_min: 1230, fim_min: 1290 }] },
        ]);
        expect(range).toEqual({
            startHour: 3,
            endHour: 23,
            stepHour: 4,
            ticks: [3, 7, 11, 15, 19, 23],
        });
    });
});

describe('legenda fixa (template sem IA)', () => {
    it('monta a legenda completa no prazo com destaque e hashtags', () => {
        const caption = buildPostCaption(makePayload());
        expect(caption).toBe([
            '✅ Meta 62 concluída no prazo!',
            '📚 24/24 atividades · 25h39 de estudo',
            '🎯 218 questões · 73,4% de acerto',
            '⭐ Destaque: Controle de Constitucionalidade (92%)',
            'Bora pra meta 63 💪',
            '',
            CAPTION_HASHTAGS,
        ].join('\n'));
    });

    it('usa "antes do prazo" para meta antecipada', () => {
        const caption = buildPostCaption(makePayload({
            meta: { status: 'anticipated', status_texto: 'antecipada', status_curto: 'antes do prazo' },
        }));
        expect(caption.split('\n')[0]).toBe('✅ Meta 62 concluída antes do prazo!');
    });

    it('omite o status curto quando a meta teve atraso (sem espaço extra)', () => {
        const caption = buildPostCaption(makePayload({
            meta: { status: 'late', status_texto: 'com 2 dias de atraso', status_curto: '' },
        }));
        expect(caption.split('\n')[0]).toBe('✅ Meta 62 concluída!');
    });

    it('omite a linha de destaque quando não há questões ou destaques', () => {
        const caption = buildPostCaption(makePayload({
            meta: { questoes_resolvidas: 0, acertos: 0, pct_acerto: null, minutos_estudados: 45 },
            destaques: [],
        }));
        expect(caption).toBe([
            '✅ Meta 62 concluída no prazo!',
            '📚 24/24 atividades · 45min de estudo',
            '🎯 0 questões',
            'Bora pra meta 63 💪',
            '',
            CAPTION_HASHTAGS,
        ].join('\n'));
    });
});
