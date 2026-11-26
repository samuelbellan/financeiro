"""
Receptor local dos cadernos do TEC Concursos. Recebe do navegador (extrair_tec.js) um JSON por
caderno e grava em <saida>/<id>-<nome>.json. Serve para rodar em qualquer PC com Python 3 (só
biblioteca padrão), por exemplo no serviço gravando direto numa pasta do Google Drive:

    python -I receptor_tec.py --saida "G:\\Meu Drive\\TEC\\ctbga"

Depois, no PC de casa (onde ficam o app e o Postgres):

    python -I scripts/concursos/converter_tec.py "G:\\Meu Drive\\TEC\\ctbga" --slug ctbga ...

Rotas (só aceita chamadas da página do TEC):
    GET  /feitos            -> ids dos cadernos já gravados (o script do navegador pula esses)
    POST /salvar?nome=...   -> grava o JSON do caderno
"""
import argparse
import json
import os
import re
import tempfile
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qs, urlparse

ORIGEM = 'https://www.tecconcursos.com.br'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--saida', required=True, help='pasta onde gravar os JSON (pode ser a do Google Drive)')
    ap.add_argument('--porta', type=int, default=8765)
    a = ap.parse_args()
    saida = os.path.abspath(a.saida)
    os.makedirs(saida, exist_ok=True)

    class H(BaseHTTPRequestHandler):
        def cors(self):
            self.send_header('Access-Control-Allow-Origin', ORIGEM)
            self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            self.send_header('Access-Control-Allow-Headers', 'Content-Type')
            self.send_header('Access-Control-Allow-Private-Network', 'true')

        def responder(self, status, corpo=b''):
            self.send_response(status)
            self.cors()
            self.end_headers()
            self.wfile.write(corpo)

        def do_OPTIONS(self):
            self.responder(204)

        def do_GET(self):
            if urlparse(self.path).path != '/feitos':
                return self.responder(404)
            ids = sorted({n.split('-', 1)[0] for n in os.listdir(saida) if n.endswith('.json')})
            self.responder(200, json.dumps(ids).encode())

        def do_POST(self):
            u = urlparse(self.path)
            nome = parse_qs(u.query).get('nome', [''])[0]
            if u.path != '/salvar' or not re.fullmatch(r'[A-Za-z0-9_.-]{1,100}', nome):
                return self.responder(400, b'nome invalido')
            try:
                dados = json.loads(self.rfile.read(int(self.headers.get('Content-Length', 0))))
                if not dados.get('questoes'):
                    return self.responder(400, b'caderno vazio')
            except (ValueError, AttributeError):
                return self.responder(400, b'json invalido')
            # Grava num temporário e renomeia: o Drive nunca sincroniza um arquivo pela metade.
            fd, temp = tempfile.mkstemp(dir=saida, suffix='.tmp')
            with os.fdopen(fd, 'w', encoding='utf-8') as f:
                json.dump(dados, f, ensure_ascii=False, indent=1)
            os.replace(temp, os.path.join(saida, nome + '.json'))
            erradas = sum(1 for q in dados['questoes'] if q.get('errou'))
            print(f'salvo {nome}: {len(dados["questoes"])} questões, {erradas} erradas', flush=True)
            self.responder(200, b'ok')

        def log_message(self, fmt, *args):
            pass

    class Servidor(HTTPServer):
        # No Windows o padrão (SO_REUSEADDR) deixa dois receptores na mesma porta e o antigo
        # continua atendendo: sem reuso, um segundo receptor falha logo avisando.
        allow_reuse_address = False

    try:
        servidor = Servidor(('127.0.0.1', a.porta), H)
    except OSError as e:
        raise SystemExit(f'Porta {a.porta} ocupada (outro receptor aberto?): {e}')
    print(f'Receptor do TEC em http://127.0.0.1:{a.porta} gravando em {saida}', flush=True)
    print('Deixe esta janela aberta; Ctrl+C para parar.', flush=True)
    try:
        servidor.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == '__main__':
    main()
