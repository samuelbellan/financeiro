# Graph Report - financeiro  (2026-10-26)

## Corpus Check
- 205 files · ~338,171 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1140 nodes · 2322 edges · 122 communities (96 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 56 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- NotaFiscal
- SalaryCalculatorService
- TelegramWebhookController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- AuthController
- .count
- FiscalConcurso
- Cartao
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- DailyLog
- FinancialSummaryAndCardLimitsTest
- README.md
- StudyGoal
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- .http
- GearItem
- Illuminate\Http\Request
- Illuminate\Database\Migrations\Migration
- DatabaseSyncTest
- Illuminate\Database\Eloquent\Model
- TestCase
- CategoriasController
- ProgressPhoto
- User.php
- BodyTrackerTest
- FiscalConcursoDataService
- keywords
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- PhotoController
- SalaryProjectionTest.php
- components.sync-modal
- Exercise
- WorkoutSession
- Controller
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- TreinosController.php
- FiscalNoticia
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- TelegramWebhookController.php
- extra
- TvWidgetEndpointTest
- FitnessAiModuleTest
- TelegramWebhookRoutingTest
- post-create-project-cmd

## God Nodes (most connected - your core abstractions)
1. `User` - 96 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 45 edges
4. `Transacao` - 45 edges
5. `TestCase` - 36 edges
6. `CartaoCompra` - 34 edges
7. `PhotoController` - 29 edges
8. `CartaoParcela` - 27 edges
9. `DailyLog` - 27 edges
10. `Measurement` - 26 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php
- `DatabaseSyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DatabaseSyncTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (122 total, 26 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.08
Nodes (12): UserFactory, DatabaseSeeder, ExerciseCatalogSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder (+4 more)

### Community 1 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramWebhookController"
Cohesion: 0.11
Nodes (5): JsonResponse, TelegramWebhookController, WhatsappLog, WhatsappMessageParser, WhatsappMessageParserTest

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 5 - "User"
Cohesion: 0.11
Nodes (4): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (19): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, InvalidArgumentException (+11 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - ".count"
Cohesion: 0.08
Nodes (11): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook (+3 more)

### Community 12 - "Cartao"
Cohesion: 0.06
Nodes (19): CartoesController, ExportController, FinancasController, Cartao, CartaoCompra, CartaoParcela, CartaoPrevisao, Categoria (+11 more)

### Community 16 - "DailyLog"
Cohesion: 0.10
Nodes (5): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

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

### Community 29 - "Illuminate\Http\Request"
Cohesion: 0.21
Nodes (5): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Illuminate\Http\Request, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.07
Nodes (9): Conta, FiscalTelegramConfig, StretchingLog, WorkoutPlanItem, WorkoutSessionExercise, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model (+1 more)

### Community 35 - "TestCase"
Cohesion: 0.16
Nodes (7): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Mockery\MockInterface, CartaoCompraTest, ExampleTest, TelegramOfxDocumentTest, TestCase

### Community 38 - "ProgressPhoto"
Cohesion: 0.18
Nodes (3): ProgressPhoto, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage

### Community 43 - "User.php"
Cohesion: 0.50
Nodes (3): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Notifications\Notifiable

### Community 46 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 74 - "Exercise"
Cohesion: 0.08
Nodes (3): Exercise, WorkoutPlan, WorkoutModuleTest

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 76 - "Controller"
Cohesion: 0.11
Nodes (5): TvWidgetController, Controller, HomeController, SalaryController, Illuminate\Support\Facades\Route

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 89 - "TreinosController.php"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 91 - "FiscalNoticia"
Cohesion: 0.14
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramNotifierService

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

### Community 106 - "TelegramWebhookController.php"
Cohesion: 0.15
Nodes (6): GeminiService, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 124 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `TelegramWebhookController`, `FiscalConcurso`, `Cartao`, `TelegramWebhookTest`, `DailyLog`, `FinancialSummaryAndCardLimitsTest`, `DatabaseSyncTest`, `Illuminate\Database\Eloquent\Model`, `TestCase`, `ProgressPhoto`, `User.php`, `BodyTrackerTest`, `SalaryProjectionTest.php`, `Exercise`, `Controller`, `TelegramWebhookController.php`, `TvWidgetEndpointTest`, `FitnessAiModuleTest`, `TelegramWebhookRoutingTest`?**
  _High betweenness centrality (0.136) - this node is a cross-community bridge._
- **Why does `OfxImportService` connect `OfxParserService` to `TelegramWebhookController.php`?**
  _High betweenness centrality (0.086) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController`, `User`, `TelegramWebhookController.php`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`?**
  _High betweenness centrality (0.050) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08172043010752689 - nodes in this community are weakly interconnected._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.10507246376811594 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06464646464646465 - nodes in this community are weakly interconnected._