#!/usr/bin/env python3
"""Gera os arquivos de dados/ a partir do conteúdo que você escreve em conteudo/.

Uso (de qualquer pasta):  python3 tools/gerar_dados.py

Lê:
  conteudo/simulados/*.json   um arquivo por simulado (em ordem alfabética: simulado-01, simulado-02...)
  conteudo/caderno.json       o caderno de questões de uma lei só
  conteudo/flashcards/*.json  um arquivo por lei
  conteudo/resumos.json       os resumos por matéria (opcional)

Grava (não edite à mão; rode este script de novo):
  dados/simulados.json, dados/painel.json, dados/revisar.json, dados/fundamentos.json,
  dados/caderno/atual.json, dados/flashcards.json, dados/flashcards_indice.json, dados/resumos.json

O texto das leis vem de leis/*.md (via tools/leis_dispositivos.py). As siglas aceitas em "fontes", "lei" e nas
linhas "> sigla art" dos resumos são as de leis_dispositivos.ARQ: cf, ctn, lrf, lai, l8137, l4320, lgpd, l14133,
l9784, l14063, lc116.
"""
import json
import os
import re
import sys
from collections import Counter
from datetime import date
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
os.chdir(RAIZ)  # leis_dispositivos lê leis/*.md a partir da raiz
sys.path.insert(0, str(RAIZ / 'tools'))
import leis_dispositivos as LD  # noqa: E402

CONTEUDO = Path('conteudo')
DADOS = Path('dados')
HOJE = date.today().isoformat()
L = 'ABCDE'
ERROS = []

# Nome e link oficial de cada lei; a chave "<sigla>_md" é o texto local (abre no balão da página).
LEIS = {
    'cf': ('Constituição Federal de 1988', 'https://www.planalto.gov.br/ccivil_03/constituicao/constituicaocompilado.htm'),
    'ctn': ('Código Tributário Nacional — Lei nº 5.172/1966', 'https://www.planalto.gov.br/ccivil_03/leis/l5172compilado.htm'),
    'lrf': ('Lei Complementar nº 101/2000 (LRF)', 'https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp101.htm'),
    'lai': ('Lei nº 12.527/2011 (LAI)', 'https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm'),
    'l8137': ('Lei nº 8.137/1990 (crimes contra a ordem tributária)', 'https://www.planalto.gov.br/ccivil_03/leis/l8137.htm'),
    'l14133': ('Lei nº 14.133/2021 (Licitações e Contratos)', 'https://www.planalto.gov.br/ccivil_03/_ato2019-2022/2021/lei/l14133.htm'),
    'lgpd': ('Lei nº 13.709/2018 (LGPD)', 'https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709compilado.htm'),
    'l14063': ('Lei nº 14.063/2020 (assinaturas eletrônicas)', 'https://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/l14063.htm'),
    'l4320': ('Lei nº 4.320/1964', 'https://www.planalto.gov.br/ccivil_03/leis/l4320compilado.htm'),
    'l9784': ('Lei nº 9.784/1999 (Processo Administrativo)', 'https://www.planalto.gov.br/ccivil_03/leis/l9784.htm'),
    'lc116': ('Lei Complementar nº 116/2003 (ISS)', 'https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp116.htm'),
}
REFERENCES = {}
for k, (nome, url) in LEIS.items():
    if url:
        REFERENCES[k] = [nome, url]
    REFERENCES[k + '_md'] = [f'{LD.ARQ[k][0]} — texto local (leis/)', 'leis/' + LD.ARQ[k][1]]
# Nome curto de cada lei no painel "O que revisar" e como o comentário a cita.
CURTO = {k: LD.ARQ[k][0] for k in LEIS}
# Id da lei em js/ref-lei.js, quando é diferente do de leis_dispositivos.py.
REF_LEI = {'l8137': 'ct'}


def trecho(cit):
    """Texto do dispositivo; artigo inteiro e longo (mais de 6 linhas) fica só com o caput."""
    t = LD.trecho(*cit)
    if t and not (cit[2] or cit[3]) and t.count('\n') >= 6:
        t = t.split('\n')[0]
    return t


def erro(msg):
    ERROS.append(msg)


def ler(p):
    try:
        return json.loads(Path(p).read_text(encoding='utf-8'))
    except json.JSONDecodeError as e:
        sys.exit(f'{p}: JSON inválido na linha {e.lineno}, coluna {e.colno}: {e.msg}')


