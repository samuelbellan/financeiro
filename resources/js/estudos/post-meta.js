import {
    BODY_FONT,
    DISPLAY_FONT,
    FOOTER_BRAND,
    THEME,
    canonicalSubjectName,
    ensureFontsLoaded,
    fillRoundRect,
    fitTextToWidth,
    formatMinutesDuration,
} from './story-common.js';

export const POST_WIDTH = 1080;
export const POST_HEIGHT = 1350;
export const POST_PAD = 96;
const CONTENT_WIDTH = POST_WIDTH - POST_PAD * 2;
const FOOTER_LINE_Y = POST_HEIGHT - POST_PAD - 52;
const FOOTER_TEXT_Y = POST_HEIGHT - POST_PAD - 26;

export const CAPTION_HASHTAGS = '#concursos #auditorfiscal #sefaz #iss #guruja #metabatida #concurseiro';

/** Formata minutos como "25h39" ou "39min" quando menor que 1h. */
export function formatPostDuration(minutes) {
    return formatMinutesDuration(minutes);
}

/** Formata percentual no padrão pt-BR com 1 casa decimal (ex.: "73,4%"). */
export function formatPercentPtBr(value, { alwaysDecimal = true } = {}) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }
    const num = Number(value);
    if (!alwaysDecimal && Number.isInteger(num)) {
        return `${num}%`;
    }
    return `${num.toFixed(1).replace('.', ',')}%`;
}

/** Formata número inteiro com separador de milhar pt-BR. */
export function formatIntPtBr(value) {
    const num = Math.max(0, Math.round(Number(value) || 0));
    return num.toLocaleString('pt-BR');
}

/**
 * Divide as disciplinas entre o slide 4 (1/2) e o slide 5 (2/2), preservando a mesma escala (maxMinutes).
 * Com 1–4 disciplinas, tudo fica no slide 4 e o slide 5 é omitido.
 */
export function splitSubjectsForSlides(horasPorMateria) {
    const list = Array.isArray(horasPorMateria) ? horasPorMateria : [];
    const maxMinutes = Math.max(1, ...list.map((item) => Number(item?.minutos) || 0));

    if (list.length <= 4) {
        return {
            part1: list,
            part2: [],
            hasSecondSlide: false,
            maxMinutes,
        };
    }

    const half = Math.ceil(list.length / 2);
    return {
        part1: list.slice(0, half),
        part2: list.slice(half),
        hasSecondSlide: true,
        maxMinutes,
    };
}

/**
 * Define a faixa de horas do slide 6: padrão 03h–21h (passo 3h).
 * Se houver qualquer sessão após 21h (1260 min), expande para 03h–23h (passo 4h).
 */
export function resolveScheduleTimelineRange(dias) {
    let hasAfter21 = false;
    for (const day of dias || []) {
        for (const s of day?.sessoes || []) {
            if (Number(s?.fim_min) > 21 * 60) {
                hasAfter21 = true;
                break;
            }
        }
        if (hasAfter21) break;
    }

    if (hasAfter21) {
        return { startHour: 3, endHour: 23, stepHour: 4, ticks: [3, 7, 11, 15, 19, 23] };
    }
    return { startHour: 3, endHour: 21, stepHour: 3, ticks: [3, 6, 9, 12, 15, 18, 21] };
}

/**
 * Monta o plano ordenado de slides (6 ou 7) já com a numeração correta do rodapé.
 */
export function buildSlidePlan(payload) {
    const { hasSecondSlide } = splitSubjectsForSlides(payload?.horas_por_materia);
    const keys = [
        'capa',
        'numeros',
        'destaques',
        'materias-1',
        ...(hasSecondSlide ? ['materias-2'] : []),
        'horarios',
        'fechamento',
    ];
    const total = keys.length;
    const ordem = payload?.meta?.ordem ?? 0;

    return keys.map((key, idx) => {
        const number = idx + 1;
        return {
            key,
            number,
            total,
            footerRight: number === 1 ? 'Arraste para o lado' : `${number}/${total}`,
            filename: postSlideFilename(ordem, number),
        };
    });
}

