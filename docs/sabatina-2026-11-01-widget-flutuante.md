# Sabatina 2026-11-01: widget flutuante do cronômetro de estudos

## Pergunta original
Criar um app ou widget que fique flutuando como extensão do módulo de tempo de estudos.

## Decisões tomadas
| Decisão | Justificativa |
|---|---|
| PC, com **Document Picture-in-Picture** (Chrome/Edge) | Janela sempre por cima, sem instalar nada, reaproveitando o código da PWA |
| Mostra matéria, tempo da sessão, total do dia e descanso; botão **Parar/Retomar** | Evita sair do PDF para a ação mais comum; troca de matéria continua no app |
| Abre pelo **⧉** do cabeçalho e **automaticamente ao dar ▶** (ajuste por aparelho, ligado por padrão) | Abre na hora em que o widget é útil; o botão serve para reabrir |
| Com o widget, iniciar uma matéria **não entra em tela cheia** | O Chrome só aceita um gesto por clique, e o estudo acontece em outra janela |
| O widget **fecha junto com o app**, com uma dica na primeira vez | É limitação do Chrome; o cronômetro não para, e nenhum dado se perde |
| O relógio roda **dentro da janela do widget** | O Chrome desacelera os timers do app quando ele está minimizado |
| Com o widget aberto, sincroniza **a cada 60 s**, mesmo com o app minimizado | Mudanças feitas no celular aparecem rápido; cada sync custa ~0,35 s |
| Visual compacto de ~260×100 px úteis, sem avatar e sem som | Não tampa o conteúdo; a dica some e o relógio encolhe se a janela diminuir |
| "Retomar" usa a última matéria do cronômetro; se ela foi arquivada ou excluída, o botão vira "Abrir app" | Não tenta adivinhar outra matéria |

## Implementação
- `resources/js/estudos/widget.js`: `widgetView()` (lógica pura) e `createWidget()` (janela PiP).
- `tests/js/estudos/widget.test.js`: 8 casos (rodando, descanso, virada do dia, idle depois de 6h, matéria arquivada ou excluída, sessões excluídas e manuais, sessão vinda de outro aparelho, sem sessões).
- `main.js`: botão ⧉, abertura automática ao iniciar, ajuste "Abrir o widget ao iniciar" (guardado em `meta.widget_auto`, por aparelho).
- `sync.js`: `setFast(true)` com o widget aberto.
- Commits: `f4f6990` (widget) e `353c9d8` (ajuste de altura: o Chrome desconta ~56 px para a barra da janela).

## Suposições
- O estudo acontece no Chrome ou no Edge do PC, com o app aberto (minimizado serve).
- A barra da janela do Chrome tem ~56 px de altura. Valor medido no Windows 11; pode variar em outro sistema ou outra escala de tela, e por isso o CSS se adapta quando sobra pouco espaço.

## Riscos e pontos em aberto
- **Não testado na prática:** Parar e Retomar pelo widget, abertura automática ao dar ▶ (ambos criariam sessões reais) e o comportamento com o app minimizado. Conferir no próximo estudo.
- Firefox e celular não têm a API: o botão não aparece. No celular, a alternativa futura é uma notificação fixa com o tempo.
- Ao parar pelo widget, a janela de observação da sessão abre no app, e não no widget.

## Próximos passos (se quiser evoluir)
1. Validar Parar e Retomar e a abertura automática numa sessão real.
2. Versão 2: trocar de matéria pelo widget (bolinhas coloridas).
3. Celular: notificação fixa com o tempo da sessão.
