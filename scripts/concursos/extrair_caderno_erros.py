"""
Extrai as questões erradas de um "Caderno de Erros" (PDF gerado no Gemini a partir das baterias
da Guruja) para o formato de importação do Material de Resumo.

O caderno tem a Parte 1 ("bateria de retomada cega": enunciado e alternativas, sem resposta) e a
Parte 2 (diagnóstico de cada questão, com o gabarito). O layout varia de caderno para caderno,
então o resultado deve ser conferido antes de importar.

Uso (um caderno por chamada; a saída é JSON na saída padrão):
    pdftotext -enc UTF-8 caderno.pdf caderno.txt
    python -I extrair_caderno_erros.py caderno.txt --secao c04 [--gabaritos 3846262=B,3863215=A]

Depende só da biblioteca padrão.
"""
import argparse
import json
import re
import sys

RODAPE = re.compile(
    r'^\s*(Caderno|Page \d|Página \d|P.gina \d|PARTE \d|Instru..o|SEM DICAS|#\s*PARTE|\(Objetivo|conceituais|pistas|'
    r'Refaça|FIM DA PARTE|Programa de|PROGRAMA|CADERNO|DIREITO|Direito Tribut|Disciplina:|.{0,3}Instru|Caderno de retomada|'
    r'MENTORIA|Mentoria|AUDITOR|Bateria|\d+\s*$|TOTAL|ACERTOS|ERROS|PONTOS|\d+ T.picos|RESOLVA)',
    re.I,
)
CABECALHO = re.compile(r'^(?:#+\s*)?Quest.o\s+(\d{1,8})\b\s*(.*)$')
DIAG = re.compile(r'\[\s*(?:Q|QUEST.O|Quest.o)\s*(\d{1,8})\s*\]')
GAB = re.compile(r'GABARITO:\s*\[?\s*(CERTO|ERRADO|Certo|Errado|[A-E])\b', re.I)
FIM_PARTE1 = re.compile(r'PARTE 2|DIAGN.STICO PROFUNDO|AN.LISE COGNITIVA|RAIO-X DE DESEMPENHO|Raio-X de Desempenho|ANÁLISE DE DESEMPENHO|Mapa das quest', re.I)
FIM_PARTE2 = re.compile(r'PARTE [345]|ANKI|Anki|PLANILHA|CONSOLIDA..O R.PIDA', re.I)


def limpar(linhas):
    return [l.rstrip() for l in linhas if l.strip() and not RODAPE.match(l) and '�' not in l[:3]]


def separar_alternativas(texto):
    """Acha a sequência a) b) c) d) [e)] no texto corrido. Devolve (enunciado, [{letra, texto}])."""
    marcas = list(re.finditer(r'(?:(?<=\s)|^)([a-eA-E])\)\s', texto))
    for i, m in enumerate(marcas):
        if m.group(1).lower() != 'a':
            continue
        seq, esperado = [m], 'b'
        for n in marcas[i + 1:]:
            if n.group(1).lower() == esperado:
                seq.append(n)
                esperado = chr(ord(esperado) + 1)
                if esperado > 'e':
                    break
        if len(seq) >= 4:
            alts = []
            for j, s in enumerate(seq):
                fim = seq[j + 1].start() if j + 1 < len(seq) else len(texto)
                alts.append({'letra': s.group(1).upper(), 'texto': texto[s.end():fim].strip()})
            return texto[:seq[0].start()].strip(), alts
    return texto.strip(), []


def origem_e_enunciado(resto):
    resto = resto.strip()
    m = re.match(r'^\(([^)]{3,80})\)\s*(.*)$', resto)
    if m:
        return m.group(1).strip(), m.group(2)
    m = re.match(r'^[—–-]\s*(.*)$', resto)
    if m:
        # "— BANCA / ÓRGÃO — Cargo (ano)" ou "— BANCA / Cargo UF Enunciado começa aqui..."
        corpo = m.group(1)
        # "(2022)" ou " / 2025" no fim da origem (não confunde com datas como 23/10/2024)
        corte = re.search(r'(\(\d{4}\)|\s/\s*\d{4}\b)', corpo)
        if corte and corte.end() < 140:
            return corpo[:corte.end()].strip(' —-'), corpo[corte.end():]
        # "BANCA / Cargo (Órgão) Enunciado...": a origem acaba antes da primeira palavra que abre
        # frase ("Assinale", "A legislação", "O CTN", "16) ..."), fora de parênteses e depois da barra.
        barra = corpo.find('/')
        abre_frase = re.compile(r'[A-ZÁÉÍÓÚÂÊÔ][a-zá-úç]|[A-ZÁÉÍÓÚÂÊÔ]\s(?:[a-zá-ú]{2,}|[A-ZÁÉÍÓÚ][a-zá-ú]|[A-Z]{2,}\s)|\d+\)\s')
        if 0 < barra < 40:
            for m in re.finditer(r'\s+', corpo):
                if m.start() <= barra + 2 or m.start() > 110:
                    continue
                antes = corpo[:m.start()]
                if antes.endswith(('-', '—', '–')):
                    continue  # "SEFAZ RS - Auditor CAGE": o cargo ainda faz parte da origem
                if antes.count('(') == antes.count(')') and abre_frase.match(corpo, m.end()):
                    return antes.strip(), corpo[m.end():]
        if len(corpo) < 90:
            return corpo.strip(), ''
        return '', corpo
    return '', resto


