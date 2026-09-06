# Graph Report - financeiro  (2026-09-04)

## Corpus Check
- 152 files · ~258,488 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 785 nodes · 1445 edges · 96 communities (70 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 26 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f01f0c52`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- CategorySanitizer
- CartaoParcela
- SalaryCalculatorService
- TelegramService
- composer.json
- User
- scripts
- Categoria
- devDependencies
- Illuminate\Database\Eloquent\Model
- TelegramWebhookController
- FiscalConcurso
- Cartao
- FiscalNewsAiService
- FiscalNewsCrawlerService
- TestCase
- FiscalConcursosController
- NotaFiscal
- README.md
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- WorkoutSession
- Illuminate\Database\Schema\Blueprint
- StudyGoal
- FiscalModuleTest
- bootstrap/app.php
- Illuminate\Http\Request
- Exercise
- FiscalConcursoDataService
- web.php
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- FiscalNoticia
- WorkoutSessionExercise
- WorkoutPlan
- Illuminate\Database\Eloquent\Relations\BelongsTo
- SalaryProjectionTest
- GearItem
- RunningLog
- AuthController
- UserBodyMetric
- Illuminate\Support\Facades\Log

## God Nodes (most connected - your core abstractions)
1. `FiscalConcurso` - 52 edges
2. `User` - 45 edges
3. `Cartao` - 32 edges
4. `FiscalNoticia` - 25 edges
5. `TelegramWebhookController` - 21 edges
6. `Transacao` - 21 edges
7. `FiscalNewsCrawlerService` - 21 edges
8. `FiscalConcursoDataService` - 20 edges
9. `FiscalTelegramNotifierService` - 20 edges
10. `FiscalModuleTest` - 20 edges

## Surprising Connections (you probably didn't know these)
- `WorkoutModuleTest` --references--> `User`  [EXTRACTED]
  tests/Feature/WorkoutModuleTest.php → app/Models/User.php
- `WhatsappMessageParserTest` --references--> `WhatsappMessageParser`  [EXTRACTED]
  tests/Unit/WhatsappMessageParserTest.php → app/Services/WhatsappMessageParser.php
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Http/Controllers/Controller.php
- `CartoesController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CartoesController.php → app/Http/Controllers/Controller.php
- `CategoriasController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CategoriasController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (96 total, 26 thin omitted)

### Community 0 - "CategorySanitizer"
Cohesion: 0.08
Nodes (12): CategorySanitizer, UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder (+4 more)

### Community 1 - "CartaoParcela"
Cohesion: 0.16
Nodes (5): Controller, ExportController, FinancasController, CartaoParcela, TransacaoPrevisao

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramService"
Cohesion: 0.09
Nodes (10): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, GeminiService, TelegramService, Command, Illuminate\Console\Command (+2 more)

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.15
Nodes (6): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "Categoria"
Cohesion: 0.15
Nodes (4): CategoriasController, Categoria, Subcategoria, WhatsappMessageParser

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.19
Nodes (4): RunningSplit, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Storage

### Community 10 - "TelegramWebhookController"
Cohesion: 0.21
Nodes (4): TelegramWebhookController, Transacao, WhatsappLog, Carbon

### Community 12 - "Cartao"
Cohesion: 0.14
Nodes (7): Cartao, CartaoCompra, CreditCardService, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Cache

### Community 15 - "TestCase"
Cohesion: 0.19
Nodes (5): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase, WhatsappMessageParserTest

### Community 17 - "NotaFiscal"
Cohesion: 0.14
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.17
Nodes (11): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como testar e gerenciar o Bot do Telegram na Nuvem, 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+3 more)

### Community 23 - "AppServiceProvider"
Cohesion: 0.33
Nodes (3): AppServiceProvider, Illuminate\Support\Facades\URL, Illuminate\Support\ServiceProvider

### Community 24 - "sidebar.js"
Cohesion: 0.47
Nodes (3): getCurrentScrollPosition(), getScrollContainer(), saveScrollPosition()

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 28 - "WorkoutSession"
Cohesion: 0.12
Nodes (3): StretchingLog, WorkoutSession, WorkoutModuleTest

### Community 31 - "StudyGoal"
Cohesion: 0.22
Nodes (3): EstudosController, StudyGoal, StudyLog

### Community 34 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 39 - "Illuminate\Http\Request"
Cohesion: 0.33
Nodes (3): CartoesController, CartaoPrevisao, Illuminate\Http\Request

### Community 47 - "web.php"
Cohesion: 0.18
Nodes (3): HomeController, MercadoController, Illuminate\Support\Facades\Route

## Knowledge Gaps
- **81 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+76 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `CategorySanitizer`, `FiscalModuleTest`, `SalaryCalculatorService`, `SalaryProjectionTest`, `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController`, `Cartao`, `Exercise`, `TestCase`, `NotaFiscal`, `WorkoutSession`?**
  _High betweenness centrality (0.105) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `FiscalModuleTest`, `User`, `Illuminate\Support\Facades\Log`, `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController`, `Cartao`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalConcursosController`, `TestCase`, `FiscalNoticia`?**
  _High betweenness centrality (0.069) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Cartao` to `CartaoParcela`, `TelegramService`, `Illuminate\Http\Request`, `Illuminate\Support\Facades\Log`, `Illuminate\Database\Eloquent\Model`, `TelegramWebhookController`, `Categoria`, `web.php`, `NotaFiscal`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _81 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CategorySanitizer` be split into smaller, more focused modules?**
  _Cohesion score 0.08172043010752689 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._
- **Should `TelegramService` be split into smaller, more focused modules?**
  _Cohesion score 0.09243697478991597 - nodes in this community are weakly interconnected._