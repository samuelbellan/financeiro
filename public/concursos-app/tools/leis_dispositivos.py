"""Índice das leis de leis/*.md por dispositivo e leitura das citações ("art. 150, § 1º, da CF").

Usado por build_simulados.py para mostrar, em "O que revisar", o texto da lei de cada questão errada.
Regra de exibição (memória do projeto): o dispositivo aparece inteiro, como na lei, e inciso ou alínea vêm
sempre com o caput (e o parágrafo) que os introduz.
"""
import re
from pathlib import Path

ARQ = {
    'cf': ('CF', 'constituicao-federal-1988.md'), 'ctn': ('CTN', 'lei-5172-1966-ctn.md'),
    'lrf': ('LRF', 'lei-complementar-101-2000-lrf.md'), 'lai': ('LAI', 'lei-12527-2011-lai.md'),
    'l8137': ('Lei 8.137', 'lei-8137-1990-crimes-ordem-tributaria.md'), 'l4320': ('Lei 4.320', 'lei-4320-1964.md'),
    'lgpd': ('LGPD', 'lei-13709-2018-lgpd.md'), 'l14133': ('Lei 14.133', 'lei-14133-2021-licitacoes.md'),
    'l9784': ('Lei 9.784', 'lei-9784-1999-processo-administrativo.md'),
    'l14063': ('Lei 14.063', 'lei-14063-2020-assinaturas-eletronicas.md'),
    'lc116': ('LC 116', 'lei-complementar-116-2003-iss.md'),
}
_ROMS = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI',
         'XVII', 'XVIII', 'XIX', 'XX', 'XXI', 'XXII', 'XXIII', 'XXIV', 'XXV', 'XXVI', 'XXVII', 'XXVIII', 'XXIX', 'XXX']


def _rom(r):
    return _ROMS.index(r) if r in _ROMS else 0


ROMANO = re.compile(r'^(?=[IVXLC])(C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$')


def _norm_art(n):
    return re.sub(r'[º°o.]', '', n).upper()


def indexa(path):
    """{art: [ (nivel, rótulo, texto) ]} — nível 0 caput, 1 parágrafo, 2 inciso, 3 alínea, 4 item."""
    arts, cur = {}, None
    texto = Path(path).read_text(encoding='utf-8')
    pref, titulo = '', False
    for linha in texto.split('\n'):
        l = linha.strip()
        if l.startswith('#') and re.search(r'Disposi[çc][õo]es Transit[óo]rias', l, re.I):
            pref = 'ADCT '  # a numeração recomeça no ADCT: chave "ADCT 2"
        if l.startswith('#'):
            titulo = True   # o texto solto depois de "## TÍTULO III" ("Crédito Tributário") é o nome do título
            continue
        if not l:
            continue
        m = re.match(r'^\*\*Art\.\s*(\d+(?:\.\d+)?)\s*[º°o]?\s*(-\s*[A-Z]+)?\.?\*\*\s*(.*)', l)
        if m:
            num = m.group(1).replace('.', '') + (m.group(2) or '').replace(' ', '')
            sobra = re.match(r'^(\d+)\s*[º°o]?\.?\s*', m.group(3))  # "Art. 5 7." no Planalto = art. 57
            if sobra and not m.group(2):
                num += sobra.group(1)
                l = l[:m.start(3)] + l[m.start(3) + sobra.end():]
            cur = pref + num.upper()
            arts[cur] = [(0, 'caput', l.replace('**', ''))]
            titulo = False
            continue
        if cur is None:
            continue
        if titulo and not re.match(r'^(§|Par[áa]grafo [úu]nico|[IVXLC]+\s*[-–—]|[a-z]\)|\d+\))', l):
            continue    # nome de título/capítulo/seção, não continuação do artigo anterior
        if re.match(r'^(§|Par[áa]grafo [úu]nico)', l):
            m = re.match(r'^§\s*(\d+)', l)
            arts[cur].append((1, ('§' + m.group(1)) if m else 'pu', l))
        elif re.match(r'^[IVXLC]+\s*[-–—]', l):
            arts[cur].append((2, re.match(r'^([IVXLC]+)', l).group(1), l))
        elif re.match(r'^[a-z]\)', l):
            arts[cur].append((3, l[0], l))
        elif re.match(r'^\d+\)', l):
            arts[cur].append((4, l.split(')')[0], l))
        else:
            # continuação (ou texto solto): junta ao dispositivo anterior
            n, r, t = arts[cur][-1]
            arts[cur][-1] = (n, r, t + ' ' + l)
    return arts


