# Graph Report - financeiro  (2026-11-14)

## Corpus Check
- 311 files · ~650,045 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2124 nodes · 5035 edges · 198 communities (151 shown, 47 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.75)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `580bf537`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- WorkoutModuleTest.php
- EstudosApiController
- EventoAuxilioDTO
- PhotoController
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- CartaoParcela
- .count
- FiscalConcurso
- FiscalNoticia
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- esc
- Cartao
- TelegramWebhookController
- README.md
- toggleSubject
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- StudySyncTest
- AllowedGoogleEmail
- Illuminate\Database\Migrations\Migration
- nowMs
- Illuminate\Database\Eloquent\Model
- Illuminate\Support\Facades\Schema
- Transacao
- StudySummaryService
- NetworkService
- Categoria
- main.js
- DailyLog
- Illuminate\Http\JsonResponse
- Exercise
- projection.js
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- BodyTrackerTest
- GearItem
- .http
- EstudosApiTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- CartoesController
- WorkoutSession
- SalaryCalculatorService
- CartaoCompra
- transaction-autocomplete.js
- SalaryProjectionTest
- Illuminate\Support\Facades\DB
- NotaFiscal
- TreinosController.php
- EstudosGurujaTest
- StudySyncService
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- StudyRoomService
- toMs
- bootstrap/app.php
- psr-4
- bosses.js
- render
- require
- qrcode.min.js
- extra
- jornada.test.js
- TvWidgetEndpointTest
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- MidiaEstudosCommandTest
- TestCase
- ProgressPhoto
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- WorkoutPlan
- GoogleAuthAndWhitelistTest
- Módulo de estudos estilo YPT (offline)
- DatabaseSyncService
- ServidorDTO
- post-create-project-cmd
- SalaryController
- FiscalNewsAiService
- planImport
- WorkoutSessionExercise
- AppServiceProvider
- estudos-sw.js
- Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)
- Controller
- ResumoEstudosCommandTest
- TransacaoUpdateDateTest
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- MidiaEstudosCommand
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo
- Illuminate\Database\Schema\Blueprint
- StudyRoomsTest
- StudyRoomsController
- PhotoAuth.php
- ImportarHoleritesCommand.php
- ScanPhotoModelsCommand.php
- TelegramSetWebhook.php

## God Nodes (most connected - your core abstractions)
1. `User` - 184 edges
2. `TestCase` - 70 edges
3. `Cartao` - 66 edges
4. `Transacao` - 64 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `StudySyncTest` - 35 edges
9. `PhotoController` - 34 edges
10. `esc()` - 34 edges

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

## Communities (198 total, 47 thin omitted)

### Community 0 - "WorkoutModuleTest.php"
Cohesion: 0.12
Nodes (9): UserFactory, DatabaseSeeder, ExerciseCatalogSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Database\Seeder (+1 more)

