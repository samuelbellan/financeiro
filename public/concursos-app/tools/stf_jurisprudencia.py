#!/usr/bin/env python3
"""Baixa do STF a jurisprudência de cada artigo das leis de leis/ e grava dados/jurisprudencia/<lei>.json,
que a Lei seca (leis.html) mostra no botão "STF" de cada artigo.

Uso: python3 tools/stf_jurisprudencia.py [lei ...]     (sem argumentos: todas; ex.: cf ctn lrf)
     --refazer  ignora o cache e consulta de novo

Fonte: a busca de jurisprudencia.stf.jus.br (a mesma API que o site do STF usa). Cada acórdão do STF traz a
"legislação citada" indexada ("LEG-FED LCP-000101 ANO-2000 / ART-00042 PAR-00001"); a consulta pega os
acórdãos que citam o artigo daquela lei. Para cada artigo ficam até POR_ARTIGO acórdãos, nesta ordem:
com tese (repercussão geral), do Plenário, mais recentes. As súmulas (e súmulas vinculantes) vêm da
base de súmulas, ligadas pela legislação citada nelas ("Código Tributário Nacional de 1966, art. 71").
As respostas ficam em tools/.stf_cache.json, para rodar de novo sem consultar o STF outra vez.
"""
import datetime
import json
import re
import sys
import time
import urllib.request
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
SAIDA = RAIZ / 'dados' / 'jurisprudencia'
CACHE = Path(__file__).resolve().parent / '.stf_cache.json'
API = 'https://jurisprudencia.stf.jus.br/api/search/search'
UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36'
CAMPO = 'documental_legislacao_citada_texto'
POR_ARTIGO = 6
PAUSA = 0.35  # segundos entre consultas, para não sobrecarregar o STF

# id (o mesmo de js/ref-lei.js) → arquivo, tokens da lei no índice do STF (sem zeros à esquerda),
# e como a lei aparece na legislação citada das súmulas.
LEIS = {
    'cf': ('constituicao-federal-1988.md', ['cf', 'ano', '1988'], r'Constitui[çc][ãa]o Federal de 1988'),
    'ctn': ('lei-5172-1966-ctn.md', ['lei', '5172', 'ano', '1966'], r'C[óo]digo Tribut[áa]rio Nacional|Lei n[º°] ?5\.172/1966'),
    'lrf': ('lei-complementar-101-2000-lrf.md', ['lcp', '101', 'ano', '2000'], r'Lei Complementar n[º°] ?101/2000'),
    'lc116': ('lei-complementar-116-2003-iss.md', ['lcp', '116', 'ano', '2003'], r'Lei Complementar n[º°] ?116/2003'),
    'l4320': ('lei-4320-1964.md', ['lei', '4320', 'ano', '1964'], r'Lei n[º°] ?4\.320/1964'),
    'ct': ('lei-8137-1990-crimes-ordem-tributaria.md', ['lei', '8137', 'ano', '1990'], r'Lei n[º°] ?8\.137/1990'),
    'lai': ('lei-12527-2011-lai.md', ['lei', '12527', 'ano', '2011'], r'Lei n[º°] ?12\.527/2011'),
    'lgpd': ('lei-13709-2018-lgpd.md', ['lei', '13709', 'ano', '2018'], r'Lei n[º°] ?13\.709/2018'),
    'l14133': ('lei-14133-2021-licitacoes.md', ['lei', '14133', 'ano', '2021'], r'Lei n[º°] ?14\.133/2021'),
    'l9784': ('lei-9784-1999-processo-administrativo.md', ['lei', '9784', 'ano', '1999'], r'Lei n[º°] ?9\.784/1999'),
    'l14063': ('lei-14063-2020-assinaturas-eletronicas.md', ['lei', '14063', 'ano', '2020'], r'Lei n[º°] ?14\.063/2020'),
    'l6404': ('lei-6404-1976.md', ['lei', '6404', 'ano', '1976'], r'Lei n[º°] ?6\.404/1976'),
}

ROMANOS = [(1000, 'M'), (900, 'CM'), (500, 'D'), (400, 'CD'), (100, 'C'), (90, 'XC'), (50, 'L'), (40, 'XL'),
           (10, 'X'), (9, 'IX'), (5, 'V'), (4, 'IV'), (1, 'I')]


def romano(n):
    out = ''
    for v, r in ROMANOS:
        while n >= v:
            out += r
            n -= v
    return out


