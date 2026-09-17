# Graph Report - financeiro  (2026-09-16)

## Corpus Check
- 161 files · ~271,806 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 838 nodes · 1569 edges · 100 communities (74 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 24 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `44a9b785`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- FiscalNoticia
- SalaryCalculatorService
- Illuminate\Database\Schema\Blueprint
- composer.json
- User
- scripts
- Illuminate\Database\Eloquent\Model
- devDependencies
- GearItem
- TelegramService
- FiscalConcurso
- Cartao
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TelegramWebhookTest.php
- Illuminate\Support\Facades\Log
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
- bootstrap/app.php
- FiscalModuleTest
- Transacao
- TelegramWebhookController.php
- Exercise
- FiscalConcursoDataService
- Categoria
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- Illuminate\Http\Request
- WorkoutSessionExercise
- WorkoutPlan
- GeminiService
- SalaryProjectionTest
- components.sync-modal
- StudyGoal
- RunningLog
- TestCase
- web.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- DatabaseSyncTest
- Illuminate\Support\Facades\Schema
- FiscalConcursosController

## God Nodes (most connected - your core abstractions)
1. `User` - 53 edges
2. `FiscalConcurso` - 52 edges
3. `Cartao` - 35 edges
4. `FiscalNoticia` - 25 edges
5. `CartaoCompra` - 23 edges
6. `Transacao` - 22 edges
7. `TelegramWebhookController` - 21 edges
8. `FiscalNewsCrawlerService` - 21 edges
9. `Exercise` - 20 edges
10. `FiscalConcursoDataService` - 20 edges

## Surprising Connections (you probably didn't know these)
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php
- `DatabaseSyncTest` --references--> `User`  [EXTRACTED]
  tests/Feature/DatabaseSyncTest.php → app/Models/User.php
- `WorkoutModuleTest` --references--> `User`  [EXTRACTED]
  tests/Feature/WorkoutModuleTest.php → app/Models/User.php
- `WhatsappMessageParserTest` --references--> `WhatsappMessageParser`  [EXTRACTED]
  tests/Unit/WhatsappMessageParserTest.php → app/Services/WhatsappMessageParser.php

## Import Cycles
- None detected.

## Communities (100 total, 26 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash (+3 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.17
Nodes (6): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.16
Nodes (5): CartaoPrevisao, StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Storage

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - "TelegramService"
Cohesion: 0.09
Nodes (11): FiscalCrawlCommand, FiscalSeedDataCommand, SyncDatabaseCommand, TelegramSetWebhook, DatabaseSyncController, DatabaseSyncService, TelegramService, Command (+3 more)

### Community 12 - "Cartao"
Cohesion: 0.17
Nodes (7): Cartao, CartaoCompra, CartaoParcela, CreditCardService, Carbon, Carbon\Carbon, Illuminate\Support\Facades\Auth

### Community 15 - "TelegramWebhookTest.php"
Cohesion: 0.29
Nodes (3): Mockery, Mockery\MockInterface, TelegramWebhookTest

### Community 17 - "NotaFiscal"
Cohesion: 0.14
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

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
Cohesion: 0.16
Nodes (6): Controller, ExportController, FinancasController, Transacao, TransacaoPrevisao, CategorySanitizer

### Community 43 - "TelegramWebhookController.php"
Cohesion: 0.17
Nodes (4): TelegramWebhookController, WhatsappLog, WhatsappMessageParser, Barryvdh\DomPDF\Facade\Pdf

### Community 44 - "Exercise"
Cohesion: 0.10
Nodes (4): Exercise, WorkoutSet, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "Categoria"
Cohesion: 0.20
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 74 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 75 - "RunningLog"
Cohesion: 0.15
Nodes (3): TreinosController, RunningLog, UserBodyMetric

### Community 76 - "TestCase"
Cohesion: 0.13
Nodes (6): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, CartaoCompraTest, ExampleTest, TestCase, WhatsappMessageParserTest

### Community 77 - "web.php"
Cohesion: 0.22
Nodes (4): AuthController, HomeController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 95 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.22
Nodes (4): ExportProductionDataCommand, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Schema

## Knowledge Gaps
- **83 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+78 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `FiscalModuleTest`, `SalaryCalculatorService`, `SalaryProjectionTest`, `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController.php`, `Cartao`, `TestCase`, `Exercise`, `TelegramWebhookTest.php`, `NotaFiscal`, `DatabaseSyncTest`?**
  _High betweenness centrality (0.105) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `FiscalModuleTest`, `FiscalNoticia`, `User`, `Illuminate\Database\Eloquent\Model`, `FiscalConcursosController`, `TelegramWebhookController.php`, `TestCase`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `Illuminate\Support\Facades\Log`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._
- **Why does `DatabaseSyncService` connect `TelegramService` to `DatabaseSyncTest`, `Illuminate\Support\Facades\Schema`?**
  _High betweenness centrality (0.045) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _83 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.08994708994708994 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04878048780487805 - nodes in this community are weakly interconnected._