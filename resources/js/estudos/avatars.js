// Avatares pixel art (48x48) que evoluem com o total estudado no dia (voltam ao estágio 0 na virada do dia).
// Estilo Cozy Indie (Stardew Valley / Celeste) com itens temáticos de mesa de estudo e animações em passos.
//
// Para criar um avatar novo: adicione uma entrada em AVATARS com um id (letras minúsculas, números e
// hífen), um nome e exatamente 7 desenhos em `estagios` (um por limite de AVATAR_LIMITES_MIN),
// todos no viewBox "0 0 48 48". Ele aparece sozinho no seletor de Ajustes.
// Avatares vetoriais (120x120) ficam em avatars-vetor.js.

import { AVATARS_VETOR } from './avatars-vetor.js';

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

// ── Utilitário Pixel Art SVG ──────────────────────────────────────────────────
// Gera um único elemento <path> contendo múltiplos retângulos pixelados por cor.
const P = (color, rects) => `<path fill="${color}" d="${rects.map(([x, y, w = 1, h = 1]) => `M${x} ${y}h${w}v${h}h-${w}z`).join(' ')}"/>`;

const INK = '#2B2327';
const SHADOW = (cx = 24, w = 22) => P('rgba(43, 35, 39, 0.16)', [
    [cx - Math.floor(w / 2), 42, w, 2],
    [cx - Math.floor(w / 2) + 2, 41, w - 4, 3]
]);

// ── Itens Cozy de Mesa de Estudos ─────────────────────────────────────────────
const zzz = `<g class="zzz-pixel">${P('#8B8797', [
    [33, 15, 3, 1], [35, 16, 1, 1], [33, 17, 3, 1],
    [38, 9, 4, 1], [40, 10, 2, 1], [38, 11, 4, 1]
])}</g>`;

const postIt = `<g class="desk-postit">${P('#FFE26A', [[5, 36, 6, 5]])}${P('#E5C541', [[9, 39, 2, 2]])}${P('#3A86FF', [[12, 33, 1, 6], [13, 32, 1, 2]])}${P(INK, [[11, 39, 1, 1]])}</g>`;

const coffeeCup = `<g class="desk-coffee">${P('#F7EBE1', [
    [37, 35, 6, 6], [43, 36, 2, 3]
])}${P('#C05C46', [
    [37, 37, 6, 2]
])}${P('#3A2318', [
    [38, 35, 4, 1]
])}<g class="steam-pixel">${P('#B8C5D0', [
    [39, 32, 1, 2], [41, 30, 1, 2], [40, 27, 1, 2]
])}</g></g>`;

const bookStack = `<g class="desk-books">${P('#2A4365', [
    [4, 38, 10, 3]
])}${P('#F7FAFC', [
    [6, 39, 7, 1]
])}${P('#7B1E30', [
    [5, 34, 9, 3]
])}${P('#F7FAFC', [
    [7, 35, 6, 1]
])}${P('#22543D', [
    [6, 30, 8, 3]
])}${P('#F7FAFC', [
    [7, 31, 6, 1]
])}</g>`;

const deskLamp = `<g class="desk-lamp">${P('#4A5568', [
    [39, 40, 5, 2]
])}${P('#718096', [
    [41, 24, 2, 16], [38, 22, 4, 2]
])}${P('#E85D04', [
    [34, 23, 6, 4]
])}${P('#FAA307', [
    [33, 27, 8, 2]
])}<g class="lamp-glow">${P('rgba(255, 230, 109, 0.22)', [
    [25, 29, 14, 3], [22, 32, 18, 4], [20, 36, 22, 5]
])}</g></g>`;

const stars = `<g class="star-pixel">${P('#FFD166', [
    [7, 14, 3, 1], [8, 13, 1, 3],
    [39, 9, 3, 1], [40, 8, 1, 3],
    [42, 22, 3, 1], [43, 21, 1, 3]
])}${P('#FFFBEB', [
    [8, 14, 1, 1], [40, 9, 1, 1], [43, 22, 1, 1]
])}</g>`;

