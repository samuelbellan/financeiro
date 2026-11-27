// Traços à mão (perfect-freehand) em coordenadas de largura fixa 1000: o desenho escala com a tela.
// Usado pelo rascunho do livro e pelas notas com caneta. Formato {c: tinta, p: [x, y, pressão, ...], s?: 1}
// validado de novo no servidor (Documento::limparRascunho): manter igual.
import { getStroke } from 'perfect-freehand';

export const LARGURA = 1000;
export const TINTAS = {
    preto: { nome: 'Preto', tam: 4 },
    azul: { nome: 'Azul', tam: 4 },
    vermelho: { nome: 'Vermelho', tam: 4 },
    verde: { nome: 'Verde', tam: 4 },
    marca: { nome: 'Marca-texto', tam: 22 },
};

/** Contorno do traço (perfect-freehand) como "d" de um path SVG. */
export function caminho(traco, final = true) {
    const pts = [];
    for (let i = 0; i < traco.p.length; i += 3) pts.push([traco.p[i], traco.p[i + 1], traco.p[i + 2]]);
    const tinta = TINTAS[traco.c] ?? TINTAS.preto;
    const contorno = getStroke(pts, {
        size: tinta.tam,
        thinning: traco.c === 'marca' ? 0 : 0.55,
        smoothing: 0.5,
        streamline: 0.45,
        simulatePressure: !!traco.s,
        last: final,
    });
    if (!contorno.length) return '';
    const d = contorno.reduce((acc, [x0, y0], i, arr) => {
        const [x1, y1] = arr[(i + 1) % arr.length];
        acc.push(x0.toFixed(1), y0.toFixed(1), ((x0 + x1) / 2).toFixed(1), ((y0 + y1) / 2).toFixed(1));
        return acc;
    }, ['M', ...contorno[0].map(v => v.toFixed(1)), 'Q']);
    return d.join(' ') + ' Z';
}

export const svgEl = (tag, attrs = {}) => {
    const n = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
    return n;
};

export const arred = v => Math.round((v || 0.5) * 100) / 100;
