# Graph Report - financeiro  (2026-11-18)

## Corpus Check
- 318 files · ~674,932 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2200 nodes · 5248 edges · 196 communities (153 shown, 43 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 120 edges (avg confidence: 0.76)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `3fa964cb`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- .count
- CreditCardReconciliationTest
- EventoAuxilioDTO
- PhotoController
- composer.json
- User
- scripts
- OfxImportService
- devDependencies
- FiscalConcursosController
- widget.js
- FiscalConcurso
- OfxParserService
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- esc
- .app
- TelegramWebhookController
- README.md
- GearItem
- Cartao
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- StudySyncTest
- AllowedGoogleEmail
- Illuminate\Database\Schema\Blueprint
- tick
- Illuminate\Database\Eloquent\Model
- Conta
- Illuminate\Http\JsonResponse
- NetworkService
- StudySummaryService
- main.js
- GurujaMcpService
- DailyLog
- OfxAccountDTO
- Exercise
- MidiaEstudosCommandTest
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- BodyTrackerTest
- TelegramWebhookRoutingTest
- .http
- EstudosApiTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- story.js
- WorkoutSession
- SalaryCalculatorService
- CartaoCompra
- transaction-autocomplete.js
- SalaryProjectionTest
- Carbon\Carbon
- NotaFiscal
- OfxParserServiceTest
- Transacao
- EstudosController
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- Controller
- StudySyncService
- bootstrap/app.php
- psr-4
- bosses.js
- render
- require
- qrcode.min.js
- extra
- dayStats
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- FiscalNoticia
- TestCase
- EstudosGurujaTest
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- Illuminate\Database\Migrations\Migration
- Módulo de estudos estilo YPT (offline)
- ServidorDTO
- post-create-project-cmd
- SalaryController
- planImport
- WorkoutSessionExercise
- AppServiceProvider
- estudos-sw.js
- Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)
- Illuminate\Support\Facades\Schema
- Categoria
- TransacaoUpdateDateTest
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo
- ResumoEstudosCommandTest
- StudyRoomsTest
- ExercisePersonalRecord
- TvWidgetEndpointTest
- OfxTransactionDTO
- Sincronização da Guruja no App de Estudos
- guruja.md

## God Nodes (most connected - your core abstractions)
1. `User` - 195 edges
2. `TestCase` - 72 edges
3. `Cartao` - 66 edges
4. `Transacao` - 64 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `esc()` - 37 edges
9. `StudySyncTest` - 37 edges
10. `PhotoController` - 34 edges

## Surprising Connections (you probably didn't know these)
- `gurujaPlan()` --indirect_call--> `a()`  [INFERRED]
  resources/js/estudos/main.js → public/js/qrcode.min.js
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `CartaoCompraTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/Cartao.php
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `CreditCardReconciliationTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardReconciliationTest.php → app/Models/Cartao.php

## Import Cycles
- None detected.