// ── BICHINHO (Pipo) ────────────────────────────────────────────────────────────
const SHELL_MAIN = '#FFF0D4';
const SHELL_SHADOW = '#E4D0B5';
const SPOT = '#E68A35';
const BODY_MINT = '#74D6A7';
const BODY_DARK = '#4FA984';
const BELLY = '#BCEBCE';
const BLUSH = '#F28E9B';

const eggBody = P(SHELL_MAIN, [
    [20, 18, 8, 2], [18, 20, 12, 3], [16, 23, 16, 5],
    [15, 28, 18, 7], [16, 35, 16, 4], [18, 39, 12, 2]
]) + P(SHELL_SHADOW, [
    [15, 32, 2, 4], [16, 36, 2, 3], [18, 39, 12, 2],
    [27, 36, 5, 3], [31, 32, 2, 4]
]) + P(INK, [
    [20, 17, 8, 1], [18, 18, 2, 2], [16, 20, 2, 3], [15, 23, 1, 5],
    [14, 28, 1, 7], [15, 35, 1, 3], [16, 38, 2, 2], [18, 40, 12, 1],
    [30, 38, 2, 2], [32, 35, 1, 3], [33, 28, 1, 7], [32, 23, 1, 5],
    [30, 20, 2, 3], [28, 18, 2, 2]
]) + P(SPOT, [
    [18, 25, 3, 3], [28, 22, 3, 3], [19, 33, 2, 3], [27, 31, 3, 2], [23, 28, 2, 2]
]);

const eggSleepFace = `<g class="avatar-face">${P(INK, [[19, 29, 3, 1], [26, 29, 3, 1]])}${P(BLUSH, [[18, 31, 3, 2], [27, 31, 3, 2]])}</g>`;

const eggCrack = P(INK, [
    [17, 27, 3, 1], [19, 26, 1, 2], [20, 28, 2, 1],
    [22, 26, 2, 1], [24, 27, 2, 2], [26, 25, 3, 1], [29, 27, 2, 1]
]);

const eggPeekingEyes = `<g class="avatar-eyes">${P(INK, [[20, 29, 3, 3], [26, 29, 3, 3]])}${P('#FFFFFF', [[20, 29, 1, 1], [26, 29, 1, 1]])}${P(BLUSH, [[18, 33, 3, 2], [27, 33, 3, 2]])}</g>`;

const halfShell = P(SHELL_MAIN, [
    [15, 30, 18, 7], [17, 37, 14, 3], [19, 40, 10, 1]
]) + P(SHELL_SHADOW, [
    [15, 34, 2, 3], [17, 37, 2, 2], [19, 40, 10, 1], [27, 37, 4, 2]
]) + P(INK, [
    [15, 30, 2, 1], [17, 32, 2, 1], [19, 30, 2, 1], [21, 32, 2, 1],
    [23, 30, 2, 1], [25, 32, 2, 1], [27, 30, 2, 1], [29, 32, 2, 1], [31, 30, 2, 1],
    [14, 31, 1, 6], [15, 37, 2, 2], [17, 39, 2, 1], [19, 41, 10, 1],
    [29, 39, 2, 1], [31, 37, 2, 2], [33, 31, 1, 6]
]) + P(SPOT, [[19, 34, 3, 3], [27, 33, 3, 2]]);

const babyHead = P(BODY_MINT, [
    [19, 20, 10, 2], [17, 22, 14, 3], [16, 25, 16, 6]
]) + P(INK, [
    [19, 19, 10, 1], [17, 20, 2, 2], [16, 22, 1, 3],
    [31, 20, 2, 2], [32, 22, 1, 3]
]);

