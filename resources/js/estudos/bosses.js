// Bosses da Jornada (31 bosses da BOSS PROGRESSION 01 to 31 com suporte a modelos 3D).
// Fonte de dados: bosses.json. Bosses com status_3d "pronto" possuem modelo 3D (.glb); os demais são 2D.
// Novos modelos podem ser adicionados apenas atualizando o JSON e copiando os arquivos para public/bosses/modelos/.

import bossesData from './bosses.json';

const INK = '#1E1A22';
const L = `stroke="${INK}" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"`;
const L2 = `stroke="${INK}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"`;
const FONT = 'font-family="system-ui, -apple-system, sans-serif" font-weight="900"';

const escSvg = (s) => String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');

const radial = (id, stops, { cx = '40%', cy = '35%', r = '70%' } = {}) => `<radialGradient id="__ID__${id}" cx="${cx}" cy="${cy}" r="${r}">${stops
    .map(([o, c, a = 1]) => `<stop offset="${o}" stop-color="${c}" stop-opacity="${a}"/>`).join('')}</radialGradient>`;
const linear = (id, stops, [x1, y1, x2, y2] = [0, 0, 0, 1]) => `<linearGradient id="__ID__${id}" x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}">${stops
    .map(([o, c, a = 1]) => `<stop offset="${o}" stop-color="${c}" stop-opacity="${a}"/>`).join('')}</linearGradient>`;
const url = (id) => `url(#__ID__${id})`;
const ground = (rx = 70, cx = 100) => `<ellipse cx="${cx}" cy="188" rx="${rx}" ry="8" fill="#000" opacity=".22"/>`;

/** Cores padrão vibrantes para os bosses que não possuem cor_destaque no JSON. */
const DEFAULT_COLORS = {
    1: '#4EBA6F', 2: '#5C7080', 3: '#7FB069', 4: '#E76F51', 5: '#9B5DE5',
    6: '#FF5A4A', 7: '#5FB8FF', 8: '#8338EC', 9: '#E09F3E', 10: '#6B705C',
    11: '#A0C4FF', 12: '#FF6B35', 13: '#2A9D8F', 14: '#F4A261', 15: '#D62828',
    16: '#A68A64', 17: '#52B788', 18: '#DDA15E', 19: '#E63946', 20: '#1D3557',
    21: '#9E2A2B', 22: '#9FE8FF', 23: '#6F1D1B', 24: '#B5838D', 25: '#F77F00',
    26: '#48CAE4', 27: '#7209B7', 28: '#B5179E', 29: '#457B9D', 30: '#3A0CA3',
    31: '#FFD166',
};

function makeBossSvgTemplate(b, cor, is3D) {
    const num = String(b.numero).padStart(2, '0');
    const shortName = b.nome.length > 17 ? b.nome.slice(0, 16) + '…' : b.nome;
    return `<defs>
        ${linear('bg', [[0, cor], [1, '#15171D']])}
        ${radial('halo', [[0, '#ffffff', 0.28], [1, '#000000', 0]])}
    </defs>
    ${ground(70)}
    <rect x="18" y="18" width="164" height="164" rx="28" fill="${url('bg')}" ${L}/>
    <circle cx="100" cy="100" r="56" fill="${url('halo')}"/>
    <circle cx="100" cy="100" r="46" fill="#101318" opacity=".38" ${L2}/>
    ${is3D ? `<g transform="translate(136 34)"><rect x="-17" y="-9" width="34" height="18" rx="5" fill="#3B82F6" ${L2}/><text x="0" y="4" text-anchor="middle" ${FONT} font-size="10" fill="#fff" letter-spacing=".5">3D</text></g>` : ''}
    <text x="100" y="112" text-anchor="middle" ${FONT} font-size="34" fill="#FFFDF5" letter-spacing="1">${num}</text>
    <text x="100" y="152" text-anchor="middle" ${FONT} font-size="11" fill="#E8E4DA" opacity=".92">${escSvg(shortName)}</text>`;
}

export const BOSSES = bossesData.bosses.map((b) => {
    const is3D = b.status_3d === 'pronto' && Boolean(b.modelo);
    const cor = b.cor_destaque || DEFAULT_COLORS[b.numero] || '#5FB8FF';
    const lore = b.lore || '';
    const titulo = lore || b.ato;
    const fala = lore ? `“${lore}”` : `“Eu sou ${b.nome}, guardião de ${b.ato}!”`;
    return {
        numero: b.numero,
        id: b.id,
        nome: b.nome,
        ato: b.ato,
        status_3d: b.status_3d,
        is3D,
        modelo: b.modelo,
        imagem_fonte: b.imagem_fonte,
        lore,
        titulo,
        fala,
        cor,
        orientacao_yaw_graus: b.orientacao_yaw_graus ?? 0,
        triangulos: b.triangulos,
        tamanho_bytes: b.tamanho_bytes,
        observacao: b.observacao,
        core: [100, 130],
        head: [100, 30],
        svg: makeBossSvgTemplate(b, cor, is3D),
    };
});

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
 * SVG do boss (usado para miniaturas no card de timer, lista e mapas).
 * `hp`/`maxHp` controlam marcas de dano; `defeated` desenha o nocaute; `locked` mostra a silhueta.
 */
