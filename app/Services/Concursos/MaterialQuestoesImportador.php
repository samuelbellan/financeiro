<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importa as questões erradas dos cadernos (saída de scripts/concursos/extrair_caderno_erros.py,
 * uma ou várias seções) para um material de resumo já importado. Reimportar atualiza o texto e
 * mantém as tentativas. Questões marcadas "incompleta" ficam de fora.
 *
 * Formato: {"material": "dtrib", "secoes": [{"secao": "c04", "questoes": [{chave, id, origem, assunto, tipo,
 *           enunciado, alternativas, gabarito, comentario, incompleta}]}]}
 * As questões novas entram sem capítulo; o livro as encaixa no capítulo do assunto ao ser aberto.
 */
class MaterialQuestoesImportador
{
    /** @return array{novas: int, atualizadas: int, puladas: int, sem_ponto: list<string>} */
    public function importar(array $dados, int $userId): array
    {
        $material = Material::where('user_id', $userId)->where('slug', $dados['material'] ?? '')->first();
        if (!$material) {
            throw new InvalidArgumentException('Material "' . ($dados['material'] ?? '') . '" não encontrado para esse usuário. Importe o resumo antes.');
        }
        $secoes = $material->secoes()->get()->keyBy('chave');
        $pontos = MaterialPonto::whereIn('secao_id', $secoes->pluck('id'))->get();

        return DB::connection('concursos_pgsql')->transaction(function () use ($dados, $material, $secoes, $pontos) {
            $r = ['novas' => 0, 'atualizadas' => 0, 'puladas' => 0, 'sem_ponto' => []];
            foreach ($dados['secoes'] ?? [] as $s) {
                $secao = $secoes[$s['secao'] ?? ''] ?? null;
                if (!$secao) {
                    throw new InvalidArgumentException('Seção "' . ($s['secao'] ?? '') . '" não existe no material.');
                }
                foreach (array_values($s['questoes'] ?? []) as $i => $q) {
                    if (!empty($q['incompleta']) || empty($q['chave']) || empty($q['enunciado']) || empty($q['gabarito'])) {
                        $r['puladas']++;
                        continue;
                    }
                    $tipo = $q['tipo'] === 'CE' ? 'CE' : 'ME';
                    $ponto = $this->pontoDa($q, $pontos);
                    if (!$ponto) {
                        $r['sem_ponto'][] = $q['chave'];
                    }
                    $campos = [
                        'secao_id' => $secao->id,
                        'ponto_id' => $ponto?->id,
                        'id_externo' => ($q['id'] ?? '') !== '' ? (string) $q['id'] : null,
                        'origem' => ($q['origem'] ?? '') !== '' ? mb_substr($q['origem'], 0, 250) : null,
                        'assunto' => ($q['assunto'] ?? '') !== '' ? mb_substr($q['assunto'], 0, 500) : null,
                        'tipo' => $tipo,
                        'enunciado' => $q['enunciado'],
                        'alternativas' => $tipo === 'ME' ? array_values($q['alternativas']) : null,
                        'gabarito' => strtoupper($q['gabarito'])[0],
                        'comentario' => $q['comentario'] ?? null,
                        'ordem' => $i + 1,
                    ];
                    $existente = MaterialQuestao::where('material_id', $material->id)->where('chave', $q['chave'])->first();
                    if ($existente) {
                        $existente->update($campos);
                        $r['atualizadas']++;
                    } else {
                        MaterialQuestao::create($campos + ['material_id' => $material->id, 'chave' => $q['chave']]);
                        $r['novas']++;
                    }
                }
            }

            return $r;
        });
    }

    /** Ponto do resumo que explica a questão: mesma chave (c05-q02) ou o nº do TEC na lista do ponto. */
    private function pontoDa(array $q, $pontos): ?MaterialPonto
    {
        return $pontos->firstWhere('chave', $q['chave'])
            ?? (($q['id'] ?? '') !== '' ? $pontos->first(fn ($p) => in_array((string) $q['id'], $p->questoes ?? [], true)) : null);
    }
}
