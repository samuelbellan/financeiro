# Graph Report - financeiro  (2026-10-09)

## Corpus Check
- 175 files · ~297,470 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 969 nodes · 1907 edges · 101 communities (74 shown, 27 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 33 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `d859a880`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- Illuminate\Http\Request
- SalaryCalculatorService
- TelegramWebhookController.php
- composer.json
- User
- scripts
- CategorySanitizer
- devDependencies
- FiscalModuleTest
- .http
- FiscalConcurso
- BodyTrackerTest
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest.php
- DailyLog
- DatabaseSyncTest
- README.md
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- WorkoutSession
- FiscalConcursosController
- StretchingLog
- Illuminate\Database\Eloquent\Model
- Transacao
- TestCase
- TelegramWebhookController
- Exercise
- FiscalConcursoDataService
- Illuminate\Support\Str
- Categoria
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- CartaoCompra
- UserBodyMetric
- WorkoutSessionExercise
- SalaryProjectionTest
- components.sync-modal
- RunningLog
- FitnessAiModuleTest
- transaction-autocomplete.js
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- Cartao
- FiscalNoticia

## God Nodes (most connected - your core abstractions)
1. `User` - 65 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 37 edges
4. `Transacao` - 33 edges
5. `TestCase` - 28 edges
6. `CartaoCompra` - 27 edges
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
- `FitnessAiModuleTest` --references--> `User`  [EXTRACTED]
  tests/Feature/FitnessAiModuleTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (101 total, 27 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.23
Nodes (6): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.05
Nodes (18): AuthController, BodyTrackerController, Controller, DatabaseSyncController, EstudosController, ExportController, HomeController, SalaryController (+10 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.07
Nodes (13): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+5 more)

### Community 3 - "TelegramWebhookController.php"
Cohesion: 0.19
Nodes (8): CartaoParcela, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.17
Nodes (6): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "CategorySanitizer"
Cohesion: 0.19
Nodes (4): CategorySanitizer, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - ".http"
Cohesion: 0.08
Nodes (11): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, TelegramSetWebhook, DatabaseSyncService, TelegramService (+3 more)

### Community 15 - "TelegramWebhookTest.php"
Cohesion: 0.29
Nodes (3): Mockery, Mockery\MockInterface, TelegramWebhookTest

### Community 16 - "DailyLog"
Cohesion: 0.06
Nodes (10): TreinosController, DailyLog, GearItem, Measurement, ProgressPhoto, WorkoutPlan, BodyTrackerService, FitnessAiService (+2 more)

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
Cohesion: 0.29
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.15
Nodes (5): FiscalTelegramConfig, SalaryProfile, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Transacao"
Cohesion: 0.10
Nodes (6): FinancasController, Transacao, TransacaoPrevisao, TransactionSuggestionService, TelegramWebhookRoutingTest, TransactionSuggestionTest

### Community 36 - "TestCase"
Cohesion: 0.15
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, ExampleTest, MercadoModuleTest, TestCase, WhatsappMessageParserTest

### Community 43 - "TelegramWebhookController"
Cohesion: 0.08
Nodes (7): MercadoController, JsonResponse, TelegramWebhookController, NotaFiscal, NotaFiscalItem, WhatsappLog, Carbon

### Community 44 - "Exercise"
Cohesion: 0.09
Nodes (4): Exercise, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "Categoria"
Cohesion: 0.20
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 54 - "CartaoCompra"
Cohesion: 0.14
Nodes (4): CartoesController, CartaoCompra, CartaoPrevisao, CartaoCompraTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 89 - "Cartao"
Cohesion: 0.16
Nodes (4): Cartao, CreditCardService, GeminiService, WhatsappMessageParser

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **27 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `SalaryCalculatorService`, `TelegramWebhookController.php`, `CategorySanitizer`, `FiscalModuleTest`, `BodyTrackerTest`, `TelegramWebhookTest.php`, `DailyLog`, `DatabaseSyncTest`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `TestCase`, `TelegramWebhookController`, `Exercise`, `CartaoCompra`, `SalaryProjectionTest`, `FitnessAiModuleTest`, `Cartao`, `FiscalNoticia`?**
  _High betweenness centrality (0.109) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController.php`, `User`, `FiscalModuleTest`, `TelegramWebhookController`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`, `FiscalConcursosController`?**
  _High betweenness centrality (0.059) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `SalaryCalculatorService`, `TelegramWebhookController.php`, `Transacao`, `SalaryProjectionTest`, `FiscalModuleTest`, `BodyTrackerTest`, `FitnessAiModuleTest`, `Exercise`, `TelegramWebhookTest.php`, `DailyLog`, `DatabaseSyncTest`, `CartaoCompra`, `FiscalNoticia`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.05454545454545454 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.07293868921775898 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04878048780487805 - nodes in this community are weakly interconnected._