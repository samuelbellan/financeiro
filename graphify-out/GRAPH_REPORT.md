# Graph Report - financeiro  (2026-10-31)

## Corpus Check
- 220 files · ~359,274 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1245 nodes · 2570 edges · 142 communities (108 shown, 34 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 74 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
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
- Cartao
- StatementImportService
- FiscalConcurso
- FiscalNoticia
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- Illuminate\Console\Command
- TelegramWebhookController
- README.md
- Transacao
- Illuminate\Database\Migrations\Migration
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- Illuminate\Database\Eloquent\Relations\HasMany
- .http
- Illuminate\Http\Request
- Illuminate\Database\Schema\Blueprint
- StudyGoal
- Illuminate\Database\Eloquent\Model
- FitnessAiModuleTest
- Controller
- NetworkService
- DailyLog
- TelegramWebhookRoutingTest
- StatementImportTest
- .handle
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- WorkoutSession
- .count
- SalaryProjectionTest
- components.conciliacao-fatura-modal
- components.import-extrato-modal
- home.blade.php
- TreinosController
- GearItem
- TreinosController.php
- CartaoParcela
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- SalaryController
- TelegramWebhookController.php
- FinancasController
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- qrcode.min.js
- extra
- Exercise
- TvWidgetEndpointTest
- TestCase
- DatabaseSyncService
- CartaoCompra
- FiscalNewsAiService
- UserFactory.php
- CategoriasController
- CategorySanitizer
- DatabaseSyncTest
- AuthController
- CreditCardReconciliationTest
- autoload-dev
- post-create-project-cmd

## God Nodes (most connected - your core abstractions)
1. `User` - 108 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 51 edges
4. `Transacao` - 48 edges
5. `TestCase` - 44 edges
6. `CartaoCompra` - 37 edges
7. `PhotoController` - 34 edges
8. `CartaoParcela` - 32 edges
9. `DailyLog` - 27 edges
10. `Measurement` - 26 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CreditCardReconciliationTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardReconciliationTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (142 total, 34 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.25
Nodes (5): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 1 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.07
Nodes (13): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+5 more)

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 5 - "User"
Cohesion: 0.13
Nodes (3): User, Illuminate\Foundation\Auth\User, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (19): OfxAccountDTO, OfxImportResultDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, InvalidArgumentException, PDO (+11 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "Cartao"
Cohesion: 0.14
Nodes (5): CartoesController, Cartao, CreditCardService, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon

### Community 10 - "StatementImportService"
Cohesion: 0.12
Nodes (4): CreditCardReconciliationService, StatementImportService, Carbon, Illuminate\Http\UploadedFile

### Community 12 - "FiscalNoticia"
Cohesion: 0.11
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.19
Nodes (3): FiscalConcursoDataService, FiscalTelegramNotifierService, WhatsappMessageParser

### Community 16 - "Illuminate\Console\Command"
Cohesion: 0.15
Nodes (7): ExportProductionDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, Illuminate\Console\Command

### Community 17 - "TelegramWebhookController"
Cohesion: 0.18
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "Transacao"
Cohesion: 0.15
Nodes (4): Categoria, Transacao, TransactionSuggestionService, TransactionSuggestionTest

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.13
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

### Community 28 - ".http"
Cohesion: 0.18
Nodes (3): GeminiService, TelegramService, PendingRequest

### Community 29 - "Illuminate\Http\Request"
Cohesion: 0.22
Nodes (4): BodyTrackerController, Illuminate\Http\JsonResponse, Illuminate\Http\Request, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 31 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.11
Nodes (7): Conta, SalaryProfile, WorkoutSessionExercise, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 36 - "Controller"
Cohesion: 0.17
Nodes (5): TvWidgetController, Controller, DatabaseSyncController, HomeController, Illuminate\Support\Facades\Route

### Community 43 - "DailyLog"
Cohesion: 0.06
Nodes (6): DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, BodyTrackerTest

### Community 46 - ".handle"
Cohesion: 0.20
Nodes (4): FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, Command

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 75 - "GearItem"
Cohesion: 0.09
Nodes (3): GearItem, RunningLog, RunningSplit

### Community 76 - "TreinosController.php"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 77 - "CartaoParcela"
Cohesion: 0.13
Nodes (8): ExportController, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 91 - "TelegramWebhookController.php"
Cohesion: 0.18
Nodes (6): Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, Illuminate\Support\Facades\Storage, Illuminate\Support\Str, Pdo\Mysql

### Community 97 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 105 - "require"
Cohesion: 0.40
Nodes (5): require, barryvdh/laravel-dompdf, laravel/framework, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 108 - "Exercise"
Cohesion: 0.09
Nodes (4): Exercise, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 122 - "TestCase"
Cohesion: 0.12
Nodes (9): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Cache, Mockery\MockInterface, ExampleTest, ImportOfxCommandTest, TelegramOfxDocumentTest, TestCase (+1 more)

### Community 132 - "UserFactory.php"
Cohesion: 0.33
Nodes (3): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash

### Community 138 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **86 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+81 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **34 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryCalculatorService`, `UserFactory.php`, `DatabaseSyncTest`, `CreditCardReconciliationTest`, `StatementImportService`, `Cartao`, `FiscalNoticia`, `FiscalConcurso`, `TelegramWebhookTest`, `TelegramWebhookController`, `Transacao`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Http\Request`, `Illuminate\Database\Eloquent\Model`, `FitnessAiModuleTest`, `Controller`, `NetworkService`, `DailyLog`, `TelegramWebhookRoutingTest`, `StatementImportTest`, `SalaryProjectionTest`, `CartaoParcela`, `TelegramWebhookController.php`, `Exercise`, `TvWidgetEndpointTest`, `TestCase`, `CartaoCompra`?**
  _High betweenness centrality (0.126) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `NotaFiscal`, `SalaryCalculatorService`, `User`, `DatabaseSyncTest`, `CreditCardReconciliationTest`, `StatementImportService`, `FiscalConcurso`, `FiscalNoticia`, `Cartao`, `TelegramWebhookTest`, `Transacao`, `FitnessAiModuleTest`, `NetworkService`, `DailyLog`, `TelegramWebhookRoutingTest`, `StatementImportTest`, `SalaryProjectionTest`, `CartaoParcela`, `Exercise`, `TvWidgetEndpointTest`, `CartaoCompra`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `Measurement` connect `DailyLog` to `Illuminate\Database\Eloquent\Model`, `TreinosController`, `FitnessAiModuleTest`, `TreinosController.php`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _86 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.11076923076923077 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.07293868921775898 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._