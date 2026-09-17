# Graph Report - financeiro  (2026-09-16)

## Corpus Check
- 164 files · ~275,255 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 856 nodes · 1625 edges · 98 communities (78 shown, 20 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 25 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `44a9b785`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- DatabaseSyncService
- SalaryCalculatorService
- Illuminate\Database\Schema\Blueprint
- composer.json
- User
- scripts
- Illuminate\Database\Eloquent\Factories\HasFactory
- devDependencies
- GearItem
- Illuminate\Console\Command
- FiscalConcurso
- Cartao
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest.php
- TelegramService
- NotaFiscal
- README.md
- Illuminate\Database\Migrations\Migration
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- WorkoutSession
- Illuminate\Support\Facades\Schema
- bootstrap/app.php
- FiscalModuleTest
- Transacao
- TelegramWebhookController
- Exercise
- FiscalConcursoDataService
- Categoria
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- Illuminate\Http\Request
- Illuminate\Database\Eloquent\Model
- WorkoutPlan
- CategorySanitizer
- SalaryProjectionTest
- components.sync-modal
- StudyGoal
- RunningLog
- TestCase
- Controller
- transaction-autocomplete.js
- FiscalNoticia

## God Nodes (most connected - your core abstractions)
1. `User` - 57 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 37 edges
4. `Transacao` - 29 edges
5. `CartaoCompra` - 27 edges
6. `FiscalNoticia` - 25 edges
7. `TestCase` - 22 edges
8. `TelegramWebhookController` - 21 edges
9. `FiscalNewsCrawlerService` - 21 edges
10. `Categoria` - 20 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php
- `DatabaseSyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DatabaseSyncTest.php → app/Models/User.php
- `TransactionSuggestionTest` --references--> `User`  [EXTRACTED]
  tests/Feature/TransactionSuggestionTest.php → app/Models/User.php
- `WorkoutModuleTest` --references--> `User`  [EXTRACTED]
  tests/Feature/WorkoutModuleTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (98 total, 20 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.10
Nodes (12): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Cache (+4 more)

### Community 1 - "DatabaseSyncService"
Cohesion: 0.28
Nodes (3): DatabaseSyncController, DatabaseSyncService, Illuminate\Http\JsonResponse

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.18
Nodes (6): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "Illuminate\Database\Eloquent\Factories\HasFactory"
Cohesion: 0.22
Nodes (3): UserBodyMetric, WorkoutPlanItem, Illuminate\Database\Eloquent\Factories\HasFactory

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - "Illuminate\Console\Command"
Cohesion: 0.17
Nodes (7): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, TelegramSetWebhook, Command, Illuminate\Console\Command

### Community 11 - "FiscalConcurso"
Cohesion: 0.09
Nodes (3): FiscalConcurso, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 12 - "Cartao"
Cohesion: 0.15
Nodes (7): Cartao, CartaoParcela, CreditCardService, WhatsappMessageParser, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth

### Community 15 - "TelegramWebhookTest.php"
Cohesion: 0.29
Nodes (3): Mockery, Mockery\MockInterface, TelegramWebhookTest

### Community 17 - "NotaFiscal"
Cohesion: 0.08
Nodes (6): MercadoController, NotaFiscal, NotaFiscalItem, GeminiService, Illuminate\Support\Facades\Storage, MercadoModuleTest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como testar e gerenciar o Bot do Telegram na Nuvem, 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

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

### Community 31 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 35 - "Transacao"
Cohesion: 0.13
Nodes (5): FinancasController, Transacao, TransacaoPrevisao, TransactionSuggestionService, TransactionSuggestionTest

### Community 43 - "TelegramWebhookController"
Cohesion: 0.24
Nodes (3): TelegramWebhookController, WhatsappLog, Carbon

### Community 44 - "Exercise"
Cohesion: 0.12
Nodes (3): Exercise, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "Categoria"
Cohesion: 0.18
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 53 - "ExercisePersonalRecord"
Cohesion: 0.16
Nodes (3): TreinosController, ExercisePersonalRecord, WorkoutGoal

### Community 54 - "Illuminate\Http\Request"
Cohesion: 0.14
Nodes (4): CartoesController, CartaoCompra, CartaoPrevisao, Illuminate\Http\Request

### Community 55 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.15
Nodes (4): SalaryProfile, WorkoutSessionExercise, WorkoutSet, Illuminate\Database\Eloquent\Model

### Community 74 - "StudyGoal"
Cohesion: 0.18
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 76 - "TestCase"
Cohesion: 0.12
Nodes (7): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, CartaoCompraTest, DatabaseSyncTest, ExampleTest, TestCase, WhatsappMessageParserTest

### Community 77 - "Controller"
Cohesion: 0.15
Nodes (6): AuthController, Controller, ExportController, HomeController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 103 - "FiscalNoticia"
Cohesion: 0.11
Nodes (4): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig, Illuminate\Database\Eloquent\Relations\BelongsTo

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **20 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `FiscalModuleTest`, `SalaryCalculatorService`, `Transacao`, `SalaryProjectionTest`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `TelegramWebhookController`, `Cartao`, `TestCase`, `FiscalConcurso`, `TelegramWebhookTest.php`, `Exercise`, `NotaFiscal`?**
  _High betweenness centrality (0.107) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `FiscalModuleTest`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `FiscalNoticia`, `TelegramWebhookController`, `Cartao`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.067) - this node is a cross-community bridge._
- **Why does `DatabaseSyncService` connect `DatabaseSyncService` to `Illuminate\Database\Seeder`, `Illuminate\Console\Command`, `TestCase`?**
  _High betweenness centrality (0.044) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.09538461538461539 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04878048780487805 - nodes in this community are weakly interconnected._