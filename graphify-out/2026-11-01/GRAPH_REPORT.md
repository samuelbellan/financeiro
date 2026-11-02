# Graph Report - financeiro  (2026-10-31)

## Corpus Check
- 250 files · ~390,976 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1600 nodes · 3596 edges · 167 communities (125 shown, 42 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.76)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `70cf2365`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- NotaFiscal
- SalaryProjectionTest.php
- PhotoController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- CartoesController
- .count
- FiscalConcurso
- FiscalNoticia
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- main.js
- Cartao
- TelegramWebhookController
- README.md
- Categoria
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- StudySyncTest
- web.php
- render
- Illuminate\Database\Eloquent\Model
- Transacao
- WorkoutSessionExercise
- Illuminate\Support\Facades\Schema
- NetworkService
- CategoriasController
- openSessionDialog
- Illuminate\Database\Schema\Blueprint
- DailyLog
- Illuminate\Http\JsonResponse
- Exercise
- Illuminate\Database\Migrations\Migration
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- ProgressPhoto
- GearItem
- .http
- SalaryProjectionTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- db.js
- WorkoutSession
- ExercisePersonalRecord
- CartaoParcela
- transaction-autocomplete.js
- Illuminate\Support\Facades\Log
- WorkoutPlan
- OfxImportService
- StudySyncService
- Illuminate\Http\Request
- StatementImportService
- BodyTrackerTest
- Illuminate\Database\Eloquent\Relations\BelongsTo
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- nowMs
- require
- qrcode.min.js
- extra
- DatabaseSyncService
- TvWidgetEndpointTest
- OfxAccountDTO
- TestCase
- OfxParserServiceTest
- avatars.js
- SalaryCalculatorService
- FiscalNewsAiService
- update_main.js
- Módulo de estudos estilo YPT (offline)
- post-create-project-cmd
- SalaryController
- OfxParserService.php
- planImport
- preview_avatars.js
- AppServiceProvider
- estudos-sw.js
- OfxImportServiceTest
- QualificacaoPermanenteDTO
- QualificacaoTemporariaDTO
- ServidorDTO
- audio.js
- keywords
- importar_ofx.php
- test_avatars.js
- update_css.js

## God Nodes (most connected - your core abstractions)
1. `User` - 127 edges
2. `Cartao` - 66 edges
3. `Transacao` - 60 edges
4. `TestCase` - 54 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `PhotoController` - 34 edges
9. `CreditCardReconciliationService` - 32 edges
10. `DailyLog` - 27 edges

