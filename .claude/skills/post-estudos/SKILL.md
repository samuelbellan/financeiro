---
name: post-estudos
description: Gera as mídias do Instagram (@samubellan) com as horas de estudo do app e as questões da Guruja. Use com "semanal" (carrossel da última meta concluída da Guruja; "--parcial" para a meta em andamento) ou "diario [YYYY-MM-DD]" (Story de um dia; padrão ontem).
---

# /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]

Decisões de produto: `docs/sabatina-2026-11-08-projecao-e-midias.md`. Siga-as; não invente outro formato.

## Regras fixas
- Horas vêm do app (nuvem, só leitura) via `php artisan estudos:resumo`. Questões vêm do MCP da Guruja.
- **Nunca** coloque nas artes ou na legenda a média da turma, a diferença para a média, quantos alunos existem, nem o nome/links da Guruja.
- Tom positivo: "ponto alto" = disciplina com maior % de acerto (com pelo menos 10 questões, se houver); "foco" = a de menor %, escrita como próximo passo ("mais questões para subir dos X%"), nunca como fracasso.
- Comparação negativa com o período anterior não aparece (o template já esconde).
- Se a Guruja falhar ou devolver dados sem `disciplinas`: **não gere o semanal**; avise. O diário pode sair sem a Guruja (`"guruja": null`).

## Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)
1. `historico_de_metas` (Guruja) → escolha a última meta com `concluida: true` (ou, com `--parcial`, a que tem `esta_em_andamento: true`). Guarde `ordem`, `data_inicio`, `data_conclusao` (na parcial, use agora como fim) e a meta anterior (para comparação).
2. `meu_desempenho_por_disciplina` com `meta_de = meta_ate = ordem`. Some `questoes` e `acertos`.
3. Horas do app no mesmo intervalo. As datas da Guruja não têm fuso: trate como horário de Brasília (`-03:00`):
   ```bash
   php artisan estudos:resumo --inicio="<data_inicio>-03:00" --fim="<data_conclusao>-03:00" --comparar-inicio="<inicio meta anterior>-03:00" --comparar-fim="<data_inicio>-03:00" --saida=storage/app/midias/<AAAA-MM-DD>_meta-<ordem>/app.json
   ```
   `<AAAA-MM-DD>` = dia da conclusão (ou de hoje, na parcial).
4. Escreva `storage/app/midias/<pasta>/dados.json`:
   ```json
   {
     "tipo": "semanal",
     "handle": "@samubellan",
     "titulo": "Meta 59",
     "subtitulo": "25/10 – 02/11",
     "selo": "concluída",            // ou "em andamento" na parcial
     "app": { ...conteúdo de app.json... },
     "guruja": { "questoes": 163, "acertos": 121,
                 "disciplinas": [ { "nome": "<disciplina>", "questoes": 16, "acertos": 11, "percentual": 68.75 } ] },
     "destaques": { "ponto_alto": "...", "foco": "..." },
     "legenda": "texto do post com emojis e hashtags (#concursos #concurseiro #rotinadeestudos #estudos #foco)"
   }
   ```
   Use o nome da disciplina exatamente como a Guruja devolve: quando bate com o nome da matéria no app, a barra herda a cor da matéria.
5. `php artisan estudos:midia <pasta>` → gera `01-capa.png`, `02-materias.png`, `03-questoes.png`, `04-story-semanal.png` e `legenda.txt` (Edge headless).
6. Abra cada PNG (Read) e confira: nada cortado, números batendo com os dados. Ajuste textos longos dos destaques se estourarem.
7. Envie os PNGs ao usuário (SendUserFile) e cole a legenda no chat.

## Diário (Story 1080×1920)
1. Dia = argumento ou ontem (no fuso do app).
2. `php artisan estudos:resumo --dia=<dia> --saida=storage/app/midias/<dia>_diario/app.json`
3. Guruja (opcional): `meu_desempenho_por_disciplina` com `apenas_meta_atual: true` → total acumulado da meta atual (a Guruja não tem recorte por dia; o card diz "acumulado").
4. `dados.json` com `"tipo": "diario"`, `"titulo"` (ex.: "Sábado, 07/11"), `"subtitulo"` (ex.: "Meta 60 em andamento"), `app`, `guruja` (ou null) e `legenda`.
5. `php artisan estudos:midia <dia>_diario`, confira o PNG e envie.

## Problemas comuns
- `CLOUD_DATABASE_URL não configurada`: o `.env` local precisa da conexão `pgsql_cloud`.
- PNG não gerado: passe `--navegador="C:\Program Files\Google\Chrome\Application\chrome.exe"`.
- Fonte diferente na imagem: o template usa Inter do Google Fonts; sem internet cai para Segoe UI.
