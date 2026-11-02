# Graph Report - financeiro  (2026-10-31)

## Corpus Check
- 228 files · ~372,978 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1331 nodes · 2842 edges · 144 communities (107 shown, 37 thin omitted)
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
- OfxParserService
- devDependencies
- CartoesController
- .count
- FiscalConcurso
- FiscalConcursosController
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- Cartao
- TelegramWebhookController
- README.md
- TransactionSuggestionService
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- Illuminate\Foundation\Testing\RefreshDatabase
- sidebar.js
- deploy
- logging.php
- .http
- CartaoParcela
- web.php
- Illuminate\Support\Facades\Schema
- StudyGoal
- Illuminate\Database\Eloquent\Model
- Transacao
- WorkoutSessionExercise
- NetworkService
- Categoria
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
- TelegramWebhookController.php
- CategorySanitizer
- SalaryProjectionTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- GearItem
- RunningLog
- ExercisePersonalRecord
- Controller
- transaction-autocomplete.js
- Illuminate\Http\Request
- WorkoutModuleTest
- FinancasController
- StatementImportService
- BodyTrackerTest
- CartaoCompra
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- FiscalNoticia
- require
- qrcode.min.js
- extra
- WhatsappMessageParserTest
- TvWidgetEndpointTest
- WorkoutSession
- TestCase
- autoload-dev
- FinancialSummaryAndCardLimitsTest
- FiscalNewsAiService
- TvWidgetController.php
- CreditCardDeleteTest
- post-create-project-cmd

## God Nodes (most connected - your core abstractions)
1. `User` - 119 edges
2. `Cartao` - 66 edges
3. `Transacao` - 60 edges
4. `FiscalConcurso` - 52 edges
5. `TestCase` - 50 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `PhotoController` - 34 edges
9. `CreditCardReconciliationService` - 32 edges
10. `DailyLog` - 27 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `CreditCardReconciliationTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardReconciliationTest.php → app/Models/Cartao.php
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
- `TransactionDeduplicationTest` --references--> `Conta`  [EXTRACTED]
  tests/Feature/TransactionDeduplicationTest.php → app/Models/Conta.php

## Import Cycles
- None detected.

## Communities (144 total, 37 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.25
Nodes (5): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 1 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type (+2 more)

### Community 5 - "User"
Cohesion: 0.08
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (18): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, getPdoConnection() (+10 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.16
Nodes (4): FiscalCrawlCommand, FiscalConcursoDataService, FiscalTelegramNotifierService, Command

### Community 15 - "TelegramWebhookTest"
Cohesion: 0.20
Nodes (3): Mockery, Mockery\MockInterface, TelegramWebhookTest

### Community 16 - "Cartao"
Cohesion: 0.12
Nodes (5): Cartao, CartaoFaturaFechamento, CreditCardService, Carbon, CreditCardClosingTest

### Community 17 - "TelegramWebhookController"
Cohesion: 0.18
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.13
Nodes (5): Conta, Illuminate\Foundation\Testing\RefreshDatabase, ImportOfxCommandTest, StatementImportTest, TelegramOfxDocumentTest

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - ".http"
Cohesion: 0.05
Nodes (14): ExportProductionDataCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook (+6 more)

### Community 29 - "web.php"
Cohesion: 0.22
Nodes (4): AuthController, HomeController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 31 - "StudyGoal"
Cohesion: 0.18
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (6): CartaoPrevisao, SalaryProfile, StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Transacao"
Cohesion: 0.14
Nodes (3): Transacao, TransactionDeduplicationService, TransactionDeduplicationTest

### Community 40 - "Categoria"
Cohesion: 0.20
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 43 - "DailyLog"
Cohesion: 0.08
Nodes (6): DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, FitnessAiModuleTest

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.15
Nodes (4): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 45 - "Exercise"
Cohesion: 0.09
Nodes (4): Exercise, WorkoutPlan, WorkoutPlanItem, ExerciseCatalogSeeder

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
Cohesion: 0.09
Nodes (16): AppServiceProvider, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http (+8 more)

### Community 63 - "CategorySanitizer"
Cohesion: 0.19
Nodes (4): CategorySanitizer, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 77 - "Controller"
Cohesion: 0.14
Nodes (4): Controller, ExportController, TransacaoPrevisao, FinancialSummaryService

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 87 - "Illuminate\Http\Request"
Cohesion: 0.24
Nodes (3): TreinosController, Illuminate\Http\Request, Illuminate\Support\Facades\Hash

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
Cohesion: 0.18
Nodes (4): Illuminate\Foundation\Testing\TestCase, DatabaseSyncTest, ExampleTest, TestCase

### Community 124 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **87 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+82 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **37 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryCalculatorService`, `FinancialSummaryAndCardLimitsTest`, `TvWidgetController.php`, `CreditCardDeleteTest`, `FiscalConcurso`, `FiscalConcursoDataService`, `TelegramWebhookTest`, `Cartao`, `TelegramWebhookController`, `TransactionSuggestionService`, `Illuminate\Foundation\Testing\RefreshDatabase`, `.http`, `CartaoParcela`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `NetworkService`, `DailyLog`, `Exercise`, `TelegramWebhookController.php`, `CategorySanitizer`, `SalaryProjectionTest`, `Controller`, `WorkoutModuleTest`, `BodyTrackerTest`, `CartaoCompra`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.131) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `FiscalNewsAiService`, `FiscalNoticia`, `FiscalConcursosController`, `FiscalConcursoDataService`, `FiscalNewsCrawlerService`, `TelegramWebhookController`, `TelegramWebhookController.php`?**
  _High betweenness centrality (0.060) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `NotaFiscal`, `SalaryCalculatorService`, `FinancialSummaryAndCardLimitsTest`, `User`, `CreditCardDeleteTest`, `FiscalConcurso`, `FiscalConcursoDataService`, `TelegramWebhookTest`, `Cartao`, `TransactionSuggestionService`, `Illuminate\Foundation\Testing\RefreshDatabase`, `.http`, `CartaoParcela`, `Transacao`, `NetworkService`, `DailyLog`, `Exercise`, `TelegramWebhookController.php`, `SalaryProjectionTest`, `Controller`, `WorkoutModuleTest`, `BodyTrackerTest`, `CartaoCompra`, `WhatsappMessageParserTest`, `TvWidgetEndpointTest`?**
  _High betweenness centrality (0.049) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _87 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.11076923076923077 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._