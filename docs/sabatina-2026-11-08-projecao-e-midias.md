# Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)

## Decisões

### Calculadora de projeção
1. Card **"Projeção" dentro de Estatísticas** (sem aba nova: a tabbar já tem 5 itens).
2. 100% no cliente, offline, reaproveitando `metrics.js` (`weekdayAverages`, `rangeStats`, `daysUntil`).
3. Ritmo padrão: **perfil por dia da semana dos últimos 30 dias**. Alternativas: média simples 7/30/90 dias e ritmo manual (h/dia + dias de folga). O resultado mostra as três datas lado a lado.
4. Dois modos: **"Quando chego?"** (alvo em horas → data) e **"Quanto preciso?"** (alvo + data → h/dia), com data a partir de um D-day em um toque.
5. Ponto de partida: total histórico do app (inclui sessões importadas), editável.
6. **Um alvo** salvo em `settings.alvo_horas` (sincroniza), com barra de progresso no card. Simulações avulsas não são salvas.
7. Bordas: ritmo zero → "sem previsão"; alvo ≤ atual → "atingido em DD/MM/AAAA" (data real em que cruzou); manual com todos os dias de folga → bloqueado; respeita `virada_hora` e fuso.

### Gerador de mídias
8. Gerado **sob demanda pelo Claude** (skill `/post-estudos semanal|diario`): o MCP da Guruja só existe no Claude.
9. **Semanal = meta da Guruja** (metas ~7 dias, não alinhadas ao calendário; o conector só recorta por meta). Horas do app recortadas por `data_inicio`→`data_conclusao` da meta.
10. **Diário = só dados do app**; questões aparecem como acumulado da meta atual.
11. Formatos: diário = Story 1080×1920; semanal = carrossel 1080×1350 com 3 slides (capa / horas por matéria e tipo / questões e acertos) + Story opcional de divulgação.
12. Entra: horas + comparação com o período anterior, top matérias, tipos, sequência de dias, D-day, questões, % de acerto geral e por disciplina, "ponto alto" e "foco da próxima semana" (tom positivo). Não entra: média da turma nem diferença para ela; nome/links da Guruja.
13. Pipeline: `php artisan estudos:resumo` (lê a nuvem, só leitura) → JSON; templates Blade versionados; rota local; captura PNG; arquivos em `storage/app/midias/AAAA-MM-DD_meta-NN/` + legenda `.txt`.
14. Visual: fundo escuro, números grandes, cores das matérias do app, **@samubellan** no rodapé, sem avatar no MVP.
15. Semanal usa a **última meta concluída**; `--parcial` gera a meta em andamento. Guruja fora → semanal não é gerado; diário funciona.

## Suposições
- Nome do concurso só aparece se estiver no rótulo do D-day.
- Um alvo só no MVP.
- Captura em resolução exata pelo navegador embutido; senão, Browsershot/Puppeteer local.
- `estudos:resumo` usa a conexão `pgsql_cloud` do `estudos:backup`.

## Riscos
- Divergência entre o `estudos:resumo` (PHP) e o `metrics.js` → testes com casos reais.
- Mudança no formato das respostas da Guruja → a skill valida os campos e para com erro claro.
- Avatar elaborado fica para a fase 2.
