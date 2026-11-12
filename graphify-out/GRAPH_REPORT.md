# Graph Report - financeiro  (2026-11-12)

## Corpus Check
- 298 files · ~528,436 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1993 nodes · 4647 edges · 181 communities (141 shown, 40 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 99 edges (avg confidence: 0.76)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `115ca43a`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- NotaFiscal
- EventoAuxilioDTO
- PhotoController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- Illuminate\Database\Eloquent\Relations\BelongsTo
- CreditCardReconciliationService
- FiscalConcurso
- FiscalNoticia
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- formatDuration
- Cartao
- TelegramWebhookController
- README.md
- WhatsappMessageParserTest
- Illuminate\Support\Facades\Schema
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- .count
- StudySyncTest
- AllowedGoogleEmail
- main.js
- Illuminate\Database\Eloquent\Model
- Illuminate\Database\Migrations\Migration
- Transacao
- StudySummaryService
- NetworkService
- Categoria
- renderTimeline
- DailyLog
- Illuminate\Http\JsonResponse
- Exercise
- projection.js
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- BodyTrackerTest
- GearItem
- .http
- User.php
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- WorkoutSession
- SalaryCalculatorService
- CartaoCompra
- transaction-autocomplete.js
- SalaryProjectionTest
- Carbon\Carbon
- NotaFiscalItem
- Illuminate\Database\Schema\Blueprint
- TreinosController.php
- FiscalModuleTest
- StudySyncService
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- toMs
- bootstrap/app.php
- psr-4
- bosses.js
- render
- require
- qrcode.min.js
- extra
- jornada.js
- TvWidgetEndpointTest
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- MidiaEstudosCommandTest
- TestCase
- ProgressPhoto
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- TelegramWebhookTest
- Módulo de estudos estilo YPT (offline)
- ServidorDTO
- post-create-project-cmd
- SalaryController
- FiscalNewsAiService
- planImport
- AppServiceProvider
- estudos-sw.js
- Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)
- BackupEstudosCommandTest
- audio.js
- TransacaoUpdateDateTest
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- MidiaEstudosCommand
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo

## God Nodes (most connected - your core abstractions)
1. `User` - 164 edges
2. `Cartao` - 66 edges
3. `TestCase` - 66 edges
4. `Transacao` - 63 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `StudySyncTest` - 35 edges
9. `PhotoController` - 34 edges
10. `CreditCardReconciliationService` - 32 edges

