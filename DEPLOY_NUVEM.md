# 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos

Este guia foi preparado para que você possa colocar seu sistema no ar em poucos minutos antes da sua viagem, garantindo acesso completo pelo celular ou notebook, com **todos os seus 1.458+ registros atuais** (transações, faturas de cartão, metas, notícias e concursos) preservados.

---

## 📦 O que foi preparado no projeto

1. **Dump dos Dados Atuais**:
   - `database/dumps/production_data.json`: Arquivo estruturado com todos os registros atuais.
   - `database/dumps/production_dump.sql`: Dump SQL nativo do PostgreSQL com preservação de IDs e auto-increment (`setval`).
   - `database/seeders/ProductionDataSeeder.php`: Seeder automático do Laravel para restaurar tudo com 1 comando.
   - `php artisan db:export-production`: Comando para você re-exportar os dados a qualquer momento se fizer novos lançamentos antes de viajar.

2. **Container Docker de Alta Performance**:
   - `Dockerfile`: Multi-stage build (Node.js 20 para compilar o frontend com Vite + Tailwind, PHP 8.3-FPM Alpine + Nginx).
   - `docker/entrypoint.sh`: Inicialização automática com migrações, restauração automática dos dados (`SEED_ON_DEPLOY=true`), otimizações de cache de rotas/views e auto-registro do Webhook do Telegram.

3. **Arquivos de Configuração Prontos para Nuvem**:
   - `render.yaml` (Blueprint para Render.com)
   - `railway.json` (Railway.app)
   - `fly.toml` (Fly.io)
   - `docker-compose.prod.yml` (Para qualquer VPS / Docker próprio)
   - `.env.production.example` (Template de variáveis de ambiente)

---

## 🌟 Opção 1: Deploy no Render.com (Recomendado)

O Render permite rodar a aplicação em Docker e fornece banco PostgreSQL gratuito/baixo custo.

### Passo 1: Enviar o código para o GitHub
Certifique-se de que o commit com os arquivos foi enviado:
```bash
git push origin main
```

### Passo 2: Criar o serviço no Render
1. Acesse [dashboard.render.com](https://dashboard.render.com/) e faça login com seu GitHub.
2. Clique no botão **"New +"** no topo e selecione **"Blueprint"**.
3. Selecione o repositório `samuelbellan/financeiro`.
4. O Render detectará automaticamente o arquivo `render.yaml`, que cria:
   - O banco de dados PostgreSQL `financeiro-db`
   - O serviço web `financeiro-app` com as variáveis já configuradas!
5. Clique em **"Apply"**.

### Passo 3: Atualizar a URL da aplicação
1. Quando o deploy inicial concluir, copie a URL gerada (exemplo: `https://financeiro-app-xxxx.onrender.com`).
2. No painel do serviço no Render, vá na aba **"Environment"** e ajuste `APP_URL` para a sua URL real:
   ```env
   APP_URL=https://financeiro-app-xxxx.onrender.com
   ```
3. O container reiniciará e o Webhook do Telegram será registrado automaticamente para a nova URL!

---

## 🚂 Opção 2: Deploy no Railway.app

1. Acesse [railway.app](https://railway.app/) e crie um novo projeto a partir do repositório `samuelbellan/financeiro`.
2. Adicione um serviço de banco de dados **PostgreSQL** clicando em `+ New -> Database -> Add PostgreSQL`.
3. No serviço do Laravel, vá em **Variables** e defina:
    - `APP_KEY` = (Gere uma chave base64 ou deixe gerada pelo provedor)
    - `APP_ENV` = `production`
    - `APP_DEBUG` = `false`
    - `APP_URL` = `https://${{RAILWAY_PUBLIC_DOMAIN}}`
    - `DB_CONNECTION` = `pgsql`
    - `DATABASE_URL` = `${{Postgres.DATABASE_URL}}`
    - `SEED_ON_DEPLOY` = `true`
    - `AUTO_SET_TELEGRAM_WEBHOOK` = `true`
    - `TELEGRAM_BOT_TOKEN` = `seu_telegram_bot_token_aqui`
    - `TELEGRAM_ALLOWED_CHAT_ID` = `seu_chat_id_aqui`
    - `TELEGRAM_WEBHOOK_SECRET` = `seu_secret_aleatorio_aqui`
    - `GEMINI_API_KEY` = `sua_gemini_api_key_aqui`

---

## ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase

Se preferir usar um banco PostgreSQL externo (como Neon.tech ou Supabase):
1. Crie uma conta gratuita em [neon.tech](https://neon.tech/) ou [supabase.com](https://supabase.com/) e crie um banco de dados.
2. Copie a Connection String (`DATABASE_URL=postgres://...`).
3. Você pode importar o dump diretamente na aba **SQL Editor** do Supabase/Neon colando o conteúdo do arquivo:
   `database/dumps/production_dump.sql`
4. Na sua aplicação na nuvem (Render/Railway/Vercel/Fly), apenas configure a variável `DATABASE_URL`.

---

## 🤖 Como testar e gerenciar o Bot do Telegram na Nuvem

Após o deploy, suas mensagens enviadas ao bot no Telegram cairão diretamente no sistema online!

Se precisar redefinir o Webhook manualmente a qualquer momento, basta rodar via console/terminal do seu host:
```bash
php artisan telegram:set-webhook https://sua-url-na-nuvem.com/webhook/telegram
```

Para verificar o status do webhook:
Envie qualquer mensagem para o bot no Telegram (ex: `Gastei 50 almoço`).

---

## 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais

Se você continuar usando o sistema localmente hoje e quiser gerar um novo backup atualizado antes de embarcar:

1. No terminal local do seu computador, execute:
   ```powershell
   php artisan db:export-production
   ```
2. Faça o commit e push:
   ```powershell
   git add database/dumps/
   git commit -m "chore: atualiza dados do banco para viagem"
   git push origin main
   ```
3. Na nuvem, o redeploy automático atualizará o banco com os novos registros!

---

## 📱 Dica de Acesso pelo Celular

Adicione o link da aplicação à tela inicial do seu celular (Chrome / Safari -> *Adicionar à tela de início*). O sistema possui design responsivo e funcionará como um aplicativo nativo.
