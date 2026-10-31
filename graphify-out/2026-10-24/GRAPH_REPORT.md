# Graph Report - financeiro  (2026-10-24)

## Corpus Check
- 201 files · ~328,663 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1107 nodes · 2236 edges · 129 communities (98 shown, 31 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 53 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- PhotoController
- TelegramWebhookController.php
- SalaryCalculatorService
- TelegramWebhookController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- NotaFiscal
- .http
- FiscalConcurso
- Illuminate\Http\Request
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- DailyLog
- Cartao
- README.md
- StudyGoal
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- OfxParserServiceTest
- GearItem
- BodyTrackerController
- Illuminate\Database\Migrations\Migration
- Illuminate\Foundation\Testing\RefreshDatabase
- Illuminate\Database\Eloquent\Model
- TestCase
- Categoria
- OfxImportService
- FitnessAiService
- TelegramWebhookRoutingTest
- FiscalConcursoDataService
- FiscalNoticia
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- Transacao
- .count
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- CartaoCompraTest
- WorkoutSession
- BodyTrackerTest
- Exercise
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- TreinosController.php
- FiscalConcursosController
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- Illuminate\Support\Facades\Log
- extra
- OfxImportService.php
- OfxParserService.php
- OfxAccountDTO
- FinancialSummaryAndCardLimitsTest
- StretchingLog
- post-create-project-cmd
- TransactionSuggestionTest
- keywords
- public/importar_ofx.php

## God Nodes (most connected - your core abstractions)
1. `User` - 85 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 43 edges
4. `Transacao` - 43 edges
5. `TestCase` - 34 edges
6. `CartaoCompra` - 32 edges
7. `DailyLog` - 27 edges
8. `Measurement` - 26 edges
9. `TelegramWebhookController` - 25 edges
10. `CartaoParcela` - 25 edges

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

## Communities (129 total, 31 thin omitted)

### Community 0 - "PhotoController"
Cohesion: 0.06
Nodes (12): PhotoController, UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder (+4 more)

### Community 1 - "TelegramWebhookController.php"
Cohesion: 0.18
Nodes (8): CartaoParcela, CategorySanitizer, FinancialSummaryService, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\URL

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (15): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+7 more)

### Community 3 - "TelegramWebhookController"
Cohesion: 0.16
Nodes (4): JsonResponse, TelegramWebhookController, WhatsappLog, Carbon

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 5 - "User"
Cohesion: 0.12
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 10 - ".http"
Cohesion: 0.06
Nodes (14): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, PregeneratePhotoCacheCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, GeminiService (+6 more)

### Community 12 - "Illuminate\Http\Request"
Cohesion: 0.12
Nodes (9): AuthController, Controller, ExportController, FinancasController, HomeController, TransacaoPrevisao, Illuminate\Http\RedirectResponse, Illuminate\Http\Request (+1 more)

### Community 16 - "DailyLog"
Cohesion: 0.09
Nodes (7): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage

### Community 17 - "Cartao"
Cohesion: 0.17
Nodes (4): CartoesController, Cartao, CartaoPrevisao, CreditCardService

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

### Community 27 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 29 - "BodyTrackerController"
Cohesion: 0.12
Nodes (5): BodyTrackerController, DatabaseSyncController, DatabaseSyncService, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 31 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.15
Nodes (3): Illuminate\Foundation\Testing\RefreshDatabase, DatabaseSyncTest, FitnessAiModuleTest

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.10
Nodes (4): Conta, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "TestCase"
Cohesion: 0.19
Nodes (6): Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Cache, Mockery\MockInterface, ExampleTest, TelegramOfxDocumentTest, TestCase

### Community 36 - "Categoria"
Cohesion: 0.15
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 38 - "OfxImportService"
Cohesion: 0.26
Nodes (5): OfxImportService, PDO, PDO, OfxImportServiceTest, PDO

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 55 - "Transacao"
Cohesion: 0.18
Nodes (3): CartaoCompra, Transacao, TransactionSuggestionService

### Community 75 - "WorkoutSession"
Cohesion: 0.11
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "Exercise"
Cohesion: 0.07
Nodes (6): Exercise, WorkoutPlan, WorkoutPlanItem, WorkoutSet, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 89 - "TreinosController.php"
Cohesion: 0.12
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

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

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 108 - "OfxImportService.php"
Cohesion: 0.29
Nodes (3): OfxImportResultDTO, PDOException, Throwable

### Community 109 - "OfxParserService.php"
Cohesion: 0.29
Nodes (3): OfxTransactionDTO, InvalidArgumentException, RuntimeException

### Community 124 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 126 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **31 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `OfxImportService` connect `OfxImportService` to `public/importar_ofx.php`, `TelegramWebhookController.php`, `OfxImportService.php`, `OfxParserService`?**
  _High betweenness centrality (0.117) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `PhotoController`, `TelegramWebhookController.php`, `SalaryCalculatorService`, `TelegramWebhookController`, `NotaFiscal`, `FiscalConcurso`, `TelegramWebhookTest`, `DailyLog`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Illuminate\Database\Eloquent\Model`, `TestCase`, `TelegramWebhookRoutingTest`, `FiscalConcursoDataService`, `Transacao`, `SalaryProjectionTest`, `CartaoCompraTest`, `BodyTrackerTest`, `Exercise`, `FinancialSummaryAndCardLimitsTest`, `TransactionSuggestionTest`?**
  _High betweenness centrality (0.105) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController.php`, `TelegramWebhookController`, `Illuminate\Support\Facades\Log`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`, `FiscalConcursosController`?**
  _High betweenness centrality (0.061) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.060129509713228495 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.0573025856044724 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.12 - nodes in this community are weakly interconnected._