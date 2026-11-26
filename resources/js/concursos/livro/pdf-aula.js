// Aula em PDF abaixo do resumo do capítulo (caderno de TI). Desenha com o pdf.js, página a página
// conforme a rolagem chega perto, porque o Chrome do Android não mostra PDF dentro da página.
import * as pdfjs from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

export async function montarPdfAula(secao) {
    const info = secao.querySelector('[data-pdf-info]');
    const lista = secao.querySelector('[data-pdf-paginas]');
    let pdf;
    try {
        pdf = await pdfjs.getDocument({ url: secao.dataset.pdf, withCredentials: true }).promise;
    } catch (e) {
        info.textContent = 'Não consegui abrir o PDF aqui; use "Abrir em outra aba".';
        return;
    }
    info.textContent = `${pdf.numPages} páginas`;

    // Espaço reservado com a proporção da 1ª página, para a rolagem não pular ao desenhar.
    const primeira = await pdf.getPage(1);
    const vp1 = primeira.getViewport({ scale: 1 });
    const caixas = [];
    for (let n = 1; n <= pdf.numPages; n++) {
        const caixa = document.createElement('div');
        caixa.className = 'aula-pdf-pagina';
        caixa.style.aspectRatio = `${vp1.width} / ${vp1.height}`;
        caixa.dataset.n = String(n);
        caixa.innerHTML = `<small>${n}</small>`;
        lista.append(caixa);
        caixas.push(caixa);
    }

    const desenhar = async caixa => {
        if (caixa.dataset.feito) return;
        caixa.dataset.feito = '1';
        const pagina = await pdf.getPage(Number(caixa.dataset.n));
        const base = pagina.getViewport({ scale: 1 });
        const escala = (caixa.clientWidth * Math.min(window.devicePixelRatio || 1, 2)) / base.width;
        const vp = pagina.getViewport({ scale: escala });
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(vp.width);
        canvas.height = Math.floor(vp.height);
        caixa.style.aspectRatio = `${base.width} / ${base.height}`;
        caixa.prepend(canvas);
        await pagina.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
    };

    const obs = new IntersectionObserver(entradas => {
        for (const e of entradas) {
            if (e.isIntersecting) {
                obs.unobserve(e.target);
                desenhar(e.target);
            }
        }
    }, { rootMargin: '800px 0px' });
    caixas.forEach(c => obs.observe(c));
}
