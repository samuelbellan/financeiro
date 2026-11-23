"""Converte as páginas compiladas do Planalto (.htm) em Markdown na pasta leis/, no mesmo formato de leis_para_md.py.

Uso: python3 tools/leis_planalto_para_md.py PASTA_COM_OS_HTM

- o texto riscado é descartado — fica só a redação vigente. O Planalto risca de dois jeitos: <strike>/<s>/<del> e
  <span style="text-decoration:line-through"> (este último é o da redação anterior e da "Vigência encerrada");
- notas de alteração entre parênteses ("(Redação dada pela...)", "(Incluído pela...)") saem;
- cada artigo começa com o número em negrito; parágrafos, incisos e alíneas ficam em linhas próprias.
"""
import html
import re
import sys
from pathlib import Path

LEIS = {
    'cf': ('constituicao-federal-1988.md', 'Constituição da República Federativa do Brasil de 1988'),
    'ctn': ('lei-5172-1966-ctn.md', 'Lei nº 5.172/1966 — Código Tributário Nacional'),
    'l4320': ('lei-4320-1964.md', 'Lei nº 4.320/1964 — Normas gerais de direito financeiro'),
    'lgpd': ('lei-13709-2018-lgpd.md', 'Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais'),
    'l14133': ('lei-14133-2021-licitacoes.md', 'Lei nº 14.133/2021 — Licitações e Contratos Administrativos'),
    'l9784': ('lei-9784-1999-processo-administrativo.md', 'Lei nº 9.784/1999 — Processo administrativo federal'),
    'l14063': ('lei-14063-2020-assinaturas-eletronicas.md', 'Lei nº 14.063/2020 — Assinaturas eletrônicas'),
    'lc116': ('lei-complementar-116-2003-iss.md', 'Lei Complementar nº 116/2003 — ISS'),
    'd1171': ('decreto-1171-1994-etica.md', 'Decreto nº 1.171/1994 — Código de Ética do Servidor Público Civil Federal'),
}
NOTA = re.compile(r'\s*\((?:[^()]*?(?:Reda[çc][ãa]o|Inclu[íi]d|Acrescid|Revogad|Vide|Vig[êe]ncia|Renumerad|Regulamento|Promulga|Produ[çc][ãa]o de efeito|Express[ãa]o|Emenda Constitucional|Lei Complementar n|Lei n|Medida Provis|ADI|ADC|Mantid|Suspens|Declarad|Retificad|Transformad)[^()]*(?:\([^()]*\)[^()]*)*)\)', re.I)
START = re.compile(r'^(Art\.\s*\d|§|Par[áa]grafo\s+[úu]nico|[IVXLC]+\s*[-–—]\s|[a-z]\)\s|T[ÍI]TULO|CAP[ÍI]TULO|SE[ÇC][ÃA]O|SUBSE[ÇC][ÃA]O)', re.I)
HEAD = [(r'T[ÍI]TULO\b', '##'), (r'CAP[ÍI]TULO\b', '###'), (r'SUBSE[ÇC][ÃA]O\b', '#####'), (r'SE[ÇC][ÃA]O\b', '####')]


def tira_riscado_css(s):
    """Apaga os <span> com text-decoration:line-through (e tudo o que há dentro, contando os <span> aninhados)."""
    tags = re.compile(r'(?is)<(/?)span\b[^>]*>')
    out, pos = [], 0
    for m in re.finditer(r'(?is)<span\b[^>]*line-through[^>]*>', s):
        if m.start() < pos:
            continue                                   # já estava dentro de um span apagado
        fundo = 1
        for t in tags.finditer(s, m.end()):
            fundo += -1 if t.group(1) else 1
            if fundo == 0:
                out.append(s[pos:m.start()])
                pos = t.end()
                break
    out.append(s[pos:])
    return ''.join(out)


def to_text(raw):
    s = raw.decode('cp1252', errors='replace')
    s = re.sub(r'(?is)<(script|style|head)\b.*?</\1>', '', s)
    s = re.sub(r'\s+', ' ', s)  # quebras de linha do HTML não separam dispositivos; só as tags de bloco
    s = tira_riscado_css(s)
    s = re.sub(r'(?is)<(strike|s|del)\b[^>]*>.*?</\1>', '', s)
    s = re.sub(r'(?i)<br\s*/?>', ' ', s)
    s = re.sub(r'(?i)</?(p|div|h\d|tr|li|table|blockquote)\b[^>]*>', '\n', s)
    s = re.sub(r'<[^>]+>', '', s)
    s = html.unescape(s).replace('\xa0', ' ')
    out = []
    for line in s.split('\n'):
        line = re.sub(r'\s+', ' ', line).strip()
        line = NOTA.sub('', line).strip()
        if line and line != 'Vigência encerrada':      # sobra do dispositivo riscado cuja vigência acabou
            out.append(line)
    return out


def convert(lines, title):
    # começa no primeiro artigo ou título do corpo da lei
    first = next(i for i, l in enumerate(lines) if re.match(r'^(Art\.\s*1|T[ÍI]TULO I\b|PRE[ÂA]MBULO)', l))
    out = [f'# {title}', '']
    for l in lines[first:]:
        if re.match(r'^(Bras[íi]lia|Este texto n[ãa]o substitui|\*)', l):
            break
        level = next((h for p, h in HEAD if re.match(p, l) and len(l) < 90), None)
        if level:
            out.extend([f'{level} {l}', ''])
            continue
        l = re.sub(r'^Artigo\s+(\d)', r'Art. \1', l)  # a Lei 4.320 escreve "Artigo 101." em alguns artigos
        l = re.sub(r'^(Art\.\s*\d+(?:\.\d+)?[º°o]?(?:-[A-Z]+)?\.?)', r'**\1**', l)
        out.extend([l, ''])
    return re.sub(r'\n{3,}', '\n\n', '\n'.join(out)).rstrip() + '\n'


if __name__ == '__main__':
    src = Path(sys.argv[1])
    for key, (name, title) in LEIS.items():
        f = src / f'{key}.htm'
        if not f.exists():
            print('falta', f)
            continue
        md = convert(to_text(f.read_bytes()), title)
        (Path(__file__).resolve().parent.parent / 'leis' / name).write_text(md, encoding='utf-8')
        print(name, len(md))