const fullBody = `<g class="avatar-body">` + P(BODY_MINT, [
    [19, 19, 10, 2], [17, 21, 14, 3], [16, 24, 16, 12],
    [18, 36, 12, 3]
]) + P(BELLY, [
    [21, 28, 6, 7], [22, 27, 4, 9]
]) + P(BODY_DARK, [
    [16, 32, 2, 4], [18, 36, 3, 2], [27, 36, 3, 2], [30, 32, 2, 4]
]) + P(INK, [
    [19, 18, 10, 1], [17, 19, 2, 2], [16, 21, 1, 3], [15, 24, 1, 12],
    [16, 36, 2, 2], [18, 38, 2, 2], [28, 38, 2, 2], [30, 36, 2, 2],
    [32, 24, 1, 12], [31, 21, 1, 3], [29, 19, 2, 2],
    [17, 40, 4, 1], [27, 40, 4, 1]
]) + P(BODY_DARK, [
    [18, 39, 3, 2], [27, 39, 3, 2]
]);

const happyFace = (y = 25) => `<g class="avatar-eyes">${P(INK, [[19, y, 2, 3], [27, y, 2, 3]])}${P('#FFFFFF', [[19, y, 1, 1], [27, y, 1, 1]])}${P(BLUSH, [[17, y + 3, 3, 2], [28, y + 3, 3, 2]])}${P(INK, [[22, y + 4, 4, 1], [23, y + 5, 2, 1]])}</g>`;

const shellHat = P(SHELL_MAIN, [
    [19, 16, 10, 3], [18, 19, 12, 2]
]) + P(INK, [
    [19, 15, 10, 1], [18, 16, 1, 3], [29, 16, 1, 3],
    [18, 20, 2, 1], [21, 19, 2, 1], [24, 20, 2, 1], [27, 19, 2, 1]
]) + P(SPOT, [[22, 16, 2, 2]]);

const headphones = `<g class="headphone-pulse">${P('#4361EE', [
    [18, 16, 12, 2], [14, 22, 3, 6], [31, 22, 3, 6]
])}${P('#3A0CA3', [
    [15, 23, 1, 4], [32, 23, 1, 4]
])}${P(INK, [
    [18, 15, 12, 1], [13, 22, 1, 6], [34, 22, 1, 6],
    [14, 28, 3, 1], [31, 28, 3, 1]
])}</g>`;

const ears = P(BODY_MINT, [
    [14, 15, 3, 5], [13, 17, 2, 4],
    [31, 15, 3, 5], [33, 17, 2, 4]
]) + P(BLUSH, [
    [15, 17, 1, 3], [32, 17, 1, 3]
]) + P(INK, [
    [14, 14, 3, 1], [13, 15, 1, 3], [12, 18, 1, 3],
    [31, 14, 3, 1], [34, 15, 1, 3], [35, 18, 1, 3]
]);

const glasses = `<g class="avatar-glasses">${P('#F3C053', [
    [18, 24, 5, 1], [18, 28, 5, 1], [18, 25, 1, 3], [22, 25, 1, 3],
    [25, 24, 5, 1], [25, 28, 5, 1], [25, 25, 1, 3], [29, 25, 1, 3],
    [23, 26, 2, 1]
])}</g>`;

const openBook = `<g class="held-book">${P('#2A4365', [
    [17, 33, 14, 6]
])}${P('#F7FAFC', [
    [18, 33, 5, 5], [25, 33, 5, 5]
])}${P('#718096', [
    [19, 34, 3, 1], [19, 36, 3, 1],
    [26, 34, 3, 1], [26, 36, 3, 1]
])}${P(INK, [
    [23, 33, 2, 6],
    [16, 34, 2, 2], [30, 34, 2, 2]
])}</g>`;

const cap = `<g class="grad-cap">${P(INK, [
    [23, 11, 2, 1], [19, 12, 10, 2], [15, 14, 18, 2], [19, 16, 10, 1],
    [20, 17, 8, 2]
])}${P('#FFD166', [
    [24, 12, 1, 1], [27, 14, 1, 4], [27, 18, 2, 2]
])}</g>`;

