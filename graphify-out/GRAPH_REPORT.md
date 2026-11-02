# Graph Report - financeiro  (2026-11-01)

## Corpus Check
- 253 files · ~385,808 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1611 nodes · 3665 edges · 161 communities (129 shown, 32 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.76)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `619b0faf`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- NotaFiscal
- SalaryCalculatorService
- PhotoController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- CartaoCompra
- CreditCardReconciliationService
- FiscalConcurso
- FiscalConcursosController
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- renderStats
- Cartao
- TelegramWebhookController
- README.md
- WhatsappMessageParserTest
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- .count
- StudySyncTest
- AllowedGoogleEmail
- Illuminate\Database\Migrations\Migration
- renderTimer
- Illuminate\Database\Eloquent\Model
- Illuminate\Database\Schema\Blueprint
- Transacao
- WorkoutSessionExercise
- NetworkService
- CategoriasController
- main.js
- DailyLog
- Illuminate\Http\JsonResponse
- Exercise
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
- Illuminate\Database\Eloquent\Relations\HasMany
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- db.js
- WorkoutSession
- web.php
- CartaoParcela
- transaction-autocomplete.js
- FiscalNoticia
- GeminiService
- Illuminate\Support\Facades\Schema
- UserBodyMetric
- StatementImportService.php
- StudySyncService
- Illuminate\Http\Request
- StatementImportService
- BackupEstudosCommand
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
- Módulo de estudos estilo YPT (offline)
- post-create-project-cmd
- Controller
- OfxParserService.php
- planImport
- AppServiceProvider
- estudos-sw.js
- OfxImportService
- audio.js
- importar_ofx.php

## God Nodes (most connected - your core abstractions)
1. `User` - 134 edges
2. `Cartao` - 66 edges
3. `Transacao` - 60 edges
4. `TestCase` - 56 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `PhotoController` - 34 edges
9. `CreditCardReconciliationService` - 32 edges
10. `DailyLog` - 27 edges

## Surprising Connections (you probably didn't know these)
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `TransactionDeduplicationTest` --references--> `Conta`  [EXTRACTED]
  tests/Feature/TransactionDeduplicationTest.php → app/Models/Conta.php

## Import Cycles
- None detected.

## Communities (161 total, 32 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.21
Nodes (6): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 1 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (15): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+7 more)

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.07
Nodes (7): User, Illuminate\Foundation\Auth\User, Mockery, CreditCardDeleteTest, GoogleAuthAndWhitelistTest, PhotoModuleTest, TelegramWebhookTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "CartaoCompra"
Cohesion: 0.11
Nodes (4): CartoesController, CartaoCompra, CartaoFatura, CartaoCompraTest

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.16
Nodes (3): FiscalConcursoDataService, FiscalNewsAiService, FiscalTelegramNotifierService

### Community 15 - "renderStats"
Cohesion: 0.17
Nodes (27): cards(), formatDay(), formatHM(), formatSigned(), renderComparison(), renderHeatmap(), renderMonthCalendar(), renderQuarterGrid() (+19 more)

### Community 16 - "Cartao"
Cohesion: 0.08
Nodes (9): Cartao, CreditCardService, Carbon, Carbon\Carbon, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Validator, CreditCardClosingTest (+1 more)

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.11
Nodes (38): daily(), liveSessions(), recentSessions(), renderSyncBadge(), renderTimeline(), amountBetween(), buildDaily(), compareWithPrevious() (+30 more)

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
Cohesion: 0.10
Nodes (10): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand (+2 more)

### Community 29 - "AllowedGoogleEmail"
Cohesion: 0.15
Nodes (6): AllowedEmailController, AuthController, AllowedGoogleEmail, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Auth, Laravel\Socialite\Facades\Socialite

### Community 31 - "renderTimer"
Cohesion: 0.24
Nodes (20): avatarStage(), avatarSvg(), avatarBlock(), ddayChips(), esc(), metaBar(), nextStageText(), renderCurrent() (+12 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (6): CartaoFaturaFechamento, Conta, FiscalTelegramConfig, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Transacao"
Cohesion: 0.10
Nodes (4): Transacao, TelegramWebhookRoutingTest, TransactionDeduplicationTest, TransactionSuggestionTest

### Community 41 - "main.js"
Cohesion: 0.17
Nodes (18): activeSubjects(), charts, chartTooltip, colorOf(), colorPicker(), cssVar(), dialog, hoursOf() (+10 more)

### Community 43 - "DailyLog"
Cohesion: 0.09
Nodes (6): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService, FitnessAiModuleTest

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.16
Nodes (4): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 45 - "Exercise"
Cohesion: 0.06
Nodes (7): Exercise, ExercisePersonalRecord, WorkoutGoal, WorkoutPlan, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

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
Cohesion: 0.11
Nodes (4): ProgressPhoto, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage, BodyTrackerTest

### Community 63 - "GearItem"
Cohesion: 0.09
Nodes (6): GearItem, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str, Pdo\Mysql, static

### Community 66 - ".http"
Cohesion: 0.15
Nodes (4): TelegramSetWebhook, JsonResponse, TelegramService, PendingRequest

### Community 69 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.13
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

### Community 74 - "db.js"
Cohesion: 0.27
Nodes (16): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+8 more)

