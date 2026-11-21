<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Importacao;
use Illuminate\Support\Facades\DB;

/**
 * Importa a árvore de assuntos de um caderno do TEC (a lista "Assuntos" da página do caderno,
 * copiada para um .txt/.md). O PDF do caderno só traz a FOLHA ("Vacância (arts. 33 a 35…)");
 * a árvore diz a quem ela pertence ("Lei nº 8.112/1990 - Estatuto…" → "Agentes Públicos"), e é
 * nesse nível intermediário que o edital costuma estar ligado.
 *
 * Formato: uma linha por assunto, hierarquia pela indentação, marcador "*" ou "-" opcional.
 * Linhas de contagem ("386 (63.70%)"), links markdown e marcadores vazios são ignorados.
 *   * [Direito Administrativo (Doutrina e Leis Federais)](https://...)
 *   547 (90.26%)
 *      * [Agentes Públicos](https://...)
 * A raiz (nível 0) é a disciplina; os demais níveis viram assuntos com parent_id.
 * Idempotente: reimportar só corrige o pai. As contagens do TEC vão para assunto_incidencias
 * (incidência segundo o TEC, por caderno); o domínio do aluno continua vindo só das tentativas.
 */
class ArvoreAssuntosImportador
{
    public function __construct(private TecImportador $tec) {}

    /**
     * @return list<array{nivel: int, nome: string, questoes: ?int}>
     */
    public function parse(string $texto): array
    {
        $texto = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], $texto);
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
        $nos = [];
        $indentes = []; // pilha de indentações abertas

        foreach (explode("\n", $texto) as $linha) {
            if (!preg_match('/^( *)(?:[*\-•]\s*)?(.*)$/u', rtrim($linha), $m)) {
                continue;
            }
            $nome = trim($m[2]);
            $nome = preg_replace('/^\[(.*)\]\([^)]*\)$/u', '$1', $nome);   // [Nome](url)
            // Linha só com a contagem ("386 (63.70%)"): pertence ao assunto da linha anterior.
            if (preg_match('/^(\d+)\s*\(\d+[.,]?\d*\s*%\)$/u', $nome, $c) || preg_match('/^(\d+)$/', $nome, $c)) {
                if ($nos && end($nos)['questoes'] === null) {
                    $nos[array_key_last($nos)]['questoes'] = (int) $c[1];
                }
                continue;
            }
            $questoes = null;
            if (preg_match('/\s*(\d+)\s*\(\d+[.,]?\d*\s*%\)$/u', $nome, $c)) { // "Nome 386 (63.70%)"
                $questoes = (int) $c[1];
                $nome = substr($nome, 0, -strlen($c[0]));
            }
            $nome = trim(preg_replace('/\s+/u', ' ', $nome));
            if ($nome === '') {
                continue;
            }

            $ind = strlen($m[1]);
            while ($indentes && end($indentes) >= $ind) {
                array_pop($indentes);
            }
            $indentes[] = $ind;
            $nos[] = ['nivel' => count($indentes) - 1, 'nome' => $nome, 'questoes' => $questoes];
        }

        return $nos;
    }

    public function importarArquivo(string $arquivo, ?int $userId = null): array
    {
        $salvo = $this->tec->guardarOrigem($arquivo);

        return $this->gravar($this->parse(file_get_contents($arquivo)), $arquivo, $salvo, $userId);
    }

    /**
     * @param  list<array{nivel: int, nome: string}>  $nos
     * @param  array{caminho: string, sha256: string}  $salvo
     */
    public function gravar(array $nos, string $arquivoOriginal, array $salvo, ?int $userId = null): array
    {
        if (!$nos || $nos[0]['nivel'] !== 0) {
            throw new TecParserException('A árvore precisa começar por uma disciplina (linha sem indentação).');
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($nos, $arquivoOriginal, $salvo, $userId) {
            $resolver = new AssuntoResolver;
            $resumo = ['disciplinas' => [], 'assuntos' => 0, 'pai_alterado' => 0];
            $disciplina = null;
            $caminho = []; // nivel => Assunto (nível 1 em diante)
            $contagens = []; // assunto_id => questões no caderno

            foreach ($nos as $no) {
                if ($no['nivel'] === 0) {
                    $disciplina = $no['nome'];
                    $resumo['disciplinas'][] = $disciplina;
                    $caminho = [];
                    continue;
                }
                $assunto = $resolver->porDisciplina($disciplina, $no['nome']);
                $pai = $caminho[$no['nivel'] - 1] ?? null;
                if ($assunto->parent_id !== $pai?->id && $assunto->id !== $pai?->id) {
                    $assunto->update(['parent_id' => $pai?->id]);
                    $resumo['pai_alterado']++;
                }
                $caminho = array_slice($caminho, 0, $no['nivel'] - 1, true);
                $caminho[$no['nivel']] = $assunto;
                $resumo['assuntos']++;
                if ($no['questoes'] !== null) {
                    $contagens[$assunto->id] = max($contagens[$assunto->id] ?? 0, $no['questoes']);
                }
            }

            // Incidência segundo o TEC: substitui as contagens anteriores deste caderno.
            $caderno = mb_substr(pathinfo($arquivoOriginal, PATHINFO_FILENAME), 0, 120);
            $db = DB::connection(config('concursos.connection'));
            $db->table('assunto_incidencias')->where('caderno', $caderno)->delete();
            foreach (array_chunk($contagens, 200, true) as $lote) {
                $db->table('assunto_incidencias')->insert(array_map(fn ($id, $n) => [
                    'assunto_id' => $id, 'caderno' => $caderno, 'questoes' => $n, 'created_at' => now(), 'updated_at' => now(),
                ], array_keys($lote), $lote));
            }
            $resumo['com_contagem'] = count($contagens);
            $resumo['caderno'] = $caderno;

            $resumo['assuntos_criados'] = $resolver->criados;
            $resumo['assuntos_adotados'] = $resolver->adotados;

            Importacao::create([
                'tipo' => 'arvore', 'arquivo_original' => $arquivoOriginal, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'referencia' => mb_strimwidth(implode(', ', $resumo['disciplinas']), 0, 250, '…'),
                'status' => 'ok', 'resumo' => $resumo, 'user_id' => $userId,
            ]);

            return $resumo;
        });
    }
}