const BICHINHO = [
    // 0: Ovo dormindo
    `${SHADOW(24, 20)}<g class="avatar-body">${eggBody}${eggSleepFace}</g>${zzz}`,
    // 1: Ovo rachado espiando + post-it
    `${SHADOW(24, 20)}<g class="avatar-body">${eggBody}${eggCrack}${eggPeekingEyes}</g>${postIt}`,
    // 2: Bebê saindo da casca + café + post-it
    `${SHADOW(24, 22)}<g class="avatar-body">${babyHead}${happyFace(23)}${halfShell}</g>${postIt}${coffeeCup}`,
    // 3: Filhote com fone de ouvido + casca de chapéu + café + post-it
    `${SHADOW(24, 22)}${fullBody}${shellHat}${happyFace(25)}${headphones}</g>${postIt}${coffeeCup}`,
    // 4: Cresceu com orelhas + pilha de livros + fones + café
    `${SHADOW(24, 22)}${fullBody}${ears}${happyFace(25)}${headphones}</g>${bookStack}${coffeeCup}`,
    // 5: Estudioso com óculos + livro aberto + abajur + livros + café
    `${SHADOW(24, 22)}${fullBody}${ears}${happyFace(25)}${glasses}${openBook}</g>${bookStack}${deskLamp}${coffeeCup}`,
    // 6: Mestre formado com capelo + estrelas douradas + abajur + livros + café
    `${SHADOW(24, 22)}${fullBody}${ears}${happyFace(25)}${glasses}${openBook}${cap}</g>${bookStack}${deskLamp}${coffeeCup}${stars}`
];

// ── PLANTA (Flora) ─────────────────────────────────────────────────────────────
const POT_MAIN = '#B85D31';
const POT_RIM = '#D17748';
const POT_SHADOW = '#8A3E1B';
const SOIL = '#4B2D1D';
const LEAF_BRIGHT = '#89DF7C';
const LEAF_MID = '#4EBA5C';
const LEAF_DARK = '#287538';

const pot = P(POT_RIM, [
    [16, 33, 16, 3]
]) + P(POT_MAIN, [
    [17, 36, 14, 4], [18, 40, 12, 2]
]) + P(POT_SHADOW, [
    [17, 37, 2, 3], [18, 40, 2, 2], [26, 37, 5, 3]
]) + P(SOIL, [
    [18, 33, 12, 2]
]) + P(INK, [
    [16, 32, 16, 1], [15, 33, 1, 3], [32, 33, 1, 3],
    [16, 36, 1, 4], [17, 40, 1, 2], [18, 42, 12, 1],
    [30, 40, 1, 2], [31, 36, 1, 4]
]);

const seedSleep = `<g class="avatar-face">${P('#E8C07D', [
    [22, 31, 4, 3]
])}${P('#937138', [
    [22, 33, 4, 1]
])}${P(INK, [
    [23, 32, 2, 1]
])}</g>`;

const sprout = P(LEAF_MID, [
    [23, 29, 2, 4]
]) + P(LEAF_BRIGHT, [
    [20, 27, 3, 2], [25, 27, 3, 2]
]) + P(LEAF_DARK, [
    [20, 29, 3, 1], [25, 29, 3, 1]
]) + P(INK, [
    [20, 26, 3, 1], [25, 26, 3, 1]
]);

const plantStem2 = P(LEAF_DARK, [
    [23, 23, 2, 10]
]) + P(LEAF_BRIGHT, [
    [16, 24, 7, 3], [25, 23, 7, 3]
]) + P(LEAF_MID, [
    [15, 27, 8, 2], [25, 26, 8, 2]
]) + P(LEAF_DARK, [
    [16, 29, 6, 1], [26, 28, 6, 1]
]);

