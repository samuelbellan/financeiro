# Material por matéria: Resumo grifado + área de Questões

Sabatina de 2026-11-26. Hoje há dois cards de Direito Tributário (`dtrib`, bateria Expert, e `dtribt`, cadernos TEC) e cada card mistura teoria com os capítulos "por caderno" dos erros.

## Decisões

1. **1 card por matéria, com duas áreas: Resumo e Questões.** Misturar teoria com o diário de erros no mesmo índice é o que confunde hoje.
2. **O grifo é por trecho**, colorido pela **última** tentativa das questões ligadas: vermelho = errou, amarelo = errou e depois acertou, verde = só acertou. A intensidade mostra quantas questões caem ali, e clicar no grifo abre as questões. Assim se vê onde estudar e o que cai.
3. **A ligação questão → trecho é manual (opção A)**, feita por um agente externo (Antigravity) com `storage/concursos/materiais/PROMPT-grifos-antigravity.md`. O resultado vai para `storage/concursos/grifos/<materia>/*.jsonl` e é conferido por `scripts/concursos/validar_grifos.py`, com um agente por matéria.
4. **Os pontos (armadilha, regra, macete, base legal) vão para a área de Questões.** O status deixa de ser manual e passa a ser calculado pelas tentativas das questões do ponto, porque hoje ninguém o atualiza.
5. **Os dois Tributários viram um só: fica o `dtrib` (id 1)**, que já tem a teoria dos 6 módulos. Questões e pontos do `dtribt` passam para ele, cada ponto com a origem como etiqueta. As 26 questões repetidas viram uma só e as tentativas são somadas. Os pontos não são fundidos agora. O `dtribt` é apagado depois de um backup em JSON.
6. **Área de Questões:**
   - "Fila de hoje": repetição espaçada com 3 caixas. Errou → caixa 1 (volta amanhã). Acertou → sobe de caixa (7 dias, depois 30, depois sai da fila). Errou na revisão → volta à caixa 1. Acerto marcado como "chute" não sobe de caixa. As certas do TEC não entram na fila.
   - "Explorar": todas as questões, com filtros por capítulo, caderno, banca, resultado e caixa. Errar uma questão aqui a coloca na fila.
   - Ao responder aparecem o gabarito, o cartão do ponto e um link "ver no resumo".
7. **Entrega em duas fases.**
   - **Fase 1:** fusão dos Tributários, importação das certas dos JSON brutos, área de Questões, status calculado, livro só com teoria (os capítulos "por caderno" saem do índice) e card novo. Vale para todas as matérias.
   - **Fase 2:** importador dos `.jsonl` para grifos por trecho com cor e intensidade. Quando uma matéria recebe os grifos novos, os antigos dela (verde/rosa/azul por alternativa) são apagados.

## Suposições a validar
- Refazer uma questão no app conta como "última tentativa" e muda a cor do grifo.
- Os `.md` dos módulos são a teoria definitiva. Se um deles for reescrito, os trechos ligados quebram (o validador acusa).
- Os JSON brutos em `storage/concursos/tec/<sigla>/` têm todas as questões de cada caderno, com `marcada`, `correta` e `errou`.

## Riscos e pontos em aberto
- **Qualidade do agente externo:** o validador confere se o trecho existe, mas não se ele decide a questão. Amostrar de 20 a 30 linhas da primeira matéria antes de importar.
- **"Não coberto":** a lista devolvida pelo agente indica onde completar o resumo.
- **Auditoria e Português** não têm teoria, então ficam só com a área de Questões.
- **Pontos duplicados no Tributário:** a fusão fica para depois.

## Fora deste plano
- Em 2026-11-26 a prova da UFMS (cargo 523) foi desvinculada do Samuel e ficou só para o Rodrigo. Backup em `storage/concursos/backup-ufms-samuel-2026-11-26.json`.
