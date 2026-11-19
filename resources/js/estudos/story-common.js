export const THEME = {
    bg: '#0F1B2D',
    card: '#18283F',
    text: '#F4F1EA',
    textSecondary: '#C9D2DE',
    footer: '#9AA7BA',
    accent: '#F5B83D',
    secondaryBar: '#6F86A6',
};

export const SUBJECT_ALIASES = {
    'D. Civil': 'Direito Civil',
    Contabilidade: 'Contabilidade Geral e Avançada',
    'TI - CD': 'Tecnologia da Informação',
    'TI - BD': 'Tecnologia da Informação',
    'Tecnologia da Informação': 'Tecnologia da Informação',
    'D. Tributário': 'Direito Tributário',
    'D. Administrativo': 'Direito Administrativo',
    'D. Constitucional': 'Direito Constitucional',
    'D. Empresarial': 'Direito Empresarial (Comercial)',
    'Mat. Financeira': 'Matemática Financeira',
    Auditoria: 'Auditoria Privada',
};

export const DISPLAY_FONT = '"Space Grotesk", system-ui, -apple-system, sans-serif';
export const BODY_FONT = '"Inter", system-ui, -apple-system, sans-serif';
export const FOOTER_BRAND = 'Samuel · Rumo a Auditor Fiscal';

export function canonicalSubjectName(name) {
    const trimmed = String(name || 'Matéria').trim();
    return SUBJECT_ALIASES[trimmed] ?? trimmed;
}

export function formatHoursMinutes(ms) {
    const totalMin = Math.max(0, Math.round(ms / 60_000));
    const h = Math.floor(totalMin / 60);
    const m = totalMin % 60;
    return `${h}h${String(m).padStart(2, '0')}`;
}

export function formatCompactDuration(ms) {
    const totalMin = Math.max(0, Math.round(ms / 60_000));
    if (totalMin < 60) return `${totalMin}min`;
    return formatHoursMinutes(ms);
}

/** Formata minutos no padrão "25h39" ou "39min" (< 1h). */
export function formatMinutesDuration(minutes) {
    const totalMin = Math.max(0, Math.round(Number(minutes) || 0));
    if (totalMin < 60) return `${totalMin}min`;
    const h = Math.floor(totalMin / 60);
    const m = totalMin % 60;
    return `${h}h${String(m).padStart(2, '0')}`;
}

export function roundRectPath(ctx, x, y, w, h, r) {
    const radius = Math.min(r, w / 2, h / 2);
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + w, y, x + w, y + h, radius);
    ctx.arcTo(x + w, y + h, x, y + h, radius);
    ctx.arcTo(x, y + h, x, y, radius);
    ctx.arcTo(x, y, x + w, y, radius);
    ctx.closePath();
}

export function fillRoundRect(ctx, x, y, w, h, r, color) {
    ctx.fillStyle = color;
    roundRectPath(ctx, x, y, w, h, r);
    ctx.fill();
}

export function fitTextToWidth(ctx, text, maxWidth) {
    let str = String(text ?? '');
    if (ctx.measureText(str).width <= maxWidth) return str;
    while (ctx.measureText(`${str}…`).width > maxWidth && str.length > 3) {
        str = str.slice(0, -1).trimEnd();
    }
    return `${str}…`;
}

export async function ensureFontsLoaded() {
    if (typeof document === 'undefined' || !document.fonts) return;
    await Promise.allSettled([
        document.fonts.load(`700 48px ${DISPLAY_FONT}`),
        document.fonts.load(`400 30px ${BODY_FONT}`),
        document.fonts.load(`500 30px ${BODY_FONT}`),
        document.fonts.load(`600 30px ${BODY_FONT}`),
        document.fonts.load(`700 30px ${BODY_FONT}`),
        document.fonts.ready,
    ]);
}
