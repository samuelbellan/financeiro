<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialAnexo;
use App\Models\Concursos\MaterialCapitulo;
use App\Services\Concursos\Livro\Documento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Monta um livro a partir de uma pasta de aulas em PDF ("01 - BD - Teoria dos Dados - v0.pdf"):
 * um capítulo por área (a sigla do nome do arquivo) e, dentro dele, um subcapítulo por aula com a
 * seção RESUMO da aula em texto (grifável) e o PDF original abaixo.
 *   php artisan concursos:material-pdfs "G:\...\Modulo 1 Guruja Aprendiz" --titulo=TI --slug=ti
 * Pode rodar de novo: reaproveita livro, capítulos e anexos pelo nome; --refazer troca o texto.
 */
class MaterialPdfsCommand extends Command
{
    protected $signature = 'concursos:material-pdfs {pasta : pasta com os PDFs das aulas}
        {--titulo=TI : título do livro} {--slug=ti : slug do livro}
        {--user= : id do aluno (padrão: dono dos livros que já existem)}
        {--refazer : troca o texto do resumo das aulas que já estão no livro}
        {--ver : só mostra o resumo extraído de cada aula, sem gravar}';

    protected $description = 'Cria um livro com as aulas em PDF de uma pasta, divididas por área, com o resumo de cada aula em texto.';

    /** Sigla do nome do arquivo → capítulo da área. */
    private const AREAS = [
        'BD' => 'Banco de Dados',
        'CD' => 'Ciência de Dados',
        'SEC' => 'Segurança da Informação',
        'ESW' => 'Engenharia de Software',
        'RED' => 'Redes',
        'GOV' => 'Gestão e Governança de TI',
        'PRG' => 'Programação',
    ];

    /** Erros de digitação dos nomes dos arquivos. */
    private const TITULOS = ['Bancos de Dados Relacioanis' => 'Bancos de Dados Relacionais', 'Álgebra Relacional. Introdução ao SQL' => 'Álgebra Relacional e Introdução ao SQL'];

    public function handle(Documento $documento): int
    {
        $pasta = rtrim((string) $this->argument('pasta'), '\\/');
        if (!is_dir($pasta)) {
            $this->error("Pasta não encontrada: {$pasta}");
            return self::FAILURE;
        }
        $aulas = $this->aulas($pasta);
        if (!$aulas) {
            $this->error('Nenhum PDF no formato "NN - SIGLA - Título.pdf".');
            return self::FAILURE;
        }

        if ($this->option('ver')) {
            foreach ($aulas as $a) {
                $this->line("===== {$a['num']} {$a['area']} · {$a['titulo']}");
                $this->line($this->resumo($a['arquivo']));
            }
            return self::SUCCESS;
        }

        $userId = (int) ($this->option('user') ?: Material::query()->orderBy('id')->value('user_id'));
        if (!$userId) {
            $this->error('Informe --user.');
            return self::FAILURE;
        }
        $material = Material::firstOrCreate(
            ['user_id' => $userId, 'slug' => $this->option('slug')],
            ['titulo' => $this->option('titulo'), 'descricao' => 'Aulas em PDF divididas por área, com o resumo de cada aula.'],
        );

        $areas = [];
        $novos = 0;
        foreach (collect($aulas)->groupBy('area')->sortBy(fn ($as) => $as->min('num')) as $sigla => $doArea) {
            $pai = MaterialCapitulo::firstOrCreate(
                ['material_id' => $material->id, 'parent_id' => null, 'titulo' => self::AREAS[$sigla] ?? $sigla],
                ['ordem' => count($areas) + 1, 'conteudo' => Documento::vazio(), 'texto' => ''],
            );
            $pai->update(['ordem' => count($areas) + 1]);
            $areas[] = $pai->titulo;

            foreach ($doArea->sortBy('num')->values() as $i => $a) {
                $cap = MaterialCapitulo::where('material_id', $material->id)->where('titulo', $a['titulo'])->first();
                $anexo = $this->anexo($material, $a['arquivo']);
                if (!$cap || $this->option('refazer')) {
                    $doc = ['type' => 'doc', 'content' => $documento->deMarkdown($this->resumo($a['arquivo'])) ?: [['type' => 'paragraph']]];
                    $cap ??= new MaterialCapitulo(['material_id' => $material->id, 'titulo' => $a['titulo']]);
                    $novos += $cap->exists ? 0 : 1;
                    $cap->fill(['conteudo' => $doc, 'texto' => $documento->texto($doc)]);
                }
                $cap->fill(['parent_id' => $pai->id, 'ordem' => $i + 1, 'pdf_anexo_id' => $anexo->id])->save();
                $this->line(sprintf('  %s · %s (%s palavras de resumo)', $pai->titulo, $a['titulo'], str_word_count((string) $cap->texto)));
            }
        }

        $this->info("Livro \"{$material->titulo}\" (id {$material->id}): " . count($areas) . ' áreas (' . implode(', ', $areas) . "), " . count($aulas) . " aulas, {$novos} novas.");

        return self::SUCCESS;
    }

