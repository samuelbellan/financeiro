<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialSecao;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importa um material de resumo a partir de JSON (gerado dos cadernos de erros).
 * Reimportar atualiza os textos, mas preserva o que o aluno fez na tela: status, vezes que errou,
 * data do último erro e os pontos criados à mão.
 *
 * Formato:
 * {"slug": "dtrib", "titulo": "...", "descricao": "...", "disciplina": "Direito Tributário",
 *  "secoes": [{"chave": "c04", "titulo": "...", "origem": "...", "intro": "md",
 *              "pontos": [{"chave": "q3845754", "titulo": "...", "armadilha": "md", "regra": "md",
 *                          "macete": "md", "base_legal": "md com [[ctn 98]]", "questoes": ["3845754"]}]}]}
 */
class MaterialImportador
{
    /** @return array{material: Material, secoes: int, novos: int, atualizados: int} */
    public function importar(array $dados, int $userId): array
    {
        foreach (['slug', 'titulo', 'secoes'] as $campo) {
            if (empty($dados[$campo])) {
                throw new InvalidArgumentException("JSON sem o campo \"{$campo}\".");
            }
        }

        return DB::connection('concursos_pgsql')->transaction(function () use ($dados, $userId) {
            $disciplinaId = isset($dados['disciplina'])
                ? Disciplina::where('nome', $dados['disciplina'])->value('id')
                : null;

            $material = Material::updateOrCreate(
                ['user_id' => $userId, 'slug' => $dados['slug']],
                ['titulo' => $dados['titulo'], 'descricao' => $dados['descricao'] ?? null, 'disciplina_id' => $disciplinaId],
            );

            $novos = $atualizados = 0;
            foreach (array_values($dados['secoes']) as $i => $s) {
                if (empty($s['chave']) || empty($s['titulo'])) {
                    throw new InvalidArgumentException('Seção ' . ($i + 1) . ' sem "chave" ou "titulo".');
                }
                $secao = MaterialSecao::updateOrCreate(
                    ['material_id' => $material->id, 'chave' => $s['chave']],
                    ['titulo' => $s['titulo'], 'origem' => $s['origem'] ?? null, 'intro' => $s['intro'] ?? null, 'ordem' => $i + 1],
                );

                foreach (array_values($s['pontos'] ?? []) as $j => $p) {
                    if (empty($p['chave']) || empty($p['titulo']) || empty($p['regra'])) {
                        throw new InvalidArgumentException("Ponto " . ($j + 1) . " da seção {$s['chave']} sem \"chave\", \"titulo\" ou \"regra\".");
                    }
                    $textos = [
                        'titulo' => $p['titulo'],
                        'armadilha' => $p['armadilha'] ?? null,
                        'regra' => $p['regra'],
                        'macete' => $p['macete'] ?? null,
                        'base_legal' => $p['base_legal'] ?? null,
                        'questoes' => array_values(array_map('strval', $p['questoes'] ?? [])) ?: null,
                        'ordem' => $j + 1,
                    ];
                    $ponto = MaterialPonto::where('secao_id', $secao->id)->where('chave', $p['chave'])->first();
                    if ($ponto) {
                        $ponto->update($textos);
                        $atualizados++;
                    } else {
                        MaterialPonto::create($textos + [
                            'secao_id' => $secao->id,
                            'chave' => $p['chave'],
                            'origem' => 'caderno',
                            'status' => $p['status'] ?? 'errando',
                            'vezes_errado' => $p['vezes_errado'] ?? 1,
                        ]);
                        $novos++;
                    }
                }
            }

            return ['material' => $material, 'secoes' => count($dados['secoes']), 'novos' => $novos, 'atualizados' => $atualizados];
        });
    }
}
