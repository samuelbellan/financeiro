---
name: sincronizar-guruja
description: Lê a meta atual da Guruja (MCP) e envia ao app de estudos (/estudos)
---

# Workflow: sincronizar-guruja

Execute sem mexer em código, sem commits e sem criar arquivos no repositório.

Pré-requisitos (configurados uma vez, ver `docs/antigravity-guruja.md`):
- Servidor MCP `guruja` ativo no Antigravity.
- Variável de ambiente `ESTUDOS_API_TOKEN` com o token da API do app de estudos.
  Nunca escreva o valor do token em arquivo, no chat ou na saída.

Passos:

1. Pelo MCP `guruja`, chame `historico_de_metas` e pegue a meta com
   `esta_em_andamento: true` (se não houver, pare e diga isso). Depois chame
   `detalhe_de_uma_meta` com o `target_id` dela.

2. Monte o JSON abaixo, copiando os valores exatamente como vieram da Guruja,
   omitindo campos nulos e SEM o campo `estimativas`:

   ```json
   {
     "lido_em": "<agora em UTC, ISO 8601, ex. 2026-11-18T22:43:39Z>",
     "meta": {
       "target_id": 0, "ordem": 0, "status": "", "data_inicio": "<valor da Guruja + \"-03:00\">",
       "dias_atraso": 0, "questoes_resolvidas": 0, "acertos": 0, "minutos_estudados": 0,
       "atividades": [{ "target_activity_id": 0, "codigo_atividade": "", "titulo": "", "disciplina": "",
                        "tipo": "", "concluida": false, "ignorada": false, "questoes": 0,
                        "acertos": 0, "minutos": 0, "relevancia": 0 }],
       "reforcos": [{ "target_activity_id": 0, "titulo": "", "disciplina": "", "instrucao": "",
                      "concluido": false, "ignorado": false, "questoes_resolvidas": 0,
                      "acertos": 0, "minutos": 0 }]
     }
   }
   ```

3. Salve o JSON num arquivo temporário FORA do repositório (pasta temp do sistema)
   e envie. No Windows (PowerShell):

   ```powershell
   curl.exe -sS -X POST https://financeiro-app-mrik.onrender.com/api/estudos/guruja/meta -H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: Bearer $env:ESTUDOS_API_TOKEN" --data "@$env:TEMP\guruja-meta.json"
   ```

   Em bash: troque `$env:ESTUDOS_API_TOKEN` por `$ESTUDOS_API_TOKEN` e o caminho por `/tmp/guruja-meta.json`.
   Se der timeout (o servidor do Render pode estar acordando), espere ~30 s e tente mais uma vez.
   Apague o arquivo temporário no fim.

4. Se a resposta trouxer `"precisa_estimativas": true`, abra as 3 metas concluídas mais
   recentes (`detalhe_de_uma_meta`) e acrescente ao JSON
   `"meta.estimativas": { "<tipo>": mediana dos minutos das atividades concluídas daquele tipo
   (arredondada, mínimo 1), "Reforço": mediana dos minutos dos reforços }`. Envie de novo.

5. Responda numa única linha: `Guruja → app: meta N, X/Y concluídas, HTTP código`.
   Em erro, inclua a mensagem (401 = token errado/expirado na variável `ESTUDOS_API_TOKEN`).
