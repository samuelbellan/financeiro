<?php

namespace App\Services;

use App\Models\Categoria;

/**
 * Interpreta mensagens de texto do WhatsApp e extrai dados de transações.
 *
 * Formatos suportados:
 *   despesa <valor> <descrição> [categoria] [subcategoria]
 *   receita <valor> <descrição> [categoria]
 *   saldo
 *   listar
 *   ajuda
 */
class WhatsappMessageParser
{
    // Comandos especiais sem valor
    const COMANDOS = ['saldo', 'listar', 'ajuda'];

    // Mapeamento de palavras-chave → categoria (fuzzy-match rápido)
    // Personalize conforme suas categorias cadastradas
    const KEYWORD_MAP = [
        // Alimentação
        'mercado'    => 'Alimentação',
        'supermercado' => 'Alimentação',
        'padaria'    => 'Alimentação',
        'restaurante' => 'Alimentação',
        'lanche'     => 'Alimentação',
        'ifood'      => 'Alimentação',
        'delivery'   => 'Alimentação',
        'alimento'   => 'Alimentação',
        'alimentacao' => 'Alimentação',
        'alimentação' => 'Alimentação',

        // Transporte
        'gasolina'   => 'Transporte',
        'combustivel' => 'Transporte',
        'combustível' => 'Transporte',
        'uber'       => 'Transporte',
        '99'         => 'Transporte',
        'onibus'     => 'Transporte',
        'ônibus'     => 'Transporte',
        'metro'      => 'Transporte',
        'metrô'      => 'Transporte',
        'estacionamento' => 'Transporte',

        // Saúde
        'medico'     => 'Saúde',
        'médico'     => 'Saúde',
        'farmacia'   => 'Saúde',
        'farmácia'   => 'Saúde',
        'remedio'    => 'Saúde',
        'remédio'    => 'Saúde',
        'consulta'   => 'Saúde',
        'exame'      => 'Saúde',
        'dentista'   => 'Saúde',
        'hospital'   => 'Saúde',

        // Lazer
        'cinema'     => 'Lazer',
        'show'       => 'Lazer',
        'streaming'  => 'Lazer',
        'netflix'    => 'Lazer',
        'spotify'    => 'Lazer',
        'jogo'       => 'Lazer',
        'viagem'     => 'Lazer',

        // Moradia
        'aluguel'    => 'Moradia',
        'condominio' => 'Moradia',
        'condomínio' => 'Moradia',
        'agua'       => 'Moradia',
        'água'       => 'Moradia',
        'luz'        => 'Moradia',
        'energia'    => 'Moradia',
        'internet'   => 'Moradia',
        'gas'        => 'Moradia',
        'gás'        => 'Moradia',

        // Receitas
        'salario'    => 'Salário',
        'salário'    => 'Salário',
        'freelance'  => 'Freelance',
        'freelancer' => 'Freelance',
        'dividendo'  => 'Investimentos',
        'rendimento' => 'Investimentos',
        'investimento' => 'Investimentos',
    ];

