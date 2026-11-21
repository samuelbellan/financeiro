# Sabatina 2026-11-21 — Módulo de estudo para concursos dirigido por índices

Módulo pós-edital para o curador (Samuel) e 2 amigos, em que o sistema faz o papel da Guruja: monta a meta do dia a partir de incidência (engenharia reversa) × domínio do aluno.

**Piloto:** Edital PROGEP/UFMS nº 152/2026, cargo **523** (Analista em Educação/Administração e Informação/Gestão Pública), aluno Rodrigo. Banca **Fapec**. Prova **17/01/2027**: 60 questões de múltipla escolha A–E, 4 h.

| Área | Questões | Pontos/questão | Pontos |
|---|---|---|---|
| Língua Portuguesa | 15 | 1 | 15 |
| Língua Inglesa | 5 | 1 | 5 |
| Legislação | 10 | 2 | 20 |
| Raciocínio Lógico | 10 | 2 | 20 |
| Conhecimentos Específicos | 20 | 2 | 40 |

---

## Decisões tomadas

| # | Decisão | Justificativa |
|---|---|---|
| 1 | Módulo **Laravel novo e relacional**. O `/concursos` (app estático) vira fonte de importação. | O motor precisa de consultas como "acerto por subtópico em 14 dias". Estado em blob chave→valor não permite isso. |
| 2 | **Postgres local**, numa conexão separada `concursos_pgsql`, acessado de fora pelo **Tailscale Funnel** (endereço fixo `*.ts.net`, grátis; o túnel rápido da Cloudflare mudava de URL a cada reinício e quebraria o login do Google e o offline). O app sobe como tarefa de inicialização do Windows. Flag `CONCURSOS_ENABLED` (desligada na nuvem). | São 100 mil questões (cerca de 500 MB), acima do Neon free. Com conexão separada, o financeiro continua no SQLite sem mudança. |
| 3 | **Pré-carregamento offline de 7 dias** de meta no PWA, com fila de respostas. | Protege os amigos quando o PC estiver desligado. |
| 4 | **3 usuários**: curador (`is_admin`) e alunos. O middleware `modulos` bloqueia o resto do sistema para os amigos. O curador vê os índices individuais, com o conhecimento deles. | Grupo fechado. Os amigos não devem acessar o financeiro. |
| 5 | **Taxonomia canônica**: Disciplina → Assunto TEC → Subtópico. O edital se liga a ela por mapeamento N:N. | Uma questão analisada uma vez serve qualquer edital, e o domínio vale entre concursos. |
| 6 | Árvore de 2 níveis criada na importação (o PDF não traz o caminho completo). O subtópico (3º nível) vem da engenharia reversa. | É a granularidade em que você monta os cadernos do TEC. |
| 7 | **Edital**: a IA extrai o conteúdo programático, o curador revisa e mapeia tópico → assunto canônico. | A IA erra a hierarquia em editais mal formatados. |
| 8 | **Questões**: PDF do TEC salvo pelo Chrome com **"Salvar como PDF"**, lido por um parser determinístico. A chave `(fonte, id_externo)` garante importação idempotente. **O gabarito vem sempre do PDF.** | O Microsoft Print to PDF gera PDF sem texto. Um parser sem IA não inventa gabarito. |
| 9 | **Engenharia reversa externa (Gemini)**, num ciclo `contexto.json` → `ledger` + `mapa` + `resolucoes` → validação. **A IA classifica e redige; o banco conta.** | O Gemini é mais barato, e LLM erra contagem. A lista fechada de subtópicos evita deriva entre lotes. |
| 10 | Baterias em 4 modos: Pareto, Equilibrado, Cauda e Adaptativo. | São estratégias de amostragem sobre a mesma base. |
| 11 | **Resoluções**: Gemini externo (arquivo separado). Se faltar, a API do sistema gera uma provisória. O validador rejeita resolução que contradiga o gabarito. | Resolução coerente com a ficha, e o aluno nunca fica sem resolução. |
| 12 | **Material TEC** em PDF, ligado aos subtópicos por **intervalo de páginas** (indicado pelo Gemini). | O PDF do TEC tem o nome do assunto no título, mas os marcadores variam de autor para autor. |
| 13 | **Lei seca** dividida em dispositivos com ID estável (`cc-2002/art-197/inc-I`, `nbc-tg-03/item-12`), com mapa de calor, diff de palavras alteradas e hash de vigência. Entram CC, CPC e as 18 leis do `/concursos`. | Leitura dirigida pelo que cai. |
| 14 | **Motor determinístico**, com orçamento de **minutos por dia da semana**, calibrado pelo tempo real do cronômetro. O motivo de cada atividade fica visível. | Explicável, testável e sem custo diário. |
| 15 | **IA no motor** só como "relatório do coach" semanal (API Gemini do sistema). O aluno aceita ou não, e o que aceitar vira parâmetro. | A IA analisa melhor do que planeja. |
| 16 | **Atividades**: Teoria, Questões, Lei seca, Revisão de erros (1/3/7/15/30 d), Flashcards (SRS) e Teste (cronometrado, sem feedback até o fim). | Ciclo: Não iniciado → Teoria → Prática → Em revisão → Dominado. |
| 17 | O aluno **declara** "Não vi / Vi parte / Vi tudo", sem teste diagnóstico. Rede de segurança: abaixo de 50% depois de 10 questões, o motor sugere a Teoria. | Pedido do usuário. |
| 18 | **Meta do dia** gerada 1× por dia e congelada. O aluno pode reordenar, adiar (alerta após 3×), trocar ou adicionar uma avulsa. | O motor mantém a direção sem engessar o aluno. |
| 19 | **Tela de resolução** no modelo do `/concursos`, com **certeza** e **motivo do erro** em cada tentativa. | Acerto no chute não conta como domínio. |
| 20 | **Timer `/estudos`**: `study_sessions.atividade_id`, `study_subjects.disciplina_id` e D-day automático. O tempo **nunca** conta como domínio. | Calibragem do tempo real, sem inflar índices. |
| 21 | **Backup**: arquivos de origem guardados para sempre em `storage/concursos/imports/`, `pg_dump` diário no Google Drive (14 diários e 8 semanais), Postgres e cloudflared como serviços do Windows, restauração testada todo mês. | Curadoria e progresso não podem ser recriados. |
| 22 | **Testes**: golden file do parser (`teste.pdf`), validador, motor e acesso. **Observabilidade**: painel de saúde e alertas pelo Telegram. | Protege o gabarito e avisa você como operador. |

