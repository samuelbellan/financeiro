# Graph Report - financeiro  (2026-10-27)

## Corpus Check
- 213 files · ~346,742 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1185 nodes · 2392 edges · 130 communities (99 shown, 31 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 61 edges (avg confidence: 0.79)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- MercadoModuleTest.php
- SalaryCalculatorService
- .http
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- Illuminate\Http\Request
- WorkoutModuleTest
- FiscalConcurso
- FiscalConcursosController
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- PhotoController
- TelegramWebhookController.php
- README.md
- CartaoCompra
- Illuminate\Database\Migrations\Migration
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- FiscalNoticia
- GearItem
- Controller
- Illuminate\Database\Schema\Blueprint
- Illuminate\Database\Eloquent\Relations\HasMany
- Illuminate\Database\Eloquent\Model
- FitnessAiModuleTest
- Categoria
- TestCase
- DailyLog
- TransactionSuggestionTest
- Transacao
- autoload-dev
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- WorkoutSession
- WorkoutSessionExercise
- SalaryProjectionTest
- home.blade.php
- WorkoutPlan
- RunningLog
- UserBodyMetric
- CartaoParcela
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- ExercisePersonalRecord
- Illuminate\Support\Facades\Log
- DatabaseSyncTest
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- qrcode.min.js
- extra
- Exercise
- TvWidgetEndpointTest
- Illuminate\Foundation\Testing\RefreshDatabase
- post-create-project-cmd
- FiscalNewsAiService

## God Nodes (most connected - your core abstractions)
1. `User` - 102 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 45 edges
4. `Transacao` - 45 edges
5. `TestCase` - 40 edges
6. `PhotoController` - 34 edges
7. `CartaoCompra` - 34 edges
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

## Communities (130 total, 31 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.12
Nodes (9): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+1 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - ".http"
Cohesion: 0.07
Nodes (8): TelegramSetWebhook, MercadoController, GeminiService, TelegramService, WhatsappMessageParser, PendingRequest, TelegramWebhookRoutingTest, WhatsappMessageParserTest

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
Nodes (19): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, InvalidArgumentException (+11 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "Illuminate\Http\Request"
Cohesion: 0.16
Nodes (5): CartoesController, Cartao, CreditCardService, Carbon, Illuminate\Http\Request

### Community 16 - "PhotoController"
Cohesion: 0.06
Nodes (12): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand (+4 more)

### Community 17 - "TelegramWebhookController.php"
Cohesion: 0.17
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "CartaoCompra"
Cohesion: 0.24
Nodes (3): CartaoCompra, Carbon\Carbon, Illuminate\Support\Facades\Auth

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

### Community 29 - "Controller"
Cohesion: 0.05
Nodes (14): TvWidgetController, AuthController, BodyTrackerController, Controller, DatabaseSyncController, EstudosController, HomeController, StudyGoal (+6 more)

### Community 31 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.13
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (7): Conta, NotaFiscal, NotaFiscalItem, SalaryProfile, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 36 - "Categoria"
Cohesion: 0.18
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 38 - "TestCase"
Cohesion: 0.15
Nodes (5): NetworkService, Illuminate\Foundation\Testing\TestCase, CelularAccessTest, ExampleTest, TestCase

### Community 43 - "DailyLog"
Cohesion: 0.07
Nodes (9): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage (+1 more)

### Community 45 - "Transacao"
Cohesion: 0.13
Nodes (4): FinancasController, Transacao, CategorySanitizer, TransactionSuggestionService

### Community 46 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 77 - "CartaoParcela"
Cohesion: 0.16
Nodes (8): ExportController, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

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
Cohesion: 0.12
Nodes (4): Exercise, ExerciseCatalogSeeder, Illuminate\Support\Str, Pdo\Mysql

### Community 122 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.15
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Cache, Mockery\MockInterface, CartaoCompraTest, ImportOfxCommandTest, TelegramOfxDocumentTest

### Community 124 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **84 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+79 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **31 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `MercadoModuleTest.php`, `SalaryCalculatorService`, `.http`, `Illuminate\Http\Request`, `WorkoutModuleTest`, `FiscalConcurso`, `FiscalConcursoDataService`, `TelegramWebhookTest`, `TelegramWebhookController.php`, `CartaoCompra`, `Controller`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Model`, `FitnessAiModuleTest`, `TestCase`, `DailyLog`, `TransactionSuggestionTest`, `Transacao`, `SalaryProjectionTest`, `CartaoParcela`, `DatabaseSyncTest`, `Exercise`, `TvWidgetEndpointTest`, `Illuminate\Foundation\Testing\RefreshDatabase`?**
  _High betweenness centrality (0.163) - this node is a cross-community bridge._
- **Why does `OfxImportService` connect `OfxParserService` to `PhotoController`, `TelegramWebhookController.php`?**
  _High betweenness centrality (0.053) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `MercadoModuleTest.php`, `SalaryCalculatorService`, `.http`, `User`, `WorkoutModuleTest`, `FiscalConcurso`, `FiscalConcursoDataService`, `TelegramWebhookTest`, `CartaoCompra`, `FitnessAiModuleTest`, `DailyLog`, `TransactionSuggestionTest`, `Transacao`, `SalaryProjectionTest`, `CartaoParcela`, `DatabaseSyncTest`, `Exercise`, `TvWidgetEndpointTest`, `Illuminate\Foundation\Testing\RefreshDatabase`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _84 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.11688311688311688 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._
- **Should `.http` be split into smaller, more focused modules?**
  _Cohesion score 0.06620209059233449 - nodes in this community are weakly interconnected._