## Surprising Connections (you probably didn't know these)
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (167 total, 42 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.25
Nodes (5): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 1 - "NotaFiscal"
Cohesion: 0.10
Nodes (5): MercadoController, NotaFiscal, NotaFiscalItem, Illuminate\Support\Facades\Auth, MercadoModuleTest

### Community 2 - "SalaryProjectionTest.php"
Cohesion: 0.14
Nodes (6): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 5 - "User"
Cohesion: 0.08
Nodes (5): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, CreditCardDeleteTest, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 12 - "FiscalNoticia"
Cohesion: 0.13
Nodes (4): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig, FiscalTelegramNotifierService

### Community 15 - "main.js"
Cohesion: 0.11
Nodes (41): cards(), charts, chartTooltip, closeFocus(), colorOf(), cssVar(), daily(), dialog (+33 more)

### Community 16 - "Cartao"
Cohesion: 0.08
Nodes (9): Cartao, CartaoCompra, CreditCardService, Carbon, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, CartaoCompraTest, CreditCardClosingTest (+1 more)

### Community 17 - "TelegramWebhookController"
Cohesion: 0.18
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "Categoria"
Cohesion: 0.10
Nodes (5): Categoria, TransactionSuggestionService, WhatsappMessageParser, TransactionSuggestionTest, WhatsappMessageParserTest

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.16
Nodes (27): renderTrend(), addDays(), amountBetween(), compareWithPrevious(), comparisonRanges(), comparisonSeries(), dayGaps(), daysBetween() (+19 more)

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
Cohesion: 0.09
Nodes (11): BackupEstudosCommand, ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand (+3 more)

### Community 29 - "web.php"
Cohesion: 0.22
Nodes (4): AuthController, HomeController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 31 - "render"
Cohesion: 0.23
Nodes (21): avatarStage(), avatarSvg(), activeSubjects(), avatarBlock(), ddayChips(), esc(), metaBar(), nextStageText() (+13 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (7): StretchingLog, UserBodyMetric, WorkoutGoal, WorkoutPlanItem, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model

### Community 35 - "Transacao"
Cohesion: 0.07
Nodes (9): Conta, Transacao, Illuminate\Support\Facades\Cache, Mockery, Mockery\MockInterface, TelegramOfxDocumentTest, TelegramWebhookRoutingTest, TelegramWebhookTest (+1 more)

### Community 41 - "openSessionDialog"
Cohesion: 0.19
Nodes (19): liveSessions(), openSessionDialog(), renderSyncBadge(), renderTimeline(), dayBounds(), findOverlaps(), formatClock(), isSuspicious() (+11 more)

### Community 43 - "DailyLog"
Cohesion: 0.08
Nodes (6): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService, FitnessAiModuleTest

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.12
Nodes (6): TvWidgetController, BodyTrackerController, Controller, EstudosController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 59 - "ProgressPhoto"
Cohesion: 0.10
Nodes (6): ProgressPhoto, ExerciseCatalogSeeder, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage, Illuminate\Support\Str, Pdo\Mysql

### Community 63 - "GearItem"
Cohesion: 0.09
Nodes (8): GearItem, UserFactory, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Notifications\Notifiable, Illuminate\Support\Facades\Hash, static

### Community 66 - ".http"
Cohesion: 0.18
Nodes (3): TelegramSetWebhook, TelegramService, PendingRequest

### Community 74 - "db.js"
Cohesion: 0.27
Nodes (16): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+8 more)

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "CartaoParcela"
Cohesion: 0.13
Nodes (8): ExportController, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Illuminate\Support\Facades\Log"
Cohesion: 0.17
Nodes (3): GeminiService, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 88 - "OfxImportService"
Cohesion: 0.20
Nodes (7): OfxImportResultDTO, OfxImportService, PDO, PDO, PDOException, Throwable, ZipArchive

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.13
Nodes (3): FinancasController, TransactionDeduplicationService, Illuminate\Http\Request

### Community 96 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.18
Nodes (3): CartaoFaturaFechamento, SalaryProfile, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 97 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 104 - "nowMs"
Cohesion: 0.29
Nodes (14): checkSuspicious(), colorPicker(), exportBackup(), importBackup(), iso(), markSeen(), nowMs(), openDialog() (+6 more)

### Community 105 - "require"
Cohesion: 0.40
Nodes (5): require, barryvdh/laravel-dompdf, laravel/framework, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 122 - "TestCase"
Cohesion: 0.07
Nodes (11): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Validator, BackupEstudosCommandTest, CelularAccessTest, DatabaseSyncTest, ExampleTest (+3 more)

### Community 123 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 124 - "avatars.js"
Cohesion: 0.25
Nodes (9): AVATAR_LIMITES_MIN, AVATAR_PADRAO, AVATARS, BICHINHO, eggCrack, happyFace(), P(), PLANTA (+1 more)

### Community 134 - "update_main.js"
Cohesion: 0.20
Nodes (9): avatarBlockReplacement, avatarBlockTarget, content, formReplacement, formTarget, stopSessionReplacement, stopSessionTarget, submitReplacement (+1 more)

### Community 135 - "Módulo de estudos estilo YPT (offline)"
Cohesion: 0.22
Nodes (8): Avatares, Backup no PC, Decisões, Módulo de estudos estilo YPT (offline), Onde está o código, Riscos conhecidos, Roteiro de teste manual (modo avião), v2

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 144 - "OfxParserService.php"
Cohesion: 0.29
Nodes (3): OfxTransactionDTO, InvalidArgumentException, RuntimeException

### Community 145 - "planImport"
Cohesion: 0.48
Nodes (4): normalize(), planImport(), toUuid(), empty

### Community 146 - "preview_avatars.js"
Cohesion: 0.38
Nodes (6): BICHINHO_STAGES, eggCrack, happyFace(), P(), PLANTA_STAGES, SHADOW()

### Community 148 - "estudos-sw.js"
Cohesion: 0.40
Nodes (3): shellResponse(), STATIC, withTimeout()

### Community 153 - "audio.js"
Cohesion: 0.80
Nodes (4): getAudioContext(), playFocusEndSound(), playLevelUpSound(), playNote()

### Community 154 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

## Knowledge Gaps
- **134 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+129 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **42 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryProjectionTest.php`, `FiscalConcurso`, `Cartao`, `TelegramWebhookController`, `Categoria`, `StudySyncTest`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `DailyLog`, `Illuminate\Http\JsonResponse`, `Exercise`, `ProgressPhoto`, `GearItem`, `SalaryProjectionTest`, `CartaoParcela`, `Illuminate\Support\Facades\Log`, `BodyTrackerTest`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.128) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `FiscalNewsAiService`, `FiscalNoticia`, `FiscalConcursoDataService`, `FiscalNewsCrawlerService`, `Cartao`, `TelegramWebhookController`, `Illuminate\Support\Facades\Log`?**
  _High betweenness centrality (0.057) - this node is a cross-community bridge._
- **Why does `Transacao` connect `Transacao` to `Illuminate\Database\Eloquent\Model`, `NotaFiscal`, `.count`, `CartaoParcela`, `TvWidgetEndpointTest`, `Cartao`, `TelegramWebhookController`, `Categoria`, `OfxImportService`, `Illuminate\Http\Request`, `StatementImportService`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _134 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.10098522167487685 - nodes in this community are weakly interconnected._
- **Should `SalaryProjectionTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.13725490196078433 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.12688172043010754 - nodes in this community are weakly interconnected._