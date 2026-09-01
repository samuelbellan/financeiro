<?php

namespace App\Services;

class CategorySanitizer
{
    /**
     * Retorna a chave canônica (slug sem acentos, espaços extras ou letras maiúsculas)
     */
    public static function slug(?string $string): string
    {
        if (empty($string)) return '';
        
        $trimmed = trim($string);
        $unaccented = strtr(
            mb_strtolower($trimmed, 'UTF-8'),
            [
                'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
                'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
                'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
                'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
                'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
                'ç'=>'c','ñ'=>'n'
            ]
        );
        return preg_replace('/[^a-z0-9]/', '', $unaccented);
    }

    /**
     * Sanitiza o nome de uma categoria.
     * Mapeia variações como "EDUCACAO", "Educacao", "educação " para a categoria oficial
     * cadastrada pelo usuário (ex: "Educação"). Se não houver correspondência exata cadastrada,
     * formata o nome de forma limpa e padronizada.
     */
    public static function sanitize(?string $rawName, array $userRegisteredCategories = []): string
    {
        if (empty($rawName)) {
            return 'Sem Categoria';
        }

        $trimmed = trim($rawName);
        $slug = static::slug($trimmed);

        if (empty($slug)) {
            return 'Sem Categoria';
        }

        // 1. Verificar se corresponde a alguma categoria cadastrada pelo usuário (ignorando caixa e acentos)
        foreach ($userRegisteredCategories as $registered) {
            if (static::slug($registered) === $slug) {
                return trim($registered);
            }
        }

        // 2. Se for tudo em caixa alta (ex: "EDUCACAO"), converter para Title Case ("Educacao")
        if (mb_strtoupper($trimmed, 'UTF-8') === $trimmed) {
            return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
        }

        return $trimmed;
    }

    /**
     * Compara se duas categorias/subcategorias são equivalentes
     */
    public static function isMatch(?string $cat1, ?string $cat2): bool
    {
        $slug1 = static::slug($cat1);
        $slug2 = static::slug($cat2);

        if ($slug1 === '' && $slug2 === '') return true;
        if ($slug1 === '' || $slug2 === '') return false;
        
        return $slug1 === $slug2;
    }
}
