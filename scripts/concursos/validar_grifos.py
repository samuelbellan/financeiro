"""
Valida os grifos questão → trecho feitos à mão (por um agente) para o Resumo de uma matéria.

Entrada: storage/concursos/grifos/<materia>/*.jsonl, uma linha por questão:
  {"id": "2991962", "trechos": [{"arquivo": "dtrib-modulo3.md", "trecho": "...", "por_que": "..."}],
   "sem_trecho": null}

Confere:
  - todo trecho existe literalmente no arquivo .md indicado (ignorando markdown e espaços);
  - nenhum id repetido e nenhum id que não está nos cadernos da matéria;
  - cobertura: quantas questões únicas dos cadernos já têm linha (e lista as que faltam com --faltam).

Uso:
    python -I scripts/concursos/validar_grifos.py dtrib
    python -I scripts/concursos/validar_grifos.py dtrib --faltam
"""
import argparse
import glob
import json
import os
import re
import sys

RAIZ = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'storage', 'concursos')

# matéria → (prefixo dos .md de teoria, pastas de cadernos brutos do TEC)
MATERIAS = {
    'ctbga': ('ctbg', ['ctbga']),
    'dadm': ('dadm', ['dadm']),
    'dciv': ('dciv', ['dciv']),
    'dcon': ('dcon', ['dcon']),
    'demp': ('demp', ['demp']),
    'dpen': ('dpen', ['dpen']),
    'dtrib': ('dtrib', ['dtrib', 'dtrib-expert']),
    'mfin': ('mfin', ['mfin']),
}


def normalizar(s):
    s = re.sub(r'[*_`#>|\\]', '', s)
    s = s.replace(' ', ' ')
    return re.sub(r'\s+', ' ', s).strip().lower()


def questoes_dos_cadernos(pastas):
    ids = {}
    for pasta in pastas:
        for f in sorted(glob.glob(os.path.join(RAIZ, 'tec', pasta, '*.json'))):
            with open(f, encoding='utf-8') as fh:
                dados = json.load(fh)
            for q in dados.get('questoes') or []:
                ids.setdefault(str(q['id']), os.path.basename(f))
    return ids


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('materia', choices=sorted(MATERIAS))
    ap.add_argument('--faltam', action='store_true', help='lista os ids ainda sem linha')
    a = ap.parse_args()

    prefixo, pastas = MATERIAS[a.materia]
    textos = {}
    for f in glob.glob(os.path.join(RAIZ, 'materiais', f'{prefixo}-modulo*.md')):
        with open(f, encoding='utf-8') as fh:
            textos[os.path.basename(f)] = normalizar(fh.read())

    esperadas = questoes_dos_cadernos(pastas)
    vistos, erros = {}, []
    for f in sorted(glob.glob(os.path.join(RAIZ, 'grifos', a.materia, '*.jsonl'))):
        nome = os.path.basename(f)
        with open(f, encoding='utf-8') as fh:
            for n, linha in enumerate(fh, 1):
                if not linha.strip():
                    continue
                try:
                    r = json.loads(linha)
                except json.JSONDecodeError as e:
                    erros.append(f'{nome}:{n} JSON inválido ({e})')
                    continue
                qid = str(r.get('id', ''))
                if qid in vistos:
                    erros.append(f'{nome}:{n} id {qid} repetido (já em {vistos[qid]})')
                vistos[qid] = nome
                if qid not in esperadas:
                    erros.append(f'{nome}:{n} id {qid} não está nos cadernos de {a.materia}')
                trechos = r.get('trechos') or []
                if not trechos and not r.get('sem_trecho'):
                    erros.append(f'{nome}:{n} id {qid} sem trechos e sem "sem_trecho"')
                for t in trechos:
                    arq, tr = t.get('arquivo'), t.get('trecho') or ''
                    if arq not in textos:
                        erros.append(f'{nome}:{n} id {qid} arquivo inexistente: {arq}')
                    elif len(normalizar(tr)) < 15:
                        erros.append(f'{nome}:{n} id {qid} trecho curto demais: {tr!r}')
                    elif normalizar(tr) not in textos[arq]:
                        erros.append(f'{nome}:{n} id {qid} trecho não encontrado em {arq}: {tr[:70]!r}')

    for e in erros:
        print('ERRO', e)
    feitas = len(set(vistos) & set(esperadas))
    print(f'{a.materia}: {feitas}/{len(esperadas)} questões únicas com linha · {len(erros)} erro(s)')
    if a.faltam:
        for qid, caderno in esperadas.items():
            if qid not in vistos:
                print('FALTA', qid, caderno)
    sys.exit(1 if erros else 0)


if __name__ == '__main__':
    main()
