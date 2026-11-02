# Módulo de estudos estilo YPT (offline)

Decisões tomadas na sabatina de 31/10/2026, já implementadas.

## Decisões

| # | Decisão | Por quê |
|---|---|---|
| 1 | PWA offline: o cronômetro roda no aparelho e sincroniza quando houver internet | O YPT é usado longe do PC |
| 2 | Histórico antigo (`study_goals`/`study_logs`) descartado, com backup JSON antes do drop | Recomeço limpo; o backup permite rollback |
| 3 | MVP: matérias com cronômetro, total + meta diária, linha do tempo de 10 min, estatísticas dia/semana/mês + heatmap + sequência, D-Day | Núcleo do YPT. Tarefas, pausas e modo foco ficam para a v2; ranking fica fora |
| 4 | Métricas: total, máximo de foco, início, término, distribuição por matéria, dias estudados, média diária | Métricas individuais do YPT + média do módulo antigo |
| 5 | O dia vira à 1h (configurável; era 3h, mudou após a análise do histórico do YPT); o corte é calculado na leitura | O estudo se concentra entre 3h e 7h e nunca entre 22h e 2h: a virada à 1h não corta sessões reais |
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
| 17 | Origem `importado` para o histórico do YPT: conta em total, matérias, calendário, sequência e médias; fica fora de máximo de foco, início/término, linha do tempo, estudo × outros e sobreposição | O YPT só fornece totais por dia/matéria, sem horários reais |
| 18 | Métricas P1 do YPT: comparação com o período anterior até o mesmo ponto, estudo × outros, intervalos na linha do tempo, calendário com horas e faixas 0/4/7/10/12 h, grades de semanas e meses, aba Tendência com filtro por matéria, média por dia da semana, estudo por hora do dia | Lista priorizada a partir dos prints do YPT; P2 fica para depois |

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

## Avatares

O avatar evolui com o total estudado no dia (estágios em 0 · 30 min · 1h · 2h · 3h · 4h · 5h) e recomeça
na virada do dia. Aparece no Cronômetro e no modo foco, com animação ao subir de estágio; a escolha fica em
Ajustes → Avatar e sincroniza entre aparelhos (`study_settings.avatar`).

Para criar um avatar novo, adicione uma entrada em `AVATARS` (`resources/js/estudos/avatars.js`): id em
minúsculas/números/hífen, nome e 7 desenhos em pixel art no viewBox `0 0 48 48` (use o helper `P`); avatares vetoriais (viewBox `0 0 120 120`, animações `v-*` em `estudos.css`) ficam em `resources/js/estudos/avatars-vetor.js`. Avatares em ilustração pronta (7 imagens WebP 480x480 em `resources/images/estudos/<id>/0.webp` a `6.webp`, importadas pelo Vite) ficam em `resources/js/estudos/avatars-imagem.js`. O teste
`tests/js/estudos/avatars.test.js` confere que todo avatar tem os 7 estágios.

## Backup no PC

**Exportar backup (principal):** em Ajustes → Backup, o app baixa um JSON com tudo o que está no
aparelho (matérias, sessões, D-Days, preferências, inclusive excluídos). Funciona offline e não
precisa de credenciais. A data do último export aparece na mesma tela.

**Backup automático (opcional):** só funciona se o `.env` do PC tiver `CLOUD_DATABASE_URL` (string de conexão do Neon).
Atenção: essa variável também ativa o modo nuvem do sync do financeiro. Sem ela, o comando sai sem erro.

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

Métricas P2: período personalizado, acumulado por matéria, início/fim ao longo do período, consistência com medianas, linha do tempo de vários dias.


Lista de tarefas, tempo de pausa, modo foco em tela cheia, resolução automática de cronômetro duplo.
