// Avatares que evoluem com o total estudado no dia (voltam ao estágio 0 na virada do dia).
//
// Para criar um avatar novo: adicione uma entrada em AVATARS com um id (letras minúsculas, números e
// hífen), um nome e exatamente 7 desenhos SVG em `estagios` (um por limite de AVATAR_LIMITES_MIN),
// todos no viewBox "0 0 120 120". Ele aparece sozinho no seletor de Ajustes.

/** Minutos estudados no dia para alcançar cada estágio (0 a 6). */
export const AVATAR_LIMITES_MIN = [0, 30, 60, 120, 180, 240, 300];
export const AVATAR_PADRAO = 'bichinho';

/** Estágio (0–6) para o total do dia e quanto falta para o próximo (null no último). */
export function avatarStage(totalMs) {
    const minutes = totalMs / 60_000;
    let stage = 0;
    AVATAR_LIMITES_MIN.forEach((limit, i) => { if (minutes >= limit) stage = i; });
    const next = AVATAR_LIMITES_MIN[stage + 1];
    return { stage, nextInMs: next === undefined ? null : Math.ceil((next - minutes) * 60_000) };
}

// ── Peças de desenho ──────────────────────────────────────────────────────────

const INK = '#3B3B3B';
const shadow = (rx = 34) => `<ellipse cx="60" cy="110" rx="${rx}" ry="6" fill="#000" opacity=".12"/>`;
const eyes = (y, gap = 9, r = 3.2) => `<circle cx="${60 - gap}" cy="${y}" r="${r}" fill="${INK}"/><circle cx="${60 + gap}" cy="${y}" r="${r}" fill="${INK}"/>`
    + `<circle cx="${60 - gap + 1}" cy="${y - 1}" r="1" fill="#fff"/><circle cx="${60 + gap + 1}" cy="${y - 1}" r="1" fill="#fff"/>`;
const smile = (y, w = 6) => `<path d="M${60 - w} ${y} Q60 ${y + w} ${60 + w} ${y}" fill="none" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>`;
const blush = (y, gap = 17) => `<ellipse cx="${60 - gap}" cy="${y}" rx="4.5" ry="2.6" fill="#F49AA7" opacity=".8"/><ellipse cx="${60 + gap}" cy="${y}" rx="4.5" ry="2.6" fill="#F49AA7" opacity=".8"/>`;

// Bichinho "Pipo": ovo de bolinhas laranja que vira uma criaturinha verde estudiosa.
const SHELL = '#FFF3DC';
const SPOT = '#F7A65A';
const BODY = '#7FD1AE';
const BODY_DARK = '#4FA984';
const egg = `<path d="M60 16 C83 16 95 50 95 72 C95 95 80 107 60 107 C40 107 25 95 25 72 C25 50 37 16 60 16Z" fill="${SHELL}" stroke="${INK}" stroke-width="3"/>`;
const spots = `<circle cx="44" cy="52" r="6" fill="${SPOT}"/><circle cx="72" cy="40" r="5" fill="${SPOT}"/><circle cx="76" cy="78" r="7" fill="${SPOT}"/><circle cx="46" cy="88" r="5" fill="${SPOT}"/><circle cx="60" cy="66" r="3.5" fill="${SPOT}"/>`;
const halfShell = `<path d="M25 70 L33 62 L41 70 L49 62 L57 70 L65 62 L73 70 L81 62 L89 70 L95 74 C95 96 80 107 60 107 C40 107 25 96 25 74 Z" fill="${SHELL}" stroke="${INK}" stroke-width="3" stroke-linejoin="round"/>`
    + `<circle cx="46" cy="90" r="5" fill="${SPOT}"/><circle cx="76" cy="86" r="6" fill="${SPOT}"/>`;
