# Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"

## Pergunta original
Criar um app desktop do módulo de estudos para não esperar o Render abrir.

## Conclusão
Não precisa de app desktop. O módulo já é uma PWA offline-first (IndexedDB + service worker, instalável pelo Chrome/Edge).
A espera vinha de dois problemas reais, ambos resolvidos:

1. **Sincronização travada.** A importação de ~3.300 sessões encheu a fila. Cada lote de 400 itens fazia ~6 consultas por item,
   passava de 140 s e o Render reiniciava a instância (502/503), reenviando o mesmo lote a cada 5 min.
2. **Banco longe do servidor.** Render em Oregon e Neon em São Paulo: cada requisição custava de 3 a 6 s só em latência de banco.

## Decisões tomadas
| Decisão | Justificativa |
|---|---|
| Não criar app desktop (Electron/Tauri) | A PWA já abre do cache e funciona offline; o gargalo era no servidor |
| Sync em lote no servidor (`whereIn` + `upsert`) | Resolve a causa: ~10 consultas por lote em vez de ~2.400 |
| Lote de 100 no cliente, cai pela metade após falha | Um lote pesado não trava mais a fila para sempre |
| Mensagem de erro mostra o HTTP | Antes toda falha virava "Falha ao sincronizar" genérica |
| Deploy de tudo junto (sync + observação + login Google) | Escolha do usuário; login com senha continua como alternativa |
| Banco migrado para Neon `financeiro-oregon` (AWS us-west-2) | Mesma região do Render; latência cai ~10× |

Commits: `16c10ae` (sync em lote + observação), `25a047a` (login Google).

## Resultados medidos
| Medida | Antes | Depois |
|---|---|---|
| Lote de sync (400 itens) | 502 após 146 s | 200 em ~11 s (com banco em SP) |
| Fila pendente | 3.370 itens | 0 |
| `/login` | 3,46 s | 0,42 s |
| `/estudos/sync` sem enviar nada | ~6,2 s | ~0,35 s |

Migração verificada por contagem + hash MD5 das 48 tabelas; só a tabela `sessions` (logins) diferiu após a troca, como esperado.

## Suposições
- O projeto Neon de São Paulo (`financeiro`) não recebe mais escritas. Ele foi conferido logo após a troca, mas não depois.
- O plano grátis do Neon (1 GB) comporta o crescimento (hoje ~13 MB).

## Riscos e pontos em aberto
- **Login Google**: falta configurar `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no Render e apagar o `client_secret_*.json` da pasta Downloads.
- **Migration com falha não para o deploy**: `docker/entrypoint.sh` usa `migrate --force || echo`, então o app sobe sem as colunas novas. Sugestão: falhar o deploy.
- **Banco antigo**: apagar o projeto Neon de São Paulo e o `.env.neon_backup` depois de alguns dias estável. Até lá, ele serve de rollback (basta voltar o `DATABASE_URL`).
- **Cold start do Render free (~50 s)** continua. A PWA abre do cache nesse intervalo; se incomodar, a alternativa é um plano pago ou um ping periódico.
- O `.env` local não aponta para o banco novo (`pgsql_cloud` sem `CLOUD_DB_*`). Atualizar se for usar a sincronização local → nuvem.

## Próximos passos
1. Configurar as credenciais do Google no Render e testar o login.
2. Fazer o `entrypoint.sh` interromper o deploy em erro de migration.
3. Depois de ~1 semana estável, apagar o projeto Neon de São Paulo e o `.env.neon_backup`.
