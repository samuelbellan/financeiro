<?php

namespace App\Services;

use App\Models\Transacao;
use App\Models\CartaoCompra;
use App\Models\Categoria;
use App\Services\CategorySanitizer;

class TransactionSuggestionService
{
    /**
     * Normaliza string removendo acentos e espaços extras para comparação insensível.
     */
    public static function normalize(?string $str): string
    {
        if (empty($str)) {
            return '';
        }

        $unaccented = strtr(
            mb_strtolower(trim($str), 'UTF-8'),
            [
                'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
                'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
                'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
                'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
                'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
                'ç'=>'c','ñ'=>'n'
            ]
        );
        return preg_replace('/\s+/', ' ', $unaccented);
    }

    /**
     * Retorna sugestões consolidadas de lançamentos para o usuário.
     *
     * @param int $userId
     * @param string|null $query Termo de busca opcional
     * @param int $limit Quantidade máxima de sugestões a retornar
     * @return array
     */
    public static function getSuggestions(int $userId, ?string $query = null, int $limit = 50): array
    {
        $userCategorias = Categoria::where('user_id', $userId)->pluck('nome')->toArray();

        // 1. Transações anteriores
        $transacoes = Transacao::where('user_id', $userId)
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->select('descricao', 'categoria', 'subcategoria', 'tipo', 'data')
            ->orderBy('data', 'desc')
            ->get();

        // 2. Compras no Cartão anteriores
        $compras = CartaoCompra::whereHas('cartao', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->select('descricao', 'categoria', 'data_compra as data')
            ->orderBy('data_compra', 'desc')
            ->get();

        $map = [];

        // Processar transações
        foreach ($transacoes as $t) {
            $norm = static::normalize($t->descricao);
            if (empty($norm)) {
                continue;
            }

            $cat = !empty($t->categoria) ? CategorySanitizer::sanitize($t->categoria, $userCategorias) : null;
            if ($cat === 'Sem Categoria') {
                $cat = null;
            }

            if (!isset($map[$norm])) {
                $map[$norm] = [
                    'descricao' => trim($t->descricao),
                    'categoria' => $cat,
                    'subcategoria' => $t->subcategoria,
                    'tipo' => $t->tipo ?? 'despesa',
                    'count' => 0,
                    'last_date' => $t->data,
                ];
            }

            $map[$norm]['count']++;

            if (empty($map[$norm]['categoria']) && !empty($cat)) {
                $map[$norm]['categoria'] = $cat;
            }
            if (empty($map[$norm]['subcategoria']) && !empty($t->subcategoria)) {
                $map[$norm]['subcategoria'] = $t->subcategoria;
            }
            if (empty($map[$norm]['tipo']) && !empty($t->tipo)) {
                $map[$norm]['tipo'] = $t->tipo;
            }
        }

        // Processar compras no cartão
        foreach ($compras as $c) {
            $norm = static::normalize($c->descricao);
            if (empty($norm)) {
                continue;
            }

            $cat = !empty($c->categoria) ? CategorySanitizer::sanitize($c->categoria, $userCategorias) : null;
            if ($cat === 'Sem Categoria') {
                $cat = null;
            }

            if (!isset($map[$norm])) {
                $map[$norm] = [
                    'descricao' => trim($c->descricao),
                    'categoria' => $cat,
                    'subcategoria' => null,
                    'tipo' => 'despesa',
                    'count' => 0,
                    'last_date' => $c->data,
                ];
            }

            $map[$norm]['count']++;

            if (empty($map[$norm]['categoria']) && !empty($cat)) {
                $map[$norm]['categoria'] = $cat;
            }
        }

        $items = array_values($map);
        $normQuery = !empty($query) ? static::normalize($query) : '';

        if (!empty($normQuery)) {
            $filtered = [];
            foreach ($items as $item) {
                $normItem = static::normalize($item['descricao']);
                if (str_contains($normItem, $normQuery)) {
                    $isPrefix = str_starts_with($normItem, $normQuery);
                    $item['rank_score'] = $isPrefix ? 1 : 2;
                    $filtered[] = $item;
                }
            }

            usort($filtered, function ($a, $b) {
                if ($a['rank_score'] !== $b['rank_score']) {
                    return $a['rank_score'] <=> $b['rank_score'];
                }
                if ($b['count'] !== $a['count']) {
                    return $b['count'] <=> $a['count'];
                }
                return strcmp($b['last_date'] ?? '', $a['last_date'] ?? '');
            });

            $items = $filtered;
        } else {
            usort($items, function ($a, $b) {
                if ($b['count'] !== $a['count']) {
                    return $b['count'] <=> $a['count'];
                }
                return strcmp($b['last_date'] ?? '', $a['last_date'] ?? '');
            });
        }

        $sliced = array_slice($items, 0, $limit);

        // Retornar array limpo para JSON/Frontend
        return array_map(function ($item) {
            return [
                'descricao' => $item['descricao'],
                'categoria' => $item['categoria'] ?? '',
                'subcategoria' => $item['subcategoria'] ?? '',
                'tipo' => $item['tipo'] ?? 'despesa',
                'count' => $item['count'],
            ];
        }, $sliced);
    }
}
