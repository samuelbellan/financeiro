# Graph Report - financeiro  (2026-10-11)

## Corpus Check
- 177 files · ~302,988 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 984 nodes · 1980 edges · 100 communities (77 shown, 23 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 35 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- Controller
- SalaryCalculatorService
- TelegramWebhookController.php
- composer.json
- User
- scripts
- Illuminate\Http\Request
- devDependencies
- NotaFiscal
- .http
- FiscalConcurso
- StudyGoal
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest
- DailyLog
- Cartao
- README.md
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- GearItem
- CartaoCompra
- WhatsappMessageParserTest
- Illuminate\Database\Eloquent\Model
- TestCase
- DatabaseSyncTest
- GeminiService
- WorkoutPlan
- FiscalConcursoDataService
- Illuminate\Support\Facades\Http
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- TreinosController.php
- Transacao
- bootstrap/app.php
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- RunningLog
- WorkoutSession
- Exercise
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- FiscalNoticia

## God Nodes (most connected - your core abstractions)
1. `User` - 73 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 42 edges
4. `Transacao` - 39 edges
5. `CartaoCompra` - 32 edges
6. `TestCase` - 30 edges
7. `DailyLog` - 27 edges
8. `Measurement` - 26 edges
9. `FiscalNoticia` - 25 edges
10. `TelegramWebhookController` - 24 edges

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

## Communities (100 total, 23 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 1 - "Controller"
Cohesion: 0.06
Nodes (12): AuthController, BodyTrackerController, CategoriasController, Controller, DatabaseSyncController, HomeController, Subcategoria, DatabaseSyncService (+4 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramWebhookController.php"
Cohesion: 0.14
Nodes (6): JsonResponse, TelegramWebhookController, WhatsappLog, Barryvdh\DomPDF\Facade\Pdf, Carbon, Illuminate\Support\Facades\DB

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.18
Nodes (6): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "Illuminate\Http\Request"
Cohesion: 0.16
Nodes (6): ExportController, FinancasController, TransacaoPrevisao, FinancialSummaryService, Illuminate\Http\Request, Illuminate\Support\Facades\Auth

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - ".http"
Cohesion: 0.08
Nodes (11): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, TelegramService, Command (+3 more)

### Community 12 - "StudyGoal"
Cohesion: 0.18
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 15 - "TelegramWebhookTest"
Cohesion: 0.20
Nodes (3): Mockery, Mockery\MockInterface, TelegramWebhookTest

### Community 16 - "DailyLog"
Cohesion: 0.05
Nodes (10): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage (+2 more)

### Community 17 - "Cartao"
Cohesion: 0.17
Nodes (3): CartoesController, Cartao, CreditCardService

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

### Community 29 - "CartaoCompra"
Cohesion: 0.24
Nodes (5): CartaoCompra, CartaoParcela, CartaoPrevisao, Carbon\Carbon, FinancialSummaryAndCardLimitsTest

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (6): SalaryProfile, StretchingLog, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "TestCase"
Cohesion: 0.16
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, CartaoCompraTest, ExampleTest, MercadoModuleTest, TestCase

### Community 46 - "Illuminate\Support\Facades\Http"
Cohesion: 0.33
Nodes (3): Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 53 - "TreinosController.php"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

### Community 55 - "Transacao"
Cohesion: 0.14
Nodes (5): Categoria, Transacao, CategorySanitizer, TransactionSuggestionService, TransactionSuggestionTest

### Community 59 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 77 - "Exercise"
Cohesion: 0.09
Nodes (4): Exercise, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 91 - "FiscalNoticia"
Cohesion: 0.12
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **23 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Model`, `Illuminate\Database\Seeder`, `SalaryCalculatorService`, `TelegramWebhookController.php`, `TestCase`, `DatabaseSyncTest`, `SalaryProjectionTest`, `Illuminate\Http\Request`, `NotaFiscal`, `.http`, `FiscalConcurso`, `Exercise`, `Illuminate\Support\Facades\Http`, `TelegramWebhookTest`, `DailyLog`, `Cartao`, `Transacao`, `CartaoCompra`?**
  _High betweenness centrality (0.116) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController.php`, `TestCase`, `FiscalConcursoDataService`, `Illuminate\Support\Facades\Http`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `SalaryCalculatorService`, `DatabaseSyncTest`, `SalaryProjectionTest`, `.http`, `FiscalConcurso`, `Exercise`, `Illuminate\Support\Facades\Http`, `TelegramWebhookTest`, `DailyLog`, `Transacao`, `CartaoCompra`, `WhatsappMessageParserTest`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08994708994708994 - nodes in this community are weakly interconnected._
- **Should `Controller` be split into smaller, more focused modules?**
  _Cohesion score 0.06462585034013606 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._