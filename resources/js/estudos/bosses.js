// Bosses da Jornada (ilustrações vetoriais 200x200, mesma ordem de BOSS_HP em jornada.js).
//
// Cada boss tem nome, título, fala, cor de destaque, `core` (onde aparecem os cortes de dano) e `head`
// (onde giram as estrelinhas de nocaute). Degradês usam ids "__ID__x", trocados por um prefixo único a cada
// desenho para não colidir com outro boss na tela. Classes b-* são animadas em estudos.css; nunca as coloque
// num elemento com atributo transform (a animação CSS substituiria a posição): envolva num <g>.

const INK = '#1E1A22';
const L = `stroke="${INK}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"`;
const L2 = `stroke="${INK}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"`;
const FONT = 'font-family="system-ui, sans-serif" font-weight="900"';

const radial = (id, stops, { cx = '40%', cy = '35%', r = '70%' } = {}) => `<radialGradient id="__ID__${id}" cx="${cx}" cy="${cy}" r="${r}">${stops
    .map(([o, c, a = 1]) => `<stop offset="${o}" stop-color="${c}" stop-opacity="${a}"/>`).join('')}</radialGradient>`;
const linear = (id, stops, [x1, y1, x2, y2] = [0, 0, 0, 1]) => `<linearGradient id="__ID__${id}" x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}">${stops
    .map(([o, c, a = 1]) => `<stop offset="${o}" stop-color="${c}" stop-opacity="${a}"/>`).join('')}</linearGradient>`;
const url = (id) => `url(#__ID__${id})`;
const ground = (rx = 70, cx = 100) => `<ellipse cx="${cx}" cy="188" rx="${rx}" ry="8" fill="#000" opacity=".22"/>`;

