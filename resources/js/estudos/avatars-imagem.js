// Avatares em ilustração pronta (uma imagem por estágio), com animações leves por cima (zzz, suor, brilhos).
// As imagens ficam em resources/images/estudos/<avatar>/0.webp a 6.webp (480x480): o Vite as copia para
// /build/assets com hash, e o service worker as guarda em cache para funcionar offline.

import { confetti, sparkle, sweat, zzz } from './avatars-vetor.js';
import familia0 from '../../images/estudos/familia/0.webp';
import familia1 from '../../images/estudos/familia/1.webp';
import familia2 from '../../images/estudos/familia/2.webp';
import familia3 from '../../images/estudos/familia/3.webp';
import familia4 from '../../images/estudos/familia/4.webp';
import familia5 from '../../images/estudos/familia/5.webp';
import familia6 from '../../images/estudos/familia/6.webp';

/** Ilustração quadrada com cantos arredondados (o __ID__ vira um id único em avatarSvg). */
const scene = (src, overlay = '') => '<defs><clipPath id="__ID__clip"><rect width="120" height="120" rx="16"/></clipPath></defs>'
    + `<image href="${src}" width="120" height="120" clip-path="url(#__ID__clip)" preserveAspectRatio="xMidYMid slice"/>`
    + `<rect x=".5" y=".5" width="119" height="119" rx="16" fill="none" stroke="#000" stroke-opacity=".12"/>${overlay}`;

const FAMILIA = [
    // 0: dormiu nos livros à noite
    scene(familia0, zzz(70, 38)),
    // 1: começou a estudar
    scene(familia1),
    // 2: a esposa traz um suco
    scene(familia2),
    // 3: o filho chega com a placa "FORÇA, PAI!"
    scene(familia3, sweat(78, 36)),
    // 4: a bebê no colo, esforço e suor
    scene(familia4, sweat(78, 34) + sweat(57, 26, 0.6)),
    // 5: aura de fogo
    scene(familia5, sweat(78, 32) + sweat(57, 24, 0.5) + sparkle(62, 7, 0.8, '#FFB703') + sparkle(20, 58, 0.7, '#FFB703', 0.6)),
    // 6: comemoração com medalha
    scene(familia6, confetti() + sparkle(10, 70, 0.9) + sparkle(112, 22, 0.8, '#FFD166', 0.5) + sparkle(62, 6, 0.7, '#FFD166', 0.9)),
];

export const AVATARS_IMAGEM = {
    familia: { nome: 'Família', estagios: FAMILIA },
};