const bigBush = P(LEAF_DARK, [
    [23, 16, 2, 17],
    [14, 27, 9, 3], [25, 26, 9, 3]
]) + P(LEAF_MID, [
    [13, 23, 10, 4], [25, 22, 10, 4],
    [16, 18, 8, 4], [24, 17, 8, 4]
]) + P(LEAF_BRIGHT, [
    [15, 22, 8, 2], [25, 21, 8, 2],
    [18, 16, 6, 2], [24, 15, 6, 2],
    [22, 13, 4, 3]
]) + P(INK, [
    [13, 22, 10, 1], [25, 21, 10, 1],
    [16, 17, 8, 1], [24, 16, 8, 1]
]);

const buds = `<g class="flower-buds">${P('#F48C9E', [
    [15, 20, 3, 3]
])}${P('#A888DB', [
    [30, 19, 3, 3]
])}${P('#FFD166', [
    [22, 12, 4, 3]
])}</g>`;

const fullFlowers = `<g class="blooming-flowers">${P('#F48C9E', [
    [13, 19, 5, 5]
])}${P('#FFFFFF', [
    [14, 20, 3, 3]
])}${P('#FFD166', [
    [15, 21, 1, 1]
])}
${P('#A888DB', [
    [29, 18, 5, 5]
])}${P('#FFFFFF', [
    [30, 19, 3, 3]
])}${P('#FFD166', [
    [31, 20, 1, 1]
])}
${P('#FFD166', [
    [21, 10, 6, 6]
])}${P('#FFFFFF', [
    [22, 11, 4, 4]
])}${P('#F77F00', [
    [23, 12, 2, 2]
])}
${P('#FFB703', [
    [12, 27, 4, 4], [32, 26, 4, 4]
])}</g>`;

const PLANTA = [
    // 0: Semente dormindo
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${seedSleep}</g>${zzz}`,
    // 1: Broto + post-it
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${sprout}</g>${postIt}`,
    // 2: Caule e folhas + café + post-it
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${plantStem2}</g>${postIt}${coffeeCup}`,
    // 3: Planta com fone de ouvido + café + post-it
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${plantStem2}${headphones}</g>${postIt}${coffeeCup}`,
    // 4: Arbusto rico + livros + fones + café
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${bigBush}${headphones}</g>${bookStack}${coffeeCup}`,
    // 5: Botões florais + abajur + livros + café
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${bigBush}${buds}</g>${bookStack}${deskLamp}${coffeeCup}`,
    // 6: Floração mágica + abajur + livros + estrelas + café
    `${SHADOW(24, 20)}<g class="avatar-body">${pot}${bigBush}${fullFlowers}</g>${bookStack}${deskLamp}${coffeeCup}${stars}`
];

export const AVATARS = {
    bichinho: { nome: 'Bichinho', estagios: BICHINHO, estilo: 'pixel' },
    planta: { nome: 'Planta', estagios: PLANTA, estilo: 'pixel' },
    ...Object.fromEntries(Object.entries(AVATARS_VETOR).map(([id, a]) => [id, { ...a, estilo: 'vetor' }])),
};

/** SVG completo do avatar (cai no padrão se o id não existir, ex.: escolhido em outro aparelho mais novo). */
export function avatarSvg(id, stage, { size = 120, label = '' } = {}) {
    const avatar = AVATARS[id] ?? AVATARS[AVATAR_PADRAO];
    const art = avatar.estagios[Math.max(0, Math.min(stage, avatar.estagios.length - 1))];
    const [cls, viewBox] = avatar.estilo === 'vetor' ? ['vec', '0 0 120 120'] : ['px', '0 0 48 48'];
    return `<svg class="${cls}" viewBox="${viewBox}" width="${size}" height="${size}" role="img" aria-label="${label || `${avatar.nome}, estágio ${stage + 1} de 7`}">${art}</svg>`;
}
