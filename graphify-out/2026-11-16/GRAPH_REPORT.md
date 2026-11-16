# Graph Report - financeiro  (2026-11-15)

## Corpus Check
- 311 files · ~652,218 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2138 nodes · 5092 edges · 189 communities (151 shown, 38 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 113 edges (avg confidence: 0.75)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `93662c04`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Database\Seeder
- EstudosApiController
- EventoAuxilioDTO
- PhotoController
- composer.json
- User
- scripts
- OfxImportService
- devDependencies
- FiscalNoticia
- .count
- FiscalConcurso
- OfxAccountDTO
- FiscalConcursoDataService
- FiscalNewsCrawlerService
- esc
- mountStatsCharts
- TelegramWebhookController
- README.md
- types.test.js
- OfxParserService
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- Illuminate\Console\Command
- StudySyncTest
- GoogleAuthAndWhitelistTest
- Illuminate\Database\Migrations\Migration
- tick
- Illuminate\Database\Eloquent\Model
- Transacao
- OfxParserServiceTest
- NetworkService
- CategoriasController
- main.js
- DailyLog
- Illuminate\Http\JsonResponse
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
- TreinosController.php
- .http
- EstudosApiTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- CartoesController
- WorkoutSession
- SalaryCalculatorService
- Cartao
- transaction-autocomplete.js
- SalaryProjectionTest
- Carbon\Carbon
- NotaFiscal
- ProgressPhoto
- audio.js
- PhotoAuth.php
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- OfxTransactionDTO
- toMs
- bootstrap/app.php
- psr-4
- bosses.js
- render
- require
- qrcode.min.js
- extra
- jornada.test.js
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- .app
- TestCase
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- Illuminate\Support\Facades\Schema
- Módulo de estudos estilo YPT (offline)
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
- TransacaoUpdateDateTest
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo
- Illuminate\Database\Schema\Blueprint
- importar_ofx.php
- Illuminate\Database\Eloquent\Relations\BelongsTo

## God Nodes (most connected - your core abstractions)
1. `User` - 184 edges
2. `TestCase` - 70 edges
3. `Cartao` - 66 edges
4. `Transacao` - 64 edges
5. `FiscalConcurso` - 52 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `StudySyncTest` - 37 edges
9. `esc()` - 35 edges
10. `PhotoController` - 34 edges

## Surprising Connections (you probably didn't know these)
- `gurujaPlan()` --indirect_call--> `a()`  [INFERRED]
  resources/js/estudos/main.js → public/js/qrcode.min.js
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php
- `CartaoCompraTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CartaoCompraTest.php → app/Models/User.php
- `CreditCardClosingTest` --references--> `User`  [EXTRACTED]
  tests/Feature/CreditCardClosingTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (189 total, 38 thin omitted)

### Community 0 - "Illuminate\Database\Seeder"
Cohesion: 0.11
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory (+3 more)

### Community 1 - "EstudosApiController"
Cohesion: 0.11
Nodes (7): EstudosApiController, CarbonImmutable, CarbonImmutable, StudySummaryService, Carbon\CarbonImmutable, Illuminate\Database\ConnectionInterface, ResumoEstudosCommandTest

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.06
Nodes (7): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, ManagersTest, PhotoModuleTest, StudyRoomsTest, TelegramWebhookTest

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxImportService"
Cohesion: 0.16
Nodes (7): OfxImportResultDTO, OfxImportService, PDO, PDO, PDOException, OfxImportServiceTest, PDO

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "FiscalNoticia"
Cohesion: 0.13
Nodes (3): FiscalConcursosController, FiscalNoticia, FiscalTelegramConfig

### Community 12 - "OfxAccountDTO"
Cohesion: 0.20
Nodes (4): OfxAccountDTO, OfxStatementDTO, InvalidArgumentException, RuntimeException

### Community 15 - "esc"
Cohesion: 0.11
Nodes (52): activeSubjects(), allTypes(), blockKey(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText() (+44 more)

### Community 16 - "mountStatsCharts"
Cohesion: 0.18
Nodes (13): applyTheme(), colorOf(), cssVar(), hoursOf(), mountDonut(), mountStatsCharts(), nameOf(), simpleBars() (+5 more)

### Community 17 - "TelegramWebhookController"
Cohesion: 0.15
Nodes (3): TelegramWebhookController, WhatsappLog, WhatsappMessageParser

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "types.test.js"
Cohesion: 0.24
Nodes (8): gurujaStudyType(), typeFromForm(), customTypes(), lastTypeFor(), NO_TYPE, typeIdFromName(), NOW, settings

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.08
Nodes (53): folgaPicker(), formatBigHours(), longDay(), projInputs(), projPaces(), renderProjection(), renderProjHowMuch(), renderProjWhen() (+45 more)

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
Cohesion: 0.05
Nodes (17): BackupEstudosCommand, ExportProductionDataCommand, FiscalCrawlCommand, FiscalSeedDataCommand, ImportOfxCommand, MidiaEstudosCommand, PregeneratePhotoCacheCommand, ResumoEstudosCommand (+9 more)

### Community 31 - "tick"
Cohesion: 0.15
Nodes (25): avatarStage(), avatarSvg(), avatarBlock(), checkRestAlert(), currentRoom(), DESK_SVG(), inkOn(), nextStageText() (+17 more)

### Community 32 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.10
Nodes (3): StretchingLog, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model

### Community 35 - "Transacao"
Cohesion: 0.06
Nodes (14): ExportController, Categoria, Conta, Transacao, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService, TransactionSuggestionService (+6 more)

### Community 36 - "OfxParserServiceTest"
Cohesion: 0.20
Nodes (3): PHPUnit\Framework\TestCase, ExampleTest, OfxParserServiceTest

### Community 41 - "main.js"
Cohesion: 0.07
Nodes (47): apiTokensCall(), charts, chartTooltip, closeFocus(), createApiToken(), dayDiff(), dialog, dismissRestAlert() (+39 more)

### Community 43 - "DailyLog"
Cohesion: 0.10
Nodes (5): TreinosController, DailyLog, Measurement, BodyTrackerService, FitnessAiService

### Community 44 - "Illuminate\Http\JsonResponse"
Cohesion: 0.06
Nodes (10): BodyTrackerController, EstudosController, StudyRoomsController, GurujaComandoResumoService, Carbon, StudyRoomService, Carbon, StudySyncService (+2 more)

### Community 45 - "Exercise"
Cohesion: 0.06
Nodes (6): Exercise, ExercisePersonalRecord, WorkoutPlan, WorkoutPlanItem, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 63 - "TreinosController.php"
Cohesion: 0.12
Nodes (3): GearItem, UserBodyMetric, WorkoutGoal

### Community 66 - ".http"
Cohesion: 0.07
Nodes (6): JsonResponse, TelegramService, PendingRequest, EstudosGurujaTest, FitnessAiModuleTest, TelegramWebhookRoutingTest

### Community 75 - "WorkoutSession"
Cohesion: 0.10
Nodes (3): RunningLog, RunningSplit, WorkoutSession

### Community 77 - "Cartao"
Cohesion: 0.06
Nodes (12): Cartao, CartaoCompra, CartaoParcela, CartaoPrevisao, CreditCardService, Carbon, CartaoCompraTest, CreditCardClosingTest (+4 more)

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Carbon\Carbon"
Cohesion: 0.12
Nodes (11): GeminiService, Carbon\Carbon, Illuminate\Http\UploadedFile, Illuminate\Support\Carbon, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, Illuminate\Support\Facades\Storage (+3 more)

### Community 84 - "NotaFiscal"
Cohesion: 0.11
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 88 - "audio.js"
Cohesion: 0.61
Nodes (7): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound()

### Community 89 - "PhotoAuth.php"
Cohesion: 0.60
Nodes (3): PhotoAuth, Closure, Symfony\Component\HttpFoundation\Response

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (3): FinancasController, TransactionDeduplicationService, Illuminate\Http\Request

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 97 - "toMs"
Cohesion: 0.11
Nodes (33): checkReforcoAlert(), recentSessions(), renderSyncBadge(), buildDaily(), dayBounds(), dayStats(), DEFAULT_SETTINGS, detectGurujaReforcoGrau() (+25 more)

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
Cohesion: 0.11
Nodes (49): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+41 more)

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
Cohesion: 0.32
Nodes (8): BOSS_HP, bossMaxHp(), cycleMark(), JORNADA_PADRAO, jornadaState(), normalizeJornada(), objectiveProgressMs(), tickJornada()

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.06
Nodes (14): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\DB, Illuminate\Support\Str, Illuminate\Validation\ValidationException, Mockery\MockInterface, Pdo\Mysql, BackupEstudosCommandTest (+6 more)

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

### Community 146 - "WorkoutSessionExercise"
Cohesion: 0.13
Nodes (3): WorkoutSessionExercise, WorkoutSet, static

### Community 148 - "estudos-sw.js"
Cohesion: 0.40
Nodes (3): shellResponse(), STATIC, withTimeout()

### Community 149 - "Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)"
Cohesion: 0.29
Nodes (6): Calculadora de projeção, Decisões, Gerador de mídias, Riscos, Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram), Suposições

### Community 152 - "Controller"
Cohesion: 0.11
Nodes (9): AllowedEmailController, TvWidgetController, AuthController, Controller, HomeController, AllowedGoogleEmail, Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Route (+1 more)

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

### Community 183 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.13
Nodes (5): ImportarHoleritesCommand, CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, Illuminate\Database\Eloquent\Relations\BelongsTo

## Knowledge Gaps
- **175 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+170 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **38 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Seeder`, `EstudosApiController`, `TelegramWebhookController`, `Controller`, `TransacaoUpdateDateTest`, `StudySyncTest`, `GoogleAuthAndWhitelistTest`, `Illuminate\Database\Eloquent\Model`, `Transacao`, `NetworkService`, `Exercise`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `BodyTrackerTest`, `.http`, `EstudosApiTest`, `Cartao`, `SalaryProjectionTest`, `Carbon\Carbon`, `NotaFiscal`, `SalaryProjectionTest.php`, `.app`, `TestCase`?**
  _High betweenness centrality (0.089) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `EstudosApiController`, `.http`, `Transacao`, `EstudosApiTest`, `NetworkService`, `User`, `Cartao`, `MidiaEstudosCommandTest`, `SalaryProjectionTest`, `Exercise`, `Carbon\Carbon`, `NotaFiscal`, `.app`, `TransacaoUpdateDateTest`, `BodyTrackerTest`, `StudySyncTest`, `GoogleAuthAndWhitelistTest`, `SalaryProjectionTest.php`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **Why does `Cartao` connect `Cartao` to `Illuminate\Database\Eloquent\Model`, `Transacao`, `CartoesController`, `.count`, `TelegramWebhookController`, `Carbon\Carbon`, `NotaFiscal`, `Illuminate\Http\Request`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _175 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Seeder` be split into smaller, more focused modules?**
  _Cohesion score 0.1067193675889328 - nodes in this community are weakly interconnected._
- **Should `EstudosApiController` be split into smaller, more focused modules?**
  _Cohesion score 0.10545790934320073 - nodes in this community are weakly interconnected._
- **Should `PhotoController` be split into smaller, more focused modules?**
  _Cohesion score 0.13333333333333333 - nodes in this community are weakly interconnected._