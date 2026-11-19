# Graph Report - financeiro  (2026-11-19)

## Corpus Check
- 301 files · ~623,680 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2129 nodes · 5121 edges · 184 communities (143 shown, 41 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 104 edges (avg confidence: 0.75)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `261532c8`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- CartoesController
- Illuminate\Http\JsonResponse
- EventoAuxilioDTO
- CreditCardReconciliationService
- composer.json
- User
- scripts
- OfxParserService
- devDependencies
- nowMs
- addDays
- StudyRoomService
- DatabaseSyncService
- CategoriasController
- StudyRoomsController
- esc
- Illuminate\Database\Eloquent\Relations\BelongsTo
- TelegramWebhookController
- README.md
- Illuminate\Database\Seeder
- WorkoutSession
- Illuminate\Database\Migrations\Migration
- 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos
- metrics.js
- sidebar.js
- deploy
- logging.php
- .count
- StudySyncTest
- AllowedGoogleEmail
- tick
- Illuminate\Database\Eloquent\Model
- MidiaEstudosCommand
- GurujaPostMetaService
- NetworkService
- StudySummaryService
- main.js
- GurujaMcpService
- DailyLog
- Exercise
- setup
- console.php
- rules/graphify.md
- workflows/graphify.md
- entrypoint.sh
- config
- require-dev
- renderGuruja
- MidiaEstudosCommandTest
- .http
- EstudosApiTest
- components.conciliacao-fatura-modal
- financas/index.blade.php
- home.blade.php
- post-meta.js
- GearItem
- SalaryCalculatorService
- Cartao
- transaction-autocomplete.js
- SalaryProjectionTest
- Illuminate\Foundation\Testing\RefreshDatabase
- NotaFiscal
- Transacao
- Instrução: post da meta da semana (carrossel) sem IA
- Illuminate\Http\Request
- StatementImportService
- SalaryProjectionTest.php
- Controller
- StudySyncService
- bootstrap/app.php
- psr-4
- bosses.js
- db.js
- require
- qrcode.min.js
- extra
- BodyTrackerTest
- audio.js
- Sabatina 2026-11-01 — "App desktop para o módulo de estudos?"
- CreditCardReconciliationTest
- GurujaConnectCommand
- WorkoutSessionExercise
- BackupEstudosCommandTest
- TransacaoUpdateDateTest
- TestCase
- rooms.js
- avatars-vetor.js
- Sabatina 2026-11-01: widget flutuante do cronômetro de estudos
- Illuminate\Support\Facades\Schema
- Módulo de estudos estilo YPT (offline)
- ServidorDTO
- post-create-project-cmd
- SalaryController
- planImport
- AppServiceProvider
- estudos-sw.js
- Sabatina 2026-11-08 — Calculadora de projeção e gerador de mídias (Instagram)
- Illuminate\Database\Schema\Blueprint
- CartaoCompra
- QualificacaoPermanenteDTO
- /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]
- QualificacaoTemporariaDTO
- Convite para teste — Horas de Estudo
- TreinosController
- Sincronização da Guruja no App de Estudos
- guruja.md

## God Nodes (most connected - your core abstractions)
1. `User` - 183 edges
2. `TestCase` - 70 edges
3. `Cartao` - 66 edges
4. `Transacao` - 64 edges
5. `GurujaMcpService` - 48 edges
6. `CartaoCompra` - 46 edges
7. `CartaoParcela` - 42 edges
8. `esc()` - 38 edges
9. `StudySyncTest` - 37 edges
10. `formatDuration()` - 34 edges

## Surprising Connections (you probably didn't know these)
- `gurujaPlan()` --indirect_call--> `a()`  [INFERRED]
  resources/js/estudos/main.js → public/js/qrcode.min.js
- `trendBuckets()` --indirect_call--> `d()`  [INFERRED]
  resources/js/estudos/metrics.js → public/js/qrcode.min.js
