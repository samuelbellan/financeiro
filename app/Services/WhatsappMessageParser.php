<?php

namespace App\Services;

use App\Models\Cartao;
use App\Models\Categoria;

/**
 * Interpreta mensagens de texto do Telegram/WhatsApp e extrai dados estruturados de transações e comandos,
 * servindo como fallback resiliente caso a IA demore ou esteja indisponível.
 */
class WhatsappMessageParser
{
    // Comandos especiais sem valor
    const COMANDOS = ['saldo', 'listar', 'ajuda'];

    // Mapeamento de palavras-chave → categoria (fuzzy-match rápido)
    const KEYWORD_MAP = [
        // Alimentação
        'mercado'       => 'Alimentação',
        'supermercado'  => 'Alimentação',
        'padaria'       => 'Alimentação',
        'restaurante'   => 'Alimentação',
        'lanche'        => 'Alimentação',
        'almoço'        => 'Alimentação',
        'almoco'        => 'Alimentação',
        'jantar'        => 'Alimentação',
        'ifood'         => 'Alimentação',
        'delivery'      => 'Alimentação',
        'fort'          => 'Alimentação',
        'comida'        => 'Alimentação',
        'alimento'      => 'Alimentação',
        'alimentacao'   => 'Alimentação',
        'alimentação'   => 'Alimentação',
        'café'          => 'Alimentação',
        'cafe'          => 'Alimentação',
        'pizza'         => 'Alimentação',
        'açougue'       => 'Alimentação',
        'acougue'       => 'Alimentação',

        // Transporte
        'gasolina'      => 'Transporte',
        'combustivel'   => 'Transporte',
        'combustível'   => 'Transporte',
        'uber'          => 'Transporte',
        '99'            => 'Transporte',
        'onibus'        => 'Transporte',
        'ônibus'        => 'Transporte',
        'metro'         => 'Transporte',
        'metrô'         => 'Transporte',
        'estacionamento'=> 'Transporte',
        'estacionar'    => 'Transporte',
        'pedagio'       => 'Transporte',
        'pedágio'       => 'Transporte',
        'mecanico'      => 'Transporte',
        'oficina'       => 'Transporte',
        'ipva'          => 'Transporte',

        // Saúde
        'medico'        => 'Saúde',
        'médico'        => 'Saúde',
        'farmacia'      => 'Saúde',
        'farmácia'      => 'Saúde',
        'remedio'       => 'Saúde',
        'remédio'       => 'Saúde',
        'consulta'      => 'Saúde',
        'exame'         => 'Saúde',
        'dentista'      => 'Saúde',
        'hospital'      => 'Saúde',

        // Lazer
        'cinema'        => 'Lazer',
        'show'          => 'Lazer',
        'streaming'     => 'Lazer',
        'netflix'       => 'Lazer',
        'spotify'       => 'Lazer',
        'jogo'          => 'Lazer',
        'viagem'        => 'Lazer',

        // Moradia
        'aluguel'       => 'Moradia',
        'condominio'    => 'Moradia',
        'condomínio'    => 'Moradia',
        'agua'          => 'Moradia',
        'água'          => 'Moradia',
        'luz'           => 'Moradia',
        'energia'       => 'Moradia',
        'internet'      => 'Moradia',
        'gas'           => 'Moradia',
        'gás'           => 'Moradia',
        'iptu'          => 'Moradia',

        // Despesas Pessoais
        'roupa'         => 'Despesas pessoais',
        'calcado'       => 'Despesas pessoais',
        'calçado'       => 'Despesas pessoais',
        'tenis'         => 'Despesas pessoais',
        'tênis'         => 'Despesas pessoais',
        'cabelo'        => 'Despesas pessoais',
        'barba'         => 'Despesas pessoais',
        'salao'         => 'Despesas pessoais',
        'salão'         => 'Despesas pessoais',

        // Receitas
        'salario'       => 'Salário',
        'salário'       => 'Salário',
        'freelance'     => 'Entradas eventuais',
        'freelancer'    => 'Entradas eventuais',
        'dividendo'     => 'Entradas eventuais',
        'rendimento'    => 'Entradas eventuais',
        'investimento'  => 'Entradas eventuais',
        'pix'           => 'Salário',
    ];

