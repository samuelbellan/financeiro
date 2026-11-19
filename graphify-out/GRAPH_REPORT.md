# Graph Report - financeiro  (2026-11-19)

## Corpus Check
- 294 files · ~613,954 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2040 nodes · 4870 edges · 181 communities (140 shown, 41 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 98 edges (avg confidence: 0.74)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6122da3a`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- .count
- EstudosController
- EventoAuxilioDTO
- OfxAccountDTO
- composer.json
- User
- scripts
- OfxImportService
- devDependencies
- nowMs
- formatDay
- StudyRoomService
- DatabaseSyncService
- CategoriasController
- StudyRoomsController
- esc
- OfxParserService
- TelegramWebhookController
- README.md
- Illuminate\Database\Seeder
- OfxParserServiceTest
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- StudySyncTest
- Controller
- Illuminate\Database\Schema\Blueprint
- tick
- Illuminate\Database\Eloquent\Model
- MidiaEstudosCommand
- EstudosApiController
- NetworkService
- StudySummaryService
- main.js
- GurujaMcpService
- DailyLog
- Exercise
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- GoogleAuthAndWhitelistTest
- TelegramWebhookRoutingTest
- .http
- EstudosApiTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- story.js
- WorkoutSession
- SalaryCalculatorService
- Cartao
- transaction-autocomplete.js
- SalaryProjectionTest
- Carbon\Carbon
- NotaFiscal
- Transacao
- Illuminate\Http\JsonResponse
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- web.php
- StudySyncService
- bootstrap/app.php
- psr-4
- bosses.js
- render
- require
- qrcode.min.js
- extra
- GearItem
- WorkoutPlan
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- GurujaConnectCommand
- OfxTransactionDTO
- importar_ofx.php
- TestCase
- EstudosGurujaTest
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- Illuminate\Database\Migrations\Migration
- Módulo de estudos estilo YPT (offline)
- ServidorDTO
- post-create-project-cmd
- SalaryController
- planImport
- AppServiceProvider
- estudos-sw.js
- Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)
- Illuminate\Support\Facades\Schema
- CartaoPrevisao
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo
- ExercisePersonalRecord
- Sincronização da Guruja no App de Estudos
- guruja.md

## God Nodes (most connected - your core abstractions)
1. `User` - 175 edges
2. `TestCase` - 68 edges
3. `Cartao` - 66 edges
4. `Transacao` - 64 edges
5. `CartaoCompra` - 46 edges
6. `CartaoParcela` - 42 edges
7. `GurujaMcpService` - 41 edges
8. `esc()` - 37 edges
9. `StudySyncTest` - 37 edges
10. `formatDuration()` - 34 edges

## Surprising Connections (you probably didn't know these)
- `gurujaPlan()` --indirect_call--> `a()`  [INFERRED]
  resources/js/estudos/main.js → public/js/qrcode.min.js
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (181 total, 41 thin omitted)

### Community 0 - ".count"
Cohesion: 0.06
Nodes (5): CartoesController, CartaoFatura, CreditCardReconciliationService, Illuminate\Support\Facades\File, MidiaEstudosCommandTest

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 3 - "OfxAccountDTO"
Cohesion: 0.20
Nodes (4): OfxAccountDTO, OfxStatementDTO, InvalidArgumentException, RuntimeException

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.07
Nodes (8): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, EstudosGurujaMcpTest, ManagersTest, StudyRoomsTest, TelegramWebhookTest, TransacaoUpdateDateTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxImportService"
Cohesion: 0.16
Nodes (7): OfxImportResultDTO, OfxImportService, PDO, PDO, PDOException, OfxImportServiceTest, PDO

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "nowMs"
Cohesion: 0.24
Nodes (20): uuid(), colorPicker(), exportBackup(), importBackup(), iso(), markSeen(), nowMs(), openSessionDialog() (+12 more)

### Community 10 - "formatDay"
Cohesion: 0.16
Nodes (22): folgaPicker(), formatBigHours(), formatDay(), longDay(), projPaces(), renderProjection(), renderProjHowMuch(), renderProjWhen() (+14 more)

### Community 12 - "DatabaseSyncService"
Cohesion: 0.29
Nodes (3): SyncDatabaseCommand, DatabaseSyncService, Command

### Community 15 - "esc"
Cohesion: 0.08
Nodes (59): cycleMark(), allTypes(), blockKey(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText() (+51 more)

### Community 17 - "TelegramWebhookController"
Cohesion: 0.12
Nodes (4): TelegramWebhookController, WhatsappLog, GeminiService, WhatsappMessageParser

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (12): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory (+4 more)

### Community 20 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.05
Nodes (71): applyTheme(), cssVar(), mountStatsCharts(), recentSessions(), renderHeatmap(), renderTrend(), shiftStats(), simpleBars() (+63 more)

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - "Illuminate\Console\Command"
Cohesion: 0.10
Nodes (9): BackupEstudosCommand, ExportProductionDataCommand, GurujaSyncCommand, ImportarHoleritesCommand, ImportOfxCommand, ResumoEstudosCommand, TelegramRegisterLocalCommand, TelegramSetWebhook (+1 more)

### Community 29 - "Controller"
Cohesion: 0.15
Nodes (6): AllowedEmailController, AuthController, Controller, AllowedGoogleEmail, Illuminate\Http\RedirectResponse, Laravel\Socialite\Facades\Socialite

### Community 31 - "tick"
Cohesion: 0.15
Nodes (31): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound(), avatarStage() (+23 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.07
Nodes (10): CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, StretchingLog, UserBodyMetric, WorkoutSessionExercise, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory (+2 more)

### Community 36 - "EstudosApiController"
Cohesion: 0.27
Nodes (3): EstudosApiController, CarbonImmutable, Carbon\CarbonImmutable

### Community 40 - "StudySummaryService"
Cohesion: 0.17
Nodes (4): CarbonImmutable, StudySummaryService, Illuminate\Database\ConnectionInterface, ResumoEstudosCommandTest

### Community 41 - "main.js"
Cohesion: 0.06
Nodes (61): activeSubjects(), apiTokensCall(), canvasToPngBlob(), charts, chartTooltip, closeFocus(), createApiToken(), currentRoom() (+53 more)

### Community 43 - "DailyLog"
Cohesion: 0.08
Nodes (7): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile

### Community 45 - "Exercise"
Cohesion: 0.08
Nodes (5): Exercise, WorkoutGoal, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 66 - ".http"
Cohesion: 0.13
Nodes (4): JsonResponse, TelegramService, PendingRequest, FitnessAiModuleTest

### Community 74 - "story.js"
Cohesion: 0.25
Nodes (14): canonicalSubjectName(), consolidateSubjects(), dayMidnightMs(), ensureFontsLoaded(), fillRoundRect(), formatCompactDuration(), formatHoursMinutes(), formatStoryDate() (+6 more)

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "Cartao"
Cohesion: 0.06
Nodes (10): Cartao, CartaoCompra, CartaoParcela, CreditCardService, Carbon, CartaoCompraTest, CreditCardClosingTest, CreditCardDeleteTest (+2 more)

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Carbon\Carbon"
Cohesion: 0.10
Nodes (16): Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Http\Client\Request, Illuminate\Support\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http (+8 more)

### Community 84 - "NotaFiscal"
Cohesion: 0.10
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 88 - "Transacao"
Cohesion: 0.08
Nodes (5): Conta, Transacao, TransactionDeduplicationService, StatementImportTest, TransactionDeduplicationTest

### Community 89 - "Illuminate\Http\JsonResponse"
Cohesion: 0.15
Nodes (4): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 96 - "web.php"
Cohesion: 0.25
Nodes (3): TvWidgetController, HomeController, Illuminate\Support\Facades\Route

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 102 - "bosses.js"
Cohesion: 0.16
Nodes (21): BOSSES, bossSvg(), DEFAULT_COLORS, ensureModelViewer(), escSvg(), ground(), koStars(), linear() (+13 more)

### Community 104 - "render"
Cohesion: 0.13
Nodes (33): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+25 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.06
Nodes (11): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Mockery\MockInterface, BackupEstudosCommandTest, BodyTrackerTest, DatabaseSyncTest, ExampleTest, ImportOfxCommandTest (+3 more)

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

### Community 152 - "CartaoPrevisao"
Cohesion: 0.07
Nodes (10): ExportController, CartaoPrevisao, Categoria, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService, TransactionSuggestionService, Illuminate\Support\Facades\URL (+2 more)

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

## Knowledge Gaps
- **179 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+174 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **41 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `TelegramWebhookController`, `Illuminate\Database\Seeder`, `CartaoPrevisao`, `Illuminate\Console\Command`, `StudySyncTest`, `Controller`, `Illuminate\Database\Eloquent\Model`, `NetworkService`, `StudySummaryService`, `Exercise`, `GoogleAuthAndWhitelistTest`, `TelegramWebhookRoutingTest`, `.http`, `EstudosApiTest`, `Cartao`, `SalaryProjectionTest`, `Carbon\Carbon`, `NotaFiscal`, `Transacao`, `SalaryProjectionTest.php`, `GurujaConnectCommand`, `TestCase`, `EstudosGurujaTest`?**
  _High betweenness centrality (0.140) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `.count`, `.http`, `User`, `EstudosApiTest`, `NetworkService`, `StudySummaryService`, `GoogleAuthAndWhitelistTest`, `Transacao`, `Cartao`, `Exercise`, `SalaryProjectionTest`, `Carbon\Carbon`, `NotaFiscal`, `CartaoPrevisao`, `EstudosGurujaTest`, `StudySyncTest`, `TelegramWebhookRoutingTest`, `SalaryProjectionTest.php`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `Transacao` connect `Transacao` to `Illuminate\Database\Eloquent\Model`, `User`, `Cartao`, `TelegramWebhookController`, `Carbon\Carbon`, `CartaoPrevisao`, `TestCase`, `Illuminate\Http\Request`, `StatementImportService`, `TelegramWebhookRoutingTest`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _179 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `.count` be split into smaller, more focused modules?**
  _Cohesion score 0.06398809523809523 - nodes in this community are weakly interconnected._
- **Should `EstudosController` be split into smaller, more focused modules?**
  _Cohesion score 0.14705882352941177 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._