    /**
     * Parseia uma mensagem crua e retorna um array com o resultado.
     *
     * @return array{
     *   tipo: 'transacao'|'comando'|'invalido',
     *   comando?: string,
     *   transacao_tipo?: 'despesa'|'receita',
     *   valor?: float,
     *   descricao?: string,
     *   categoria?: string|null,
     *   subcategoria?: string|null,
     *   erro?: string
     * }
     */
    public function parse(string $mensagem, int $userId): array
    {
        $texto = trim(mb_strtolower($mensagem));

        // --- Comando de PDF/Fatura (caixa ou cartao) ---
        if (preg_match('/^(?:fatura|pdf|exportar)\b/iu', $texto) || str_contains($texto, 'pdf') || str_contains($texto, 'fatura')) {
            $faturaDestino = 'casa';
            if (str_contains($texto, 'cartao') || str_contains($texto, 'cartão') || str_contains($texto, 'fatura')) {
                if (str_contains($texto, 'caixa')) {
                    $faturaDestino = 'casa';
                } else {
                    $faturaDestino = 'cartao';
                }
            }

            $mes = now()->month;
            $ano = now()->year;

            $meses = [
                1 => ['janeiro', 'jan'],
                2 => ['fevereiro', 'fev'],
                3 => ['março', 'marco', 'mar'],
                4 => ['abril', 'abr'],
                5 => ['maio', 'mai'],
                6 => ['junho', 'jun'],
                7 => ['julho', 'jul'],
                8 => ['agosto', 'ago'],
                9 => ['setembro', 'set'],
                10 => ['outubro', 'out'],
                11 => ['novembro', 'nov'],
                12 => ['dezembro', 'dez'],
            ];

            foreach ($meses as $num => $aliases) {
                foreach ($aliases as $alias) {
                    if (str_contains($texto, $alias)) {
                        $mes = $num;
                        break 2;
                    }
                }
            }

            if (preg_match('/\b(20\d{2})\b/', $texto, $matchesAno)) {
                $ano = (int)$matchesAno[1];
            }

            $cartaoId = null;
            if ($faturaDestino === 'cartao') {
                $cartoes = \App\Models\Cartao::where('user_id', $userId)->get();
                foreach ($cartoes as $c) {
                    if (str_contains($texto, mb_strtolower($c->nome))) {
                        $cartaoId = $c->id;
                        break;
                    }
                }
            }

            return [
                'tipo' => 'comando',
                'comando' => 'fatura_pdf',
                'fatura_destino' => $faturaDestino,
                'cartao_id' => $cartaoId,
                'mes' => $mes,
                'ano' => $ano,
            ];
        }

        // --- Comandos especiais ---
        if (in_array($texto, self::COMANDOS)) {
            return ['tipo' => 'comando', 'comando' => $texto];
        }

        // --- Transação ---
        // Regex: (despesa|receita) <valor> <resto...>
        $pattern = '/^(despesa|receita)\s+([\d]+(?:[.,][\d]{1,2})?)\s+(.+)$/iu';
        if (!preg_match($pattern, $texto, $matches)) {
            return [
                'tipo' => 'invalido',
                'erro' => "Não entendi. Use:\n*despesa 45.90 mercado alimentação*\n*receita 3200 salário*\nOu envie *ajuda*.",
            ];
        }

        $tipoTransacao = mb_strtolower($matches[1]);
        $valor         = (float) str_replace(',', '.', $matches[2]);
        $resto         = trim($matches[3]);

        if ($valor <= 0) {
            return ['tipo' => 'invalido', 'erro' => 'O valor precisa ser maior que zero.'];
        }

        // Separar descrição, categoria e subcategoria do texto restante
        [$descricao, $categoria, $subcategoria] = $this->extrairCampos($resto, $tipoTransacao, $userId);

        return [
            'tipo'           => 'transacao',
            'transacao_tipo' => $tipoTransacao,
            'valor'          => $valor,
            'descricao'      => $descricao,
            'categoria'      => $categoria,
            'subcategoria'   => $subcategoria,
        ];
    }

    /**
     * Extrai descrição, categoria e subcategoria do texto restante.
     * Lógica: tenta comparar as últimas palavras com categorias/subcategorias reais do banco.
     */
    protected function extrairCampos(string $texto, string $tipo, int $userId): array
    {
        $palavras = preg_split('/\s+/', $texto);
        $descricao    = $texto;
        $categoria    = null;
        $subcategoria = null;

        // Busca categorias reais do usuário
        $categorias = Categoria::where('user_id', $userId)
            ->with('subcategorias')
            ->get();

        // Tenta match da última ou penúltima palavra com uma subcategoria
        $totalPalavras = count($palavras);

        for ($i = $totalPalavras - 1; $i >= max(0, $totalPalavras - 3); $i--) {
            $palavra = mb_strtolower($palavras[$i]);

            // Verifica subcategorias
            foreach ($categorias as $cat) {
                foreach ($cat->subcategorias as $sub) {
                    if (mb_strtolower($sub->nome) === $palavra || $this->similar($palavra, mb_strtolower($sub->nome))) {
                        $subcategoria = $sub->nome;
                        $categoria    = $cat->nome;
                        $descricao    = trim(implode(' ', array_slice($palavras, 0, $i)));
                        if (empty($descricao)) $descricao = $texto;
                        return [$descricao, $categoria, $subcategoria];
                    }
                }
            }

            // Verifica categorias
            foreach ($categorias as $cat) {
                if (mb_strtolower($cat->nome) === $palavra || $this->similar($palavra, mb_strtolower($cat->nome))) {
                    $categoria = $cat->nome;
                    $descricao = trim(implode(' ', array_slice($palavras, 0, $i)));
                    if (empty($descricao)) $descricao = $texto;
                    return [$descricao, $categoria, $subcategoria];
                }
            }

            // Verifica mapa de palavras-chave
            if (isset(self::KEYWORD_MAP[$palavra])) {
                $nomeCategoria = self::KEYWORD_MAP[$palavra];
                // Confirma se o usuário tem essa categoria
                $catReal = $categorias->first(fn($c) => mb_strtolower($c->nome) === mb_strtolower($nomeCategoria));
                $categoria = $catReal ? $catReal->nome : $nomeCategoria;
                $descricao = trim(implode(' ', array_slice($palavras, 0, $i)));
                if (empty($descricao)) $descricao = $texto;
                return [$descricao, $categoria, $subcategoria];
            }
        }

        // Nenhum match — usa o texto inteiro como descrição
        return [$descricao, $categoria, $subcategoria];
    }

    /**
     * Verifica similaridade simples entre duas strings (para fuzzy-match).
     */
    protected function similar(string $a, string $b): bool
    {
        // Aceita se uma contiver a outra e ambas têm >= 4 chars
        if (strlen($a) < 4 || strlen($b) < 4) return false;
        return str_contains($b, $a) || str_contains($a, $b);
    }
}
