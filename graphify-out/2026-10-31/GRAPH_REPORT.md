# Graph Report - financeiro  (2026-10-31)

## Corpus Check
- 220 files · ~358,961 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1243 nodes · 2567 edges · 143 communities (101 shown, 42 thin omitted)
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
- FiscalConcursosController
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- .count
- TelegramWebhookController.php
- README.md
- Transacao
- Illuminate\Database\Migrations\Migration
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- Illuminate\Database\Eloquent\Relations\HasMany
- GearItem
- Illuminate\Http\JsonResponse
- Illuminate\Database\Schema\Blueprint
- StudyGoal
- Illuminate\Database\Eloquent\Model
- DailyLog
- Controller
- NetworkService
- Measurement
- TelegramWebhookRoutingTest
- StatementImportTest
- ProgressPhoto
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- WorkoutSession
- WorkoutModuleTest.php
- SalaryProjectionTest
- components.conciliacao-fatura-modal
- components.import-extrato-modal
- home.blade.php
- TreinosController
- RunningLog
- TreinosController.php
- CartaoParcela
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- FiscalNoticia
- Illuminate\Support\Facades\Log
- Illuminate\Http\Request
- PhotoAuth.php
- bootstrap/app.php
- psr-4
- require
- qrcode.min.js
- extra
- WorkoutModuleTest
- TvWidgetEndpointTest
- TestCase
- FitnessAiService
- CartaoCompra
- FiscalNewsAiService
- Exercise
- CategoriasController
- BodyTrackerTest
- DatabaseSyncTest
- AuthController
- MercadoModuleTest
- autoload-dev
- dev
- post-autoload-dump

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

## Communities (143 total, 42 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 1 - "NotaFiscal"
Cohesion: 0.13
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, Illuminate\Support\Facades\Auth

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
Cohesion: 0.17
Nodes (12): scripts, post-create-project-cmd, post-update-cmd, pre-package-uninstall, test, Illuminate\\Foundation\\ComposerScripts::prePackageUninstall, @php artisan config:clear --ansi, @php artisan key:generate --ansi (+4 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (18): OfxAccountDTO, OfxImportResultDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, InvalidArgumentException, PDO (+10 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "Cartao"
Cohesion: 0.17
Nodes (3): CartoesController, Cartao, CreditCardService

### Community 16 - ".count"
Cohesion: 0.05
Nodes (16): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ScanPhotoModelsCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand (+8 more)

### Community 17 - "TelegramWebhookController.php"
Cohesion: 0.12
Nodes (6): JsonResponse, TelegramWebhookController, WhatsappLog, GeminiService, WhatsappMessageParser, Carbon

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "Transacao"
Cohesion: 0.14
Nodes (5): Categoria, Transacao, CategorySanitizer, TransactionSuggestionService, TransactionSuggestionTest

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

### Community 29 - "Illuminate\Http\JsonResponse"
Cohesion: 0.16
Nodes (4): BodyTrackerController, DatabaseSyncController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 31 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.15
Nodes (5): Conta, SalaryProfile, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 36 - "Controller"
Cohesion: 0.11
Nodes (5): TvWidgetController, Controller, HomeController, SalaryController, Illuminate\Support\Facades\Route

### Community 46 - "ProgressPhoto"
Cohesion: 0.16
Nodes (4): ProgressPhoto, ExerciseCatalogSeeder, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 76 - "TreinosController.php"
Cohesion: 0.12
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 77 - "CartaoParcela"
Cohesion: 0.11
Nodes (10): ExportController, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\URL (+2 more)

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 91 - "Illuminate\Support\Facades\Log"
Cohesion: 0.31
Nodes (4): Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, ZipArchive

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
Cohesion: 0.12
Nodes (9): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Cache, Mockery\MockInterface, ExampleTest, ImportOfxCommandTest, TelegramOfxDocumentTest, TestCase (+1 more)

### Community 138 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 139 - "dev"
Cohesion: 0.67
Nodes (3): dev, Composer\\Config::disableProcessTimeout, npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others

### Community 140 - "post-autoload-dump"
Cohesion: 0.67
Nodes (3): post-autoload-dump, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan package:discover --ansi

## Knowledge Gaps
- **86 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+81 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **42 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `NotaFiscal`, `SalaryCalculatorService`, `BodyTrackerTest`, `DatabaseSyncTest`, `MercadoModuleTest`, `FiscalConcurso`, `TelegramWebhookTest`, `TelegramWebhookController.php`, `Transacao`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Database\Eloquent\Model`, `DailyLog`, `Controller`, `NetworkService`, `TelegramWebhookRoutingTest`, `StatementImportTest`, `ProgressPhoto`, `WorkoutModuleTest.php`, `SalaryProjectionTest`, `CartaoParcela`, `FiscalNoticia`, `WorkoutModuleTest`, `TvWidgetEndpointTest`, `TestCase`, `CartaoCompra`?**
  _High betweenness centrality (0.123) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `NotaFiscal`, `SalaryCalculatorService`, `User`, `BodyTrackerTest`, `DatabaseSyncTest`, `MercadoModuleTest`, `FiscalConcurso`, `TelegramWebhookTest`, `Transacao`, `DailyLog`, `NetworkService`, `TelegramWebhookRoutingTest`, `StatementImportTest`, `ProgressPhoto`, `WorkoutModuleTest.php`, `SalaryProjectionTest`, `CartaoParcela`, `FiscalNoticia`, `WorkoutModuleTest`, `TvWidgetEndpointTest`, `CartaoCompra`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **Why does `Measurement` connect `Measurement` to `Illuminate\Database\Eloquent\Model`, `DailyLog`, `BodyTrackerTest`, `FitnessAiService`, `TreinosController.php`, `ProgressPhoto`, `Illuminate\Support\Facades\Log`?**
  _High betweenness centrality (0.039) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _86 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08620689655172414 - nodes in this community are weakly interconnected._
- **Should `NotaFiscal` be split into smaller, more focused modules?**
  _Cohesion score 0.12554112554112554 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.07293868921775898 - nodes in this community are weakly interconnected._