def questoes_parte1(linhas):
    blocos, atual = [], None
    for l in linhas:
        m = CABECALHO.match(l.strip())
        if m:
            atual = {'id': m.group(1), 'resto': m.group(2), 'linhas': []}
            blocos.append(atual)
        elif atual is not None:
            atual['linhas'].append(l.strip())
    saida = []
    for b in blocos:
        origem, inicio = origem_e_enunciado(b['resto'])
        texto = ' '.join([inicio] + b['linhas'])
        resto_cargo = re.match(r'^([^.()]{1,40}?)\s+\d{1,2}\)\s+', texto.strip())
        if resto_cargo and origem:  # "Controle Externo 12) A Reforma...": o fim do cargo ficou no enunciado
            origem = f"{origem} {resto_cargo.group(1)}"
            texto = texto.strip()[resto_cargo.end():]
        texto = re.sub(r'^\d{1,2}\)\s+', '', texto.strip())  # "16) Considere..." (numeração da bateria)
        texto = re.sub(r'^Retomada\s+\d+\s+', '', texto)  # C04: "Retomada 01 ..."
        ce = re.search(r'\(\s*\)\s*Certo|\bCerto\s+Errado\b|\sCerto\s*$|\sCerto\s', texto)
        enunciado, alts = separar_alternativas(texto)
        if not alts and ce:
            enunciado = texto[:ce.start()].strip()
        saida.append({
            'id': b['id'],
            'origem': origem,
            'enunciado': re.sub(r'\s{2,}', ' ', enunciado),
            'alternativas': alts,
            'tipo': 'ME' if alts else 'CE',
        })
    return saida


def gabaritos_e_comentarios(linhas_p2):
    texto = '\n'.join(linhas_p2)
    marcas = list(DIAG.finditer(texto))
    res = {}
    for i, m in enumerate(marcas):
        fim = marcas[i + 1].start() if i + 1 < len(marcas) else len(texto)
        bloco = texto[m.start():fim]
        corte = FIM_PARTE2.search(bloco)
        if corte:
            bloco = bloco[:corte.start()]
        g = GAB.search(bloco[:600])
        res.setdefault(m.group(1).lstrip('0') or '0', {
            'gabarito': g.group(1).upper() if g else None,
            'comentario': bloco.strip(),
        })
    return res


def blocos_posicionais(linhas_p2):
    """Para cadernos sem id na Parte 2 (C07): um bloco por "1. Diagnóstico:", na ordem da Parte 1."""
    texto = '\n'.join(linhas_p2)
    corte = FIM_PARTE2.search(texto)
    if corte:
        texto = texto[:corte.start()]
    pos = [m.start() for m in re.finditer(r'^1\. Diagn.stico:', texto, re.M)]
    return [texto[p:(pos[i + 1] if i + 1 < len(pos) else len(texto))].strip() for i, p in enumerate(pos)]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('arquivo')
    ap.add_argument('--secao', required=True)
    ap.add_argument('--gabaritos', default='', help='correções manuais: id=LETRA,id=CERTO')
    ap.add_argument('--posicional', action='store_true', help='comentários pela ordem (Parte 2 sem ids)')
    a = ap.parse_args()

    bruto = open(a.arquivo, encoding='utf-8', errors='replace').read().replace('�', '').splitlines()
    ini = next((i for i, l in enumerate(bruto) if re.search(r'PARTE 1', l)), 0)
    fim = next((i for i, l in enumerate(bruto[ini + 1:], ini + 1) if 'FIM DA PARTE 1' in l), None)
    if fim is None:
        fim = next((i for i, l in enumerate(bruto[ini + 1:], ini + 1) if FIM_PARTE1.search(l) and not re.search(r'PARTE 1', l)), len(bruto))
    qs = questoes_parte1(limpar(bruto[ini:fim]))
    parte2 = [l for l in bruto[fim:] if l.strip()]

    if a.posicional:
        blocos = blocos_posicionais(parte2)
        info = {q['id'].lstrip('0'): {'gabarito': None, 'comentario': blocos[i] if i < len(blocos) else ''} for i, q in enumerate(qs)}
    else:
        info = gabaritos_e_comentarios(parte2)
    manuais = dict(p.split('=') for p in a.gabaritos.split(',') if '=' in p)

    for q in qs:
        k = q['id'].lstrip('0') or '0'
        d = info.get(k, {})
        gab = manuais.get(q['id']) or d.get('gabarito')
        if gab in ('CERTO', 'C') and q['tipo'] == 'CE':
            gab = 'C'
        elif gab in ('ERRADO', 'E') and q['tipo'] == 'CE':
            gab = 'E'
        q['gabarito'] = gab
        # Sem gabarito, ou gabarito de letra sem a alternativa (o PDF perdeu texto): não dá para refazer.
        letras = [x['letra'] for x in q['alternativas']]
        q['incompleta'] = not gab or (q['tipo'] == 'CE' and gab not in ('C', 'E')) or (q['tipo'] == 'ME' and gab not in letras)
        q['comentario'] = '\n'.join(limpar(d.get('comentario', '').splitlines()))
        q['chave'] = (f"{a.secao}-q{q['id']}" if len(q['id']) <= 3 else f"q{q['id']}")
        if len(q['id']) <= 3:
            q['id'] = ''  # numeração interna do caderno, não é id do TEC

    sys.stdout.reconfigure(encoding='utf-8')
    json.dump({'secao': a.secao, 'questoes': qs}, sys.stdout, ensure_ascii=False, indent=1)


if __name__ == '__main__':
    main()