export function postSlideFilename(ordem, slideNumber) {
    return `meta-${ordem}-${slideNumber}.png`;
}

/**
 * Gera a legenda fixa (sem IA) seguindo o template oficial.
 */
export function buildPostCaption(payload) {
    const meta = payload?.meta ?? {};
    const ordem = Number(meta.ordem) || 0;
    const statusCurto = String(meta.status_curto ?? '').trim();
    const statusSuffix = statusCurto ? ` ${statusCurto}` : '';

    const feitas = Number(meta.atividades_feitas) || 0;
    const total = Number(meta.atividades_total) || 0;
    const horas = formatPostDuration(meta.minutos_estudados ?? 0);

    const questoes = Number(meta.questoes_resolvidas) || 0;
    const pctAcertoText = meta.pct_acerto !== null && meta.pct_acerto !== undefined
        ? formatPercentPtBr(meta.pct_acerto, { alwaysDecimal: true })
        : null;

    const lines = [
        `✅ Meta ${ordem} concluída${statusSuffix}!`,
        `📚 ${feitas}/${total} atividades · ${horas} de estudo`,
        pctAcertoText !== null
            ? `🎯 ${questoes} questões · ${pctAcertoText} de acerto`
            : `🎯 ${questoes} questões`,
    ];

    const topDestaque = Array.isArray(payload?.destaques) && payload.destaques.length > 0
        ? payload.destaques[0]
        : null;
    if (topDestaque && questoes > 0) {
        const pctTop = formatPercentPtBr(topDestaque.pct_acerto, { alwaysDecimal: false });
        lines.push(`⭐ Destaque: ${topDestaque.titulo} (${pctTop})`);
    }

    lines.push(`Bora pra meta ${ordem + 1} 💪`);
    lines.push('');
    lines.push(CAPTION_HASHTAGS);

    return lines.join('\n');
}

// ── Desenho em <canvas> 1080×1350 ────────────────────────────────────────────

function createSlideCanvas() {
    const canvas = document.createElement('canvas');
    canvas.width = POST_WIDTH;
    canvas.height = POST_HEIGHT;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = THEME.bg;
    ctx.fillRect(0, 0, POST_WIDTH, POST_HEIGHT);
    ctx.textBaseline = 'top';
    return { canvas, ctx };
}

