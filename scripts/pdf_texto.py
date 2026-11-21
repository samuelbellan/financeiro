"""Extrai o texto de cada página de um PDF e imprime JSON em UTF-8 no stdout.

Uso: python pdf_texto.py arquivo.pdf
Saída: {"paginas": ["texto da página 1", ...], "imagens": [n_imagens_pag1, ...]}

Usado pelo módulo de concursos (App\\Services\\Concursos\\TecPdfExtractor).
pdfplumber com x_tolerance baixo separa corretamente as palavras dos PDFs gerados
pelo Chrome ("Salvar como PDF"), onde o pypdf cola palavras de linhas diferentes.
"""
import json
import sys

import pdfplumber


def main() -> int:
    if len(sys.argv) != 2:
        print(__doc__, file=sys.stderr)
        return 2

    paginas, imagens = [], []
    with pdfplumber.open(sys.argv[1]) as pdf:
        for pagina in pdf.pages:
            paginas.append(pagina.extract_text(x_tolerance=1.5) or "")
            imagens.append(len(pagina.images))

    sys.stdout.buffer.write(json.dumps({"paginas": paginas, "imagens": imagens}, ensure_ascii=False).encode("utf-8"))
    return 0


if __name__ == "__main__":
    sys.exit(main())