### Community 1 - "EstudosApiController"
Cohesion: 0.27
Nodes (3): EstudosApiController, CarbonImmutable, Carbon\CarbonImmutable

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.06
Nodes (9): User, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, CreditCardDeleteTest, MercadoModuleTest (+1 more)

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (18): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, getPdoConnection() (+10 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "CartaoParcela"
Cohesion: 0.11
Nodes (3): CartaoFatura, CartaoParcela, CreditCardReconciliationTest

### Community 12 - "FiscalNoticia"
Cohesion: 0.11
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 13 - "FiscalConcursoDataService"
Cohesion: 0.15
Nodes (4): FiscalCrawlCommand, FiscalConcursoDataService, FiscalTelegramNotifierService, Command

### Community 15 - "esc"
Cohesion: 0.11
Nodes (44): cycleMark(), blockKey(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText(), colorVars() (+36 more)

### Community 16 - "Cartao"
Cohesion: 0.12
Nodes (8): Cartao, CreditCardService, Carbon, WhatsappMessageParser, Barryvdh\DomPDF\Facade\Pdf, Carbon\Carbon, Illuminate\Support\Facades\URL, CreditCardClosingTest

### Community 17 - "TelegramWebhookController"
Cohesion: 0.16
Nodes (3): JsonResponse, TelegramWebhookController, WhatsappLog

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "toggleSubject"
Cohesion: 0.18
Nodes (20): activeSubjects(), allTypes(), colorPicker(), gjNorm(), gurujaStudy(), gurujaStudyType(), gurujaSubjectFor(), iso() (+12 more)

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.08
Nodes (49): mountStatsCharts(), renderHeatmap(), renderTrend(), shiftStats(), statsRange(), trendSessions(), addDays(), allTimeStats() (+41 more)

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
Cohesion: 0.11
Nodes (9): BackupEstudosCommand, ExportProductionDataCommand, FiscalSeedDataCommand, ImportOfxCommand, PregeneratePhotoCacheCommand, ResumoEstudosCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand (+1 more)

### Community 29 - "AllowedGoogleEmail"
Cohesion: 0.13
Nodes (5): AllowedEmailController, AuthController, AllowedGoogleEmail, Illuminate\Http\RedirectResponse, ManagersTest

### Community 31 - "nowMs"
Cohesion: 0.15
Nodes (26): avatarStage(), avatarSvg(), avatarBlock(), checkRestAlert(), currentRoom(), DESK_SVG(), nextStageText(), nowMs() (+18 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.11
Nodes (7): CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 35 - "Transacao"
Cohesion: 0.12
Nodes (3): Transacao, TransactionDeduplicationService, TransactionDeduplicationTest

### Community 36 - "StudySummaryService"
Cohesion: 0.32
Nodes (3): CarbonImmutable, StudySummaryService, Illuminate\Database\ConnectionInterface

### Community 40 - "Categoria"
Cohesion: 0.10
Nodes (6): CategoriasController, Categoria, Subcategoria, CategorySanitizer, TransactionSuggestionService, TransactionSuggestionTest

### Community 41 - "main.js"
Cohesion: 0.06
Nodes (52): apiTokensCall(), applyTheme(), charts, chartTooltip, closeFocus(), colorOf(), createApiToken(), cssVar() (+44 more)

### Community 43 - "DailyLog"
Cohesion: 0.09
Nodes (5): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.12
Nodes (5): BodyTrackerController, EstudosController, GurujaComandoResumoService, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 45 - "Exercise"
Cohesion: 0.08
Nodes (3): Exercise, ExercisePersonalRecord, WorkoutModuleTest

### Community 46 - "projection.js"
Cohesion: 0.19
Nodes (19): folgaPicker(), formatBigHours(), longDay(), projInputs(), projPaces(), renderProjection(), renderProjHowMuch(), renderProjWhen() (+11 more)

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 66 - ".http"
Cohesion: 0.15
Nodes (3): TelegramService, PendingRequest, TelegramWebhookRoutingTest

### Community 75 - "WorkoutSession"
Cohesion: 0.09
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "CartaoCompra"
Cohesion: 0.12
Nodes (4): CartaoCompra, CartaoPrevisao, CartaoCompraTest, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Illuminate\Support\Facades\DB"
Cohesion: 0.10
Nodes (13): Illuminate\Http\UploadedFile, Illuminate\Support\Carbon, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, Illuminate\Support\Facades\Storage, Illuminate\Support\Facades\Validator (+5 more)

### Community 84 - "NotaFiscal"
Cohesion: 0.10
Nodes (5): MercadoController, NotaFiscal, NotaFiscalItem, GeminiService, Illuminate\Support\Facades\Auth

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.13
Nodes (5): ExportController, FinancasController, TransacaoPrevisao, FinancialSummaryService, Illuminate\Http\Request

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 97 - "toMs"
Cohesion: 0.13
Nodes (29): liveSessions(), openSessionDialog(), openSuspiciousDialog(), recentSessions(), renderTimeline(), subjectById(), dayBounds(), dayStats() (+21 more)

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
Cohesion: 0.13
Nodes (33): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+25 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 108 - "jornada.test.js"
Cohesion: 0.22
Nodes (14): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound(), BOSS_HP (+6 more)

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.05
Nodes (12): Conta, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, BackupEstudosCommandTest, DatabaseSyncTest, ExampleTest, FitnessAiModuleTest, ImportOfxCommandTest (+4 more)

### Community 124 - "avatars-vetor.js"
Cohesion: 0.08
Nodes (27): AVATAR_LIMITES_MIN, AVATAR_PADRAO, AVATARS, BICHINHO, eggCrack, happyFace(), AVATARS_IMAGEM, FAMILIA (+19 more)

### Community 125 - "Sabatina 2026-11-01: widget flutuante do cronômetro de estudos"
Cohesion: 0.25
Nodes (7): Decisões tomadas, Implementação, Pergunta original, Próximos passos (se quiser evoluir), Riscos e pontos em aberto, Sabatina 2026-11-01: widget flutuante do cronômetro de estudos, Suposições

### Community 133 - "GoogleAuthAndWhitelistTest"
Cohesion: 0.12
Nodes (5): Laravel\Socialite\Facades\Socialite, Mockery, Mockery\MockInterface, GoogleAuthAndWhitelistTest, TelegramWebhookTest

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

### Community 152 - "Controller"
Cohesion: 0.22
Nodes (4): TvWidgetController, Controller, HomeController, Illuminate\Support\Facades\Route

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

### Community 182 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

## Knowledge Gaps
- **172 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+167 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **47 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `WorkoutModuleTest.php`, `GoogleAuthAndWhitelistTest`, `CartaoParcela`, `FiscalConcurso`, `FiscalConcursoDataService`, `Cartao`, `TelegramWebhookController`, `ResumoEstudosCommandTest`, `TransacaoUpdateDateTest`, `StudySyncTest`, `AllowedGoogleEmail`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `NetworkService`, `Categoria`, `StudyRoomsTest`, `Exercise`, `ImportarHoleritesCommand.php`, `BodyTrackerTest`, `.http`, `EstudosApiTest`, `CartaoCompra`, `SalaryProjectionTest`, `Illuminate\Support\Facades\DB`, `EstudosGurujaTest`, `Illuminate\Http\Request`, `SalaryProjectionTest.php`, `TvWidgetEndpointTest`, `TestCase`?**
  _High betweenness centrality (0.110) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `WorkoutModuleTest.php`, `User`, `GoogleAuthAndWhitelistTest`, `CartaoParcela`, `FiscalConcurso`, `FiscalConcursoDataService`, `Cartao`, `ResumoEstudosCommandTest`, `TransacaoUpdateDateTest`, `StudySyncTest`, `AllowedGoogleEmail`, `Transacao`, `NetworkService`, `Categoria`, `StudyRoomsTest`, `Exercise`, `BodyTrackerTest`, `.http`, `EstudosApiTest`, `CartaoCompra`, `SalaryProjectionTest`, `Illuminate\Support\Facades\DB`, `EstudosGurujaTest`, `Illuminate\Http\Request`, `SalaryProjectionTest.php`, `TvWidgetEndpointTest`, `MidiaEstudosCommandTest`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Cartao` to `Illuminate\Database\Eloquent\Model`, `User`, `Categoria`, `CartaoParcela`, `.count`, `CartoesController`, `CartaoCompra`, `TelegramWebhookController`, `Illuminate\Support\Facades\DB`, `NotaFiscal`, `TestCase`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _172 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `WorkoutModuleTest.php` be split into smaller, more focused modules?**
  _Cohesion score 0.11688311688311688 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.1354679802955665 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._