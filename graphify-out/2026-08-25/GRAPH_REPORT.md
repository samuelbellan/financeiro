# Graph Report - financeiro  (2026-08-25)

## Corpus Check
- 143 files · ~156,281 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 700 nodes · 1290 edges · 93 communities (72 shown, 21 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 23 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6a4102d4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- TelegramWebhookController
- SalaryCalculatorService
- TelegramService
- composer.json
- Illuminate\Database\Eloquent\Model
- scripts
- ImagineController
- devDependencies
- FFmpegController
- FiscalNoticia
- FiscalConcurso
- Categoria
- FiscalNewsCrawlerService
- Illuminate\Console\Command
- SalaryProjectionTest.php
- SalaryController
- User
- README.md
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- SalaryController.php
- AppServiceProvider
- sidebar.js
- .project
- logging.php
- ExampleTest
- FilhoDTO
- Illuminate\Database\Schema\Blueprint
- FiscalConcursoDataService
- QualificacaoPermanenteDTO
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- QualificacaoTemporariaDTO
- ServidorDTO
- bootstrap/app.php

## God Nodes (most connected - your core abstractions)
1. `User` - 48 edges
2. `FiscalConcurso` - 35 edges
3. `Cartao` - 30 edges
4. `FiscalNoticia` - 22 edges
5. `TelegramWebhookController` - 21 edges
6. `PhotoController` - 20 edges
7. `Transacao` - 20 edges
8. `FiscalNewsCrawlerService` - 20 edges
9. `CartaoCompra` - 19 edges
10. `CartaoParcela` - 19 edges

## Surprising Connections (you probably didn't know these)
- `CartoesController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CartoesController.php → app/Http/Controllers/Controller.php
- `CategoriasController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CategoriasController.php → app/Http/Controllers/Controller.php
- `EstudosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/EstudosController.php → app/Http/Controllers/Controller.php
- `FFmpegController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FFmpegController.php → app/Http/Controllers/Controller.php
- `FiscalConcursosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FiscalConcursosController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (93 total, 21 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (16): AuthController, Controller, ExportController, FinancasController, HomeController, OpenCutController, PhotoController, PhotoAuth (+8 more)

### Community 1 - "TelegramWebhookController"
Cohesion: 0.20
Nodes (4): TelegramWebhookController, WhatsappLog, WhatsappMessageParser, Carbon

### Community 3 - "TelegramService"
Cohesion: 0.13
Nodes (4): OmniRouteController, GeminiService, TelegramService, PendingRequest

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.06
Nodes (16): CartoesController, EstudosController, Cartao, CartaoCompra, CartaoParcela, CartaoPrevisao, NotaFiscalItem, SalaryProfile (+8 more)

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "ImagineController"
Cohesion: 0.08
Nodes (11): ImagineController, CategorySanitizer, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\File, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Process, Illuminate\Support\Str (+3 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 10 - "FiscalNoticia"
Cohesion: 0.16
Nodes (4): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig, FiscalTelegramNotifierService

### Community 11 - "FiscalConcurso"
Cohesion: 0.13
Nodes (3): FiscalConcurso, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log

### Community 12 - "Categoria"
Cohesion: 0.17
Nodes (3): CategoriasController, Categoria, Subcategoria

### Community 14 - "Illuminate\Console\Command"
Cohesion: 0.25
Nodes (5): FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, Command, Illuminate\Console\Command

### Community 17 - "User"
Cohesion: 0.05
Nodes (20): User, DatabaseSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Database\Seeder (+12 more)

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 24 - "sidebar.js"
Cohesion: 0.47
Nodes (3): getCurrentScrollPosition(), getScrollContainer(), saveScrollPosition()

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 85 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

## Knowledge Gaps
- **65 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+60 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **21 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `TelegramWebhookController`, `Illuminate\Database\Eloquent\Model`, `ImagineController`, `FiscalConcurso`, `SalaryProjectionTest.php`?**
  _High betweenness centrality (0.097) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `TelegramWebhookController`, `FiscalConcursoDataService`, `Illuminate\Database\Eloquent\Model`, `FiscalNoticia`, `FiscalNewsCrawlerService`, `User`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **Why does `Controller` connect `Illuminate\Http\Request` to `TelegramWebhookController`, `TelegramService`, `Illuminate\Database\Eloquent\Model`, `ImagineController`, `FFmpegController`, `FiscalNoticia`, `Categoria`, `SalaryController`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _65 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.060153776571687016 - nodes in this community are weakly interconnected._
- **Should `TelegramService` be split into smaller, more focused modules?**
  _Cohesion score 0.12903225806451613 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04878048780487805 - nodes in this community are weakly interconnected._