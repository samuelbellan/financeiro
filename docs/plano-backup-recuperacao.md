# Plano: backup completo no Google Drive e recuperação se o PC queimar

Levantamento feito em 2026-11-27. Objetivo: com um PC novo, o Google Drive e o GitHub, voltar a ter
o app inteiro, perdendo no máximo **1 hora** de dados.

## O que já existe

| Dado | Onde vive | Backup hoje | Frequência |
|---|---|---|---|
| Banco principal (finanças, estudos, usuários) | `database/database.sqlite` no PC | `backup:drive` → `G:\Meu Drive\Backups\financeiro\db` (VACUUM INTO) | de hora em hora ✅ |
| Arquivos (notas fiscais, fotos, mídias, PDFs, `storage/concursos`) | `G:\Meu Drive\financeiro` (o Drive é o principal) | espelho no PC em `storage/espelho-drive` | de hora em hora ✅ |
| Banco de concursos (Postgres): **resumos, notas do resumo, notas e grifos da lei, questões, tentativas, revisões** | Postgres no PC | `concursos:backup` → `G:\Meu Drive\Backups\concursos\db` (pg_dump) | **1x por dia, às 03:00** ⚠️ |
| Código | PC + GitHub | `git push` manual | quando há commit ⚠️ |
| Lei seca (`E:\leis`) | PC | não precisa: é um clone do github.com/ctresb/leis | — |

As notas novas ficam no Postgres de concursos:
- **resumo:** dentro do texto do capítulo (`material_capitulos.conteudo`);
- **lei:** na tabela `lei_notas`.

Elas entram no `pg_dump`, mas esse dump só roda uma vez por dia.

## O que falta

1. **Postgres só 1x por dia.** Se o PC queimar às 22h, o estudo do dia inteiro se perde (notas, grifos, questões respondidas).
2. **`.env` fora de qualquer backup.** Ele guarda as senhas e os tokens: Postgres, Google OAuth, Telegram, Guruja, `APP_KEY`, `DADOS_DIR`. Sem ele dá para recomeçar, mas é preciso refazer cada integração.
3. **Código sem commit.** Hoje são 27 arquivos alterados só no PC. O repositório é público, então nada sensível pode ir para lá.
4. **Nenhuma falha avisa ninguém.** Se o Google Drive para desktop fechar ou o `pg_dump` falhar, o backup para em silêncio.
5. **A restauração nunca foi testada** e não há roteiro escrito. A sabatina de 21/11 previa "restauração testada todo mês", mas isso não foi feito.
6. **As cópias manuais `database/database.backup-*.sqlite`** ficam só no PC. São secundárias, porque o backup de hora em hora já cobre.

## Etapas

### Etapa 1: Postgres de hora em hora ✅ feita em 2026-11-27
- Mudar a tarefa "Financeiro - Backup Concursos" para rodar **de hora em hora**. O dump tem ~8 MB e leva segundos.
- Retenção igual à do SQLite: 24 recentes + 14 diárias + 8 semanais (hoje é 14 + 8). Reaproveitar a lógica de `BackupDriveCommand::aplicarRetencao`.
- Mais simples ainda: o `backup:drive` passa a chamar o `concursos:backup` no fim, numa tarefa só. A tarefa diária separada é desligada.
- Ajustar `scripts/concursos-setup.ps1` / `backup-drive-setup.ps1` e o teste do comando.

### Etapa 2: cofre de configuração no Drive ✅ feita em 2026-11-27 (sem cifra)
- `backup:drive` copia `.env` para `G:\Meu Drive\Backups\financeiro\config\env_AAAAMMDD.txt`, só quando ele muda, guardando as 10 últimas.
- Também copia: `.claude/launch.json`, a lista das tarefas agendadas (`Export-ScheduledTask` das tarefas "Financeiro - *") e a config do Tailscale Funnel (o comando usado).
- Esses arquivos ficam no Drive pessoal, que é privado. Nunca vão para o git (repo público).
- Opcional: cifrar o `.env` com uma senha que só você sabe (7-Zip AES). Custo: sem a senha, o cofre não abre.

### Etapa 3: alerta quando o backup falha ✅ feita em 2026-11-27
- Os dois comandos mandam mensagem no **Telegram** (o bot já existe) quando falham: Drive inacessível, `pg_dump` com erro ou cópia vazia.
- Uma checagem diária ("o backup mais novo tem menos de 2 h?") avisa também quando a tarefa simplesmente não rodou, por exemplo com o PC desligado ou a tarefa desativada.
- Na tela inicial do app, mostrar um selo com a idade do último backup de cada banco (verde < 2 h, vermelho > 26 h).

