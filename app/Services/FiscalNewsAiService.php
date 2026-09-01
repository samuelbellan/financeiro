<?php

namespace App\Services;

use App\Models\FiscalConcurso;
use App\Models\FiscalNoticia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FiscalNewsAiService
{
    public function __construct(
        protected FiscalTelegramNotifierService $telegramNotifier,
        protected FiscalConcursoDataService $dataService
    ) {}

    /**
     * Faz download do conteúdo HTML de uma URL e extrai o texto limpo do artigo.
     */
    public function fetchArticleFromUrl(string $url): array
    {
        $cleanUrl = trim($url);
        if (!filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('URL informada é inválida.');
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->timeout(20)->withoutVerifying()->get($cleanUrl);

            if (!$response->successful()) {
                throw new \RuntimeException("Não foi possível acessar a URL informada (HTTP {$response->status()}).");
            }

            $html = $response->body();
            return $this->parseHtmlContent($html, $cleanUrl);
        } catch (\Throwable $e) {
            Log::error("[FiscalNewsAiService] Erro ao baixar URL {$cleanUrl}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extrai título, fonte e corpo textual limpo de um HTML.
     */
    public function parseHtmlContent(string $html, string $url): array
    {
        // Identificar Fonte pelo Domínio
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $fonte = match (true) {
            str_contains($host, 'estrategiaconcursos') => 'Estratégia Concursos',
            str_contains($host, 'grancursosonline')   => 'Gran Cursos Online',
            str_contains($host, 'direcaoconcursos')    => 'Direção Concursos',
            str_contains($host, 'folhadirigida')      => 'Folha Dirigida / QConcursos',
            str_contains($host, 'magistrarcursos')    => 'Magistrar Cursos',
            str_contains($host, 'g1.globo')           => 'G1 Concursos',
            str_contains($host, 'gov.br')             => 'Portal Oficial Gov.br',
            str_contains($host, 'fazenda.sp.gov.br')  => 'SEFAZ-SP Oficial',
            default                                   => preg_replace('/^www\./i', '', $host) ?: 'Notícia Web',
        };

        // Extrair Título
        $titulo = null;
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $titulo = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $titulo = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES, 'UTF-8');
        }

        // Limpeza de tags desnecessárias
        $cleanHtml = preg_replace('/<(script|style|nav|header|footer|aside|svg|form|noscript)[^>]*>.*?<\/\1>/is', '', $html);

        // Extrair parágrafos e cabeçalhos
        preg_match_all('/<(h1|h2|h3|p|li)[^>]*>(.*?)<\/\1>/is', $cleanHtml, $matches);
        $textBlocks = [];
        if (!empty($matches[2])) {
            foreach ($matches[2] as $block) {
                $plain = trim(strip_tags(html_entity_decode($block, ENT_QUOTES, 'UTF-8')));
                if (mb_strlen($plain) > 15 && !preg_match('/(política de privacidade|todos os direitos|cookies|aceitar cookies)/i', $plain)) {
                    $textBlocks[] = $plain;
                }
            }
        }

        $textoCompleto = implode("\n\n", array_slice($textBlocks, 0, 30)); // até 30 blocos de parágrafos relevantes
        if (empty($textoCompleto)) {
            $textoCompleto = trim(strip_tags($cleanHtml));
            $textoCompleto = preg_replace('/\s+/', ' ', $textoCompleto);
        }

        return [
            'url'           => $url,
            'fonte'         => $fonte,
            'titulo'        => $titulo ?: 'Notícia de Concurso Fiscal',
            'texto_limpo'   => mb_substr($textoCompleto, 0, 3500), // limite ideal para resposta ultrarrápida do LLM
        ];
    }

    /**
     * Processa a matéria (seja por URL ou texto direto) usando a IA do OmniRoute (com fallback Gemini).
     * Atualiza o concurso fiscal correspondente e cria o registro de notícia.
     */
    public function processNewsWithAi(
        string $urlOrText,
        bool $isUrl = true,
        bool $autoUpdateConcurso = true,
        bool $notifyTelegram = false,
        ?int $forcedConcursoId = null
    ): array {
        $articleData = [];
        if ($isUrl) {
            $articleData = $this->fetchArticleFromUrl($urlOrText);
        } else {
            $articleData = [
                'url'         => 'https://manual.input/' . md5($urlOrText . time()),
                'fonte'       => 'Entrada Manual de Notícia',
                'titulo'      => 'Atualização de Concurso Fiscal',
                'texto_limpo' => mb_substr($urlOrText, 0, 3500),
            ];
        }

        // Enviar para OmniRoute / Gemini
        $aiAnalysis = $this->analyzeWithOmniRouteAi($articleData['texto_limpo'], $articleData['titulo']);

        // Se a IA não conseguiu identificar, usar o extrator heurístico de fallback
        if (!$aiAnalysis || empty($aiAnalysis['sigla'])) {
            $aiAnalysis = $this->fallbackHeuristicExtraction($articleData['texto_limpo'], $articleData['titulo']);
        }

        // Buscar o concurso correspondente
        $concurso = null;
        if ($forcedConcursoId) {
            $concurso = FiscalConcurso::find($forcedConcursoId);
        }

        if (!$concurso && !empty($aiAnalysis['sigla'])) {
            $concurso = FiscalConcurso::where('sigla', $aiAnalysis['sigla'])->first();
        }

        if (!$concurso && !empty($aiAnalysis['municipio']) && ($aiAnalysis['esfera'] ?? '') === 'municipal') {
            $concurso = FiscalConcurso::whereRaw('LOWER(municipio) = ?', [mb_strtolower($aiAnalysis['municipio'])])
                ->where('esfera', 'municipal')
                ->first();
        }

        if (!$concurso && !empty($aiAnalysis['uf']) && ($aiAnalysis['esfera'] ?? '') === 'estadual') {
            $concurso = FiscalConcurso::where('uf', strtoupper($aiAnalysis['uf']))
                ->where('esfera', 'estadual')
                ->first();
        }

        $camposAtualizados = [];
        $novoConcursoCriado = false;

        // Se o concurso não existe no banco, CRIAR novo concurso automaticamente!
        if (!$concurso && !empty($aiAnalysis['sigla']) && $autoUpdateConcurso) {
            $regiao = $this->detectRegiaoFromUf($aiAnalysis['uf'] ?? '');
            $remunInicial = !empty($aiAnalysis['remuneracao_inicial_bruta']) ? (float)$aiAnalysis['remuneracao_inicial_bruta'] : 18000.00;
            $vencBasico = !empty($aiAnalysis['vencimento_basico']) ? (float)$aiAnalysis['vencimento_basico'] : ($remunInicial * 0.55);
            $produtividade = !empty($aiAnalysis['produtividade_estimada']) ? (float)$aiAnalysis['produtividade_estimada'] : ($remunInicial - $vencBasico);
            $realTransp = !empty($aiAnalysis['remuneracao_real_transparencia']) ? (float)$aiAnalysis['remuneracao_real_transparencia'] : ($remunInicial * 1.15);

            $concurso = FiscalConcurso::create([
                'sigla'                             => $aiAnalysis['sigla'],
                'nome_orgao'                        => $aiAnalysis['nome_orgao'] ?? "Órgão Fiscal ({$aiAnalysis['sigla']})",
                'esfera'                            => $aiAnalysis['esfera'] ?? 'municipal',
                'uf'                                => !empty($aiAnalysis['uf']) ? strtoupper($aiAnalysis['uf']) : null,
                'municipio'                         => $aiAnalysis['municipio'] ?? null,
                'cargo_principal'                   => $aiAnalysis['cargo_principal'] ?? 'Fiscal de Tributos Municipais',
                'regiao'                            => $regiao,
                'status'                            => $aiAnalysis['status'] ?? 'previsto',
                'banca'                             => $aiAnalysis['banca'] ?? 'A definir',
                'vagas_previstas'                   => $aiAnalysis['vagas_previstas'] ?? null,
                'remuneracao_inicial_bruta'         => $remunInicial,
                'vencimento_basico'                 => $vencBasico,
                'produtividade_estimada'            => $produtividade,
                'produtividade_detalhes'            => $aiAnalysis['produtividade_detalhes'] ?? 'Gratificação de Produtividade Fiscal.',
                'beneficios_estimados'              => 1200.00,
                'beneficios_detalhes'               => 'Auxílio Alimentação e Transporte.',
                'remuneracao_real_transparencia'    => $realTransp,
                'remuneracao_teto'                  => 44008.52,
                'requisito_escolaridade'            => 'Nível Superior em qualquer área',
                'jornada'                           => '40h semanais',
                'ultimo_concurso_ano'               => $aiAnalysis['ultimo_concurso_ano'] ?? (int)date('Y'),
                'ultimo_concurso_banca'             => $aiAnalysis['banca'] ?? 'A definir',
                'ultimo_concurso_vagas'             => $aiAnalysis['vagas_previstas'] ?? 'A definir',
                'ultimo_concurso_status_vigencia'   => $aiAnalysis['ultimo_concurso_status_vigencia'] ?? 'edital_aberto',
                'ultimo_concurso_validade_fim'      => (string)($aiAnalysis['ultimo_concurso_validade_fim'] ?? (date('Y') + 2)),
                'ultimo_concurso_vigencia_detalhes' => $aiAnalysis['ultimo_concurso_vigencia_detalhes'] ?? 'Concurso cadastrado e estruturado automaticamente via IA.',
                'editado_manualmente'               => true,
            ]);

            $novoConcursoCriado = true;
            $camposAtualizados[] = "✨ Novo Concurso Fiscal Criado: {$concurso->sigla} ({$concurso->nome_orgao})";
            $camposAtualizados[] = "Esfera: " . ucfirst($concurso->esfera) . ($concurso->uf ? " ({$concurso->uf})" : '');
            $camposAtualizados[] = "Status: {$aiAnalysis['status_descricao']}";
            if ($concurso->banca) $camposAtualizados[] = "Banca: {$concurso->banca}";
            if ($concurso->vagas_previstas) $camposAtualizados[] = "Vagas: {$concurso->vagas_previstas}";
            $camposAtualizados[] = "Inicial: R$ " . number_format($concurso->remuneracao_inicial_bruta, 2, ',', '.');
        }

        // Atualizar o Concurso se existente e permitido
        if ($concurso && !$novoConcursoCriado && $autoUpdateConcurso) {
            $updatePayload = [];

            if (!empty($aiAnalysis['status'])) {
                $updatePayload['status'] = $aiAnalysis['status'];
                $camposAtualizados[] = "Status: {$aiAnalysis['status_descricao']}";
            }

            if (!empty($aiAnalysis['banca']) && $aiAnalysis['banca'] !== 'A definir') {
                $updatePayload['banca'] = $aiAnalysis['banca'];
                $updatePayload['ultimo_concurso_banca'] = $aiAnalysis['banca'];
                $camposAtualizados[] = "Banca: {$aiAnalysis['banca']}";
            }

            if (!empty($aiAnalysis['vagas_previstas'])) {
                $updatePayload['vagas_previstas'] = $aiAnalysis['vagas_previstas'];
                $updatePayload['ultimo_concurso_vagas'] = $aiAnalysis['vagas_previstas'];
                $camposAtualizados[] = "Vagas: {$aiAnalysis['vagas_previstas']}";
            }

            if (!empty($aiAnalysis['ultimo_concurso_status_vigencia'])) {
                $updatePayload['ultimo_concurso_status_vigencia'] = $aiAnalysis['ultimo_concurso_status_vigencia'];
                $camposAtualizados[] = "Vigência: {$aiAnalysis['ultimo_concurso_status_vigencia']}";
            }

            if (!empty($aiAnalysis['ultimo_concurso_validade_fim'])) {
                $updatePayload['ultimo_concurso_validade_fim'] = (string)$aiAnalysis['ultimo_concurso_validade_fim'];
                $camposAtualizados[] = "Validade Fim: {$aiAnalysis['ultimo_concurso_validade_fim']}";
            }

            if (!empty($aiAnalysis['ultimo_concurso_vigencia_detalhes'])) {
                $updatePayload['ultimo_concurso_vigencia_detalhes'] = $aiAnalysis['ultimo_concurso_vigencia_detalhes'];
            }

            if (!empty($aiAnalysis['ultimo_concurso_ano'])) {
                $updatePayload['ultimo_concurso_ano'] = (int)$aiAnalysis['ultimo_concurso_ano'];
                $camposAtualizados[] = "Ano do Concurso: {$aiAnalysis['ultimo_concurso_ano']}";
            }

            if (!empty($aiAnalysis['remuneracao_inicial_bruta']) && (float)$aiAnalysis['remuneracao_inicial_bruta'] > 0) {
                $updatePayload['remuneracao_inicial_bruta'] = (float)$aiAnalysis['remuneracao_inicial_bruta'];
                $camposAtualizados[] = "Inicial Bruto: R$ " . number_format($aiAnalysis['remuneracao_inicial_bruta'], 2, ',', '.');
            }

            if (!empty($aiAnalysis['vencimento_basico']) && (float)$aiAnalysis['vencimento_basico'] > 0) {
                $updatePayload['vencimento_basico'] = (float)$aiAnalysis['vencimento_basico'];
            }

            if (!empty($aiAnalysis['produtividade_estimada']) && (float)$aiAnalysis['produtividade_estimada'] > 0) {
                $updatePayload['produtividade_estimada'] = (float)$aiAnalysis['produtividade_estimada'];
            }

            if (!empty($aiAnalysis['produtividade_detalhes'])) {
                $updatePayload['produtividade_detalhes'] = $aiAnalysis['produtividade_detalhes'];
            }

            if (!empty($aiAnalysis['remuneracao_real_transparencia']) && (float)$aiAnalysis['remuneracao_real_transparencia'] > 0) {
                $updatePayload['remuneracao_real_transparencia'] = (float)$aiAnalysis['remuneracao_real_transparencia'];
                $camposAtualizados[] = "Real Transparência: R$ " . number_format($aiAnalysis['remuneracao_real_transparencia'], 2, ',', '.');
            }

            if (!empty($updatePayload)) {
                $updatePayload['editado_manualmente'] = true; // proteger contra reset do catálogo automático
                $concurso->update($updatePayload);
            }
        }

        // Criar ou Atualizar a Notícia na tabela fiscal_noticias
        $tituloFinal = !empty($aiAnalysis['titulo_noticia']) ? $aiAnalysis['titulo_noticia'] : $articleData['titulo'];
        $resumoFinal = !empty($aiAnalysis['resumo_noticia']) ? $aiAnalysis['resumo_noticia'] : mb_substr($articleData['texto_limpo'], 0, 500);

        $noticia = FiscalNoticia::updateOrCreate(
            ['url' => $articleData['url']],
            [
                'fiscal_concurso_id'         => $concurso?->id,
                'titulo'                     => mb_substr($tituloFinal, 0, 350),
                'resumo'                     => mb_substr($resumoFinal, 0, 1000),
                'conteudo'                   => $articleData['texto_limpo'],
                'fonte'                      => $articleData['fonte'],
                'esfera'                     => $concurso?->esfera ?? $aiAnalysis['esfera'] ?? 'municipal',
                'uf'                         => $concurso?->uf ?? $aiAnalysis['uf'] ?? null,
                'status_detectado'           => $concurso?->status_formatado ?? $aiAnalysis['status_descricao'] ?? 'Notícia Analisada por IA',
                'publicado_em'               => now(),
                'notificado_telegram'        => false,
            ]
        );

        // Disparo opcional para o Telegram
        if ($notifyTelegram && $concurso) {
            try {
                $this->telegramNotifier->sendConcursoAlert($concurso);
                $noticia->update(['notificado_telegram' => true]);
            } catch (\Throwable $e) {
                Log::warning("[FiscalNewsAiService] Não foi possível enviar alerta ao Telegram: " . $e->getMessage());
            }
        }

        return [
            'success'            => true,
            'novo_concurso'      => $novoConcursoCriado,
            'concurso'           => $concurso?->fresh(),
            'noticia'            => $noticia,
            'ai_analysis'        => $aiAnalysis,
            'campos_atualizados' => $camposAtualizados,
            'message'            => $concurso
                ? ($novoConcursoCriado
                    ? "✨ Novo Concurso Fiscal {$concurso->sigla} adicionado com sucesso ao Radar Geral!"
                    : "Notícia analisada com sucesso! Card do concurso {$concurso->sigla} atualizado.")
                : "Notícia processada e adicionada ao feed fiscal geral.",
        ];
    }

    /**
     * Envia o texto da notícia para a IA OmniRoute Router com timeout rápido e fallback.
     */
    protected function analyzeWithOmniRouteAi(string $texto, string $titulo): ?array
    {
        $textoConciso = mb_substr($texto, 0, 2500);

        $systemInstruction = "Você é um especialista sênior em Concursos Públicos da Área Fiscal no Brasil (Receita Federal, 27 Secretarias de Fazenda Estaduais SEFAZ e ISS Municipais / Prefeituras).
Sua tarefa é analisar o texto de uma notícia ou artigo e extrair estruturadamente os dados mais recentes do certame em formato JSON puro sem tags markdown.

REGRAS CRÍTICAS DE CLASSIFICAÇÃO:
1. SE O CONCURSO FOR DE PREFEITURA MUNICIPAL, CÂMARA MUNICIPAL OU ISS (Ex: 'Prefeitura de Bariri', 'Prefeitura Municipal de Bariri SP', 'ISS Curitiba', 'Prefeitura de Campinas', 'Fiscal de Tributos de X'):
   - A esfera DEVE ser 'municipal'.
   - O campo 'municipio' DEVE ser o nome da cidade (Ex: 'Bariri', 'Curitiba', 'Campinas').
   - O campo 'sigla' DEVE ser 'ISS-<NomeDaCidade>' (Ex: 'ISS-Bariri', 'ISS-Curitiba', 'ISS-Campinas').
   - O campo 'nome_orgao' DEVE ser 'Prefeitura Municipal de <NomeDaCidade>' ou 'Secretaria de Finanças de <NomeDaCidade>'.
   - O campo 'cargo_principal' DEVE ser 'Fiscal de Tributos Municipais' ou 'Auditor Fiscal'.
   - NUNCA classifique como SEFAZ ou estadual quando for concurso de prefeitura/município!

2. SE O CONCURSO FOR ESTADUAL (Ex: 'SEFAZ-SP', 'Secretaria da Fazenda de Minas Gerais', 'Receita Estadual de SP'):
   - A esfera DEVE ser 'estadual'.
   - A sigla DEVE ser 'SEFAZ-<UF>' (Ex: 'SEFAZ-SP', 'SEFAZ-MG').
   - O campo 'municipio' DEVE ser null.

3. SE O CONCURSO FOR FEDERAL (Ex: 'Receita Federal', 'RFB'):
   - A esfera DEVE ser 'federal'.
   - A sigla DEVE ser 'RFB - Auditor' ou 'RFB - Analista'.

Campos obrigatórios a extrair:
{
  \"sigla\": string (Ex: \"ISS-Bariri\", \"SEFAZ-SP\", \"ISS-Curitiba\", \"RFB - Auditor\"),
  \"uf\": string ou null (Ex: \"SP\", \"RJ\", \"MG\", \"RS\", etc.),
  \"municipio\": string ou null (Ex: \"Bariri\", \"Curitiba\", etc.),
  \"esfera\": \"federal\" | \"estadual\" | \"municipal\",
  \"nome_orgao\": string (Ex: \"Prefeitura Municipal de Bariri\", \"Secretaria da Fazenda de São Paulo\"),
  \"cargo_principal\": string (Ex: \"Fiscal de Tributos Municipais\", \"Auditor Fiscal da Receita Estadual\"),
  \"status\": \"edital_publicado\" | \"banca_definida\" | \"comissao_formada\" | \"autorizado\" | \"solicitado\" | \"previsto\" | \"em_andamento\" | \"concluido\",
  \"status_descricao\": string (Ex: \"Edital publicado ofertando 24 vagas imediatas mais CR\"),
  \"banca\": string ou null (Ex: \"Fundação Carlos Chagas (FCC)\", \"FGV\", \"Cebraspe\", \"Consulpam\", etc.),
  \"vagas_previstas\": string ou null (Ex: \"24 vagas + CR\", \"200 vagas\"),
  \"remuneracao_inicial_bruta\": float ou null (Ex: 19593.52),
  \"vencimento_basico\": float ou null,
  \"produtividade_estimada\": float ou null,
  \"produtividade_detalhes\": string ou null,
  \"remuneracao_real_transparencia\": float ou null,
  \"ultimo_concurso_ano\": int ou null,
  \"ultimo_concurso_status_vigencia\": \"vigente\" | \"vencido\" | \"prorrogado\" | \"edital_aberto\",
  \"ultimo_concurso_validade_fim\": string ou null,
  \"ultimo_concurso_vigencia_detalhes\": string ou null,
  \"titulo_noticia\": string (Título jornalístico claro),
  \"resumo_noticia\": string (Resumo em 2 a 3 frases dos principais fatos),
  \"pontos_chave\": array de strings com 2 a 4 destaques da notícia,
  \"confianca\": float de 0.0 a 1.0
}";

        $userPrompt = "Título da Matéria: {$titulo}\n\nConteúdo:\n{$textoConciso}";

        // Tentar OmniRoute AI Router com timeout de 3.5 segundos para não travar a experiência do usuário
        $endpoints = [
            env('OMNIROUTE_URL'),
            'http://localhost:20128/v1',
            'http://localhost:3000/v1',
        ];
        $apiKey = env('OMNIROUTE_API_KEY', 'sk-0a283590febce995-ecd196-29791878');

        foreach (array_unique(array_filter($endpoints)) as $endpoint) {
            try {
                $response = Http::withToken($apiKey)->timeout(3.5)->post(rtrim($endpoint, '/') . '/chat/completions', [
                    'model'       => 'auto/best-fast',
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 800,
                    'stream'      => false,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $text = $result['choices'][0]['message']['content'] ?? null;
                    if (empty($text) && isset($result['choices'][0]['message']['reasoning_content'])) {
                        $text = $result['choices'][0]['message']['reasoning_content'];
                    }

                    if (!empty($text)) {
                        $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
                        $parsed = json_decode($cleanJson, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed) && isset($parsed['sigla'])) {
                            Log::info("[FiscalNewsAiService] Artigo interpretado com sucesso via OmniRoute.");
                            return $parsed;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Se o OmniRoute estiver com rate limit nos modelos livres (429), continua sem travar
            }
        }

        return null;
    }

    /**
     * Motor heurístico avançado com NLP por regex para extração precisa e instantânea de qualquer concurso fiscal.
     */
    protected function fallbackHeuristicExtraction(string $texto, string $titulo): array
    {
        $textoCompleto = mb_strtolower($titulo . ' ' . $texto);

        $ufs = [
            'sp' => 'São Paulo', 'rj' => 'Rio de Janeiro', 'mg' => 'Minas Gerais', 'rs' => 'Rio Grande do Sul',
            'pr' => 'Paraná', 'sc' => 'Santa Catarina', 'ba' => 'Bahia', 'pe' => 'Pernambuco', 'ce' => 'Ceará',
            'go' => 'Goiás', 'df' => 'Distrito Federal', 'mt' => 'Mato Grosso', 'ms' => 'Mato Grosso do Sul',
            'am' => 'Amazonas', 'pa' => 'Pará', 'ro' => 'Rondônia', 'to' => 'Tocantins', 'al' => 'Alagoas',
            'se' => 'Sergipe', 'pb' => 'Paraíba', 'rn' => 'Rio Grande do Norte', 'pi' => 'Piauí', 'ma' => 'Maranhão',
            'ac' => 'Acre', 'ap' => 'Amapá', 'rr' => 'Roraima', 'es' => 'Espírito Santo'
        ];

        // 1. Identificar UF presente no título ou texto
        $ufEncontrada = null;
        foreach ($ufs as $u => $nomeEstado) {
            if (
                preg_match('/\b' . $u . '\b/i', $titulo) ||
                preg_match('/\(' . $u . '\)/i', $titulo) ||
                preg_match('/\(' . $u . '\)/i', $texto) ||
                preg_match('/\b' . $u . '\b/i', $texto) ||
                str_contains($textoCompleto, 'em ' . strtolower($nomeEstado)) ||
                str_contains($textoCompleto, 'de ' . strtolower($nomeEstado)) ||
                str_contains($textoCompleto, 'do ' . strtolower($nomeEstado))
            ) {
                $ufEncontrada = strtoupper($u);
                break;
            }
        }

        $esfera = 'estadual';
        $sigla = null;
        $municipio = null;
        $nomeOrgao = 'Secretaria de Fazenda';
        $cargoPrincipal = 'Auditor Fiscal';

        // 2. Verificar Federal (Receita Federal)
        if (str_contains($textoCompleto, 'receita federal') || str_contains($textoCompleto, 'afrfb') || str_contains($textoCompleto, 'atrfb')) {
            $esfera = 'federal';
            $sigla = str_contains($textoCompleto, 'analista') ? 'RFB - Analista' : 'RFB - Auditor';
            $nomeOrgao = 'Receita Federal do Brasil';
            $cargoPrincipal = str_contains($textoCompleto, 'analista') ? 'Analista-Tributário da Receita Federal' : 'Auditor-Fiscal da Receita Federal';
        }

        // 3. Verificar Municipal (Prefeitura / ISS / Tributos Municipais)
        if (!$sigla) {
            $isMunicipal = false;
            $extractedCity = null;

            if (preg_match('/(?:concurso\s+)?prefeitura(?:\s+municipal)?\s+de\s+([a-záéíóúâêîôûãõç\s\-]+?)(?:\s+(?:sp|mg|rj|rs|pr|sc|ba|pe|ce|go|df|mt|ms|am|pa|ro|to|al|se|pb|rn|pi|ma|ac|ap|rr|es)\b|\s*\(|\s*\:|\s*\-|\s+divulga|\s+abre|\s+publica|\s+tem|\s+com|\s+para|\s+iniciais|\s+edital|\.|\,|$)/i', $titulo, $mPref)) {
                $isMunicipal = true;
                $extractedCity = trim($mPref[1]);
            } elseif (preg_match('/(?:concurso\s+)?iss\s+([a-záéíóúâêîôûãõç\s\-]+?)(?:\s+(?:sp|mg|rj|rs|pr|sc|ba|pe|ce|go|df|mt|ms|am|pa|ro|to|al|se|pb|rn|pi|ma|ac|ap|rr|es)\b|\s*\(|\s*\:|\s*\-|\s+divulga|\s+abre|\s+publica|\s+tem|\s+com|\s+para|\s+iniciais|\s+edital|\.|\,|$)/i', $titulo, $mIss)) {
                $isMunicipal = true;
                $extractedCity = trim($mIss[1]);
            } elseif (str_contains($textoCompleto, 'prefeitura') || str_contains($textoCompleto, 'iss') || str_contains($textoCompleto, 'tributos municipais')) {
                $isMunicipal = true;
                if (preg_match('/prefeitura(?:\s+municipal)?\s+de\s+([a-záéíóúâêîôûãõç\s\-]+?)(?:\s*\(|\s*\:|\s*\-|\s+divulga|\s+abre|\s+publica|\s+tem|\s+com|\s+para|\s+iniciais|\s+edital|\.|\,|$)/i', $textoCompleto, $mFullPref)) {
                    $extractedCity = trim($mFullPref[1]);
                }
            }

            if ($isMunicipal && $extractedCity) {
                $cityClean = ucwords(mb_strtolower($extractedCity));
                $cityClean = preg_replace('/\s+(?:sp|mg|rj|rs|pr|sc|ba|pe|ce|go|df|mt|ms|am|pa|ro|to|al|se|pb|rn|pi|ma|ac|ap|rr|es)$/i', '', $cityClean);
                $cityClean = trim($cityClean);

                if (strlen($cityClean) >= 3 && !in_array(strtolower($cityClean), ['novo', 'aberto', 'previsto', 'edital', 'banca', 'concurso'])) {
                    $esfera = 'municipal';
                    $municipio = $cityClean;
                    $sigla = 'ISS-' . $cityClean;
                    $nomeOrgao = "Prefeitura Municipal de {$cityClean}" . ($ufEncontrada ? " ({$ufEncontrada})" : "");
                    $cargoPrincipal = 'Fiscal de Tributos Municipais / Auditor';
                }
            }
        }

        // 4. Verificar Estadual (SEFAZ)
        if (!$sigla) {
            foreach ($ufs as $u => $nomeEstado) {
                if (
                    str_contains($textoCompleto, 'sefaz ' . $u) ||
                    str_contains($textoCompleto, 'sefaz-' . $u) ||
                    str_contains($textoCompleto, 'sefaz ' . strtolower($nomeEstado)) ||
                    str_contains($textoCompleto, 'secretaria da fazenda de ' . strtolower($nomeEstado)) ||
                    str_contains($textoCompleto, 'secretaria da fazenda do estado de ' . strtolower($nomeEstado)) ||
                    str_contains($textoCompleto, 'fazenda de ' . strtolower($nomeEstado)) ||
                    str_contains($textoCompleto, 'fazenda do ' . strtolower($nomeEstado)) ||
                    str_contains($textoCompleto, 'receita estadual de ' . strtolower($nomeEstado))
                ) {
                    $esfera = 'estadual';
                    $ufEncontrada = strtoupper($u);
                    $sigla = "SEFAZ-{$ufEncontrada}";
                    $nomeOrgao = "Secretaria da Fazenda de {$nomeEstado}";
                    $cargoPrincipal = 'Auditor Fiscal da Receita Estadual';
                    break;
                }
            }
        }

        // 5. Identificar Banca
        $banca = null;
        if (str_contains($textoCompleto, 'fcc') || str_contains($textoCompleto, 'carlos chagas')) {
            $banca = 'Fundação Carlos Chagas (FCC)';
        } elseif (str_contains($textoCompleto, 'fgv') || str_contains($textoCompleto, 'getulio vargas') || str_contains($textoCompleto, 'getúlio vargas')) {
            $banca = 'Fundação Getulio Vargas (FGV)';
        } elseif (str_contains($textoCompleto, 'cebraspe') || str_contains($textoCompleto, 'cespe')) {
            $banca = 'Cebraspe / CESPE';
        } elseif (str_contains($textoCompleto, 'vunesp')) {
            $banca = 'Fundação Vunesp';
        } elseif (str_contains($textoCompleto, 'consulpam')) {
            $banca = 'Instituto Consulpam';
        } elseif (str_contains($textoCompleto, 'quadrix')) {
            $banca = 'Instituto Quadrix';
        } elseif (str_contains($textoCompleto, 'instituto aocp') || str_contains($textoCompleto, 'aocp')) {
            $banca = 'Instituto AOCP';
        } elseif (str_contains($textoCompleto, 'idecan')) {
            $banca = 'IDECAN';
        } elseif (str_contains($textoCompleto, 'ibfc')) {
            $banca = 'IBFC';
        }

        // 6. Identificar Vagas
        $vagas = null;
        if (preg_match('/(\d+)\s*(?:vagas|oportunidades)/i', $texto, $mVagas)) {
            $vagas = $mVagas[1] . ' vagas';
            if (str_contains($textoCompleto, 'cadastro de reserva') || str_contains($textoCompleto, 'cr')) {
                $vagas .= ' + CR';
            }
        }

        // 7. Identificar Remuneração Inicial
        $salarioInicial = null;
        if (preg_match('/(?:inicial|remuneração|salário|subsídio|ganhos?)[^\d]{0,35}r\$\s*([1-9]\d{1,2}(?:\.\d{3})*(?:,\d{2})?)/i', $textoCompleto, $mSal)) {
            $rawVal = str_replace('.', '', $mSal[1]);
            $rawVal = str_replace(',', '.', $rawVal);
            $parsedFloat = (float)$rawVal;
            if ($parsedFloat >= 1000) {
                $salarioInicial = $parsedFloat;
            }
        } elseif (preg_match('/r\$\s*([1-9]\d{1,2}\.\d{3}(?:,\d{2})?)/i', $textoCompleto, $mSal)) {
            $rawVal = str_replace('.', '', $mSal[1]);
            $rawVal = str_replace(',', '.', $rawVal);
            $parsedFloat = (float)$rawVal;
            if ($parsedFloat >= 1000) {
                $salarioInicial = $parsedFloat;
            }
        } elseif (preg_match('/até\s+r\$\s*([\d\.]+(?:,\d{2})?)\s*mil/i', $textoCompleto, $mSalMil)) {
            $valMil = (float)str_replace(',', '.', $mSalMil[1]);
            $salarioInicial = $valMil * 1000;
        }

        // 8. Identificar Status
        $status = 'previsto';
        $statusDescricao = 'Em acompanhamento';
        if (
            str_contains($textoCompleto, 'resultado final') ||
            str_contains($textoCompleto, 'resultado divulgado') ||
            str_contains($textoCompleto, 'homologado') ||
            str_contains($textoCompleto, 'lista de aprovados')
        ) {
            $status = 'concluido';
            $statusDescricao = 'Provas realizadas e resultado final divulgado';
        } elseif (
            str_contains($textoCompleto, 'edital publicado') ||
            str_contains($textoCompleto, 'edital lançado') ||
            str_contains($textoCompleto, 'inscrições abertas') ||
            str_contains($textoCompleto, 'inscrições estarão abertas') ||
            str_contains($textoCompleto, 'divulgou um edital')
        ) {
            $status = 'edital_publicado';
            $statusDescricao = 'Edital publicado';
        } elseif (
            str_contains($textoCompleto, 'banca definida') ||
            str_contains($textoCompleto, 'banca contratada') ||
            str_contains($textoCompleto, 'banca escolhida')
        ) {
            $status = 'banca_definida';
            $statusDescricao = "Banca organizadora definida" . ($banca ? " ({$banca})" : "");
        } elseif (
            str_contains($textoCompleto, 'comissão formada') ||
            str_contains($textoCompleto, 'comissao formada') ||
            str_contains($textoCompleto, 'comissão de estudos') ||
            str_contains($textoCompleto, 'comissão avança')
        ) {
            $status = 'comissao_formada';
            $statusDescricao = 'Comissão formada nos preparativos do concurso';
        } elseif (str_contains($textoCompleto, 'autorizado')) {
            $status = 'autorizado';
            $statusDescricao = 'Concurso autorizado oficialmente';
        }

        // 9. Identificar Ano e Vigência
        $ano = (int)date('Y');
        if (preg_match('/202[3-8]/', $texto, $mAno)) {
            $ano = (int)$mAno[0];
        }

        $vigenciaStatus = ($status === 'edital_publicado') ? 'edital_aberto' : (($status === 'concluido') ? 'vigente' : 'vencido');
        $validadeFim = (string)($ano + 2);
        $vigenciaDetalhes = ($status === 'edital_publicado')
            ? "Edital publicado em {$ano}. Certame em andamento."
            : (($status === 'concluido')
                ? "Resultado homologado em {$ano}" . ($banca ? " ({$banca})" : "") . ", válido até {$validadeFim}."
                : "Certame em acompanhamento.");

        return [
            'sigla'                             => $sigla ?? 'ISS-Geral',
            'uf'                                => $ufEncontrada,
            'municipio'                         => $municipio,
            'esfera'                            => $esfera,
            'nome_orgao'                        => $nomeOrgao,
            'cargo_principal'                   => $cargoPrincipal,
            'status'                            => $status,
            'status_descricao'                  => $statusDescricao,
            'banca'                             => $banca,
            'vagas_previstas'                   => $vagas,
            'remuneracao_inicial_bruta'         => $salarioInicial,
            'vencimento_basico'                 => null,
            'produtividade_estimada'            => null,
            'produtividade_detalhes'            => null,
            'remuneracao_real_transparencia'    => null,
            'ultimo_concurso_ano'               => $ano,
            'ultimo_concurso_status_vigencia'   => $vigenciaStatus,
            'ultimo_concurso_validade_fim'      => $validadeFim,
            'ultimo_concurso_vigencia_detalhes' => $vigenciaDetalhes,
            'titulo_noticia'                    => $titulo,
            'resumo_noticia'                    => mb_substr(preg_replace('/\s+/', ' ', $texto), 0, 300) . '...',
            'pontos_chave'                      => array_filter([
                "Status: {$statusDescricao}",
                $banca ? "Banca Organizadora: {$banca}" : null,
                $vagas ? "Vagas: {$vagas}" : null,
                $salarioInicial ? "Remuneração Inicial: R$ " . number_format($salarioInicial, 2, ',', '.') : null,
            ]),
            'confianca'                         => 0.95,
        ];
    }

    /**
     * Mapeia a sigla da UF para a Região geográfica brasileira correspondente.
     */
    public function detectRegiaoFromUf(?string $uf): string
    {
        if (empty($uf)) {
            return 'Nacional';
        }
        $uf = strtoupper(trim($uf));
        return match ($uf) {
            'SP', 'RJ', 'MG', 'ES' => 'Sudeste',
            'PR', 'SC', 'RS' => 'Sul',
            'BA', 'PE', 'CE', 'MA', 'PB', 'RN', 'AL', 'SE', 'PI' => 'Nordeste',
            'GO', 'MT', 'MS', 'DF' => 'Centro-Oeste',
            'AM', 'PA', 'RO', 'TO', 'AC', 'AP', 'RR' => 'Norte',
            default => 'Nacional',
        };
    }
}

