<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Subtopico;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Curadoria dos subtópicos propostos pelo Gemini (3º nível da taxonomia).
 * Aprovado = entra na lista fechada que vai nos próximos pacotes (o Gemini passa a usar o id).
 * Juntar move as questões para o destino; excluir só desfaz o vínculo (a questão continua no assunto).
 */
class CuradoriaSubtopicos
{
    private function db()
    {
        return DB::connection(config('concursos.connection'));
    }

    public function aprovar(Subtopico $s, ?string $nome = null, ?string $descricao = null): void
    {
        $dados = ['status' => 'aprovado'];
        if ($nome !== null && trim($nome) !== '') {
            $dados['nome'] = mb_substr(trim($nome), 0, 150);
        }
        if ($descricao !== null) {
            $dados['descricao'] = trim($descricao) ?: null;
        }
        $s->update($dados);
    }

    /** Junta $origem em $destino (mesma disciplina). As questões passam para o destino e a origem some. */
    public function juntar(Subtopico $origem, Subtopico $destino): int
    {
        if ($origem->id === $destino->id) {
            throw new \InvalidArgumentException('Escolha outro subtópico para juntar.');
        }
        $disc = Assunto::whereKey([$origem->assunto_id, $destino->assunto_id])->pluck('disciplina_id')->unique();
        if ($disc->count() !== 1) {
            throw new \InvalidArgumentException('Só dá para juntar subtópicos da mesma disciplina.');
        }

        return $this->db()->transaction(function () use ($origem, $destino) {
            $links = $this->db()->table('questao_subtopico')->where('subtopico_id', $origem->id)->get();
            $jaNoDestino = $this->db()->table('questao_subtopico')->where('subtopico_id', $destino->id)
                ->whereIn('questao_id', $links->pluck('questao_id'))->pluck('principal', 'questao_id');
            foreach ($links as $l) {
                if (!$jaNoDestino->has($l->questao_id)) {
                    $this->db()->table('questao_subtopico')->insert(['questao_id' => $l->questao_id, 'subtopico_id' => $destino->id, 'principal' => $l->principal]);
                } elseif ($l->principal && !$jaNoDestino[$l->questao_id]) {
                    $this->db()->table('questao_subtopico')->where(['questao_id' => $l->questao_id, 'subtopico_id' => $destino->id])->update(['principal' => true]);
                }
            }
            if (!$destino->descricao && $origem->descricao) {
                $destino->update(['descricao' => $origem->descricao]);
            }
            $origem->delete(); // cascade apaga os vínculos antigos

            return $links->count();
        });
    }

    /** Exclui o subtópico. Questão que o tinha como principal promove o próximo subtópico dela. */
    public function excluir(Subtopico $s): void
    {
        $this->db()->transaction(function () use ($s) {
            $principais = $this->db()->table('questao_subtopico')->where('subtopico_id', $s->id)->where('principal', true)->pluck('questao_id');
            $s->delete();
            foreach ($principais as $qid) {
                $prox = $this->db()->table('questao_subtopico')->where('questao_id', $qid)->orderBy('subtopico_id')->value('subtopico_id');
                if ($prox) {
                    $this->db()->table('questao_subtopico')->where(['questao_id' => $qid, 'subtopico_id' => $prox])->update(['principal' => true]);
                }
            }
        });
    }

    /**
     * Subtópicos da mesma disciplina com nome parecido (candidatos a juntar).
     *
     * @return array<int, list<Subtopico>> subtopico_id => parecidos
     */
    public function parecidos(Collection $daqui, Collection $daDisciplina): array
    {
        $tokens = fn (string $nome) => collect(explode('-', Str::slug(preg_replace('/\([^)]*\)/u', '', $nome))))
            ->reject(fn ($w) => strlen($w) < 3 || in_array($w, ['das', 'dos', 'para', 'com', 'por', 'sua', 'seus', 'suas', 'art', 'arts', 'lei'], true))
            ->map(fn ($w) => substr($w, 0, 6))->unique()->values()->all();
        $cache = [];
        $saida = [];
        foreach ($daqui as $s) {
            $a = $cache[$s->id] ??= $tokens($s->nome);
            foreach ($daDisciplina as $o) {
                if ($o->id === $s->id) {
                    continue;
                }
                $b = $cache[$o->id] ??= $tokens($o->nome);
                $uniao = count(array_unique([...$a, ...$b]));
                if ($uniao && count(array_intersect($a, $b)) / $uniao >= 0.5) {
                    $saida[$s->id][] = $o;
                }
            }
        }

        return $saida;
    }
}
