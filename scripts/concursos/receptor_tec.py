"""
Receptor local dos cadernos do TEC Concursos. Recebe do navegador um JSON por caderno e grava em
<pasta>/<id>-<nome>.json. Só biblioteca padrão do Python 3.

Dois modos:

  * Uma matéria (extrair_tec.js), por exemplo no serviço gravando no Google Drive:
        python -I receptor_tec.py --saida "G:\\Meu Drive\\TEC\\ctbga"

  * Pasta inteira (vigiar_tec.js, o "vigia" dos cadernos novos), no PC de casa:
        python -I scripts/concursos/receptor_tec.py --raiz storage/concursos/tec --importar
    Cada caderno vai para a subpasta da matéria (o script do navegador escolhe pela sigla do nome) e
    /feitos olha todas as subpastas. Com --importar, ao fim da rodada ele roda
    `php artisan concursos:tec-novos`, que importa as questões e escreve o PENDENTES.md.
    Abra http://127.0.0.1:8765/ para pegar o favorito "TEC: cadernos novos".

Rotas:
    GET  /                    -> página com o favorito
    GET  /vigiar.js           -> o script do vigia (o favorito carrega daqui)
    GET  /feitos              -> ids dos cadernos já gravados (o navegador pula esses)
    POST /salvar?nome=...[&pasta=dpen] -> grava o JSON do caderno
    POST /fim                 -> (com --importar) roda o concursos:tec-novos e devolve a saída
Os POST só são aceitos vindos da página do TEC.
"""
import argparse
import json
import os
import re
import subprocess
import tempfile
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import parse_qs, urlparse

ORIGEM = 'https://www.tecconcursos.com.br'
AQUI = os.path.dirname(os.path.abspath(__file__))
REPO = os.path.abspath(os.path.join(AQUI, '..', '..'))

PAGINA = """<!doctype html><meta charset="utf-8"><title>TEC: cadernos novos</title>
<style>body{font:16px system-ui;max-width:640px;margin:40px auto;padding:0 16px;line-height:1.5}
a.fav{display:inline-block;padding:10px 16px;background:#0a7;color:#fff;border-radius:8px;text-decoration:none;font-weight:600}</style>
<h1>Vigia dos cadernos do TEC</h1>
<p>Arraste o botão abaixo para a barra de favoritos do Chrome (Ctrl+Shift+B mostra a barra):</p>
<p><a class="fav" href="__FAV__">TEC: cadernos novos</a></p>
<p>Depois, logado no TEC, clique no favorito em qualquer página do site. Ele abre uma aba de dados,
procura os cadernos finalizados da pasta que ainda não estão aqui, baixa só esses e, no fim,
importa no app (se o receptor estiver com <code>--importar</code>).</p>
<p>Deixe este receptor aberto enquanto o favorito roda.</p>"""

# Abre a aba de dados do TEC (página sem os scripts do site, que se recarrega nos bloqueios 405/429)
# e roda nela o vigiar_tec.js servido por este receptor.
FAVORITO = ("javascript:(()=>{const R='http://127.0.0.1:__PORTA__',U='/api/pastas-cadernos/__PASTA__/subpastas/itens';"
            "if(!/tecconcursos\\.com\\.br$/.test(location.hostname)){alert('Abra o site do TEC e clique de novo.');return}"
            "const w=location.pathname.startsWith('/api/')?window:window.open(U,'tec-vigia');"
            "fetch(R+'/vigiar.js').then(r=>r.text()).then(t=>{const i=setInterval(()=>{try{"
            "if(w.location.pathname.startsWith('/api/')&&w.document.readyState==='complete'){clearInterval(i);w.eval(t)}"
            "}catch(e){}},300)}).catch(()=>alert('O receptor do TEC não respondeu em '+R+'. Ele está aberto?'))})()")


