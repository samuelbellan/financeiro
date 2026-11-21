<?php

namespace App\Services\Concursos;

use Symfony\Component\Process\Process;

/**
 * Extrai o texto das páginas de um PDF chamando scripts/pdf_texto.py (pdfplumber).
 * O Python usado é o do venv do projeto (.venv-concursos), configurável em CONCURSOS_PYTHON.
 */
class TecPdfExtractor
{
    /** @return array{paginas: string[], imagens: int[]} */
    public function extrair(string $arquivoPdf): array
    {
        if (!is_file($arquivoPdf)) {
            throw new TecParserException("Arquivo não encontrado: {$arquivoPdf}");
        }

        $process = new Process(
            [config('concursos.python'), base_path('scripts/pdf_texto.py'), $arquivoPdf],
            null,
            ['PYTHONIOENCODING' => 'utf-8'],
            null,
            120
        );
        $process->run();

        if (!$process->isSuccessful()) {
            throw new TecParserException('Falha ao extrair o texto do PDF: ' . trim($process->getErrorOutput()));
        }

        $dados = json_decode($process->getOutput(), true);
        if (!is_array($dados) || !isset($dados['paginas'])) {
            throw new TecParserException('O extrator de PDF devolveu uma saída inválida.');
        }

        if (trim(implode('', $dados['paginas'])) === '') {
            throw new TecParserException(
                'O PDF não tem texto (provavelmente foi gerado pelo "Microsoft Print to PDF"). '
                . 'Exporte pelo Chrome/Edge escolhendo o destino "Salvar como PDF".'
            );
        }

        return ['paginas' => $dados['paginas'], 'imagens' => $dados['imagens'] ?? []];
    }
}