## Surprising Connections (you probably didn't know these)
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `CreditCardReconciliationTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardReconciliationTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php

## Import Cycles
- None detected.

## Communities (181 total, 40 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.19
Nodes (6): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.07
Nodes (6): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, CreditCardDeleteTest, PhotoModuleTest, StudyRoomsTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (18): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, getPdoConnection() (+10 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.09
Nodes (6): ImportarHoleritesCommand, CartaoFatura, CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 12 - "FiscalNoticia"
Cohesion: 0.13
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 15 - "formatDuration"
Cohesion: 0.13
Nodes (39): cycleMark(), activeSubjects(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText(), colorVars() (+31 more)

### Community 16 - "Cartao"
Cohesion: 0.11
Nodes (5): CartoesController, Cartao, CreditCardService, Carbon, CreditCardClosingTest

### Community 17 - "TelegramWebhookController"
Cohesion: 0.17
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.08
Nodes (52): mountStatsCharts(), renderHeatmap(), renderTrend(), trendSessions(), addDays(), allTimeStats(), amountBetween(), buildDaily() (+44 more)

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - ".count"
Cohesion: 0.09
Nodes (11): BackupEstudosCommand, ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand (+3 more)

### Community 29 - "AllowedGoogleEmail"
Cohesion: 0.13
Nodes (5): AllowedEmailController, AllowedGoogleEmail, Mockery, GoogleAuthAndWhitelistTest, ManagersTest

### Community 31 - "main.js"
Cohesion: 0.08
Nodes (50): avatarStage(), avatarSvg(), applyTheme(), avatarBlock(), charts, chartTooltip, checkRestAlert(), cssVar() (+42 more)

### Community 35 - "Transacao"
Cohesion: 0.12
Nodes (3): Transacao, TransactionDeduplicationService, TransactionDeduplicationTest

### Community 36 - "StudySummaryService"
Cohesion: 0.14
Nodes (6): ResumoEstudosCommand, StudySummaryService, Carbon\CarbonImmutable, CarbonImmutable, Illuminate\Database\ConnectionInterface, ResumoEstudosCommandTest

### Community 40 - "Categoria"
Cohesion: 0.09
Nodes (6): CategoriasController, Categoria, Subcategoria, CategorySanitizer, TransactionSuggestionService, TransactionSuggestionTest

### Community 41 - "renderTimeline"
Cohesion: 0.15
Nodes (21): allTypes(), blockKey(), colorOf(), hoursOf(), liveSessions(), mountDonut(), nameOf(), objectiveLabel() (+13 more)

### Community 43 - "DailyLog"
Cohesion: 0.10
Nodes (5): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.07
Nodes (9): BodyTrackerController, DatabaseSyncController, EstudosController, StudyRoomsController, DatabaseSyncService, Carbon, StudyRoomService, Illuminate\Http\JsonResponse (+1 more)

### Community 45 - "Exercise"
Cohesion: 0.06
Nodes (7): Exercise, WorkoutPlan, WorkoutPlanItem, WorkoutSessionExercise, WorkoutSet, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 46 - "projection.js"
Cohesion: 0.19
Nodes (19): folgaPicker(), formatBigHours(), longDay(), projInputs(), projPaces(), renderProjection(), renderProjHowMuch(), renderProjWhen() (+11 more)

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 63 - "GearItem"
Cohesion: 0.08
Nodes (4): GearItem, RunningLog, RunningSplit, static

### Community 66 - ".http"
Cohesion: 0.09
Nodes (5): TelegramSetWebhook, TelegramService, PendingRequest, CreditCardReconciliationTest, TelegramWebhookRoutingTest

### Community 69 - "User.php"
Cohesion: 0.25
Nodes (5): UserFactory, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Notifications\Notifiable

### Community 77 - "CartaoCompra"
Cohesion: 0.12
Nodes (8): CartaoCompra, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Carbon\Carbon"
Cohesion: 0.11
Nodes (13): Carbon\Carbon, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, Illuminate\Support\Facades\Storage, Illuminate\Support\Facades\Validator (+5 more)

### Community 84 - "NotaFiscalItem"
Cohesion: 0.14
Nodes (3): MercadoController, NotaFiscalItem, GeminiService

### Community 87 - "TreinosController.php"
Cohesion: 0.13
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (14): TvWidgetController, AuthController, Controller, ExportController, FinancasController, HomeController, PhotoAuth, Closure (+6 more)

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 97 - "toMs"
Cohesion: 0.18
Nodes (16): recentSessions(), renderSyncBadge(), findOverlaps(), lastStopped(), restAlertStage(), restMaxGapMs(), sessionEnd(), splitSession() (+8 more)

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 102 - "bosses.js"
Cohesion: 0.20
Nodes (9): BOSSES, bossSvg(), eye(), feathers(), hydraHead(), koStars(), stone(), url() (+1 more)

### Community 104 - "render"
Cohesion: 0.14
Nodes (37): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+29 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 108 - "jornada.js"
Cohesion: 0.36
Nodes (7): BOSS_HP, bossMaxHp(), JORNADA_PADRAO, jornadaState(), normalizeJornada(), objectiveProgressMs(), tickJornada()

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.06
Nodes (12): Conta, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Mockery\MockInterface, CartaoCompraTest, DatabaseSyncTest, ExampleTest, FitnessAiModuleTest (+4 more)

### Community 124 - "avatars-vetor.js"
Cohesion: 0.08
Nodes (27): AVATAR_LIMITES_MIN, AVATAR_PADRAO, AVATARS, BICHINHO, eggCrack, happyFace(), AVATARS_IMAGEM, FAMILIA (+19 more)

### Community 125 - "Sabatina 2026-11-01: widget flutuante do cronômetro de estudos"
Cohesion: 0.25
Nodes (7): Decisões tomadas, Implementação, Pergunta original, Próximos passos (se quiser evoluir), Riscos e pontos em aberto, Sabatina 2026-11-01: widget flutuante do cronômetro de estudos, Suposições

### Community 135 - "Módulo de estudos estilo YPT (offline)"
Cohesion: 0.20
Nodes (9): Avatares, Backup no PC, Decisões, Jornada dos Bosses, Módulo de estudos estilo YPT (offline), Onde está o código, Riscos conhecidos, Roteiro de teste manual (modo avião) (+1 more)

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 145 - "planImport"
Cohesion: 0.48
Nodes (4): normalize(), planImport(), toUuid(), empty

### Community 148 - "estudos-sw.js"
Cohesion: 0.40
Nodes (3): shellResponse(), STATIC, withTimeout()

### Community 149 - "Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)"
Cohesion: 0.29
Nodes (6): Calculadora de projeção, Decisões, Gerador de mídias, Riscos, Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram), Suposições

### Community 153 - "audio.js"
Cohesion: 0.61
Nodes (7): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound()

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

## Knowledge Gaps
- **165 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+160 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **40 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `TelegramWebhookTest`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Cartao`, `TelegramWebhookController`, `BackupEstudosCommandTest`, `TransacaoUpdateDateTest`, `StudySyncTest`, `AllowedGoogleEmail`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `StudySummaryService`, `NetworkService`, `Categoria`, `Exercise`, `BodyTrackerTest`, `.http`, `User.php`, `CartaoCompra`, `SalaryProjectionTest`, `Carbon\Carbon`, `FiscalModuleTest`, `Illuminate\Http\Request`, `SalaryProjectionTest.php`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.121) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `NotaFiscal`, `User`, `TelegramWebhookTest`, `Cartao`, `WhatsappMessageParserTest`, `BackupEstudosCommandTest`, `TransacaoUpdateDateTest`, `StudySyncTest`, `AllowedGoogleEmail`, `Transacao`, `StudySummaryService`, `NetworkService`, `Categoria`, `Exercise`, `BodyTrackerTest`, `.http`, `CartaoCompra`, `SalaryProjectionTest`, `Carbon\Carbon`, `FiscalModuleTest`, `SalaryProjectionTest.php`, `TvWidgetEndpointTest`, `MidiaEstudosCommandTest`?**
  _High betweenness centrality (0.050) - this node is a cross-community bridge._
- **Why does `Transacao` connect `Transacao` to `Illuminate\Database\Eloquent\Model`, `NotaFiscal`, `.http`, `TelegramWebhookTest`, `TransacaoUpdateDateTest`, `Categoria`, `CartaoCompra`, `TelegramWebhookController`, `Carbon\Carbon`, `NotaFiscalItem`, `TestCase`, `Illuminate\Http\Request`, `StatementImportService`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _165 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.06717687074829932 - nodes in this community are weakly interconnected._