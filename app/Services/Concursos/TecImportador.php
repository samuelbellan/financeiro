<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Importacao;
use App\Models\Concursos\Questao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Importa um caderno do TEC (PDF) para o banco de concursos.
 *  - Idempotente: a chave é (fonte, id_externo). Reimportar atualiza, não duplica.
 *  - O PDF de origem é guardado em storage/concursos/imports/AAAA-MM-DD/ (fonte para reconstruir o banco).
 *  - Disciplinas e assuntos são criados na hora, com o nome exato do TEC.
 *  - Gabarito que MUDOU em relação ao banco é aplicado (o TEC corrige após recursos) e listado no resumo,
 *    porque ledger/resoluções daquela questão precisam ser revistos.
 *  - Caderno inconsistente (TecParserException) não grava nenhuma questão; fica só o registro "recusada".
 */
class TecImportador
{
    public function __construct(
        private TecPdfExtractor $extrator,
        private TecCadernoParser $parser,
    ) {}

    /** @return array{importacao_id: int, status: string, resumo: array} */
    public function importarPdf(string $arquivo, ?int $userId = null): array
    {
        $salvo = $this->guardarOrigem($arquivo);

        try {
            $x = $this->extrator->extrair($arquivo);
            $lido = $this->parser->parse($x['paginas'], $x['imagens']);
        } catch (TecParserException $e) {
            $imp = Importacao::create([
                'tipo' => 'pdf_tec', 'arquivo_original' => $arquivo, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'status' => 'recusada', 'user_id' => $userId,
                'resumo' => ['erro' => $e->getMessage(), 'problemas' => $e->problemas],
            ]);

            throw $e;
        }

        return $this->gravar($lido, $arquivo, $salvo, $userId);
    }

    /**
     * Grava um caderno já lido pelo parser (separado para os testes não dependerem do Python).
     * Também usado pelo TecApiImportador (tipo 'api_tec'), que passa $depois para gravar a resolução.
     *
     * @param  array{caminho: string, sha256: string}  $salvo
     */
    public function gravar(array $lido, string $arquivoOriginal, array $salvo, ?int $userId = null, string $tipo = 'pdf_tec', ?callable $depois = null): array
    {
        return DB::connection(config('concursos.connection'))->transaction(function () use ($lido, $arquivoOriginal, $salvo, $userId, $tipo, $depois) {
            $imp = Importacao::create([
                'tipo' => $tipo, 'arquivo_original' => $arquivoOriginal, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'referencia' => $lido['caderno']['titulo'], 'status' => 'ok', 'user_id' => $userId,
            ]);

            $resumo = ['caderno' => $lido['caderno'], 'novas' => 0, 'atualizadas' => 0, 'gabarito_alterado' => [],
                'assuntos_criados' => [], 'avisos' => $lido['avisos']];
            $cacheAssuntos = [];
            $resolver = new AssuntoResolver;

            foreach ($lido['questoes'] as $q) {
                $questao = Questao::firstOrNew(['fonte' => 'tec', 'id_externo' => $q['id_externo']]);
                $nova = !$questao->exists;

                if (!$nova && $questao->gabarito !== $q['gabarito']) {
                    $resumo['gabarito_alterado'][] = [
                        'id_externo' => $q['id_externo'], 'antes' => $questao->gabarito, 'agora' => $q['gabarito'],
                    ];
                }

                $questao->fill([
                    'banca' => $q['banca'], 'banca_sigla' => $q['banca_sigla'], 'prova' => $q['prova'],
                    'orgao' => $q['orgao'], 'cargo' => $q['cargo'], 'ano' => $q['ano'], 'cabecalho' => $q['cabecalho'],
                    'tipo' => $q['tipo'], 'enunciado' => $q['enunciado'], 'gabarito' => $q['gabarito'],
                    'anulada' => $q['anulada'], 'importacao_id' => $imp->id,
                ]);
                if (array_key_exists('desatualizada', $q)) {
                    $questao->desatualizada = (bool) $q['desatualizada'];
                }
                $questao->save();

                $questao->alternativas()->delete();
                foreach ($q['alternativas'] as $i => $alt) {
                    $questao->alternativas()->create(['letra' => $alt['letra'], 'texto' => $alt['texto'], 'ordem' => $i + 1]);
                }

                $vinculos = [];
                foreach ($q['assuntos'] as $i => $a) {
                    $chave = $a['disciplina'] . "\0" . $a['assunto'];
                    if (!isset($cacheAssuntos[$chave])) {
                        // Cria, adota um assunto "a confirmar" vindo do edital, ou segue a união da curadoria.
                        $cacheAssuntos[$chave] = $resolver->porDisciplina($a['disciplina'], $a['assunto'])->id;
                    }
                    $vinculos[$cacheAssuntos[$chave]] = ['principal' => $i === 0];
                }
                $questao->assuntos()->sync($vinculos);

                $nova ? $resumo['novas']++ : $resumo['atualizadas']++;
                if ($depois) {
                    $depois($questao, $q, $imp, $resumo);
                }
            }

            $resumo['assuntos_criados'] = $resolver->criados;
            $resumo['assuntos_adotados'] = $resolver->adotados;
            $imp->update(['resumo' => $resumo]);

            return ['importacao_id' => $imp->id, 'status' => 'ok', 'resumo' => $resumo];
        });
    }

    /** Copia o arquivo para storage/concursos/imports/AAAA-MM-DD/<sha256>.<ext> (nunca sobrescreve). */
    public function guardarOrigem(string $arquivo): array
    {
        if (!is_file($arquivo)) {
            throw new TecParserException("Arquivo não encontrado: {$arquivo}");
        }

        $sha = hash_file('sha256', $arquivo);
        $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) ?: 'bin';
        $dir = \App\Support\Dados::path('concursos/imports/' . now()->format('Y-m-d'));
        File::ensureDirectoryExists($dir);
        $destino = "{$dir}/{$sha}.{$ext}";
        if (!is_file($destino)) {
            File::copy($arquivo, $destino);
        }

        return ['caminho' => $destino, 'sha256' => $sha];
    }
}
