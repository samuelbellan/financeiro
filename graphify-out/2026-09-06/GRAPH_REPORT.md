# Graph Report - financeiro  (2026-09-04)

## Corpus Check
- 154 files · ~260,794 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 791 nodes · 1470 edges · 89 communities (66 shown, 23 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 26 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f01f0c52`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- CategorySanitizer
- Illuminate\Http\Request
- SalaryCalculatorService
- TelegramService
- composer.json
- User
- scripts
- User.php
- devDependencies
- Illuminate\Database\Eloquent\Model
- FiscalConcurso
- Cartao
- FiscalNewsAiService
- FiscalNewsCrawlerService
- Illuminate\Foundation\Testing\RefreshDatabase
- FiscalConcursosController
- TestCase
- README.md
- Illuminate\Database\Migrations\Migration
- Illuminate\Database\Schema\Blueprint
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- WorkoutSession
- Illuminate\Support\Facades\Schema
- FiscalModuleTest
- Exercise
- FiscalConcursoDataService
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- ExercisePersonalRecord
- WorkoutSessionExercise
- WorkoutPlan
- SalaryProjectionTest
- GearItem
- RunningLog
- WorkoutModuleTest.php
- FiscalNoticia

## God Nodes (most connected - your core abstractions)
1. `FiscalConcurso` - 52 edges
2. `User` - 45 edges
3. `Cartao` - 32 edges
4. `FiscalNoticia` - 25 edges
5. `TelegramWebhookController` - 21 edges
6. `Transacao` - 21 edges
7. `FiscalNewsCrawlerService` - 21 edges
8. `Exercise` - 20 edges
9. `FiscalConcursoDataService` - 20 edges
10. `FiscalTelegramNotifierService` - 20 edges

## Surprising Connections (you probably didn't know these)
- `WorkoutModuleTest` --references--> `User`  [EXTRACTED]
  tests/Feature/WorkoutModuleTest.php → app/Models/User.php
- `WhatsappMessageParserTest` --references--> `WhatsappMessageParser`  [EXTRACTED]
  tests/Unit/WhatsappMessageParserTest.php → app/Services/WhatsappMessageParser.php
- `CartoesController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CartoesController.php → app/Http/Controllers/Controller.php
- `FiscalConcursosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FiscalConcursosController.php → app/Http/Controllers/Controller.php
- `MercadoController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/MercadoController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (89 total, 23 thin omitted)

### Community 0 - "CategorySanitizer"
Cohesion: 0.07
Nodes (14): CategorySanitizer, UserFactory, DatabaseSeeder, ExerciseCatalogSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory (+6 more)

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.05
Nodes (21): AuthController, CategoriasController, Controller, EstudosController, ExportController, FinancasController, HomeController, TreinosController (+13 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramService"
Cohesion: 0.10
Nodes (9): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, GeminiService, TelegramService, Command, Illuminate\Console\Command (+1 more)

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.17
Nodes (3): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "User.php"
Cohesion: 0.50
Nodes (3): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Notifications\Notifiable

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.17
Nodes (5): SalaryProfile, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo, Illuminate\Support\Facades\Storage

### Community 12 - "Cartao"
Cohesion: 0.06
Nodes (16): CartoesController, MercadoController, TelegramWebhookController, Cartao, CartaoCompra, CartaoParcela, CartaoPrevisao, NotaFiscal (+8 more)

### Community 17 - "TestCase"
Cohesion: 0.18
Nodes (4): Illuminate\Foundation\Testing\TestCase, ExampleTest, MercadoModuleTest, TestCase

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

### Community 77 - "WorkoutModuleTest.php"
Cohesion: 0.13
Nodes (3): StretchingLog, UserBodyMetric, WorkoutModuleTest

### Community 103 - "FiscalNoticia"
Cohesion: 0.15
Nodes (4): FiscalNoticia, FiscalTelegramConfig, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

## Knowledge Gaps
- **81 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+76 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **23 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `CategorySanitizer`, `FiscalModuleTest`, `SalaryCalculatorService`, `SalaryProjectionTest`, `FiscalNoticia`, `User.php`, `Illuminate\Database\Eloquent\Model`, `Cartao`, `WorkoutModuleTest.php`, `TestCase`?**
  _High betweenness centrality (0.104) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `FiscalModuleTest`, `User`, `FiscalNoticia`, `Illuminate\Database\Eloquent\Model`, `Cartao`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalConcursosController`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Cartao` to `Illuminate\Http\Request`, `TelegramService`, `Illuminate\Database\Eloquent\Model`, `FiscalNoticia`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _81 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CategorySanitizer` be split into smaller, more focused modules?**
  _Cohesion score 0.0748663101604278 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.054987212276214836 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._