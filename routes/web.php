<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EstudosApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FinancasController;
use App\Http\Controllers\CartoesController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\EstudosController;
use App\Http\Controllers\StudyRoomsController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\FiscalConcursosController;
use App\Http\Controllers\MercadoController;
use App\Http\Controllers\TreinosController;
use App\Http\Controllers\DatabaseSyncController;
use App\Http\Controllers\Api\TvWidgetController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// ── Telegram Webhook (rotas públicas) ───────────────────────────────────────────
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'receive'])
    ->name('webhook.telegram');
Route::get('/webhook/telegram/ping', [TelegramWebhookController::class, 'ping'])
    ->name('webhook.telegram.ping');
Route::post('/webhook/telegram/register-local', [TelegramWebhookController::class, 'registerLocalUrl'])
    ->name('webhook.telegram.register-local');


// ── API de leitura do módulo de estudos (token pessoal, sem sessão) ─────────
Route::get('/api/estudos/dia', [EstudosApiController::class, 'dia'])
    ->middleware('throttle:60,1')
    ->name('api.estudos.dia');
Route::get('/api/estudos/periodo', [EstudosApiController::class, 'periodo'])
    ->middleware('throttle:60,1')
    ->name('api.estudos.periodo');
Route::post('/api/estudos/guruja/meta', [EstudosApiController::class, 'gurujaStore'])
    ->middleware('throttle:30,1')
    ->name('api.estudos.guruja');

// ── TV Launcher Widget & Controle Remoto (Rede Local) ─────────────────
Route::get('/api/tv/widget-resumo', [TvWidgetController::class, 'resumo'])->name('api.tv.widget-resumo');
Route::get('/tv', [TvWidgetController::class, 'controlUi'])->name('tv.control');
Route::post('/api/tv/action', [TvWidgetController::class, 'action'])->name('api.tv.action');