    /** @return list<array{num: int, area: string, titulo: string, arquivo: string}> */
    private function aulas(string $pasta): array
    {
        $saida = [];
        foreach (glob($pasta . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $arquivo) {
            if (!preg_match('/^(\d+)\s*-\s*([A-Z]+)\s*-\s*(.+?)(?:\s*-\s*v\d+)?(?:\s*\(\d+\))?\.pdf$/iu', basename($arquivo), $m)) {
                continue;
            }
            $titulo = trim($m[3]);
            $saida[] = ['num' => (int) $m[1], 'area' => strtoupper($m[2]), 'titulo' => self::TITULOS[$titulo] ?? $titulo, 'arquivo' => $arquivo];
        }

        return $saida;
    }

    /** PDF guardado como anexo do livro (o mesmo arquivo não é copiado duas vezes). */
    private function anexo(Material $material, string $arquivo): MaterialAnexo
    {
        $nome = basename($arquivo);
        $anexo = MaterialAnexo::where('material_id', $material->id)->where('nome', $nome)->first();
        if ($anexo && Storage::disk('local')->exists($anexo->caminho)) {
            return $anexo;
        }
        $caminho = "concursos/pdfs/{$material->id}/" . md5($nome) . '.pdf';
        Storage::disk('local')->put($caminho, fopen($arquivo, 'rb'));

        return MaterialAnexo::updateOrCreate(
            ['material_id' => $material->id, 'nome' => $nome],
            ['caminho' => $caminho, 'mime' => 'application/pdf', 'tamanho' => filesize($arquivo)],
        );
    }

    /**
     * Seção RESUMO da aula (perguntas centralizadas + respostas) em Markdown: cada pergunta vira
     * título e as linhas de cada parágrafo são juntadas. Tira a marca d'água e os números de página.
     */
    private function resumo(string $arquivo): string
    {
        $p = new Process(['pdftotext', '-enc', 'UTF-8', '-layout', $arquivo, '-']);
        $p->setTimeout(120)->run();
        $saida = (string) @iconv('UTF-8', 'UTF-8//IGNORE', $p->getOutput());
        $linhas = preg_split('/\r?\n/u', $saida) ?: [];

        $ini = $fim = null;
        foreach ($linhas as $i => $l) {
            $t = trim($l);
            if ($ini === null && $t === 'RESUMO') {
                $ini = $i + 1;
            } elseif ($ini !== null && preg_match('/^(QUESTÕES COMENTADAS|LISTA DE QUESTÕES)$/u', $t)) {
                $fim = $i;
                break;
            }
        }
        if ($ini === null) {
            return '';
        }

        // Blocos separados por linha em branco. Linhas com colunas (3+ espaços no meio) formam
        // uma tabela; linha recuada até a 2ª coluna continua a célula de cima.
        $blocos = [];
        $atual = [];
        $tabela = null;
        $fecharTabela = function () use (&$tabela, &$blocos) {
            if ($tabela) {
                $blocos[] = ['tabela' => $tabela['linhas']];
                $tabela = null;
            }
        };
        foreach (array_slice($linhas, $ini, ($fim ?? count($linhas)) - $ini) as $l) {
            $l = rtrim(str_replace("\f", '', $l));
            if (str_contains($l, '@gmail.com') || preg_match('/^\s*\d{1,3}\s*$/u', $l)) {
                continue; // marca d'água com nome/CPF/e-mail e número de página
            }
            $recuo = strlen($l) - strlen(ltrim($l));
            if (preg_match('/\S\s{3,}\S/u', trim($l))) {
                if ($atual) {
                    $blocos[] = $atual;
                    $atual = [];
                }
                $celulas = preg_split('/\s{3,}/u', trim($l));
                if (!$tabela) {
                    preg_match('/^(\s*\S.*?\s{3,})\S/u', $l, $m);
                    $tabela = ['col2' => strlen($m[1] ?? ''), 'linhas' => []];
                }
                $tabela['linhas'][] = $celulas;
                continue;
            }
            $pergunta = str_ends_with(trim($l), '?') && mb_strlen(trim($l)) < 100;
            if ($tabela && (trim($l) === '' || ($recuo >= $tabela['col2'] - 2 && !$pergunta))) {
                if (trim($l) !== '') {
                    $u = count($tabela['linhas']) - 1;
                    $c = count($tabela['linhas'][$u]) - 1;
                    $tabela['linhas'][$u][$c] .= ' ' . trim($l);
                }
                continue;
            }
            $fecharTabela();
            if (trim($l) === '') {
                if ($atual) {
                    $blocos[] = $atual;
                    $atual = [];
                }
                continue;
            }
            $atual[] = $l;
        }
        $fecharTabela();
        if ($atual) {
            $blocos[] = $atual;
        }

        // Tabela do PDF sai embaralhada no texto (células de várias linhas se misturam): no lugar
        // dela vai um aviso só, que também engole os pedaços soltos entre partes da mesma tabela.
        $md = [];
        $naTabela = false;
        foreach ($blocos as $b) {
            if (isset($b['tabela'])) {
                if (!$naTabela) {
                    $md[] = '*(Tabela: veja no PDF da aula, logo abaixo.)*';
                }
                $naTabela = true;
                continue;
            }
            $palavras = str_word_count(implode(' ', $b));
            if ($naTabela && !str_ends_with(trim(end($b)), '?') && ($palavras < 12 || preg_match('/\S {2}\S/u', trim($b[0])))) {
                continue;
            }
            $naTabela = false;
            // Perguntas (centralizadas, ou em maiúsculas no começo do parágrafo) viram título;
            // itens com marcador viram lista; o resto das linhas seguidas, um parágrafo só.
            $par = [];
            $saida = [];
            foreach ($b as $l) {
                $t = trim($l);
                $recuo = strlen($l) - strlen(ltrim($l));
                $pergunta = mb_strlen($t) < 100 && str_ends_with($t, '?')
                    && ($recuo >= 6 || (mb_strtoupper($t) === $t && preg_match('/\p{Lu}{3}/u', $t)));
                $tituloSolto = count($b) === 1 && $recuo >= 6 && mb_strlen($t) < 90 && !preg_match('/[.;:,]$/u', $t);
                if ($pergunta || $tituloSolto) {
                    if ($par) {
                        $saida[] = implode(' ', $par);
                        $par = [];
                    }
                    $saida[] = "\n### " . $this->frase($t) . "\n";
                } elseif (preg_match('/^[•●▪■◦\-–]\s*(.+)$/u', $t, $m)) {
                    if ($par) {
                        $saida[] = implode(' ', $par);
                        $par = [];
                    }
                    $saida[] = '- ' . $m[1];
                } elseif ($saida && str_starts_with(end($saida), '- ') && !$par) {
                    $saida[count($saida) - 1] .= ' ' . $t;
                } else {
                    $par[] = $t;
                }
            }
            if ($par) {
                $saida[] = implode(' ', $par);
            }
            $md[] = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $saida)));
        }

        return implode("\n\n", $md);
    }

    /** "O QUE É O SGBD?" / "O que é a pirâmide dikw?" → siglas conhecidas em maiúsculas. */
    private function frase(string $t): string
    {
        $siglas = ['dikw', 'acid', 'sgbd', 'sgbds', 'sql', 'nosql', 'etl', 'elt', 'olap', 'oltp', 'bi', 'dw', 'ia', 'pln', 'ml', 'rnn', 'cnn', 'llm', 'api', 'apis', 'soap', 'rest', 'wsdl', 'uddi', 'xml', 'json', 'http', 'ssl', 'tls', 'vpn', 'ids', 'ips', 'ddos', 'dos', 'pki', 'icp', 'rsa', 'aes', 'des', 'md5', 'sha', 'hdfs', 'rdd', 'dama', 'dmbok', 'crud', 'dml', 'ddl', 'dcl', 'tcl', 'svm', 'knn', 'pca', 'roc', 'auc', 'mapreduce', 'kdd', 'crisp-dm', 'dax'];
        $nomes = ['apis' => 'APIs', 'apis?' => 'APIs?', 'hadoop' => 'Hadoop', 'spark' => 'Spark', 'power' => 'Power', 'bi?' => 'BI?', 'mapreduce' => 'MapReduce', 'yarn' => 'YARN'];

        return preg_replace_callback('/[\p{L}\d-]+\??/u', function ($m) use ($siglas, $nomes) {
            $w = $m[0];
            $limpo = mb_strtolower(rtrim($w, '?'));
            if (isset($nomes[mb_strtolower($w)])) {
                return $nomes[mb_strtolower($w)];
            }
            if (in_array($limpo, $siglas, true)) {
                return mb_strtoupper($w);
            }

            return $w;
        }, $t);
    }
}
