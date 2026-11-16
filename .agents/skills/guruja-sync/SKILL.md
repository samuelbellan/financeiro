---
name: guruja-sync
description: Sincroniza a meta e as atividades atuais da Guruja no aplicativo de estudos. Use sempre que o usuário pedir "Sincronize a meta da Guruja agora", "atualizar guruja", "sincronizar guruja", "puxar tarefas da guruja" ou similar.
---

# Sincronização da Guruja no App de Estudos

Esta skill orienta o agente a realizar a sincronização sob demanda da meta e das tarefas de estudo da Guruja.

## Procedimento de Execução

Ao receber a solicitação ("Sincronize a meta da Guruja agora" ou similar):

1. **Executar o comando Artisan de sincronização:**
   ```bash
   php artisan estudos:guruja-sync
   ```
   - O comando tentará sincronizar via MCP da Guruja (`https://mcp.guruja.com.br/mcp`), ou através da rotina do Claude caso os triggers estejam definidos.
   - Se o comando retornar código 0 (`✓`), informe ao usuário que as tarefas da Guruja foram sincronizadas e o card já foi atualizado.

2. **Se faltar autenticação ou o conector retornar erro:**
   - **Caso o usuário tenha o token Bearer do MCP**:
     Execute passando o token:
     ```bash
     php artisan estudos:guruja-sync --token="<token>"
     ```
   - **Caso o usuário forneça o JSON ou um print das tarefas**:
     Extraia a lista de atividades e passe para o comando:
     ```bash
     php artisan estudos:guruja-sync --json='{"atividades": [...]}'
     ```
   - **Caso necessário navegar no portal**:
     Delegue para o subagente `browser` para abrir `https://aluno.guruja.com.br`, obter os dados do planejamento atual do aluno e importar via `php artisan estudos:guruja-sync`.

3. **Notificar o usuário:**
   Apresente um resumo claro das matérias e quantidade de atividades pendentes sincronizadas.