- `CreditCardDeleteTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardDeleteTest.php → app/Models/Cartao.php
- `CreditCardReconciliationTest` --references--> `Cartao`  [EXTRACTED]
  tests/Feature/CreditCardReconciliationTest.php → app/Models/Cartao.php
- `BodyTrackerTest` --references--> `User`  [EXTRACTED]
  tests/Feature/BodyTrackerTest.php → app/Models/User.php

## Import Cycles
- None detected.

## Communities (184 total, 41 thin omitted)

### Community 1 - "Illuminate\Http\JsonResponse"
Cohesion: 0.11
Nodes (4): BodyTrackerController, EstudosController, Illuminate\Http\JsonResponse, Symfony\Component\HttpFoundation\BinaryFileResponse

### Community 2 - "EventoAuxilioDTO"
Cohesion: 0.18
Nodes (4): EventoAuxilioDTO, self, FilhoDTO, self

### Community 4 - "composer.json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 5 - "User"
Cohesion: 0.06
Nodes (10): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Mockery, CreditCardDeleteTest, EstudosGurujaMcpTest, GoogleAuthAndWhitelistTest, ManagersTest (+2 more)

### Community 6 - "scripts"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 7 - "OfxParserService"
Cohesion: 0.05
Nodes (18): OfxAccountDTO, OfxImportResultDTO, OfxStatementDTO, OfxTransactionDTO, OfxImportService, PDO, OfxParserService, getPdoConnection() (+10 more)

### Community 8 - "devDependencies"
Cohesion: 0.08
Nodes (25): chart.js, concurrently, idb, laravel-vite-plugin, dependencies, chart.js, idb, devDependencies (+17 more)

### Community 9 - "nowMs"
Cohesion: 0.17
Nodes (26): uuid(), allTypes(), checkSuspicious(), colorPicker(), exportBackup(), gurujaStudy(), gurujaStudyType(), importBackup() (+18 more)

### Community 10 - "addDays"
Cohesion: 0.11
Nodes (34): BOSS_HP, bossMaxHp(), cycleMark(), JORNADA_PADRAO, jornadaState(), normalizeJornada(), objectiveProgressMs(), folgaPicker() (+26 more)

### Community 15 - "esc"
Cohesion: 0.11
Nodes (51): activeSubjects(), blockKey(), bossCard(), bossLabel(), cards(), chargeBar(), chargeText(), colorVars() (+43 more)

### Community 16 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.13
Nodes (5): ImportarHoleritesCommand, CartaoFaturaFechamento, SalaryPayslip, SalaryProfile, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 17 - "TelegramWebhookController"
Cohesion: 0.08
Nodes (5): TelegramWebhookController, WhatsappLog, GeminiService, WhatsappMessageParser, WhatsappMessageParserTest

### Community 18 - "README.md"
Cohesion: 0.25
Nodes (7): About Laravel, Agentic Development, Code of Conduct, Contributing, Learning Laravel, License, Security Vulnerabilities

### Community 19 - "Illuminate\Database\Seeder"
Cohesion: 0.11
Nodes (11): UserFactory, DatabaseSeeder, ProductionDataSeeder, UserSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory (+3 more)

### Community 22 - "🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos"
Cohesion: 0.15
Nodes (12): 🔄 Como atualizar os dados antes da viagem se fizer novos lançamentos locais, 🤖 Como funciona o Bot do Telegram com Roteamento Inteligente (Local-First + Nuvem Fallback), 📱 Dica de Acesso pelo Celular, 🚀 Guia de Deploy em Nuvem - Sistema Financeiro & Concursos, 📦 O que foi preparado no projeto, 🌟 Opção 1: Deploy no Render.com com Banco Vitalício no Neon.tech (Recomendado), 🚂 Opção 2: Deploy no Railway.app, ⚡ Opção 3: Usando Banco Gratuito no Neon.tech ou Supabase (+4 more)

### Community 23 - "metrics.js"
Cohesion: 0.07
Nodes (53): recentSessions(), renderSyncBadge(), amountBetween(), buildDaily(), compareWithPrevious(), comparisonRanges(), comparisonSeries(), CUSTOM_TYPE_MAX (+45 more)

### Community 24 - "sidebar.js"
Cohesion: 0.27
Nodes (9): closeSyncModalGlobal(), executeSyncAction(), fetchSyncStatusGlobal(), getCsrfToken(), getCurrentScrollPosition(), getScrollContainer(), initCloudSync(), openSyncModalGlobal() (+1 more)

### Community 25 - "deploy"
Cohesion: 0.20
Nodes (9): build, builder, dockerfilePath, deploy, healthcheckPath, healthcheckTimeout, restartPolicyMaxRetries, restartPolicyType (+1 more)

### Community 26 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 27 - ".count"
Cohesion: 0.12
Nodes (8): BackupEstudosCommand, ExportProductionDataCommand, GurujaSyncCommand, ImportOfxCommand, ResumoEstudosCommand, SyncDatabaseCommand, TelegramRegisterLocalCommand, Illuminate\Console\Command

### Community 29 - "AllowedGoogleEmail"
Cohesion: 0.18
Nodes (5): AllowedEmailController, AuthController, AllowedGoogleEmail, Illuminate\Http\RedirectResponse, Laravel\Socialite\Facades\Socialite

### Community 31 - "tick"
Cohesion: 0.10
Nodes (39): avatarStage(), avatarSvg(), avatarBlock(), checkReforcoAlert(), checkRestAlert(), currentRoom(), DESK_SVG(), dismissRestAlert() (+31 more)

### Community 36 - "GurujaPostMetaService"
Cohesion: 0.11
Nodes (5): EstudosApiController, CarbonImmutable, GurujaPostMetaService, CarbonImmutable, Carbon\CarbonImmutable

### Community 40 - "StudySummaryService"
Cohesion: 0.17
Nodes (4): CarbonImmutable, StudySummaryService, Illuminate\Database\ConnectionInterface, ResumoEstudosCommandTest

### Community 41 - "main.js"
Cohesion: 0.06
Nodes (67): setMeta(), apiTokensCall(), applyTheme(), boot(), canvasToPngBlob(), charts, chartTooltip, checkGurujaAuthRedirect() (+59 more)

### Community 43 - "DailyLog"
Cohesion: 0.07
Nodes (6): DailyLog, Measurement, ProgressPhoto, BodyTrackerService, FitnessAiService, FitnessAiModuleTest

### Community 45 - "Exercise"
Cohesion: 0.09
Nodes (5): Exercise, WorkoutPlanItem, WorkoutSet, ExerciseCatalogSeeder, WorkoutModuleTest

### Community 47 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 53 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "require-dev"
Cohesion: 0.29
Nodes (7): require-dev, fakerphp/faker, laravel/pail, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 59 - "renderGuruja"
Cohesion: 0.32
Nodes (12): gjDone(), gjDoneGuruja(), gjKey(), gjMarcada(), gurujaAgo(), gurujaDayDate(), gurujaDayLabel(), gurujaItem() (+4 more)

### Community 66 - ".http"
Cohesion: 0.06
Nodes (7): TelegramSetWebhook, JsonResponse, TelegramService, PendingRequest, EstudosGurujaPostMetaTest, EstudosGurujaTest, TelegramWebhookRoutingTest

### Community 74 - "post-meta.js"
Cohesion: 0.12
Nodes (44): buildPostCaption(), buildSlidePlan(), CAPTION_HASHTAGS, createSlideCanvas(), drawFittedNumber(), drawFooter(), drawSlideClosing(), drawSlideCover() (+36 more)

### Community 75 - "GearItem"
Cohesion: 0.08
Nodes (4): GearItem, RunningLog, RunningSplit, static

### Community 77 - "Cartao"
Cohesion: 0.10
Nodes (6): Cartao, CreditCardService, Carbon, CartaoCompraTest, CreditCardClosingTest, FinancialSummaryAndCardLimitsTest

### Community 78 - "transaction-autocomplete.js"
Cohesion: 0.80
Nodes (4): escapeHtml(), highlightMatch(), initTransactionAutocomplete(), normalizeStr()

### Community 83 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.10
Nodes (17): GurujaComandoResumoService, Carbon\Carbon, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Http\Client\Request, Illuminate\Http\UploadedFile, Illuminate\Support\Carbon, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\DB (+9 more)

### Community 84 - "NotaFiscal"
Cohesion: 0.12
Nodes (4): MercadoController, NotaFiscal, NotaFiscalItem, MercadoModuleTest

### Community 88 - "Transacao"
Cohesion: 0.09
Nodes (7): Conta, Transacao, Mockery\MockInterface, StatementImportTest, TelegramOfxDocumentTest, TransactionDeduplicationTest, TransactionSuggestionTest

### Community 89 - "Instrução: post da meta da semana (carrossel) sem IA"
Cohesion: 0.18
Nodes (10): 1. Onde fica e como o usuário usa, 2.1 Guruja (via `GurujaMcpService`, que já faz OAuth e `callMcp`), 2.2 Horas do app (banco local, mesma lógica de `/api/estudos/periodo`), 2.3 Cálculos (em PHP, no endpoint), 2. Dados (servidor), 3. Slides (cliente, `resources/js/estudos/post-meta.js`), 4. Legenda (template fixo, sem IA), 5. Testes (+2 more)

### Community 91 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (3): FinancasController, TransactionDeduplicationService, Illuminate\Http\Request

### Community 95 - "SalaryProjectionTest.php"
Cohesion: 0.13
Nodes (6): CassemsEventoDTO, self, ConsignadoDTO, self, DespesaForaFolhaDTO, self

### Community 96 - "Controller"
Cohesion: 0.14
Nodes (5): TvWidgetController, Controller, DatabaseSyncController, HomeController, Illuminate\Support\Facades\Route

### Community 98 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 99 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 102 - "bosses.js"
Cohesion: 0.23
Nodes (15): BOSSES, bossSvg(), DEFAULT_COLORS, ensureModelViewer(), escSvg(), ground(), koStars(), linear() (+7 more)

### Community 104 - "db.js"
Cohesion: 0.30
Nodes (13): applyServerChanges(), db(), dropFromQueue(), ensureUser(), getMeta(), loadAll(), pendingQueue(), saveLocal() (+5 more)

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, barryvdh/laravel-dompdf, laravel/framework, laravel/socialite, laravel/tinker, php

### Community 106 - "qrcode.min.js"
Cohesion: 0.23
Nodes (6): a(), b(), d(), g(), r(), s()

### Community 107 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 114 - "audio.js"
Cohesion: 0.61
Nodes (7): getAudioContext(), playBossHitSound(), playFocusEndSound(), playLevelUpSound(), playNote(), playRestDueSound(), playRestSoonSound()

### Community 116 - "Sabatina 2026-11-01 — "App desktop para o módulo de estudos?""
Cohesion: 0.22
Nodes (8): Conclusão, Decisões tomadas, Pergunta original, Próximos passos, Resultados medidos, Riscos e pontos em aberto, Sabatina 2026-11-01 — "App desktop para o módulo de estudos?", Suposições

### Community 122 - "TestCase"
Cohesion: 0.08
Nodes (7): Illuminate\Foundation\Testing\TestCase, CelularAccessTest, DatabaseSyncTest, ExampleTest, ImportOfxCommandTest, TvWidgetEndpointTest, TestCase

### Community 123 - "rooms.js"
Cohesion: 0.60
Nodes (3): createRoomsApi(), memberSessionMs(), memberTodayMs()

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

### Community 152 - "CartaoCompra"
Cohesion: 0.10
Nodes (12): ExportController, CartaoCompra, CartaoParcela, CartaoPrevisao, Categoria, TransacaoPrevisao, CategorySanitizer, FinancialSummaryService (+4 more)

### Community 159 - "/post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD]"
Cohesion: 0.33
Nodes (5): Diário (Story 1080×1920), /post-estudos semanal | semanal --parcial | diario [YYYY-MM-DD], Problemas comuns, Regras fixas, Semanal (carrossel 1080×1350, 3 slides, + Story de divulgação)

### Community 164 - "Convite para teste — Horas de Estudo"
Cohesion: 0.50
Nodes (3): Convite para teste — Horas de Estudo, 🇺🇸 English, 🇧🇷 Português

### Community 173 - "TreinosController"
Cohesion: 0.08
Nodes (5): TreinosController, ExercisePersonalRecord, UserBodyMetric, WorkoutGoal, WorkoutPlan

## Knowledge Gaps
- **189 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+184 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **41 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Relations\BelongsTo`, `TelegramWebhookController`, `Illuminate\Database\Seeder`, `CartaoCompra`, `.count`, `StudySyncTest`, `AllowedGoogleEmail`, `Illuminate\Database\Eloquent\Model`, `StudySummaryService`, `DailyLog`, `Exercise`, `.http`, `EstudosApiTest`, `Cartao`, `SalaryProjectionTest`, `Illuminate\Foundation\Testing\RefreshDatabase`, `NotaFiscal`, `Transacao`, `SalaryProjectionTest.php`, `Controller`, `BodyTrackerTest`, `CreditCardReconciliationTest`, `GurujaConnectCommand`, `BackupEstudosCommandTest`, `TransacaoUpdateDateTest`, `TestCase`?**
  _High betweenness centrality (0.117) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `User`, `TelegramWebhookController`, `CartaoCompra`, `StudySyncTest`, `StudySummaryService`, `DailyLog`, `Exercise`, `MidiaEstudosCommandTest`, `.http`, `EstudosApiTest`, `Cartao`, `SalaryProjectionTest`, `Illuminate\Foundation\Testing\RefreshDatabase`, `NotaFiscal`, `Transacao`, `SalaryProjectionTest.php`, `BodyTrackerTest`, `CreditCardReconciliationTest`, `BackupEstudosCommandTest`, `TransacaoUpdateDateTest`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **Why does `GurujaMcpService` connect `GurujaMcpService` to `Illuminate\Http\JsonResponse`, `GurujaPostMetaService`, `Illuminate\Foundation\Testing\RefreshDatabase`, `GurujaConnectCommand`, `.count`?**
  _High betweenness centrality (0.027) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _189 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CartoesController` be split into smaller, more focused modules?**
  _Cohesion score 0.14666666666666667 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Http\JsonResponse` be split into smaller, more focused modules?**
  _Cohesion score 0.11397849462365592 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._