const feet = (y, gap = 14) => `<ellipse cx="${60 - gap}" cy="${y}" rx="9" ry="5" fill="${BODY_DARK}" stroke="${INK}" stroke-width="2.5"/><ellipse cx="${60 + gap}" cy="${y}" rx="9" ry="5" fill="${BODY_DARK}" stroke="${INK}" stroke-width="2.5"/>`;
const ears = `<path d="M34 52 L30 28 L50 42 Z" fill="${BODY}" stroke="${INK}" stroke-width="3" stroke-linejoin="round"/><path d="M86 52 L90 28 L70 42 Z" fill="${BODY}" stroke="${INK}" stroke-width="3" stroke-linejoin="round"/>`;
const bigBody = `<ellipse cx="60" cy="72" rx="36" ry="34" fill="${BODY}" stroke="${INK}" stroke-width="3"/><ellipse cx="60" cy="82" rx="20" ry="16" fill="#BDEBD6"/>`;
const glasses = `<circle cx="51" cy="62" r="8" fill="none" stroke="${INK}" stroke-width="2.5"/><circle cx="69" cy="62" r="8" fill="none" stroke="${INK}" stroke-width="2.5"/><path d="M59 62 L61 62" stroke="${INK}" stroke-width="2.5"/>`;
const book = `<path d="M38 86 L60 90 L82 86 L82 104 L60 108 L38 104 Z" fill="#4F86F7" stroke="${INK}" stroke-width="2.5" stroke-linejoin="round"/><path d="M60 90 L60 108" stroke="${INK}" stroke-width="2.5"/><path d="M44 92 L54 94 M44 97 L54 99 M66 94 L76 92 M66 99 L76 97" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>`;
const cap = `<path d="M60 14 L94 26 L60 38 L26 26 Z" fill="${INK}"/><path d="M44 32 L44 42 Q60 50 76 42 L76 32" fill="${INK}"/><path d="M94 26 L94 42" stroke="#F6C343" stroke-width="2.5"/><circle cx="94" cy="44" r="3" fill="#F6C343"/>`;
const star = (x, y, s = 1) => `<path transform="translate(${x} ${y}) scale(${s})" d="M0 -7 L2 -2 L7 -2 L3 1.5 L4.5 7 L0 3.8 L-4.5 7 L-3 1.5 L-7 -2 L-2 -2 Z" fill="#F6C343"/>`;

const BICHINHO = [
    // 0 — ovo dormindo
    `${shadow(30)}${egg}${spots}<text x="86" y="24" font-size="13" font-weight="700" fill="${INK}" opacity=".55">z</text><text x="96" y="14" font-size="9" font-weight="700" fill="${INK}" opacity=".4">z</text>`,
    // 1 — ovo rachado, espiando
    `${shadow(30)}${egg}${spots}<path d="M36 50 L45 43 L51 51 L60 43 L68 51 L75 43 L84 50" fill="none" stroke="${INK}" stroke-width="2.5" stroke-linejoin="round"/>${eyes(60, 9, 3)}`,
    // 2 — filhote saindo da casca
    `${shadow(32)}<circle cx="60" cy="54" r="28" fill="${BODY}" stroke="${INK}" stroke-width="3"/>${eyes(50)}${blush(58, 15)}${smile(57, 5)}${halfShell}`,
    // 3 — filhote inteiro, com chapeuzinho de casca
    `${shadow(32)}${feet(102, 13)}<ellipse cx="60" cy="74" rx="30" ry="28" fill="${BODY}" stroke="${INK}" stroke-width="3"/>`
        + `<path d="M42 52 L48 44 L54 50 L60 42 L66 50 L72 44 L78 52 Q60 40 42 52Z" fill="${SHELL}" stroke="${INK}" stroke-width="2.5" stroke-linejoin="round"/>${eyes(70)}${blush(78, 16)}${smile(78, 5)}`,
    // 4 — cresceu: orelhas e bracinhos
    `${shadow()}${feet(104, 16)}${ears}${bigBody}<path d="M26 76 Q16 70 20 62" fill="none" stroke="${INK}" stroke-width="3" stroke-linecap="round"/><path d="M94 76 Q104 70 100 62" fill="none" stroke="${INK}" stroke-width="3" stroke-linecap="round"/>${eyes(62)}${blush(72, 20)}${smile(72, 6)}`,
    // 5 — estudioso: óculos e livro
    `${shadow()}${feet(104, 16)}${ears}${bigBody}${eyes(62, 9, 2.6)}${glasses}${blush(73, 22)}${smile(74, 5)}${book}`,
    // 6 — formado: capelo e estrelas
    `${shadow()}${feet(104, 16)}${ears}${bigBody}${eyes(62, 9, 2.6)}${glasses}${blush(73, 22)}${smile(74, 5)}${book}${cap}${star(14, 40)}${star(106, 60, .8)}${star(18, 86, .7)}`,
];

