"""
Converte os cadernos baixados do TEC Concursos (um JSON por caderno, com todas as questões, a
alternativa marcada e o gabarito) para os dois arquivos que o Material de Resumo importa:

  - material (seções, uma por número de caderno: C01, C02...), sem pontos: o resumo é escrito depois;
  - questões erradas (formato de MaterialQuestoesImportador), uma vez cada, na primeira seção em que
    apareceu.

Uso:
    python -I converter_tec.py storage/concursos/tec/ctbga --slug ctbga \
        --titulo "Contabilidade Geral e Avançada · Erros dos cadernos" --disciplina "Contabilidade Geral e Avançada" \
        --saida storage/concursos/materiais

Depois:
    php artisan concursos:importar-material storage/concursos/materiais/ctbga.json --questoes=storage/concursos/materiais/ctbga-questoes.json

O conteúdo é do TEC: fica só em storage/concursos (fora do git). Depende só da biblioteca padrão.
"""
import argparse
import glob
import json
import os
import re
import sys
from collections import Counter, OrderedDict
from html import unescape
from html.parser import HTMLParser

BLOCO = {'p', 'div', 'li', 'tr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'ul', 'ol', 'blockquote'}


class Texto(HTMLParser):
    """HTML do TEC para texto corrido, mantendo parágrafos, itens e linhas de tabela."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.partes = []
        self.figuras = 0

    def handle_starttag(self, tag, attrs):
        if tag == 'br':
            self.partes.append('\n')
        elif tag in BLOCO:
            self.partes.append('\n')
            if tag == 'li':
                self.partes.append('• ')
        elif tag in ('td', 'th'):
            self.partes.append(' | ')
        elif tag == 'img':
            self.figuras += 1
            self.partes.append(' [figura] ')

    def handle_endtag(self, tag):
        if tag in BLOCO:
            self.partes.append('\n')

    def handle_data(self, data):
        self.partes.append(data)

    def texto(self):
        t = ''.join(self.partes).replace('\xa0', ' ')
        t = re.sub(r'[ \t]+', ' ', t)
        t = re.sub(r' *\n *', '\n', t)
        t = re.sub(r'\n\| ', '\n', t)
        t = re.sub(r'\n{3,}', '\n\n', t)
        return t.strip()


def html_texto(html):
    p = Texto()
    p.feed(html or '')
    p.close()
    return unescape(p.texto()), p.figuras


def numero_caderno(nome):
    m = re.match(r'C(\d{2})', nome)
    return m.group(1) if m else None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('pasta')
    ap.add_argument('--slug', required=True)
    ap.add_argument('--titulo', required=True)
    ap.add_argument('--disciplina', required=True)
    ap.add_argument('--saida', required=True)
    a = ap.parse_args()

    cadernos = []
    for arq in glob.glob(os.path.join(a.pasta, '*.json')):
        d = json.load(open(arq, encoding='utf-8'))
        num = numero_caderno(d['caderno'])
        if num:
            cadernos.append((num, d))
    cadernos.sort(key=lambda x: (x[0], 'Teste' in x[1]['caderno'], int(x[1]['cadernoId'])))

    secoes = OrderedDict()
    vistas = set()
    for num, d in cadernos:
        s = secoes.setdefault(num, {'cadernos': [], 'total': 0, 'erros': 0, 'assuntos': Counter(), 'questoes': []})
        s['cadernos'].append(d['caderno'])
        for q in d['questoes']:
            if not q['resolvida'] or q.get('anulada'):
                continue
            s['total'] += 1
            for ass in (q.get('assunto') or '').split(', ')[:1]:
                if ass:
                    s['assuntos'][ass] += 1
            if not q['errou']:
                continue
            s['erros'] += 1
            if q['id'] in vistas:
                continue
            vistas.add(q['id'])
            s['questoes'].append(q)

    material = {'slug': a.slug, 'titulo': a.titulo, 'disciplina': a.disciplina,
                'descricao': 'Questões que você errou nos cadernos do TEC (Guruja), para refazer. O resumo de cada assunto entra aos poucos.',
                'secoes': []}
    saida_q = {'material': a.slug, 'secoes': []}
    resumo = []
    for num, s in secoes.items():
        if s['total'] == 0:
            continue
        assunto = s['assuntos'].most_common(1)[0][0] if s['assuntos'] else ''
        acertos = s['total'] - s['erros']
        material['secoes'].append({
            'chave': f'c{num}',
            'titulo': f'C{num} · {assunto}' if assunto else f'C{num}',
            'origem': f"{len(s['cadernos'])} caderno(s) · {s['total']} questões · {acertos} acertos ({acertos / s['total']:.0%}) · {s['erros']} erros",
            'pontos': [],
        })
        qs = []
        for q in s['questoes']:
            enun, fig = html_texto(q['enunciado'])
            alts = [html_texto(x)[0] for x in q['alternativas']]
            ce = q['tipo'] == 'CERTO_ERRADO'
            if ce:
                gab, marcada = ('C' if q['correta'] == 1 else 'E'), ('C' if q['marcada'] == 1 else 'E')
            else:
                gab, marcada = chr(64 + q['correta']), chr(64 + q['marcada'])
            if fig:
                enun += f"\n\n(Esta questão tem {'figura' if fig == 1 else str(fig) + ' figuras'}: veja no TEC, #{q['id']}.)"
            origem = ' · '.join(str(x) for x in [q.get('banca'), q.get('ano'), q.get('orgao'), q.get('cargo')] if x)
            com = [f"No TEC você marcou {marcada}; gabarito {gab}." + (' Questão marcada como desatualizada no TEC.' if q.get('desatualizada') else '')]
            if q.get('assunto'):
                com.append(f"Assunto: {q['assunto']}")
            if q.get('comentario'):
                c = q['comentario']
                com.append(f"Comentário do prof. {c.get('professor') or ''}".strip() + ':\n' + html_texto(c.get('texto'))[0])
            qs.append({
                'chave': f"q{q['id']}",
                'id': str(q['id']),
                'origem': origem,
                'tipo': 'CE' if ce else 'ME',
                'enunciado': enun,
                'alternativas': [] if ce else [{'letra': chr(65 + i), 'texto': t} for i, t in enumerate(alts)],
                'gabarito': gab,
                'comentario': '\n\n'.join(com),
                'incompleta': not enun or (not ce and len(alts) < 2),
            })
        saida_q['secoes'].append({'secao': f'c{num}', 'questoes': qs})
        resumo.append(f"C{num}: {s['total']} resolvidas, {s['erros']} erros, {len(qs)} questões novas para refazer")

    os.makedirs(a.saida, exist_ok=True)
    for nome, dados in ((f'{a.slug}.json', material), (f'{a.slug}-questoes.json', saida_q)):
        with open(os.path.join(a.saida, nome), 'w', encoding='utf-8') as f:
            json.dump(dados, f, ensure_ascii=False, indent=1)
    sys.stdout.reconfigure(encoding='utf-8')
    print('\n'.join(resumo))
    print(f"Total: {sum(len(s['questoes']) for s in saida_q['secoes'])} questões erradas (sem repetir)")


if __name__ == '__main__':
    main()