Route::get('/financas/relatorio/pdf', [ExportController::class, 'exportResumoFinanceiroPdf'])->name('financas.relatorio.pdf');
Route::get('/celular', function () { return view('celular'); })->name('celular');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Gestão de Convites / Whitelist Google (Admin)
    Route::get('/admin/convites', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'index'])->name('admin.convites');
    Route::post('/admin/convites', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'store'])->name('admin.convites.store');
    Route::delete('/admin/convites/{id}', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'destroy'])->name('admin.convites.destroy');
    Route::post('/admin/usuarios/{user}/gestor', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'toggleManager'])->name('admin.usuarios.gestor');

    // Sincronização Nuvem Neon
    Route::get('/sync/status', [DatabaseSyncController::class, 'status'])->name('sync.status');
    Route::post('/sync/run', [DatabaseSyncController::class, 'sync'])->name('sync.run');
    
    // Finanças de Casa & Supermercado
    Route::get('/financas', [FinancasController::class, 'index'])->name('financas.index');
    Route::get('/financas/mercado', [MercadoController::class, 'index'])->name('financas.mercado.index');
    Route::post('/financas/mercado/upload', [MercadoController::class, 'upload'])->name('financas.mercado.upload');
    Route::delete('/financas/mercado/notas/{notaFiscal}', [MercadoController::class, 'destroy'])->name('financas.mercado.notas.destroy');
    Route::delete('/financas/mercado/itens/{item}', [MercadoController::class, 'destroyItem'])->name('financas.mercado.itens.destroy');
    Route::get('/financas/sugestoes-descricao', [FinancasController::class, 'sugestoesDescricao'])->name('financas.sugestoes-descricao');
    Route::post('/financas/importar-extrato', [FinancasController::class, 'importarExtrato'])->name('financas.importar-extrato');
    Route::get('/financas/duplicidades', [FinancasController::class, 'listarDuplicidades'])->name('financas.duplicidades.index');
    Route::post('/financas/duplicidades/mesclar', [FinancasController::class, 'mesclarDuplicidade'])->name('financas.duplicidades.mesclar');
    Route::post('/financas/duplicidades/mesclar-todas', [FinancasController::class, 'mesclarTodasDuplicidades'])->name('financas.duplicidades.mesclar-todas');
    Route::post('/financas/duplicidades/ignorar', [FinancasController::class, 'ignorarDuplicidade'])->name('financas.duplicidades.ignorar');
    Route::post('/financas', [FinancasController::class, 'store'])->name('financas.store');
    Route::post('/financas/previsoes', [FinancasController::class, 'storePrevisao'])->name('financas.previsoes.store');
    Route::put('/financas/previsoes/{previsao}', [FinancasController::class, 'updatePrevisao'])->name('financas.previsoes.update');
    Route::delete('/financas/previsoes/{previsao}', [FinancasController::class, 'destroyPrevisao'])->name('financas.previsoes.destroy');
    Route::put('/financas/{transacao}', [FinancasController::class, 'update'])->name('financas.update');
    Route::delete('/financas/{transacao}', [FinancasController::class, 'destroy'])->name('financas.destroy');

    // Categorias & Subcategorias
    Route::get('/financas/categorias', [CategoriasController::class, 'index'])->name('categorias.index');
    Route::post('/financas/categorias', [CategoriasController::class, 'store'])->name('categorias.store');
    Route::post('/financas/subcategorias', [CategoriasController::class, 'storeSub'])->name('subcategorias.store');
    Route::delete('/financas/categorias/{categoria}', [CategoriasController::class, 'destroy'])->name('categorias.destroy');
    Route::delete('/financas/subcategorias/{subcategoria}', [CategoriasController::class, 'destroySub'])->name('subcategorias.destroy');

    // Cartões
    Route::get('/financas/cartoes', [CartoesController::class, 'index'])->name('cartoes.index');
    Route::post('/financas/cartoes', [CartoesController::class, 'store'])->name('cartoes.store');
    Route::put('/financas/cartoes/{cartao}', [CartoesController::class, 'update'])->name('cartoes.update');
    Route::post('/financas/cartoes/compras', [CartoesController::class, 'storeCompra'])->name('cartoes.compras.store');
    Route::put('/financas/cartoes/compras/{compra}', [CartoesController::class, 'updateCompra'])->name('cartoes.compras.update');
    Route::delete('/financas/cartoes/compras/{compra}', [CartoesController::class, 'destroyCompra'])->name('cartoes.compras.destroy');
    Route::post('/financas/cartoes/previsoes', [CartoesController::class, 'storePrevisao'])->name('cartoes.previsoes.store');
    Route::put('/financas/cartoes/previsoes/{previsao}', [CartoesController::class, 'updatePrevisao'])->name('cartoes.previsoes.update');
    Route::delete('/financas/cartoes/previsoes/{previsao}', [CartoesController::class, 'destroyPrevisao'])->name('cartoes.previsoes.destroy');
    Route::delete('/financas/cartoes/{cartao}', [CartoesController::class, 'destroy'])->name('cartoes.destroy');
    Route::patch('/financas/cartoes/{cartao}/toggle-status', [CartoesController::class, 'toggleStatus'])->name('cartoes.toggle-status');
    Route::post('/financas/cartoes/{cartao}/recalcular', [CartoesController::class, 'recalcular'])->name('cartoes.recalcular');
    Route::put('/financas/cartoes/parcelas/{parcela}', [CartoesController::class, 'updateParcela'])->name('cartoes.parcelas.update');
    Route::post('/financas/cartoes/conciliar-fatura', [CartoesController::class, 'conciliarFatura'])->name('cartoes.conciliar.fatura');
    Route::get('/financas/cartoes/conciliar-fatura/pdf', [CartoesController::class, 'exportarRelatorioConciliacaoPdf'])->name('cartoes.conciliar.pdf');
    Route::post('/financas/cartoes/faturas/{fatura}/reconciliar', [CartoesController::class, 'reconciliarFaturaSalva'])->name('cartoes.faturas.reconciliar');
    Route::get('/financas/cartoes/faturas/{fatura}/download', [CartoesController::class, 'downloadFaturaArquivo'])->name('cartoes.faturas.download');
    Route::delete('/financas/cartoes/faturas/{fatura}', [CartoesController::class, 'destroyFatura'])->name('cartoes.faturas.destroy');
    Route::post('/financas/cartoes/conciliar/lancar-item', [CartoesController::class, 'lancarItemFaltante'])->name('cartoes.conciliar.lancar-item');
    Route::post('/financas/cartoes/conciliar/ajustar-centavos', [CartoesController::class, 'ajustarCentavos'])->name('cartoes.conciliar.ajustar-centavos');
    Route::post('/financas/cartoes/{cartao}/fatura-fechamento', [CartoesController::class, 'toggleFechamentoFatura'])->name('cartoes.fatura.fechamento');
    
    // WhatsApp Resumo
    Route::get('/financas/whatsapp/resumo', [FinancasController::class, 'whatsappResumo'])->name('financas.whatsapp.resumo');

    // Exportações
    Route::get('/financas/export/fatura/{cartao}/{format}', [ExportController::class, 'exportFatura'])->name('export.fatura');
    Route::get('/financas/export/orcamento/{format}', [ExportController::class, 'exportOrcamento'])->name('export.orcamento');
    Route::post('/financas/export/orcamento/{format}', [ExportController::class, 'exportOrcamentoPost'])->name('export.orcamento.post');
    Route::delete('/financas/telegram-logs', [TelegramWebhookController::class, 'clearLogs'])->name('telegram.logs.clear');
    Route::delete('/financas/telegram-logs/{log}', [TelegramWebhookController::class, 'destroyLog'])->name('telegram.logs.destroy');
    
    // Sistema 2 - Horas de Estudo (PWA offline estilo YPT)
    Route::get('/estudos', [EstudosController::class, 'index'])->name('estudos.index');
    Route::post('/estudos/sync', [EstudosController::class, 'sync'])->name('estudos.sync');
    Route::get('/estudos/guruja', [EstudosController::class, 'guruja'])->name('estudos.guruja');
    Route::post('/estudos/guruja/atualizar', [EstudosController::class, 'gurujaAtualizar'])->middleware('throttle:10,10')->name('estudos.guruja.atualizar');
    Route::post('/estudos/guruja/feita', [EstudosController::class, 'gurujaFeita'])->name('estudos.guruja.feita');
    Route::post('/estudos/guruja/ordem', [EstudosController::class, 'gurujaOrdem'])->name('estudos.guruja.ordem');
    Route::post('/estudos/guruja/comando', [EstudosController::class, 'gurujaComando'])->name('estudos.guruja.comando');
    Route::get('/estudos/token', [EstudosController::class, 'token'])->name('estudos.token');
    Route::get('/estudos/salas', [StudyRoomsController::class, 'index'])->name('estudos.salas');
    Route::post('/estudos/salas', [StudyRoomsController::class, 'store'])->name('estudos.salas.store');
    Route::post('/estudos/salas/entrar', [StudyRoomsController::class, 'join'])->name('estudos.salas.join');
    Route::post('/estudos/salas/{room}/sair', [StudyRoomsController::class, 'leave'])->whereUuid('room')->name('estudos.salas.leave');
    Route::post('/estudos/salas/{room}/remover/{user}', [StudyRoomsController::class, 'removeMember'])->whereUuid('room')->whereNumber('user')->name('estudos.salas.remove');
    Route::delete('/estudos/salas/{room}', [StudyRoomsController::class, 'destroy'])->whereUuid('room')->name('estudos.salas.destroy');
    Route::get('/estudos/api-tokens', [EstudosApiController::class, 'listTokens'])->name('estudos.api-tokens');
    Route::post('/estudos/api-tokens', [EstudosApiController::class, 'createToken'])->name('estudos.api-tokens.store');
    Route::delete('/estudos/api-tokens', [EstudosApiController::class, 'revokeTokens'])->name('estudos.api-tokens.destroy');



    // Compatibilidade com rota antiga
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Sistema 7 - Galeria & Mapeador de Fotos Inteligente
    Route::get('/photos/login', [PhotoController::class, 'showLogin'])->name('photos.login');
    Route::post('/photos/login', [PhotoController::class, 'login'])->name('photos.login.post');
    Route::post('/photos/logout', [PhotoController::class, 'logout'])->name('photos.logout');

    Route::middleware(\App\Http\Middleware\PhotoAuth::class)->group(function () {
        Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
        Route::get('/photos/album/{id}', [PhotoController::class, 'showAlbum'])->name('photos.album');
        Route::get('/photos/file', [PhotoController::class, 'servePhoto'])->name('photos.serve');
        Route::post('/photos/sync', [PhotoController::class, 'sync'])->name('photos.sync');
        Route::post('/photos/locations/add', [PhotoController::class, 'addLocation'])->name('photos.locations.add');
        Route::post('/photos/locations/remove', [PhotoController::class, 'removeLocation'])->name('photos.locations.remove');
        Route::post('/photos/pregenerate-thumbs', [PhotoController::class, 'pregenerateThumbs'])->name('photos.pregenerate');
        Route::post('/photos/album/{id}/rate', [PhotoController::class, 'rateAlbum'])->name('photos.album.rate');
        Route::post('/photos/album/{id}/favorite', [PhotoController::class, 'favoriteAlbum'])->name('photos.album.favorite');
        Route::post('/photos/album/{id}/tags', [PhotoController::class, 'updateTags'])->name('photos.album.tags');
        Route::post('/photos/album/{id}/delete', [PhotoController::class, 'deleteAlbum'])->name('photos.album.delete');
        Route::post('/photos/album/{id}/open-folder', [PhotoController::class, 'openFolder'])->name('photos.album.open-folder');
        Route::post('/photos/rescan-models', [PhotoController::class, 'rescanModels'])->name('photos.rescan-models');
    });

    // Sistema 7 - Simulador & Projetor Salarial (TJMS)
    Route::get('/salario', [SalaryController::class, 'index'])->name('salary.index');
    Route::post('/salario/projetar', [SalaryController::class, 'project'])->name('salary.project');
    Route::post('/salario/perfis', [SalaryController::class, 'saveProfile'])->name('salary.profiles.save');
    Route::put('/salario/perfis/{id}', [SalaryController::class, 'updateProfile'])->name('salary.profiles.update');
    Route::get('/salario/perfis/{id}', [SalaryController::class, 'loadProfile'])->name('salary.profiles.load');
    Route::delete('/salario/perfis/{id}', [SalaryController::class, 'deleteProfile'])->name('salary.profiles.delete');
    Route::post('/salario/perfis/{id}/ativar', [SalaryController::class, 'activateProfile'])->name('salary.profiles.activate');
    Route::patch('/salario/holerites/{mesAno}/outlier', [SalaryController::class, 'toggleOutlier'])
        ->where('mesAno', '\d{4}-\d{2}')
        ->name('salary.payslips.outlier');

    // Sistema 9 - Radar de Concursos Fiscais & Remunerações com Alertas Telegram
    Route::get('/concursos-fiscais', [FiscalConcursosController::class, 'index'])->name('fiscal.index');
    Route::get('/concursos-fiscais/concurso/{id}', [FiscalConcursosController::class, 'show'])->name('fiscal.show');
    Route::put('/concursos-fiscais/concurso/{id}', [FiscalConcursosController::class, 'update'])->name('fiscal.update');
    Route::post('/concursos-fiscais/concurso/{id}/reset', [FiscalConcursosController::class, 'reset'])->name('fiscal.reset');
    Route::post('/concursos-fiscais/ai-extract-url', [FiscalConcursosController::class, 'extractFromUrl'])->name('fiscal.ai-extract-url');
    Route::post('/concursos-fiscais/crawl', [FiscalConcursosController::class, 'crawl'])->name('fiscal.crawl');
    Route::post('/concursos-fiscais/send-news-telegram/{id}', [FiscalConcursosController::class, 'sendNewsToTelegram'])->name('fiscal.send-news-telegram');
    Route::post('/concursos-fiscais/send-concurso-telegram/{id}', [FiscalConcursosController::class, 'sendConcursoToTelegram'])->name('fiscal.send-concurso-telegram');
    Route::post('/concursos-fiscais/test-telegram', [FiscalConcursosController::class, 'testTelegram'])->name('fiscal.test-telegram');
    Route::post('/concursos-fiscais/telegram-config', [FiscalConcursosController::class, 'saveTelegramConfig'])->name('fiscal.telegram-config');

    // Sistema 10 - Treinos & Exercícios Físicos & BodyTracker
    Route::get('/treinos', [TreinosController::class, 'index'])->name('treinos.index');
    Route::post('/treinos/plans', [TreinosController::class, 'storePlan'])->name('treinos.plans.store');
    Route::delete('/treinos/plans/{id}', [TreinosController::class, 'destroyPlan'])->name('treinos.plans.destroy');
    Route::post('/treinos/plans/generate-default', [TreinosController::class, 'generateDefaultPlans'])->name('treinos.plans.generate-default');

    Route::post('/treinos/gears', [TreinosController::class, 'storeGear'])->name('treinos.gears.store');
    Route::delete('/treinos/gears/{id}', [TreinosController::class, 'destroyGear'])->name('treinos.gears.destroy');

    Route::post('/treinos/prs', [TreinosController::class, 'storePr'])->name('treinos.prs.store');
    Route::delete('/treinos/prs/{id}', [TreinosController::class, 'destroyPr'])->name('treinos.prs.destroy');

    Route::post('/treinos/sessions', [TreinosController::class, 'storeSession'])->name('treinos.sessions.store');
    Route::delete('/treinos/sessions/{id}', [TreinosController::class, 'destroySession'])->name('treinos.sessions.destroy');

    // Módulo Coach IA Gemini (Almoço/Refeição Livre, Treino Extra, Revisão Diária)
    Route::post('/treinos/ai/analyze-meal', [TreinosController::class, 'analyzeMeal'])->name('treinos.ai.analyze-meal');
    Route::post('/treinos/ai/analyze-workout', [TreinosController::class, 'analyzeWorkout'])->name('treinos.ai.analyze-workout');
    Route::post('/treinos/ai/daily-review', [TreinosController::class, 'dailyReview'])->name('treinos.ai.daily-review');
    Route::post('/treinos/ai/apply-suggestion', [TreinosController::class, 'applyAiSuggestion'])->name('treinos.ai.apply-suggestion');

    Route::get('/tracker/photos/{id}', [\App\Http\Controllers\BodyTrackerController::class, 'showPhotoFile'])->name('tracker.photo.show');

    // Endpoints REST do BodyTracker (documentação técnica)
    Route::prefix('api/tracker')->group(function () {
        Route::post('/daily-log', [\App\Http\Controllers\BodyTrackerController::class, 'saveDailyLog'])->name('api.tracker.daily-log.save');
        Route::get('/daily-log/today', [\App\Http\Controllers\BodyTrackerController::class, 'todayDailyLog'])->name('api.tracker.daily-log.today');
        Route::get('/daily-log/by-date', [\App\Http\Controllers\BodyTrackerController::class, 'getDailyLogByDate'])->name('api.tracker.daily-log.by-date');
        Route::get('/daily-log', [\App\Http\Controllers\BodyTrackerController::class, 'getDailyLogs'])->name('api.tracker.daily-log.index');

        Route::post('/measurements', [\App\Http\Controllers\BodyTrackerController::class, 'saveMeasurement'])->name('api.tracker.measurements.save');
        Route::get('/measurements/latest', [\App\Http\Controllers\BodyTrackerController::class, 'latestMeasurement'])->name('api.tracker.measurements.latest');
        Route::get('/measurements/summary-deltas', [\App\Http\Controllers\BodyTrackerController::class, 'summaryDeltas'])->name('api.tracker.measurements.summary-deltas');

        Route::post('/photos', [\App\Http\Controllers\BodyTrackerController::class, 'uploadPhoto'])->name('api.tracker.photos.upload');
        Route::get('/photos', [\App\Http\Controllers\BodyTrackerController::class, 'getPhotos'])->name('api.tracker.photos.index');
        Route::delete('/photos/{id}', [\App\Http\Controllers\BodyTrackerController::class, 'deletePhoto'])->name('api.tracker.photos.delete');
    });
});

