# Graph Report - financeiro  (2026-10-25)

## Corpus Check
- 203 files · ~331,101 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1122 nodes · 2291 edges · 125 communities (96 shown, 29 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 55 edges (avg confidence: 0.8)
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
- Illuminate\Http\Request
- .count
- FiscalConcurso
- CartaoParcela
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
- .http
- GearItem
- Illuminate\Http\JsonResponse
- Illuminate\Database\Migrations\Migration
- TestCase
- Illuminate\Database\Eloquent\Model
- Illuminate\Foundation\Testing\RefreshDatabase
- CategoriasController
- GeminiService
- WorkoutSession
- FiscalModuleTest
- FiscalConcursoDataService
- TelegramWebhookController.php
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- Transacao
- PhotoController
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- WorkoutPlan
- RunningLog
- Controller
- Exercise
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- TreinosController.php
- FiscalNoticia
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- Illuminate\Support\Facades\Log
- extra
- CartaoCompra
- TvWidgetEndpointTest
- keywords
- WorkoutModuleTest
- post-create-project-cmd

## God Nodes (most connected - your core abstractions)
1. `User` - 92 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 45 edges
4. `Transacao` - 45 edges
5. `TestCase` - 36 edges
6. `CartaoCompra` - 34 edges
7. `CartaoParcela` - 27 edges
8. `DailyLog` - 27 edges
9. `Measurement` - 26 edges
10. `PhotoController` - 25 edges

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

## Communities (125 total, 29 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 1 - "NotaFiscal"
Cohesion: 0.14
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (15): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+7 more)

### Community 3 - "TelegramWebhookController"
Cohesion: 0.17
Nodes (4): JsonResponse, TelegramWebhookController, WhatsappLog, Carbon

### Community 4 - "composer.json"
Cohesion: 0.18
Nodes (10): autoload-dev, psr-4, description, license, minimum-stability, name, prefer-stable, Tests\\ (+2 more)

### Community 5 - "User"
Cohesion: 0.11
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PhotoModuleTest

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
Cohesion: 0.22
Nodes (4): AuthController, FinancasController, Illuminate\Http\RedirectResponse, Illuminate\Http\Request

### Community 10 - ".count"
Cohesion: 0.09
Nodes (10): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, PregeneratePhotoCacheCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, DatabaseSyncService (+2 more)

### Community 12 - "CartaoParcela"
Cohesion: 0.14
Nodes (7): ExportController, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 16 - "DailyLog"
Cohesion: 0.06
Nodes (10): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage (+2 more)

### Community 17 - "Cartao"
Cohesion: 0.16
Nodes (4): CartoesController, Cartao, CreditCardService, WhatsappMessageParser

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

### Community 29 - "Illuminate\Http\JsonResponse"
Cohesion: 0.16
Nodes (4): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 31 - "TestCase"
Cohesion: 0.18
Nodes (4): Illuminate\Foundation\Testing\TestCase, DatabaseSyncTest, ExampleTest, TestCase

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (5): Conta, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.11
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Cache, Mockery\MockInterface, TelegramOfxDocumentTest, TelegramWebhookRoutingTest, WhatsappMessageParserTest

### Community 46 - "TelegramWebhookController.php"
Cohesion: 0.24
Nodes (4): Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\DB

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
Cohesion: 0.15
Nodes (5): Categoria, Transacao, CategorySanitizer, TransactionSuggestionService, TransactionSuggestionTest

### Community 76 - "Controller"
Cohesion: 0.28
Nodes (4): TvWidgetController, Controller, HomeController, Illuminate\Support\Facades\Route

### Community 77 - "Exercise"
Cohesion: 0.12
Nodes (3): Exercise, WorkoutPlanItem, ExerciseCatalogSeeder

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 89 - "TreinosController.php"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 91 - "FiscalNoticia"
Cohesion: 0.13
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

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

### Community 113 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 124 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **29 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryCalculatorService`, `TelegramWebhookController`, `CartaoParcela`, `TelegramWebhookTest`, `DailyLog`, `Cartao`, `TestCase`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Foundation\Testing\RefreshDatabase`, `FiscalModuleTest`, `TelegramWebhookController.php`, `Transacao`, `SalaryProjectionTest`, `Controller`, `Exercise`, `Illuminate\Support\Facades\Log`, `CartaoCompra`, `TvWidgetEndpointTest`, `WorkoutModuleTest`?**
  _High betweenness centrality (0.139) - this node is a cross-community bridge._
- **Why does `OfxImportService` connect `OfxParserService` to `TelegramWebhookController.php`?**
  _High betweenness centrality (0.088) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `Illuminate\Support\Facades\Log`, `FiscalModuleTest`, `FiscalConcursoDataService`, `TelegramWebhookController.php`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`?**
  _High betweenness centrality (0.073) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08994708994708994 - nodes in this community are weakly interconnected._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.1437908496732026 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.0573025856044724 - nodes in this community are weakly interconnected._