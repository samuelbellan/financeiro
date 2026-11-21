"""Valida arquivos JSON do contrato de concursos contra os schemas desta pasta.

Uso:  python validar.py arquivo1.json [arquivo2.json ...]
      python validar.py exemplos/*.json
O schema é escolhido pelo nome do arquivo (edital-, -contexto-, -ledger-, -resolucoes-, -mapa-assunto).
"""
import json, sys, pathlib
from jsonschema import Draft202012Validator
from referencing import Registry, Resource

BASE = pathlib.Path(__file__).resolve().parent
SCHEMAS = {p.name: json.loads(p.read_text(encoding="utf-8")) for p in BASE.glob("*.schema.json")}
REG = Registry().with_resources([(s["$id"], Resource.from_contents(s)) for s in SCHEMAS.values()])
PARES = [("edital", "edital"), ("contexto", "contexto"), ("ledger", "ledger"),
         ("resolucoes", "resolucoes"), ("mapa-assunto", "mapa-assunto")]

def schema_para(nome: str):
    for chave, alvo in PARES:
        if chave in nome:
            return alvo
    return None

def main(arquivos):
    falhou = False
    for caminho in arquivos:
        p = pathlib.Path(caminho)
        alvo = schema_para(p.name)
        if not alvo:
            print(f"?    {p.name}: nome não indica o tipo (edital/contexto/ledger/resolucoes/mapa-assunto)")
            falhou = True
            continue
        try:
            dados = json.loads(p.read_text(encoding="utf-8"))
        except json.JSONDecodeError as e:
            print(f"ERRO {p.name}: JSON inválido na linha {e.lineno}, coluna {e.colno}: {e.msg}")
            falhou = True
            continue
        erros = sorted(Draft202012Validator(SCHEMAS[f"{alvo}.schema.json"], registry=REG).iter_errors(dados),
                       key=lambda e: list(e.absolute_path))
        if not erros:
            print(f"OK   {p.name} ({alvo})")
            continue
        falhou = True
        print(f"ERRO {p.name} ({alvo}): {len(erros)} problema(s)")
        for e in erros[:20]:
            onde = "/".join(str(x) for x in e.absolute_path) or "(raiz)"
            print(f"     {onde}: {e.message[:300]}")
    return 1 if falhou else 0

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(2)
    sys.exit(main(sys.argv[1:]))
