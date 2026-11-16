# Regras de Sincronização da Guruja

Quando o usuário pedir **"Sincronize a meta da Guruja agora"**, **"Atualizar Guruja"**, ou qualquer variante de sincronização de tarefas/metas da Guruja:
1. Dispare a rotina de sincronização utilizando `php artisan estudos:guruja-sync`.
2. Se o MCP exigir token avulso ou se o usuário fornecer um JSON das tarefas ou print, utilize `php artisan estudos:guruja-sync --json="..."` para persistir no banco e atualizar o app de estudos.
3. Se o usuário pedir para extrair diretamente do portal web da Guruja, utilize o subagente `browser` para navegar até `https://aluno.guruja.com.br`, obter os dados do planejamento e alimentar o comando de sincronização.