function drawTopLabel(ctx, text) {
    ctx.fillStyle = THEME.accent;
    ctx.font = `700 28px ${DISPLAY_FONT}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '4.5px';
    ctx.fillText(String(text).toUpperCase(), POST_PAD, POST_PAD);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
}

function drawFooter(ctx, rightText, highlightRight = false) {
    ctx.fillStyle = 'rgba(201, 210, 222, 0.14)';
    ctx.fillRect(POST_PAD, FOOTER_LINE_Y, CONTENT_WIDTH, 1);

    ctx.fillStyle = THEME.footer;
    ctx.font = `400 26px ${BODY_FONT}`;
    ctx.fillText(FOOTER_BRAND, POST_PAD, FOOTER_TEXT_Y);

    ctx.fillStyle = highlightRight ? THEME.accent : THEME.footer;
    ctx.font = `${highlightRight ? '600' : '500'} 26px ${BODY_FONT}`;
    const rw = ctx.measureText(rightText).width;
    ctx.fillText(rightText, POST_WIDTH - POST_PAD - rw, FOOTER_TEXT_Y);
}

function drawFittedNumber(ctx, text, x, y, maxWidth, startSize, color) {
    let size = startSize;
    ctx.fillStyle = color;
    while (size > 36) {
        ctx.font = `700 ${size}px ${DISPLAY_FONT}`;
        if (ctx.measureText(text).width <= maxWidth) break;
        size -= 4;
    }
    ctx.fillText(text, x, y);
}

function drawSlideCover(ctx, payload, slide) {
    const meta = payload.meta ?? {};
    const ordem = meta.ordem ?? 0;
    const isCompleted = meta.concluida !== false;

    drawTopLabel(ctx, isCompleted ? 'META CONCLUÍDA' : 'META EM ANDAMENTO');

    fillRoundRect(ctx, POST_PAD, 360, 108, 10, 5, THEME.accent);

    ctx.fillStyle = THEME.text;
    ctx.font = `700 124px ${DISPLAY_FONT}`;
    ctx.fillText(`Meta ${ordem}`, POST_PAD - 4, 408);

    ctx.fillStyle = THEME.accent;
    ctx.font = `700 112px ${DISPLAY_FONT}`;
    ctx.fillText(isCompleted ? 'concluída' : 'em andamento', POST_PAD - 4, 542);

    const statusText = String(meta.status_texto || 'no prazo');
    ctx.font = `700 38px ${DISPLAY_FONT}`;
    const statusTextW = ctx.measureText(statusText).width;
    const pillPadX = 36;
    const pillW = Math.min(CONTENT_WIDTH, statusTextW + pillPadX * 2 + 28);
    const pillH = 82;
    const pillY = 716;

    fillRoundRect(ctx, POST_PAD, pillY, pillW, pillH, 24, THEME.card);
    fillRoundRect(ctx, POST_PAD + 28, pillY + 33, 16, 16, 8, THEME.accent);

    ctx.fillStyle = THEME.text;
    ctx.font = `700 36px ${DISPLAY_FONT}`;
    ctx.fillText(fitTextToWidth(ctx, statusText, pillW - 76), POST_PAD + 60, pillY + 21);

    if (meta.periodo) {
        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `500 38px ${BODY_FONT}`;
        ctx.fillText(String(meta.periodo), POST_PAD, pillY + pillH + 38);
    }

    drawFooter(ctx, slide.footerRight, true);
}

function drawSlideNumbers(ctx, payload, slide) {
    const meta = payload.meta ?? {};
    drawTopLabel(ctx, 'NÚMEROS');

    ctx.fillStyle = THEME.text;
    ctx.font = `700 56px ${DISPLAY_FONT}`;
    ctx.fillText(`Meta ${meta.ordem ?? ''} em números`, POST_PAD, 152);

    const cards = [
        {
            label: 'ATIVIDADES',
            value: `${meta.atividades_feitas ?? 0}/${meta.atividades_total ?? 0}`,
            sub: 'concluídas',
            accent: false,
        },
        {
            label: 'QUESTÕES',
            value: formatIntPtBr(meta.questoes_resolvidas ?? 0),
            sub: 'resolvidas',
            accent: false,
        },
        {
            label: 'ACERTO',
            value: formatPercentPtBr(meta.pct_acerto, { alwaysDecimal: true }),
            sub: (meta.questoes_resolvidas ?? 0) > 0
                ? `${formatIntPtBr(meta.acertos ?? 0)} de ${formatIntPtBr(meta.questoes_resolvidas ?? 0)}`
                : '',
            accent: true,
        },
        {
            label: 'HORAS',
            value: formatPostDuration(meta.minutos_estudados ?? 0),
            sub: 'na meta',
            accent: true,
        },
    ];

    const gap = 28;
    const cardW = (CONTENT_WIDTH - gap) / 2;
    const cardH = 340;
    const startY = 278;

    cards.forEach((card, idx) => {
        const col = idx % 2;
        const row = Math.floor(idx / 2);
        const cx = POST_PAD + col * (cardW + gap);
        const cy = startY + row * (cardH + gap);

        fillRoundRect(ctx, cx, cy, cardW, cardH, 24, THEME.card);

        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `600 24px ${BODY_FONT}`;
        if ('letterSpacing' in ctx) ctx.letterSpacing = '2.8px';
        ctx.fillText(card.label, cx + 38, cy + 44);
        if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

        drawFittedNumber(
            ctx,
            card.value,
            cx + 38,
            cy + 118,
            cardW - 76,
            92,
            card.accent ? THEME.accent : THEME.text
        );

        if (card.sub) {
            ctx.fillStyle = THEME.footer;
            ctx.font = `400 26px ${BODY_FONT}`;
            ctx.fillText(card.sub, cx + 38, cy + 244);
        }
    });

    drawFooter(ctx, slide.footerRight);
}

function drawSlideHighlights(ctx, payload, slide) {
    const destaques = Array.isArray(payload.destaques) ? payload.destaques.slice(0, 3) : [];
    drawTopLabel(ctx, 'DESTAQUES');

    ctx.fillStyle = THEME.text;
    ctx.font = `700 56px ${DISPLAY_FONT}`;
    ctx.fillText('Melhores acertos', POST_PAD, 152);

    if (destaques.length === 0) {
        fillRoundRect(ctx, POST_PAD, 280, CONTENT_WIDTH, 220, 24, THEME.card);
        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `500 32px ${BODY_FONT}`;
        ctx.fillText('Nenhuma atividade com questões nesta meta.', POST_PAD + 44, 372);
        drawFooter(ctx, slide.footerRight);
        return;
    }

    const cardPadX = 40;
    const cardH = 248;
    const gap = 28;
    const startY = 264;
    const trackW = CONTENT_WIDTH - cardPadX * 2;

    destaques.forEach((item, idx) => {
        const cy = startY + idx * (cardH + gap);
        fillRoundRect(ctx, POST_PAD, cy, CONTENT_WIDTH, cardH, 24, THEME.card);

        const pctValue = Math.min(100, Math.max(0, Number(item.pct_acerto) || 0));
        const pctLabel = formatPercentPtBr(pctValue, { alwaysDecimal: true });

        ctx.font = `700 44px ${DISPLAY_FONT}`;
        ctx.fillStyle = idx === 0 ? THEME.accent : THEME.text;
        const pctW = ctx.measureText(pctLabel).width;
        ctx.fillText(pctLabel, POST_PAD + CONTENT_WIDTH - cardPadX - pctW, cy + 34);

        ctx.font = `600 31px ${BODY_FONT}`;
        ctx.fillStyle = THEME.text;
        const label = fitTextToWidth(ctx, item.rotulo || item.titulo || 'Atividade', trackW - pctW - 28);
        ctx.fillText(label, POST_PAD + cardPadX, cy + 42);

        const barY = cy + 116;
        const barH = 22;
        fillRoundRect(ctx, POST_PAD + cardPadX, barY, trackW, barH, 11, THEME.bg);
        if (pctValue > 0) {
            const fillW = Math.max(barH, (trackW * pctValue) / 100);
            fillRoundRect(
                ctx,
                POST_PAD + cardPadX,
                barY,
                fillW,
                barH,
                11,
                idx === 0 ? THEME.accent : THEME.secondaryBar
            );
        }

        const ratioText = `${item.acertos ?? 0}/${item.questoes ?? 0}`;
        ctx.font = `700 30px ${DISPLAY_FONT}`;
        ctx.fillStyle = THEME.textSecondary;
        const ratioW = ctx.measureText(ratioText).width;
        ctx.fillText(ratioText, POST_PAD + CONTENT_WIDTH - cardPadX - ratioW, cy + 168);

        if (item.disciplina) {
            ctx.font = `400 26px ${BODY_FONT}`;
            ctx.fillStyle = THEME.footer;
            const disc = fitTextToWidth(ctx, canonicalSubjectName(item.disciplina), trackW - ratioW - 24);
            ctx.fillText(disc, POST_PAD + cardPadX, cy + 170);
        }
    });

    drawFooter(ctx, slide.footerRight);
}

function drawSlideSubjects(ctx, payload, slide, partIndex) {
    const allSubjects = Array.isArray(payload.horas_por_materia) ? payload.horas_por_materia : [];
    const { part1, part2, hasSecondSlide, maxMinutes } = splitSubjectsForSlides(allSubjects);
    const items = partIndex === 0 ? part1 : part2;

    const topLabel = hasSecondSlide
        ? `HORAS POR MATÉRIA (${partIndex + 1}/2)`
        : 'HORAS POR MATÉRIA';
    drawTopLabel(ctx, topLabel);

    let title;
    if (partIndex === 0) {
        const count = allSubjects.length;
        title = count === 1 ? '1 disciplina na semana' : `${count} disciplinas na semana`;
    } else {
        const count = part2.length;
        title = count === 1 ? 'A outra 1' : `As outras ${count}`;
    }

    ctx.fillStyle = THEME.text;
    ctx.font = `700 56px ${DISPLAY_FONT}`;
    ctx.fillText(title, POST_PAD, 152);

    const cardY = 252;
    if (items.length === 0) {
        fillRoundRect(ctx, POST_PAD, cardY, CONTENT_WIDTH, 200, 24, THEME.card);
        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `500 30px ${BODY_FONT}`;
        ctx.fillText('Nenhuma hora registrada por disciplina.', POST_PAD + 44, cardY + 84);
        drawFooter(ctx, slide.footerRight);
        return;
    }

    const cardPadX = 42;
    const cardPadY = 44;
    const maxCardH = FOOTER_LINE_Y - cardY - 36;
    const availH = maxCardH - cardPadY * 2;
    const rowStep = Math.min(132, Math.max(76, Math.floor(availH / items.length)));
    const compact = rowStep < 104;
    const nameSize = compact ? 25 : 30;
    const durSize = compact ? 26 : 32;
    const barH = compact ? 14 : 18;
    const barOffset = compact ? 36 : 46;
    const lastRowHeight = barOffset + barH;
    const cardH = Math.min(maxCardH, cardPadY * 2 + (items.length - 1) * rowStep + lastRowHeight);

    fillRoundRect(ctx, POST_PAD, cardY, CONTENT_WIDTH, cardH, 24, THEME.card);

    const trackX = POST_PAD + cardPadX;
    const trackW = CONTENT_WIDTH - cardPadX * 2;

    items.forEach((item, idx) => {
        const ry = cardY + cardPadY + idx * rowStep;
        const durText = formatPostDuration(item.minutos);

        ctx.font = `700 ${durSize}px ${DISPLAY_FONT}`;
        ctx.fillStyle = THEME.text;
        const durW = ctx.measureText(durText).width;
        ctx.fillText(durText, POST_PAD + CONTENT_WIDTH - cardPadX - durW, ry);

        ctx.font = `500 ${nameSize}px ${BODY_FONT}`;
        ctx.fillStyle = THEME.text;
        const displayName = fitTextToWidth(
            ctx,
            canonicalSubjectName(item.disciplina),
            trackW - durW - 24
        );
        ctx.fillText(displayName, trackX, ry + 2);

        const barY = ry + barOffset;
        fillRoundRect(ctx, trackX, barY, trackW, barH, barH / 2, THEME.bg);
        const ratio = Math.min(1, Math.max(0, (Number(item.minutos) || 0) / maxMinutes));
        if (ratio > 0) {
            const fillW = Math.max(barH, trackW * ratio);
            const isTop = partIndex === 0 && idx === 0;
            fillRoundRect(ctx, trackX, barY, fillW, barH, barH / 2, isTop ? THEME.accent : THEME.secondaryBar);
        }
    });

    drawFooter(ctx, slide.footerRight);
}

function drawSlideSchedule(ctx, payload, slide) {
    const horarios = payload.horarios ?? {};
    const dias = Array.isArray(horarios.dias) ? horarios.dias : [];
    const diasComEstudo = Number(horarios.dias_com_estudo) || 0;
    const diasTotal = Number(horarios.dias_total) || dias.length || 7;
    const temImportado = Boolean(horarios.tem_importado)
        || dias.some((d) => (d.sessoes || []).some((s) => s.origem === 'importado'));

    drawTopLabel(ctx, 'HORÁRIOS DE ESTUDO');

    ctx.fillStyle = THEME.text;
    ctx.font = `700 52px ${DISPLAY_FONT}`;
    ctx.fillText(`${diasComEstudo} de ${diasTotal} dias com estudo`, POST_PAD, 152);

    const timelineY = 236;
    const cardPadX = 36;
    const cardPadY = 30;
    const legendH = temImportado ? 42 : 0;
    const ticksH = 38;
    const dayCount = Math.max(1, dias.length);
    const maxTracksAreaH = 424 - legendH;
    const rowStep = Math.min(54, Math.max(26, Math.floor(maxTracksAreaH / dayCount)));
    const trackH = Math.max(16, Math.min(34, rowStep - 14));
    const tracksTotalH = dayCount * rowStep - (rowStep - trackH);
    const timelineH = cardPadY * 2 + tracksTotalH + ticksH + legendH;

    fillRoundRect(ctx, POST_PAD, timelineY, CONTENT_WIDTH, timelineH, 24, THEME.card);

    const { startHour, endHour, ticks } = resolveScheduleTimelineRange(dias);
    const startMin = startHour * 60;
    const spanMin = Math.max(60, (endHour - startHour) * 60);

    const labelColW = 104;
    const trackX = POST_PAD + cardPadX + labelColW;
    const trackW = CONTENT_WIDTH - cardPadX * 2 - labelColW;
    const tracksTopY = timelineY + cardPadY;

    // Linhas guia verticais e rótulos de hora
    ctx.font = `500 20px ${BODY_FONT}`;
    ticks.forEach((hour, idx) => {
        const ratio = (hour - startHour) / (endHour - startHour);
        const gx = trackX + trackW * ratio;
        if (idx > 0 && idx < ticks.length - 1) {
            ctx.fillStyle = 'rgba(201, 210, 222, 0.12)';
            ctx.fillRect(Math.round(gx), tracksTopY, 1, tracksTotalH);
        }
        const hLabel = `${String(hour).padStart(2, '0')}h`;
        const lw = ctx.measureText(hLabel).width;
        const lx = idx === 0
            ? trackX
            : idx === ticks.length - 1
                ? trackX + trackW - lw
                : gx - lw / 2;
        ctx.fillStyle = THEME.footer;
        ctx.fillText(hLabel, lx, tracksTopY + tracksTotalH + 14);
    });

    // Faixas por dia
    const dayFontSize = rowStep >= 42 ? 22 : 18;
    dias.forEach((day, idx) => {
        const ty = tracksTopY + idx * rowStep;

        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `500 ${dayFontSize}px ${BODY_FONT}`;
        ctx.fillText(String(day.rotulo || ''), POST_PAD + cardPadX, ty + Math.max(0, (trackH - dayFontSize) / 2));

        fillRoundRect(ctx, trackX, ty, trackW, trackH, 7, THEME.bg);

        for (const s of day.sessoes || []) {
            const leftRatio = Math.max(0, (Number(s.inicio_min) - startMin) / spanMin);
            const rightRatio = Math.min(1, (Number(s.fim_min) - startMin) / spanMin);
            if (rightRatio <= leftRatio) continue;
            const bx = trackX + trackW * leftRatio;
            const bw = Math.max(4, trackW * (rightRatio - leftRatio));
            const color = s.origem === 'importado' ? THEME.secondaryBar : THEME.accent;
            fillRoundRect(ctx, bx, ty + 3, bw, Math.max(6, trackH - 6), 4, color);
        }
    });

    if (temImportado) {
        const ly = tracksTopY + tracksTotalH + ticksH + 10;
        ctx.font = `500 20px ${BODY_FONT}`;
        fillRoundRect(ctx, trackX, ly + 4, 16, 16, 4, THEME.accent);
        ctx.fillStyle = THEME.textSecondary;
        const timerLbl = 'Cronômetro / manual';
        ctx.fillText(timerLbl, trackX + 24, ly);

        const secondX = trackX + 24 + ctx.measureText(timerLbl).width + 32;
        fillRoundRect(ctx, secondX, ly + 4, 16, 16, 4, THEME.secondaryBar);
        ctx.fillStyle = THEME.textSecondary;
        ctx.fillText('Importado (horário estimado)', secondX + 24, ly);
    }

    // 4 cards 2×2 com números em 56px
    const statsStartY = timelineY + timelineH + 26;
    const gap = 24;
    const cardW = (CONTENT_WIDTH - gap) / 2;
    const cardH = Math.min(168, Math.floor((FOOTER_LINE_Y - statsStartY - 26 - gap) / 2));

    const statCards = [
        {
            label: 'MÉDIA POR DIA',
            value: formatPostDuration(horarios.media_dia_min ?? 0),
            accent: true,
        },
        {
            label: 'INÍCIO MÉDIO',
            value: horarios.inicio_medio || '--:--',
            accent: false,
        },
        {
            label: 'ANTES DAS 7H',
            value: `${Math.round(Number(horarios.pct_antes_7h) || 0)}%`,
            accent: false,
        },
        {
            label: 'MAIOR FOCO SEGUIDO',
            value: formatPostDuration(horarios.maior_foco_min ?? 0),
            accent: false,
        },
    ];

    statCards.forEach((card, idx) => {
        const col = idx % 2;
        const row = Math.floor(idx / 2);
        const cx = POST_PAD + col * (cardW + gap);
        const cy = statsStartY + row * (cardH + gap);

        fillRoundRect(ctx, cx, cy, cardW, cardH, 24, THEME.card);

        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `600 22px ${BODY_FONT}`;
        if ('letterSpacing' in ctx) ctx.letterSpacing = '2.2px';
        ctx.fillText(card.label, cx + 32, cy + 28);
        if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

        ctx.fillStyle = card.accent ? THEME.accent : THEME.text;
        ctx.font = `700 56px ${DISPLAY_FONT}`;
        ctx.fillText(card.value, cx + 32, cy + 72);
    });

    drawFooter(ctx, slide.footerRight);
}

function drawSlideClosing(ctx, payload, slide) {
    const ordem = Number(payload?.meta?.ordem) || 0;
    const historico = payload?.historico ?? {};

    drawTopLabel(ctx, 'FECHAMENTO');

    ctx.fillStyle = THEME.textSecondary;
    ctx.font = `700 48px ${DISPLAY_FONT}`;
    ctx.fillText('Próxima:', POST_PAD, 300);

    ctx.fillStyle = THEME.accent;
    ctx.font = `700 108px ${DISPLAY_FONT}`;
    ctx.fillText(`Meta ${ordem + 1}`, POST_PAD - 4, 368);

    const cards = [
        {
            label: 'METAS CONCLUÍDAS',
            value: formatIntPtBr(historico.metas_concluidas ?? 0),
            accent: true,
        },
        {
            label: 'METAS ADIADAS',
            value: formatIntPtBr(historico.metas_adiadas ?? 0),
            accent: false,
        },
    ];

    const gap = 28;
    const cardW = (CONTENT_WIDTH - gap) / 2;
    const cardH = 310;
    const startY = 584;

    cards.forEach((card, idx) => {
        const cx = POST_PAD + idx * (cardW + gap);
        fillRoundRect(ctx, cx, startY, cardW, cardH, 24, THEME.card);

        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `600 24px ${BODY_FONT}`;
        if ('letterSpacing' in ctx) ctx.letterSpacing = '2.6px';
        ctx.fillText(card.label, cx + 38, startY + 46);
        if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

        drawFittedNumber(
            ctx,
            card.value,
            cx + 38,
            startY + 126,
            cardW - 76,
            108,
            card.accent ? THEME.accent : THEME.text
        );
    });

    drawFooter(ctx, slide.footerRight);
}

/**
 * Renderiza todos os slides (6 ou 7) em <canvas> 1080×1350 no padrão visual do Story.
 */
export async function renderPostMetaCanvases(payload) {
    await ensureFontsLoaded();

    const plan = buildSlidePlan(payload);
    return plan.map((slide) => {
        const { canvas, ctx } = createSlideCanvas();

        switch (slide.key) {
            case 'capa':
                drawSlideCover(ctx, payload, slide);
                break;
            case 'numeros':
                drawSlideNumbers(ctx, payload, slide);
                break;
            case 'destaques':
                drawSlideHighlights(ctx, payload, slide);
                break;
            case 'materias-1':
                drawSlideSubjects(ctx, payload, slide, 0);
                break;
            case 'materias-2':
                drawSlideSubjects(ctx, payload, slide, 1);
                break;
            case 'horarios':
                drawSlideSchedule(ctx, payload, slide);
                break;
            case 'fechamento':
                drawSlideClosing(ctx, payload, slide);
                break;
        }

        return { ...slide, canvas };
    });
}