export function bossSvg(index, { size = 200, hp = null, maxHp = null, defeated = false, locked = false, label = '' } = {}) {
    const boss = BOSSES[((index % BOSSES.length) + BOSSES.length) % BOSSES.length];
    const prefix = `bs${++seq}-`;
    const lost = hp !== null && maxHp ? 1 - hp / maxHp : 0;
    const extra = locked ? '' : defeated ? koStars(boss) : wounds(boss, lost);
    const cls = ['boss-art', defeated ? 'is-defeated' : '', locked ? 'is-locked' : '', boss.is3D ? 'has-3d' : 'is-2d'].filter(Boolean).join(' ');
    const art = boss.svg.replaceAll('__ID__', prefix);
    return `<svg class="${cls}" viewBox="0 0 200 200" width="${size}" height="${size}" role="img" aria-label="${label || (locked ? 'Boss desconhecido' : `${boss.nome} (${boss.is3D ? '3D' : '2D'})`)}"><g class="b-body">${art}</g>${extra}</svg>`;
}

let modelViewerLoaded = false;

/** Carrega dinamicamente a versão recomendada @google/model-viewer@3.5.0 apenas quando necessário. */
export function ensureModelViewer() {
    if (typeof window === 'undefined') return;
    if (modelViewerLoaded || customElements.get('model-viewer')) {
        modelViewerLoaded = true;
        return;
    }
    const script = document.createElement('script');
    script.type = 'module';
    script.src = 'https://unpkg.com/@google/model-viewer@3.5.0/dist/model-viewer.min.js';
    document.head.appendChild(script);
    modelViewerLoaded = true;
}

/** Renderiza o elemento <model-viewer> para bosses 3D, girado pelo orientacao_yaw_graus de cada boss para ficar de frente. */
export function renderBoss3D(boss, { height = 280, autoRotate = true, cls = '' } = {}) {
    ensureModelViewer();
    const modelSrc = boss.modelo.startsWith('/') ? boss.modelo : `/bosses/${boss.modelo}`;
    const posterAttr = boss.imagem_fonte ? `poster="/bosses/${boss.imagem_fonte}"` : '';
    const yaw = boss.orientacao_yaw_graus ?? 0;
    return `<model-viewer
        src="${modelSrc}"
        ${posterAttr}
        alt="${escSvg(boss.nome)} em 3D"
        orientation="0deg 0deg ${yaw}deg"
        camera-controls
        ${autoRotate ? 'auto-rotate' : ''}
        auto-rotate-delay="0"
        rotation-per-second="20deg"
        shadow-intensity="1"
        exposure="1.1"
        interaction-prompt="none"
        loading="lazy"
        class="${cls || 'arena-model-viewer'}"
        style="--poster-color: transparent; height: ${height}px;">
    </model-viewer>`;
}

/** Renderiza o card 2D para bosses sem modelo 3D (status_3d: 'pendente'). */
export function renderBoss2D(boss, { cls = '' } = {}) {
    const num = String(boss.numero).padStart(2, '0');
    return `<div class="arena-2d-card ${cls}" style="--boss:${boss.cor}">
        <span class="boss-badge-2d">${num}</span>
        <b class="boss-name-2d">${escSvg(boss.nome)}</b>
        <small class="boss-act-2d">${escSvg(boss.ato)}</small>
        ${boss.lore ? `<p class="boss-lore-2d">“${escSvg(boss.lore)}”</p>` : ''}
        <span class="boss-2d-status">⚔ 2D · Modelo em breve</span>
    </div>`;
}

/** Renderiza o palco da arena: 3D com model-viewer para bosses 3D ou card 2D para os pendentes. */
export function renderBossStage(boss, { height = 280, autoRotate = true } = {}) {
    if (boss.is3D) {
        return `<div class="arena-stage-model" style="--boss:${boss.cor}">
            ${renderBoss3D(boss, { height, autoRotate, cls: 'arena-model-viewer' })}
            <div class="model-hint"><small>Arraste para girar · Toque ou role para zoom</small></div>
        </div>`;
    }
    return renderBoss2D(boss);
}
