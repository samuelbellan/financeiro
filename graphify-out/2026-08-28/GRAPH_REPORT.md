# Graph Report - financeiro  (2026-08-28)

## Corpus Check
- 146 files · ~170,648 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 740 nodes · 1369 edges · 104 communities (76 shown, 28 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 25 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6a4102d4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- PhotoController
- Illuminate\Http\Request
- SalaryCalculatorService
- .http
- composer.json
- User
- scripts
- ImagineController
- devDependencies
- FFmpegController
- FiscalConcurso
- Illuminate\Database\Eloquent\Model
- FiscalNewsAiService
- FiscalNewsCrawlerService
- SalaryProjectionTest.php
- Illuminate\Console\Command
- FiscalModuleTest
- README.md
- Illuminate\Support\Facades\Schema
- SalaryController
- AppServiceProvider
- sidebar.js
- FiscalNoticia
- logging.php
- ExampleTest
- SalaryController.php
- Illuminate\Database\Schema\Blueprint
- PhotoAuth.php
- .project
- FilhoDTO
- bootstrap/app.php
- FiscalConcursoDataService
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- Illuminate\Database\Migrations\Migration
- QualificacaoPermanenteDTO
- TestCase
- FFmpegModuleTest
- QualificacaoTemporariaDTO
- ServidorDTO
- DatabaseSeeder.php
- OmniRouteModuleTest
- ImagineModuleTest
- Illuminate\Support\Facades\Log
- User.php

## God Nodes (most connected - your core abstractions)
1. `FiscalConcurso` - 52 edges
2. `User` - 52 edges
3. `Cartao` - 30 edges
4. `FiscalNoticia` - 25 edges
5. `TelegramWebhookController` - 21 edges
6. `FiscalNewsCrawlerService` - 21 edges
7. `PhotoController` - 20 edges
8. `Transacao` - 20 edges
9. `FiscalConcursoDataService` - 20 edges
10. `FiscalTelegramNotifierService` - 20 edges

## Surprising Connections (you probably didn't know these)
- `EstudosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/EstudosController.php → app/Http/Controllers/Controller.php
- `FFmpegController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FFmpegController.php → app/Http/Controllers/Controller.php
- `FiscalConcursosController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/FiscalConcursosController.php → app/Http/Controllers/Controller.php
- `HomeController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/HomeController.php → app/Http/Controllers/Controller.php
- `ImagineController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/ImagineController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (104 total, 28 thin omitted)

### Community 0 - "PhotoController"
Cohesion: 0.08
Nodes (7): HomeController, PhotoController, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Route, static

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.05
Nodes (25): AuthController, CartoesController, CategoriasController, Controller, ExportController, FinancasController, TelegramWebhookController, Cartao (+17 more)

### Community 3 - ".http"
Cohesion: 0.12
Nodes (4): OmniRouteController, GeminiService, TelegramService, PendingRequest

### Community 4 - "composer.json"
Cohesion: 0.05
Nodes (40): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+32 more)

### Community 5 - "User"
Cohesion: 0.24
Nodes (3): User, Illuminate\Foundation\Auth\User, PhotoModuleTest

### Community 6 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 8 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 9 - "FFmpegController"
Cohesion: 0.09
Nodes (7): FFmpegController, OpenCutController, Illuminate\Support\Facades\File, Illuminate\Support\Facades\Process, Illuminate\Support\Str, Pdo\Mysql, ZipArchive

### Community 12 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (9): EstudosController, CartaoPrevisao, NotaFiscalItem, SalaryProfile, StudyGoal, StudyLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model (+1 more)

### Community 16 - "Illuminate\Console\Command"
Cohesion: 0.25
Nodes (5): FiscalCrawlCommand, FiscalSeedDataCommand, TelegramSetWebhook, Command, Illuminate\Console\Command

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 24 - "sidebar.js"
Cohesion: 0.47
Nodes (3): getCurrentScrollPosition(), getScrollContainer(), saveScrollPosition()

### Community 25 - "FiscalNoticia"
Cohesion: 0.12
Nodes (4): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig, FiscalTelegramNotifierService

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 31 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 44 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 85 - "TestCase"
Cohesion: 0.24
Nodes (5): Illuminate\Foundation\Testing\TestCase, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Storage, ExampleTest, TestCase

### Community 97 - "DatabaseSeeder.php"
Cohesion: 0.36
Nodes (4): DatabaseSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder

### Community 104 - "User.php"
Cohesion: 0.29
Nodes (4): Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Notifications\Notifiable

## Knowledge Gaps
- **65 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+60 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **28 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `PhotoController`, `Illuminate\Http\Request`, `DatabaseSeeder.php`, `OmniRouteModuleTest`, `ImagineModuleTest`, `User.php`, `Illuminate\Database\Eloquent\Model`, `SalaryProjectionTest.php`, `FiscalModuleTest`, `TestCase`, `FFmpegModuleTest`?**
  _High betweenness centrality (0.098) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Http\Request`, `Illuminate\Support\Facades\Log`, `User.php`, `Illuminate\Database\Eloquent\Model`, `FiscalConcursoDataService`, `FiscalNewsAiService`, `FiscalNewsCrawlerService`, `FiscalModuleTest`, `TestCase`, `FiscalNoticia`?**
  _High betweenness centrality (0.060) - this node is a cross-community bridge._
- **Why does `Controller` connect `Illuminate\Http\Request` to `PhotoController`, `.http`, `ImagineController`, `FFmpegController`, `Illuminate\Database\Eloquent\Model`, `SalaryController`, `FiscalNoticia`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _65 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.08333333333333333 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.052360338074623786 - nodes in this community are weakly interconnected._
- **Should `.http` be split into smaller, more focused modules?**
  _Cohesion score 0.11742424242424243 - nodes in this community are weakly interconnected._