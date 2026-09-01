# Graph Report - financeiro  (2026-08-13)

## Corpus Check
- 129 files · ~134,292 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 617 nodes · 1079 edges · 83 communities (70 shown, 13 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 20 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6a4102d4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Cartao
- SalaryCalculatorService
- Categoria
- composer.json
- Illuminate\Database\Eloquent\Model
- scripts
- ImagineController
- devDependencies
- FFmpegController
- OmniRouteController
- User
- TestCase
- CategorySanitizer
- DatabaseSeeder.php
- SalaryProjectionTest
- ImagineModuleTest
- FFmpegModuleTest
- README.md
- Illuminate\Database\Schema\Blueprint
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Migrations\Migration
- User.php
- AppServiceProvider
- sidebar.js
- bootstrap/app.php
- logging.php
- ExampleTest
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh

## God Nodes (most connected - your core abstractions)
1. `User` - 44 edges
2. `Cartao` - 28 edges
3. `PhotoController` - 20 edges
4. `Transacao` - 20 edges
5. `CartaoCompra` - 19 edges
6. `CartaoParcela` - 19 edges
7. `TelegramWebhookController` - 18 edges
8. `CartoesController` - 17 edges
9. `Categoria` - 17 edges
10. `Controller` - 16 edges

## Surprising Connections (you probably didn't know these)
- `CartoesController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CartoesController.php → app/Http/Controllers/Controller.php
- `CategoriasController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/CategoriasController.php → app/Http/Controllers/Controller.php
- `EstudosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/EstudosController.php → app/Http/Controllers/Controller.php
- `FFmpegController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FFmpegController.php → app/Http/Controllers/Controller.php
- `ImagineController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ImagineController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (83 total, 13 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (15): AuthController, Controller, ExportController, FinancasController, HomeController, OpenCutController, PhotoController, PhotoAuth (+7 more)

### Community 1 - "Cartao"
Cohesion: 0.09
Nodes (12): CartoesController, TelegramWebhookController, Cartao, CartaoCompra, CartaoParcela, CartaoPrevisao, WhatsappLog, CreditCardService (+4 more)

### Community 2 - "SalaryCalculatorService"
Cohesion: 0.06
Nodes (14): ConsignadoDTO, self, EventoAuxilioDTO, self, FilhoDTO, self, self, QualificacaoPermanenteDTO (+6 more)

### Community 3 - "Categoria"
Cohesion: 0.07
Nodes (11): TelegramSetWebhook, CategoriasController, Categoria, Subcategoria, GeminiService, TelegramService, WhatsappMessageParser, Illuminate\Console\Command (+3 more)

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.11
Nodes (8): EstudosController, NotaFiscalItem, SalaryProfile, StudyGoal, StudyLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 7 - "ImagineController"
Cohesion: 0.12
Nodes (6): ImagineController, Illuminate\Support\Facades\File, Illuminate\Support\Facades\Process, Illuminate\Support\Str, Pdo\Mysql, ZipArchive

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 11 - "User"
Cohesion: 0.24
Nodes (3): User, Illuminate\Foundation\Auth\User, PhotoModuleTest

### Community 12 - "TestCase"
Cohesion: 0.20
Nodes (4): Illuminate\Foundation\Testing\TestCase, ExampleTest, OmniRouteModuleTest, TestCase

### Community 13 - "CategorySanitizer"
Cohesion: 0.24
Nodes (4): CategorySanitizer, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 14 - "DatabaseSeeder.php"
Cohesion: 0.31
Nodes (5): DatabaseSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 16 - "ImagineModuleTest"
Cohesion: 0.25
Nodes (3): Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage, ImagineModuleTest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 22 - "User.php"
Cohesion: 0.33
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

### Community 24 - "sidebar.js"
Cohesion: 0.47
Nodes (3): getCurrentScrollPosition(), getScrollContainer(), saveScrollPosition()

### Community 25 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

## Knowledge Gaps
- **65 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+60 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **13 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Cartao`, `SalaryCalculatorService`, `Illuminate\Database\Eloquent\Model`, `TestCase`, `CategorySanitizer`, `DatabaseSeeder.php`, `SalaryProjectionTest`, `ImagineModuleTest`, `FFmpegModuleTest`, `User.php`?**
  _High betweenness centrality (0.105) - this node is a cross-community bridge._
- **Why does `Controller` connect `Illuminate\Http\Request` to `Cartao`, `SalaryCalculatorService`, `Categoria`, `Illuminate\Database\Eloquent\Model`, `ImagineController`, `FFmpegController`, `OmniRouteController`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `TelegramService` connect `Categoria` to `Cartao`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _65 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.0625 - nodes in this community are weakly interconnected._
- **Should `Cartao` be split into smaller, more focused modules?**
  _Cohesion score 0.09216255442670537 - nodes in this community are weakly interconnected._
- **Should `SalaryCalculatorService` be split into smaller, more focused modules?**
  _Cohesion score 0.06033182503770739 - nodes in this community are weakly interconnected._