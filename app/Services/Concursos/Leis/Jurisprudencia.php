<?php

namespace App\Services\Concursos\Leis;

use RuntimeException;

/**
 * Precedentes do acervo ctresb/leis (pasta jurisprudencia/, textos do Banco Nacional de Precedentes do CNJ).
 * No material, [[sv 24]], [[sumstj 392]], [[temastf 1124]], [[adi 5135]] viram links para cá.
 */
class Jurisprudencia
{
    /** sigla da citação → [rótulo (sprintf), caminho no repositório (sprintf), nome do tipo] */
    public const TIPOS = [
        'sv' => ['SV %s', 'jurisprudencia/stf/sumulas-vinculantes/sumula-vinculante-%s.md', 'Súmulas vinculantes'],
        'sumstf' => ['Súmula %s/STF', 'jurisprudencia/stf/sumulas/sumula-%s.md', 'Súmulas do STF'],
        'sumstj' => ['Súmula %s/STJ', 'jurisprudencia/stj/sumulas/sumula-%s.md', 'Súmulas do STJ'],
        'temastf' => ['Tema %s/STF', 'jurisprudencia/stf/repercussao-geral/tema-%s.md', 'Repercussão geral (STF)'],
        'temastj' => ['Tema %s/STJ', 'jurisprudencia/stj/repetitivos/tema-%s.md', 'Repetitivos (STJ)'],
        'adi' => ['ADI %s', 'jurisprudencia/stf/controle-concentrado/adi-%s.md', 'ADI'],
        'adc' => ['ADC %s', 'jurisprudencia/stf/controle-concentrado/adc-%s.md', 'ADC'],
        'adpf' => ['ADPF %s', 'jurisprudencia/stf/controle-concentrado/adpf-%s.md', 'ADPF'],
    ];

    public function __construct(private readonly AcervoLeis $acervo)
    {
    }

    public function existe(string $tipo): bool
    {
        return isset(self::TIPOS[$tipo]);
    }

    public function rotulo(string $tipo, string $numero): string
    {
        $n = ctype_digit($numero) && strlen($numero) > 3 ? number_format((int) $numero, 0, ',', '.') : $numero;

        return sprintf(self::TIPOS[$tipo][0], $n);
    }

    public function caminho(string $tipo, string $numero): string
    {
        return sprintf(self::TIPOS[$tipo][1], $numero);
    }

    /**
     * Texto do precedente, sem o bloco bruto "Registro original do CNJ" e sem seções "não informado".
     *
     * @return array{rotulo: string, titulo: string, meta: array<string, string>, md: string, path: string}
     */
    public function ler(string $tipo, string $numero): array
    {
        if (!$this->existe($tipo) || !preg_match('/^\d+$/', $numero)) {
            throw new RuntimeException('Precedente inválido.');
        }
        $path = $this->caminho($tipo, ltrim($numero, '0') ?: '0');
        $md = str_replace("\r\n", "\n", $this->acervo->lerCaminho($path));

        $analise = AcervoLeis::analisar($md);
        $corpo = preg_replace('/\A---\n.*?\n---\n/s', '', $md);
        $corpo = preg_replace('/^## Registro original do CNJ.*\z/ms', '', $corpo);
        $corpo = preg_replace('/^# .*\n/m', '', $corpo, 1); // o título vai no cabeçalho
        $corpo = preg_replace('/^## [^\n]+\n+\s*não informado\s*\n*/mi', '', $corpo);

        return [
            'rotulo' => $this->rotulo($tipo, $numero),
            'titulo' => $analise['meta']['titulo'] ?? $this->rotulo($tipo, $numero),
            'meta' => $analise['meta'],
            'md' => trim($corpo),
            'path' => $path,
        ];
    }
}
