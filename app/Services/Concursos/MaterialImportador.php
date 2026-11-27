<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
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

                [$n, $a] = $this->gravarPontos($secao, $s['pontos'] ?? []);
                $novos += $n;
                $atualizados += $a;
            }

            return ['material' => $material, 'secoes' => count($dados['secoes']), 'novos' => $novos, 'atualizados' => $atualizados];
        });
    }

    /**
     * Pontos de UMA seção que já existe (concursos:importar-pontos, para o caderno que chegou depois):
     * não mexe nas outras seções nem nos pontos que não estão no arquivo. Os pontos novos entram no fim
     * da seção, e as questões do material listadas em cada ponto passam a apontar para ele.
     *
     * Formato: lista de {"chave": "c04", "titulo"?, "intro"?, "pontos": [...]} (o mesmo das partes).
     *
     * @return array{secoes: int, novos: int, atualizados: int, ligadas: int}
     */
    public function importarPontos(Material $material, array $partes): array
    {
        return DB::connection('concursos_pgsql')->transaction(function () use ($material, $partes) {
            $r = ['secoes' => 0, 'novos' => 0, 'atualizados' => 0, 'ligadas' => 0];
            foreach ($partes as $s) {
                $secao = $material->secoes()->where('chave', $s['chave'] ?? '')->first();
                if (!$secao) {
                    throw new InvalidArgumentException('Seção "' . ($s['chave'] ?? '') . "\" não existe no material {$material->slug}: importe o caderno antes (concursos:tec-novos).");
                }
                $secao->update(array_filter(['titulo' => $s['titulo'] ?? null, 'intro' => $s['intro'] ?? null], fn ($v) => $v !== null && $v !== ''));
                [$n, $a, $pontos] = $this->gravarPontos($secao, $s['pontos'] ?? [], false);
                $r['secoes']++;
                $r['novos'] += $n;
                $r['atualizados'] += $a;
                foreach ($pontos as $ponto) {
                    $r['ligadas'] += MaterialQuestao::where('material_id', $material->id)
                        ->whereIn('id_externo', $ponto->questoes ?? [])
                        ->update(['ponto_id' => $ponto->id]);
                }
            }

            return $r;
        });
    }

    /**
     * Cria ou atualiza os pontos de uma seção pela chave. Com $ordemDaLista, a ordem é a do arquivo
     * (importação do material inteiro); sem, os existentes mantêm a ordem e os novos vão para o fim.
     *
     * @return array{0: int, 1: int, 2: list<MaterialPonto>}
     */
    private function gravarPontos(MaterialSecao $secao, array $lista, bool $ordemDaLista = true): array
    {
        $novos = $atualizados = 0;
        $gravados = [];
        $proxima = (int) MaterialPonto::where('secao_id', $secao->id)->max('ordem') + 1;
        foreach (array_values($lista) as $j => $p) {
            if (empty($p['chave']) || empty($p['titulo']) || empty($p['regra'])) {
                throw new InvalidArgumentException("Ponto " . ($j + 1) . " da seção {$secao->chave} sem \"chave\", \"titulo\" ou \"regra\".");
            }
            $textos = [
                'titulo' => $p['titulo'],
                'armadilha' => $p['armadilha'] ?? null,
                'regra' => $p['regra'],
                'macete' => $p['macete'] ?? null,
                'base_legal' => $p['base_legal'] ?? null,
                'questoes' => array_values(array_map('strval', $p['questoes'] ?? [])) ?: null,
            ];
            $ponto = MaterialPonto::where('secao_id', $secao->id)->where('chave', $p['chave'])->first();
            if ($ponto) {
                $ponto->update($textos + ($ordemDaLista ? ['ordem' => $j + 1] : []));
                $atualizados++;
            } else {
                $ponto = MaterialPonto::create($textos + [
                    'secao_id' => $secao->id,
                    'chave' => $p['chave'],
                    'ordem' => $ordemDaLista ? $j + 1 : $proxima++,
                    'origem' => 'caderno',
                    'status' => $p['status'] ?? 'errando',
                    'vezes_errado' => $p['vezes_errado'] ?? 1,
                ]);
                $novos++;
            }
            $gravados[] = $ponto;
        }

        return [$novos, $atualizados, $gravados];
    }
}
