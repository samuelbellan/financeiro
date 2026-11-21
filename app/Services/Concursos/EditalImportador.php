<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Edital;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\Importacao;
use Illuminate\Support\Facades\DB;

/**
 * Importa um edital verticalizado (edital.schema.json, um cargo por arquivo).
 *  - Idempotente: edital por (órgão, número), cargo por código, área/disciplina por nome,
 *    tópico por "ordem". O que sumiu do arquivo é removido; o que ficou é atualizado.
 *  - assuntos_sugeridos viram vínculos "sugerido" tópico → assunto (criando o assunto canônico se
 *    ainda não existir). Vínculos já "confirmado" na curadoria são preservados.
 */
class EditalImportador
{
    public function __construct(
        private ContratoJsonValidator $validador,
        private TecImportador $tec,
    ) {}

    public function importarArquivo(string $arquivo, ?int $userId = null): array
    {
        $salvo = $this->tec->guardarOrigem($arquivo);
        $dados = $this->validador->validar($arquivo, 'edital');

        return $this->gravar($dados, $arquivo, $salvo, $userId);
    }

    /** @param  array{caminho: string, sha256: string}  $salvo */
    public function gravar(array $d, string $arquivoOriginal, array $salvo, ?int $userId = null): array
    {
        return DB::connection(config('concursos.connection'))->transaction(function () use ($d, $arquivoOriginal, $salvo, $userId) {
            $e = $d['edital'];
            $imp = Importacao::create([
                'tipo' => 'edital', 'arquivo_original' => $arquivoOriginal, 'arquivo_salvo' => $salvo['caminho'],
                'sha256' => $salvo['sha256'], 'referencia' => "{$e['orgao']} {$e['numero']} - cargo {$d['cargo']['codigo']}",
                'status' => 'ok', 'user_id' => $userId,
            ]);

            $edital = Edital::updateOrCreate(
                ['orgao' => $e['orgao'], 'numero' => $e['numero']],
                [
                    'banca' => $e['banca'], 'data_publicacao' => $e['data_publicacao'] ?? null, 'data_prova' => $e['data_prova'],
                    'duracao_min' => $e['duracao_min'] ?? null, 'tipo_questao' => $e['tipo_questao'] ?? null,
                    'nota_minima' => $e['nota_minima'] ?? null,
                ]
            );

            $cargo = EditalCargo::updateOrCreate(
                ['edital_id' => $edital->id, 'codigo' => $d['cargo']['codigo']],
                ['nome' => $d['cargo']['nome'], 'vagas' => $d['cargo']['vagas'] ?? null, 'nivel' => $d['cargo']['nivel'] ?? null,
                    'importacao_id' => $imp->id]
            );

            $resumo = ['edital' => "{$e['orgao']} - {$e['numero']}", 'cargo' => "{$d['cargo']['codigo']} - {$d['cargo']['nome']}",
                'data_prova' => $e['data_prova'], 'areas' => 0, 'disciplinas' => 0, 'topicos' => 0, 'pontos_totais' => 0,
                'topicos_sem_assunto' => 0, 'topicos_sem_questoes_tec' => 0, 'assuntos_criados' => [],
                'assuntos_a_confirmar' => 0, 'assuntos_ambiguos' => []];
            $resolver = new AssuntoResolver;

            $areasVistas = [];
            foreach ($d['areas'] as $ia => $a) {
                $area = $cargo->areas()->updateOrCreate(['nome' => $a['nome']], [
                    'n_questoes' => $a['n_questoes'], 'pontos_por_questao' => $a['pontos_por_questao'],
                    'minimo_pontos' => $a['minimo_pontos'] ?? null, 'posicao' => $ia + 1,
                ]);
                $areasVistas[] = $area->id;
                $resumo['areas']++;
                $resumo['pontos_totais'] += $a['n_questoes'] * $a['pontos_por_questao'];

                $discVistas = [];
                foreach ($a['disciplinas'] as $id => $disc) {
                    $disciplina = $area->disciplinas()->updateOrCreate(['nome' => $disc['nome']], ['posicao' => $id + 1]);
                    $discVistas[] = $disciplina->id;
                    $resumo['disciplinas']++;

                    $topVistos = [];
                    foreach ($disc['topicos'] as $it => $tp) {
                        $topico = $disciplina->topicos()->updateOrCreate(['ordem' => $tp['ordem']], [
                            'texto' => $tp['texto'], 'normas' => $tp['normas'] ?? null,
                            'sem_questoes_tec' => $tp['sem_questoes_tec'] ?? false, 'posicao' => $it + 1,
                        ]);
                        $topVistos[] = $topico->id;
                        $resumo['topicos']++;

                        $this->vincularAssuntos($topico, $tp['assuntos_sugeridos'] ?? [], $resolver);

                        if ($topico->sem_questoes_tec) {
                            $resumo['topicos_sem_questoes_tec']++;
                        } elseif (!$topico->assuntos()->exists()) {
                            $resumo['topicos_sem_assunto']++;
                        }
                    }
                    $disciplina->topicos()->whereNotIn('id', $topVistos)->delete();
                }
                $area->disciplinas()->whereNotIn('id', $discVistas)->delete();
            }
            $cargo->areas()->whereNotIn('id', $areasVistas)->delete();

            $resumo['assuntos_criados'] = $resolver->criados;
            $resumo['assuntos_ambiguos'] = array_values(array_unique($resolver->ambiguos));
            $resumo['assuntos_a_confirmar'] = count(array_filter(
                $resolver->criados,
                fn ($a) => str_starts_with($a, AssuntoResolver::DISCIPLINA_A_CONFIRMAR)
            ));
            $imp->update(['resumo' => $resumo]);

            return ['importacao_id' => $imp->id, 'cargo_id' => $cargo->id, 'resumo' => $resumo];
        });
    }

    private function vincularAssuntos($topico, array $sugeridos, AssuntoResolver $resolver): void
    {
        foreach ($sugeridos as $s) {
            $assunto = is_string($s)
                ? $resolver->porNome($s)
                : $resolver->porDisciplina($s['disciplina'], $s['assunto']);
            if (!$assunto) {
                continue; // nome ambíguo: fica no resumo para a curadoria
            }

            // Não rebaixa um vínculo já confirmado na curadoria.
            if (!$topico->assuntos()->where('assuntos.id', $assunto->id)->exists()) {
                $topico->assuntos()->attach($assunto->id, ['status' => 'sugerido']);
            }
        }
    }
}
