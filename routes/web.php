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

Route::get('/', function () {
    return view('welcome');
});

// ── Telegram Webhook (rota pública, sem autenticação) ─────────────────────────
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'receive'])
    ->name('webhook.telegram');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Finanças de Casa
    Route::get('/financas', [FinancasController::class, 'index'])->name('financas.index');
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
    Route::delete('/estudos/logs/{log}', [EstudosController::class, 'destroyLog'])->name('estudos.logs.destroy');

    // Compatibilidade com rota antiga
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');
});
