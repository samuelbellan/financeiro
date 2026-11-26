# Sabatina 2026-11-26 — Vade Mecum anotável

Inspiração: telas de um site de estudo para concursos fiscais (questões com alternativa corrigida, Vade Mecum por artigo, cadernos, flashcards, áudio, mesa do edital, dicionário, radar). Inspiração de **features e fluxo**, não de identidade visual.

## Decisões

| # | Decisão | Por quê |
|---|---|---|
| 1 | Primeira feature: **Vade Mecum anotável** (sobre `/concursos/legislacao/{lei}`). Flashcards, estante e correção na alternativa ficam para depois. | Escolha do usuário. |
| 2 | MVP: **marcador + nota por artigo, grifo de trecho, "cai na prova"**. Fora: tags temáticas, áudio. | Tags exigem curadoria; áudio é outra feature. |
| 3 | Âncora do grifo: lei + artigo + **trecho + ~30 caracteres de contexto**; reposiciona procurando o trecho; se sumir vira **órfão visível**, nunca apagado. | A lei muda (`concursos:baixar-leis --atualizar`, emendas). |
| 4 | Tudo **por aluno**. | O material já é por aluno (`Material.user_id`). |
| 5 | "Cai na prova" com **dois números**: incidência do banco (todos os alunos, de `QuestaoClassificacao.dispositivos`) e pessoal (questões erradas + pontos do material que citam). | Separa importância para a banca de importância para o aluno. |
| 6 | Incidência sobre o **banco inteiro**; filtro por banca do edital depois. | Fapec tem poucas questões. |
| 7 | **Desktop primeiro**; no celular, ler/marcar/anotar e grifo por "modo grifar". | Evitar briga com o menu nativo de seleção. |
| 8 | Botão **"Levar ao material"** na nota: cria um ponto (status revisando) no capítulo escolhido, com `[[lei art]]`. | A nota entra no ciclo errando/revisando/dominado e depois nos flashcards. |
| 9 | Unidade = **artigo**; incidência agrega e mostra quebra por § / inciso no detalhe. Sem reescrever `AcervoLeis::analisar`. | O grifo dá a precisão dentro do artigo. |

## Suposições
- Evoluir `leis/show.blade.php`, sem tela nova.
- Tabelas novas em `concursos_pgsql` (`php artisan migrate` sem `--database`).
- Conversor de slug: `lei-8112-1990` → `lei8112`, `cf-1988` → `cf`.

## Riscos
- Dispositivos hoje só cobrem Lei 8.112, 9.784, CF e 1.102 (questões UFMS). No Direito Tributário a incidência do banco fica zerada até classificar questões de lá.
- Peso da página na CF inteira com badges e grifos.
- Trecho grifado repetido no mesmo artigo: o contexto desempata (teste unitário).

## Entrega
1. **Etapa 1**: marcador, nota, "cai na prova", "Levar ao material", testes de feature.
2. **Etapa 2**: grifo de trecho com reancoragem e órfãos, modo grifar no celular, teste unitário da reancoragem.
3. Revisão do usuário antes de commit; nada de conteúdo TEC no git.
4. Depois: flashcards + "Revisar hoje".
