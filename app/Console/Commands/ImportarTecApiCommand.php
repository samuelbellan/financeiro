<?php

namespace App\Console\Commands;

use App\Services\Concursos\TecApiImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Console\Command;

/**
 * Importa cadernos do TEC baixados pela API (JSON do receptor local) para o banco de questões.
 *   php artisan concursos:importar-tec-api storage/concursos/tec/ufms-afo
 *   php artisan concursos:importar-tec-api storage/concursos/tec/ufms-afo/123-AFO.json --disciplina="AFO, Direito Financeiro e Contabilidade Pública"
 * Aceita arquivos ou pastas (todos os *.json). Cada arquivo é tudo-ou-nada.
 */
class ImportarTecApiCommand extends Command
{
    protected $signature = 'concursos:importar-tec-api {caminhos* : Arquivos JSON ou pastas}
        {--disciplina= : Disciplina do TEC para questões que vierem sem a matéria}';

    protected $description = 'Importa cadernos do TEC (JSON da API) para o banco de questões, com o comentário do professor como resolução.';

    public function handle(TecApiImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');

            return self::FAILURE;
        }

        $arquivos = [];
        foreach ($this->argument('caminhos') as $c) {
            foreach (is_dir($c) ? (glob(rtrim($c, '/\\') . '/*.json') ?: []) : [$c] as $arq) {
                array_push($arquivos, ...$this->desempacotar($arq));
            }
        }
        if (!$arquivos) {
            $this->error('Nenhum JSON encontrado.');

            return self::FAILURE;
        }

        $falhas = 0;
        $totais = ['novas' => 0, 'atualizadas' => 0];
        foreach ($arquivos as $arquivo) {
            $this->line('<options=bold>' . basename($arquivo) . '</>');
            try {
                $r = $importador->importarArquivo($arquivo, $this->option('disciplina'));
            } catch (TecParserException $e) {
                $this->error('  RECUSADO: ' . $e->getMessage());
                $falhas++;
                continue;
            }

            $s = $r['resumo'];
            $totais['novas'] += $s['novas'];
            $totais['atualizadas'] += $s['atualizadas'];
            $this->info("  OK: {$s['novas']} novas, {$s['atualizadas']} atualizadas · resolução: {$s['resolucoes_tec']} do professor, "
                . "{$s['resolucoes_minimas']} só gabarito, {$s['resolucoes_ia_mantidas']} da IA mantidas (importação #{$r['importacao_id']}).");
            foreach ($s['assuntos_criados'] as $a) {
                $this->line("  + assunto novo: {$a}");
            }
            foreach ($s['assuntos_adotados'] ?? [] as $a) {
                $this->line("  = assunto do edital agora com disciplina: {$a}");
            }
            foreach ($s['gabarito_alterado'] as $g) {
                $this->warn("  ! gabarito mudou na questão {$g['id_externo']}: " . ($g['antes'] ?? 'anulada') . ' -> ' . ($g['agora'] ?? 'anulada') . ' (revise ledger/resolução)');
            }
            if (!empty($s['puladas'])) {
                $this->warn('  ! ' . count($s['puladas']) . ' sem gabarito no comentário (não importadas): ' . implode(', ', array_slice($s['puladas'], 0, 10)) . (count($s['puladas']) > 10 ? '…' : ''));
            }
            $semAssunto = count(array_filter($s['avisos'], fn ($a) => str_contains($a, 'sem assunto')));
            if ($semAssunto) {
                $this->warn("  ! {$semAssunto} questão(ões) sem assunto no TEC");
            }
        }

        $this->info("Total: {$totais['novas']} novas, {$totais['atualizadas']} atualizadas" . ($falhas ? ", {$falhas} arquivo(s) recusado(s)" : '') . '.');

        return $falhas ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Pacote exportado do navegador ({pacote, blocos: {nome: caderno}}) vira um arquivo por bloco
     * em storage/concursos/tec/<pacote>/ (fora do git); arquivo comum passa direto.
     *
     * @return list<string>
     */
    private function desempacotar(string $arquivo): array
    {
        $dados = is_file($arquivo) && filesize($arquivo) > 0 ? json_decode((string) file_get_contents($arquivo), true) : null;
        if (!is_array($dados) || !isset($dados['blocos']) || !is_array($dados['blocos'])) {
            return [$arquivo];
        }

        $pasta = storage_path('concursos/tec/' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($dados['pacote'] ?? 'pacote')));
        \Illuminate\Support\Facades\File::ensureDirectoryExists($pasta);
        $saida = [];
        foreach ($dados['blocos'] as $nome => $bloco) {
            $destino = $pasta . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $nome) . '.json';
            file_put_contents($destino, json_encode($bloco, JSON_UNESCAPED_UNICODE));
            $saida[] = $destino;
        }
        $this->line('Pacote ' . basename($arquivo) . ': ' . count($saida) . " blocos em {$pasta}");
        sort($saida);

        return $saida;
    }
}
