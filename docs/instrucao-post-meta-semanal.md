# Instrução: post da meta da semana (carrossel) sem IA

Objetivo: gerar dentro do app `/estudos`, sem IA, o carrossel do Instagram de uma meta
concluída da Guruja — o mesmo resultado que hoje a skill `post-meta-guruja` gera com o
Claude. Deve seguir o padrão já usado pelo gerador de Story diário
(`resources/js/estudos/story.js`, botão "Gerar Story"): desenho em `<canvas>` no
navegador, prévia e download em PNG.

Entregue ao agente (Antigravity) este arquivo inteiro como especificação.

## 1. Onde fica e como o usuário usa

- Botão **"Gerar post da meta"** no card da Guruja (aba Cronômetro) e na aba
  Estatísticas, ao lado de "Gerar Story do dia".
- Abre um diálogo (mesmo estilo do diálogo do Story) com:
  - seletor da meta (padrão: a última meta concluída; lista as concluídas do histórico,
    a mais recente primeiro);
  - prévia dos 7 slides em miniatura, rolagem horizontal;
  - botões **Baixar todos** (7 PNGs, `meta-62-1.png` … `meta-62-7.png`; um download por
    arquivo, sem zip) e **Copiar legenda**;
  - a legenda pronta num `<textarea>` só leitura.
- Se a meta escolhida estiver "em andamento" com 100% das atividades feitas, mostrar
  um aviso: "Finalize a meta no portal da Guruja para ela aparecer como concluída".

## 2. Dados (servidor)

Criar `GET /estudos/guruja/post-meta?ordem=N` (rota no grupo autenticado de
`routes/web.php`, ao lado das outras `/estudos/guruja/*`), que devolve um JSON pronto
para desenhar. Sem `ordem`, usa a última meta concluída.

### 2.1 Guruja (via `GurujaMcpService`, que já faz OAuth e `callMcp`)
1. `historico_de_metas` → lista de metas. Achar a de `ordem` N (ou a última com
   `concluida: true`). Guardar `target_id`, `status`, `data_inicio`,
   `data_conclusao`, `dias_atraso`, `vezes_adiada`.
2. `detalhe_de_uma_meta(target_id)` → `questoes_resolvidas`, `acertos`,
   `minutos_estudados`, `atividades[]`, `reforcos[]`.
3. Do histórico inteiro: `metas_concluidas` = quantas têm `concluida: true`;
   `metas_adiadas` = quantas têm `vezes_adiada > 0`.
4. Cachear a resposta de uma meta concluída (ela não muda mais): tabela nova
   `study_guruja_posts` (`user_id`, `ordem`, `payload` json, timestamps) ou
   `Cache::rememberForever("guruja-post:{$userId}:{$ordem}")`. Meta em andamento:
   não cachear.
5. Nunca expor metas futuras nem dados de outros alunos.

### 2.2 Horas do app (banco local, mesma lógica de `/api/estudos/periodo`)
- Janela: `data_inicio` até `data_conclusao` (horário de Brasília, sem fuso no dado
  da Guruja → tratar como `-03:00`). Fuso do app `America/Cuiaba`, dia vira à 1h.
- Por dia da janela: as sessões (início, fim, origem `timer`/`manual`/`importado`).
- Estatísticas **só com sessões de cronômetro e manuais** (importado fica de fora):
  - `media_dia_min` = total ÷ número de dias da janela;
  - `inicio_medio` = média do primeiro início de cronômetro de cada dia com estudo,
    "HH:MM";
  - `pct_antes_7h` = % do tempo estudado antes das 07:00;
  - `maior_foco_min` = maior sessão contínua;
  - `dias_com_estudo` / `dias_total`.

### 2.3 Cálculos (em PHP, no endpoint)
- `% acerto` = `acertos / questoes_resolvidas` da meta (totais da meta, não a soma das
  atividades), 1 casa decimal.
- Horas no formato "25h39"; abaixo de 1h, "39min".
- **Destaques**: as 3 atividades concluídas com `questoes > 0` e maior % de acerto
  (desempate: mais questões). Rótulo = título da atividade, cortado em ~38 caracteres
  com "…".
- **Horas por matéria**: somar `minutos` das atividades e dos reforços por
  `disciplina`; ordem decrescente. Se a soma divergir de `minutos_estudados`, manter a
  soma por matéria mesmo (a diferença são aprofundamentos).
