// Avatares vetoriais (viewBox 120x120): quanto mais tempo estudado no dia, mais o personagem se esforça.
// Temas: RPG (Herói), comédia (Estudante Caótico), família (Família) e animais (Hamster).
//
// As classes v-* ganham animação em estudos.css (mais rápidas com o cronômetro rodando). Nunca coloque uma
// classe v-* num elemento que tenha o atributo transform: a animação CSS substituiria a posição. Envolva num <g>.

const INK = '#2B2327';
const LINE = `stroke="${INK}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"`;
const FONT = 'font-family="system-ui, sans-serif" font-weight="800"';

// ── Peças comuns ──────────────────────────────────────────────────────────────

const shadow = (rx = 34, cx = 60) => `<ellipse cx="${cx}" cy="110" rx="${rx}" ry="5" fill="#000" opacity=".13"/>`;

/** Braço/perna com contorno: traço grosso escuro e o traço colorido por cima. */
const limb = (d, color, w = 6) => `<path d="${d}" fill="none" stroke="${INK}" stroke-width="${w + 4}" stroke-linecap="round" stroke-linejoin="round"/>`
    + `<path d="${d}" fill="none" stroke="${color}" stroke-width="${w}" stroke-linecap="round" stroke-linejoin="round"/>`;

const hand = (x, y, color, r = 4.5) => `<circle cx="${x}" cy="${y}" r="${r}" fill="${color}" ${LINE}/>`;

const sweat = (x, y, delay = 0) => `<path class="v-sweat" style="animation-delay:${delay}s" d="M${x} ${y} q-4.5 7 0 9.5 q4.5 -2.5 0 -9.5z" fill="#8FD3FF" stroke="${INK}" stroke-width="1.5" stroke-linejoin="round"/>`;

const zzz = (x, y) => `<g class="v-zzz" fill="${INK}" opacity=".6" ${FONT}><text x="${x}" y="${y}" font-size="14">z</text><text x="${x + 10}" y="${y - 11}" font-size="10">z</text></g>`;

const sparkle = (x, y, s = 1, color = '#FFD166', delay = 0) => `<g transform="translate(${x} ${y}) scale(${s})"><path class="v-twinkle" style="animation-delay:${delay}s" d="M0 -8 Q1.5 -1.5 8 0 Q1.5 1.5 0 8 Q-1.5 1.5 -8 0 Q-1.5 -1.5 0 -8z" fill="${color}" stroke="${INK}" stroke-width="1.2"/></g>`;

const actionLines = (d) => `<path class="v-lines" d="${d}" fill="none" stroke="${INK}" stroke-width="2.2" stroke-linecap="round" opacity=".7"/>`;

const confetti = () => `<g class="v-confetti">${[
    [12, 20, '#E85D75', 20], [26, 8, '#4F86F7', -30], [96, 10, '#86D46B', 45], [108, 26, '#F6C343', -15],
    [18, 44, '#F6C343', 60], [104, 48, '#E85D75', 10], [40, 4, '#86D46B', 15], [82, 4, '#B39DDB', -40],
].map(([x, y, c, a]) => `<rect x="${x}" y="${y}" width="6" height="3.5" rx="1" fill="${c}" transform="rotate(${a} ${x + 3} ${y + 2})"/>`).join('')}</g>`;

/**
 * Rosto. Humores: dormindo, feliz, bocejo, focado, nervoso, esforco, grito, vitoria.
 * Os olhos piscam (v-blink) e a boca fica r * 2.6 abaixo dos olhos.
 */
