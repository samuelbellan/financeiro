# Graph Report - financeiro  (2026-10-31)

## Corpus Check
- 227 files · ~372,435 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1325 nodes · 2820 edges · 142 communities (110 shown, 32 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 97 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `13a8269a`
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
- OfxImportService
- devDependencies
- CartaoCompra
- CreditCardReconciliationService
- FiscalConcurso
- FiscalNoticia
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- Cartao
- TelegramWebhookController
- README.md
- TransactionSuggestionTest
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- .http
- Controller
- Illuminate\Database\Schema\Blueprint
- MercadoController.php
- Illuminate\Database\Eloquent\Model
- Transacao
- WorkoutSessionExercise
- NetworkService
- Categoria
- Illuminate\Support\Facades\Schema
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
- TelegramWebhookController.php
- OfxParserService
- SalaryProjectionTest.php
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- GearItem
- WorkoutSession
- TreinosController.php
- CartaoPrevisao
- transaction-autocomplete.js
- OfxImportService.php
- GeminiService
- Illuminate\Http\Request
- StatementImportService
- BodyTrackerTest
- OfxParserServiceTest
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- .count
- require
- qrcode.min.js
- extra
- WhatsappMessageParserTest
- TvWidgetEndpointTest
- OfxAccountDTO
- TestCase
- autoload-dev
- FitnessAiModuleTest
- TelegramWebhookRoutingTest.php
- post-create-project-cmd

## God Nodes (most connected - your core abstractions)
1. `User` - 115 edges
2. `Cartao` - 63 edges
3. `Transacao` - 60 edges
4. `FiscalConcurso` - 52 edges
5. `TestCase` - 48 edges
6. `CartaoCompra` - 44 edges
7. `CartaoParcela` - 40 edges
8. `PhotoController` - 34 edges
9. `CreditCardReconciliationService` - 32 edges
10. `DailyLog` - 27 edges

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

## Communities (142 total, 32 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 1 - "NotaFiscal"
Cohesion: 0.14
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.05
Nodes (15): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+7 more)

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 5 - "User"
Cohesion: 0.08
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxImportService"
Cohesion: 0.19
Nodes (7): OfxImportService, PDO, getPdoConnection(), PDO, PDO, OfxImportServiceTest, PDO

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "CartaoCompra"
Cohesion: 0.17
Nodes (3): CartoesController, CartaoCompra, CartaoParcela

### Community 12 - "FiscalNoticia"
Cohesion: 0.11
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.19
Nodes (3): FiscalConcursoDataService, FiscalNewsAiService, FiscalTelegramNotifierService

### Community 16 - "Cartao"
Cohesion: 0.13
Nodes (5): Cartao, CreditCardService, Carbon, Carbon\Carbon, CreditCardClosingTest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

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

### Community 27 - "Illuminate\Console\Command"
Cohesion: 0.11
Nodes (10): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand (+2 more)

### Community 28 - ".http"
Cohesion: 0.09
Nodes (5): TelegramSetWebhook, CartaoFatura, TelegramService, PendingRequest, CreditCardReconciliationTest

### Community 29 - "Controller"
Cohesion: 0.19
Nodes (4): TvWidgetController, Controller, HomeController, Illuminate\Support\Facades\Route

### Community 31 - "MercadoController.php"
Cohesion: 0.18
Nodes (4): EstudosController, StudyGoal, StudyLog, Illuminate\Support\Facades\Auth

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.11
Nodes (6): CartaoFaturaFechamento, StretchingLog, WorkoutPlanItem, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Transacao"
Cohesion: 0.12
Nodes (5): Conta, Transacao, TransactionDeduplicationService, TelegramOfxDocumentTest, TransactionDeduplicationTest

### Community 40 - "Categoria"
Cohesion: 0.11
Nodes (6): CategoriasController, Categoria, Subcategoria, CategorySanitizer, TransactionSuggestionService, Barryvdh\DomPDF\Facade\Pdf

### Community 43 - "DailyLog"
Cohesion: 0.07
Nodes (6): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.11
Nodes (6): BodyTrackerController, DatabaseSyncController, DatabaseSyncService, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\Storage, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 45 - "Exercise"
Cohesion: 0.08
Nodes (4): Exercise, WorkoutPlan, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 59 - "TelegramWebhookController.php"
Cohesion: 0.27
Nodes (4): Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 76 - "TreinosController.php"
Cohesion: 0.11
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 77 - "CartaoPrevisao"
Cohesion: 0.12
Nodes (6): ExportController, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 87 - "OfxImportService.php"
Cohesion: 0.14
Nodes (6): OfxImportResultDTO, OfxTransactionDTO, InvalidArgumentException, PDOException, RuntimeException, Throwable

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.13
Nodes (5): AuthController, FinancasController, JsonResponse, Illuminate\Http\RedirectResponse, Illuminate\Http\Request

### Community 96 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

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

### Community 122 - "TestCase"
Cohesion: 0.08
Nodes (10): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Http\UploadedFile, CartaoCompraTest, DatabaseSyncTest, ExampleTest, ImportOfxCommandTest, StatementImportTest (+2 more)

### Community 124 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **87 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+82 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **32 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `FitnessAiModuleTest`, `TelegramWebhookRoutingTest.php`, `FiscalConcurso`, `TelegramWebhookTest`, `Cartao`, `TelegramWebhookController`, `TransactionSuggestionTest`, `.http`, `Controller`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `NetworkService`, `Categoria`, `Exercise`, `TelegramWebhookController.php`, `SalaryProjectionTest.php`, `CartaoPrevisao`, `BodyTrackerTest`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.118) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `FiscalNoticia`, `FiscalConcursoDataService`, `FiscalNewsCrawlerService`, `TelegramWebhookController`, `TestCase`, `TelegramWebhookController.php`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._
- **Why does `Transacao` connect `Transacao` to `Illuminate\Database\Eloquent\Model`, `TelegramWebhookRoutingTest.php`, `Categoria`, `CartaoCompra`, `TelegramWebhookController.php`, `CartaoPrevisao`, `TelegramWebhookTest`, `Cartao`, `TelegramWebhookController`, `TransactionSuggestionTest`, `StatementImportService`, `TestCase`, `Illuminate\Http\Request`, `Controller`, `MercadoController.php`?**
  _High betweenness centrality (0.045) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _87 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08994708994708994 - nodes in this community are weakly interconnected._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.13970588235294118 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.054426705370101594 - nodes in this community are weakly interconnected._