_IDX = {}


def lei(sigla):
    if sigla not in _IDX:
        _IDX[sigla] = indexa(Path('leis') / ARQ[sigla][1])
    return _IDX[sigla]


def trecho(sigla, art, par=None, inc=None, ali=None):
    """Texto do dispositivo citado: caput + (parágrafo inteiro | inciso inteiro com alíneas | alínea)."""
    a = lei(sigla).get(_norm_art(art))
    if not a:
        return None
    out = [a[0][2]]
    if not (par or inc or ali):
        return '\n'.join(t for _, _, t in a)
    # escolhe o bloco do parágrafo (ou o caput), depois o inciso dentro dele, depois a alínea
    i0, fim = 1, len(a)
    if par:
        alvo = 'pu' if par == 'pu' else '§' + par
        pos = next((i for i, (n, r, _) in enumerate(a) if n == 1 and r == alvo), None)
        if pos is None and par == 'pu':  # o parágrafo único virou § 1º depois de uma alteração
            pos = next((i for i, (n, r, _) in enumerate(a) if n == 1 and r == '§1'), None)
        if pos is None:
            return '\n'.join(t for _, _, t in a)
        nxt = next((i for i in range(pos + 1, len(a)) if a[i][0] <= 1), len(a))
        if not (inc or ali):
            return '\n'.join([out[0]] + [t for _, _, t in a[pos:nxt]])
        out.append(a[pos][2])
        i0, fim = pos + 1, nxt
    else:
        fim = next((i for i in range(1, len(a)) if a[i][0] == 1), len(a))
    if inc:
        pos = next((i for i in range(i0, fim) if a[i][0] == 2 and a[i][1] == inc), None)
        if pos is None:
            return '\n'.join(t for _, _, t in a)
        nxt = next((i for i in range(pos + 1, fim) if a[i][0] <= 2), fim)
        if not ali:
            return '\n'.join(out + [t for _, _, t in a[pos:nxt]])
        out.append(a[pos][2])
        pos2 = next((i for i in range(pos + 1, nxt) if a[i][0] == 3 and a[i][1] == ali), None)
        return '\n'.join(out + ([a[pos2][2]] if pos2 is not None else [t for _, _, t in a[pos + 1:nxt]]))
    return '\n'.join(out + [t for _, _, t in a[i0:fim]])


def rotulo(sigla, art, par=None, inc=None, ali=None):
    s = f'{ARQ[sigla][0]}, art. {art}'
    if par:
        s += ', parágrafo único' if par == 'pu' else f', § {par}º'
    if inc:
        s += f', {inc}'
    if ali:
        s += f', "{ali}"'
    return s


# ---------------------------------------------------------------- citações no comentário
NOMES = [
    (r'Constitui[çc][ãa]o|\bCF\b', 'cf'), (r'\bCTN\b|C[óo]digo Tribut[áa]rio', 'ctn'),
    (r'\bLRF\b|101/2000|Complementar n?º?\s*101|\bLC n?º?\s*101', 'lrf'), (r'\bLAI\b|12\.527', 'lai'),
    (r'8\.137', 'l8137'), (r'4\.320', 'l4320'), (r'\bLGPD\b|13\.709', 'lgpd'), (r'14\.133', 'l14133'),
    (r'9\.784', 'l9784'), (r'14\.063', 'l14063'), (r'LC n?º?\s*116|116/2003|Complementar n?º?\s*116', 'lc116'),
]
PADRAO = {'CTN': 'ctn', 'CF': 'cf', 'LRF': 'lrf', 'LAI': 'lai', 'Lei 4.320': 'l4320', 'Licitações (Lei 14.133)': 'l14133',
          'LGPD': 'lgpd', 'Lei 14.063': 'l14063', 'Lei 9.784': 'l9784', 'Lei 8.137': 'l8137',
          'ISS (LC 116)': 'lc116'}
CIT = re.compile(r'\b[Aa]rts?\.\s*(\d[\s\S]{0,140}?)(?=,?\s+e\s+arts?\.|,\s*arts?\.|[:;]|\.\s|\.$|\)|$| do\b| da\b| de\b| que\b| e o\b| no\b| na\b| para\b| com\b| é\b| s[ãa]o\b| fala\b| prevê\b| diz\b)')


