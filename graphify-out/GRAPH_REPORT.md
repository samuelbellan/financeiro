# Graph Report - financeiro  (2026-09-02)

## Corpus Check
- 143 files · ~268,143 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 717 nodes · 1314 edges · 89 communities (74 shown, 15 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `0142b2d6`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- PhotoController
- Illuminate\Http\Request
- SalaryCalculatorService
- TelegramService
- composer.json
- Illuminate\Console\Command
- scripts
- TestCase
- devDependencies
- User.php
- NotaFiscal
- FiscalConcurso
- Illuminate\Database\Eloquent\Model
- FiscalNewsAiService
- FiscalNewsCrawlerService
- SalaryProjectionTest
- FiscalNoticia
- User
- README.md
- Illuminate\Support\Facades\Schema
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- AppServiceProvider
- sidebar.js
- deploy
- logging.php
- ExampleTest
- Illuminate\Foundation\Testing\RefreshDatabase
- Illuminate\Database\Schema\Blueprint
- PhotoAuth.php
- bootstrap/app.php
- FiscalConcursoDataService
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- Illuminate\Database\Migrations\Migration
- Illuminate\Database\Seeder
- Illuminate\Support\Facades\Log

## God Nodes (most connected - your core abstractions)
1. `FiscalConcurso` - 52 edges
2. `User` - 41 edges
3. `Cartao` - 32 edges
4. `FiscalNoticia` - 25 edges
5. `TelegramWebhookController` - 21 edges
6. `Transacao` - 21 edges
7. `FiscalNewsCrawlerService` - 21 edges
8. `PhotoController` - 20 edges
9. `FiscalConcursoDataService` - 20 edges
10. `FiscalTelegramNotifierService` - 20 edges

## Surprising Connections (you probably didn't know these)
- `WhatsappMessageParserTest` --references--> `WhatsappMessageParser`  [EXTRACTED]
  tests/Unit/WhatsappMessageParserTest.php → app/Services/WhatsappMessageParser.php
- `CategoriasController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CategoriasController.php → app/Http/Controllers/Controller.php
- `EstudosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/EstudosController.php → app/Http/Controllers/Controller.php
- `FiscalConcursosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FiscalConcursosController.php → app/Http/Controllers/Controller.php
- `PhotoController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/PhotoController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (89 total, 15 thin omitted)

### Community 0 - "PhotoController"
Cohesion: 0.08
Nodes (7): PhotoController, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql, static

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (23): AuthController, CartoesController, Controller, ExportController, FinancasController, HomeController, MercadoController, TelegramWebhookController (+15 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (15): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+7 more)

### Community 3 - "TelegramService"
Cohesion: 0.08
Nodes (7): CategoriasController, Categoria, Subcategoria, GeminiService, TelegramService, WhatsappMessageParser, PendingRequest

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "Illuminate\Console\Command"
Cohesion: 0.18
Nodes (7): ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, Command, Illuminate\Console\Command, Illuminate\Support\Facades\DB

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "TestCase"
Cohesion: 0.15
Nodes (5): Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\Storage, ExampleTest, PhotoModuleTest, TestCase

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "User.php"
Cohesion: 0.29
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

### Community 10 - "NotaFiscal"
Cohesion: 0.14
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 12 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (8): EstudosController, CartaoPrevisao, StudyGoal, StudyLog, WhatsappLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 16 - "FiscalNoticia"
Cohesion: 0.12
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 17 - "User"
Cohesion: 0.17
Nodes (3): User, Illuminate\Foundation\Auth\User, FiscalModuleTest

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

### Community 31 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 34 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 97 - "Illuminate\Database\Seeder"
Cohesion: 0.25
Nodes (5): DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

## Knowledge Gaps
- **81 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+76 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **15 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `PhotoController`, `Illuminate\Http\Request`, `Illuminate\Database\Seeder`, `SalaryCalculatorService`, `TestCase`, `User.php`, `NotaFiscal`, `Illuminate\Database\Eloquent\Model`, `FiscalConcursoDataService`, `SalaryProjectionTest`?**
  _High betweenness centrality (0.106) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Http\Request`, `Illuminate\Support\Facades\Log`, `User.php`, `Illuminate\Database\Eloquent\Model`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalNoticia`, `User`?**
  _High betweenness centrality (0.067) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Illuminate\Http\Request` to `NotaFiscal`, `TelegramService`, `Illuminate\Database\Eloquent\Model`, `Illuminate\Support\Facades\Log`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _81 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.08333333333333333 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.06092384519350812 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.0573025856044724 - nodes in this community are weakly interconnected._