## Suposições a validar

- O PC fica ligado na maior parte do tempo.
- O Gemini Hydrogen devolve 50 questões de ledger sem cortar a resposta. Se cortar, o lote cai para 25 ou é dividido em partes.
- O layout do PDF de caderno do TEC é estável entre cadernos.

## Riscos e pontos em aberto

- **A prova é em 57 dias.** A Fase 1 precisa ficar pronta em cerca de 2 semanas.
- **Banca Fapec com poucas questões no TEC.** A incidência usa todas as bancas como base, com um ajuste quando houver questões da Fapec.
- **A pontuação pesa diferente**: Legislação, Raciocínio Lógico e Específicos valem 2 pontos. O motor prioriza por **ponto**, não por questão.
- **Normas próprias da UFMS** (Estatuto, Regimento, Código de Ética, Manual de Atos) quase não têm questões no TEC. Dependem de lei seca e de questões inéditas do Gemini.
- **Bug existente:** `/sync/run` e `/sync/status` aceitam qualquer usuário logado. A correção entra na Fase 0.

## Fases

- **Fase 0**: correção de `/sync`, middleware `modulos`, conexão `concursos_pgsql`, flag e backup.
- **Fase 1 (MVP)**: edital, parser, contrato JSON e validador, curadoria de subtópicos, tela de resolução, motor (Teoria + Questões + Revisão de erros), tela da meta e timer.
- **Fase 2**: lei seca, material, flashcards, teste, baterias e offline.
- **Fase 3**: as 18 leis, coach, fallback de resolução por IA, alertas, restauração automática, scraping de comentários e migração do local para Postgres.

## Contrato de dados

Os JSON Schemas ficam em [`resources/schemas/concursos/`](../resources/schemas/concursos/), com exemplos e o guia para os prompts do Gemini.
