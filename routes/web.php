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
use App\Http\Controllers\ConcursosController;
use App\Http\Controllers\StudyRoomsController;
use App\Http\Controllers\SalaryController;
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

Route::middleware(['auth', 'modulos'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Gestão de Convites / Whitelist Google (Admin)
    Route::get('/admin/convites', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'index'])->name('admin.convites');
    Route::post('/admin/convites', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'store'])->name('admin.convites.store');
    Route::delete('/admin/convites/{id}', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'destroy'])->name('admin.convites.destroy');
    Route::post('/admin/usuarios/{user}/gestor', [\App\Http\Controllers\Admin\AllowedEmailController::class, 'toggleManager'])->name('admin.usuarios.gestor');

    // Sincronização Nuvem Neon
    // Só o dono: a sincronização lê e grava o banco inteiro, de todos os usuários.
    Route::middleware('owner')->group(function () {
        Route::get('/sync/status', [DatabaseSyncController::class, 'status'])->name('sync.status');
        Route::post('/sync/run', [DatabaseSyncController::class, 'sync'])->name('sync.run');
    });
    
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
    Route::get('/estudos/guruja/post-meta', [EstudosController::class, 'gurujaPostMeta'])->name('estudos.guruja.post-meta');
    Route::post('/estudos/guruja/atualizar', [EstudosController::class, 'gurujaAtualizar'])->middleware('throttle:10,10')->name('estudos.guruja.atualizar');
    Route::get('/estudos/guruja/auth', [EstudosController::class, 'gurujaAuth'])->name('estudos.guruja.auth');
    Route::get('/estudos/guruja/callback', [EstudosController::class, 'gurujaCallback'])->name('estudos.guruja.callback');
    Route::post('/estudos/guruja/desconectar', [EstudosController::class, 'gurujaDesconectar'])->name('estudos.guruja.desconectar');
    Route::post('/estudos/guruja/importar', [EstudosController::class, 'gurujaImportar'])->name('estudos.guruja.importar');
    Route::post('/estudos/guruja/feita', [EstudosController::class, 'gurujaFeita'])->name('estudos.guruja.feita');
    Route::post('/estudos/guruja/ordem', [EstudosController::class, 'gurujaOrdem'])->name('estudos.guruja.ordem');
    Route::post('/estudos/guruja/comando', [EstudosController::class, 'gurujaComando'])->name('estudos.guruja.comando');
    Route::get('/estudos/token', [EstudosController::class, 'token'])->name('estudos.token');
    Route::delete('/estudos/guruja/comando', [EstudosController::class, 'gurujaComandoApagar'])->name('estudos.guruja.comando.apagar');
    Route::get('/estudos/salas', [StudyRoomsController::class, 'index'])->name('estudos.salas');
    Route::post('/estudos/salas', [StudyRoomsController::class, 'store'])->name('estudos.salas.store');
    Route::post('/estudos/salas/entrar', [StudyRoomsController::class, 'join'])->name('estudos.salas.join');
    Route::post('/estudos/salas/{room}/sair', [StudyRoomsController::class, 'leave'])->whereUuid('room')->name('estudos.salas.leave');
    Route::post('/estudos/salas/{room}/remover/{user}', [StudyRoomsController::class, 'removeMember'])->whereUuid('room')->whereNumber('user')->name('estudos.salas.remove');
    Route::delete('/estudos/salas/{room}', [StudyRoomsController::class, 'destroy'])->whereUuid('room')->name('estudos.salas.destroy');
    Route::get('/estudos/api-tokens', [EstudosApiController::class, 'listTokens'])->name('estudos.api-tokens');
    Route::post('/estudos/api-tokens', [EstudosApiController::class, 'createToken'])->name('estudos.api-tokens.store');
    Route::delete('/estudos/api-tokens', [EstudosApiController::class, 'revokeTokens'])->name('estudos.api-tokens.destroy');

    // Módulo novo de concursos (banco concursos_pgsql; só onde CONCURSOS_ENABLED=true)
    Route::middleware([\App\Http\Middleware\EnsureConcursosHabilitado::class, \App\Http\Middleware\EnsurePlanoConcursos::class])->prefix('concursos/questoes')->name('concursos.questoes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Concursos\QuestoesController::class, 'index'])->name('index');
        Route::get('/resolver', [\App\Http\Controllers\Concursos\QuestoesController::class, 'resolver'])->name('resolver');
        Route::get('/{questao}', [\App\Http\Controllers\Concursos\QuestoesController::class, 'show'])->whereNumber('questao')->name('show');
        Route::post('/{questao}/responder', [\App\Http\Controllers\Concursos\QuestoesController::class, 'responder'])->whereNumber('questao')->name('responder');
        Route::post('/tentativas/{tentativa}/motivo', [\App\Http\Controllers\Concursos\QuestoesController::class, 'motivo'])->whereNumber('tentativa')->name('motivo');
    });
    Route::middleware(\App\Http\Middleware\EnsureConcursosHabilitado::class)->prefix('concursos')->name('concursos.')->group(function () {
        Route::get('/meta', [\App\Http\Controllers\Concursos\MetaController::class, 'meta'])->name('meta')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/meta/atividades/{atividade}/feita', [\App\Http\Controllers\Concursos\MetaController::class, 'feita'])->whereNumber('atividade')->name('meta.feita')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/meta/atividades/{atividade}/adiar', [\App\Http\Controllers\Concursos\MetaController::class, 'adiar'])->whereNumber('atividade')->name('meta.adiar')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::get('/perfil', [\App\Http\Controllers\Concursos\MetaController::class, 'perfil'])->name('perfil')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/perfil', [\App\Http\Controllers\Concursos\MetaController::class, 'salvarPerfil'])->name('perfil.salvar')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::get('/painel', [\App\Http\Controllers\Concursos\PainelController::class, 'index'])->name('painel')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::get('/simulados', [\App\Http\Controllers\Concursos\SimuladosController::class, 'index'])->name('simulados')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/simulados', [\App\Http\Controllers\Concursos\SimuladosController::class, 'store'])->name('simulados.store')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::get('/simulados/{simulado}', [\App\Http\Controllers\Concursos\SimuladosController::class, 'show'])->whereNumber('simulado')->name('simulados.show')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/simulados/{simulado}/respostas', [\App\Http\Controllers\Concursos\SimuladosController::class, 'salvar'])->whereNumber('simulado')->name('simulados.salvar')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::post('/simulados/{simulado}/entregar', [\App\Http\Controllers\Concursos\SimuladosController::class, 'entregar'])->whereNumber('simulado')->name('simulados.entregar')->middleware(\App\Http\Middleware\EnsurePlanoConcursos::class);
        Route::get('/curadoria', [\App\Http\Controllers\Concursos\CuradoriaController::class, 'index'])->name('curadoria');
        Route::get('/curadoria/assuntos/{assunto}', [\App\Http\Controllers\Concursos\CuradoriaController::class, 'assunto'])->whereNumber('assunto')->name('curadoria.assunto');
        Route::post('/curadoria/assuntos/{assunto}/aprovar-todos', [\App\Http\Controllers\Concursos\CuradoriaController::class, 'aprovarTodos'])->whereNumber('assunto')->name('curadoria.aprovar-todos');
        Route::post('/curadoria/subtopicos/{subtopico}', [\App\Http\Controllers\Concursos\CuradoriaController::class, 'acao'])->whereNumber('subtopico')->name('curadoria.acao');

        // Material de resumo (cadernos de erros) e lei seca do acervo ctresb/leis
        Route::get('/material', [\App\Http\Controllers\Concursos\MaterialController::class, 'index'])->name('material.index');
        Route::post('/material', [\App\Http\Controllers\Concursos\MaterialController::class, 'store'])->name('material.store');
        // Material de resumo como livro: capítulos, editor visual, notas na margem e imagens.
        Route::get('/material/{material}', [\App\Http\Controllers\Concursos\LivroController::class, 'show'])->whereNumber('material')->name('material.show');
        Route::get('/material/{material}/capitulos/{capitulo}', [\App\Http\Controllers\Concursos\LivroController::class, 'capitulo'])->whereNumber(['material', 'capitulo'])->name('material.capitulo');
        Route::post('/material/{material}/capitulos', [\App\Http\Controllers\Concursos\LivroController::class, 'criar'])->whereNumber('material')->name('material.capitulos.store');
        Route::post('/material/{material}/capitulos/ordem', [\App\Http\Controllers\Concursos\LivroController::class, 'ordenar'])->whereNumber('material')->name('material.capitulos.ordem');
        Route::patch('/material/capitulos/{capitulo}', [\App\Http\Controllers\Concursos\LivroController::class, 'renomear'])->whereNumber('capitulo')->name('material.capitulos.update');
        Route::delete('/material/capitulos/{capitulo}', [\App\Http\Controllers\Concursos\LivroController::class, 'apagar'])->whereNumber('capitulo')->name('material.capitulos.destroy');
        Route::put('/material/capitulos/{capitulo}/conteudo', [\App\Http\Controllers\Concursos\LivroController::class, 'salvar'])->whereNumber('capitulo')->name('material.capitulos.conteudo');
        Route::post('/material/capitulos/{capitulo}/pontos', [\App\Http\Controllers\Concursos\LivroController::class, 'novoPontoNoTexto'])->whereNumber('capitulo')->name('material.capitulos.pontos');
        Route::post('/material/capitulos/{capitulo}/anotacoes', [\App\Http\Controllers\Concursos\LivroController::class, 'anotar'])->whereNumber('capitulo')->name('material.capitulos.anotacoes');
        Route::post('/material/{material}/anexos', [\App\Http\Controllers\Concursos\LivroController::class, 'enviarAnexo'])->whereNumber('material')->name('material.anexos.store');
        Route::get('/material/anexos/{anexo}', [\App\Http\Controllers\Concursos\LivroController::class, 'verAnexo'])->whereNumber('anexo')->name('material.anexos.show');
        Route::get('/material/{material}/questoes', [\App\Http\Controllers\Concursos\LivroController::class, 'questoes'])->whereNumber('material')->name('material.questoes');
        Route::get('/material/{material}/buscar', [\App\Http\Controllers\Concursos\LivroController::class, 'buscar'])->whereNumber('material')->name('material.buscar');
        Route::get('/material/{material}/imprimir', [\App\Http\Controllers\Concursos\LivroController::class, 'imprimir'])->whereNumber('material')->name('material.imprimir');
        Route::get('/material/citacao', [\App\Http\Controllers\Concursos\LivroController::class, 'citacao'])->name('material.citacao');
        Route::get('/material/{material}/refazer', [\App\Http\Controllers\Concursos\RefazerController::class, 'index'])->whereNumber('material')->name('material.refazer');
        Route::post('/material/questoes/{questao}/responder', [\App\Http\Controllers\Concursos\RefazerController::class, 'responder'])->whereNumber('questao')->name('material.questoes.responder');
        Route::post('/material/pontos/{ponto}/errei', [\App\Http\Controllers\Concursos\MaterialController::class, 'errei'])->whereNumber('ponto')->name('material.pontos.errei');
        Route::post('/material/pontos/{ponto}/status', [\App\Http\Controllers\Concursos\MaterialController::class, 'status'])->whereNumber('ponto')->name('material.pontos.status');
        Route::get('/legislacao', [\App\Http\Controllers\Concursos\LeisController::class, 'index'])->name('leis.index');
        Route::get('/legislacao/buscar', [\App\Http\Controllers\Concursos\LeisController::class, 'buscar'])->name('leis.buscar');
        Route::get('/legislacao/acervo', [\App\Http\Controllers\Concursos\LeisController::class, 'acervo'])->name('leis.acervo');
        Route::get('/jurisprudencia/{tipo}/{numero}', [\App\Http\Controllers\Concursos\LeisController::class, 'juris'])->where(['tipo' => '[a-z]+', 'numero' => '\d{1,6}'])->name('juris.show');
        Route::get('/legislacao/{lei}', [\App\Http\Controllers\Concursos\LeisController::class, 'show'])->where('lei', '[a-z][a-z0-9]*')->name('leis.show');
        Route::get('/legislacao/{lei}/artigos/{artigo}', [\App\Http\Controllers\Concursos\LeisController::class, 'artigo'])->where(['lei' => '[a-z][a-z0-9]*', 'artigo' => '\d+(-[A-Za-z])?'])->name('leis.artigo');
    });

    // Sistema - Estudo para Concursos (Questões, Simulados, Flashcards, Lei Seca e Edital)
    Route::get('/concursos',[ConcursosController::class, 'index'])->name('concursos.index');
    Route::get('/concursos/__estado', [ConcursosController::class, 'getState'])->name('concursos.state.get');
    Route::post('/concursos/__estado', [ConcursosController::class, 'saveState'])->name('concursos.state.save');
    Route::get('/concursos/{page}', [ConcursosController::class, 'page'])
        ->where('page', '(inicio|caderno|simulados|flashcards|refazer|resumos|leis)\.html')
        ->name('concursos.page');
    Route::get('/concursos/{path}', [ConcursosController::class, 'asset'])
        ->where('path', '(css|js|dados|leis)/.+|icone\.svg')
        ->name('concursos.asset');



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
    Route::patch('/salario/holerites/{mesAno}/outlier', [SalaryController::class, 'toggleOutlier'])
        ->where('mesAno', '\d{4}-\d{2}')
        ->name('salary.payslips.outlier');

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

