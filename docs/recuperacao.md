# Recuperação: o PC queimou, e agora?

Tudo o que é preciso está em dois lugares:
- **GitHub**: o código (`github.com/samuelbellan/financeiro`).
- **Google Drive**:

| Pasta no Drive | O que tem |
|---|---|
| `Meu Drive\financeiro` | arquivos do app (notas fiscais, fotos, PDFs, materiais, grifos do Antigravity…). É a pasta principal, não uma cópia. |
| `Meu Drive\Backups\financeiro\db` | banco principal (finanças, estudos, usuários), cópia de hora em hora |
| `Meu Drive\Backups\concursos\db` | banco de concursos (resumos, notas, grifos, questões, revisões), cópia de hora em hora |
| `Meu Drive\Backups\financeiro\config` | `.env` (senhas e tokens), `launch.json` e as tarefas agendadas exportadas |

Perda máxima: cerca de 1 hora de uso. O código que não foi para o GitHub fica só no PC (veja a etapa 5 de `plano-backup-recuperacao.md`).

## Passo a passo num PC novo

### 1. Programas
Instale, nesta ordem:
- **Google Drive para desktop**, logado na sua conta. Espere a pasta `Meu Drive` sincronizar.
  - Confira a letra do drive: hoje é `G:`. Se mudar, ajuste no passo 3.
  - Marque as pastas `financeiro` e `Backups` como "disponível offline" para os arquivos baixarem logo.
- **Git**, **PHP 8.4** (com as extensões pdo_sqlite, pdo_pgsql, intl e zip), **Composer**, **Node 22**.
- **PostgreSQL 18**, anotando a senha do superusuário `postgres`.
- **Python 3**, usado na extração dos cadernos.
- **Tailscale** (`winget install --id Tailscale.Tailscale`) e depois `tailscale up`.

### 2. Código
```powershell
cd $HOME\Documents
git clone https://github.com/samuelbellan/financeiro.git
cd financeiro
composer install
npm ci
npm run build
```

### 3. Configuração (`.env`)
Copie o `.env` mais novo do cofre para a pasta do projeto:
```powershell
$cofre = Get-ChildItem "G:\Meu Drive\Backups\financeiro\config\env_*.txt" | Sort-Object Name | Select-Object -Last 1
Copy-Item $cofre.FullName .env
```
Confira no `.env`:
- `DADOS_DIR`, `BACKUP_DRIVE_DIR` e `CONCURSOS_BACKUP_DIR` apontam para a letra certa do Drive;
- `DB_DATABASE`, se existir, aponta para a pasta nova do projeto;
- `CONCURSOS_PG_BIN` aponta para a pasta do Postgres instalado.

### 4. Bancos
1. Crie o usuário e o banco de concursos (pede a senha do `postgres`; gera uma senha nova para o usuário `concursos` e grava no `.env`):
   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts\concursos-setup.ps1
   ```
2. Restaure os dois bancos a partir das cópias mais novas do Drive:
   ```powershell
   php artisan backup:restaurar
   ```
   Antes de sobrescrever, o comando guarda o que existir. Para usar uma cópia específica: `--sqlite=<arquivo>` ou `--dump=<arquivo>`. Para restaurar só um banco: `--so=principal` ou `--so=concursos`.
3. Aplique migrações mais novas que a cópia, se houver, e limpe o cache:
   ```powershell
   php artisan migrate --force
   php artisan optimize:clear
   ```

### 5. Arquivos que o app espera no PC
- `storage/` usa junctions para o Drive. Recrie-as como na memória "Dados no Google Drive": `Dados::path()` já lê `DADOS_DIR`, e só os caminhos antigos dependem das junctions.
- `public/storage` → `G:\Meu Drive\financeiro\app\public` (`New-Item -ItemType Junction`).
- Lei seca local (`E:\leis`, opcional, ~7 GB): `git clone --depth 1 https://github.com/ctresb/leis E:\leis` com sparse-checkout excluindo o arquivo do ES com aspas no nome, e `git config core.protectNTFS false`. Sem ela, o app baixa cada lei sob demanda.

### 6. Servidor, backup e integrações
```powershell
# Num PowerShell aberto como administrador:
powershell -ExecutionPolicy Bypass -File scripts\servidor-setup.ps1
```
```powershell
powershell -ExecutionPolicy Bypass -File scripts\backup-drive-setup.ps1
```
```powershell
powershell -ExecutionPolicy Bypass -File scripts\backup-teste-setup.ps1
```
- O primeiro script sobe o servidor e o Funnel.
- O segundo agenda o backup de hora em hora, a conferência a cada 3 h e o teste semanal.
- O terceiro cria o banco onde o teste semanal restaura.

Depois:
- **Telegram:**
  ```powershell
  php artisan telegram:set-webhook https://samu.tail036655.ts.net/webhook/telegram
  ```
  Confira com `--info`.
- **Login Google:** o callback `https://samu.tail036655.ts.net/auth/google/callback` já está no Google Cloud Console. Se o nome do Tailscale mudar, atualize lá e no `.env`.
- **Guruja:** se der "Token MCP expirado", rode `php artisan estudos:guruja-connect` e entre no navegador.

### 7. Conferir
```powershell
php artisan backup:testar
```
```powershell
php artisan backup:conferir
```
- O primeiro restaura as cópias em bancos temporários e confere tabela por tabela.
- O segundo mostra a idade das cópias.

Por fim, abra o app pelo endereço do Tailscale e veja um capítulo do resumo, a lei seca com suas notas e as finanças.

## No dia a dia
- O selo na tela inicial (para admin) mostra a idade do último backup de cada banco.
- Se um backup falhar, chega mensagem no Telegram. Se ele parar de rodar, a conferência a cada 3 h roda e avisa.
- Todo domingo às 10:00 chega o resultado do teste de restauração.
- As tarefas agendadas exportadas (`tarefas_*.xml` no cofre) servem de referência se algum script não recriar uma delas: `Register-ScheduledTask -Xml (Get-Content arquivo.xml | Out-String) -TaskName "…"`. O arquivo junta várias tarefas, então separe cada `<Task>` antes.
