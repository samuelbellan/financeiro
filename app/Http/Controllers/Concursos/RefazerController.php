<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialTentativa;
use App\Services\Concursos\Leis\Citacoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Refazer as questões erradas dos cadernos, dentro do material de resumo: escolhe caderno, assunto
 * (ponto do resumo), situação e ordem; responde uma por vez e vê gabarito, regra e comentário.
 */
class RefazerController extends Controller
{
    public const SITUACOES = [
        'errando' => 'Ainda erro (nunca refeitas ou erradas na última vez)',
        'nunca' => 'Nunca refeitas',
        'todas' => 'Todas',
    ];

    public function index(Request $request, Material $material): View
    {
        abort_unless($material->user_id === $request->user()->id, 404);
        $f = $request->validate([
            'secao' => ['nullable', 'integer'],
            'ponto' => ['nullable', 'integer'],
            'situacao' => ['nullable', Rule::in(array_keys(self::SITUACOES))],
            'ordem' => ['nullable', Rule::in(['caderno', 'aleatoria'])],
            'qtd' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]) + ['situacao' => 'errando', 'ordem' => 'caderno', 'qtd' => 20];

        $todas = MaterialQuestao::with(['secao', 'ponto', 'ultimaTentativa'])
            ->where('material_id', $material->id)
            ->get()
            ->sortBy(fn ($q) => sprintf('%05d-%05d', $q->secao->ordem, $q->ordem))
            ->values();

        $filtradas = $todas
            ->when($f['secao'] ?? null, fn ($c, $s) => $c->where('secao_id', $s))
            ->when($f['ponto'] ?? null, fn ($c, $p) => $c->where('ponto_id', $p))
            ->filter(fn ($q) => match ($f['situacao']) {
                'nunca' => $q->ultimaTentativa === null,
                'errando' => $q->ultimaTentativa === null || !$q->ultimaTentativa->acertou,
                default => true,
            })
            ->values();
        if ($f['ordem'] === 'aleatoria') {
            $filtradas = $filtradas->shuffle();
        }
        $fila = $request->boolean('iniciar') ? $filtradas->take($f['qtd'])->values() : collect();

        return view('concursos.material.refazer', [
            'material' => $material,
            'f' => $f,
            'resumo' => $this->resumo($todas),
            'assuntos' => $todas->whereNotNull('ponto_id')->groupBy('ponto_id')
                ->map(fn ($qs) => ['id' => $qs->first()->ponto_id, 'titulo' => $qs->first()->ponto->titulo, 'secao' => $qs->first()->secao->titulo, 'n' => $qs->count()])
                ->sortBy('titulo')->values(),
            'disponiveis' => $filtradas->count(),
            'fila' => $fila->map(fn ($q) => $this->paraTela($q))->all(),
        ]);
    }

    public function responder(Request $request, MaterialQuestao $questao, Citacoes $citacoes): JsonResponse
    {
        abort_unless($questao->material->user_id === $request->user()->id, 404);
        $dados = $request->validate(['resposta' => ['required', Rule::in($questao->opcoes())]]);

        $acertou = $dados['resposta'] === $questao->gabarito;
        MaterialTentativa::create([
            'questao_id' => $questao->id,
            'user_id' => $request->user()->id,
            'resposta' => $dados['resposta'],
            'acertou' => $acertou,
        ]);

        $ponto = $questao->ponto;
        if (!$acertou && $ponto) {
            // Errou de novo: o ponto do resumo volta para "ainda erro" e conta o erro.
            $ponto->update(['vezes_errado' => $ponto->vezes_errado + 1, 'status' => 'errando', 'ultimo_erro_em' => today()]);
        }

        return response()->json([
            'acertou' => $acertou,
            'gabarito' => $questao->gabarito,
            'comentario' => $questao->comentario,
            'tec' => $questao->id_externo ? 'https://www.tecconcursos.com.br/questoes/' . $questao->id_externo : null,
            'ponto' => $ponto ? [
                'titulo' => $ponto->titulo,
                'armadilha' => $citacoes->html($ponto->armadilha),
                'regra' => $citacoes->html($ponto->regra),
                'base_legal' => $citacoes->html($ponto->base_legal),
                'url' => route('concursos.material.show', $questao->material_id) . '#ponto-' . $ponto->id,
            ] : null,
        ]);
    }

    /** Por caderno: total, ainda erro, acertadas na última vez. */
    private function resumo(Collection $todas): Collection
    {
        return $todas->groupBy('secao_id')->map(fn ($qs) => [
            'id' => $qs->first()->secao_id,
            'titulo' => $qs->first()->secao->titulo,
            'total' => $qs->count(),
            'errando' => $qs->filter(fn ($q) => !$q->ultimaTentativa?->acertou)->count(),
            'acertadas' => $qs->filter(fn ($q) => $q->ultimaTentativa?->acertou)->count(),
        ])->values();
    }

    private function paraTela(MaterialQuestao $q): array
    {
        // Itens "I. … II. …" que o PDF juntou numa linha voltam a ficar um por linha.
        $quebra = fn (string $t) => preg_replace('/\s+(?=(?:I|II|III|IV|V|VI)\s?[.\-–)]\s)/u', "\n", $t);

        return [
            'id' => $q->id,
            'url' => route('concursos.material.questoes.responder', $q),
            'caderno' => $q->secao->titulo,
            'assunto' => $q->ponto?->titulo,
            'origem' => $q->origem,
            'tipo' => $q->tipo,
            'enunciado' => $quebra($q->enunciado),
            'alternativas' => $q->tipo === 'ME' ? array_map(fn ($a) => ['letra' => $a['letra'], 'texto' => $quebra($a['texto'])], $q->alternativas ?? []) : [['letra' => 'C', 'texto' => 'Certo'], ['letra' => 'E', 'texto' => 'Errado']],
            'antes' => $q->ultimaTentativa ? ($q->ultimaTentativa->acertou ? 'acertou' : 'errou') . ' em ' . $q->ultimaTentativa->created_at->format('d/m') : null,
        ];
    }
}
