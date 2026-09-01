# Graph Report - financeiro  (2026-08-31)

## Corpus Check
- 140 files · ~156,886 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 685 nodes · 1268 edges · 90 communities (73 shown, 17 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6a4102d4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Cartao
- SalaryCalculatorService
- TelegramService
- composer.json
- User
- scripts
- TestCase
- devDependencies
- UserFactory.php
- NotaFiscal
- FiscalConcurso
- Illuminate\Database\Eloquent\Model
- FiscalNewsAiService
- FiscalNewsCrawlerService
- SalaryProjectionTest
- Illuminate\Console\Command
- FiscalModuleTest
- README.md
- Illuminate\Support\Facades\Schema
- AppServiceProvider
- sidebar.js
- logging.php
- ExampleTest
- Illuminate\Database\Schema\Blueprint
- FiscalNoticia
- FiscalConcursoDataService
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- Illuminate\Database\Migrations\Migration
- DatabaseSeeder.php
- FiscalConcursosController.php
- User.php

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
- `AuthController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/AuthController.php → app/Http/Controllers/Controller.php
- `CategoriasController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CategoriasController.php → app/Http/Controllers/Controller.php
- `EstudosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/EstudosController.php → app/Http/Controllers/Controller.php
- `FiscalConcursosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FiscalConcursosController.php → app/Http/Controllers/Controller.php
- `MercadoController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/MercadoController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (90 total, 17 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.10
Nodes (10): AuthController, PhotoController, PhotoAuth, Closure, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\RedirectResponse (+2 more)

### Community 1 - "Cartao"
Cohesion: 0.06
Nodes (22): CartoesController, Controller, ExportController, FinancasController, HomeController, TelegramWebhookController, Cartao, CartaoCompra (+14 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "TelegramService"
Cohesion: 0.07
Nodes (8): CategoriasController, MercadoController, Categoria, Subcategoria, GeminiService, TelegramService, WhatsappMessageParser, PendingRequest

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.18
Nodes (4): User, Illuminate\Foundation\Auth\User, Illuminate\Support\Facades\Storage, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "TestCase"
Cohesion: 0.28
Nodes (4): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, ExampleTest, TestCase

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "UserFactory.php"
Cohesion: 0.16
Nodes (6): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str, Pdo\Mysql, static

### Community 10 - "NotaFiscal"
Cohesion: 0.13
Nodes (3): NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 12 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.11
Nodes (8): EstudosController, FiscalTelegramConfig, SalaryProfile, StudyGoal, StudyLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 16 - "Illuminate\Console\Command"
Cohesion: 0.25
Nodes (5): FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, Command, Illuminate\Console\Command

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 24 - "sidebar.js"
Cohesion: 0.47
Nodes (3): getCurrentScrollPosition(), getScrollContainer(), saveScrollPosition()

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 97 - "DatabaseSeeder.php"
Cohesion: 0.36
Nodes (4): DatabaseSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 104 - "User.php"
Cohesion: 0.29
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

## Knowledge Gaps
- **65 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+60 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **17 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Cartao`, `DatabaseSeeder.php`, `SalaryCalculatorService`, `TestCase`, `User.php`, `UserFactory.php`, `NotaFiscal`, `Illuminate\Database\Eloquent\Model`, `SalaryProjectionTest`, `FiscalModuleTest`?**
  _High betweenness centrality (0.080) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Cartao`, `FiscalNoticia`, `User`, `FiscalConcursosController.php`, `User.php`, `TestCase`, `Illuminate\Database\Eloquent\Model`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalModuleTest`?**
  _High betweenness centrality (0.062) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Cartao` to `TelegramService`, `TestCase`, `FiscalConcursosController.php`, `NotaFiscal`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _65 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.09682539682539683 - nodes in this community are weakly interconnected._
- **Should `Cartao` be split into smaller, more focused modules?**
  _Cohesion score 0.05906553041434029 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._