def chave(rotulo):
    """'Art. 18-A' → '18a', como o norm() de js/lei-seca.js."""
    s = re.sub(r'^Art\.?', '', rotulo)
    s = re.sub(r'(\d)o\b', r'\1', s)
    return re.sub(r'[.º°ª\s]', '', s).lower()


def artigos(arquivo):
    vistos = []
    for linha in (RAIZ / 'leis' / arquivo).read_text(encoding='utf-8').splitlines():
        m = re.match(r'^\*\*(Art\.?\s*[^*]+?)\*\*', linha)
        if m:
            k = chave(m.group(1))
            if re.fullmatch(r'\d+(-[a-z])?', k) and k not in vistos:
                vistos.append(k)
    return vistos


def token_stf(k):
    """'18-a' → '18a': no índice do STF 'ART-0018A' vira os tokens 'art' e '18a'."""
    return k.replace('-', '')


def consultar(corpo):
    req = urllib.request.Request(API, data=json.dumps(corpo).encode(),
                                 headers={'Content-Type': 'application/json', 'User-Agent': UA})
    for tentativa in range(4):
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                return json.load(r)['result']
        except Exception as e:  # o STF às vezes derruba a conexão: espera e tenta de novo
            if tentativa == 3:
                raise
            print(f'  (erro {e}; tentando de novo)', file=sys.stderr)
            time.sleep(5 * (tentativa + 1))


def consulta_artigo(toks, art):
    # span_near: os tokens da lei e, depois, "art N" colados, no mesmo item da legislação citada
    # (slop 95 < o intervalo de 100 posições entre itens da lista, então não pula para outra lei).
    span = {'span_near': {'clauses': [*({'span_term': {CAMPO: t}} for t in toks),
                                      {'span_near': {'clauses': [{'span_term': {CAMPO: 'art'}},
                                                                 {'span_term': {CAMPO: token_stf(art)}}],
                                                     'slop': 0, 'in_order': True}}],
                          'slop': 95, 'in_order': True}}
    return {
        'size': POR_ARTIGO, 'track_total_hits': True,
        '_source': ['titulo', 'julgamento_data', 'orgao_julgador', 'relator_processo_nome', 'relator_acordao_nome',
                    'ementa_texto', 'documental_tese_texto', 'documental_tese_tema_texto', 'documental_tese_tipo',
                    'is_repercussao_geral_merito', 'inteiro_teor_url', CAMPO],
        'query': {'bool': {
            'filter': [{'term': {'base': 'acordaos'}}, span],
            'should': [{'exists': {'field': 'documental_tese_texto', 'boost': 10}},
                       {'term': {'is_repercussao_geral_merito': {'value': True, 'boost': 5}}},
                       {'match': {'orgao_julgador': {'query': 'Tribunal Pleno', 'operator': 'and', 'boost': 3}}}],
        }},
        'sort': [{'_score': 'desc'}, {'julgamento_data': 'desc'}],
    }


PARTE = {'PAR': lambda v: '§ %sº' % int(v) if int(v) < 10 else '§ %s' % int(v),
         'INC': lambda v: romano(int(v)), 'LET': lambda v: v.lower(), 'CAPUT': lambda v: 'caput'}


def dispositivos(entradas, toks, art):
    """Partes do artigo citadas pelo acórdão: 'ART-00150 INC-00006 LET-A' → 'VI, a'."""
    cab = re.compile(r'\b' + r'\W+'.join(re.escape(t) if not t.isdigit() else '0*' + t for t in toks) + r'\b', re.I)
    alvo = re.compile(r'^0*' + re.escape(token_stf(art)) + r'$', re.I)
    out = []
    for e in entradas or []:
        if not cab.search(e):
            continue
        toks_e = re.findall(r'\b(ART|PAR|INC|LET|CAPUT)(?:-(\w+))?', e)
        atual = None
        for tipo, val in toks_e:
            if tipo == 'ART':
                atual = [] if alvo.match(val) else None
                if atual is not None:
                    out.append(atual)
            elif atual is not None and tipo in PARTE:
                try:
                    atual.append(PARTE[tipo](val))
                except ValueError:
                    pass
    partes = sorted({', '.join(p) for p in out if p})
    return partes


