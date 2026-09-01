<?php

namespace Tests\Feature;

use App\Models\FiscalConcurso;
use App\Models\FiscalNoticia;
use App\Models\User;
use App\Services\FiscalConcursoDataService;
use App\Services\FiscalNewsCrawlerService;
use App\Services\FiscalTelegramNotifierService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Garantir catálogo populado
        app(FiscalConcursoDataService::class)->syncDatabaseCatalog();
    }

    public function test_fiscal_catalog_seeds_all_categories()
    {
        $this->assertGreaterThan(30, FiscalConcurso::count());

        $rfb = FiscalConcurso::where('sigla', 'RFB - Auditor')->first();
        $this->assertNotNull($rfb);
        $this->assertEquals('federal', $rfb->esfera);
        $this->assertGreaterThan(20000, (float)$rfb->remuneracao_inicial_bruta);

        $sefazSp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();
        $this->assertNotNull($sefazSp);
        $this->assertEquals('estadual', $sefazSp->esfera);
        $this->assertGreaterThan(30000, (float)$sefazSp->remuneracao_inicial_bruta);

        $issSp = FiscalConcurso::where('sigla', 'ISS-SP')->first();
        $this->assertNotNull($issSp);
        $this->assertEquals('municipal', $issSp->esfera);
    }

    public function test_crawler_parses_and_matches_fiscal_news()
    {
        $sampleRss = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
    <title>Notícias Concursos Fiscais</title>
    <item>
        <title>Concurso SEFAZ SP: Comissão formada para Auditor Fiscal da Receita Estadual</title>
        <link>https://exemplo.com/noticias/concurso-sefaz-sp-2026</link>
        <description>A Secretaria da Fazenda de São Paulo formou comissão para novo concurso com 250 vagas de Auditor Fiscal da Receita Estadual.</description>
        <pubDate>Tue, 25 Aug 2026 10:00:00 -0300</pubDate>
    </item>
</channel>
</rss>
XML;

        Http::fake([
            '*' => Http::response($sampleRss, 200, ['Content-Type' => 'application/xml']),
        ]);

        $crawler = app(FiscalNewsCrawlerService::class);
        $novas = $crawler->crawlAll();

        $this->assertNotEmpty($novas);
        $noticia = FiscalNoticia::where('url', 'https://exemplo.com/noticias/concurso-sefaz-sp-2026')->first();
        $this->assertNotNull($noticia);
        $this->assertEquals('estadual', $noticia->esfera);
        $this->assertEquals('SP', $noticia->uf);
        $this->assertEquals('Comissão Formada', $noticia->status_detectado);
        $this->assertNotNull($noticia->fiscal_concurso_id);
    }

    public function test_telegram_message_formatter_creates_rich_salary_breakdown()
    {
        $concurso = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();
        $notifier = app(FiscalTelegramNotifierService::class);

        $msg = $notifier->formatConcursoProfileMessage($concurso);

        $this->assertStringContainsString('SEFAZ-SP', $msg);
        $this->assertStringContainsString('ESTRUTURA REMUNERATÓRIA APROFUNDADA', $msg);
        $this->assertStringContainsString('Vencimento Básico', $msg);
        $this->assertStringContainsString('Produtividade', $msg);
    }

    public function test_web_dashboard_renders_for_authenticated_user()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get(route('fiscal.index'));

        $response->assertStatus(200);
        $response->assertSee('Radar de Concursos Fiscais');
        $response->assertSee('SEFAZ-SP');
        $response->assertSee('SEFAZ-MT');

        // Testar busca por RFB
        $responseRfb = $this->actingAs($user)->get(route('fiscal.index', ['busca' => 'RFB']));
        $responseRfb->assertStatus(200);
        $responseRfb->assertSee('RFB - Auditor');
    }

    public function test_web_show_endpoint_returns_json_details()
    {
        $user = User::first() ?? User::factory()->create();
        $concurso = FiscalConcurso::first();

        $response = $this->actingAs($user)->get(route('fiscal.show', $concurso->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'concurso' => [
                'id' => $concurso->id,
                'sigla' => $concurso->sigla,
            ]
        ]);
    }

    public function test_telegram_bot_webhook_handles_fiscal_commands()
    {
        $user = User::first() ?? User::factory()->create();

        // Mocking TelegramService para não fazer chamada externa de rede durante o teste
        $mockTelegram = $this->createMock(TelegramService::class);
        $mockTelegram->expects($this->atLeastOnce())
            ->method('sendMessage')
            ->willReturn(true);
        $this->app->instance(TelegramService::class, $mockTelegram);

        $headers = [];
        $secret = config('telegram.webhook_secret');
        if (!empty($secret)) {
            $headers['X-Telegram-Bot-Api-Secret-Token'] = $secret;
        }

        // Testar comando /sefaz sp
        $response = $this->postJson(route('webhook.telegram'), [
            'message' => [
                'chat' => ['id' => 1524296232],
                'text' => '/sefaz sp',
            ]
        ], $headers);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'processed' => 'fiscal_command']);

        // Testar comando /receita
        $responseReceita = $this->postJson(route('webhook.telegram'), [
            'message' => [
                'chat' => ['id' => 1524296232],
                'text' => '/receita',
            ]
        ], $headers);

        $responseReceita->assertStatus(200);
        $responseReceita->assertJson(['ok' => true, 'processed' => 'fiscal_command']);
    }

    public function test_crawler_and_feed_ignores_concluded_past_exams_and_prioritizes_upcoming()
    {
        $crawler = app(FiscalNewsCrawlerService::class);

        // Notícia de concurso passado (ex: gabarito definitivo/homologação de certame antigo)
        $pastNewsItem = [
            'title'       => 'Concurso SEFAZ Antigo: Divulgado o gabarito definitivo e homologação do resultado final de 2021',
            'link'        => 'https://exemplo.com/noticias/gabarito-definitivo-2021',
            'description' => 'A banca organizadora divulgou o gabarito definitivo e a homologação do resultado final das provas objetivas realizadas em 2021.',
            'pubDate'     => now(),
        ];

        $savedPast = $crawler->processAndSaveItem($pastNewsItem, 'Teste Fonte');
        $this->assertNull($savedPast, 'Notícia de concurso antigo ou gabarito passado não deve ser salva!');

        // Notícia de concurso futuro / novo edital
        $upcomingNewsItem = [
            'title'       => 'Novo Concurso SEFAZ SP: Comissão avança para novo edital de Auditor Fiscal',
            'link'        => 'https://exemplo.com/noticias/novo-edital-sefaz-sp-2026',
            'description' => 'A Secretaria da Fazenda de São Paulo prepara o novo concurso público para Auditor com 250 vagas.',
            'pubDate'     => now(),
        ];

        $savedUpcoming = $crawler->processAndSaveItem($upcomingNewsItem, 'Teste Fonte');
        $this->assertNotNull($savedUpcoming, 'Notícia de concurso futuro/novo certame deve ser aceita e cadastrada.');
    }

    public function test_fiscal_catalog_seeds_all_27_state_tax_agencies_with_vigencia_and_regions()
    {
        $estaduais = FiscalConcurso::where('esfera', 'estadual')->get();
        $this->assertCount(27, $estaduais, 'Deve conter todas as 27 Secretarias de Fazenda Estaduais (26 Estados + DF).');

        // Validar que todas as UFs brasileiras estão presentes
        $ufsEsperadas = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];

        $ufsCadastradas = $estaduais->pluck('uf')->toArray();
        foreach ($ufsEsperadas as $uf) {
            $this->assertContains($uf, $ufsCadastradas, "UF {$uf} deve estar cadastrada nos fiscos estaduais.");
        }

        // Validar vigência e regiões populadas
        foreach ($estaduais as $fisco) {
            $this->assertNotEmpty($fisco->regiao, "Fisco {$fisco->sigla} deve ter região preenchida.");
            $this->assertNotEmpty($fisco->ultimo_concurso_status_vigencia, "Fisco {$fisco->sigla} deve ter status de vigência.");
            $this->assertNotNull($fisco->status_vigencia_formatado);
            $this->assertNotNull($fisco->status_vigencia_badge_color);
            $this->assertGreaterThan(0, (float)$fisco->remuneracao_inicial_bruta);
            $this->assertGreaterThan(0, (float)$fisco->remuneracao_real_transparencia);
        }

        // Testar SEFAZ-SP (Vencido)
        $sp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();
        $this->assertEquals('vencido', $sp->ultimo_concurso_status_vigencia);
        $this->assertGreaterThanOrEqual(8, $sp->anos_sem_concurso);

        // Testar SEFAZ-MG (Vigente)
        $mg = FiscalConcurso::where('sigla', 'SEFAZ-MG')->first();
        $this->assertEquals('vigente', $mg->ultimo_concurso_status_vigencia);
    }

    public function test_web_dashboard_renders_state_tax_agencies_tab_with_filters()
    {
        $user = User::first() ?? User::factory()->create();

        // Acessar aba de estaduais
        $response = $this->actingAs($user)->get(route('fiscal.index', ['tab' => 'estaduais']));
        $response->assertStatus(200);
        $response->assertSee('Panorama dos 27 Fiscos Estaduais');
        $response->assertSee('SEFAZ-SP');
        $response->assertSee('SEFAZ-RJ');
        $response->assertSee('SEFAZ-MG');
        $response->assertSee('SEFAZ-AC');
        $response->assertSee('SEFAZ-SE');
        $response->assertSee('Vencido');
        $response->assertSee('Vigente');

        // Filtrar por Região Sul
        $responseSul = $this->actingAs($user)->get(route('fiscal.index', [
            'tab' => 'estaduais',
            'regiao_estadual' => 'Sul'
        ]));
        $responseSul->assertStatus(200);
        $responseSul->assertSee('SEFAZ-RS');
        $responseSul->assertSee('SEFAZ-PR');
        $responseSul->assertSee('SEFAZ-SC');

        // Filtrar por Vencidos
        $responseVencidos = $this->actingAs($user)->get(route('fiscal.index', [
            'tab' => 'estaduais',
            'vigencia_estadual' => 'vencidos'
        ]));
        $responseVencidos->assertStatus(200);
        $responseVencidos->assertSee('SEFAZ-SP');
    }

    public function test_user_can_manually_update_concurso_data()
    {
        $user = User::first() ?? User::factory()->create();
        $sp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();

        $payload = [
            'nome_orgao'                     => 'Secretaria da Fazenda de São Paulo (Personalizada)',
            'cargo_principal'                => 'Auditor Fiscal da Receita Estadual - Classe Especial',
            'status'                         => 'edital_publicado',
            'banca'                          => 'FGV Projetos Custom',
            'vagas_previstas'                => '350 vagas imediatas',
            'requisito_escolaridade'         => 'Nível Superior em qualquer curso reconhecido pelo MEC',
            'jornada'                        => '40h semanais',
            'regiao'                         => 'Sudeste',
            'remuneracao_inicial_bruta'      => 38500.00,
            'vencimento_basico'              => 22000.00,
            'produtividade_estimada'         => 16500.00,
            'produtividade_detalhes'         => 'Quotas fiscais trimestrais ajustadas manualmente.',
            'remuneracao_real_transparencia' => 44000.00,
            'remuneracao_teto'               => 46366.19,
            'ultimo_concurso_ano'            => 2013,
            'ultimo_concurso_status_vigencia'=> 'vencido',
            'ultimo_concurso_validade_fim'   => 'Expirado em 2018',
            'ultimo_concurso_vigencia_detalhes' => 'Concurso homologado e esgotado.',
        ];

        $response = $this->actingAs($user)->putJson(route('fiscal.update', $sp->id), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'concurso' => [
                'id' => $sp->id,
                'editado_manualmente' => true,
                'nome_orgao' => 'Secretaria da Fazenda de São Paulo (Personalizada)',
                'banca' => 'FGV Projetos Custom',
            ]
        ]);

        $sp->refresh();
        $this->assertTrue($sp->editado_manualmente);
        $this->assertEquals('Secretaria da Fazenda de São Paulo (Personalizada)', $sp->nome_orgao);
        $this->assertEquals(38500.00, (float)$sp->remuneracao_inicial_bruta);
        $this->assertEquals('FGV Projetos Custom', $sp->banca);
    }

    public function test_sync_database_catalog_preserves_manually_edited_concursos()
    {
        $sp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();
        $sp->update([
            'editado_manualmente' => true,
            'remuneracao_inicial_bruta' => 99999.00,
            'nome_orgao' => 'SEFAZ SP - Versão Protegida do Usuário',
        ]);

        // Executar sincronização do catálogo
        app(FiscalConcursoDataService::class)->syncDatabaseCatalog();

        // Verificar que o registro manual permaneceu intocado
        $sp->refresh();
        $this->assertTrue($sp->editado_manualmente);
        $this->assertEquals(99999.00, (float)$sp->remuneracao_inicial_bruta, 'O valor manual não deve ser sobrescrito pelo syncDatabaseCatalog.');
        $this->assertEquals('SEFAZ SP - Versão Protegida do Usuário', $sp->nome_orgao);
    }

    public function test_user_can_reset_concurso_to_master_catalog()
    {
        $user = User::first() ?? User::factory()->create();
        $sp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();

        // Modificar e travar como manual
        $sp->update([
            'editado_manualmente' => true,
            'remuneracao_inicial_bruta' => 99999.00,
            'nome_orgao' => 'Nome Modificado',
        ]);

        // Disparar reset
        $response = $this->actingAs($user)->postJson(route('fiscal.reset', $sp->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'concurso' => [
                'editado_manualmente' => false,
            ]
        ]);

        $sp->refresh();
        $this->assertFalse($sp->editado_manualmente);
        $this->assertNotEquals(99999.00, (float)$sp->remuneracao_inicial_bruta);
        $this->assertStringContainsString('São Paulo', $sp->nome_orgao);
    }

    public function test_ai_service_extracts_and_updates_concurso_from_text_or_url()
    {
        $aiService = app(\App\Services\FiscalNewsAiService::class);
        $sp = FiscalConcurso::where('sigla', 'SEFAZ-SP')->first();

        $noticiaTexto = "O Concurso Auditor Fiscal da Receita Estadual da Sefaz SP teve suas provas aplicadas no início de 2026 e o resultado final divulgado em junho de 2026, com a organização da Fundação Carlos Chagas (FCC). O certame ofertou 250 vagas com remuneração inicial de R$ 31.200,00.";

        $resultado = $aiService->processNewsWithAi(
            urlOrText: $noticiaTexto,
            isUrl: false,
            autoUpdateConcurso: true,
            notifyTelegram: false
        );

        $this->assertTrue($resultado['success']);
        $this->assertNotNull($resultado['concurso']);
        $this->assertEquals('SEFAZ-SP', $resultado['concurso']->sigla);

        // Verificar que o card da SEFAZ-SP foi atualizado no banco
        $sp->refresh();
        $this->assertTrue($sp->editado_manualmente);
        $this->assertEquals('Fundação Carlos Chagas (FCC)', $sp->banca);
        $this->assertContains($sp->status, ['concluido', 'em_andamento', 'edital_publicado']);
        $this->assertEquals('vigente', $sp->ultimo_concurso_status_vigencia);
    }

    public function test_ai_service_creates_fiscal_noticia_linked_to_concurso()
    {
        $aiService = app(\App\Services\FiscalNewsAiService::class);
        $texto = "A Secretaria da Fazenda de Minas Gerais SEFAZ MG homologou o concurso de Auditor Fiscal da Receita Estadual.";

        $resultado = $aiService->processNewsWithAi(
            urlOrText: $texto,
            isUrl: false,
            autoUpdateConcurso: true
        );

        $this->assertNotNull($resultado['noticia']);
        $noticia = $resultado['noticia'];
        $this->assertDatabaseHas('fiscal_noticias', [
            'id' => $noticia->id,
            'uf' => 'MG',
            'esfera' => 'estadual',
        ]);
        $this->assertEquals($resultado['concurso']?->id, $noticia->fiscal_concurso_id);
    }

    public function test_web_ai_extract_endpoint_processes_payload()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('fiscal.ai-extract-url'), [
            'raw_text' => 'Concurso SEFAZ SP: Provas aplicadas e resultado homologado em 2026 pela banca FCC.',
            'auto_update' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'concurso',
            'noticia',
            'ai_analysis',
            'message',
        ]);
        $response->assertJson([
            'success' => true,
            'concurso' => [
                'sigla' => 'SEFAZ-SP',
                'banca' => 'Fundação Carlos Chagas (FCC)',
            ]
        ]);
    }

    public function test_ai_service_parses_html_content_cleanly()
    {
        $aiService = app(\App\Services\FiscalNewsAiService::class);
        $html = "
            <html>
                <head>
                    <title>Concurso SEFAZ SP: Edital Publicado e Inscrições Abertas</title>
                    <meta property='og:title' content='Concurso SEFAZ SP 2026: Tudo sobre o Edital'>
                </head>
                <body>
                    <nav><a href='/'>Menu</a></nav>
                    <script>alert('spam');</script>
                    <h1>Edital da Sefaz SP Publicado para Auditor Fiscal</h1>
                    <p>A Fundação Carlos Chagas (FCC) é a banca organizadora do certame de 250 vagas.</p>
                    <footer>Todos os direitos reservados</footer>
                </body>
            </html>
        ";

        $parsed = $aiService->parseHtmlContent($html, 'https://www.estrategiaconcursos.com.br/blog/concurso-sefaz-sp');

        $this->assertEquals('Estratégia Concursos', $parsed['fonte']);
        $this->assertEquals('Concurso SEFAZ SP 2026: Tudo sobre o Edital', $parsed['titulo']);
        $this->assertStringContainsString('Fundação Carlos Chagas (FCC)', $parsed['texto_limpo']);
        $this->assertStringNotContainsString('alert', $parsed['texto_limpo']);
        $this->assertStringNotContainsString('Todos os direitos reservados', $parsed['texto_limpo']);
    }
}