## Communities (196 total, 43 thin omitted)

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 3 - "PhotoController"
Cohesion: 0.08
Nodes (8): PhotoController, DatabaseSeeder, ExerciseCatalogSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, Illuminate\Support\Facades\Hash

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.05
Nodes (10): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, CreditCardDeleteTest, EstudosGurujaMcpTest (+2 more)

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxImportService"
Cohesion: 0.14
Nodes (9): OfxImportResultDTO, OfxImportService, PDO, PDO, PDOException, OfxImportServiceTest, PDO, Throwable (+1 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 10 - "widget.js"
Cohesion: 0.21
Nodes (11): renderSettings(), reforcoAlertStage(), restAlertStage(), restMaxGapMs(), createWidget(), esc(), SIZE, widgetSupported() (+3 more)

### Community 12 - "OfxParserService"
Cohesion: 0.23
Nodes (3): OfxParserService, getPdoConnection(), PDO

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.16
Nodes (3): FiscalConcursoDataService, FiscalNewsAiService, FiscalTelegramNotifierService

### Community 15 - "esc"
Cohesion: 0.10
Nodes (53): cycleMark(), blockKey(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText(), colorVars() (+45 more)

### Community 17 - "TelegramWebhookController"
Cohesion: 0.17
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "GearItem"
Cohesion: 0.12
Nodes (4): GearItem, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 20 - "Cartao"
Cohesion: 0.20
Nodes (4): Cartao, CreditCardService, Carbon, CreditCardClosingTest

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.07
Nodes (63): projPaces(), renderSyncBadge(), addDays(), allTimeStats(), amountBetween(), buildDaily(), compareWithPrevious(), comparisonRanges() (+55 more)

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - "Illuminate\Console\Command"
Cohesion: 0.06
Nodes (16): BackupEstudosCommand, ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportarHoleritesCommand, ImportOfxCommand, MidiaEstudosCommand, PregeneratePhotoCacheCommand (+8 more)

### Community 29 - "AllowedGoogleEmail"
Cohesion: 0.08
Nodes (6): AllowedEmailController, AllowedGoogleEmail, Mockery, GoogleAuthAndWhitelistTest, ManagersTest, TelegramWebhookTest

### Community 31 - "tick"
Cohesion: 0.11
Nodes (37): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound(), avatarStage() (+29 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.10
Nodes (7): CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Conta"
Cohesion: 0.12
Nodes (4): Conta, Mockery\MockInterface, StatementImportTest, TelegramOfxDocumentTest

### Community 36 - "Illuminate\Http\JsonResponse"
Cohesion: 0.06
Nodes (11): BodyTrackerController, DatabaseSyncController, EstudosApiController, CarbonImmutable, StudyRoomsController, DatabaseSyncService, Carbon, StudyRoomService (+3 more)

### Community 40 - "StudySummaryService"
Cohesion: 0.32
Nodes (3): CarbonImmutable, StudySummaryService, Illuminate\Database\ConnectionInterface

### Community 41 - "main.js"
Cohesion: 0.06
Nodes (66): activeSubjects(), apiTokensCall(), applyTheme(), canvasToPngBlob(), charts, chartTooltip, colorOf(), createApiToken() (+58 more)

### Community 42 - "GurujaMcpService"
Cohesion: 0.20
Nodes (3): GurujaSyncCommand, GurujaMcpService, Carbon

### Community 43 - "DailyLog"
Cohesion: 0.08
Nodes (6): TreinosController, DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService

### Community 44 - "OfxAccountDTO"
Cohesion: 0.20
Nodes (4): OfxAccountDTO, OfxStatementDTO, InvalidArgumentException, RuntimeException

### Community 45 - "Exercise"
Cohesion: 0.07
Nodes (4): Exercise, WorkoutPlan, WorkoutPlanItem, WorkoutModuleTest

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 74 - "story.js"
Cohesion: 0.25
Nodes (14): canonicalSubjectName(), consolidateSubjects(), dayMidnightMs(), ensureFontsLoaded(), fillRoundRect(), formatCompactDuration(), formatHoursMinutes(), formatStoryDate() (+6 more)

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "CartaoCompra"
Cohesion: 0.09
Nodes (8): ExportController, CartaoCompra, CartaoParcela, CartaoPrevisao, TransacaoPrevisao, FinancialSummaryService, Illuminate\Support\Facades\URL, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Carbon\Carbon"
Cohesion: 0.12
Nodes (11): GurujaComandoResumoService, WhatsappMessageParser, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Carbon, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http (+3 more)

### Community 84 - "NotaFiscal"
Cohesion: 0.11
Nodes (5): MercadoController, NotaFiscal, NotaFiscalItem, GeminiService, Illuminate\Support\Facades\Storage

### Community 87 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 88 - "Transacao"
Cohesion: 0.12
Nodes (3): Transacao, TransactionDeduplicationService, TransactionDeduplicationTest

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (6): CartoesController, FinancasController, PhotoAuth, Closure, Illuminate\Http\Request, Symfony\Component\HttpFoundation\Response

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 96 - "Controller"
Cohesion: 0.14
Nodes (6): TvWidgetController, AuthController, Controller, HomeController, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 102 - "bosses.js"
Cohesion: 0.23
Nodes (15): BOSSES, bossSvg(), DEFAULT_COLORS, ensureModelViewer(), escSvg(), ground(), koStars(), linear() (+7 more)

### Community 104 - "render"
Cohesion: 0.09
Nodes (56): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+48 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 108 - "dayStats"
Cohesion: 0.14
Nodes (17): BOSS_HP, bossMaxHp(), JORNADA_PADRAO, jornadaState(), normalizeJornada(), objectiveProgressMs(), allTypes(), gurujaStudyType() (+9 more)

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.05
Nodes (13): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Http\UploadedFile, Illuminate\Support\Str, Pdo\Mysql, BackupEstudosCommandTest, CartaoCompraTest, DatabaseSyncTest (+5 more)

### Community 124 - "avatars-vetor.js"
Cohesion: 0.08
Nodes (27): AVATAR_LIMITES_MIN, AVATAR_PADRAO, AVATARS, BICHINHO, eggCrack, happyFace(), AVATARS_IMAGEM, FAMILIA (+19 more)

### Community 125 - "Sabatina 2026-11-01: widget flutuante do cronômetro de estudos"
Cohesion: 0.25
Nodes (7): Decisões tomadas, Implementação, Pergunta original, Próximos passos (se quiser evoluir), Riscos e pontos em aberto, Sabatina 2026-11-01: widget flutuante do cronômetro de estudos, Suposições

### Community 135 - "Módulo de estudos estilo YPT (offline)"
Cohesion: 0.20
Nodes (9): Avatares, Backup no PC, Decisões, Jornada dos Bosses, Módulo de estudos estilo YPT (offline), Onde está o código, Riscos conhecidos, Roteiro de teste manual (modo avião) (+1 more)

### Community 139 - "post-create-project-cmd"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 145 - "planImport"
Cohesion: 0.48
Nodes (4): normalize(), planImport(), toUuid(), empty

### Community 148 - "estudos-sw.js"
Cohesion: 0.40
Nodes (3): shellResponse(), STATIC, withTimeout()

### Community 149 - "Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)"
Cohesion: 0.29
Nodes (6): Calculadora de projeção, Decisões, Gerador de mídias, Riscos, Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram), Suposições

### Community 152 - "Categoria"
Cohesion: 0.10
Nodes (8): CategoriasController, Categoria, Subcategoria, CategorySanitizer, TransactionSuggestionService, Illuminate\Support\Facades\Auth, Laravel\Socialite\Facades\Socialite, TransactionSuggestionTest

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

### Community 173 - "ExercisePersonalRecord"
Cohesion: 0.10
Nodes (3): ExercisePersonalRecord, UserBodyMetric, WorkoutGoal

## Knowledge Gaps
- **179 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+174 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **43 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `CreditCardReconciliationTest`, `PhotoController`, `.app`, `TelegramWebhookController`, `GearItem`, `Cartao`, `Categoria`, `TransacaoUpdateDateTest`, `Illuminate\Console\Command`, `StudySyncTest`, `AllowedGoogleEmail`, `Illuminate\Database\Eloquent\Model`, `Conta`, `NetworkService`, `GurujaMcpService`, `ResumoEstudosCommandTest`, `StudyRoomsTest`, `Exercise`, `TvWidgetEndpointTest`, `BodyTrackerTest`, `TelegramWebhookRoutingTest`, `EstudosApiTest`, `CartaoCompra`, `SalaryProjectionTest`, `Carbon\Carbon`, `Transacao`, `SalaryProjectionTest.php`, `Controller`, `TestCase`, `EstudosGurujaTest`?**
  _High betweenness centrality (0.111) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `CreditCardReconciliationTest`, `User`, `.app`, `Cartao`, `Categoria`, `TransacaoUpdateDateTest`, `StudySyncTest`, `AllowedGoogleEmail`, `Conta`, `NetworkService`, `ResumoEstudosCommandTest`, `StudyRoomsTest`, `Exercise`, `MidiaEstudosCommandTest`, `TvWidgetEndpointTest`, `BodyTrackerTest`, `TelegramWebhookRoutingTest`, `EstudosApiTest`, `CartaoCompra`, `SalaryProjectionTest`, `Carbon\Carbon`, `Transacao`, `SalaryProjectionTest.php`, `EstudosGurujaTest`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `FiscalConcurso` connect `FiscalConcurso` to `Illuminate\Database\Eloquent\Model`, `User`, `FiscalConcursosController`, `FiscalConcursoDataService`, `FiscalNewsCrawlerService`, `.app`, `Carbon\Carbon`, `FiscalNoticia`, `TestCase`?**
  _High betweenness centrality (0.026) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _179 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CreditCardReconciliationTest` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.07632850241545894 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._