- **Status**: `ontime` → "no prazo"; `anticipated` → "antecipada"; `late` →
  "com N dia(s) de atraso" (N = `dias_atraso`; singular/plural).
- Período: "09/11 a 15/11".

## 3. Slides (cliente, `resources/js/estudos/post-meta.js`)

Reaproveitar de `story.js`: `THEME`, fontes, `roundRectPath`/`fillRoundRect`,
`formatHoursMinutes`, `SUBJECT_ALIASES`/`canonicalSubjectName`. Se precisar, extrair
esses helpers para um módulo comum (`story-common.js`) e importar nos dois.

- Tamanho 1080×1350 (4:5), padding 96px.
- Identidade (igual ao Story): fundo `#0F1B2D`; cards `#18283F` raio 24px; texto
  `#F4F1EA`; secundário `#C9D2DE`; rodapé `#9AA7BA`; destaque `#F5B83D`; barra
  secundária `#6F86A6`. Títulos e números em Space Grotesk 700; corpo em Inter.
- Em todo slide: rótulo no topo em caixa alta, 28px, espaçamento entre letras, cor de
  destaque. Rodapé: "Samuel · Rumo a Auditor Fiscal" à esquerda e "n/7" à direita (na
  capa: "Arraste para o lado").
- Sem emoji nas imagens. Só estatísticas: nada de recomendações, pontos fracos ou
  frases motivacionais.
- Esperar `document.fonts.ready` antes de desenhar (como o Story).

Slides:
1. **Capa** — rótulo "META CONCLUÍDA"; título grande "Meta N concluída"; status (no
   prazo / antecipada / com X dias de atraso); período.
2. **Números** — grade 2×2 de cards: Atividades "X/Y"; Questões; Acerto "73,4%";
   Horas "25h39".
3. **Destaques** — "Melhores acertos"; até 3 barras horizontais com o % (100% = a
   barra cheia), o título da atividade e "17/25".
4. **Horas por matéria (1/2)** — título "N disciplinas na semana"; primeira metade das
   disciplinas (arredondar para cima), barras horizontais, 100% = a matéria com mais
   horas.
5. **Horas por matéria (2/2)** — título "As outras X"; a outra metade, **mesma escala**
   do slide 4. Se houver só 1–4 disciplinas, juntar tudo no slide 4, pular o 5 e
   renumerar o rodapé ("n/6").
6. **Horários de estudo** — título "X de Y dias com estudo"; uma faixa por dia da meta
   (rótulo "seg 10"), das 03h às 21h com marcações a cada 3h (se houver sessão depois
   das 21h: 03h–23h, marcações a cada 4h); blocos de cronômetro/manual na cor de
   destaque e importados em `#6F86A6` com a legenda "Importado (horário estimado)"
   (só se houver importados). Abaixo, 4 cards 2×2 com números em 56px: média por dia
   ("4h03", cor de destaque), horário médio de início ("03:37"), % do estudo antes das
   7h, maior foco seguido ("1h02").
7. **Fechamento** — "Próxima: Meta N+1"; 2 cards: metas concluídas e metas adiadas.

## 4. Legenda (template fixo, sem IA)

```
✅ Meta {N} concluída {status_curto}!
📚 {atividades_feitas}/{atividades_total} atividades · {horas} de estudo
🎯 {questoes} questões · {pct_acerto}% de acerto
⭐ Destaque: {destaque_1_titulo} ({destaque_1_pct}%)
Bora pra meta {N+1} 💪

#concursos #auditorfiscal #sefaz #iss #guruja #metabatida #concurseiro
```
- `status_curto`: "no prazo" / "antes do prazo" / "" (com atraso: omitir).
- Sem destaque (meta sem questões): omitir a linha ⭐.

## 5. Testes

- `tests/Feature`: endpoint com o MCP falso (como em `EstudosGurujaMcpTest.php`):
  meta concluída → JSON com totais, destaques, horas por matéria somando atividades +
  reforços, status e janela de horas; meta inexistente → 404; sem conexão com a
  Guruja → 409 com mensagem para conectar.
- `tests/js/estudos`: funções puras de `post-meta.js` (divisão das matérias entre os
  slides 4/5, formatação "25h39"/"39min", legenda, rodapé renumerado).

## 6. Fora do escopo

- Post de trajetória completa (pode vir depois com o mesmo módulo).
- Publicar no Instagram automaticamente.
