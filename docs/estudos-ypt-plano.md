# Módulo de estudos estilo YPT (offline)

Decisões tomadas na sabatina de 31/10/2026, já implementadas.

## Decisões

| # | Decisão | Por quê |
|---|---|---|
| 1 | PWA offline: o cronômetro roda no aparelho e sincroniza quando houver internet | O YPT é usado longe do PC |
| 2 | Histórico antigo (`study_goals`/`study_logs`) descartado, com backup JSON antes do drop | Recomeço limpo; o backup permite rollback |
| 3 | MVP: matérias com cronômetro, total + meta diária, linha do tempo de 10 min, estatísticas dia/semana/mês + heatmap + sequência, D-Day | Núcleo do YPT. Tarefas, pausas e modo foco ficam para a v2; ranking fica fora |
| 4 | Métricas: total, máximo de foco, início, término, distribuição por matéria, dias estudados, média diária | Métricas individuais do YPT + média do módulo antigo |
| 5 | O dia vira às 3h (configurável); o corte é calculado na leitura | Estudo de madrugada não quebra a sequência; mudar a virada recalcula o histórico |
| 6 | Sessão = início/fim, uma matéria por vez, sem tolerância; o tempo vem do timestamp | Regra do YPT; sobrevive a tela bloqueada e app fechado |
| 7 | Sessão aberta há mais de 3h (configurável) fica `suspeita` e pede confirmação ao reabrir | Protege os números sem cortar sessões longas reais |
| 8 | Entrada manual permitida (`origem = manual`): conta no total, não no máximo de foco nem no início/término | A vida real cabe, e a métrica de foco continua honesta |
| 9 | A PWA roda na URL da nuvem (HTTPS) | Service worker exige HTTPS; o IndexedDB é separado por origem |
| 10 | Vários aparelhos: UUID no cliente, exclusão lógica, vence quem sincronizar por último (relógio do servidor) | Não depende do relógio do celular |
| 11 | Tabelas: `study_subjects`, `study_sessions`, `study_settings`, `study_ddays`. Excluir matéria com sessões = arquivar | Não se perde hora estudada |
| 12 | `POST /estudos/sync` com fila no cliente, cursor e espera crescente; autenticação pela sessão web; `GET /estudos/token` renova o CSRF | Sem Sanctum; 401 e 419 tratados |
| 13 | JS puro + Vite + `idb` + Chart.js empacotado + service worker escrito à mão (escopo `/estudos`) | Nada de CDN; o SW não afeta o resto do sistema |
| 14 | Testes: Vitest no motor de métricas (`npm test`), PHPUnit no sync, roteiro manual de modo avião | Os bugs prováveis estão no corte do dia e no fuso |
| 15 | A nuvem é a única fonte da verdade para estudos: as tabelas `study_*` ficam fora do `DatabaseSyncService` e do export; fora da nuvem, `/estudos` redireciona para `CLOUD_APP_URL` | Evita dois mecanismos de sync (o atual faz upsert cego e usa `setval`, que não funciona com UUID) |
| 16 | Fuso padrão = fuso do aparelho (editável em Ajustes) | O PC está em America/Cuiaba, não em São Paulo |

## Onde está o código

- Servidor: `app/Http/Controllers/EstudosController.php`, `app/Services/StudySyncService.php`, migration `2026_10_31_150000_create_study_tracker_tables.php`
- Cliente: `resources/js/estudos/` (`metrics.js` regras e métricas, `db.js` IndexedDB e fila, `sync.js`, `main.js` interface), `resources/css/estudos.css`
- PWA: `resources/views/estudos/app.blade.php` (casca), `public/estudos-sw.js`, `public/estudos.webmanifest`, `public/img/estudos/`
- Testes: `tests/Feature/StudySyncTest.php`, `tests/js/estudos/metrics.test.js`

## Riscos conhecidos

- **iOS**: o Safari pode apagar o IndexedDB de uma PWA que fica cerca de 7 dias sem abrir. O que já sincronizou volta do servidor; o que estava pendente se perde.
- **Render gratuito hiberna**: o primeiro sync do dia pode levar de 30 a 60 s. O cronômetro não depende da rede.
- **Cronômetro aberto em dois aparelhos** só gera aviso de sobreposição (v2: encerrar o mais antigo automaticamente).
- **Backup do módulo antigo na nuvem**: a migration grava o JSON em `storage/app/backups/` do container, que é efêmero no Render. Exporte as tabelas antigas da nuvem antes do deploy se quiser guardar.

## Backup no PC

`php artisan estudos:backup` baixa da nuvem (só leitura) as tabelas `study_*` para
`storage/app/backups/estudos/estudos_AAAAMMDD_HHMMSS.json` e mantém os 30 mais recentes (`--keep=N`).
O `iniciar.bat` roda esse comando em segundo plano a cada inicialização; o resultado fica em
`storage/logs/estudos-backup.log`.

## Roteiro de teste manual (modo avião)

1. Abrir `https://<nuvem>/estudos` no celular, fazer login e instalar ("Adicionar à tela inicial").
2. Criar uma matéria e esperar o ✓ Sincronizado.
3. Ativar o modo avião, abrir o app instalado e cronometrar 5 min.
4. Bloquear a tela, desbloquear, reabrir o app e parar o cronômetro.
5. Desligar o modo avião e conferir ↻ → ✓.
6. Abrir no PC e conferir a sessão e as estatísticas.

## v2

Lista de tarefas, tempo de pausa, modo foco em tela cheia, resolução automática de cronômetro duplo.
