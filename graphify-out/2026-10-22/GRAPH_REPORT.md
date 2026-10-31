# Graph Report - financeiro  (2026-10-20)

## Corpus Check
- 186 files · ~320,687 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1029 nodes · 2074 edges · 115 communities (82 shown, 33 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 35 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `1d948b5b`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- Transacao
- SalaryCalculatorService
- TelegramWebhookController
- composer.json
- User
- scripts
- CartaoParcela
- devDependencies
- NotaFiscal
- .http
- FiscalConcurso
- BodyTrackerController
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
- BodyTrackerTest
- Illuminate\Foundation\Testing\RefreshDatabase
- Illuminate\Database\Eloquent\Model
- TestCase
- CategoriasController
- Illuminate\Http\Request
- TelegramWebhookRoutingTest.php
- FiscalConcursoDataService
- Illuminate\Support\Facades\Log
- Controller
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- StudyGoal
- Categoria
- Exercise
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- WhatsappMessageParserTest
- RunningLog
- WorkoutSession
- WorkoutModuleTest
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- TreinosController.php
- FiscalConcursosController
- PhotoAuth.php
- bootstrap/app.php
- TransactionSuggestionTest
- FiscalNoticia
- TelegramWebhookController.php
- AuthController

## God Nodes (most connected - your core abstractions)
1. `User` - 82 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 43 edges
4. `Transacao` - 40 edges
5. `CartaoCompra` - 32 edges
6. `TestCase` - 32 edges
7. `DailyLog` - 27 edges
8. `Measurement` - 26 edges
9. `CartaoParcela` - 25 edges
10. `FiscalNoticia` - 25 edges

## Surprising Connections (you probably didn't know these)
- `FinancialSummaryAndCardLimitsTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/FinancialSummaryAndCardLimitsTest.php → app/Models/Cartao.php
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

## Communities (115 total, 33 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 1 - "Transacao"
Cohesion: 0.15
Nodes (7): ExportController, FinancasController, CartaoPrevisao, Transacao, TransacaoPrevisao, FinancialSummaryService, Illuminate\Support\Facades\URL

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramWebhookController"
Cohesion: 0.18
Nodes (4): JsonResponse, TelegramWebhookController, WhatsappLog, Carbon

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.13
Nodes (7): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "NotaFiscal"
Cohesion: 0.09
Nodes (5): MercadoController, NotaFiscal, NotaFiscalItem, GeminiService, MercadoModuleTest

### Community 10 - ".http"
Cohesion: 0.08
Nodes (12): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, PregeneratePhotoCacheCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, DatabaseSyncService (+4 more)

### Community 12 - "BodyTrackerController"
Cohesion: 0.21
Nodes (3): BodyTrackerController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 16 - "DailyLog"
Cohesion: 0.08
Nodes (7): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, Illuminate\Http\UploadedFile

### Community 17 - "Cartao"
Cohesion: 0.14
Nodes (5): CartoesController, Cartao, CartaoCompra, CreditCardService, CartaoCompraTest

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

### Community 29 - "BodyTrackerTest"
Cohesion: 0.14
Nodes (3): ExerciseCatalogSeeder, Illuminate\Support\Facades\Storage, BodyTrackerTest

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (5): SalaryProfile, WorkoutSet, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "TestCase"
Cohesion: 0.18
Nodes (4): Illuminate\Foundation\Testing\TestCase, ExampleTest, FitnessAiModuleTest, TestCase

### Community 44 - "TelegramWebhookRoutingTest.php"
Cohesion: 0.20
Nodes (3): Illuminate\Support\Facades\Cache, Mockery\MockInterface, TelegramWebhookRoutingTest

### Community 47 - "Controller"
Cohesion: 0.18
Nodes (4): Controller, DatabaseSyncController, HomeController, Illuminate\Support\Facades\Route

### Community 54 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 55 - "Categoria"
Cohesion: 0.17
Nodes (3): Categoria, CategorySanitizer, TransactionSuggestionService

### Community 77 - "WorkoutModuleTest"
Cohesion: 0.12
Nodes (3): StretchingLog, WorkoutPlanItem, WorkoutModuleTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 105 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 106 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 113 - "TelegramWebhookController.php"
Cohesion: 0.32
Nodes (4): Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\DB

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **33 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `Transacao`, `SalaryCalculatorService`, `TelegramWebhookController`, `CartaoParcela`, `NotaFiscal`, `FiscalConcurso`, `TelegramWebhookTest`, `Cartao`, `BodyTrackerTest`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Illuminate\Database\Eloquent\Model`, `TestCase`, `TelegramWebhookRoutingTest.php`, `FiscalConcursoDataService`, `Categoria`, `SalaryProjectionTest`, `WorkoutModuleTest`, `TransactionSuggestionTest`, `TelegramWebhookController.php`?**
  _High betweenness centrality (0.126) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `FiscalConcursoDataService`, `Illuminate\Support\Facades\Log`, `FiscalNewsAiService`, `FiscalNoticia`, `TelegramWebhookController.php`, `FiscalNewsCrawlerService`, `FiscalConcursosController`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **Why does `CartaoCompra` connect `Cartao` to `Illuminate\Database\Eloquent\Model`, `Transacao`, `TelegramWebhookController`, `CartaoParcela`, `TelegramWebhookController.php`, `Categoria`?**
  _High betweenness centrality (0.033) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08994708994708994 - nodes in this community are weakly interconnected._
- **Should `Transacao` be split into smaller, more focused modules?**
  _Cohesion score 0.1477832512315271 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._