    /**
     * Parseia uma mensagem crua e retorna um array com o resultado estruturado.
     */
    public function parse(string $mensagem, int $userId): array
    {
        $rawText = trim($mensagem);
        $texto = trim(mb_strtolower($mensagem));

        // 1. Comandos especiais diretos
        if (in_array($texto, self::COMANDOS)) {
            return ['tipo' => 'comando', 'comando' => $texto];
        }

        // Variações de Saldo (ex: "saldo conta corrente", "saldo da conta", "ver saldo", "meu saldo")
        if (preg_match('/^(?:saldo|ver saldo|consultar saldo|meu saldo)\b/iu', $texto) || in_array($texto, ['saldo conta corrente', 'saldo da conta', 'saldo conta', 'saldo caixa'])) {
            $saldoDestino = 'casa';
            $cartaoId = null;
            $cartoes = Cartao::where('user_id', $userId)->get();
            foreach ($cartoes as $c) {
                if (str_contains($texto, mb_strtolower($c->nome)) || ($c->bandeira && str_contains($texto, mb_strtolower($c->bandeira)))) {
                    $saldoDestino = 'cartao';
                    $cartaoId = $c->id;
                    break;
                }
            }
            return [
                'tipo' => 'comando',
                'comando' => 'saldo',
                'saldo_destino' => $saldoDestino,
                'cartao_id' => $cartaoId,
                'mes' => now()->month,
                'ano' => now()->year,
            ];
        }

        // Variações de Listar / Extrato
        if (preg_match('/^(?:listar|ultimos lancamentos|últimos lançamentos|ultimas transacoes|últimas transações|extrato)\b/iu', $texto)) {
            return ['tipo' => 'comando', 'comando' => 'listar'];
        }

        // Variações de Ajuda
        if (preg_match('/^(?:ajuda|help|como usar|\/help|\/ajuda)\b/iu', $texto)) {
            return ['tipo' => 'comando', 'comando' => 'ajuda'];
        }

        // 2. Fatura / PDF
        if (preg_match('/^(?:fatura|pdf|exportar|extrato)\b/iu', $texto) || str_contains($texto, 'fatura') || str_contains($texto, 'pdf')) {
            $faturaDestino = 'casa';
            if (str_contains($texto, 'cartao') || str_contains($texto, 'cartão') || str_contains($texto, 'fatura')) {
                $faturaDestino = str_contains($texto, 'caixa') ? 'casa' : 'cartao';
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
                $cartoes = Cartao::where('user_id', $userId)->get();
                foreach ($cartoes as $c) {
                    if (str_contains($texto, mb_strtolower($c->nome)) || ($c->bandeira && str_contains($texto, mb_strtolower($c->bandeira)))) {
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

        // 3. Detectar Cartão de Crédito
        $cartaoId = null;
        $cartaoEncontrado = null;
        $cartoes = Cartao::where('user_id', $userId)->get();
        $isCartao = false;

        foreach ($cartoes as $c) {
            $nomeCard = mb_strtolower($c->nome);
            $bandeiraCard = $c->bandeira ? mb_strtolower($c->bandeira) : '';

            if (
                str_contains($texto, $nomeCard) ||
                (!empty($bandeiraCard) && str_contains($texto, $bandeiraCard)) ||
                (str_contains($nomeCard, 'visa') && str_contains($texto, 'visa')) ||
                (str_contains($nomeCard, 'vuon') && str_contains($texto, 'vuon')) ||
                (str_contains($nomeCard, 'inter') && str_contains($texto, 'inter')) ||
                (str_contains($nomeCard, 'master') && str_contains($texto, 'master'))
            ) {
                $cartaoId = $c->id;
                $cartaoEncontrado = $c;
                $isCartao = true;
                break;
            }
        }

        if (!$isCartao && (str_contains($texto, 'no cartao') || str_contains($texto, 'no cartão') || str_contains($texto, 'no credito') || str_contains($texto, 'no crédito') || str_contains($texto, 'cartão') || str_contains($texto, 'cartao'))) {
            $isCartao = true;
            $ativo = $cartoes->where('ativo', true)->first() ?? $cartoes->first();
            if ($ativo) {
                $cartaoId = $ativo->id;
                $cartaoEncontrado = $ativo;
            }
        }

        // 4. Detectar Parcelas e Tipo de Compra
        $tipoCompra = 'avista';
        $numeroParcelas = 1;

        if (preg_match('/\b(\d{1,2})\s*x\b/i', $texto, $matchParcela)) {
            $tipoCompra = 'parcelada';
            $numeroParcelas = max(1, (int)$matchParcela[1]);
        } elseif (preg_match('/parcelad[oa]\s+(?:em\s+)?(\d{1,2})/i', $texto, $matchParcela)) {
            $tipoCompra = 'parcelada';
            $numeroParcelas = max(1, (int)$matchParcela[1]);
        } elseif (preg_match('/(?:assinatura|mensalidade|recorrente)/i', $texto)) {
            $tipoCompra = 'recorrente';
        }

        // 5. Detectar Tipo de Transação (Receita vs Despesa)
        $tipoTransacao = 'despesa';
        if (preg_match('/^(?:receita|ganhei|recebi|salario|salário|pix\s+recebido|deposito|depósito)\b/iu', $texto) || str_contains($texto, 'receita') || str_contains($texto, 'salário') || str_contains($texto, 'salario')) {
            if (!$isCartao) {
                $tipoTransacao = 'receita';
            }
        }

        // 6. Extrair Valor Monetário
        if (!preg_match('/(?:r\$\s*)?(\d{1,6}(?:[.,]\d{1,2})?)/i', $texto, $matchValor)) {
            return [
                'tipo' => 'invalido',
                'erro' => "Não entendi o comando ou valor.\nExemplos de uso:\n• *Compra no visa 23.11 fort alimentação*\n• *despesa 45.90 mercado alimentação*\n• *receita 3200 salário*\n• *saldo* ou *ajuda*",
            ];
        }

        $valorStr = str_replace(',', '.', $matchValor[1]);
        $valor = (float)$valorStr;

        if ($valor <= 0) {
            return ['tipo' => 'invalido', 'erro' => 'O valor precisa ser maior que zero.'];
        }

        // 7. Limpar texto para extrair descrição e categoria
        $textoLimpo = $texto;

        // Remove o valor encontrado
        $textoLimpo = preg_replace('/(?:r\$\s*)?' . preg_quote($matchValor[1], '/') . '/i', ' ', $textoLimpo, 1);

        // Remove parcelas
        if ($numeroParcelas > 1) {
            $textoLimpo = preg_replace('/\b\d{1,2}\s*x\b/i', ' ', $textoLimpo);
            $textoLimpo = preg_replace('/parcelad[oa]\s+(?:em\s+)?\d{1,2}/i', ' ', $textoLimpo);
        }

        // Remove menções ao cartão
        if ($cartaoEncontrado) {
            $textoLimpo = str_ireplace([
                'no ' . mb_strtolower($cartaoEncontrado->nome),
                mb_strtolower($cartaoEncontrado->nome),
                'no ' . mb_strtolower($cartaoEncontrado->bandeira ?? ''),
                mb_strtolower($cartaoEncontrado->bandeira ?? ''),
                'no cartao', 'no cartão', 'no credito', 'no crédito', 'cartao', 'cartão'
            ], ' ', $textoLimpo);
        } else {
            $textoLimpo = str_ireplace(['no cartao', 'no cartão', 'no credito', 'no crédito', 'cartao', 'cartão', 'visa', 'vuon', 'inter', 'mastercard', 'master'], ' ', $textoLimpo);
        }

        // Remove palavras de comando iniciais/conectivos
        $palavrasIgnorar = [
            'compra', 'compras', 'comprei', 'gastei', 'gasto', 'despesa', 'despesas', 'paguei', 'pagamento',
            'lance', 'lancar', 'lançar', 'receita', 'recebi', 'ganhei', 'salario', 'salário',
            'no', 'na', 'nos', 'nas', 'de', 'do', 'da', 'dos', 'das', 'em', 'com', 'para', 'pra', 'por', 'r$'
        ];

        $palavras = preg_split('/\s+/', trim($textoLimpo));
        $palavrasFiltradas = [];
        foreach ($palavras as $p) {
            $pLimpa = trim($p, " ,.-_");
            if (!empty($pLimpa) && !in_array($pLimpa, $palavrasIgnorar)) {
                $palavrasFiltradas[] = $pLimpa;
            }
        }

        // 8. Identificar Categoria e Subcategoria
        $categorias = Categoria::where('user_id', $userId)->with('subcategorias')->get();
        $categoriaIdentificada = null;
        $subcategoriaIdentificada = null;
        $palavrasDescricao = [];

        foreach ($palavrasFiltradas as $p) {
            $pLower = mb_strtolower($p);
            $matched = false;

            // Checa subcategorias do banco
            foreach ($categorias as $cat) {
                foreach ($cat->subcategorias as $sub) {
                    if (mb_strtolower($sub->nome) === $pLower || str_contains(mb_strtolower($sub->nome), $pLower)) {
                        $subcategoriaIdentificada = $sub->nome;
                        $categoriaIdentificada = $cat->nome;
                        $matched = true;
                        break 2;
                    }
                }
            }

            // Checa categorias do banco
            if (!$matched) {
                foreach ($categorias as $cat) {
                    if (mb_strtolower($cat->nome) === $pLower || str_contains(mb_strtolower($cat->nome), $pLower)) {
                        $categoriaIdentificada = $cat->nome;
                        $matched = true;
                        break;
                    }
                }
            }

            // Checa mapa de palavras-chave
            if (!$matched && isset(self::KEYWORD_MAP[$pLower])) {
                $catName = self::KEYWORD_MAP[$pLower];
                $catReal = $categorias->first(fn($c) => mb_strtolower($c->nome) === mb_strtolower($catName));
                $categoriaIdentificada = $catReal ? $catReal->nome : $catName;
                if ($catReal) {
                    if ($pLower === 'fort' || $pLower === 'mercado' || $pLower === 'supermercado') {
                        $subReal = $catReal->subcategorias->first(fn($s) => str_contains(mb_strtolower($s->nome), 'mercado'));
                        if ($subReal) $subcategoriaIdentificada = $subReal->nome;
                    }
                }
                // Se for um item / estabelecimento específico como "fort", "uber", etc., preserva na descrição
                if (!in_array($pLower, ['alimentacao', 'alimentação', 'despesa', 'despesas', 'transporte', 'saude', 'saúde', 'moradia'])) {
                    $palavrasDescricao[] = ucfirst($p);
                }
                $matched = true;
            }

            if (!$matched) {
                $palavrasDescricao[] = ucfirst($p);
            }
        }

        // Descrição final
        $descricao = implode(' ', $palavrasDescricao);
        if (empty($descricao)) {
            $descricao = $subcategoriaIdentificada ?? $categoriaIdentificada ?? ($isCartao ? 'Compra no Cartão' : 'Despesa');
        }

        // Fallback de categoria
        if (empty($categoriaIdentificada)) {
            $categoriaIdentificada = $tipoTransacao === 'receita' ? 'Salário' : 'Outros';
        }

        $destino = $isCartao && $cartaoId ? 'cartao' : 'casa';

        return [
            'tipo'               => 'transacao',
            'transacao_destino'  => $destino,
            'cartao_id'          => $cartaoId,
            'cartao_tipo_compra' => $tipoCompra,
            'numero_parcelas'    => $numeroParcelas,
            'transacao_tipo'     => $tipoTransacao,
            'valor'              => $valor,
            'descricao'          => $descricao,
            'categoria'          => $categoriaIdentificada,
            'subcategoria'       => $subcategoriaIdentificada,
        ];
    }
}
