# Prompt: questões inéditas de uma norma (tópicos sem questões no TEC)

Use para os tópicos do edital que o TEC não cobre: as normas próprias da UFMS (Estatuto,
Regimento Geral, Código de Ética, Manual de Atos Oficiais).

**Como usar:** uma conversa nova por norma. Anexe:
- o **texto oficial da norma**, em PDF ou TXT, baixado do site da UFMS;
- `ineditas.schema.json` e `comum.schema.json`.

Cole o texto abaixo, preenchendo as 4 linhas entre colchetes. Cada resposta traz um JSON com
10 questões. Para mais 10, responda **"mais 10"**. Salve cada resposta num `.txt` e importe:

```powershell
php artisan concursos:importar-ineditas "C:\...\estatuto-1.txt"
```

---

Você é examinador da banca **FAPEC**, que elabora as provas da UFMS. Escreva questões **inéditas**
de múltipla escolha sobre a norma anexada, para o cargo de nível superior da UFMS.

- ÁREA DO EDITAL: [ex.: Legislação]
- TÓPICO DO EDITAL (numeração): [ex.: 5]
- NORMA: [ex.: Estatuto da UFMS (Resolução nº 93-Coun/UFMS/2021)]
- CÓDIGO DA NORMA (minúsculas e hífens): [ex.: estatuto-ufms-2021]

## Regras

1. **Só o texto anexado.** Toda afirmação correta tem de estar literalmente no texto da norma,
   e toda incorreta tem de contrariar um trecho dele. Não use conhecimento externo, nem versões
   antigas da norma. Se o texto anexado não cobrir um assunto, não pergunte sobre ele.
2. **Estilo FAPEC:** enunciado curto ("De acordo com o Estatuto da UFMS, …", "Assinale a
   alternativa correta/incorreta"), 5 alternativas de A a E, uma só correta. Misture os comandos
   correta/incorreta e, às vezes, use itens I/II/III com alternativas combinando os itens.
3. **Distratores do jeito que a banca faz:** trocar o órgão competente (Conselho Universitário ↔
   Reitoria ↔ Conselho Diretor), o prazo, o quórum, a fração, "poderá" ↔ "deverá", inverter uma
   exceção. Nada de alternativa absurda que se elimina sem ler a norma.
4. **Cubra a norma por inteiro**, sem concentrar tudo nos primeiros artigos. Priorize o que
   costuma cair: competências dos órgãos, composição dos conselhos, mandatos, prazos, quórum,
   princípios, deveres e vedações.
5. **Gabarito equilibrado:** em cada resposta de 10, cada letra é a correta 1 a 3 vezes.
6. **Dificuldade:** B = literalidade direta; M = distinguir dois dispositivos parecidos;
   A = combinar dispositivos ou pegadinha de exceção. Faça cerca de 3 B, 5 M e 2 A.
7. **dispositivos:** `codigo-da-norma/art-N[/par-N][/inc-ROMANO]`, por exemplo
   `estatuto-ufms-2021/art-15/inc-III`. Parágrafo único = `par-unico`; só o caput = `/caput`.
8. **resolucao.alternativas:** uma entrada para cada letra (A–E), exatamente uma com
   `"correta": true`, e ela tem de ser o `gabarito`. Cada explicação cita o artigo.
9. **Não repita questões** que você já escreveu nesta conversa.

## Formato de saída (obrigatório)

Responda **somente** com um JSON válido segundo `ineditas.schema.json`, sem texto antes ou
depois:

```json
{
  "schema_versao": "1",
  "topico": { "area": "Legislação", "ordem": "5" },
  "norma": { "codigo": "estatuto-ufms-2021", "titulo": "Estatuto da UFMS (Resolução nº 93-Coun/UFMS/2021)" },
  "questoes": [
    {
      "enunciado": "De acordo com o Estatuto da UFMS, compete ao Conselho Universitário:",
      "alternativas": [
        { "letra": "A", "texto": "…" }, { "letra": "B", "texto": "…" }, { "letra": "C", "texto": "…" },
        { "letra": "D", "texto": "…" }, { "letra": "E", "texto": "…" }
      ],
      "gabarito": "C",
      "dificuldade": "M",
      "tipologia": "literalidade_norma",
      "ponto_cobrado": "Competência do Conselho Universitário para aprovar o Regimento Geral.",
      "dispositivos": ["estatuto-ufms-2021/art-15/inc-III"],
      "distrator_principal": { "letra": "A", "mecanismo": "atribui ao Reitor competência que é do Conselho Universitário" },
      "resolucao": {
        "resumo": "…",
        "fundamentacao": "…",
        "alternativas": [
          { "letra": "A", "correta": false, "explicacao": "…" },
          { "letra": "B", "correta": false, "explicacao": "…" },
          { "letra": "C", "correta": true, "explicacao": "…" },
          { "letra": "D", "correta": false, "explicacao": "…" },
          { "letra": "E", "correta": false, "explicacao": "…" }
        ]
      }
    }
  ]
}
```

Comece pelas 10 primeiras questões.
