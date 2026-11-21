# Contrato de dados: módulo de concursos (versão 1)

Formato dos arquivos que o Gemini gera e que o sistema importa. Os validadores do sistema (Fase 1) vão usar estes mesmos schemas: o que passar aqui, entra.

| Arquivo | Quem gera | Um por | Schema |
|---|---|---|---|
| `edital-*.json` | Gemini (ou a IA do sistema) | cargo | [edital.schema.json](edital.schema.json) |
| `*-contexto-lote-N.json` | **sistema** (até ele existir, você monta à mão) | lote | [contexto.schema.json](contexto.schema.json) |
| `*-ledger-lote-N[-parte-K].json` | Gemini | lote (ou parte) | [ledger.schema.json](ledger.schema.json) |
| `*-resolucoes-lote-N[-parte-K].json` | Gemini | lote (ou parte) | [resolucoes.schema.json](resolucoes.schema.json) |
| `*-mapa-assunto.json` | Gemini (consolidação) | assunto | [mapa-assunto.schema.json](mapa-assunto.schema.json) |

Os exemplos válidos estão em [`exemplos/`](exemplos/). O edital do cargo 523 da UFMS já está pronto lá, extraído do PDF.

## Regras de ouro

1. **Nenhum número de contagem.** Nada de `n/N`, percentual, "5 subtópicos = 61%" ou totais de inventário. O sistema calcula tudo a partir dos ledgers. Os schemas **rejeitam** campos que não existem neles.
2. **A questão é identificada pelo `id_externo`**, o número do TEC (`3796866`), nunca por `Q001`. Isso elimina a renumeração por lote e a "trava de URL" do prompt de consolidação.
3. **O gabarito é dado, não decidido.** O Gemini copia o gabarito do PDF em `gabarito_conferido`. Se divergir do PDF, o lote é rejeitado.
4. **Subtópicos vêm de lista fechada.** Use `{"id": "..."}` da lista `subtopicos_aprovados`. Só proponha `{"novo": "...", "justificativa": "..."}` quando nenhum da lista servir.
5. **Dispositivos só podem ser IDs que existem** em `dispositivos_validos`, no formato `cc-2002/art-202/inc-VI`.
6. **Se a resposta for cortada**, peça "continue a partir do id_externo X" e salve como `-parte-2`, com `lote.parte`/`lote.total_partes` preenchidos.

## Como fica o seu fluxo com o Gemini

**Lote 1 de um assunto novo.** Ainda não existe lista de subtópicos, então:
- Anexe o PDF de 50 questões e, se tiver, o PDF de teoria do TEC.
- Peça o `ledger` e o `resolucoes`. Todos os subtópicos virão como `novo`.
- Revise os subtópicos propostos. Eles viram a lista aprovada, com `id` em slug (`causas-interruptivas`).

**Lotes 2 em diante.** Cole a lista aprovada (o bloco `subtopicos_aprovados` do contexto) no prompt.

**Consolidação.** Anexe todos os ledgers e o PDF de teoria e peça o `mapa-assunto`.

## Bloco para colar no fim dos seus prompts

Substitua a seção **"11. FORMATO DE SAÍDA…"** do *Mapeamento Completo* (e a diagramação de PDF do prompt de consolidação) por:

```text
## FORMATO DE SAÍDA (OBRIGATÓRIO)
Não gere PDF. Responda SOMENTE com JSON válido, sem texto antes ou depois,
seguindo EXATAMENTE o JSON Schema anexado (<nome>.schema.json + comum.schema.json).

Regras:
- schema_versao = "1".
- Identifique cada questão pelo número do TEC (id_externo, ex.: "3796866"),
  nunca por Q001.
- gabarito_conferido = gabarito que está no PDF. Não altere o gabarito; se
  discordar dele, registre em "observacoes" e mantenha o do PDF.
- NÃO inclua contagens, percentuais, n/N, totais ou inventário: o sistema calcula.
- Subtópicos: use {"id": ...} da lista SUBTOPICOS_APROVADOS abaixo. Só use
  {"novo": ..., "justificativa": ...} se nenhum servir.
- Dispositivos legais: use somente IDs da lista DISPOSITIVOS_VALIDOS, no
  formato norma/art-N/inc-X. Se a lista estiver vazia, omita o campo.
- Se a resposta não couber, pare no fim de uma questão completa e diga
  apenas: CONTINUA A PARTIR DE <id_externo>.

SUBTOPICOS_APROVADOS:
<cole aqui>

DISPOSITIVOS_VALIDOS:
<cole aqui ou deixe vazio>
```