def _lei_perto(texto, ini, fim):
    depois = texto[fim:fim + 45]
    for p, s in NOMES:
        if re.match(r'^\s*,?\s*(d[aoe]s?\s+)?(' + p + ')', depois):
            return s
    antes = texto[max(0, ini - 40):ini]
    achou = None
    for p, s in NOMES:
        for m in re.finditer(p, antes):
            if achou is None or m.start() > achou[0]:
                achou = (m.start(), s)
    return achou[1] if achou and re.search(r'[,:]\s*$|\bdo\s*$|\bda\s*$', antes[achou[0]:]) else None


def citacoes(texto, padrao=None):
    """[(sigla, art, par, inc, ali)] na ordem em que aparecem; sem nome de lei, usa `padrao`."""
    out = []
    for m in CIT.finditer(texto):
        seg = re.sub(r'(\d)\s*[º°o]-([A-Z])', r'\1-\2', m.group(1))  # "art. 8º-A" → "8-A"
        sigla = _lei_perto(texto, m.start(), m.end()) or padrao
        if not sigla:
            continue
        toks = re.findall(r'\barts?\.|\d+(?:-[A-Z])?|§+\s*\d+|par[áa]grafo [úu]nico|caput|\b[IVXLC]+\b|“[a-z]”|"[a-z]"|\b[a-z]\b|\ba\b|\be\b', seg)
        art = par = inc = None
        prev_art, rng, ultimo = None, False, 'art'  # ultimo: o que o número solto continua ("§§ 1º e 2º")
        for t in toks:
            if t.lower().startswith('art'):
                ultimo = 'art'
                continue
            if re.fullmatch(r'\d+(?:-[A-Z])?', t) and ultimo == 'par' and art and t.isdigit() and int(t) <= 30:
                t = '§' + t  # "§§ 1º e 2º": o 2 é parágrafo, não artigo
            if re.fullmatch(r'\d+(?:-[A-Z])?', t) and int(re.match(r'\d+', t).group()) > 260:
                continue  # número de lei ou ano ("LC nº 236/2026"), não artigo
            if re.fullmatch(r'\d+(?:-[A-Z])?', t):
                if rng and prev_art and t.isdigit() and prev_art.isdigit() and 0 < int(t) - int(prev_art) <= 4:
                    for k in range(int(prev_art) + 1, int(t) + 1):
                        out.append((sigla, str(k), None, None, None))
                    prev_art, art, par, inc, rng = t, t, None, None, False
                    continue
                art, par, inc, rng = t, None, None, False
                prev_art = t
                out.append((sigla, art, None, None, None))
            elif t.startswith('§') and art:
                par, inc, rng, ultimo = re.sub(r'\D', '', t), None, False, 'par'
                if out and out[-1][1] == art and not out[-1][2] and not out[-1][3]:
                    out.pop()  # "art. 44, § 1º": fica só o parágrafo
                if not out or out[-1] != (sigla, art, par, None, None):
                    out.append((sigla, art, par, None, None))
            elif t.lower().startswith('par') and art:
                par, inc, ultimo = 'pu', None, 'par'
                if out and out[-1][1] == art and not out[-1][2] and not out[-1][3]:
                    out.pop()
                out.append((sigla, art, 'pu', None, None))
            elif ROMANO.match(t) and art:
                if rng and inc and 0 < _rom(t) - _rom(inc) <= 6:  # "I a III": inclui o II
                    for k in range(_rom(inc) + 1, _rom(t)):
                        out.append((sigla, art, par, _ROMS[k], None))
                rng = False
                inc = t
                if out and out[-1][1] == art and out[-1][2] == par and not out[-1][3]:
                    out.pop()
                out.append((sigla, art, par, inc, None))
            elif re.fullmatch(r'[“"]?[a-z][”"]?', t) and t not in ('a', 'e', 'o') and art and inc:
                ali = t.strip('“”"')
                if out and out[-1][3] == inc and not out[-1][4]:
                    out.pop()
                out.append((sigla, art, par, inc, ali))
            elif t == 'a':
                rng = True
            elif t == 'e':
                rng = False
    vistos, res = set(), []
    for c in out:
        if c not in vistos:
            vistos.add(c)
            res.append(c)
    # artigo inteiro citado junto com um trecho dele (art. 38 e art. 38, IV): fica só o trecho
    especificos = {(c[0], c[1]) for c in res if c[2] or c[3]}
    return [c for c in res if c[2] or c[3] or (c[0], c[1]) not in especificos]