// Planta: semente no vaso até florescer.
const LEAF = '#6DBE6D';
const LEAF_EDGE = '#2F6B3A';
const pot = `<path d="M38 86 L82 86 L76 108 L44 108 Z" fill="#C9774A" stroke="${INK}" stroke-width="3" stroke-linejoin="round"/><rect x="33" y="78" width="54" height="10" rx="3" fill="#D98A5B" stroke="${INK}" stroke-width="3"/>`;
const soil = '<ellipse cx="60" cy="79" rx="23" ry="3.5" fill="#5B3A29"/>';
const stem = (top) => `<path d="M60 79 L60 ${top}" stroke="${LEAF_EDGE}" stroke-width="3.5" stroke-linecap="round"/>`;
const leaf = (x, y, rx, ry, angle) => `<ellipse cx="${x}" cy="${y}" rx="${rx}" ry="${ry}" transform="rotate(${angle} ${x} ${y})" fill="${LEAF}" stroke="${LEAF_EDGE}" stroke-width="2"/>`;
const flower = (x, y, color) => [0, 72, 144, 216, 288].map((a) => {
    const rad = (a * Math.PI) / 180;
    return `<circle cx="${(x + Math.cos(rad) * 5).toFixed(1)}" cy="${(y + Math.sin(rad) * 5).toFixed(1)}" r="4.2" fill="${color}" stroke="${INK}" stroke-width="1.2"/>`;
}).join('') + `<circle cx="${x}" cy="${y}" r="3.2" fill="#F6C343" stroke="${INK}" stroke-width="1.2"/>`;
const bush = `${stem(36)}${leaf(46, 64, 12, 6, -30)}${leaf(74, 64, 12, 6, 30)}${leaf(44, 50, 13, 6.5, -25)}${leaf(76, 50, 13, 6.5, 25)}${leaf(48, 36, 12, 6, -35)}${leaf(72, 36, 12, 6, 35)}${leaf(60, 26, 7, 11, 0)}`;

const PLANTA = [
    `${shadow(26)}${pot}${soil}<ellipse cx="60" cy="77" rx="4" ry="2.6" fill="#E8C07D" stroke="${INK}" stroke-width="1.5"/>`,
    `${shadow(26)}${pot}${soil}${stem(68)}${leaf(55, 68, 5, 2.6, -30)}${leaf(65, 68, 5, 2.6, 30)}`,
    `${shadow(26)}${pot}${soil}${stem(58)}${leaf(51, 60, 9, 4.5, -30)}${leaf(69, 60, 9, 4.5, 30)}`,
    `${shadow(26)}${pot}${soil}${stem(46)}${leaf(49, 66, 10, 5, -30)}${leaf(71, 66, 10, 5, 30)}${leaf(50, 50, 10, 5, -35)}${leaf(70, 50, 10, 5, 35)}`,
    `${shadow(28)}${pot}${soil}${stem(36)}${leaf(46, 66, 12, 6, -30)}${leaf(74, 66, 12, 6, 30)}${leaf(47, 52, 12, 6, -30)}${leaf(73, 52, 12, 6, 30)}${leaf(50, 38, 11, 5.5, -35)}${leaf(70, 38, 11, 5.5, 35)}`,
    `${shadow(30)}${pot}${soil}${bush}<circle cx="38" cy="44" r="3.5" fill="#F49AA7" stroke="${INK}" stroke-width="1.2"/><circle cx="84" cy="40" r="3.5" fill="#F49AA7" stroke="${INK}" stroke-width="1.2"/><circle cx="60" cy="16" r="3.5" fill="#F49AA7" stroke="${INK}" stroke-width="1.2"/>`,
    `${shadow(30)}${pot}${soil}${bush}${flower(36, 42, '#F49AA7')}${flower(86, 38, '#B39DDB')}${flower(60, 14, '#FFFFFF')}${flower(30, 62, '#F7A65A')}${flower(90, 62, '#F49AA7')}`,
];

export const AVATARS = {
    bichinho: { nome: 'Bichinho', estagios: BICHINHO },
    planta: { nome: 'Planta', estagios: PLANTA },
};

/** SVG completo do avatar (cai no padrão se o id não existir, ex.: escolhido em outro aparelho mais novo). */
export function avatarSvg(id, stage, { size = 120, label = '' } = {}) {
    const avatar = AVATARS[id] ?? AVATARS[AVATAR_PADRAO];
    const art = avatar.estagios[Math.max(0, Math.min(stage, avatar.estagios.length - 1))];
    return `<svg viewBox="0 0 120 120" width="${size}" height="${size}" role="img" aria-label="${label || `${avatar.nome}, estágio ${stage + 1} de 7`}">${art}</svg>`;
}
