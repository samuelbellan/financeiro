# Graph Report - financeiro  (2026-10-10)

## Corpus Check
- 177 files · ~301,369 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 982 nodes · 1970 edges · 104 communities (78 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 35 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- BodyTrackerController
- SalaryCalculatorService
- Cartao
- composer.json
- User
- scripts
- Transacao
- devDependencies
- NotaFiscal
- Illuminate\Console\Command
- FiscalConcurso
- StudyGoal
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- DailyLog
- .http
- README.md
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- GearItem
- TelegramWebhookRoutingTest.php
- WhatsappMessageParserTest
- Illuminate\Database\Eloquent\Model
- FitnessAiModuleTest
- Illuminate\Foundation\Testing\RefreshDatabase
- TelegramWebhookController
- Exercise
- FiscalConcursoDataService
- TelegramWebhookController.php
- CategoriasController
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- TreinosController.php
- Illuminate\Http\Request
- WhatsappLog
- bootstrap/app.php
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- Controller
- RunningLog
- WorkoutSession
- WorkoutPlan
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- FiscalNoticia
- TestCase

## God Nodes (most connected - your core abstractions)
1. `User` - 73 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 42 edges
4. `Transacao` - 39 edges
5. `CartaoCompra` - 31 edges
6. `TestCase` - 30 edges
7. `DailyLog` - 27 edges
8. `Measurement` - 26 edges
9. `FiscalNoticia` - 25 edges
10. `TelegramWebhookController` - 24 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php
- `DatabaseSyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DatabaseSyncTest.php → app/Models/User.php
- `FinancialSummaryAndCardLimitsTest` --references--> `User`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (104 total, 26 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.12
Nodes (9): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+1 more)

### Community 1 - "BodyTrackerController"
Cohesion: 0.13
Nodes (5): BodyTrackerController, DatabaseSyncController, DatabaseSyncService, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "Cartao"
Cohesion: 0.14
Nodes (7): Cartao, CartaoCompra, CartaoParcela, CreditCardService, Carbon\Carbon, Illuminate\Support\Facades\Auth, FinancialSummaryAndCardLimitsTest

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.09
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, FiscalModuleTest

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "Transacao"
Cohesion: 0.11
Nodes (8): ExportController, FinancasController, Categoria, Transacao, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService, TransactionSuggestionService

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - "Illuminate\Console\Command"
Cohesion: 0.14
Nodes (8): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, Command, Illuminate\Console\Command

### Community 12 - "StudyGoal"
Cohesion: 0.18
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 16 - "DailyLog"
Cohesion: 0.06
Nodes (9): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage (+1 more)

### Community 17 - ".http"
Cohesion: 0.18
Nodes (3): GeminiService, TelegramService, PendingRequest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "AppServiceProvider"
Cohesion: 0.33
Nodes (3): AppServiceProvider, Illuminate\Support\Facades\URL, Illuminate\Support\ServiceProvider

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (6): NotaFiscalItem, SalaryProfile, StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 36 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.18
Nodes (3): Illuminate\Foundation\Testing\RefreshDatabase, DatabaseSyncTest, TransactionSuggestionTest

### Community 43 - "TelegramWebhookController"
Cohesion: 0.27
Nodes (3): JsonResponse, TelegramWebhookController, Carbon

### Community 44 - "Exercise"
Cohesion: 0.12
Nodes (4): Exercise, ExerciseCatalogSeeder, Illuminate\Support\Str, Pdo\Mysql

### Community 46 - "TelegramWebhookController.php"
Cohesion: 0.29
Nodes (5): Barryvdh\DomPDF\Facade\Pdf, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 53 - "TreinosController.php"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 54 - "Illuminate\Http\Request"
Cohesion: 0.28
Nodes (3): CartoesController, CartaoPrevisao, Illuminate\Http\Request

### Community 59 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 74 - "Controller"
Cohesion: 0.13
Nodes (6): AuthController, Controller, HomeController, MercadoController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 91 - "FiscalNoticia"
Cohesion: 0.11
Nodes (4): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig, FiscalTelegramNotifierService

### Community 106 - "TestCase"
Cohesion: 0.20
Nodes (4): Illuminate\Foundation\Testing\TestCase, CartaoCompraTest, ExampleTest, TestCase

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Seeder`, `SalaryCalculatorService`, `Cartao`, `Illuminate\Foundation\Testing\RefreshDatabase`, `FitnessAiModuleTest`, `SalaryProjectionTest`, `Transacao`, `NotaFiscal`, `TestCase`, `TelegramWebhookController`, `Exercise`, `WorkoutSession`, `TelegramWebhookController.php`, `TelegramWebhookTest`, `DailyLog`, `TelegramWebhookRoutingTest.php`?**
  _High betweenness centrality (0.116) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `FiscalConcursoDataService`, `TelegramWebhookController.php`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `SalaryCalculatorService`, `FitnessAiModuleTest`, `Cartao`, `Illuminate\Foundation\Testing\RefreshDatabase`, `User`, `SalaryProjectionTest`, `Transacao`, `NotaFiscal`, `Exercise`, `WorkoutSession`, `TelegramWebhookTest`, `DailyLog`, `TelegramWebhookRoutingTest.php`, `WhatsappMessageParserTest`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.11688311688311688 - nodes in this community are weakly interconnected._
- **Should `BodyTrackerController` be split into smaller, more focused modules?**
  _Cohesion score 0.12698412698412698 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._