### Community 75 - "WorkoutSession"
Cohesion: 0.08
Nodes (4): RunningLog, RunningSplit, StretchingLog, WorkoutSession

### Community 76 - "web.php"
Cohesion: 0.25
Nodes (3): EstudosController, HomeController, Illuminate\Support\Facades\Route

### Community 77 - "CartaoParcela"
Cohesion: 0.10
Nodes (11): ExportController, CartaoParcela, CartaoPrevisao, Categoria, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService, TransactionSuggestionService (+3 more)

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "FiscalNoticia"
Cohesion: 0.17
Nodes (3): FiscalNoticia, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 88 - "StatementImportService.php"
Cohesion: 0.22
Nodes (4): OfxImportResultDTO, PDOException, Throwable, ZipArchive

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.10
Nodes (3): FinancasController, TransactionDeduplicationService, Illuminate\Http\Request

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
Cohesion: 0.26
Nodes (20): uuid(), checkSuspicious(), closeFocus(), exportBackup(), importBackup(), iso(), keepScreenOn(), nowMs() (+12 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 122 - "TestCase"
Cohesion: 0.07
Nodes (10): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Mockery\MockInterface, BackupEstudosCommandTest, DatabaseSyncTest, ExampleTest, ImportOfxCommandTest, StatementImportTest (+2 more)

### Community 123 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 124 - "avatars.js"
Cohesion: 0.25
Nodes (9): AVATAR_LIMITES_MIN, AVATAR_PADRAO, AVATARS, BICHINHO, eggCrack, happyFace(), P(), PLANTA (+1 more)

### Community 135 - "Módulo de estudos estilo YPT (offline)"
Cohesion: 0.22
Nodes (8): Avatares, Backup no PC, Decisões, Módulo de estudos estilo YPT (offline), Onde está o código, Riscos conhecidos, Roteiro de teste manual (modo avião), v2

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 143 - "Controller"
Cohesion: 0.14
Nodes (3): TvWidgetController, Controller, SalaryController

### Community 144 - "OfxParserService.php"
Cohesion: 0.29
Nodes (3): OfxTransactionDTO, InvalidArgumentException, RuntimeException

### Community 145 - "planImport"
Cohesion: 0.48
Nodes (4): normalize(), planImport(), toUuid(), empty

### Community 148 - "estudos-sw.js"
Cohesion: 0.40
Nodes (3): shellResponse(), STATIC, withTimeout()

### Community 149 - "OfxImportService"
Cohesion: 0.26
Nodes (5): OfxImportService, PDO, PDO, OfxImportServiceTest, PDO

### Community 153 - "audio.js"
Cohesion: 0.80
Nodes (4): getAudioContext(), playFocusEndSound(), playLevelUpSound(), playNote()

## Knowledge Gaps
- **120 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+115 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **32 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryCalculatorService`, `CartaoCompra`, `FiscalConcurso`, `Controller`, `Cartao`, `TelegramWebhookController`, `StudySyncTest`, `AllowedGoogleEmail`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `NetworkService`, `DailyLog`, `Exercise`, `ProgressPhoto`, `GearItem`, `Illuminate\Database\Eloquent\Relations\HasMany`, `CartaoParcela`, `FiscalNoticia`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.115) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `NotaFiscal`, `SalaryCalculatorService`, `Transacao`, `User`, `NetworkService`, `CartaoCompra`, `FiscalConcurso`, `DailyLog`, `CartaoParcela`, `TvWidgetEndpointTest`, `Exercise`, `Cartao`, `FiscalNoticia`, `WhatsappMessageParserTest`, `ProgressPhoto`, `StudySyncTest`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Eloquent\Relations\HasMany`, `FiscalConcursosController`, `FiscalConcursoDataService`, `FiscalNewsCrawlerService`, `Cartao`, `FiscalNoticia`, `AllowedGoogleEmail`?**
  _High betweenness centrality (0.043) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _120 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.10507246376811594 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.05520614954577219 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._