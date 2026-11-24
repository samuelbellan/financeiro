<?php

namespace App\Support;

/**
 * Pasta principal dos arquivos do app (PDFs, fotos, notas, materiais de concursos).
 * Com DADOS_DIR definido (pasta do Google Drive), tudo fica lá; sem ele, em storage/.
 * A estrutura de subpastas é a mesma nos dois casos (app/public, app/private, concursos/...).
 * Logs, cache, sessões e o banco SQLite continuam sempre no PC.
 */
class Dados
{
    /** Raiz a partir do .env; usada pelos arquivos de config (que carregam antes de config('dados')). */
    public static function raiz(): string
    {
        $dir = (string) env('DADOS_DIR', '');

        return rtrim($dir !== '' ? $dir : storage_path(), '\\/');
    }

    public static function path(string $relativo = ''): string
    {
        $raiz = rtrim((string) (config('dados.dir') ?: storage_path()), '\\/');
        $relativo = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativo), DIRECTORY_SEPARATOR);

        return $relativo === '' ? $raiz : $raiz . DIRECTORY_SEPARATOR . $relativo;
    }

    /** Se a pasta principal existe (o Google Drive para desktop pode estar fechado). */
    public static function disponivel(): bool
    {
        return is_dir(self::path());
    }
}
