<?php

namespace App\Services\Concursos;

use RuntimeException;

/**
 * O PDF não pôde ser lido com segurança. O lote inteiro é recusado:
 * importar parte das questões com gabarito desalinhado é pior do que não importar.
 */
class TecParserException extends RuntimeException
{
    /** @param string[] $problemas */
    public function __construct(string $message, public readonly array $problemas = [])
    {
        parent::__construct($problemas ? $message . "\n - " . implode("\n - ", $problemas) : $message);
    }
}