def resumir(h, toks, art):
    s = h['_source']
    ementa = re.sub(r'\s+', ' ', s.get('ementa_texto') or '').strip()
    if len(ementa) > 900:
        ementa = ementa[:900].rsplit(' ', 1)[0] + ' …'
    item = {
        't': s.get('titulo'),
        'data': s.get('julgamento_data'),
        'org': s.get('orgao_julgador'),
        'rel': (s.get('relator_acordao_nome') or s.get('relator_processo_nome') or '').title(),
        'ementa': ementa,
        'url': s.get('inteiro_teor_url'),
    }
    if s.get('documental_tese_texto'):
        item['tese'] = re.sub(r'\s+', ' ', s['documental_tese_texto']).strip()
        item['tema'] = re.sub(r'\s+', ' ', s.get('documental_tese_tema_texto') or '').strip()
        item['tipo_tese'] = s.get('documental_tese_tipo')
    disp = dispositivos(s.get(CAMPO), toks, art)
    if disp:
        item['disp'] = disp
    return {k: v for k, v in item.items() if v}


def sumulas(cache):
    if 'sumulas' not in cache:
        docs = []
        for de in range(0, 2000, 100):
            r = consultar({'size': 100, 'from': de, 'sort': [{'julgamento_data': 'asc'}, {'dg_unique': 'asc'}],
                           '_source': ['titulo', 'sumula_texto', 'situacao_sumula', 'is_vinculante', 'julgamento_data',
                                       CAMPO, 'aprovacao_url'],
                           'query': {'term': {'base': 'sumulas'}}})
            docs += [h['_source'] for h in r['hits']['hits']]
            time.sleep(PAUSA)
            if len(r['hits']['hits']) < 100:
                break
        cache['sumulas'] = docs
    return cache['sumulas']


def sumulas_da_lei(docs, padrao):
    """{artigo: [súmulas]} pela legislação citada: 'Código Tributário Nacional de 1966, art. 71, § 1º; e art. 97'."""
    out = {}
    for s in docs:
        if s.get('situacao_sumula') not in ('Aprovada', 'Alterada'):
            continue
        for linha in (s.get(CAMPO) or '').splitlines():
            if not re.match(r'\s*(?:' + padrao + ')', linha):
                continue
            resto = re.sub(r'^.*?(?=\bart)', '', linha, count=1)
            for m in re.finditer(r'\barts?\.\s*(\d+)[º°]?(?:-([A-Z]))?((?:[^;]*?))(?=;|$|\barts?\.)', resto):
                k = m.group(1) + ('-' + m.group(2).lower() if m.group(2) else '')
                parte = m.group(3).strip(' ,.e')
                out.setdefault(k, {})[s['titulo']] = {
                    't': s['titulo'],
                    'texto': re.sub(r'\s+', ' ', s.get('sumula_texto') or '').strip(),
                    'data': s.get('julgamento_data'),
                    'vinc': s.get('is_vinculante') in (True, 'True', 'true'),
                    'url': s.get('aprovacao_url'),
                    **({'disp': [parte]} if parte else {}),
                }
    return {k: sorted(v.values(), key=lambda x: (not x['vinc'], x['t'])) for k, v in out.items()}


def main():
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    refazer = '--refazer' in sys.argv
    cache = {} if refazer or not CACHE.exists() else json.loads(CACHE.read_text(encoding='utf-8'))
    cache.setdefault('art', {})
    SAIDA.mkdir(parents=True, exist_ok=True)
    docs = sumulas(cache)
    CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')
    hoje = datetime.date.today().isoformat()
    for lei in args or LEIS:
        arquivo, toks, padrao = LEIS[lei]
        sums = sumulas_da_lei(docs, padrao)
        arts = artigos(arquivo)
        print(f'{lei}: {len(arts)} artigos', flush=True)
        saida = {}
        for i, art in enumerate(arts):
            ck = f'{lei}:{art}'
            if ck not in cache['art']:
                r = consultar(consulta_artigo(toks, art))
                cache['art'][ck] = {'n': r['hits']['total']['value'], 'hits': r['hits']['hits']}
                time.sleep(PAUSA)
                if i % 20 == 19:
                    CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')
                    print(f'  {i + 1}/{len(arts)}', flush=True)
            c = cache['art'][ck]
            item = {}
            if c['n']:
                item['n'] = c['n']
                item['ac'] = [resumir(h, toks, art) for h in c['hits']]
            if art in sums:
                item['sum'] = sums[art]
            if item:
                saida[art] = item
        CACHE.write_text(json.dumps(cache, ensure_ascii=False), encoding='utf-8')
        destino = SAIDA / f'{lei}.json'
        destino.write_text(json.dumps({'fonte': 'Supremo Tribunal Federal — jurisprudencia.stf.jus.br', 'atualizado': hoje,
                                       'arts': saida}, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
        print(f'  → {destino.relative_to(RAIZ)}: {len(saida)} artigos com jurisprudência, '
              f'{destino.stat().st_size // 1024} KB', flush=True)


if __name__ == '__main__':
    main()
