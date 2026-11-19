import { formatClock } from './metrics.js';
import {
    BODY_FONT,
    DISPLAY_FONT,
    FOOTER_BRAND,
    THEME,
    canonicalSubjectName,
    ensureFontsLoaded,
    fillRoundRect,
    formatCompactDuration,
    formatHoursMinutes,
} from './story-common.js';

const STORY_WIDTH = 1080;
const STORY_HEIGHT = 1920;
const PAD_X = 96;
const PAD_TOP = 256;
const PAD_BOTTOM = 256;
const CONTENT_WIDTH = STORY_WIDTH - PAD_X * 2;

function formatStoryDate(day) {
    const [y, m, d] = day.split('-').map(Number);
    const date = new Date(y, m - 1, d);
    const weekday = date
        .toLocaleDateString('pt-BR', { weekday: 'long' })
        .replace(/-feira$/i, '');
    const month = date.toLocaleDateString('pt-BR', { month: 'long' });
    const capitalized = weekday.charAt(0).toUpperCase() + weekday.slice(1);
    return `${capitalized}, ${d} de ${month}`;
}

function dayMidnightMs(day) {
    const [y, m, d] = day.split('-').map(Number);
    return new Date(y, m - 1, d, 0, 0, 0, 0).getTime();
}

function consolidateSubjects(bySubject, subjects) {
    const byId = new Map(subjects.map((s) => [s.id, s.nome]));
    const totals = new Map();
    for (const [subjectId, ms] of Object.entries(bySubject || {})) {
        if (!ms || ms <= 0) continue;
        const name = canonicalSubjectName(byId.get(subjectId) ?? 'Matéria removida');
        totals.set(name, (totals.get(name) ?? 0) + ms);
    }
    return [...totals.entries()]
        .map(([name, ms]) => ({ name, ms }))
        .sort((a, b) => b.ms - a.ms);
}

function resolveStartEnd(stats) {
    if (stats.startMs !== null && stats.endMs !== null) {
        return { startMs: stats.startMs, endMs: stats.endMs };
    }
    const pieces = stats.pieces || [];
    if (pieces.length === 0) return { startMs: null, endMs: null };
    let startMs = Infinity;
    let endMs = -Infinity;
    for (const p of pieces) {
        if (p.start < startMs) startMs = p.start;
        if (p.end > endMs) endMs = p.end;
    }
    return { startMs, endMs };
}

function timelineRangeHours(pieces, midnightMs) {
    let startHour = 3;
    let endHour = 23;
    for (const p of pieces) {
        const sH = (p.start - midnightMs) / 3_600_000;
        const eH = (p.end - midnightMs) / 3_600_000;
        if (sH < startHour) startHour = Math.floor(sH);
        if (eH > endHour) endHour = Math.ceil(eH);
    }
    return { startHour, endHour };
}

/**
 * Gera um canvas 1080×1920 com as estatísticas do dia no padrão visual do Story.
 */
