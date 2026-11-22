<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Simulado;
use App\Models\Concursos\Tentativa;
use Illuminate\Support\Facades\DB;

/**
 * Monta, guarda e corrige simulados no formato do edital.
 *  - Cada área recebe o nº de questões do quadro de provas (× 1/3 no rápido).
 *  - Dentro da área, as vagas vão para os tópicos na proporção do valor (incidência do TEC),
 *    por maiores restos; tópico sem questões cede a vaga aos outros.
 *  - Prefere questões que o aluno nunca respondeu e que não caíram em simulados anteriores.
 *  - Na entrega, resposta vira tentativa (origem "simulado"; marcada como chute → certeza "chute",
 *    senão "acho"). Questão em branco conta como erro na nota, mas não vira tentativa.
 */
class SimuladoService
{
    public function __construct(private MotorMeta $motor) {}

    public function criar(Perfil $perfil, string $tipo): Simulado
    {
        $fator = Simulado::TIPOS[$tipo]['fator'];
        $cargo = $perfil->cargo()->with('edital', 'areas')->first();
        $indices = $this->motor->indices($perfil, today())->groupBy('area');

        $respondidas = Tentativa::where('user_id', $perfil->user_id)->distinct()->pluck('questao_id')->flip();
        $emSimulados = Simulado::where('user_id', $perfil->user_id)->pluck('questao_ids')->flatten()->flip();
        $usadas = [];
        $composicao = [];

        foreach ($cargo->areas as $area) {
            $previstas = max(1, (int) round($area->n_questoes * $fator));
            $topicos = ($indices[$area->nome] ?? collect())->filter(fn ($i) => $i['questoes'])->values();
            $escolhidas = $this->distribuir($topicos->all(), $previstas, $usadas, $respondidas, $emSimulados);
            array_push($usadas, ...$escolhidas);
            $composicao[] = ['area' => $area->nome, 'pontos_por_questao' => (float) $area->pontos_por_questao,
                'previstas' => $previstas, 'questao_ids' => $escolhidas];
        }

        // O tempo acompanha as questões que de fato entraram (área sem questões no banco não consome tempo).
        $previstas = array_sum(array_column($composicao, 'previstas'));
        $proporcao = $previstas ? count($usadas) / $previstas : 0;
        $duracao = (int) max(10, round(($cargo->edital->duracao_min ?: 240) * $fator * $proporcao));

        return Simulado::create([
            'user_id' => $perfil->user_id, 'cargo_id' => $cargo->id, 'tipo' => $tipo,
            'composicao' => $composicao, 'questao_ids' => $usadas, 'respostas' => [],
            'duracao_min' => $duracao, 'iniciado_em' => now(), 'prazo_em' => now()->addMinutes($duracao),
        ]);
    }

    /** @return list<int> */
    private function distribuir(array $topicos, int $vagas, array $usadas, $respondidas, $emSimulados): array
    {
        $usadasSet = array_flip($usadas);
        // Questões livres por tópico, na ordem de preferência (inédita para o aluno primeiro).
        $livres = [];
        foreach ($topicos as $i => $t) {
            $ids = array_values(array_filter($t['questoes'], fn ($q) => !isset($usadasSet[$q])));
            shuffle($ids);
            usort($ids, fn ($a, $b) => [isset($respondidas[$a]), isset($emSimulados[$a])] <=> [isset($respondidas[$b]), isset($emSimulados[$b])]);
            $livres[$i] = $ids;
        }

        // Vagas por maiores restos sobre o valor; o que um tópico não consegue preencher vai para os outros.
        $cotas = array_fill_keys(array_keys($topicos), 0);
        $restante = $vagas;
        while ($restante > 0) {
            $ativos = array_filter(array_keys($topicos), fn ($i) => count($livres[$i]) > $cotas[$i]);
            if (!$ativos) {
                break;
            }
            $soma = array_sum(array_map(fn ($i) => $topicos[$i]['valor'], $ativos)) ?: 1;
            $alvo = [];
            foreach ($ativos as $i) {
                $alvo[$i] = $restante * $topicos[$i]['valor'] / $soma;
            }
            $dadas = 0;
            foreach ($ativos as $i) {
                $n = min((int) floor($alvo[$i]), count($livres[$i]) - $cotas[$i]);
                $cotas[$i] += $n;
                $dadas += $n;
            }
            if ($dadas === 0) {
                arsort($alvo); // sobra: uma por vez, para quem tem o maior resto
                foreach (array_keys($alvo) as $i) {
                    if ($restante - $dadas <= 0) {
                        break;
                    }
                    if (count($livres[$i]) > $cotas[$i]) {
                        $cotas[$i]++;
                        $dadas++;
                    }
                }
            }
            $restante -= $dadas;
        }

        $saida = [];
        foreach ($cotas as $i => $n) {
            foreach (array_slice($livres[$i], 0, $n) as $q) {
                if (!in_array($q, $saida, true)) {
                    $saida[] = $q;
                }
            }
        }
        shuffle($saida);

        return $saida;
    }

