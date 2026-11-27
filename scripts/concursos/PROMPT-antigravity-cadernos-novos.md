# Cadernos novos do TEC → resumo e grifos (prompt para o Antigravity)

## Antes do agente (sem IA, sem tokens)

1. Deixe o receptor aberto, numa janela de terminal na pasta do projeto:
   ```
   python -I scripts/concursos/receptor_tec.py --raiz storage/concursos/tec --importar
   ```
2. Na primeira vez, abra http://127.0.0.1:8765/ e arraste o botão **TEC: cadernos novos** para a barra de favoritos.
3. Logado no TEC, clique no favorito. Ele abre uma aba de dados, lista a pasta 5145567 inteira, baixa só os
   cadernos **finalizados** que ainda não estão em `storage/concursos/tec/<matéria>/` e, no fim, roda
   `php artisan concursos:tec-novos`. Esse comando importa as questões (certas e erradas) no material da matéria
   e escreve `storage/concursos/tec/PENDENTES.md`.
4. Se aparecer pendência, abra o Antigravity na pasta do projeto e cole o prompt abaixo.

Sem o receptor com `--importar`, rode `php artisan concursos:tec-novos` à mão depois do favorito.
Matérias sem material no app (TI) são ignoradas. A tabela sigla → matéria fica em `scripts/concursos/vigiar_tec.js`
e `config/concursos.php` (`tec.subpastas`).

---

## Prompt

Você trabalha no repositório `C:\Users\SaMuB\Documents\financeiro` (Laravel). Chegaram cadernos novos do TEC
Concursos, já importados no app. Falta escrever o **resumo dos erros** (pontos) e ligar as questões ao
**resumo teórico** (grifos). Tudo o que você precisa está nos arquivos abaixo; não acesse o site do TEC.

### O que pode e o que não pode

- Pode criar e editar arquivos **só** em `storage/concursos/materiais/<slug>-partes/` e `storage/concursos/grifos/<slug>/`.
- Pode rodar **só** estes comandos:
  `php artisan concursos:tec-novos --pendencias`, `php artisan concursos:importar-pontos …`,
  `php artisan concursos:importar-grifos …`, `python -I -X utf8 scripts/concursos/validar_grifos.py …`.
- Não altere código, banco, migrations, os `.md` de teoria nem os JSON dos cadernos. Não faça commit nem push.
- O conteúdo do TEC é protegido: escreva os pontos com suas palavras, não copie o comentário do professor.

### Passo 1 — o que falta

Rode `php artisan concursos:tec-novos --pendencias` e leia `storage/concursos/tec/PENDENTES.md`. Para cada caderno
ele dá: a matéria (`slug`), a **seção** do material (ex.: `c04`; no Tributário pode ser `tc04`), o arquivo do
caderno, as **erradas sem ponto** e as **questões sem grifo** (só nas matérias que já têm grifos).

### Passo 2 — pontos de resumo das erradas

1. Leia, no JSON do caderno, cada questão listada em "Erradas sem ponto": `enunciado` e `alternativas` (HTML),
   `marcada` e `correta` (posição a partir de 1; em certo/errado 1 = Certo, 2 = Errado) e `comentario.texto`.
2. Abra um arquivo de partes já existente da mesma matéria (`storage/concursos/materiais/<slug>-partes/p01.json`)
   para copiar o estilo. Crie o **próximo** arquivo da pasta (`p02.json`, `p03.json`…). Se a pasta não existe, crie-a
   começando em `p01.json`. Formato: uma lista com um objeto por seção.
   ```json
   [
     {
       "chave": "c04",
       "titulo": "C04 · Tema do caderno em poucas palavras",
       "intro": "Padrão dos erros: **tema 1**, **tema 2** e **tema 3**.",
       "pontos": [
         {
           "chave": "<slug>-c04-<tema-curto>",
           "titulo": "Frase curta com o assunto do ponto",
           "armadilha": "1) O que a banca trocou. 2) Outra pegadinha, se houver.",
           "regra": "A regra certa, em markdown, com **negrito** no que decide a questão e o artigo/súmula entre parênteses.",
           "macete": "Uma linha para lembrar.",
           "questoes": ["2727681", "561263"]
         }
       ]
     }
   ]
   ```
   - `chave` da seção = exatamente a seção do PENDENTES.md. Se a seção **já tinha pontos** (outro caderno com o
     mesmo número), não mande `titulo` nem `intro`: só os `pontos` novos.
   - Agrupe as erradas por regra: um ponto pode ter várias questões; **toda** errada listada tem de estar em
     algum `questoes` (os ids são strings).
   - `chave` do ponto: única, em minúsculas, `<slug>-<seção>-<tema>`. Não reaproveite chaves de outros arquivos.
   - Diga em `armadilha` onde eu errei (o que marquei × o gabarito); `regra` explica por que o gabarito está
     certo e as outras alternativas, erradas. Número de artigo, prazo, fração e percentual: confira no texto
     de lei citado no comentário ou no resumo teórico (`storage/concursos/materiais/<slug>-modulo*.md`); se não
     der para confirmar, não ponha o número.
   - Se o gabarito parecer errado ou não houver comentário, diga isso no ponto em vez de inventar a explicação.
3. Importe: `php artisan concursos:importar-pontos <slug> storage/concursos/materiais/<slug>-partes/pNN.json`.
   A saída diz quantas questões foram ligadas. Rodar de novo é seguro (atualiza pela chave).

### Passo 3 — grifos (só se o PENDENTES.md listar "Questões sem grifo")

Siga as regras de `storage/concursos/materiais/PROMPT-grifos-antigravity.md` (formato da linha, trecho literal do
`.md`, `por_que`, `sem_trecho`), com estas diferenças:
- Faça só as questões listadas, no arquivo `.jsonl` que o PENDENTES.md indica (um por caderno; crie se não existir).
- Antes de escolher um trecho, procure nos outros `.jsonl` da matéria se outra questão do mesmo assunto já usa
  um trecho: copie **igual**, para os grifos acumularem questões.
- Rode `python -I -X utf8 scripts/concursos/validar_grifos.py <slug>` até `0 erro(s)` e todas as questões com linha.
- Importe: `php artisan concursos:importar-grifos <slug>`. Avisos de "trecho não achado" de questões que não são
  deste caderno já existiam: ignore.

### Passo 4 — conferir e responder

Rode `php artisan concursos:tec-novos --pendencias` de novo: tem de dar `Nada pendente.` (ou só o que você não
conseguiu, com o motivo). Responda com: por matéria, quantos pontos novos e quantas questões ligadas; quantos
grifos; as questões que ficaram `não coberto` (os buracos do resumo teórico); e gabaritos que pareceram errados.
