<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\EditalTopico;
use App\Models\Concursos\Importacao;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoClassificacao;
use App\Models\Concursos\QuestaoResolucao;
use Illuminate\Support\Facades\DB;

/**
 * Importa questões inéditas escritas pelo Gemini a partir do texto de uma norma (ineditas.schema.json),
 * para os tópicos que o TEC não cobre (Estatuto, Regimento, Código de Ética, Manual de Atos da UFMS).
 *  - Não há PDF: o gabarito vem do Gemini. Por isso a coerência é conferida aqui (5 letras A–E,
 *    uma correta na resolução e ela igual ao gabarito) e a questão fica marcada como fonte "inedita".
 *  - Cada norma vira um assunto da disciplina NORMAS, ligado ao tópico do edital (confirmado).
 *  - id_externo = hash da norma + enunciado: reimportar o mesmo arquivo não duplica.
 *  - A classificação e a resolução vêm junto (não passam pelo ciclo de pacotes).
 */
class IneditasImportador
{
    public const DISCIPLINA = 'Normas do órgão (questões inéditas)';

    public function __construct(
        private TecImportador $tec,
        private ContratoJsonValidator $validador,
    ) {}

    public function importarArquivo(string $arquivo, ?int $userId = null): array
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($arquivo));
        $ini = strpos($texto, '{');
        $fim = strrpos($texto, '}');
        $json = $ini === false || $fim === false ? '' : substr($texto, $ini, $fim - $ini + 1);
        if (!is_array(json_decode($json, true))) {
            throw new TecParserException('O arquivo não tem um JSON válido (resposta cortada ou colada pela metade?).');
        }

        $tmp = storage_path('framework/cache/ineditas-' . uniqid() . '.json');
        file_put_contents($tmp, $json);
        try {
            $dados = $this->validador->validar($tmp, 'ineditas');
        } finally {
            @unlink($tmp);
        }

        return $this->gravar($dados, $arquivo, $this->tec->guardarOrigem($arquivo), $userId);
    }

    /** @param array{caminho: string, sha256: string} $salvo */
    public function gravar(array $dados, string $arquivoOriginal, array $salvo, ?int $userId = null): array
    {
        $topicos = EditalTopico::where('ordem', $dados['topico']['ordem'])
            ->whereHas('disciplina.area', fn ($q) => $q->where('nome', $dados['topico']['area']))->get();
        if ($topicos->isEmpty()) {
            throw new TecParserException("Não achei o tópico {$dados['topico']['ordem']} da área \"{$dados['topico']['area']}\" em nenhum edital importado.");
        }

        $problemas = [];
        $enunciados = [];
        foreach ($dados['questoes'] as $i => $q) {
            $n = $i + 1;
            if (array_column($q['alternativas'], 'letra') !== ['A', 'B', 'C', 'D', 'E']) {
                $problemas[] = "Questão {$n}: as alternativas precisam ser A, B, C, D, E, nessa ordem.";
            }
            $corretas = array_column(array_filter($q['resolucao']['alternativas'], fn ($a) => $a['correta']), 'letra');
            if ($corretas !== [$q['gabarito']]) {
                $problemas[] = "Questão {$n}: o gabarito é {$q['gabarito']}, mas a resolução marca " . (implode(',', $corretas) ?: 'nenhuma') . ' como correta.';
            }
            $letrasRes = array_column($q['resolucao']['alternativas'], 'letra');
            sort($letrasRes);
            if ($letrasRes !== ['A', 'B', 'C', 'D', 'E']) {
                $problemas[] = "Questão {$n}: a resolução precisa explicar as 5 alternativas.";
            }
            $chave = mb_strtolower(preg_replace('/\s+/u', ' ', trim($q['enunciado'] . ' ' . implode(' ', array_column($q['alternativas'], 'texto')))));
            if (isset($enunciados[$chave])) {
                $problemas[] = "Questão {$n}: repete a questão {$enunciados[$chave]}.";
            }
            $enunciados[$chave] = $n;
        }
        if ($problemas) {
            throw new TecParserException('Arquivo de inéditas recusado (nada foi gravado).', $problemas);
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($dados, $topicos, $arquivoOriginal, $salvo, $userId) {
            $imp = Importacao::create([
                'tipo' => 'ineditas', 'arquivo_original' => $arquivoOriginal, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'status' => 'ok', 'user_id' => $userId,
                'referencia' => mb_strimwidth($dados['norma']['titulo'], 0, 250, '…'),
            ]);
            $disciplina = Disciplina::firstOrCreate(['nome' => self::DISCIPLINA]);
            $assunto = Assunto::firstOrCreate(['disciplina_id' => $disciplina->id, 'nome' => mb_substr($dados['norma']['titulo'], 0, 250)]);
            $db = DB::connection(config('concursos.connection'));
            foreach ($topicos as $t) {
                if (!$db->table('edital_topico_assunto')->where(['topico_id' => $t->id, 'assunto_id' => $assunto->id])->exists()) {
                    $db->table('edital_topico_assunto')->insert(['topico_id' => $t->id, 'assunto_id' => $assunto->id, 'status' => 'confirmado']);
                }
            }

            $novas = 0;
            foreach ($dados['questoes'] as $q) {
                $id = substr(sha1($dados['norma']['codigo'] . '|' . trim($q['enunciado']) . '|' . implode('|', array_column($q['alternativas'], 'texto'))), 0, 16);
                $questao = Questao::firstOrNew(['fonte' => 'inedita', 'id_externo' => $id]);
                $novas += $questao->exists ? 0 : 1;
                $questao->fill([
                    'banca' => 'Inédita (IA, estilo FAPEC)', 'banca_sigla' => 'INÉDITA', 'orgao' => 'UFMS',
                    'prova' => mb_substr($dados['norma']['titulo'], 0, 200), 'ano' => (int) now()->format('Y'), 'tipo' => 'ME',
                    'enunciado' => trim($q['enunciado']), 'gabarito' => $q['gabarito'], 'anulada' => false, 'importacao_id' => $imp->id,
                ])->save();
                $questao->alternativas()->delete();
                foreach ($q['alternativas'] as $o => $a) {
                    $questao->alternativas()->create(['letra' => $a['letra'], 'texto' => $a['texto'], 'ordem' => $o + 1]);
                }
                $questao->assuntos()->sync([$assunto->id => ['principal' => true]]);
                QuestaoClassificacao::updateOrCreate(['questao_id' => $questao->id], [
                    'tipologia' => $q['tipologia'] ?? 'literalidade_norma', 'dificuldade' => $q['dificuldade'],
                    'ponto_cobrado' => $q['ponto_cobrado'], 'comando' => null,
                    'distrator_letra' => $q['distrator_principal']['letra'] ?? null,
                    'distrator_mecanismo' => $q['distrator_principal']['mecanismo'] ?? null,
                    'dispositivos' => $q['dispositivos'] ?? null, 'importacao_id' => $imp->id,
                ]);
                QuestaoResolucao::updateOrCreate(['questao_id' => $questao->id], [
                    'fonte' => 'ia', 'autor' => null, 'resumo' => $q['resolucao']['resumo'], 'fundamentacao' => $q['resolucao']['fundamentacao'] ?? null,
                    'dispositivos' => $q['dispositivos'] ?? null, 'alternativas' => $q['resolucao']['alternativas'], 'importacao_id' => $imp->id,
                ]);
            }

            $resumo = ['norma' => $dados['norma']['titulo'], 'topicos' => $topicos->count(), 'questoes' => count($dados['questoes']),
                'novas' => $novas, 'total_da_norma' => $assunto->questoes()->count()];
            $imp->update(['resumo' => $resumo]);

            return $resumo;
        });
    }
}