    /** Guarda respostas parciais (autosave). Só aceita questões do simulado e letras da questão. */
    public function salvarRespostas(Simulado $s, array $respostas): void
    {
        $letras = Questao::with('alternativas')->whereIn('id', $s->questao_ids)->get()
            ->mapWithKeys(fn ($q) => [$q->id => $q->alternativas->pluck('letra')->all()]);
        $atual = $s->respostas ?? [];
        foreach ($respostas as $qid => $r) {
            $qid = (int) $qid;
            if (!isset($letras[$qid])) {
                continue;
            }
            $letra = is_array($r) ? ($r['r'] ?? null) : $r;
            if ($letra === null || $letra === '') {
                unset($atual[$qid]);
            } elseif (in_array($letra, $letras[$qid], true)) {
                $atual[$qid] = ['r' => $letra, 'chute' => (bool) (is_array($r) ? ($r['chute'] ?? false) : false)];
            }
        }
        $s->update(['respostas' => $atual]);
    }

    public function entregar(Simulado $s): Simulado
    {
        if (!$s->emAndamento()) {
            return $s;
        }

        return DB::connection(config('concursos.connection'))->transaction(function () use ($s) {
            $questoes = Questao::whereIn('id', $s->questao_ids)->get()->keyBy('id');
            $respostas = $s->respostas ?? [];
            $resultado = [];
            $acertos = 0;
            $nota = 0.0;
            $pontos = 0.0;

            foreach ($s->composicao as $c) {
                $ac = 0;
                foreach ($c['questao_ids'] as $qid) {
                    $q = $questoes[$qid] ?? null;
                    $r = $respostas[$qid] ?? $respostas[(string) $qid] ?? null;
                    if (!$q || !$r) {
                        continue;
                    }
                    $correta = !$q->anulada && $r['r'] === $q->gabarito;
                    Tentativa::create([
                        'user_id' => $s->user_id, 'questao_id' => $q->id, 'resposta' => $r['r'], 'gabarito' => $q->gabarito ?? '-',
                        'correta' => $correta, 'certeza' => $r['chute'] ? 'chute' : 'acho', 'origem' => 'simulado', 'simulado_id' => $s->id,
                    ]);
                    $ac += $correta ? 1 : 0;
                }
                $feitas = count($c['questao_ids']);
                $resultado[] = ['area' => $c['area'], 'previstas' => $c['previstas'], 'feitas' => $feitas, 'acertos' => $ac,
                    'pontos' => $ac * $c['pontos_por_questao'], 'pontos_max' => $feitas * $c['pontos_por_questao'],
                    'pontos_prova' => $c['previstas'] * $c['pontos_por_questao']];
                $acertos += $ac;
                $nota += $ac * $c['pontos_por_questao'];
                $pontos += $feitas * $c['pontos_por_questao'];
            }

            $s->update(['finalizado_em' => now(), 'acertos' => $acertos, 'nota_total' => $nota, 'pontos_total' => $pontos, 'resultado' => $resultado]);

            return $s;
        });
    }
}