function face(cx, cy, { gap = 7, r = 3, mood = 'feliz', blush = true } = {}) {
    const lx = cx - gap;
    const rx = cx + gap;
    const my = cy + r * 2.6;
    const stroke = `fill="none" stroke="${INK}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
    const openEyes = (scale = 1) => `<g class="v-blink">${[lx, rx].map((x) => `<ellipse cx="${x}" cy="${cy}" rx="${r * 0.85 * scale}" ry="${r * 1.15 * scale}" fill="${INK}"/>`
        + `<circle cx="${x + r * 0.3}" cy="${cy - r * 0.45}" r="${r * 0.35}" fill="#fff"/>`).join('')}</g>`;
    const closedEyes = `<path d="M${lx - r} ${cy} q${r} ${r} ${2 * r} 0 M${rx - r} ${cy} q${r} ${r} ${2 * r} 0" ${stroke}/>`;
    const happyEyes = `<path d="M${lx - r} ${cy + 1} q${r} ${-r * 1.4} ${2 * r} 0 M${rx - r} ${cy + 1} q${r} ${-r * 1.4} ${2 * r} 0" ${stroke}/>`;
    const sleepyEyes = `<path d="M${lx - r} ${cy} h${2 * r} M${rx - r} ${cy} h${2 * r}" ${stroke}/>`;
    const brows = (angle) => `<path d="M${lx - r - 1} ${cy - r - 4 - angle} l${2 * r + 2} ${angle * 2} M${rx + r + 1} ${cy - r - 4 - angle} l${-(2 * r + 2)} ${angle * 2}" ${stroke}/>`;

    const parts = {
        dormindo: closedEyes + `<path d="M${cx - 2} ${my} q2 1.5 4 0" ${stroke}/>`,
        feliz: openEyes() + `<path d="M${cx - 4} ${my - 1} q4 4.5 8 0" ${stroke}/>`,
        bocejo: sleepyEyes + `<ellipse cx="${cx}" cy="${my + 1}" rx="3.5" ry="4.5" fill="#7A2E2E" stroke="${INK}" stroke-width="1.8"/>`,
        focado: openEyes() + brows(1) + `<path d="M${cx - 3} ${my} h6" ${stroke}/>`,
        nervoso: openEyes(1.15) + brows(-1) + `<path d="M${cx - 5} ${my} q2.5 -2 5 0 q2.5 2 5 0" ${stroke}/>`,
        esforco: openEyes() + brows(2) + `<rect x="${cx - 5}" y="${my - 2.5}" width="10" height="5" rx="1.5" fill="#fff" stroke="${INK}" stroke-width="1.8"/><path d="M${cx - 5} ${my} h10" stroke="${INK}" stroke-width="1.1"/>`,
        grito: openEyes() + brows(2.5) + `<ellipse cx="${cx}" cy="${my + 1}" rx="4" ry="4.5" fill="#7A2E2E" stroke="${INK}" stroke-width="1.8"/>`,
        vitoria: happyEyes + `<path d="M${cx - 5} ${my - 1.5} q5 7 10 0z" fill="#7A2E2E" stroke="${INK}" stroke-width="1.8" stroke-linejoin="round"/>`,
    };
    const cheeks = blush ? `<ellipse cx="${lx - 2}" cy="${cy + r * 1.9}" rx="3.5" ry="2" fill="#F49AA7" opacity=".75"/><ellipse cx="${rx + 2}" cy="${cy + r * 1.9}" rx="3.5" ry="2" fill="#F49AA7" opacity=".75"/>` : '';
    return cheeks + parts[mood];
}

// ── HERÓI (RPG): aprendiz com espada de madeira até herói lendário ───────────

const H_SKIN = '#F5C9A0';
const H_HAIR = '#7A4A2A';
const H_TUNIC = '#4F7CD9';
const H_PANTS = '#3F4A6B';
const H_BOOT = '#5A3A22';
const STEEL = '#C3CED9';
const GOLD = '#F2C94C';

const heroLegs = limb('M54 94 V103', H_PANTS, 7) + limb('M66 94 V103', H_PANTS, 7)
    + `<path d="M48 101 h10 v6 h-12 q0 -6 2 -6z" fill="${H_BOOT}" ${LINE}/><path d="M62 101 h10 q2 0 2 6 h-12z" fill="${H_BOOT}" ${LINE}/>`;
const heroBody = `<rect x="45" y="66" width="30" height="31" rx="11" fill="${H_TUNIC}" ${LINE}/>`
    + `<rect x="46" y="84" width="28" height="5" fill="#6B4A2E"/><rect x="57" y="83.5" width="6" height="6" rx="1" fill="#F6C343" stroke="${INK}" stroke-width="1.5"/>`;
const heroArmor = (color = STEEL) => `<path d="M46 70 q14 -6 28 0 v12 q-14 7 -28 0z" fill="${color}" ${LINE}/><path d="M52 72 q8 -3 16 0" stroke="#fff" stroke-width="2" fill="none" opacity=".8"/>`;
const heroCape = `<path d="M47 70 q-13 18 -11 37 q24 5 48 0 q2 -19 -11 -37z" fill="#C8423B" ${LINE}/>`;
const heroHead = `<circle cx="60" cy="46" r="20" fill="${H_SKIN}" ${LINE}/>`;
const heroHair = `<path d="M40 44 q2 -22 20 -22 q18 0 20 22 l-6 -6 l-4 5 l-5 -7 l-5 6 l-5 -6 l-5 6 l-4 -5z" fill="${H_HAIR}" ${LINE}/>`;
const heroHelmet = (color = '#9AA8B6', plume = false) => (plume ? `<g class="v-flame"><path d="M60 21 q-5 -13 6 -18 q-2 8 7 10 q-4 8 -13 8z" fill="#E04E4E" ${LINE}/></g>` : '')
    + `<path d="M38 44 q0 -24 22 -24 q22 0 22 24 h-6 q-2 -6 -16 -6 q-14 0 -16 6z" fill="${color}" ${LINE}/><path d="M60 21 v16" stroke="${INK}" stroke-width="1.6" opacity=".5"/>`;
const heroFace = (mood) => face(60, 49, { gap: 8, r: 3.2, mood });

const leftArmDown = limb('M47 74 Q41 82 42 90', H_TUNIC, 7) + hand(42, 91, H_SKIN);
const rightArmDown = limb('M73 74 Q79 82 78 90', H_TUNIC, 7) + hand(78, 91, H_SKIN);

/** Espada com o cabo na origem (onde fica a mão). */
const sword = (x, y, angle, blade, guard = '#8A5A33', glow = '') => `<g transform="translate(${x} ${y}) rotate(${angle})">`
    + (glow ? `<ellipse class="v-twinkle" cx="0" cy="-24" rx="10" ry="26" fill="${glow}" opacity=".45"/>` : '')
    + `<path d="M-3.5 -4 V-36 L0 -42 L3.5 -36 V-4z" fill="${blade}" ${LINE}/><path d="M0 -8 V-33" stroke="#fff" stroke-width="1.4" opacity=".7"/>`
    + `<rect x="-10" y="-6" width="20" height="5" rx="2.5" fill="${guard}" ${LINE}/><rect x="-2.5" y="-1" width="5" height="10" rx="2" fill="#6B4A2E" ${LINE}/></g>`;
const swordArm = (blade, guard, glow) => limb('M73 74 Q85 70 86 57', H_TUNIC, 7) + sword(86, 56, 12, blade, guard, glow) + hand(86, 57, H_SKIN);
const shieldArm = limb('M47 74 Q39 80 38 86', H_TUNIC, 7)
    + `<circle cx="35" cy="86" r="12" fill="#8A5A33" ${LINE}/><circle cx="35" cy="86" r="7.5" fill="${STEEL}" stroke="${INK}" stroke-width="1.8"/><path d="M35 81 v10 M30 86 h10" stroke="${INK}" stroke-width="1.8"/>`;
const scrollArms = limb('M47 74 Q42 84 50 87', H_TUNIC, 7) + limb('M73 74 Q78 84 70 87', H_TUNIC, 7)
    + `<rect x="47" y="79" width="26" height="15" rx="2" fill="#F7E8C8" ${LINE}/><path d="M52 84 h16 M52 88 h11" stroke="#A8865A" stroke-width="1.6" stroke-linecap="round"/>`
    + `<rect x="44" y="77" width="5" height="19" rx="2.5" fill="#E4CFA4" ${LINE}/><rect x="71" y="77" width="5" height="19" rx="2.5" fill="#E4CFA4" ${LINE}/>`
    + hand(48, 88, H_SKIN, 4) + hand(72, 88, H_SKIN, 4);

const slime = (x, king = false) => `<g class="v-bob"><path d="M${x - 15} 108 q-1 -19 15 -21 q16 2 15 21z" fill="#86D46B" ${LINE}/>`
    + `<path d="M${x - 8} 94 q3 -3 6 -2" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" opacity=".8"/>`
    + `<ellipse cx="${x - 5}" cy="100" rx="2" ry="2.6" fill="${INK}"/><ellipse cx="${x + 5}" cy="100" rx="2" ry="2.6" fill="${INK}"/>`
    + `<path d="M${x - 9} 95 l5 2 M${x + 9} 95 l-5 2" stroke="${INK}" stroke-width="1.8" stroke-linecap="round"/>`
    + (king ? `<path d="M${x - 8} 88 l2 -8 l4 4 l2 -6 l2 6 l4 -4 l2 8z" fill="${GOLD}" ${LINE}/>` : '') + '</g>';

const chest = `<rect x="88" y="94" width="24" height="14" rx="2" fill="#A0632E" ${LINE}/><path d="M88 94 l2 -10 h20 l2 10z" fill="#B87437" ${LINE}/>`
    + `<circle cx="95" cy="92" r="3.5" fill="#FFD24A" stroke="${INK}" stroke-width="1.4"/><circle cx="102" cy="91" r="3.5" fill="#FFD24A" stroke="${INK}" stroke-width="1.4"/><circle cx="106" cy="93" r="3" fill="#FFD24A" stroke="${INK}" stroke-width="1.4"/>`
    + `<rect x="98" y="97" width="4" height="5" rx="1" fill="${GOLD}" stroke="${INK}" stroke-width="1.4"/>`;

const HEROI = [
    // 0: aprendiz cochilando em pé, espada de madeira encostada
    `${shadow(30)}${sword(94, 106, 18, '#C99A5B')}<g transform="rotate(-6 60 106)"><g class="v-bob">${heroLegs}${heroBody}${leftArmDown}${rightArmDown}${heroHead}${heroHair}${heroFace('dormindo')}</g></g>${zzz(80, 24)}`,
    // 1: acorda e estuda o pergaminho de treino
    `${shadow(30)}<g class="v-bob">${heroLegs}${heroBody}${heroHead}${heroHair}${heroFace('feliz')}${scrollArms}</g>`,
    // 2: começa a treinar com a espada de madeira
    `${shadow(32)}<g class="v-bob">${heroLegs}${heroBody}${leftArmDown}${swordArm('#C99A5B')}${heroHead}${heroHair}${heroFace('focado')}</g>${sweat(36, 36)}`,
    // 3: peitoral, escudo e espada de ferro
    `${shadow(34)}<g class="v-bob">${heroLegs}${heroBody}${heroArmor()}${shieldArm}${swordArm('#E3E9EF')}${heroHead}${heroHair}${heroFace('esforco')}</g>${sweat(34, 34)}${sweat(84, 30, 0.7)}`,
    // 4: elmo e o primeiro slime
    `${shadow(40)}${slime(16)}<g class="v-bob">${heroLegs}${heroBody}${heroArmor()}${shieldArm}${swordArm('#E3E9EF')}${heroHead}${heroHelmet()}${heroFace('esforco')}</g>`
        + `${sweat(32, 30)}${sweat(86, 28, 0.5)}${sweat(30, 50, 1)}${actionLines('M100 26 l9 -4 M102 38 l10 0 M100 50 l9 4')}`,
    // 5: capa, penacho e espada encantada contra o rei slime
    `${shadow(42)}${slime(16, true)}<g class="v-bob">${heroCape}${heroLegs}${heroBody}${heroArmor()}${shieldArm}${swordArm('#CFEAFF', '#5B7BD6', '#7FC8FF')}${heroHead}${heroHelmet('#9AA8B6', true)}${heroFace('grito')}</g>`
        + `${sweat(32, 30)}${sweat(84, 30, 0.4)}${sweat(30, 50, 0.9)}${actionLines('M100 24 l10 -5 M103 36 l11 0 M101 48 l10 5')}${sparkle(108, 12, 0.8, '#7FC8FF')}`,
    // 6: herói lendário com armadura dourada e o tesouro
    `${shadow(46)}<g class="v-bob">${heroCape}${heroLegs}${heroBody}${heroArmor(GOLD)}${leftArmDown}${limb('M73 74 Q90 64 88 43', H_TUNIC, 7)}${sword(88, 42, 6, '#FFF3B0', GOLD, '#FFE27A')}${hand(88, 43, H_SKIN)}`
        + `${heroHead}${heroHelmet(GOLD, true)}${heroFace('vitoria')}</g>${chest}${sparkle(14, 30, 1)}${sparkle(24, 62, 0.7, '#FFD166', 0.5)}${sparkle(110, 70, 0.8, '#FFD166', 0.9)}`
        + `<text x="8" y="16" font-size="12" fill="${GOLD}" stroke="${INK}" stroke-width=".8" ${FONT}>LV MAX</text>`,
];

// ── ESTUDANTE CAÓTICO (comédia): do cochilo na mesa ao gênio de óculos escuros ──

const C_SKIN = '#E3A982';
const C_HAIR = '#2F2A3A';
const C_SHIRT = '#F6C343';

const desk = `<rect x="10" y="86" width="100" height="7" rx="3" fill="#C68B59" ${LINE}/><rect x="16" y="93" width="88" height="15" fill="#9C6B43" ${LINE}/>`;
const cTorso = `<path d="M36 88 q0 -22 24 -22 q24 0 24 22z" fill="${C_SHIRT}" ${LINE}/><path d="M54 67 l6 7 l6 -7" fill="none" stroke="${INK}" stroke-width="2" stroke-linejoin="round"/>`;
const cHead = `<circle cx="39" cy="49" r="4.5" fill="${C_SKIN}" ${LINE}/><circle cx="81" cy="49" r="4.5" fill="${C_SKIN}" ${LINE}/><circle cx="60" cy="46" r="21" fill="${C_SKIN}" ${LINE}/>`;
const cHair = `<path d="M39 44 q-1 -22 21 -23 q22 1 21 23 q-5 -7 -11 -6 q-3 -6 -10 -3 q-6 -4 -11 1 q-6 0 -10 8z" fill="${C_HAIR}" ${LINE}/>`;
const cHairMessy = `<path d="M39 46 l-7 -9 l9 0 l-4 -12 l10 6 l1 -13 l8 9 l5 -12 l5 12 l9 -9 l0 13 l10 -5 l-5 11 l8 3 l-7 6 q-5 -8 -11 -6 q-3 -6 -10 -3 q-6 -4 -11 1 q-6 0 -10 7z" fill="${C_HAIR}" ${LINE}/>`;
const cFace = (mood) => face(60, 44, { gap: 8, r: 3.3, mood });
const eyeBags = `<path d="M48 50 q4 2.5 8 0 M64 50 q4 2.5 8 0" fill="none" stroke="#9B6B8E" stroke-width="1.6" stroke-linecap="round"/>`;
const cHands = hand(44, 87, C_SKIN, 5) + hand(76, 87, C_SKIN, 5);
const cPencil = `${limb('M79 85 L89 71', '#F6C343', 3)}<path d="M89 71 l3 -4" stroke="${INK}" stroke-width="3" stroke-linecap="round"/>${hand(78, 86, C_SKIN, 5)}`;
const openBook = `<path d="M42 87 L60 83 L78 87 L78 79 L60 75 L42 79Z" fill="#fff" ${LINE}/><path d="M60 75 V83 M46 81 l10 -2 M64 79 l10 2" stroke="#9AA8B6" stroke-width="1.4" stroke-linecap="round"/>`;
const closedBook = `<rect x="44" y="80" width="30" height="6" rx="1.5" fill="#4F86F7" ${LINE}/>`;
const mug = (x, steam = true) => `<path d="M${x + 10} 75 q6 0 6 4.5 q0 4.5 -6 4.5" fill="none" ${LINE}/><rect x="${x}" y="72" width="11" height="14" rx="2.5" fill="#fff" ${LINE}/><rect x="${x + 1.5}" y="76" width="8" height="3" fill="#C05C46"/>`
    + (steam ? `<path class="v-steam" d="M${x + 5} 69 q-3 -4 0 -8 q3 -4 0 -8" fill="none" stroke="#B8C5D0" stroke-width="2" stroke-linecap="round"/>` : '');
const can = `<rect x="98" y="70" width="9" height="16" rx="2" fill="#2EC4B6" ${LINE}/><path d="M103.5 73 l-3 5 h3 l-2 5" fill="none" stroke="#F6C343" stroke-width="1.8" stroke-linejoin="round"/>`;
const bookStack = `<rect x="14" y="78" width="26" height="8" rx="1.5" fill="#7B1E30" ${LINE}/><rect x="16" y="70" width="23" height="8" rx="1.5" fill="#22543D" ${LINE}/><rect x="13" y="62" width="25" height="8" rx="1.5" fill="#2A4365" ${LINE}/>`;
const headSteam = `<g class="v-steam"><circle cx="30" cy="24" r="5" fill="#E4E8EE" stroke="${INK}" stroke-width="1.4"/><circle cx="24" cy="16" r="3.5" fill="#E4E8EE" stroke="${INK}" stroke-width="1.4"/></g>`
    + `<g class="v-steam" style="animation-delay:.8s"><circle cx="90" cy="22" r="5" fill="#E4E8EE" stroke="${INK}" stroke-width="1.4"/><circle cx="96" cy="14" r="3.5" fill="#E4E8EE" stroke="${INK}" stroke-width="1.4"/></g>`;
const headFire = `<g class="v-flame"><path d="M42 24 q-5 -14 6 -22 q0 10 6 10 q-2 -11 7 -17 q2 12 8 12 q2 -8 9 -8 q-3 10 1 16 q-3 9 -18 9 q-15 0 -19 0z" fill="#FF8A3D" ${LINE}/>`
    + '<path d="M51 22 q0 -8 6 -12 q0 8 6 8 q2 -6 6 -6 q0 8 -2 10z" fill="#FFD166"/></g>';
const papers = (many) => `<g class="v-float">${[[8, 40, -20], [100, 44, 25], ...(many ? [[18, 12, 15], [98, 6, -30], [4, 66, 35]] : [])]
    .map(([x, y, a]) => `<rect x="${x}" y="${y}" width="13" height="16" rx="1" fill="#fff" stroke="${INK}" stroke-width="1.6" transform="rotate(${a} ${x + 6} ${y + 8})"/>`
        + `<path d="M${x + 3} ${y + 5} h7 M${x + 3} ${y + 9} h7" stroke="#9AA8B6" stroke-width="1.3" transform="rotate(${a} ${x + 6} ${y + 8})"/>`).join('')}</g>`;
const sunglasses = `<path d="M46 41 h12 v3 q0 6 -6 6 q-6 0 -6 -6z M62 41 h12 v3 q0 6 -6 6 q-6 0 -6 -6z" fill="${INK}"/><path d="M58 42 h4 M46 42 l-6 -2 M74 42 l6 -2" stroke="${INK}" stroke-width="2"/>`
    + '<path d="M48 43 l3 0" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/>';
const bulb = `<g class="v-twinkle"><circle cx="96" cy="18" r="15" fill="#FFE27A" opacity=".35"/></g><path d="M96 6 a9 9 0 0 1 5 16 v3 h-10 v-3 a9 9 0 0 1 5 -16z" fill="#FFE27A" ${LINE}/><rect x="91" y="25" width="10" height="5" rx="1.5" fill="#9AA8B6" ${LINE}/>`;
const gradePaper = `<g transform="rotate(8 92 78)"><rect x="82" y="66" width="20" height="22" rx="1.5" fill="#fff" ${LINE}/><text x="92" y="82" text-anchor="middle" font-size="11" fill="#D62839" ${FONT}>A+</text></g>`;

const CAOTICO = [
    // 0: dormiu na mesa com bolha no nariz
    `${shadow(46)}${cTorso}<g transform="rotate(14 60 66)">${cHead}${cHair}${cFace('dormindo')}</g><g transform="translate(60 54)"><circle class="v-bubble" cx="0" cy="0" r="5" fill="#BFE6FF" opacity=".85" stroke="${INK}" stroke-width="1.2"/></g>${desk}${closedBook}${cHands}${zzz(84, 22)}`,
    // 1: bocejando, sem entender nada
    `${shadow(46)}${cTorso}${cHead}${cHair}${cFace('bocejo')}${desk}${openBook}${cHands}${mug(88)}<text x="84" y="22" font-size="18" fill="${INK}" ${FONT}>?</text>`,
    // 2: concentrado, segundo café
    `${shadow(46)}${cTorso}${cHead}${cHair}${cFace('focado')}${desk}${openBook}${hand(44, 87, C_SKIN, 5)}${cPencil}${mug(84)}${mug(97, false)}`,
    // 3: cabelo bagunçado, olheiras, terceiro café
    `${shadow(46)}${cTorso}${cHead}${cHairMessy}${cFace('nervoso')}${eyeBags}${desk}${openBook}${hand(44, 87, C_SKIN, 5)}${cPencil}${mug(14, false)}${mug(84)}${mug(97, false)}${sweat(84, 34)}`,
    // 4: a cabeça soltando vapor, pilha de livros
    `${shadow(46)}${headSteam}${papers(false)}${cTorso}${cHead}${cHairMessy}${cFace('esforco')}${eyeBags}${desk}${bookStack}${openBook}${hand(44, 87, C_SKIN, 5)}${cPencil}${mug(84)}${mug(97, false)}`
        + `${sweat(84, 34)}${sweat(34, 40, 0.6)}`,
    // 5: cérebro pegando fogo, papéis voando, energético
    `${shadow(46)}${papers(true)}${cTorso}${cHead}${cHairMessy}${headFire}${cFace('grito')}${eyeBags}${desk}${bookStack}${openBook}${hand(44, 87, C_SKIN, 5)}${cPencil}${mug(84)}${can}`
        + `${sweat(86, 34)}${sweat(32, 40, 0.5)}${sweat(88, 52, 1)}`,
    // 6: virou gênio: óculos escuros, lâmpada acesa e nota A+
    `${shadow(46)}${confetti()}${bulb}${cTorso}${cHead}${cHair}${cFace('vitoria')}${sunglasses}${desk}${bookStack}${openBook}${cHands}${gradePaper}${sparkle(12, 30, 0.9)}`,
];

// ── FAMÍLIA: a criança estuda e a família vai chegando para torcer ───────────

const F_KID_SKIN = '#F1C7A3';
const F_KID_HAIR = '#5B3A29';
const fDesk = `<rect x="32" y="88" width="56" height="6" rx="3" fill="#C68B59" ${LINE}/><rect x="37" y="94" width="46" height="14" fill="#9C6B43" ${LINE}/>`;
const kidTorso = `<path d="M44 90 q0 -16 16 -16 q16 0 16 16z" fill="#6CC3A0" ${LINE}/>`;
const kidHead = `<circle cx="60" cy="60" r="15" fill="${F_KID_SKIN}" ${LINE}/><path d="M45 58 q0 -16 15 -16 q15 0 15 16 q-4 -6 -8 -5 q-3 -4 -7 -2 q-4 -3 -8 1 q-4 -1 -7 6z" fill="${F_KID_HAIR}" ${LINE}/>`;
const kidFace = (mood) => face(60, 62, { gap: 6, r: 2.6, mood });
const kidHands = hand(49, 88, F_KID_SKIN, 4) + hand(71, 88, F_KID_SKIN, 4);
const kidBook = `<path d="M46 89 L60 86 L74 89 L74 82 L60 79 L46 82Z" fill="#fff" ${LINE}/><path d="M60 79 V86" stroke="#9AA8B6" stroke-width="1.4"/>`;
const kidPencil = `${limb('M72 86 L80 75', '#F6C343', 2.5)}${hand(71, 87, F_KID_SKIN, 4)}`;
const moon = `<path d="M18 8 a11 11 0 1 0 12 16 a9 9 0 1 1 -12 -16z" fill="#FFE27A" ${LINE}/>`;

/** Adulto de pé: corpo centrado em x; braços como caminhos a partir dos ombros. */
function adult(x, { skin, shirt, hair, longHair = false, mood = 'feliz', armL, armR, extra = '' }) {
    const legs = limb(`M${x - 5} 96 V104`, '#4A5568', 6) + limb(`M${x + 5} 96 V104`, '#4A5568', 6);
    const back = longHair ? `<path d="M${x - 13} 52 q0 -15 13 -15 q13 0 13 15 v17 q-4 2 -8 0 v-14 h-10 v14 q-4 2 -8 0z" fill="${hair}" ${LINE}/>` : '';
    const top = `<path d="M${x - 12} 51 q0 -13 12 -13 q12 0 12 13 q-6 -6 -12 -4 q-6 -2 -12 4z" fill="${hair}" ${LINE}/>`;
    return `${back}${legs}<rect x="${x - 11}" y="66" width="22" height="32" rx="9" fill="${shirt}" ${LINE}/>`
        + `${limb(armL, shirt, 5)}${limb(armR, shirt, 5)}<circle cx="${x}" cy="54" r="12" fill="${skin}" ${LINE}/>${top}`
        + face(x, 56, { gap: 5, r: 2.2, mood }) + extra;
}
const ends = (d) => d.match(/(-?[\d.]+) (-?[\d.]+)$/).slice(1).map(Number);
const withHands = (html, arms, skin) => html + arms.map((d) => hand(...ends(d), skin, 3.6)).join('');

const MOM = { skin: '#E8B48A', shirt: '#E85D75', hair: '#3A2318', longHair: true };
const momJuice = (mood) => withHands(adult(18, { ...MOM, mood, armL: 'M10 72 Q6 82 8 90', armR: 'M26 72 Q32 78 34 82' }), ['M10 72 Q6 82 8 90', 'M26 72 Q32 78 34 82'], MOM.skin)
    + `<rect x="31" y="70" width="8" height="11" rx="1.5" fill="#FFB347" opacity=".9" ${LINE}/>`;
const momCheer = (mood) => withHands(adult(18, { ...MOM, mood, armL: 'M10 72 Q4 62 6 52', armR: 'M26 72 Q32 62 30 52' }), ['M10 72 Q4 62 6 52', 'M26 72 Q32 62 30 52'], MOM.skin);

const DAD = { skin: '#C98E64', shirt: '#4F86F7', hair: '#2B2327' };
const dadSign = (text, mood = 'feliz') => withHands(adult(102, { ...DAD, mood, armL: 'M93 72 Q88 52 92 36', armR: 'M111 72 Q116 52 112 36' }), ['M93 72 Q88 52 92 36', 'M111 72 Q116 52 112 36'], DAD.skin)
    + `<rect x="84" y="18" width="34" height="18" rx="2" fill="#fff" ${LINE}/><text x="101" y="32" text-anchor="middle" font-size="11" fill="#D62839" ${FONT}>${text}</text>`;
const dadCheer = withHands(adult(102, { ...DAD, mood: 'vitoria', armL: 'M93 72 Q86 62 88 50', armR: 'M111 72 Q118 62 116 50' }), ['M93 72 Q86 62 88 50', 'M111 72 Q118 62 116 50'], DAD.skin);

const dog = `<g class="v-hop"><g class="v-wag"><path d="M37 100 q8 -4 9 -12" fill="none" stroke="${INK}" stroke-width="6" stroke-linecap="round"/><path d="M37 100 q8 -4 9 -12" fill="none" stroke="#C9925A" stroke-width="3" stroke-linecap="round"/></g>`
    + `<ellipse cx="28" cy="99" rx="11" ry="9" fill="#C9925A" ${LINE}/><ellipse cx="24" cy="106" rx="4" ry="2.5" fill="#C9925A" ${LINE}/><ellipse cx="33" cy="106" rx="4" ry="2.5" fill="#C9925A" ${LINE}/>`
    + `<circle cx="22" cy="86" r="8.5" fill="#C9925A" ${LINE}/><path d="M15 82 q-5 6 -1 12 q4 -2 4 -8z" fill="#7A5230" ${LINE}/><path d="M28 81 q5 5 2 11 q-4 -2 -4 -8z" fill="#7A5230" ${LINE}/>`
    + `<circle cx="20" cy="85" r="1.5" fill="${INK}"/><circle cx="25" cy="85" r="1.5" fill="${INK}"/><ellipse cx="22.5" cy="89" rx="2" ry="1.5" fill="${INK}"/><path d="M22 92 q1 3 3 0" fill="#F49AA7" stroke="${INK}" stroke-width="1"/></g>`;

const sibling = `<g class="v-hop" style="animation-delay:.3s">${limb('M89 104 V108', '#4A5568', 4)}${limb('M95 104 V108', '#4A5568', 4)}<rect x="85" y="88" width="14" height="17" rx="6" fill="#B39DDB" ${LINE}/>`
    + `${limb('M86 92 Q80 86 81 79', '#B39DDB', 4)}${limb('M98 92 Q104 86 103 79', '#B39DDB', 4)}`
    + `<circle cx="80" cy="77" r="5.5" fill="#F6C343" stroke="${INK}" stroke-width="1.6"/><circle cx="104" cy="77" r="5.5" fill="#E85D75" stroke="${INK}" stroke-width="1.6"/>`
    + `<circle cx="92" cy="80" r="9" fill="${F_KID_SKIN}" ${LINE}/><path d="M83 79 q0 -10 9 -10 q9 0 9 10 q-4 -4 -9 -3 q-5 -1 -9 3z" fill="#E09F3E" ${LINE}/>`
    + `<circle cx="99" cy="71" r="3" fill="#E85D75" stroke="${INK}" stroke-width="1.3"/>${face(92, 81, { gap: 3.5, r: 1.8, mood: 'vitoria', blush: false })}</g>`;

const banner = `<path d="M6 6 q54 12 108 0 v16 q-54 12 -108 0z" fill="#E85D75" ${LINE}/><text x="60" y="23" text-anchor="middle" font-size="12" fill="#fff" ${FONT}>ORGULHO!</text>`;
const medal = `<path d="M55 74 l5 8 l5 -8" fill="none" stroke="#D62839" stroke-width="2.5"/><circle cx="60" cy="84" r="4.5" fill="${GOLD}" stroke="${INK}" stroke-width="1.6"/>`;
const kidCheer = withHands(limb('M48 82 Q40 74 42 62', '#6CC3A0', 5) + limb('M72 82 Q80 74 78 62', '#6CC3A0', 5), ['M48 82 Q40 74 42 62', 'M72 82 Q80 74 78 62'], F_KID_SKIN);

const FAMILIA = [
    // 0: noite, a criança cochilou em cima do caderno
    `${shadow(30)}${moon}${sparkle(96, 16, 0.7, '#FFE27A')}${sparkle(80, 30, 0.5, '#FFE27A', 0.6)}${kidTorso}<g transform="rotate(16 60 74)">${kidHead}${kidFace('dormindo')}</g>${fDesk}${kidHands}${zzz(78, 40)}`,
    // 1: acordou e abriu o livro
    `${shadow(30)}${kidTorso}${kidHead}${kidFace('feliz')}${fDesk}${kidBook}${kidHands}`,
    // 2: a mãe traz um suco
    `${shadow(50)}${momJuice('feliz')}${kidTorso}${kidHead}${kidFace('focado')}${fDesk}${kidBook}${kidHands}`,
    // 3: o pai chega com a plaquinha
    `${shadow(54)}${momJuice('feliz')}${dadSign('VAI!')}${kidTorso}${kidHead}${kidFace('focado')}${fDesk}${kidBook}${kidHands}${sweat(76, 48)}`,
    // 4: o cachorro vem fazer companhia
    `${shadow(54)}${momJuice('feliz')}${dadSign('VAI!')}${kidTorso}${kidHead}${kidFace('esforco')}${fDesk}${kidBook}${hand(49, 88, F_KID_SKIN, 4)}${kidPencil}${dog}${sweat(76, 48)}${sweat(42, 50, 0.6)}`,
    // 5: todo mundo torcendo, a irmã com pompons
    `${shadow(54)}<g class="v-hop">${momCheer('vitoria')}</g>${dadSign('VAI!!', 'vitoria')}${kidTorso}${kidHead}${kidFace('esforco')}${fDesk}${kidBook}${hand(49, 88, F_KID_SKIN, 4)}${kidPencil}${dog}${sibling}`
        + `${sweat(76, 48)}${sweat(42, 50, 0.5)}${sweat(78, 62, 1)}${actionLines('M40 40 l-5 -5 M80 40 l5 -5 M60 36 v-6')}`,
    // 6: festa! faixa, confete e medalha
    `${shadow(54)}${confetti()}${banner}<g class="v-hop">${momCheer('vitoria')}</g><g class="v-hop" style="animation-delay:.2s">${dadCheer}</g>${kidTorso}${medal}${kidCheer}${kidHead}${kidFace('vitoria')}${fDesk}${kidBook}${dog}${sibling}`,
];

// ── HAMSTER (animais): do ninho à rodinha soltando faísca até o pódio ────────

const FUR = '#E9A15B';
const BELLY = '#FCE6C8';
const PINK = '#F49AA7';

/** Hamster centrado em (x, y); `held` é desenhado entre as patinhas (coordenadas locais). */
function hamster(x, y, { mood = 'feliz', tilt = 0, run = false, band = false, glasses = false, held = '', medal: withMedal = false } = {}) {
    const feet = `<ellipse cx="-9" cy="16" rx="5.5" ry="3.2" fill="${PINK}" ${LINE}/><ellipse cx="9" cy="16" rx="5.5" ry="3.2" fill="${PINK}" ${LINE}/>`;
    return `<g transform="translate(${x} ${y}) rotate(${tilt})">`
        + `<circle cx="-12" cy="-14" r="6.5" fill="${FUR}" ${LINE}/><circle cx="-12" cy="-14" r="3.2" fill="${PINK}"/><circle cx="12" cy="-14" r="6.5" fill="${FUR}" ${LINE}/><circle cx="12" cy="-14" r="3.2" fill="${PINK}"/>`
        + (run ? `<g class="v-run">${feet}</g>` : feet)
        + `<ellipse cx="0" cy="0" rx="21" ry="18" fill="${FUR}" ${LINE}/><ellipse cx="0" cy="6" rx="13" ry="10" fill="${BELLY}"/>`
        + `<path d="M-4 -17 q4 -3 8 0" fill="none" stroke="#C97F3D" stroke-width="2" stroke-linecap="round"/>`
        + face(0, -5, { gap: 8, r: 2.8, mood })
        + `<ellipse cx="0" cy="-1.5" rx="2" ry="1.4" fill="${PINK}" stroke="${INK}" stroke-width="1"/>`
        + (glasses ? `<circle cx="-8" cy="-5" r="5" fill="none" stroke="${INK}" stroke-width="1.6"/><circle cx="8" cy="-5" r="5" fill="none" stroke="${INK}" stroke-width="1.6"/><path d="M-3 -5 h6" stroke="${INK}" stroke-width="1.6"/>` : '')
        + (band ? `<path d="M-19 -11 q19 -8 38 0 v5 q-19 -8 -38 0z" fill="#E04E4E" ${LINE}/><path d="M19 -9 l7 -4 M19 -7 l7 2" stroke="#E04E4E" stroke-width="3" stroke-linecap="round"/>` : '')
        + (withMedal ? `<path d="M-5 0 l5 7 l5 -7" fill="none" stroke="#D62839" stroke-width="2.5"/><circle cx="0" cy="9" r="4.5" fill="${GOLD}" stroke="${INK}" stroke-width="1.6"/>` : '')
        + held
        + `<ellipse cx="-7" cy="10" rx="3.5" ry="3" fill="${FUR}" stroke="${INK}" stroke-width="1.6"/><ellipse cx="7" cy="10" rx="3.5" ry="3" fill="${FUR}" stroke="${INK}" stroke-width="1.6"/>`
        + '</g>';
}

const seed = `<ellipse cx="0" cy="11" rx="3.5" ry="6" fill="#5B4636" stroke="${INK}" stroke-width="1.4"/><path d="M0 6 v10" stroke="#E8DCC4" stroke-width="1.2"/>`;
const tinyBook = `<path d="M-11 16 l11 -3 l11 3 v-10 l-11 -3 l-11 3z" fill="#4F86F7" ${LINE}/><path d="M0 3 v10" stroke="#fff" stroke-width="1.4"/>`;
const nestBack = `<path d="M26 98 q34 -14 68 0" fill="#D9B27C" ${LINE}/>`;
const nestFront = `<path d="M24 94 q36 26 72 0 q-36 10 -72 0z" fill="#C9975C" ${LINE}/><path d="M34 98 l6 3 M50 102 l6 1 M66 102 l6 -1 M82 98 l-6 3" stroke="#8A5A33" stroke-width="1.6" stroke-linecap="round"/>`;

const wheel = (fast = false, sparks = false) => limb('M60 62 L38 108', '#8A97A6', 4) + limb('M60 62 L82 108', '#8A97A6', 4)
    + `<circle cx="60" cy="62" r="42" fill="#EAF2F8" fill-opacity=".55" stroke="${INK}" stroke-width="6"/><circle cx="60" cy="62" r="42" fill="none" stroke="#B8C5D0" stroke-width="3"/>`
    + `<g class="v-spin${fast ? ' v-fast' : ''}"><circle cx="60" cy="62" r="42" fill="none" stroke="#7D8A99" stroke-width="2" stroke-dasharray="3 7"/>`
    + '<path d="M60 22 V102 M20 62 H100 M31.7 33.7 L88.3 90.3 M88.3 33.7 L31.7 90.3" stroke="#B8C5D0" stroke-width="2"/></g>'
    + `<circle cx="60" cy="62" r="4" fill="#8A97A6" stroke="${INK}" stroke-width="1.8"/>`
    + (sparks ? `${sparkle(28, 100, 0.7, '#FFB703')}${sparkle(92, 100, 0.7, '#FFB703', 0.4)}${sparkle(20, 84, 0.5, '#FFB703', 0.8)}` : '');

const podium = `<rect x="36" y="88" width="48" height="20" rx="2" fill="${GOLD}" ${LINE}/><text x="60" y="103" text-anchor="middle" font-size="13" fill="${INK}" ${FONT}>1</text>`;
const trophy = `<path d="M96 70 h16 v6 q0 9 -8 10 q-8 -1 -8 -10z" fill="${GOLD}" ${LINE}/><path d="M96 73 q-5 0 -4 5 q1 4 5 3 M112 73 q5 0 4 5 q-1 4 -5 3" fill="none" stroke="${INK}" stroke-width="2"/>`
    + `<rect x="102" y="86" width="4" height="8" fill="${GOLD}" ${LINE}/><rect x="97" y="94" width="14" height="5" rx="1" fill="#8A5A33" ${LINE}/>`;
const seedPile = `<g transform="translate(18 104)">${[[-6, 0], [0, -2], [6, 0], [-3, -7], [3, -7]].map(([x, y]) => `<ellipse cx="${x}" cy="${y}" rx="2.8" ry="4.5" fill="#5B4636" stroke="${INK}" stroke-width="1.2"/>`).join('')}</g>`;

const HAMSTER = [
    // 0: dormindo enroladinho no ninho
    `${shadow(36)}${nestBack}<g class="v-bob">${hamster(60, 88, { mood: 'dormindo' })}</g>${nestFront}${zzz(80, 58)}`,
    // 1: acordou e pegou uma semente
    `${shadow(26)}<g class="v-bob">${hamster(60, 90, { mood: 'feliz', held: seed })}</g>`,
    // 2: óculos e um livrinho
    `${shadow(26)}<g class="v-bob">${hamster(60, 90, { mood: 'focado', glasses: true, held: tinyBook })}</g>`,
    // 3: começa a correr na rodinha
    `${shadow(36)}${wheel()}${hamster(60, 84, { mood: 'focado', tilt: -8, run: true })}${sweat(80, 56)}`,
    // 4: correndo rápido, suando
    `${shadow(36)}${wheel(true)}${hamster(60, 84, { mood: 'esforco', tilt: -14, run: true })}${sweat(80, 56)}${sweat(84, 70, 0.6)}${actionLines('M30 76 h-10 M32 86 h-12 M32 96 h-9')}`,
    // 5: faixa na testa e a rodinha soltando faísca
    `${shadow(36)}${wheel(true, true)}${hamster(60, 84, { mood: 'grito', tilt: -18, run: true, band: true })}${sweat(80, 54)}${sweat(86, 68, 0.4)}${sweat(38, 60, 0.8)}${actionLines('M30 74 h-12 M30 84 h-14 M32 94 h-11')}`,
    // 6: campeão no pódio
    `${shadow(44)}${seedPile}${podium}<g class="v-bob">${hamster(60, 68, { mood: 'vitoria', band: true, medal: true })}</g>${trophy}${confetti()}${sparkle(18, 40, 0.9)}${sparkle(104, 46, 0.8, '#FFD166', 0.5)}`,
];

export const AVATARS_VETOR = {
    heroi: { nome: 'Herói (RPG)', estagios: HEROI },
    caotico: { nome: 'Estudante Caótico', estagios: CAOTICO },
    familia: { nome: 'Família', estagios: FAMILIA },
    hamster: { nome: 'Hamster', estagios: HAMSTER },
};