/** Olho com brilho: esclera, íris com degradê, pupila (fenda opcional) e reflexo. */
function eye(cx, cy, r, iris, { slit = false, look = [0, 0], id } = {}) {
    const [dx, dy] = look;
    const pupil = slit
        ? `<ellipse cx="${cx + dx}" cy="${cy + dy}" rx="${r * 0.18}" ry="${r * 0.62}" fill="${INK}"/>`
        : `<circle cx="${cx + dx}" cy="${cy + dy}" r="${r * 0.32}" fill="${INK}"/>`;
    return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="#FFFDF5" ${L2}/>`
        + `<circle cx="${cx + dx}" cy="${cy + dy}" r="${r * 0.62}" fill="${id ? url(id) : iris}"/>`
        + pupil
        + `<circle cx="${cx + dx - r * 0.25}" cy="${cy + dy - r * 0.3}" r="${r * 0.2}" fill="#fff"/>`;
}

// ── 1. Slime da Preguiça ─────────────────────────────────────────────────────

const SLIME = `<defs>
    ${radial('body', [[0, '#C9F79A'], [0.45, '#7BD34E'], [1, '#2F8A2B']])}
    ${radial('puddle', [[0, '#7BD34E', 0.7], [1, '#2F8A2B', 0]], { cx: '50%', cy: '50%' })}
    ${linear('cap', [[0, '#5B7CFA'], [1, '#3448B8']])}
</defs>
${ground(78)}
<ellipse cx="100" cy="182" rx="86" ry="12" fill="${url('puddle')}"/>
<g class="b-squish">
    <path d="M24 180 C14 156 30 120 56 100 C62 64 96 50 118 60 C146 68 160 90 158 104 C182 122 192 158 176 180
        C170 188 160 184 154 180 C146 188 134 186 128 180 C116 190 92 190 80 182 C70 190 52 188 46 182 C38 188 28 186 24 180Z"
        fill="${url('body')}" ${L}/>
    <path d="M40 160 C34 140 44 120 60 110" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" opacity=".35"/>
    <ellipse cx="72" cy="86" rx="14" ry="8" fill="#fff" opacity=".55" transform="rotate(-30 72 86)"/>
    <circle cx="90" cy="76" r="3.5" fill="#fff" opacity=".7"/>
    <g opacity=".35" fill="#E9FFD9"><circle cx="148" cy="150" r="7"/><circle cx="58" cy="164" r="5"/><circle cx="134" cy="120" r="4"/><circle cx="160" cy="168" r="3"/></g>
    <path d="M60 182 v8 q0 5 4 5 q4 0 4 -5 v-6" fill="#7BD34E" ${L2}/>
    <path d="M140 183 v5 q0 4 3 4 q3 0 3 -4 v-4" fill="#7BD34E" ${L2}/>
    <path d="M62 78 C70 40 120 30 134 66 C150 46 168 36 184 40 C172 52 160 68 140 84 C124 76 92 72 62 78Z" fill="${url('cap')}" ${L}/>
    <path d="M80 58 l14 16 M104 48 l12 22 M140 66 l14 -16 M160 50 l10 4" stroke="#AFC2FF" stroke-width="5" stroke-linecap="round" opacity=".8"/>
    <path d="M60 80 C96 70 120 74 142 86" fill="none" stroke="#F6F3EA" stroke-width="10" stroke-linecap="round"/>
    <path d="M60 80 C96 70 120 74 142 86" fill="none" stroke="${INK}" stroke-width="2" stroke-dasharray="1 9" stroke-linecap="round" opacity=".4"/>
    <g class="b-dangle"><circle cx="186" cy="42" r="9" fill="#F6F3EA" ${L2}/></g>
    <g class="b-blink">
        <ellipse cx="80" cy="120" rx="16" ry="13" fill="#FFFDF5" ${L2}/>
        <ellipse cx="128" cy="118" rx="16" ry="13" fill="#FFFDF5" ${L2}/>
        <circle cx="82" cy="126" r="6.5" fill="${INK}"/><circle cx="126" cy="124" r="6.5" fill="${INK}"/>
        <circle cx="80" cy="124" r="2" fill="#fff"/><circle cx="124" cy="122" r="2" fill="#fff"/>
    </g>
    <path d="M63 116 C68 106 92 106 97 117 Z" fill="#4FA83A" ${L2}/>
    <path d="M111 114 C116 104 140 104 145 115 Z" fill="#4FA83A" ${L2}/>
    <path d="M70 140 q6 4 14 2 M120 140 q8 3 14 -1" stroke="#2F8A2B" stroke-width="2.5" fill="none" stroke-linecap="round" opacity=".6"/>
    <g class="b-yawn"><ellipse cx="104" cy="152" rx="15" ry="11" fill="#5A1F2B" ${L2}/>
        <path d="M94 158 q10 -7 20 0 q-4 5 -10 5 q-6 0 -10 -5z" fill="#F07C8E"/>
        <path d="M92 146 h6 v4 h-6z M110 146 h6 v4 h-6z" fill="#fff"/></g>
    <g class="b-drip"><path d="M118 160 q4 10 0 16 q-4 -6 0 -16z" fill="#BDEBFF" ${L2}/></g>
</g>
<g class="b-zzz" fill="#E9FFD9" stroke="${INK}" stroke-width="1.5" paint-order="stroke" ${FONT}>
    <text x="150" y="104" font-size="20">Z</text><text x="166" y="86" font-size="14">z</text><text x="176" y="72" font-size="10">z</text>
</g>`;

// ── 2. Goblin da Distração ───────────────────────────────────────────────────

const GOBLIN = `<defs>
    ${radial('skin', [[0, '#C7E07A'], [0.6, '#8DB443'], [1, '#56772A']])}
    ${linear('hood', [[0, '#5B3C8C'], [1, '#2C1B4A']])}
    ${radial('glow', [[0, '#7FF3FF', 0.9], [1, '#7FF3FF', 0]], { cx: '50%', cy: '50%', r: '50%' })}
    ${radial('iris', [[0, '#FFF27A'], [1, '#E0A100']], { cx: '50%', cy: '50%' })}
    ${linear('screen', [[0, '#C8FBFF'], [1, '#2BC4E8']])}
</defs>
${ground(60)}
<g class="b-idle">
    <path d="M52 188 C50 150 62 124 100 120 C138 124 150 150 148 188Z" fill="${url('hood')}" ${L}/>
    <path d="M100 124 v62" stroke="#1A1030" stroke-width="2"/>
    <path d="M84 132 q-3 18 2 30 M116 132 q3 18 -2 30" stroke="#D9C8F2" stroke-width="2.5" fill="none" stroke-linecap="round"/>
    <circle cx="86" cy="164" r="2.5" fill="#D9C8F2"/><circle cx="114" cy="164" r="2.5" fill="#D9C8F2"/>
    <path d="M44 74 C30 66 14 60 4 62 C16 72 26 84 52 96Z" fill="${url('skin')}" ${L}/>
    <path d="M156 74 C170 66 186 60 196 62 C184 72 174 84 148 96Z" fill="${url('skin')}" ${L}/>
    <path d="M16 66 C26 72 36 80 48 88 M184 66 C174 72 164 80 152 88" stroke="#E79AA0" stroke-width="4" stroke-linecap="round" opacity=".8"/>
    <path d="M24 70 l-2 -6 M30 72 l-2 -7" stroke="${INK}" stroke-width="2" stroke-linecap="round"/>
    <path d="M60 96 C50 70 60 36 100 34 C140 36 150 70 140 96 C136 116 120 128 100 128 C80 128 64 116 60 96Z" fill="${url('skin')}" ${L}/>
    <path d="M70 46 C80 38 92 36 100 36" stroke="#E4F2B6" stroke-width="4" fill="none" stroke-linecap="round" opacity=".7"/>
    <path d="M66 68 l24 6 M134 68 l-24 6" stroke="${INK}" stroke-width="4" stroke-linecap="round"/>
    <g class="b-blink">${eye(80, 84, 11, '', { slit: true, look: [2, 4], id: 'iris' })}${eye(120, 84, 11, '', { slit: true, look: [-1, 4], id: 'iris' })}</g>
    <path d="M100 86 C96 96 92 102 96 106 C100 108 104 106 104 104" fill="#7FA338" ${L2}/>
    <path d="M78 110 C90 120 112 120 124 106" fill="#5A1F2B" ${L2}/>
    <path d="M84 112 l4 6 l3 -5 M114 112 l-3 6 l-4 -5" fill="#FFFDF5" ${L2}/>
    <circle cx="70" cy="100" r="4" fill="#E79AA0" opacity=".5"/><circle cx="132" cy="100" r="4" fill="#E79AA0" opacity=".5"/>
    <circle cx="100" cy="150" r="36" fill="${url('glow')}" class="b-glow"/>
    <path d="M62 150 C70 140 80 138 86 144" fill="none" stroke="${INK}" stroke-width="15" stroke-linecap="round"/>
    <path d="M62 150 C70 140 80 138 86 144" fill="none" stroke="#5B3C8C" stroke-width="10" stroke-linecap="round"/>
    <path d="M138 150 C130 140 120 138 114 144" fill="none" stroke="${INK}" stroke-width="15" stroke-linecap="round"/>
    <path d="M138 150 C130 140 120 138 114 144" fill="none" stroke="#5B3C8C" stroke-width="10" stroke-linecap="round"/>
    <rect x="84" y="132" width="32" height="50" rx="6" fill="#222" ${L}/>
    <rect x="88" y="137" width="24" height="38" rx="2" fill="${url('screen')}"/>
    <path d="M91 145 h12 M91 151 h18 M91 157 h9 M91 163 h15" stroke="#fff" stroke-width="2.5" stroke-linecap="round" opacity=".85"/>
    <circle cx="84" cy="146" r="7" fill="${url('skin')}" ${L2}/><circle cx="116" cy="146" r="7" fill="${url('skin')}" ${L2}/>
</g>
<g class="b-float" ${FONT} font-size="10" text-anchor="middle">
    <circle cx="30" cy="120" r="11" fill="#F23F55" ${L2}/><text x="30" y="124" fill="#fff">99</text>
    <circle cx="172" cy="112" r="9" fill="#F23F55" ${L2}/><text x="172" y="116" fill="#fff">7</text>
    <circle cx="160" cy="30" r="8" fill="#F23F55" ${L2}/><text x="160" y="34" fill="#fff">!</text>
</g>
<g class="b-float2"><rect x="18" y="20" width="34" height="22" rx="7" fill="#fff" ${L2}/><path d="M28 42 l-4 8 l10 -8" fill="#fff" ${L2}/>
    <text x="35" y="36" text-anchor="middle" ${FONT} font-size="12" fill="${INK}">♥ 3</text></g>`;

// ── 3. Fantasma do Esquecimento ──────────────────────────────────────────────

const page = (x, y, a, delay) => `<g transform="translate(${x} ${y}) rotate(${a})"><g class="b-page" style="animation-delay:${delay}s">
    <path d="M-11 -14 h17 l5 5 v23 h-22z" fill="#FFFBEF" ${L2}/><path d="M6 -14 v5 h5" fill="none" ${L2}/>
    <path d="M-7 -5 h12 M-7 0 h14 M-7 5 h9" stroke="#8A9BB8" stroke-width="2" stroke-linecap="round"/></g></g>`;

const GHOST = `<defs>
    ${radial('body', [[0, '#FFFFFF'], [0.55, '#DCEBFF'], [1, '#8FA9E8']], { cx: '45%', cy: '30%', r: '75%' })}
    ${radial('aura', [[0, '#B9D2FF', 0.55], [1, '#B9D2FF', 0]], { cx: '50%', cy: '50%', r: '50%' })}
    ${radial('hole', [[0, '#05030A'], [0.7, '#2A2350'], [1, '#4B3F8A']], { cx: '50%', cy: '55%' })}
</defs>
<circle cx="100" cy="100" r="96" fill="${url('aura')}" class="b-glow"/>
${ground(46)}
<g class="b-hover">
    <path d="M100 22 C150 22 166 70 162 112 C160 140 174 160 160 176 C150 166 144 180 132 172 C124 184 110 176 102 186
        C94 176 82 186 72 174 C60 182 52 168 42 176 C30 160 40 140 38 112 C34 70 50 22 100 22Z" fill="${url('body')}" opacity=".96" ${L}/>
    <path d="M60 52 C70 36 86 30 100 30" stroke="#fff" stroke-width="6" fill="none" stroke-linecap="round" opacity=".9"/>
    <path d="M44 120 C24 116 14 98 16 84 C26 96 40 98 50 98" fill="${url('body')}" ${L}/>
    <path d="M156 116 C176 112 188 92 186 76 C176 90 162 94 152 94" fill="${url('body')}" ${L}/>
    <g class="b-swirl"><ellipse cx="78" cy="86" rx="15" ry="19" fill="${url('hole')}" ${L2}/>
        <path d="M78 86 m-2 0 a2 2 0 1 1 4 1 a5 5 0 1 1 -8 -3 a9 9 0 1 1 13 7" fill="none" stroke="#9FB8FF" stroke-width="1.8" stroke-linecap="round"/></g>
    <g class="b-swirl"><ellipse cx="122" cy="86" rx="15" ry="19" fill="${url('hole')}" ${L2}/>
        <path d="M122 86 m-2 0 a2 2 0 1 1 4 1 a5 5 0 1 1 -8 -3 a9 9 0 1 1 13 7" fill="none" stroke="#9FB8FF" stroke-width="1.8" stroke-linecap="round"/></g>
    <path d="M60 66 l14 4 M140 66 l-14 4" stroke="#6E7FB8" stroke-width="3" stroke-linecap="round"/>
    <path d="M84 126 C88 116 112 116 116 126 C118 140 112 146 100 146 C88 146 82 140 84 126Z" fill="${url('hole')}" ${L2}/>
    <path d="M90 136 q10 -6 20 0" stroke="#9FB8FF" stroke-width="2" fill="none" opacity=".7"/>
    <ellipse cx="66" cy="110" rx="7" ry="4" fill="#B4C8FF" opacity=".6"/><ellipse cx="134" cy="110" rx="7" ry="4" fill="#B4C8FF" opacity=".6"/>
    <path d="M70 156 h16 M110 160 h20 M84 170 h12" stroke="#A9BCEB" stroke-width="3" stroke-linecap="round" opacity=".7"/>
</g>
${page(176, 56, 18, 0)}${page(22, 64, -16, 0.7)}${page(178, 140, 30, 1.4)}${page(20, 150, -28, 2.1)}
<g class="b-twinkle" ${FONT} fill="#8FA9E8" stroke="${INK}" stroke-width="1.2" paint-order="stroke">
    <text x="150" y="30" font-size="20">?</text><text x="38" y="34" font-size="15">?</text></g>`;

// ── 4. Golem da Procrastinação ───────────────────────────────────────────────

const stone = (d, extra = '') => `<path d="${d}" fill="${url('stone')}" ${L} ${extra}/>`;

const GOLEM = `<defs>
    ${radial('stone', [[0, '#C9C3BA'], [0.6, '#8F877D'], [1, '#5A534C']])}
    ${radial('core', [[0, '#FFE9A8'], [0.5, '#FF9E3D'], [1, '#C2410C']], { cx: '50%', cy: '50%' })}
    ${radial('rune', [[0, '#FFB866', 0.9], [1, '#FF7A1A', 0]], { cx: '50%', cy: '50%', r: '50%' })}
    ${linear('moss', [[0, '#8BC34A'], [1, '#4E7D2A']])}
</defs>
${ground(80)}
<g class="b-stomp">
    ${stone('M58 150 L52 186 H92 L88 150Z')}${stone('M112 150 L108 186 H148 L142 150Z')}
    ${stone('M44 70 C40 54 60 46 100 46 C140 46 160 54 156 70 L160 140 C160 156 142 160 100 160 C58 160 40 156 40 140Z')}
    <path d="M56 60 C70 52 90 50 104 50" stroke="#E6E1D9" stroke-width="5" fill="none" stroke-linecap="round" opacity=".7"/>
    <path d="M52 100 l14 6 l-4 12 M146 96 l-10 10 l6 14 M70 140 l12 -4 l8 8" fill="none" stroke="#4A443E" stroke-width="2.5" stroke-linecap="round"/>
    <path d="M42 74 C50 62 64 66 72 58 C78 70 60 78 48 84Z" fill="${url('moss')}" ${L2}/>
    <path d="M128 52 C138 60 152 56 158 70 C148 74 136 68 128 66Z" fill="${url('moss')}" ${L2}/>
    <circle cx="100" cy="104" r="30" fill="${url('core')}" class="b-glow"/>
    <circle cx="100" cy="104" r="24" fill="#FFF6DE" ${L}/>
    <g stroke="${INK}" stroke-width="2.5" stroke-linecap="round">
        <path d="M100 84 v5 M100 119 v5 M80 104 h5 M115 104 h5"/><path d="M86 90 l2 2 M114 90 l-2 2 M86 118 l2 -2 M114 118 l-2 -2" stroke-width="1.8"/></g>
    <g class="b-tick"><path d="M100 104 V88" stroke="#C2410C" stroke-width="3" stroke-linecap="round"/></g>
    <path d="M100 104 L110 110" stroke="${INK}" stroke-width="3.5" stroke-linecap="round"/>
    <circle cx="100" cy="104" r="3" fill="${INK}"/>
    <path d="M86 94 l6 6 l-3 6 M116 112 l-5 -2" stroke="#9AA4B0" stroke-width="1.5" fill="none"/>
    ${stone('M70 46 C66 26 80 14 100 14 C120 14 134 26 130 46 C126 54 74 54 70 46Z')}
    <path d="M78 22 C86 16 96 16 104 17" stroke="#E6E1D9" stroke-width="4" fill="none" stroke-linecap="round" opacity=".7"/>
    <path d="M82 32 l14 5 l-14 3z M118 32 l-14 5 l14 3z" fill="#FFB347" class="b-glow" ${L2}/>
    <path d="M90 46 h20" stroke="${INK}" stroke-width="3" stroke-linecap="round"/>
    <g class="b-arm-l">${stone('M44 72 C24 76 16 100 18 124 L22 150 H48 L50 122 C52 106 52 90 56 80Z')}
        ${stone('M12 146 C10 134 22 128 34 128 C48 128 56 136 54 150 C52 164 42 168 32 168 C20 168 14 160 12 146Z')}
        <path d="M26 92 l6 8 l-6 8 l6 8" stroke="#FF8A2A" stroke-width="3" fill="none" stroke-linecap="round" class="b-glow"/></g>
    <g class="b-arm-r">${stone('M156 72 C176 76 184 100 182 124 L178 150 H152 L150 122 C148 106 148 90 144 80Z')}
        ${stone('M188 146 C190 134 178 128 166 128 C152 128 144 136 146 150 C148 164 158 168 168 168 C180 168 186 160 188 146Z')}
        <path d="M174 92 l-6 8 l6 8 l-6 8" stroke="#FF8A2A" stroke-width="3" fill="none" stroke-linecap="round" class="b-glow"/></g>
    <g transform="rotate(-8 166 178)"><rect x="132" y="166" width="62" height="22" rx="3" fill="#C89B6D" ${L2}/>
        <text x="163" y="182" text-anchor="middle" ${FONT} font-size="11" fill="#5A3714">AMANHÃ</text></g>
</g>`;

// ── 5. Harpia das Notificações ───────────────────────────────────────────────

const feathers = (side) => {
    const s = side === 'l' ? -1 : 1;
    const x = (v) => 100 + s * v;
    return [0, 1, 2, 3, 4].map((i) => {
        const tip = [96, 92, 84, 72, 58][i];
        const y = 40 + i * 16;
        return `<path d="M${x(30)} ${y + 10} C${x(56)} ${y - 4} ${x(tip - 10)} ${y - 6} ${x(tip)} ${y + 2} C${x(tip - 16)} ${y + 14} ${x(50)} ${y + 22} ${x(30)} ${y + 24}Z" fill="${url(i % 2 ? 'wingB' : 'wingA')}" ${L2}/>`;
    }).reverse().join('');
};

const bell = (x, y, delay) => `<g transform="translate(${x} ${y})"><g class="b-ring" style="animation-delay:${delay}s">
    <path d="M-9 6 C-9 -6 -6 -12 0 -12 C6 -12 9 -6 9 6 L12 9 H-12Z" fill="#FFCD3C" ${L2}/><circle cx="0" cy="12" r="3" fill="#FFCD3C" ${L2}/>
    <path d="M-5 -4 C-4 -8 -2 -9 0 -9" stroke="#FFF4C2" stroke-width="2" fill="none" stroke-linecap="round"/>
    <circle cx="9" cy="-10" r="6" fill="#F23F55" ${L2}/><text x="9" y="-6.5" text-anchor="middle" ${FONT} font-size="8" fill="#fff">!</text></g></g>`;

const HARPY = `<defs>
    ${linear('wingA', [[0, '#B98AF5'], [1, '#6B3FB8']], [0, 0, 1, 1])}
    ${linear('wingB', [[0, '#9C6BE8'], [1, '#4B2A8A']], [0, 0, 1, 1])}
    ${radial('body', [[0, '#E8D6FF'], [0.6, '#A77BE6'], [1, '#5E3AA0']])}
    ${radial('face', [[0, '#FFE7D6'], [1, '#E7B49A']])}
    ${radial('eye', [[0, '#FFD1F0'], [1, '#E0287F']], { cx: '50%', cy: '50%' })}
</defs>
${ground(50)}
<g class="b-wing-l">${feathers('l')}</g>
<g class="b-wing-r">${feathers('r')}</g>
<g class="b-hover">
    <path d="M82 150 l-6 26 M88 152 l-2 26 M118 150 l6 26 M112 152 l2 26" stroke="#E0A100" stroke-width="5" stroke-linecap="round"/>
    <path d="M70 178 l6 -4 l6 4 M80 180 l6 -4 l4 5 M110 180 l4 -5 l6 4 M118 178 l6 -4 l6 4" stroke="${INK}" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M70 92 C66 120 72 150 100 156 C128 150 134 120 130 92 C120 84 80 84 70 92Z" fill="${url('body')}" ${L}/>
    <path d="M84 104 q6 6 0 12 q6 6 0 12 M116 104 q-6 6 0 12 q-6 6 0 12 M100 112 q5 6 0 12 q5 6 0 12" stroke="#7C55C2" stroke-width="2" fill="none"/>
    <path d="M78 34 C70 20 74 8 80 4 C82 16 88 22 92 28 C92 14 98 4 106 2 C104 14 108 22 112 28 C116 18 124 14 130 16 C124 26 124 32 124 40Z" fill="${url('wingA')}" ${L2}/>
    <path d="M68 62 C64 36 82 26 100 26 C118 26 136 36 132 62 C130 82 118 96 100 96 C82 96 70 82 68 62Z" fill="${url('face')}" ${L}/>
    <path d="M66 58 C62 70 64 84 74 92 C70 76 74 66 82 58 M134 58 C138 70 136 84 126 92 C130 76 126 66 118 58" fill="#6B3FB8" ${L2}/>
    <path d="M72 48 C84 40 116 40 128 48 L124 62 C112 56 88 56 76 62Z" fill="#3A1F6B" ${L2}/>
    <g class="b-blink">${eye(86, 60, 8, '', { slit: true, id: 'eye' })}${eye(114, 60, 8, '', { slit: true, id: 'eye' })}</g>
    <path d="M94 72 L100 84 L106 72 Q100 69 94 72Z" fill="#FFB300" ${L2}/>
    <path d="M100 76 v7" stroke="${INK}" stroke-width="1.5"/>
    <circle cx="78" cy="74" r="4" fill="#FF8FB8" opacity=".6"/><circle cx="122" cy="74" r="4" fill="#FF8FB8" opacity=".6"/>
</g>
${bell(26, 30, 0)}${bell(176, 34, 0.35)}${bell(20, 116, 0.7)}${bell(182, 120, 1.05)}
<g class="b-lines" stroke="#FFCD3C" stroke-width="2.5" stroke-linecap="round"><path d="M40 20 l6 -6 M42 32 l8 -1 M160 22 l-6 -6 M158 34 l-8 -1"/></g>`;

// ── 6. Cavaleiro do Cansaço ──────────────────────────────────────────────────

const KNIGHT = `<defs>
    ${linear('armor', [[0, '#9AA5B8'], [0.5, '#56617A'], [1, '#2B3244']], [0, 0, 1, 1])}
    ${linear('cape', [[0, '#3B2D6B'], [1, '#160F2E']])}
    ${linear('blade', [[0, '#F1F5FB'], [0.5, '#B7C3D6'], [1, '#7C8AA3']], [0, 0, 1, 0])}
    ${radial('visor', [[0, '#E3B8FF', 1], [1, '#9B5DE5', 0]], { cx: '50%', cy: '50%', r: '50%' })}
    ${radial('mist', [[0, '#9B5DE5', 0.4], [1, '#9B5DE5', 0]], { cx: '50%', cy: '50%', r: '50%' })}
</defs>
<ellipse cx="100" cy="170" rx="96" ry="30" fill="${url('mist')}" class="b-glow"/>
${ground(70)}
<g class="b-cape"><path d="M56 64 C36 100 30 150 26 188 L52 178 L66 190 L84 178 L100 190 L116 178 L134 190 L148 178 L174 188 C170 150 164 100 144 64Z" fill="${url('cape')}" ${L}/>
    <path d="M60 90 C50 120 46 150 44 176 M140 90 C150 120 154 150 156 176" stroke="#4C3A86" stroke-width="3" fill="none"/></g>
<g class="b-breathe">
    <path d="M70 150 L66 186 H90 L92 150Z M130 150 L134 186 H110 L108 150Z" fill="${url('armor')}" ${L}/>
    <path d="M62 184 h30 M108 184 h30" stroke="${INK}" stroke-width="5" stroke-linecap="round"/>
    <path d="M64 70 C60 100 64 130 70 154 H130 C136 130 140 100 136 70 C124 62 76 62 64 70Z" fill="${url('armor')}" ${L}/>
    <path d="M100 70 V150" stroke="#2B3244" stroke-width="2"/>
    <path d="M74 80 C80 74 92 72 100 72" stroke="#D6DEEA" stroke-width="4" fill="none" stroke-linecap="round" opacity=".7"/>
    <path d="M86 98 C86 90 94 86 100 92 C92 94 92 106 100 110 C92 112 86 106 86 98Z" fill="#E3B8FF" ${L2}/>
    <text x="110" y="100" ${FONT} font-size="11" fill="#E3B8FF" stroke="${INK}" stroke-width="1" paint-order="stroke">z</text>
    <path d="M68 136 H132" stroke="#3E2A1A" stroke-width="8"/><rect x="94" y="131" width="12" height="10" rx="2" fill="#E0A100" ${L2}/>
    <path d="M40 62 C42 50 56 44 70 50 L74 80 C60 84 46 80 40 62Z M160 62 C158 50 144 44 130 50 L126 80 C140 84 154 80 160 62Z" fill="${url('armor')}" ${L}/>
    <path d="M46 62 l20 -4 M154 62 l-20 -4" stroke="#2B3244" stroke-width="2"/>
    <path d="M72 18 C72 8 128 8 128 18 L132 56 C132 66 68 66 68 56Z" fill="${url('armor')}" ${L}/>
    <path d="M100 8 V64" stroke="#2B3244" stroke-width="2"/>
    <path d="M78 16 C86 12 96 11 100 11" stroke="#D6DEEA" stroke-width="3.5" fill="none" stroke-linecap="round" opacity=".7"/>
    <rect x="76" y="32" width="48" height="10" rx="3" fill="#0B0816" ${L2}/>
    <ellipse cx="100" cy="37" rx="30" ry="10" fill="${url('visor')}" class="b-glow"/>
    <g class="b-droop"><path d="M82 36 q6 4 12 0 M106 36 q6 4 12 0" stroke="#F4DEFF" stroke-width="3" fill="none" stroke-linecap="round"/></g>
    <path d="M82 48 h36 M84 54 h32" stroke="#2B3244" stroke-width="2.5" stroke-linecap="round"/>
    <g class="b-plume"><path d="M126 14 C150 2 168 20 172 44 C160 34 150 30 132 30Z" fill="#7B3FC4" ${L2}/>
        <path d="M134 18 C150 14 160 24 164 36" stroke="#B98AF5" stroke-width="2.5" fill="none" stroke-linecap="round"/></g>
    <path d="M100 96 V178" stroke="${INK}" stroke-width="14" stroke-linecap="round"/>
    <path d="M100 100 V178 L100 186" stroke="${url('blade')}" stroke-width="10" stroke-linecap="square"/>
    <path d="M100 100 V176" stroke="#fff" stroke-width="2" opacity=".6"/>
    <path d="M78 98 H122" stroke="${INK}" stroke-width="12" stroke-linecap="round"/><path d="M78 98 H122" stroke="#C9A227" stroke-width="7" stroke-linecap="round"/>
    <rect x="95" y="80" width="10" height="16" rx="2" fill="#5B3A1E" ${L2}/><circle cx="100" cy="78" r="6" fill="#C9A227" ${L2}/>
    <circle cx="92" cy="90" r="9" fill="${url('armor')}" ${L2}/><circle cx="108" cy="90" r="9" fill="${url('armor')}" ${L2}/>
</g>
<g transform="rotate(80 166 180)"><path d="M156 172 h18 l-2 18 h-14z" fill="#F7EBE1" ${L2}/><path d="M174 176 q7 0 7 6 q0 6 -7 6" fill="none" ${L2}/></g>
<path d="M152 186 q8 -2 16 0" stroke="#6B3A1E" stroke-width="3" stroke-linecap="round" opacity=".7"/>
<g class="b-zzz" fill="#E3B8FF" stroke="${INK}" stroke-width="1.5" paint-order="stroke" ${FONT}>
    <text x="140" y="30" font-size="16">Z</text><text x="156" y="16" font-size="11">z</text></g>`;

// ── 7. Hidra da Ansiedade ────────────────────────────────────────────────────

const hydraHead = (x, y, angle, cls, mood) => {
    const mouth = mood === 'grito'
        ? `<path d="M-12 8 C-6 22 10 22 16 8 Z" fill="#5A1F2B" ${L2}/><path d="M-8 9 l2 5 l2 -5 M8 9 l2 5 l2 -5" fill="#fff" ${L2}/>`
        : `<path d="M-10 10 q4 -4 8 0 q4 4 8 0 q4 -4 8 0" fill="none" ${L2}/>`;
    return `<g transform="translate(${x} ${y}) rotate(${angle})"><g class="${cls}">
        <path d="M-22 -8 C-22 -26 22 -28 26 -6 C30 10 18 22 0 22 C-16 22 -24 10 -22 -8Z" fill="${url('scale')}" ${L}/>
        <path d="M-18 -16 L-26 -34 L-8 -22 M16 -18 L22 -36 L6 -24" fill="#E8505B" ${L2}/>
        <path d="M-14 -20 C-6 -26 4 -26 10 -24" stroke="#C8FFF2" stroke-width="3" fill="none" stroke-linecap="round" opacity=".7"/>
        ${eye(-8, -4, 6, '', { slit: true, id: 'eye' })}${eye(10, -4, 6, '', { slit: true, id: 'eye' })}
        <path d="M-15 -12 l10 3 M17 -12 l-10 3" stroke="${INK}" stroke-width="2.5" stroke-linecap="round"/>
        ${mouth}<circle cx="-14" cy="6" r="1.4" fill="${INK}"/><circle cx="-10" cy="7" r="1.4" fill="${INK}"/></g></g>`;
};

const HYDRA = `<defs>
    ${linear('scale', [[0, '#5EE6C1'], [0.55, '#1FA58A'], [1, '#0B5D55']], [0, 0, 1, 1])}
    ${linear('belly', [[0, '#FFF1C2'], [1, '#E9C46A']])}
    ${radial('eye', [[0, '#FFF27A'], [1, '#FF4D4D']], { cx: '50%', cy: '50%' })}
</defs>
${ground(84)}
<g class="b-idle">
    <path d="M18 182 C10 160 30 140 60 144 C80 132 120 132 140 144 C170 140 190 160 182 182Z" fill="${url('scale')}" ${L}/>
    <path d="M40 176 C50 160 80 152 100 152 C120 152 150 160 160 176" fill="none" stroke="#0B5D55" stroke-width="3"/>
    <path d="M30 168 q6 -6 12 0 M150 168 q6 -6 12 0 M60 154 q6 -6 12 0 M128 154 q6 -6 12 0" stroke="#5EE6C1" stroke-width="2.5" fill="none"/>
    <path d="M70 150 C70 110 80 80 100 70 C120 80 130 110 130 150Z" fill="${url('scale')}" ${L}/>
    <path d="M88 150 C88 120 92 96 100 84 C108 96 112 120 112 150" fill="${url('belly')}" ${L2}/>
    <path d="M90 136 h20 M92 122 h16 M95 108 h10" stroke="#C9A24A" stroke-width="2"/>
    <g class="b-neck-l"><path d="M76 146 C56 120 34 100 40 70" fill="none" stroke="${INK}" stroke-width="24" stroke-linecap="round"/>
        <path d="M76 146 C56 120 34 100 40 70" fill="none" stroke="#1FA58A" stroke-width="18" stroke-linecap="round"/>
        <path d="M70 136 C54 116 40 100 42 82" fill="none" stroke="#5EE6C1" stroke-width="4" stroke-linecap="round" opacity=".6"/>
        ${hydraHead(40, 62, -14, '', 'nervoso')}</g>
    <g class="b-neck-r"><path d="M124 146 C144 120 166 100 160 70" fill="none" stroke="${INK}" stroke-width="24" stroke-linecap="round"/>
        <path d="M124 146 C144 120 166 100 160 70" fill="none" stroke="#1FA58A" stroke-width="18" stroke-linecap="round"/>
        <path d="M130 136 C146 116 160 100 158 82" fill="none" stroke="#5EE6C1" stroke-width="4" stroke-linecap="round" opacity=".6"/>
        ${hydraHead(160, 62, 14, '', 'nervoso')}</g>
    <g class="b-neck-c"><path d="M100 90 C100 70 100 56 100 44" fill="none" stroke="${INK}" stroke-width="26" stroke-linecap="round"/>
        <path d="M100 90 C100 70 100 56 100 44" fill="none" stroke="#1FA58A" stroke-width="20" stroke-linecap="round"/>
        ${hydraHead(100, 36, 0, '', 'grito')}</g>
</g>
<g class="b-twinkle" ${FONT} stroke="${INK}" stroke-width="1.5" paint-order="stroke">
    <text x="14" y="30" font-size="22" fill="#FFCD3C">!</text><text x="176" y="28" font-size="22" fill="#FFCD3C">?</text>
    <text x="128" y="16" font-size="15" fill="#FF6B6B">!?</text></g>`;

// ── 8. Dragão da Banca ───────────────────────────────────────────────────────

const DRAGON = `<defs>
    ${linear('scale', [[0, '#FF6B5A'], [0.5, '#C9252F'], [1, '#6E0E1E']], [0, 0, 1, 1])}
    ${linear('wing', [[0, '#8E1B2A'], [1, '#3E0712']], [0, 0, 1, 1])}
    ${linear('gold', [[0, '#FFF2B0'], [0.5, '#F2B705'], [1, '#A86A00']])}
    ${linear('belly', [[0, '#FFD98A'], [1, '#E09A2D']])}
    ${radial('eye', [[0, '#FFFBD1'], [1, '#FFB000']], { cx: '50%', cy: '50%' })}
    ${radial('fire', [[0, '#FFF6C9'], [0.4, '#FFB000'], [1, '#FF4D00', 0]], { cx: '30%', cy: '50%', r: '70%' })}
    ${radial('aura', [[0, '#FF8A3D', 0.35], [1, '#FF8A3D', 0]], { cx: '50%', cy: '50%', r: '50%' })}
</defs>
<circle cx="100" cy="100" r="98" fill="${url('aura')}" class="b-glow"/>
${ground(86)}
<g class="b-wing-l"><path d="M70 80 C40 40 16 30 2 36 C10 50 8 62 4 76 C14 72 20 76 22 86 C30 80 38 84 40 94 C50 90 58 94 62 104Z" fill="${url('wing')}" ${L}/>
    <path d="M70 80 C50 60 30 48 4 38 M64 92 C50 76 34 70 22 86 M62 100 C54 92 46 90 40 94" stroke="#C9252F" stroke-width="2.5" fill="none"/></g>
<g class="b-wing-r"><path d="M130 80 C160 40 184 30 198 36 C190 50 192 62 196 76 C186 72 180 76 178 86 C170 80 162 84 160 94 C150 90 142 94 138 104Z" fill="${url('wing')}" ${L}/>
    <path d="M130 80 C150 60 170 48 196 38 M136 92 C150 76 166 70 178 86 M138 100 C146 92 154 90 160 94" stroke="#C9252F" stroke-width="2.5" fill="none"/></g>
<g class="b-breathe">
    <path d="M150 170 C176 168 192 150 186 128 C182 140 172 146 160 146" fill="none" stroke="${INK}" stroke-width="16" stroke-linecap="round"/>
    <path d="M150 170 C176 168 192 150 186 128 C182 140 172 146 160 146" fill="none" stroke="#C9252F" stroke-width="10" stroke-linecap="round"/>
    <path d="M186 128 l-4 -14 l12 8z" fill="#F2B705" ${L2}/>
    <path d="M56 186 C48 150 58 110 100 100 C142 110 152 150 144 186Z" fill="${url('scale')}" ${L}/>
    <path d="M78 186 C74 156 82 126 100 116 C118 126 126 156 122 186Z" fill="${url('belly')}" ${L2}/>
    <path d="M82 134 h36 M80 148 h40 M80 162 h40 M82 176 h36" stroke="#B7701E" stroke-width="2"/>
    <path d="M64 120 q6 -4 10 2 M126 120 q4 -6 10 -2 M60 150 q6 -4 10 2 M130 150 q4 -6 10 -2" stroke="#FF8F7E" stroke-width="2.5" fill="none" stroke-linecap="round"/>
    <path d="M100 100 C88 92 84 80 86 70 M100 100 C112 92 116 80 114 70" stroke="${INK}" stroke-width="3" fill="none"/>
    <path d="M84 72 C80 58 86 40 100 38 C114 40 120 58 116 72 C124 78 126 92 118 98 C110 104 90 104 82 98 C74 92 76 78 84 72Z" fill="${url('scale')}" ${L}/>
    <path d="M86 44 C74 30 70 16 74 6 C80 18 88 26 94 38Z M114 44 C126 30 130 16 126 6 C120 18 112 26 106 38Z" fill="#F4E3C0" ${L2}/>
    <path d="M86 42 L88 30 L94 38 L100 26 L106 38 L112 30 L114 42Z" fill="${url('gold')}" ${L2}/>
    <circle cx="100" cy="34" r="3" fill="#2BC4E8" ${L2}/>
    <path d="M78 64 C70 62 64 66 62 72 C70 70 76 72 80 76 M122 64 C130 62 136 66 138 72 C130 70 124 72 120 76" fill="#C9252F" ${L2}/>
    <g class="b-blink">${eye(90, 62, 7, '', { slit: true, id: 'eye' })}${eye(110, 62, 7, '', { slit: true, id: 'eye' })}</g>
    <path d="M82 54 l12 4 M118 54 l-12 4" stroke="${INK}" stroke-width="3" stroke-linecap="round"/>
    <path d="M86 84 C92 94 108 94 114 84 C110 90 90 90 86 84Z" fill="#5A1F2B" ${L2}/>
    <path d="M90 85 l2 5 l2 -4 M110 85 l-2 5 l-2 -4" fill="#fff" ${L2}/>
    <circle cx="94" cy="76" r="1.8" fill="${INK}"/><circle cx="106" cy="76" r="1.8" fill="${INK}"/>
    <g class="b-smoke" fill="#B7B0A8" opacity=".7"><circle cx="90" cy="70" r="3"/><circle cx="110" cy="70" r="3"/></g>
    <path d="M58 128 C44 132 36 144 40 158" fill="none" stroke="${INK}" stroke-width="16" stroke-linecap="round"/>
    <path d="M58 128 C44 132 36 144 40 158" fill="none" stroke="#C9252F" stroke-width="10" stroke-linecap="round"/>
    <path d="M142 128 C156 132 164 144 160 158" fill="none" stroke="${INK}" stroke-width="16" stroke-linecap="round"/>
    <path d="M142 128 C156 132 164 144 160 158" fill="none" stroke="#C9252F" stroke-width="10" stroke-linecap="round"/>
    <g transform="rotate(-6 100 162)">
        <rect x="40" y="150" width="120" height="30" rx="4" fill="#FFF6DD" ${L}/>
        <path d="M40 150 C34 150 32 156 32 165 C32 174 34 180 40 180 M160 150 C166 150 168 156 168 165 C168 174 166 180 160 180" fill="#EAD9AE" ${L2}/>
        <text x="100" y="170" text-anchor="middle" ${FONT} font-size="13" fill="#7A1020" letter-spacing="1.5">GABARITO</text>
        <circle cx="144" cy="172" r="7" fill="#C9252F" ${L2}/><path d="M141 172 l2 2 l4 -4" stroke="#fff" stroke-width="1.6" fill="none"/>
    </g>
    <path d="M36 158 l-4 6 M40 160 l-2 7 M45 160 l0 6 M164 158 l4 6 M160 160 l2 7 M155 160 l0 6" stroke="#F4E3C0" stroke-width="3" stroke-linecap="round"/>
</g>
<g class="b-ember" fill="#FFB000"><circle cx="30" cy="140" r="2.5"/><circle cx="172" cy="104" r="2"/><circle cx="20" cy="100" r="1.8"/><circle cx="180" cy="160" r="2.2"/></g>`;

export const BOSSES = [
    { id: 'slime', nome: 'Slime da Preguiça', titulo: 'Guardião do sofá', cor: '#5DBB3F', fala: '“Só mais cinco minutinhos...”', core: [100, 140], head: [100, 66], svg: SLIME },
    { id: 'goblin', nome: 'Goblin da Distração', titulo: 'Senhor das abas abertas', cor: '#8DB443', fala: '“Dá uma olhadinha no celular, vai...”', core: [100, 150], head: [100, 30], svg: GOBLIN },
    { id: 'fantasma', nome: 'Fantasma do Esquecimento', titulo: 'Ladrão de revisões', cor: '#8FA9E8', fala: '“O que você estudou semana passada mesmo?”', core: [100, 120], head: [100, 24], svg: GHOST },
    { id: 'golem', nome: 'Golem da Procrastinação', titulo: 'Arauto do amanhã', cor: '#FF9E3D', fala: '“Deixa pra amanhã. Amanhã você rende mais.”', core: [100, 110], head: [100, 12], svg: GOLEM },
    { id: 'harpia', nome: 'Harpia das Notificações', titulo: 'Rainha do plim', cor: '#A77BE6', fala: '“Plim! Plim! Alguém te mencionou!”', core: [100, 120], head: [100, 0], svg: HARPY },
    { id: 'cavaleiro', nome: 'Cavaleiro do Cansaço', titulo: 'Sentinela da madrugada', cor: '#9B5DE5', fala: '“Seus olhos estão pesados... descanse para sempre.”', core: [100, 120], head: [100, 6], svg: KNIGHT },
    { id: 'hidra', nome: 'Hidra da Ansiedade', titulo: 'Mil cabeças, mil “e se?”', cor: '#1FA58A', fala: '“E se cair o que você não estudou? E SE?!”', core: [100, 130], head: [100, 4], svg: HYDRA },
    { id: 'dragao', nome: 'Dragão da Banca', titulo: 'Guardião do gabarito', cor: '#C9252F', fala: '“Só os persistentes chegam ao meu gabarito.”', core: [100, 140], head: [100, 4], svg: DRAGON },
];

/** Marcas de dano por fração de vida perdida: cortes, curativo e suor. */
function wounds(boss, lostRatio) {
    const [cx, cy] = boss.core;
    const [hx, hy] = boss.head;
    const claw = (x, y) => [-8, 0, 8].map((o) => `M${x - 12 + o} ${y + 12 + o * 0.3} q10 -8 ${22 - Math.abs(o) / 2} -26`).join(' ');
    const slash = (x, y, delay) => `<g class="b-slash" style="animation-delay:${delay}s">
        <path d="${claw(x, y)}" fill="none" stroke="${INK}" stroke-width="5" stroke-linecap="round" opacity=".55"/>
        <path d="${claw(x, y)}" fill="none" stroke="#FF4D5E" stroke-width="2.6" stroke-linecap="round"/>
        <path d="${claw(x, y)}" fill="none" stroke="#FFD6DA" stroke-width="1" stroke-linecap="round" stroke-dasharray="4 8"/></g>`;
    let out = '';
    if (lostRatio >= 0.34) out += slash(cx + 18, cy - 4, 0);
    if (lostRatio >= 0.67) {
        out += slash(cx - 24, cy + 18, 0.2);
        out += `<g transform="translate(${hx - 30} ${hy + 30}) rotate(-25)"><rect x="-13" y="-5" width="26" height="10" rx="3" fill="#F5D6B4" stroke="${INK}" stroke-width="2"/>
            <path d="M-4 -2 v4 M0 -2 v4 M4 -2 v4" stroke="#C69C72" stroke-width="1.5"/></g>`;
        out += `<path class="b-sweat" d="M${hx + 32} ${hy + 22} q-5 8 0 11 q5 -3 0 -11z" fill="#8FD3FF" stroke="${INK}" stroke-width="1.5"/>`;
    }
    return out;
}

const koStars = (boss) => {
    const [hx, hy] = boss.head;
    return `<g transform="translate(${hx} ${Math.max(14, hy + 4)})"><g class="b-ko">${[0, 120, 240].map((a) => {
        const r = (a * Math.PI) / 180;
        return `<path transform="translate(${(Math.cos(r) * 26).toFixed(1)} ${(Math.sin(r) * 7).toFixed(1)})" d="M0 -7 L2 -2 L7 -2 L3 1.5 L4.5 7 L0 3.5 L-4.5 7 L-3 1.5 L-7 -2 L-2 -2Z" fill="#FFD166" stroke="${INK}" stroke-width="1.4"/>`;
    }).join('')}</g></g>`;
};

let seq = 0;

/**
 * SVG do boss. `hp`/`maxHp` controlam as marcas de dano; `defeated` desenha o nocaute; `locked` mostra só a
 * silhueta (bosses que ainda não apareceram).
 */
export function bossSvg(index, { size = 200, hp = null, maxHp = null, defeated = false, locked = false, label = '' } = {}) {
    const boss = BOSSES[((index % BOSSES.length) + BOSSES.length) % BOSSES.length];
    const prefix = `bs${++seq}-`;
    const lost = hp !== null && maxHp ? 1 - hp / maxHp : 0;
    const extra = locked ? '' : defeated ? koStars(boss) : wounds(boss, lost);
    const cls = ['boss-art', defeated ? 'is-defeated' : '', locked ? 'is-locked' : ''].filter(Boolean).join(' ');
    const art = boss.svg.replaceAll('__ID__', prefix);
    return `<svg class="${cls}" viewBox="0 0 200 200" width="${size}" height="${size}" role="img" aria-label="${label || (locked ? 'Boss desconhecido' : boss.nome)}"><g class="b-body">${art}</g>${extra}</svg>`;
}