### Etapa 4: restaurar com um comando + roteiro ✅ feita em 2026-11-27 (teste semanal, não mensal; roteiro em docs/recuperacao.md)
- `php artisan backup:restaurar` restaura no PC novo:
  - o `.sqlite` mais novo do Drive vai para `database/database.sqlite`;
  - o `.dump` mais novo vai para o Postgres, com `pg_restore --clean --if-exists`;
  - antes de sobrescrever, guarda o que existir.
- `docs/recuperacao.md`, passo a passo para um PC zerado:
  1. Instalar Google Drive para desktop, PHP, Composer, Node, Postgres 18 e Git.
  2. `git clone` do repositório, depois `composer install` e `npm ci && npm run build`.
  3. Copiar o `.env` do cofre (Etapa 2) e conferir `DADOS_DIR` e a letra do drive.
  4. Criar o usuário e o banco `concursos` no Postgres e rodar `php artisan backup:restaurar`.
  5. Rodar `scripts/servidor-setup.ps1`, `concursos-setup.ps1` e `backup-drive-setup.ps1` (tarefas agendadas).
  6. Clonar de novo `E:\leis` (sparse-checkout, ver a memória do material de resumo).
  7. Tailscale Funnel e webhook do Telegram (`telegram:set-webhook`).
- **Teste mensal automático:** `backup:testar` restaura o último dump e o último `.sqlite` num banco/arquivo temporário, conta as linhas das tabelas principais (capítulos, lei_notas, lei_grifos, tentativas, transações) e avisa pelo Telegram se algo vier vazio ou menor que no mês anterior.

### Etapa 5: código sempre no GitHub
- Rotina: commit + push ao fim de cada entrega (hoje há 27 arquivos sem commit).
- Rede de segurança: o `backup:drive` faz `git bundle` do repositório, incluindo o que não foi commitado (com `git stash create`), para `G:\Meu Drive\Backups\financeiro\codigo\`, 1x por dia e guardando 7 cópias. Assim, mesmo um trabalho que ainda não foi para o GitHub fica no Drive.

## Como ficou (etapas 1 e 3)
- Tarefa **"Financeiro - Backup Drive"** (de hora em hora): `backup:drive` faz o SQLite, o espelho dos arquivos e chama o `concursos:backup` (pg_dump). A tarefa diária antiga do Postgres foi removida.
- Retenção do Postgres: 24 recentes + 14 diárias + 8 semanais (`CONCURSOS_BACKUP_RECENTES/DIARIOS/SEMANAIS`).
- Falha de qualquer parte avisa no Telegram (`App\Services\AvisoBackup`). A mesma falha só é repetida a cada 6 h, e há aviso de novo quando volta a funcionar.
- Tarefa **"Financeiro - Conferir Backup"** (a cada 3 h, nos :30): `backup:conferir` checa a idade da cópia mais nova de cada banco. Se passou de 3 h (`BACKUP_ATRASO_HORAS`), roda o backup na hora. Se isso acontece em duas conferências seguidas, avisa que a tarefa de hora em hora não está rodando.
- Na tela inicial (só para admin), um selo com a idade do último backup de cada banco: verde até 2 h, amarelo, vermelho depois de 26 h.

## Como ficou (etapas 2 e 4)
- `backup:drive` guarda `.env`, `.claude/launch.json` e as tarefas "Financeiro - *" exportadas em `Backups\financeiro\config`, só quando mudam, com 10 versões de cada.
- `php artisan backup:restaurar [--so=principal|concursos] [--sqlite=] [--dump=] [--force]`: restaura as cópias mais novas e guarda antes o que existia.
- `php artisan backup:testar`: toda semana, tarefa "Financeiro - Testar Backup" (domingo 10:00). O SQLite é restaurado num arquivo temporário (integrity_check + contagem por tabela). O Postgres é restaurado no banco `concursos_restauracao`, criado uma vez por `scripts\backup-teste-setup.ps1`; sem esse banco, só confere se o dump abre e lista as tabelas. O resultado vai para o Telegram.

## Ordem sugerida
1 → 3 → 2 → 4 → 5. As etapas 1 e 3 fecham o risco de perder um dia de estudo sem perceber. A etapa 4 é a que prova que tudo funciona.

## Fora do escopo
- Pôr os bancos dentro do Drive: já recusado, porque o Drive para desktop não respeita os locks do SQLite e do Postgres.
- Uma segunda nuvem além do Drive. O Drive mantém versões e lixeira por 30 dias, e o PC tem o espelho dos arquivos. Se quiser uma terceira cópia fora do Google no futuro, dá para mandar os dumps também para um HD externo.