Para o ledger, peça **dois arquivos separados**, em duas respostas: primeiro o `ledger` e depois o `resolucoes`. As resoluções são o maior volume de texto; juntas na mesma resposta, elas cortariam o ledger.

## Edital → assuntos do TEC (o próximo passo para o cargo 523)

O arquivo `exemplos/edital-ufms-152-2026-cargo-523.json` ainda não tem `assuntos_sugeridos`. Para popular as matérias e os assuntos:

1. No filtro do TEC, abra cada disciplina do edital e copie a árvore de assuntos.
2. Peça ao Gemini: *"Para cada tópico deste edital, preencha `assuntos_sugeridos` com os assuntos do TEC (lista anexa) que o cobrem, escritos exatamente como o TEC escreve. Não invente assuntos que não estejam na lista. Devolva o JSON completo seguindo edital.schema.json."*
   Pode ser só o nome do assunto (`"Crase"`) em vez de `{disciplina, assunto}`: o sistema cria o assunto como "a confirmar" e preenche a disciplina sozinho quando o primeiro caderno do TEC daquele assunto for importado. O nome precisa ser **idêntico** ao que o TEC imprime no caderno; se divergir, a importação do caderno cria outro assunto e o do edital continua sem questões (aparece na curadoria).
3. Os tópicos com `sem_questoes_tec: true` (Estatuto, Regimento e Código de Ética da UFMS, Manual de Atos) ficam sem assunto. Eles serão cobertos por lei seca e questões inéditas.

## Cadernos do TEC: questões + árvore de assuntos

O PDF do caderno imprime só a **folha** do assunto ("Vacância (arts. 33 a 35…)"), e o edital costuma estar ligado a um nível acima ("Lei nº 8.112/1990 - Estatuto…"). Por isso, cada caderno entra em dois passos:

1. Importe os PDFs, em qualquer quantidade e ordem. Partes repetidas não duplicam.

   ```powershell
   php artisan concursos:importar-pdf "C:\...\1.pdf" "C:\...\2.pdf"
   ```

2. Copie a lista **Assuntos** da página do caderno no TEC para um `.txt`. A hierarquia vem da indentação; as contagens e os links são ignorados. Guarde o arquivo em `storage/concursos/arvores/`, que fica fora do git. Depois importe:

   ```powershell
   php artisan concursos:importar-arvore storage\concursos\arvores\legislacao.txt
   ```

   O comando mostra quantas questões cada tópico do edital passou a alcançar.

## Pacotes do Gemini: ida e volta

1. Gere os pacotes. Rodar de novo depois de importar mais PDFs gera só os lotes das questões novas.

   ```powershell
   php artisan concursos:gerar-pacotes
   ```

2. No Gemini, use uma conversa por pacote. Anexe o pacote e os arquivos de `_anexar-sempre`.
3. Salve as 3 respostas (ledger, resoluções parte 1 e parte 2) numa pasta. O nome não importa, porque o tipo e o lote são lidos do conteúdo. Depois importe a pasta inteira:

   ```powershell
   php artisan concursos:importar-gemini "C:\...\Concurso UFMS\Gemini"
   ```

   O arquivo inteiro é recusado se a questão não for do lote, se o gabarito divergir do PDF, se a alternativa correta da resolução estiver errada ou se o subtópico não existir. Subtópicos `novo` ficam pendentes para a curadoria. Arquivos vazios são pulados, e reimportar sobrescreve.

## Validar um arquivo antes de importar

O importador já valida sozinho. Para validar à mão com Python:

```powershell
pip install jsonschema
```

Depois, dentro desta pasta, passe um ou mais arquivos ao validador:

```powershell
python validar.py "C:\caminho\prescricao-ledger-lote-2.json"
```

O tipo de cada arquivo é reconhecido pelo nome (`edital-`, `-contexto-`, `-ledger-`, `-resolucoes-`, `-mapa-assunto`). O validador mostra o caminho exato de cada erro, por exemplo `questoes/3/gabarito_conferido`. No sistema, ele vai virar `php artisan concursos:validar`.
