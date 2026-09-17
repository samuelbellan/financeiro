<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FinancasController;
use App\Http\Controllers\CartoesController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\EstudosController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\FiscalConcursosController;
use App\Http\Controllers\MercadoController;
use App\Http\Controllers\TreinosController;
use App\Http\Controllers\DatabaseSyncController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// ── Telegram Webhook (rota pública, sem autenticação) ─────────────────────────
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'receive'])
    ->name('webhook.telegram');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

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
    
    // Exportações
    Route::get('/financas/export/fatura/{cartao}/{format}', [ExportController::class, 'exportFatura'])->name('export.fatura');
    Route::get('/financas/export/orcamento/{format}', [ExportController::class, 'exportOrcamento'])->name('export.orcamento');
    Route::post('/financas/export/orcamento/{format}', [ExportController::class, 'exportOrcamentoPost'])->name('export.orcamento.post');
    Route::delete('/financas/telegram-logs', [TelegramWebhookController::class, 'clearLogs'])->name('telegram.logs.clear');
    Route::delete('/financas/telegram-logs/{log}', [TelegramWebhookController::class, 'destroyLog'])->name('telegram.logs.destroy');
    
    // Sistema 2 - Calculadora de Estudos
    Route::get('/estudos', [EstudosController::class, 'index'])->name('estudos.index');
    Route::post('/estudos/goals', [EstudosController::class, 'storeGoal'])->name('estudos.goals.store');
    Route::post('/estudos/goals/{goal}/activate', [EstudosController::class, 'activateGoal'])->name('estudos.goals.activate');
    Route::delete('/estudos/goals/{goal}', [EstudosController::class, 'destroyGoal'])->name('estudos.goals.destroy');
    Route::post('/estudos/logs', [EstudosController::class, 'storeLog'])->name('estudos.logs.store');
    Route::put('/estudos/logs/{log}', [EstudosController::class, 'updateLog'])->name('estudos.logs.update');
    Route::delete('/estudos/logs/{log}', [EstudosController::class, 'destroyLog'])->name('estudos.logs.destroy');



    // Compatibilidade com rota antiga
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    // Sistema 7 - Simulador & Projetor Salarial (TJMS)
    Route::get('/salario', [SalaryController::class, 'index'])->name('salary.index');
    Route::post('/salario/projetar', [SalaryController::class, 'project'])->name('salary.project');
    Route::post('/salario/perfis', [SalaryController::class, 'saveProfile'])->name('salary.profiles.save');
    Route::put('/salario/perfis/{id}', [SalaryController::class, 'updateProfile'])->name('salary.profiles.update');
    Route::get('/salario/perfis/{id}', [SalaryController::class, 'loadProfile'])->name('salary.profiles.load');
    Route::delete('/salario/perfis/{id}', [SalaryController::class, 'deleteProfile'])->name('salary.profiles.delete');
    Route::post('/salario/perfis/{id}/ativar', [SalaryController::class, 'activateProfile'])->name('salary.profiles.activate');

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

    // Sistema 10 - Treinos & Exercícios Físicos
    Route::get('/treinos', [TreinosController::class, 'index'])->name('treinos.index');
});