export async function renderStudyStoryCanvas({ day, stats, subjects, settings }) {
    await ensureFontsLoaded();

    const canvas = document.createElement('canvas');
    canvas.width = STORY_WIDTH;
    canvas.height = STORY_HEIGHT;
    const ctx = canvas.getContext('2d');

    // Fundo
    ctx.fillStyle = THEME.bg;
    ctx.fillRect(0, 0, STORY_WIDTH, STORY_HEIGHT);
    ctx.textBaseline = 'top';

    let y = PAD_TOP;

    // 1. Cabeçalho
    ctx.fillStyle = THEME.accent;
    ctx.font = `700 28px ${DISPLAY_FONT}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '4.5px';
    ctx.fillText('ESTUDO DO DIA', PAD_X, y);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
    y += 42;

    ctx.fillStyle = THEME.text;
    ctx.font = `700 46px ${DISPLAY_FONT}`;
    ctx.fillText(formatStoryDate(day), PAD_X, y);
    y += 74;

    // 2. Número grande + barra da meta diária
    ctx.fillStyle = THEME.text;
    ctx.font = `700 184px ${DISPLAY_FONT}`;
    ctx.fillText(formatHoursMinutes(stats.totalMs), PAD_X - 4, y);
    y += 188;

    const metaMin = Number(settings?.meta_diaria_min || 300);
    const metaMs = metaMin * 60_000;
    const pctRaw = metaMs > 0 ? (stats.totalMs / metaMs) * 100 : 0;
    const pctClamped = Math.min(100, Math.max(0, pctRaw));
    const metaAtingida = metaMs > 0 && stats.totalMs >= metaMs;

    fillRoundRect(ctx, PAD_X, y, CONTENT_WIDTH, 18, 9, THEME.card);
    if (pctClamped > 0) {
        const fillW = Math.max(18, (CONTENT_WIDTH * pctClamped) / 100);
        fillRoundRect(ctx, PAD_X, y, fillW, 18, 9, THEME.accent);
    }
    y += 36;

    ctx.font = `400 30px ${BODY_FONT}`;
    if (metaAtingida) {
        ctx.fillStyle = THEME.accent;
        ctx.font = `700 30px ${BODY_FONT}`;
        ctx.fillText('Meta do dia batida', PAD_X, y);
    } else {
        const pctLabel = `${Math.round(pctRaw)}%`;
        ctx.font = `700 30px ${BODY_FONT}`;
        ctx.fillStyle = THEME.text;
        ctx.fillText(pctLabel, PAD_X, y);
        const pctWidth = ctx.measureText(pctLabel).width;

        ctx.font = `400 30px ${BODY_FONT}`;
        ctx.fillStyle = THEME.textSecondary;
        ctx.fillText(` da meta de ${formatHoursMinutes(metaMs)}`, PAD_X + pctWidth, y);
    }
    y += 76;

    // 3. Linha do tempo
    const cardPadX = 40;
    const cardPadY = 34;
    const timelineCardH = 198;
    fillRoundRect(ctx, PAD_X, y, CONTENT_WIDTH, timelineCardH, 24, THEME.card);

    ctx.fillStyle = THEME.textSecondary;
    ctx.font = `600 24px ${BODY_FONT}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '3.2px';
    ctx.fillText('LINHA DO TEMPO', PAD_X + cardPadX, y + cardPadY);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    const trackX = PAD_X + cardPadX;
    const trackY = y + cardPadY + 48;
    const trackW = CONTENT_WIDTH - cardPadX * 2;
    const trackH = 54;
    fillRoundRect(ctx, trackX, trackY, trackW, trackH, 10, THEME.bg);

    const pieces = stats.pieces || [];
    const midnightMs = dayMidnightMs(day);
    const { startHour, endHour } = timelineRangeHours(pieces, midnightMs);
    const spanHours = Math.max(1, endHour - startHour);

    // Linhas divisórias e rótulos (6 marcações)
    const tickCount = 5;
    ctx.font = `500 22px ${BODY_FONT}`;
    for (let i = 0; i <= tickCount; i++) {
        const ratio = i / tickCount;
        const tickX = trackX + trackW * ratio;
        if (i > 0 && i < tickCount) {
            ctx.fillStyle = 'rgba(201, 210, 222, 0.1)';
            ctx.fillRect(Math.round(tickX), trackY, 1, trackH);
        }
        const hourLabel = `${String(Math.round(startHour + spanHours * ratio)).padStart(2, '0')}h`;
        ctx.fillStyle = THEME.footer;
        const labelW = ctx.measureText(hourLabel).width;
        const labelX = i === 0 ? trackX : i === tickCount ? trackX + trackW - labelW : tickX - labelW / 2;
        ctx.fillText(hourLabel, labelX, trackY + trackH + 16);
    }

    // Blocos de sessões
    const rangeStartMs = midnightMs + startHour * 3_600_000;
    const rangeSpanMs = spanHours * 3_600_000;
    for (const p of pieces) {
        const leftRatio = Math.max(0, (p.start - rangeStartMs) / rangeSpanMs);
        const rightRatio = Math.min(1, (p.end - rangeStartMs) / rangeSpanMs);
        if (rightRatio <= leftRatio) continue;
        const bx = trackX + trackW * leftRatio;
        const bw = Math.max(4, trackW * (rightRatio - leftRatio));
        const color = p.session?.origem === 'importado' ? THEME.secondaryBar : THEME.accent;
        fillRoundRect(ctx, bx, trackY + 4, bw, trackH - 8, 4, color);
    }

    y += timelineCardH + 30;

    // 4. Horas por matéria
    const subjectRows = consolidateSubjects(stats.bySubject, subjects);
    const maxSubjectMs = subjectRows[0]?.ms ?? 1;
    const compactSubjects = subjectRows.length > 5;
    const rowStep = compactSubjects ? 56 : 70;
    const maxRowsFit = Math.floor((STORY_HEIGHT - PAD_BOTTOM - y - 280) / rowStep);
    const visibleSubjects = subjectRows.slice(0, Math.max(1, maxRowsFit));
    const subjectsCardH = cardPadY * 2 + 48 + Math.max(1, visibleSubjects.length) * rowStep - (compactSubjects ? 12 : 16);

    fillRoundRect(ctx, PAD_X, y, CONTENT_WIDTH, subjectsCardH, 24, THEME.card);

    ctx.fillStyle = THEME.textSecondary;
    ctx.font = `600 24px ${BODY_FONT}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '3.2px';
    ctx.fillText('HORAS POR MATÉRIA', PAD_X + cardPadX, y + cardPadY);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    let sy = y + cardPadY + 50;
    visibleSubjects.forEach((row, idx) => {
        const durText = formatCompactDuration(row.ms);
        ctx.font = `700 30px ${DISPLAY_FONT}`;
        ctx.fillStyle = THEME.text;
        const durW = ctx.measureText(durText).width;
        ctx.fillText(durText, PAD_X + CONTENT_WIDTH - cardPadX - durW, sy);

        ctx.font = `500 30px ${BODY_FONT}`;
        ctx.fillStyle = THEME.text;
        const maxNameW = trackW - durW - 24;
        let displayName = row.name;
        while (ctx.measureText(displayName).width > maxNameW && displayName.length > 4) {
            displayName = `${displayName.slice(0, -2)}…`;
        }
        ctx.fillText(displayName, trackX, sy);

        const barY = sy + 42;
        fillRoundRect(ctx, trackX, barY, trackW, 14, 7, THEME.bg);
        const barW = Math.max(14, (trackW * row.ms) / maxSubjectMs);
        fillRoundRect(ctx, trackX, barY, barW, 14, 7, idx === 0 ? THEME.accent : THEME.secondaryBar);

        sy += rowStep;
    });

    y += subjectsCardH + 30;

    // 5. Três cards: Início, Término e Maior foco
    const gap = 24;
    const statW = (CONTENT_WIDTH - gap * 2) / 3;
    const statH = 148;
    const { startMs, endMs } = resolveStartEnd(stats);
    const maxFocusMs = stats.maxFocusMs > 0 ? stats.maxFocusMs : Math.max(0, ...pieces.map((p) => p.end - p.start));

    const statCards = [
        { label: 'INÍCIO', value: startMs !== null ? formatClock(startMs, day, settings) : '--:--', accent: false },
        { label: 'TÉRMINO', value: endMs !== null ? formatClock(endMs, day, settings) : '--:--', accent: false },
        { label: 'MAIOR FOCO', value: formatCompactDuration(maxFocusMs), accent: true },
    ];

    statCards.forEach((card, idx) => {
        const cx = PAD_X + idx * (statW + gap);
        fillRoundRect(ctx, cx, y, statW, statH, 24, THEME.card);

        ctx.fillStyle = THEME.textSecondary;
        ctx.font = `600 22px ${BODY_FONT}`;
        if ('letterSpacing' in ctx) ctx.letterSpacing = '2.4px';
        ctx.fillText(card.label, cx + 28, y + 30);
        if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

        ctx.fillStyle = card.accent ? THEME.accent : THEME.text;
        ctx.font = `700 52px ${DISPLAY_FONT}`;
        ctx.fillText(card.value, cx + 28, y + 68);
    });

    y += statH + 40;

    // 6. Rodapé
    const footerText = FOOTER_BRAND;
    ctx.fillStyle = THEME.footer;
    ctx.font = `400 26px ${BODY_FONT}`;
    const footerW = ctx.measureText(footerText).width;
    ctx.fillText(footerText, (STORY_WIDTH - footerW) / 2, y);

    return canvas;
}
