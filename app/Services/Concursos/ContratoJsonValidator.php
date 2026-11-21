<?php

namespace App\Services\Concursos;

use Symfony\Component\Process\Process;

/**
 * Valida um arquivo do contrato (edital, ledger, resoluções, mapa) contra os JSON Schemas de
 * resources/schemas/concursos, usando o mesmo validar.py que o curador roda à mão.
 */
class ContratoJsonValidator
{
    /** @throws TecParserException com a lista de erros, se o arquivo não seguir o schema */
    public function validar(string $arquivo, string $tipo): array
    {
        $process = new Process(
            [config('concursos.python'), resource_path('schemas/concursos/validar.py'), '--tipo', $tipo, $arquivo],
            null,
            ['PYTHONIOENCODING' => 'utf-8'],
            null,
            60
        );
        $process->run();

        $r = json_decode($process->getOutput(), true);
        if (!is_array($r)) {
            throw new TecParserException('O validador de JSON falhou: ' . trim($process->getErrorOutput()));
        }
        if (!$r['ok']) {
            throw new TecParserException("O arquivo não segue o schema \"{$tipo}\".", $r['erros']);
        }

        return json_decode(file_get_contents($arquivo), true);
    }
}
