<?php

namespace App\Http\Controllers;

use App\Models\FiscalConcurso;
use App\Models\FiscalNoticia;
use App\Models\FiscalTelegramConfig;
use App\Services\FiscalConcursoDataService;
use App\Services\FiscalNewsAiService;
use App\Services\FiscalNewsCrawlerService;
use App\Services\FiscalTelegramNotifierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FiscalConcursosController extends Controller
{
    public function __construct(
        protected FiscalConcursoDataService $dataService,
        protected FiscalTelegramNotifierService $telegramNotifier,
        protected FiscalNewsCrawlerService $crawler,
        protected FiscalNewsAiService $newsAiService
    ) {}

    /**
     * Exibe o painel principal do módulo de Concursos Fiscais.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Sincronizar dados mestre se estiver vazio
        if (FiscalConcurso::count() === 0) {
            $this->dataService->syncDatabaseCatalog();
        }

        // 2. Estatísticas / KPIs
        $totalConcursos = FiscalConcurso::count();
        $comissaoOuBanca = FiscalConcurso::whereIn('status', ['comissao_formada', 'escolha_banca', 'banca_definida'])->count();
        $editaisAbertos = FiscalConcurso::whereIn('status', ['edital_publicado', 'inscricoes_abertas'])->count();
        $maiorSalarioInicial = FiscalConcurso::max('remuneracao_inicial_bruta') ?? 0;
        $mediaSalarioInicial = FiscalConcurso::avg('remuneracao_inicial_bruta') ?? 0;
        $totalNoticias = FiscalNoticia::count();

        // 3. Filtros da listagem de Concursos
        $esfera = $request->query('esfera', 'todas');
        $status = $request->query('status', 'todos');
        $busca  = $request->query('busca', '');
        $ordenar = $request->query('ordenar', 'salario_desc');

        $query = FiscalConcurso::query();

        if ($esfera !== 'todas') {
            $query->where('esfera', $esfera);
        }

        if ($status !== 'todos') {
            $query->where('status', $status);
        } else {
            // Prioriza concursos que ainda não foram realizados / em andamento / futuros
            $query->apenasFuturosOuAbertos();
        }

        if (!empty($busca)) {
            $query->where(function ($q) use ($busca) {
                $term = "%{$busca}%";
                $q->where('sigla', 'like', $term)
                  ->orWhere('nome_orgao', 'like', $term)
                  ->orWhere('municipio', 'like', $term)
                  ->orWhere('uf', 'like', $term)
                  ->orWhere('cargo_principal', 'like', $term);
            });
        }

        // Ordenação
        match ($ordenar) {
            'salario_asc'  => $query->orderBy('remuneracao_inicial_bruta', 'asc'),
            'real_desc'    => $query->orderBy('remuneracao_real_transparencia', 'desc'),
            'nome'         => $query->orderBy('sigla', 'asc'),
            'status'       => $query->orderBy('status', 'asc'),
            default        => $query->orderBy('remuneracao_inicial_bruta', 'desc'),
        };

        $concursos = $query->paginate(15)->withQueryString();

        // 4. Últimas notícias rastreadas de concursos futuros ou abertos
        $noticiasRecentes = FiscalNoticia::apenasFuturosOuAbertos()
            ->with('concurso')
            ->orderBy('publicado_em', 'desc')
            ->take(10)
            ->get();

        // 5. Configurações do Telegram do usuário
        $telegramConfig = FiscalTelegramConfig::firstOrCreate(
            ['user_id' => $user->id],
            [
                'chat_id'                  => config('telegram.allowed_chat_id'),
                'notificar_automaticamente'=> true,
                'notificar_federal'        => true,
                'notificar_estadual'       => true,
                'notificar_municipal'      => true,
                'filtro_salario_minimo'    => 0,
            ]
        );

        // 6. Ranking Top 10 Melhores Remunerações Reais
        $topSalarios = FiscalConcurso::orderBy('remuneracao_real_transparencia', 'desc')->take(8)->get();

        // 7. Dados Específicos para a Aba "Panorama Fiscos Estaduais (27 SEFAZ)"
        $tab = $request->query('tab', 'radar');
        $regiaoEstadual = $request->query('regiao_estadual', 'todas');
        $vigenciaEstadual = $request->query('vigencia_estadual', 'todas');
        $buscaEstadual = $request->query('busca_estadual', '');
        $ordenarEstadual = $request->query('ordenar_estadual', 'salario_desc');

        $queryEstaduais = FiscalConcurso::estadual()->with(['noticias' => function ($q) {
            $q->orderBy('publicado_em', 'desc')->limit(3);
        }]);

        if ($regiaoEstadual !== 'todas') {
            $queryEstaduais->where('regiao', $regiaoEstadual);
        }

        if ($vigenciaEstadual === 'vencidos') {
            $queryEstaduais->whereIn('ultimo_concurso_status_vigencia', ['vencido', 'sem_concurso_valido']);
        } elseif ($vigenciaEstadual === 'vigentes') {
            $queryEstaduais->whereIn('ultimo_concurso_status_vigencia', ['vigente', 'prorrogado']);
        } elseif ($vigenciaEstadual === 'novos_editais') {
            $queryEstaduais->whereIn('status', ['comissao_formada', 'escolha_banca', 'banca_definida', 'autorizado', 'edital_publicado']);
        }

        if (!empty($buscaEstadual)) {
            $queryEstaduais->where(function ($q) use ($buscaEstadual) {
                $term = "%{$buscaEstadual}%";
                $q->where('sigla', 'like', $term)
                  ->orWhere('nome_orgao', 'like', $term)
                  ->orWhere('uf', 'like', $term)
                  ->orWhere('banca', 'like', $term)
                  ->orWhere('ultimo_concurso_banca', 'like', $term);
            });
        }

        match ($ordenarEstadual) {
            'salario_asc' => $queryEstaduais->orderBy('remuneracao_inicial_bruta', 'asc'),
            'real_desc'   => $queryEstaduais->orderBy('remuneracao_real_transparencia', 'desc'),
            'antigos'     => $queryEstaduais->orderBy('ultimo_concurso_ano', 'asc'),
            'recentes'    => $queryEstaduais->orderBy('ultimo_concurso_ano', 'desc'),
            'uf'          => $queryEstaduais->orderBy('uf', 'asc'),
            default       => $queryEstaduais->orderBy('remuneracao_inicial_bruta', 'desc'),
        };

        $fiscosEstaduais = $queryEstaduais->get();

        // KPIs Exclusivos da Esfera Estadual (27 SEFAZ)
        $totalEstaduais = FiscalConcurso::estadual()->count();
        $estaduaisVencidos = FiscalConcurso::estadual()->whereIn('ultimo_concurso_status_vigencia', ['vencido', 'sem_concurso_valido'])->count();
        $estaduaisVigentes = FiscalConcurso::estadual()->whereIn('ultimo_concurso_status_vigencia', ['vigente', 'prorrogado'])->count();
        $estaduaisComissaoOuBanca = FiscalConcurso::estadual()->whereIn('status', ['comissao_formada', 'escolha_banca', 'banca_definida', 'autorizado', 'edital_publicado'])->count();
        $mediaSalarioEstadual = FiscalConcurso::estadual()->avg('remuneracao_inicial_bruta') ?? 0;
        $mediaRealEstadual = FiscalConcurso::estadual()->avg('remuneracao_real_transparencia') ?? 0;
        $maiorSalarioEstadual = FiscalConcurso::estadual()->max('remuneracao_inicial_bruta') ?? 0;

        return view('fiscal.index', compact(
            'totalConcursos',
            'comissaoOuBanca',
            'editaisAbertos',
            'maiorSalarioInicial',
            'mediaSalarioInicial',
            'totalNoticias',
            'concursos',
            'noticiasRecentes',
            'telegramConfig',
            'topSalarios',
            'esfera',
            'status',
            'busca',
            'ordenar',
            'tab',
            'fiscosEstaduais',
            'totalEstaduais',
            'estaduaisVencidos',
            'estaduaisVigentes',
            'estaduaisComissaoOuBanca',
            'mediaSalarioEstadual',
            'mediaRealEstadual',
            'maiorSalarioEstadual',
            'regiaoEstadual',
            'vigenciaEstadual',
            'buscaEstadual',
            'ordenarEstadual'
        ));
    }

    /**
     * Retorna detalhes aprofundados de um concurso fiscal em formato JSON.
     */
    public function show(int $id)
    {
        $concurso = FiscalConcurso::with(['noticias' => function ($q) {
            $q->orderBy('publicado_em', 'desc')->limit(5);
        }])->findOrFail($id);

        return response()->json([
            'success'  => true,
            'concurso' => $concurso,
        ]);
    }

    /**
     * Atualiza manualmente os dados de um concurso fiscal e marca como editado_manualmente.
     */
    public function update(Request $request, int $id)
    {
        $concurso = FiscalConcurso::findOrFail($id);

        $validated = $request->validate([
            'nome_orgao'                      => 'required|string|max:150',
            'cargo_principal'                 => 'required|string|max:150',
            'status'                          => 'required|string',
            'banca'                           => 'nullable|string|max:100',
            'vagas_previstas'                 => 'nullable|string|max:100',
            'requisito_escolaridade'          => 'nullable|string|max:255',
            'jornada'                         => 'nullable|string|max:50',
            'remuneracao_inicial_bruta'       => 'nullable|numeric|min:0',
            'vencimento_basico'               => 'nullable|numeric|min:0',
            'produtividade_estimada'          => 'nullable|numeric|min:0',
            'produtividade_detalhes'          => 'nullable|string',
            'beneficios_estimados'            => 'nullable|numeric|min:0',
            'beneficios_detalhes'             => 'nullable|string',
            'remuneracao_real_transparencia'  => 'nullable|numeric|min:0',
            'remuneracao_teto'                => 'nullable|numeric|min:0',
            'ultimo_concurso_ano'             => 'nullable|integer',
            'ultimo_concurso_banca'           => 'nullable|string|max:100',
            'ultimo_concurso_vagas'           => 'nullable|string|max:100',
            'ultimo_concurso_link'            => 'nullable|string|max:500',
            'regiao'                          => 'nullable|string|max:50',
            'ultimo_concurso_status_vigencia' => 'nullable|string|max:50',
            'ultimo_concurso_validade_fim'    => 'nullable|string|max:50',
            'ultimo_concurso_vigencia_detalhes'=> 'nullable|string',
            'link_portal_transparencia'       => 'nullable|string|max:500',
            'observacoes_estrategicas'        => 'nullable|string',
        ]);

        $validated['editado_manualmente'] = true;

        $concurso->update($validated);

        return response()->json([
            'success'  => true,
            'message'  => "Concurso {$concurso->sigla} atualizado com sucesso! As alterações manuais foram salvas e protegidas contra sobrescrita automática.",
            'concurso' => $concurso->fresh(),
        ]);
    }

    /**
     * Restaura os dados originais do catálogo mestre para o concurso.
     */
    public function reset(int $id)
    {
        $concurso = $this->dataService->resetToMaster($id);
        if (!$concurso) {
            return response()->json(['success' => false, 'message' => 'Concurso não encontrado.'], 404);
        }

        return response()->json([
            'success'  => true,
            'message'  => "Concurso {$concurso->sigla} restaurado para o padrão original do catálogo mestre.",
            'concurso' => $concurso,
        ]);
    }

    /**
     * Extrai e atualiza automaticamente os dados de um concurso a partir da URL de uma matéria ou texto livre via IA OmniRoute / Gemini.
     */
    public function extractFromUrl(Request $request)
    {
        $request->validate([
            'url'              => 'nullable|string',
            'raw_text'         => 'nullable|string',
            'auto_update'      => 'nullable|boolean',
            'notify_telegram'  => 'nullable|boolean',
            'concurso_id'      => 'nullable|integer',
        ]);

        $url = trim((string)$request->input('url'));
        $rawText = trim((string)$request->input('raw_text'));

        if (empty($url) && empty($rawText)) {
            return response()->json([
                'success' => false,
                'message' => 'Informe a URL da notícia ou cole o texto da matéria para análise.',
            ], 422);
        }

        $isUrl = !empty($url);
        $target = $isUrl ? $url : $rawText;

        try {
            $result = $this->newsAiService->processNewsWithAi(
                urlOrText: $target,
                isUrl: $isUrl,
                autoUpdateConcurso: $request->boolean('auto_update', true),
                notifyTelegram: $request->boolean('notify_telegram', false),
                forcedConcursoId: $request->filled('concurso_id') ? (int)$request->input('concurso_id') : null
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('[FiscalConcursosController] Erro no processamento de notícia via IA: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Falha ao processar a matéria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa o crawler sob demanda para buscar notícias frescas.
     */
    public function crawl(Request $request)
    {
        try {
            $novas = $this->crawler->crawlAll();
            $total = count($novas);

            if ($request->boolean('notify') && $total > 0) {
                $this->telegramNotifier->notifyPendingNews();
            }

            return response()->json([
                'success' => true,
                'message' => "Crawler executado com sucesso! {$total} notícias processadas.",
                'total'   => $total,
            ]);
        } catch (\Throwable $e) {
            Log::error('[FiscalConcursosController] Erro no crawler: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar o crawler: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envia uma notícia específica para o Telegram do usuário.
     */
    public function sendNewsToTelegram(int $id)
    {
        $noticia = FiscalNoticia::with('concurso')->findOrFail($id);
        $enviado = $this->telegramNotifier->sendNewsAlert($noticia);

        if ($enviado) {
            return response()->json([
                'success' => true,
                'message' => 'Notícia com raio-x salarial enviada para o seu Telegram com sucesso!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Falha ao enviar notícia. Verifique as credenciais do Telegram no .env.',
        ], 500);
    }

    /**
     * Envia o raio-x completo de um concurso para o Telegram.
     */
    public function sendConcursoToTelegram(int $id)
    {
        $concurso = FiscalConcurso::findOrFail($id);
        $mensagem = $this->telegramNotifier->formatConcursoProfileMessage($concurso);

        $chatId = config('telegram.allowed_chat_id');
        $chatIds = explode(',', $chatId);
        $success = false;

        foreach ($chatIds as $cid) {
            $clean = trim($cid);
            if (!empty($clean)) {
                $ok = app(TelegramService::class)->sendMessage($clean, $mensagem);
                if ($ok) {
                    $success = true;
                }
            }
        }

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "Raio-X de {$concurso->sigla} enviado para o Telegram com sucesso!",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível enviar mensagem. Verifique a configuração do Telegram.',
        ], 500);
    }

    /**
     * Dispara um teste completo de notificação para o Telegram do usuário.
     */
    public function testTelegram()
    {
        $noticia = FiscalNoticia::with('concurso')->orderBy('publicado_em', 'desc')->first();

        if (!$noticia) {
            $concurso = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first() ?? FiscalConcurso::first();
            $msg = $this->telegramNotifier->formatConcursoProfileMessage($concurso);
            $ok = app(TelegramService::class)->sendMessage(config('telegram.allowed_chat_id'), $msg);
        } else {
            $ok = $this->telegramNotifier->sendNewsAlert($noticia);
        }

        if ($ok) {
            return response()->json([
                'success' => true,
                'message' => '✓ Mensagem de teste do Radar Fiscal enviada ao Telegram com sucesso!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Falha ao enviar teste para o Telegram. Verifique se o Bot Token e Chat ID estão corretos.',
        ], 500);
    }

    /**
     * Salva as configurações de notificação do Telegram.
     */
    public function saveTelegramConfig(Request $request)
    {
        $validated = $request->validate([
            'chat_id'                  => 'nullable|string|max:100',
            'notificar_automaticamente'=> 'nullable|boolean',
            'notificar_federal'        => 'nullable|boolean',
            'notificar_estadual'       => 'nullable|boolean',
            'notificar_municipal'      => 'nullable|boolean',
            'filtro_salario_minimo'    => 'nullable|numeric|min:0',
        ]);

        $config = FiscalTelegramConfig::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'chat_id'                  => $validated['chat_id'] ?? config('telegram.allowed_chat_id'),
                'notificar_automaticamente'=> $request->boolean('notificar_automaticamente'),
                'notificar_federal'        => $request->boolean('notificar_federal'),
                'notificar_estadual'       => $request->boolean('notificar_estadual'),
                'notificar_municipal'      => $request->boolean('notificar_municipal'),
                'filtro_salario_minimo'    => $validated['filtro_salario_minimo'] ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Configurações de alerta salvas com sucesso!',
            'config'  => $config,
        ]);
    }
}
