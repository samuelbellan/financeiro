# Sincronizar a Guruja pelo Antigravity

O workflow `/sincronizar-guruja` (`.agents/workflows/sincronizar-guruja.md`) faz no
Antigravity o mesmo que a tarefa agendada do Claude: lê a meta atual da Guruja e
envia ao app de estudos. Roda com o modelo do Antigravity (Gemini).

## Configuração (uma vez)

### 1. Servidor MCP da Guruja

No Antigravity: menu `…` do painel do agente → **MCP Servers** → **Manage MCP Servers**
→ **View raw config** e acrescente dentro de `mcpServers`:

```json
"guruja": {
  "command": "npx",
  "args": ["-y", "mcp-remote", "https://mcp.guruja.com.br/mcp"]
}
```

O endereço `https://mcp.guruja.com.br/mcp` é o provável; se não conectar, confira a URL
exata em claude.ai → Configurações → Conectores → Guruja (ou no tutorial da Guruja) e
troque no `args`. Precisa do Node.js instalado. Na primeira execução abre o navegador para você entrar
na conta da Guruja e autorizar. Se a Guruja recusar a conexão (o conector pode
aceitar só o Claude), o workflow não funciona: use o botão "✓ Concluí" no app.

### 2. Token da API do app

Gere um token na página de API do app (o mesmo tipo usado pela tarefa agendada).
Depois, no PowerShell:

```powershell
setx ESTUDOS_API_TOKEN "cole-o-token-aqui"
```

Feche e abra o Antigravity para ele ler a variável. Não coloque o token em arquivos
do repositório.

## Uso

No chat do agente do Antigravity, com este repositório aberto, digite:

```
/sincronizar-guruja
```

Resposta esperada: `Guruja → app: meta 62, 12/26 concluídas, HTTP 200`.