def main():
    ap = argparse.ArgumentParser()
    g = ap.add_mutually_exclusive_group(required=True)
    g.add_argument('--saida', help='uma pasta só, onde gravar os JSON (pode ser a do Google Drive)')
    g.add_argument('--raiz', help='pasta com uma subpasta por matéria (storage/concursos/tec)')
    ap.add_argument('--porta', type=int, default=8765)
    ap.add_argument('--pasta-tec', type=int, default=5145567, help='id da pasta do TEC que o vigia lê')
    ap.add_argument('--importar', action='store_true', help='no fim da rodada, roda php artisan concursos:tec-novos')
    a = ap.parse_args()
    base = os.path.abspath(a.saida or a.raiz)
    os.makedirs(base, exist_ok=True)

    def pastas():
        if a.saida:
            return [base]
        return [os.path.join(base, d) for d in os.listdir(base) if os.path.isdir(os.path.join(base, d))]

    class H(BaseHTTPRequestHandler):
        def cors(self):
            self.send_header('Access-Control-Allow-Origin', ORIGEM)
            self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            self.send_header('Access-Control-Allow-Headers', 'Content-Type')
            self.send_header('Access-Control-Allow-Private-Network', 'true')

        def responder(self, status, corpo=b'', tipo='text/plain; charset=utf-8'):
            self.send_response(status)
            self.cors()
            self.send_header('Content-Type', tipo)
            self.send_header('Cache-Control', 'no-store')
            self.end_headers()
            self.wfile.write(corpo)

        def do_OPTIONS(self):
            self.responder(204)

        def do_GET(self):
            p = urlparse(self.path).path
            if p == '/feitos':
                ids = sorted({n.split('-', 1)[0] for d in pastas() for n in os.listdir(d) if n.endswith('.json')})
                return self.responder(200, json.dumps(ids).encode(), 'application/json')
            if p == '/vigiar.js':
                with open(os.path.join(AQUI, 'vigiar_tec.js'), 'rb') as f:
                    return self.responder(200, f.read(), 'text/javascript; charset=utf-8')
            if p == '/':
                fav = FAVORITO.replace('__PORTA__', str(a.porta)).replace('__PASTA__', str(a.pasta_tec))
                html = PAGINA.replace('__FAV__', fav.replace('&', '&amp;').replace('"', '&quot;'))
                return self.responder(200, html.encode(), 'text/html; charset=utf-8')
            self.responder(404)

        def do_POST(self):
            if self.headers.get('Origin') != ORIGEM:
                return self.responder(403, b'origem nao permitida')
            u = urlparse(self.path)
            if u.path == '/fim':
                return self.fim()
            q = parse_qs(u.query)
            nome = q.get('nome', [''])[0]
            pasta = q.get('pasta', [''])[0]
            if u.path != '/salvar' or not re.fullmatch(r'[A-Za-z0-9_.-]{1,100}', nome):
                return self.responder(400, b'nome invalido')
            if a.raiz and not re.fullmatch(r'[a-z0-9-]{1,40}', pasta):
                return self.responder(400, b'pasta invalida (modo --raiz)')
            destino = os.path.join(base, pasta) if a.raiz else base
            try:
                dados = json.loads(self.rfile.read(int(self.headers.get('Content-Length', 0))))
                if not dados.get('questoes'):
                    return self.responder(400, b'caderno vazio')
            except (ValueError, AttributeError):
                return self.responder(400, b'json invalido')
            os.makedirs(destino, exist_ok=True)
            # Grava num temporário e renomeia: o Drive nunca sincroniza um arquivo pela metade.
            fd, temp = tempfile.mkstemp(dir=destino, suffix='.tmp')
            with os.fdopen(fd, 'w', encoding='utf-8') as f:
                json.dump(dados, f, ensure_ascii=False, indent=1)
            os.replace(temp, os.path.join(destino, nome + '.json'))
            erradas = sum(1 for q in dados['questoes'] if q.get('errou'))
            onde = f'{pasta}/' if a.raiz else ''
            print(f'salvo {onde}{nome}: {len(dados["questoes"])} questões, {erradas} erradas', flush=True)
            self.responder(200, b'ok')

        def fim(self):
            if not a.importar:
                return self.responder(200, 'Receptor sem --importar: rode php artisan concursos:tec-novos.'.encode())
            print('importando: php artisan concursos:tec-novos', flush=True)
            try:
                p = subprocess.run(['php', 'artisan', 'concursos:tec-novos'], cwd=REPO, capture_output=True,
                                   text=True, encoding='utf-8', errors='replace', timeout=900)
                saida = re.sub(r'\x1b\[[0-9;]*m', '', (p.stdout + p.stderr)).strip()
            except (OSError, subprocess.TimeoutExpired) as e:
                saida = f'Falhou ao rodar o php artisan: {e}'
            print(saida, flush=True)
            self.responder(200, saida.encode())

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
    print(f'Receptor do TEC em http://127.0.0.1:{a.porta} gravando em {base}' + (' (uma subpasta por matéria)' if a.raiz else ''), flush=True)
    if a.raiz:
        print(f'Favorito do vigia: abra http://127.0.0.1:{a.porta}/', flush=True)
    print('Deixe esta janela aberta; Ctrl+C para parar.', flush=True)
    try:
        servidor.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == '__main__':
    main()
