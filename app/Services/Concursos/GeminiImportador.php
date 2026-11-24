<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Importacao;
use App\Models\Concursos\Lote;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoClassificacao;
use App\Models\Concursos\QuestaoResolucao;
use App\Models\Concursos\Subtopico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa o que o Gemini devolve para um pacote: o ledger (classificação) e as resoluções.
 *  - O tipo é reconhecido pelo CONTEÚDO, não pelo nome do arquivo ("resp1.txt" serve).
 *    Cercas ```json e texto antes/depois do JSON são descartados.
 *  - O lote é achado por assunto + número. Questão que não está no lote, gabarito diferente do
 *    PDF, resolução com a alternativa correta errada ou subtópico inexistente → o arquivo inteiro
 *    é recusado (a IA classifica e redige; quem manda no gabarito é o banco).
 *  - Subtópico "novo" vira subtópico pendente do assunto do lote, esperando a curadoria.
 *  - Reimportar sobrescreve a classificação/resolução daquelas questões.
 */
class GeminiImportador
{
    public function __construct(
        private TecImportador $tec,
        private ContratoJsonValidator $validador,
    ) {}

    /**
     * Lê um arquivo do Gemini e diz o que ele é.
     *
     * @return array{tipo: 'ledger'|'resolucoes'|'contexto'|null, dados: ?array, json: string}
     */
    public function ler(string $arquivo): array
    {
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($arquivo));
        $ini = strpos($texto, '{');
        $fim = strrpos($texto, '}');
        $json = $ini === false || $fim === false ? '' : substr($texto, $ini, $fim - $ini + 1);
        $dados = json_decode($json, true);

        return ['tipo' => is_array($dados) ? self::tipo($dados) : null, 'dados' => is_array($dados) ? $dados : null, 'json' => $json];
    }

    public static function tipo(array $dados): ?string
    {
        $q = $dados['questoes'][0] ?? null;
        if (!is_array($q)) {
            return null;
        }

        return match (true) {
            array_key_exists('enunciado', $q) => 'contexto',
            array_key_exists('resumo', $q) => 'resolucoes',
            array_key_exists('tipologia', $q) => 'ledger',
            default => null,
        };
    }

    /** @return array resumo (ver gravar) */
    public function importarArquivo(string $arquivo, ?int $userId = null): array
    {
        $lido = $this->ler($arquivo);
        if (!in_array($lido['tipo'], ['ledger', 'resolucoes'], true)) {
            throw new TecParserException('Não reconheci o arquivo como ledger nem como resoluções do Gemini (JSON inválido ou incompleto).');
        }

        // O validador lê de arquivo: grava o JSON limpo num temporário com o nome do tipo.
        $tmp = storage_path('framework/cache/gemini-' . uniqid() . "-{$lido['tipo']}.json");
        file_put_contents($tmp, $lido['json']);
        try {
            $this->validador->validar($tmp, $lido['tipo']);
        } finally {
            @unlink($tmp);
        }

        $salvo = $this->tec->guardarOrigem($arquivo);
        try {
            return $this->gravar($lido['dados'], $lido['tipo'], $arquivo, $salvo, $userId);
        } catch (TecParserException $e) {
            Importacao::create([
                'tipo' => $lido['tipo'], 'arquivo_original' => $arquivo, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'status' => 'recusada', 'user_id' => $userId,
                'resumo' => ['erro' => $e->getMessage(), 'problemas' => $e->problemas],
            ]);

            throw $e;
        }
    }

    /**
     * @param  'ledger'|'resolucoes'  $tipo
     * @param  array{caminho: string, sha256: string}  $salvo
     * @return array{tipo: string, lote_id: int, lote: string, questoes: int, subtopicos_novos: list<string>,
     *     flags: array<string, list<string>>, lote_status: string, sem_ledger: int, sem_resolucao: int}
     */
    public function gravar(array $dados, string $tipo, string $arquivoOriginal, array $salvo, ?int $userId = null): array
    {
        $lote = $this->lote($dados);
        $doLote = $lote->questoes()->with('alternativas')->get()->keyBy('id_externo');
        $subtopicos = Subtopico::where('assunto_id', $lote->assunto_id)->get()->keyBy('slug');

        $problemas = [];
        $vistos = [];
        foreach ($dados['questoes'] as $item) {
            $id = $item['id_externo'];
            $q = $doLote[$id] ?? null;
            if (isset($vistos[$id])) {
                $problemas[] = "Questão {$id} aparece duas vezes.";
            }
            $vistos[$id] = true;
            if (!$q) {
                $problemas[] = "Questão {$id} não faz parte do lote {$lote->numero} de \"{$lote->assunto->nome}\".";
                continue;
            }
            if ($q->gabarito === null) {
                $problemas[] = "Questão {$id} está anulada no TEC.";
            } elseif ($item['gabarito_conferido'] !== $q->gabarito) {
                $problemas[] = "Questão {$id}: o Gemini usou gabarito {$item['gabarito_conferido']}, mas o do TEC é {$q->gabarito}.";
            }
            array_push($problemas, ...($tipo === 'ledger' ? $this->checarLedger($item, $subtopicos) : $this->checarResolucao($item, $q)));
        }
        if ($problemas) {
            throw new TecParserException('Arquivo do Gemini recusado (nada foi gravado).', $problemas);
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($dados, $tipo, $lote, $doLote, $subtopicos, $arquivoOriginal, $salvo, $userId) {
            $imp = Importacao::create([
                'tipo' => $tipo, 'arquivo_original' => $arquivoOriginal, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'status' => 'ok', 'user_id' => $userId,
                'referencia' => mb_strimwidth("{$lote->assunto->nome} - lote {$lote->numero}", 0, 250, '…'),
            ]);
            $resumo = ['tipo' => $tipo, 'lote_id' => $lote->id, 'lote' => "{$lote->assunto->nome} - lote {$lote->numero}",
                'questoes' => count($dados['questoes']), 'subtopicos_novos' => [], 'flags' => []];

            foreach ($dados['questoes'] as $item) {
                $q = $doLote[$item['id_externo']];
                if ($tipo === 'ledger') {
                    $this->gravarLedger($q, $item, $lote, $imp, $subtopicos, $resumo);
                } else {
                    QuestaoResolucao::updateOrCreate(['questao_id' => $q->id], [
                        'fonte' => 'ia', 'autor' => null, 'lote_id' => $lote->id, 'importacao_id' => $imp->id,
                        'resumo' => $item['resumo'], 'fundamentacao' => $item['fundamentacao'] ?? null,
                        'dispositivos' => $item['dispositivos'] ?? null, 'alternativas' => $item['alternativas'],
                    ]);
                }
            }

            $ids = $doLote->pluck('id');
            $resumo['sem_ledger'] = $ids->count() - QuestaoClassificacao::whereIn('questao_id', $ids)->count();
            $resumo['sem_resolucao'] = $ids->count() - QuestaoResolucao::whereIn('questao_id', $ids)->count();
            $resumo['lote_status'] = match (true) {
                !$resumo['sem_ledger'] && !$resumo['sem_resolucao'] => 'completo',
                !$resumo['sem_ledger'] => 'ledger',
                default => 'enviado',
            };
            $lote->update(['status' => $resumo['lote_status']]);
            $imp->update(['resumo' => $resumo]);

            return $resumo;
        });
    }

    private function lote(array $dados): Lote
    {
        $ref = $dados['assunto'];
        $disc = Disciplina::where('nome', $ref['disciplina'])->first();
        $assunto = $disc ? Assunto::where('disciplina_id', $disc->id)->where('nome', $ref['assunto'])->first() : null;
        if ($assunto?->unido_em_id) {
            $assunto = Assunto::find($assunto->unido_em_id);
        }
        $lote = $assunto ? Lote::with('assunto')->where('assunto_id', $assunto->id)->where('numero', $dados['lote']['numero'])->first() : null;
        if (!$lote) {
            throw new TecParserException("Não existe o lote {$dados['lote']['numero']} de \"{$ref['disciplina']} - {$ref['assunto']}\". Confira se o Gemini copiou o assunto do pacote sem mudar nada.");
        }

        return $lote;
    }

    private function checarLedger(array $item, $subtopicos): array
    {
        $problemas = [];
        foreach ($item['subtopicos'] as $s) {
            if (isset($s['id']) && !isset($subtopicos[$s['id']])) {
                $problemas[] = "Questão {$item['id_externo']}: subtópico \"{$s['id']}\" não existe neste assunto.";
            }
        }

        return $problemas;
    }

    private function checarResolucao(array $item, Questao $q): array
    {
        $id = $item['id_externo'];
        $problemas = [];
        $esperadas = $q->tipo === 'CE' ? ['Certo', 'Errado'] : $q->alternativas->pluck('letra')->all();
        $letras = array_column($item['alternativas'], 'letra');
        if (array_diff($letras, $esperadas) || count($letras) !== count(array_unique($letras))) {
            $problemas[] = "Questão {$id}: alternativas " . implode(',', $letras) . ' não batem com as da questão (' . implode(',', $esperadas) . ').';
        }
        $corretas = array_column(array_filter($item['alternativas'], fn ($a) => $a['correta']), 'letra');
        if ($q->gabarito !== null && $corretas !== [$q->gabarito]) {
            $problemas[] = "Questão {$id}: a resolução marca como correta " . (implode(',', $corretas) ?: 'nenhuma') . ", mas o gabarito é {$q->gabarito}.";
        }

        return $problemas;
    }

    private function gravarLedger(Questao $q, array $item, Lote $lote, Importacao $imp, $subtopicos, array &$resumo): void
    {
        QuestaoClassificacao::updateOrCreate(['questao_id' => $q->id], [
            'lote_id' => $lote->id, 'importacao_id' => $imp->id,
            'tipologia' => $item['tipologia'], 'dificuldade' => $item['dificuldade'], 'ponto_cobrado' => $item['ponto_cobrado'],
            'comando' => $item['comando'] ?? null,
            'distrator_letra' => $item['distrator_principal']['letra'] ?? null,
            'distrator_mecanismo' => $item['distrator_principal']['mecanismo'] ?? null,
            'dispositivos' => $item['dispositivos'] ?? null, 'flags' => $item['flags'] ?? null,
            'duplicada_de' => $item['duplicada_de'] ?? null, 'observacoes' => $item['observacoes'] ?? null,
        ]);
        if (!empty($item['flags'])) {
            $resumo['flags'][$q->id_externo] = $item['flags'];
        }

        $vinculos = [];
        foreach ($item['subtopicos'] as $i => $s) {
            if (isset($s['id'])) {
                $sub = $subtopicos[$s['id']];
            } else {
                $slug = Str::limit(Str::slug($s['novo']), 80, '');
                $sub = $subtopicos[$slug] ?? null;
                if (!$sub) {
                    $sub = Subtopico::create(['assunto_id' => $lote->assunto_id, 'slug' => $slug,
                        'nome' => mb_substr($s['novo'], 0, 150), 'descricao' => $s['justificativa'], 'status' => 'pendente']);
                    $subtopicos[$slug] = $sub;
                    $resumo['subtopicos_novos'][] = $sub->nome;
                }
            }
            $vinculos[$sub->id] ??= ['principal' => $i === 0];
        }
        $q->subtopicos()->sync($vinculos);
    }
}
