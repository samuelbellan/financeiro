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
                        // Caderno de erros: entra como errada no TEC, na caixa 1 da fila, vencida hoje.
                        MaterialQuestao::create($campos + ['material_id' => $material->id, 'chave' => $q['chave'],
                            'resultado_tec' => 'errada', 'caixa' => 1, 'revisar_em' => today()->toDateString()]);
                        $r['novas']++;
                    }
                }
            }

            return $r;
        });
    }

    /**
     * Todas as questões resolvidas nos cadernos (converter_tec.py --todas, com "resultado": certa | errada).
     * As que já existem no material (pelo nº do TEC) mantêm texto, ponto e tentativas: só ganham o
     * resultado do TEC (errada vence certa, porque a mesma questão pode ter caído em dois cadernos).
     * As novas entram sem capítulo; erradas novas entram na caixa 1 da fila, vencidas hoje.
     *
     * @return array{novas: int, certas: int, existentes: int, puladas: int, secoes_criadas: int}
     */
    public function importarTodas(array $dados, int $userId, ?string $slug = null, string $prefixoSecao = ''): array
    {
        $slug ??= $dados['material'] ?? '';
        $material = Material::where('user_id', $userId)->where('slug', $slug)->first();
        if (!$material) {
            throw new InvalidArgumentException("Material \"{$slug}\" não encontrado para esse usuário.");
        }

        return DB::connection('concursos_pgsql')->transaction(function () use ($dados, $material, $prefixoSecao) {
            $r = ['novas' => 0, 'certas' => 0, 'existentes' => 0, 'puladas' => 0, 'secoes_criadas' => 0];
            $secoes = $material->secoes()->get()->keyBy('chave');
            $pontos = Material::pontosDe($material->id)->get();
            $porId = MaterialQuestao::where('material_id', $material->id)->whereNotNull('id_externo')->get()->keyBy('id_externo');
            $hoje = today()->toDateString();

            foreach ($dados['secoes'] ?? [] as $s) {
                $chave = $prefixoSecao . ($s['secao'] ?? '');
                $secao = $secoes[$chave] ?? null;
                if (!$secao) {
                    $secao = $material->secoes()->create([
                        'chave' => $chave,
                        'titulo' => ($s['titulo'] ?? '') !== '' ? $s['titulo'] : strtoupper($chave),
                        'origem' => $s['origem'] ?? null,
                        'ordem' => $this->abrirOrdem($secoes, $chave),
                    ]);
                    $secoes[$chave] = $secao;
                    $r['secoes_criadas']++;
                }
                foreach (array_values($s['questoes'] ?? []) as $i => $q) {
                    $resultado = ($q['resultado'] ?? '') === 'certa' ? 'certa' : 'errada';
                    $id = (string) ($q['id'] ?? '');
                    if ($id !== '' && ($existente = $porId[$id] ?? null)) {
                        if ($existente->resultado_tec === null || ($resultado === 'errada' && $existente->resultado_tec === 'certa')) {
                            $existente->update(['resultado_tec' => $resultado] + ($resultado === 'errada' && !$existente->caixa ? ['caixa' => 1, 'revisar_em' => $hoje] : []));
                        }
                        $r['existentes']++;
                        continue;
                    }
                    if (!empty($q['incompleta']) || empty($q['chave']) || empty($q['enunciado']) || empty($q['gabarito'])) {
                        $r['puladas']++;
                        continue;
                    }
                    $tipo = $q['tipo'] === 'CE' ? 'CE' : 'ME';
                    $nova = MaterialQuestao::create([
                        'material_id' => $material->id,
                        'chave' => $q['chave'],
                        'secao_id' => $secao->id,
                        'ponto_id' => $this->pontoDa($q, $pontos)?->id,
                        'id_externo' => $id !== '' ? $id : null,
                        'origem' => ($q['origem'] ?? '') !== '' ? mb_substr($q['origem'], 0, 250) : null,
                        'assunto' => ($q['assunto'] ?? '') !== '' ? mb_substr($q['assunto'], 0, 500) : null,
                        'tipo' => $tipo,
                        'enunciado' => $q['enunciado'],
                        'alternativas' => $tipo === 'ME' ? array_values($q['alternativas']) : null,
                        'gabarito' => strtoupper($q['gabarito'])[0],
                        'comentario' => $q['comentario'] ?? null,
                        'ordem' => $i + 1,
                        'resultado_tec' => $resultado,
                        'caixa' => $resultado === 'errada' ? 1 : null,
                        'revisar_em' => $resultado === 'errada' ? $hoje : null,
                    ]);
                    if ($id !== '') {
                        $porId[$id] = $nova;
                    }
                    $r['novas']++;
                    $r['certas'] += $resultado === 'certa' ? 1 : 0;
                }
            }

            return $r;
        });
    }

    /**
     * Ordem de uma seção nova de caderno (c04, tc04…): logo depois da irmã de número menor com o mesmo
     * prefixo, empurrando as seguintes; sem irmãs, no fim do livro. Assim o C04 que chega depois cai
     * entre o C03 e o C05, e os grupos do Tributário (c… em 1-13, tc… em 101+) não se misturam.
     */
    private function abrirOrdem($secoes, string $chave): int
    {
        if (!preg_match('/^(.*?)c(\d+)$/', $chave, $m)) {
            return (int) $secoes->max('ordem') + 1;
        }
        $num = (int) $m[2];
        $padrao = '/^' . preg_quote($m[1], '/') . 'c(\d+)$/';
        $numeros = [];
        foreach ($secoes as $s) {
            if (preg_match($padrao, $s->chave, $x)) {
                $numeros[$s->chave] = (int) $x[1];
            }
        }
        if (!$numeros) {
            return (int) $secoes->max('ordem') + 1;
        }
        asort($numeros);
        $antes = $depois = null;
        foreach ($numeros as $ch => $n) {
            if ($n < $num) {
                $antes = $secoes[$ch];
            } elseif ($depois === null) {
                $depois = $secoes[$ch];
            }
        }
        $ordem = $antes ? $antes->ordem + 1 : $depois->ordem;
        foreach ($numeros as $ch => $n) {
            if ($n > $num) {
                $secoes[$ch]->increment('ordem');
            }
        }

        return $ordem;
    }

    /** Ponto do resumo que explica a questão: mesma chave (c05-q02) ou o nº do TEC na lista do ponto. */
    private function pontoDa(array $q, $pontos): ?MaterialPonto
    {
        return $pontos->firstWhere('chave', $q['chave'])
            ?? (($q['id'] ?? '') !== '' ? $pontos->first(fn ($p) => in_array((string) $q['id'], $p->questoes ?? [], true)) : null);
    }
}