def grava(p, dados):
    p = Path(p)
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(json.dumps(dados, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
    print(f'  {p}')


# ---------------------------------------------------------------- simulados
def simulados():
    questoes = []
    arquivos = sorted((CONTEUDO / 'simulados').glob('*.json'))
    for n, arq in enumerate(arquivos, 1):
        s = ler(arq)
        nome = s['nome']
        if not re.match(r'Simulado \d+ · ', nome):
            nome = f'Simulado {n} · {nome}'
        for i, q in enumerate(s['questoes'], 1):
            onde = f'{arq.name}, questão {i}'
            ops = q['alternativas']
            if q.get('gabarito') not in L[:len(ops)]:
                erro(f'{onde}: "gabarito" deve ser uma letra de A a {L[len(ops) - 1]}')
                continue
            fontes = q.get('fontes', [])
            for f in fontes:
                if f not in LEIS:
                    erro(f'{onde}: fonte "{f}" desconhecida (use: {", ".join(LEIS)})')
            refs = []
            for f in fontes:
                refs += [r for r in (f, f + '_md') if r in REFERENCES]
            alts = q.get('por_alternativa', [])
            if alts and len(alts) != len(ops):
                erro(f'{onde}: "por_alternativa" precisa ter um texto por alternativa')
            questoes.append({
                'id': f'S{n}-{i:02d}', 'source': nome, 'number': i,
                'area': q.get('area', 'Geral'), 'tema': q.get('tema') or q.get('area', 'Geral'), 'fontes': fontes,
                'stem': q['enunciado'], 'options': ops, 'official': q['gabarito'], 'checked': q['gabarito'],
                'explanation': q.get('comentario', ''), 'refs': refs, 'alts': alts,
                'items': [{'id': a, 'ok': b, 'note': c} for a, b, c in q.get('itens', [])],
                'fundamento': q.get('fundamento', []),
                **({'figure': q['figura']} if q.get('figura') else {}),
            })
    return questoes


def normas_da_questao(q, normas):
    """[[índice da norma, [artigos citados no comentário]], ...] — usado pelo painel do início."""
    out = []
    for f in q['fontes']:
        nome = CURTO[f]
        if nome not in normas:
            normas.append(nome)
        arts = list(dict.fromkeys(c[1] for c in LD.citacoes(q['explanation'], f) if c[0] == f))
        out.append([normas.index(nome), arts])
    return out


# ---------------------------------------------------------------- caderno
def caderno():
    p = CONTEUDO / 'caderno.json'
    if not p.exists():
        return {'titulo': 'Caderno', 'sigla': '', 'lei': None, 'arquivo': None}, []
    c = ler(p)
    lei = c.get('lei')
    if lei and lei not in LEIS:
        erro(f'caderno.json: lei "{lei}" desconhecida')
        lei = None
    info = {'titulo': c['titulo'], 'sigla': c.get('sigla') or (CURTO[lei] if lei else ''), 'lei': REF_LEI.get(lei, lei),
            'arquivo': 'leis/' + LD.ARQ[lei][1] if lei else None}
    out = []
    for n, q in enumerate(c['questoes'], 1):
        ops = q['alternativas']
        if q.get('gabarito') not in L[:len(ops)]:
            erro(f'caderno.json, questão {n}: "gabarito" deve ser uma letra de A a {L[len(ops) - 1]}')
            continue
        xs = q.get('comentarios') or [''] * len(ops)
        if len(xs) != len(ops):
            erro(f'caderno.json, questão {n}: "comentarios" precisa ter um texto por alternativa')
        law = []
        if lei:
            for cit in LD.citacoes(q.get('ref', ''), lei):
                txt = trecho(cit)
                if txt and not any(b['k'] == cit[1] for b in law):
                    law.append({'k': cit[1], 'paras': txt.split('\n'), 'v': 1})
        out.append({'n': n, 'model': 'v1', 'level': q.get('nivel', 'Médio'), 'format': q.get('formato', ''),
                    'secao': q.get('secao', 'Geral'), 'stem': q['enunciado'], 'opts': ops, 'x': xs,
                    'a': L.index(q['gabarito']), 'ref': q.get('ref', ''), 'law': law})
    return info, out


# ---------------------------------------------------------------- flashcards
LACUNA = re.compile(r'\[\[(.+?)\]\]')
NUMEROS = re.compile(r'\d|\b(um|uma|dois|duas|três|quatro|cinco|seis|sete|oito|nove|dez|quinze|vinte|trinta|sessenta|noventa|cem)\b', re.I)


def texto_do_card(lei, art):
    """(caput, trecho) do dispositivo citado em "Art. 5º, XI": o caput à parte, como na lei."""
    if lei not in LEIS:
        return [], []
    cits = LD.citacoes(art, lei)
    if not cits:
        return [], []
    linhas = []
    for cit in cits:
        t = trecho(cit)
        if t:
            linhas += [l for l in t.split('\n') if l not in linhas]
    sig, a, par, inc, ali = cits[0]
    if not (par or inc) or len(linhas) < 2:
        return [], linhas
    corte = 1 + (1 if par and inc else 0)
    return linhas[:corte], linhas[corte:]


def flashcards():
    leis, cards = [], []
    for arq in sorted((CONTEUDO / 'flashcards').glob('*.json')):
        f = ler(arq)
        lei = f['lei']
        secoes = []
        for i, c in enumerate(f['cards'], 1):
            tipo = c.get('tipo', 'pergunta')
            o = {'id': f'{lei}-{i}', 'lei': REF_LEI.get(lei, lei), 'tipo': tipo, 'secao': c.get('secao', 'Geral'), 'art': c.get('art', '')}
            if o['secao'] not in secoes:
                secoes.append(o['secao'])
            if tipo == 'lacuna':
                if not LACUNA.search(c.get('texto', '')):
                    erro(f'{arq.name}, card {i}: lacuna sem [[resposta]] no "texto"')
                    continue
                o.update(texto=c['texto'], contexto=c.get('contexto', ''), verso=' · '.join(LACUNA.findall(c['texto'])))
            else:
                o.update(frente=c['frente'], verso=c['verso'])
                caput, trecho = texto_do_card(lei, o['art'])
                if c.get('trecho'):
                    caput, trecho = [], [c['trecho']] if isinstance(c['trecho'], str) else c['trecho']
                if trecho:
                    o['trecho'] = trecho
                if caput:
                    o['caput'] = caput
            if c.get('armadilha'):
                o['armadilha'] = c['armadilha']
            if NUMEROS.search(o['verso']):
                o['num'] = 1
            cards.append(o)
        leis.append({'id': REF_LEI.get(lei, lei), 'sigla': f['sigla'], 'curto': f.get('curto', f['sigla']), 'nome': f.get('nome', f['sigla']), 'secoes': secoes})
    rot = lambda c: f'{next(l["sigla"] for l in leis if l["id"] == c["lei"])} · {c["secao"]}'
    secoes = list(dict.fromkeys(rot(c) for c in cards))
    ids = [l['id'] for l in leis]
    indice = {'gerado': HOJE, 'leis': [{'id': l['id'], 'sigla': l['sigla']} for l in leis], 'secoes': secoes,
              'cards': [[c['id'], secoes.index(rot(c)), ids.index(c['lei'])] for c in cards]}
    return {'gerado': HOJE, 'leis': leis, 'cards': cards}, indice


# ---------------------------------------------------------------- resumos
ROM = re.compile(r'^[IVXL]+(?:,[IVXL]+)*$')
TITULO = re.compile(r'([.;:])\s+[^.;:]*\b(?:Seção|SEÇÃO|Subseção|CAPÍTULO|Capítulo|TÍTULO|Título|LIVRO)\b[^.;:]*$')


def bloco_lei(spec, onde):
    """'ctn 151 I,VI' → {'rot', 'lei', 'art', 'txt'}: o texto exato do dispositivo, com o caput."""
    sigla, art, *resto = spec.split()
    if sigla not in LEIS:
        erro(f'{onde}: "> {spec}": lei "{sigla}" desconhecida')
        return None
    par, incs, ali = None, [None], None
    for tok in resto:
        if tok.startswith('§'):
            par = tok[1:]
        elif tok == 'pu':
            par = 'pu'
        elif ROM.match(tok):
            incs = tok.split(',')
        elif re.fullmatch(r'[a-z]', tok):
            ali = tok
    linhas = []
    for inc in incs:
        t = LD.trecho(sigla, art, par, inc, ali)
        if not t:
            erro(f'{onde}: "> {spec}": artigo não encontrado em leis/')
            return None
        for l in t.split('\n'):
            l = re.sub(r'\s+(?:Vigência|Regulamento|Produção de efeitos?)$', '', TITULO.sub(r'\1', l))
            if l not in linhas:
                linhas.append(l)
    inc_rot = ', '.join(i for i in incs if i) or None
    return {'rot': LD.rotulo(sigla, art, par, inc_rot, ali), 'lei': REF_LEI.get(sigla, sigla), 'art': 'Art. ' + art, 'txt': linhas}


def resumos(ids_sim, n_caderno):
    p = CONTEUDO / 'resumos.json'
    if not p.exists():
        return {'gerado': HOJE, 'materias': []}
    r = ler(p)
    for m in r['materias']:
        for t in m['topicos']:
            onde = f'resumos.json, "{t["titulo"]}"'
            linhas = t.pop('linhas', None)
            if linhas is None:
                linhas = t.pop('texto', '').split('\n')
            t['linhas'] = [x for x in (bloco_lei(l[1:].strip(), onde) if l.startswith('>') else l.rstrip() for l in linhas) if x is not None]
            t.setdefault('lei', m.get('lei'))
            t['ids'] = t.get('ids', [])
            t['lom'] = t.pop('caderno', [])
            for i in t['ids']:
                if i not in ids_sim:
                    erro(f'{onde}: questão "{i}" não existe nos simulados')
            for i in t['lom']:
                if not 1 <= i <= n_caderno:
                    erro(f'{onde}: questão {i} não existe no caderno')
    return {'gerado': r.get('gerado', HOJE), 'materias': r['materias']}


# ---------------------------------------------------------------- O que revisar (texto da lei de cada questão)
def revisar(questoes, cad_info, cad):
    blocos, por_rot = [], {}

    def bloco(c):
        texto = trecho(c)
        if not texto:
            return None
        rot = LD.rotulo(*c)
        if rot not in por_rot:
            por_rot[rot] = len(blocos)
            blocos.append([rot, texto])
        return por_rot[rot]

    def regra(ex):
        frases, out = re.split(r'(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚ“"(])', ex.strip()), ''
        for f in frases:
            if out and len(out) + len(f) > 320:
                break
            out = (out + ' ' + f).strip()
        return out

    rq = {}
    for q in questoes:
        padrao = q['fontes'][0] if q['fontes'] else None
        texto = q['explanation'] + ' ' + ' '.join(i['note'] for i in q['items'])
        ids = [b for b in (bloco(c) for c in LD.citacoes(texto, padrao)) if b is not None][:3]
        rq[q['id']] = {'b': ids, 'g': regra(q['explanation']), 'o': q['options']}
    lom = {}
    lei = {v: k for k, v in REF_LEI.items()}.get(cad_info['lei'], cad_info['lei'])
    if lei:
        for q in cad:
            for b in q['law']:
                if b['k'] not in lom:
                    i = bloco((lei, b['k'], None, None, None))
                    if i is not None:
                        lom[b['k']] = i
    return {'blocos': blocos, 'q': rq, 'lom': lom}


def main():
    print('Lendo conteudo/ ...')
    qs = simulados()
    cad_info, cad = caderno()
    fc, fc_indice = flashcards()
    rs = resumos({q['id'] for q in qs}, len(cad))
    if ERROS:
        print(f'\n{len(ERROS)} problema(s) — nada foi gravado:')
        for e in ERROS:
            print(' -', e)
        sys.exit(1)

    fontes = list(dict.fromkeys(q['source'] for q in qs))
    areas = list(dict.fromkeys(q['area'] for q in qs))
    temas = list(dict.fromkeys(q['tema'] for q in qs))
    normas = []
    secoes = list(dict.fromkeys(q['secao'] for q in cad))
    painel = {
        'gerado': HOJE,
        'simulados': {'fontes': fontes, 'areas': areas, 'normas': normas, 'temas': temas,
                      'q': {q['id']: [fontes.index(q['source']), areas.index(q['area']), normas_da_questao(q, normas), temas.index(q['tema'])] for q in qs}},
        'lom': {'info': cad_info, 'secoes': secoes, 'q': {str(q['n']): secoes.index(q['secao']) for q in cad},
                'arts': {str(q['n']): [b['k'] for b in q['law']] for q in cad}},
    }
    fundamentos = {q['id']: q['fundamento'] for q in qs if q['fundamento']}
    publicadas = [{k: v for k, v in q.items() if k not in ('tema', 'fontes', 'fundamento')} for q in qs]

    print('Gravando:')
    grava(DADOS / 'simulados.json', {'questions': publicadas, 'references': REFERENCES})
    grava(DADOS / 'painel.json', painel)
    grava(DADOS / 'revisar.json', revisar(qs, cad_info, cad))
    grava(DADOS / 'fundamentos.json', fundamentos)
    grava(DADOS / 'caderno' / 'atual.json', cad)
    grava(DADOS / 'flashcards.json', fc)
    grava(DADOS / 'flashcards_indice.json', fc_indice)
    grava(DADOS / 'resumos.json', rs)
    print(f'\n{len(qs)} questões em {len(fontes)} simulados · {len(cad)} no caderno · {len(fc["cards"])} flashcards · '
          f'{sum(len(m["topicos"]) for m in rs["materias"])} tópicos de resumo')
    print('Gabaritos por letra:', dict(sorted(Counter(q['official'] for q in qs).items())))


if __name__ == '__main__':
    main()
