# Manual de Instruções — Módulo Estudo para Concursos

O módulo **Estudo para Concursos** (baseado em [henferuz/estudo-concursos](https://github.com/henferuz/estudo-concursos)) foi integrado diretamente ao sistema **Financeiro**. Você não precisa rodar nenhum servidor Python separado (`iniciar.py`): basta acessar o sistema normalmente com seu login, e todo o seu progresso é salvo automaticamente no banco de dados do Laravel, separado por usuário.

---

## 1. Como acessar

1. Faça login no sistema normalmente.
2. No **Painel Inicial (`/home`)**, clique no card **Estudo para Concursos** ou no item **Estudo Concursos** no menu lateral esquerdo (disponível em todas as telas do sistema).
3. Você será levado diretamente para `/concursos/inicio.html`.
4. Para voltar ao painel principal do sistema a qualquer momento, clique em **← Painel** no canto superior direito da barra de navegação.

---

## 2. O que cada tela faz

| Tela | Para que serve |
|---|---|
| **Início** (`inicio.html`) | Contagem regressiva até a prova, resumo diário dos últimos 14 dias, lista de tarefas de hoje ("Para hoje"), painel "O que revisar" com seus pontos mais fracos, desempenho por simulado e por disciplina. |
| **Caderno** (`caderno.html`) | Caderno de questões focado em uma lei específica, em ordem de artigos. Após responder, exibe gabarito, artigo correspondente e análise de cada alternativa (destacando palavras alteradas pela banca). |
| **Simulados** (`simulados.html`) | Lista de simulados completos com pontuação de 0 a 10 (botão **Notas**), justificativa por alternativa e citação automática do texto da lei. |
| **Flashcards** (`flashcards.html`) | Revisão espaçada (SRS): você tenta lembrar a resposta, vira o card e avalia se foi fácil/difícil. O sistema agenda o retorno do card no dia ideal. Também permite criar seus próprios cards. |
| **Refazer erros** (`refazer.html`) | Reúne automaticamente todas as questões que você errou. Você refaz sem ver a resposta antiga, informa o nível de certeza e o motivo do erro. Ao acertar **3 vezes seguidas em dias diferentes**, a questão é marcada como dominada. |
| **Resumos** (`resumos.html`) | Mostra o texto exato da lei, explicação *"Em palavras simples"* e a pegadinha *"Na prova"*, além de listar abaixo as questões que você errou naquele assunto. |
| **Lei seca** (`leis.html`) | Leitura de leis completas com **4 cores de grifo**, sublinhado, negrito, anotações pessoais e o botão **STF** ao lado de cada artigo (súmulas e jurisprudência do STF vinculadas ao artigo). |
| **Edital** (botão no topo) | Edital verticalizado interativo. Marque os tópicos estudados e acompanhe o percentual geral sempre visível na barra superior. |

---

## 3. Onde fica salvo o seu progresso

- Todo o progresso (respostas de simulados e cadernos, revisões de flashcards, grifos e notas da lei seca, histórico de erros e marcações do edital) é salvo automaticamente na tabela `concursos_states` do banco de dados do sistema, vinculado à sua conta (`user_id`).
- Cada usuário autenticado no sistema possui seu próprio progresso isolado.
- Se aparecer uma **faixa vermelha na parte inferior da tela**, significa que a sessão expirou ou houve falha de comunicação com o servidor — recarregue a página ou faça login novamente antes de continuar.

---

## 4. Como personalizar para o SEU concurso

O módulo já vem com questões de exemplo (CF, LRF, CTN e LAI) e 26 arquivos de leis/normas (CF, CTN, LRF, LC 116, Lei 4.320, Lei 8.137, LAI, LGPD, Lei 14.133, Lei 9.784, Lei 14.063, Lei 6.404, Decreto 1.171, MCASP e NBCs).

Todos os arquivos de configuração e conteúdo ficam dentro da pasta `public/concursos-app/`:

| O que você quer alterar | Arquivo / Pasta |
|---|---|
| Nome do concurso, **data da prova**, nota mínima e **edital verticalizado** | `public/concursos-app/js/config.js` |
| Simulados (um arquivo `.json` por simulado) | `public/concursos-app/conteudo/simulados/*.json` |
| Caderno de questões de uma lei | `public/concursos-app/conteudo/caderno.json` |
| Flashcards (um arquivo `.json` por lei) | `public/concursos-app/conteudo/flashcards/*.json` |
| Resumos | `public/concursos-app/conteudo/resumos.json` |
| Textos das leis (em Markdown) | `public/concursos-app/leis/*.md` |

### Passo a passo após editar o conteúdo

1. **Se você editou apenas `public/concursos-app/js/config.js`** (data da prova, nome ou edital): basta salvar o arquivo e recarregar a página no navegador.
2. **Se você adicionou ou editou arquivos em `public/concursos-app/conteudo/`**: rode o gerador de dados no terminal (requer Python 3 instalado):

```powershell
python public/concursos-app/tools/gerar_dados.py
```

O script valida os JSONs (gabaritos, estrutura, referências de artigos), puxa automaticamente o texto exato da lei a partir de `public/concursos-app/leis/` e atualiza os arquivos compilados em `public/concursos-app/dados/`. Se houver qualquer erro, ele aponta exatamente o arquivo e a questão sem corromper os dados.

> **Importante sobre ordem das questões:** o progresso é vinculado ao ID da questão (ex.: `S1-03` = Simulado 1, questão 3). Se você já começou a estudar um simulado ou caderno, **adicione novas questões ao final** em vez de reordenar ou remover questões antigas.

---

## 5. Como adicionar uma lei nova na Lei Seca

1. Baixe a página compilada da lei no site do Planalto e salve como `<pasta>/<sigla>.htm` (ex.: `l8112.htm`).
2. Adicione a lei no dicionário `LEIS` em `public/concursos-app/tools/leis_planalto_para_md.py` e execute:
   ```powershell
   python public/concursos-app/tools/leis_planalto_para_md.py <pasta>
   ```
   Isso gera o arquivo Markdown em `public/concursos-app/leis/`.
3. Registre a nova lei em:
   - `LEIS` em `public/concursos-app/js/lei-seca.js` (para aparecer na aba **Lei seca**);
   - `ARQ` em `public/concursos-app/tools/leis_dispositivos.py` e `LEIS` em `public/concursos-app/tools/gerar_dados.py` (para citações automáticas em questões e flashcards).
4. Execute `python public/concursos-app/tools/gerar_dados.py`.

### Atualizar jurisprudência do STF (opcional)

Para baixar/atualizar as decisões e súmulas do STF vinculadas aos artigos (requer internet; a primeira execução leva ~30 minutos):

```powershell
python public/concursos-app/tools/stf_jurisprudencia.py
```

---

## 6. Prompts prontos para gerar conteúdo com IA

Você pode usar qualquer IA (ou o próprio assistente aqui no projeto) para gerar simulados, flashcards, cadernos e resumos no formato exato do sistema. Sempre anexe o arquivo de exemplo correspondente em `public/concursos-app/conteudo/` e o texto da lei em `public/concursos-app/leis/`.

- **Edital e data da prova:** envie seu edital e peça para reescrever `public/concursos-app/js/config.js` mantendo a estrutura de `nome`, `dataProva` (`AAAA-MM-DD`) e `edital`.
- **Simulado:** anexe `public/concursos-app/conteudo/simulados/simulado-01.json` + o `.md` da lei e peça novas questões no mesmo formato JSON (`area`, `tema`, `fontes`, `enunciado`, `alternativas`, `gabarito`, `comentario`, `por_alternativa`).
- **Flashcards:** anexe `public/concursos-app/conteudo/flashcards/cf.json` + o `.md` da lei e peça cards de pergunta (`frente`/`verso`) e de lacuna (`"tipo": "lacuna"` com `[[resposta]]`), sempre preenchendo `art` e `armadilha`.
- **Resumos dos erros:** cole os IDs ou enunciados que você errou, anexe `public/concursos-app/conteudo/resumos.json` e peça resumos usando `> sigla artigo [incisos]`, *"Em palavras simples:"* e *"Na prova:"*.

Depois de salvar os JSONs gerados em `public/concursos-app/conteudo/`, basta rodar `python public/concursos-app/tools/gerar_dados.py` e recarregar a página!

---

## 7. Livro de resumo (Concursos → Resumos, `/concursos/material`)

Cada matéria é um **livro** com capítulos e subcapítulos. Só funciona onde o módulo novo está ligado (`CONCURSOS_ENABLED=true`, o PC com o Postgres; de fora, pelo link do ngrok).

- **Capítulos:** montados sozinhos pela árvore de assuntos do TEC a partir das questões que você errou (quem não tem assunto conhecido vira capítulo com o nome do caderno). Você cria, renomeia, move e apaga capítulos pelo **⋯** ao lado de cada um no sumário, ou em **+ Novo capítulo**.
- **Ler:** o capítulo abre em modo leitura. Selecione um trecho para **grifar** (4 cores) ou deixar uma **✎ Nota** na margem. ← e → passam de capítulo; **/** abre a busca no livro.
- **Editar:** botão **Editar** (ou tecla **e**). Barra com negrito, títulos, listas, tabela, link, imagem e desfazer. Digite **/** numa linha vazia para inserir: caixa (Pegadinha, Regra certa, Macete, Atenção, Exemplo, Base legal), **Ponto de erro** (com Ainda erro / Revisando / Dominado e "Errei de novo"), **Questão errada** (respondível no próprio texto), artigo de lei ou súmula e imagem. `[[ctn 98]]`, `[[sv 24]]` etc. viram link que abre o artigo sem sair da página. Cole ou arraste imagens direto no texto. Tudo salva sozinho ("Salvo às …").
- **Imprimir / PDF do livro:** no rodapé do sumário, o livro inteiro com capa, sumário, caixas e suas notas.
- **Refazer erradas:** agora filtra também por capítulo; cada capítulo tem "Refazer as deste capítulo".

Depois de atualizar o código no PC:

```
composer install
npm install && npm run build
php artisan migrate --database=concursos_pgsql --path=database/migrations/concursos
```

Os materiais que já existiam são convertidos para livro ao abrir pela primeira vez (ou de uma vez com `php artisan concursos:material-livro`). Para os cadernos do TEC, o fluxo continua o mesmo (`scripts/concursos/converter_tec.py` e `concursos:importar-material ... --questoes=...`): as questões novas caem no capítulo do assunto delas.
