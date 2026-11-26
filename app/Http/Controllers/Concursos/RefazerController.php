<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialTentativa;
use App\Services\Concursos\Leis\Citacoes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Área de Questões do material: a fila de revisão do dia (caixas 1 → 7 → 30 dias) e "Explorar"
 * (todas as questões dos cadernos, certas e erradas, com filtros). Responde uma por vez e vê o
 * gabarito, o ponto do resumo (armadilha, regra, macete) e a caixa para onde a questão foi.
 */
class RefazerController extends Controller
{
    public const SITUACOES = [
        'fila' => 'Fila de hoje (revisões vencidas)',
        'errando' => 'Ainda erro',
        'revisando' => 'Revisando (acertei, ainda na fila)',
        'dominado' => 'Dominadas',
        'erradas_tec' => 'Erradas no TEC',
        'certas_tec' => 'Certas no TEC',
        'nunca' => 'Nunca refeitas no app',
        'todas' => 'Todas',
    ];

    public function index(Request $request, Material $material): View
    {
        abort_unless($material->user_id === $request->user()->id, 404);
        $f = $request->validate([
            'secao' => ['nullable', 'integer'],
            'ponto' => ['nullable', 'integer'],
            'capitulo' => ['nullable', 'integer'],
            'banca' => ['nullable', 'string', 'max:80'],
            'situacao' => ['nullable', Rule::in(array_keys(self::SITUACOES))],
            'ordem' => ['nullable', Rule::in(['caderno', 'aleatoria'])],
            'qtd' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]) + ['situacao' => 'fila', 'ordem' => 'caderno', 'qtd' => 20];

        $todas = MaterialQuestao::with(['secao', 'ponto', 'capitulo', 'ultimaTentativa'])
            ->where('material_id', $material->id)
            ->get()
            ->sortBy(fn ($q) => sprintf('%05d-%05d', $q->secao?->ordem ?? 0, $q->ordem))
            ->values();

        $filtradas = $todas
            ->when($f['secao'] ?? null, fn ($c, $s) => $c->where('secao_id', $s))
            ->when($f['ponto'] ?? null, fn ($c, $p) => $c->where('ponto_id', $p))
            ->when($f['capitulo'] ?? null, fn ($c, $cap) => $c->whereIn('capitulo_id', $this->comSubcapitulos($material, (int) $cap)))
            ->when($f['banca'] ?? null, fn ($c, $b) => $c->filter(fn ($q) => self::banca($q) === $b))
            ->filter(fn ($q) => match ($f['situacao']) {
                'fila' => $q->naFilaHoje(),
                'errando', 'revisando', 'dominado' => $q->estado() === $f['situacao'],
                'erradas_tec' => $q->resultado_tec === 'errada',
                'certas_tec' => $q->resultado_tec === 'certa',
                'nunca' => $q->ultimaTentativa === null,
                default => true,
            })
            ->values();
        if ($f['situacao'] === 'fila') {
            // Caixas mais baixas (erros mais recentes) e revisões mais atrasadas primeiro.
            $filtradas = $filtradas->sortBy(fn ($q) => sprintf('%d-%s', $q->caixa, $q->revisar_em->format('Ymd')))->values();
        }
        if ($f['ordem'] === 'aleatoria') {
            $filtradas = $filtradas->shuffle();
        }
        $fila = $request->boolean('iniciar') ? $filtradas->take($f['qtd'])->values() : collect();

        return view('concursos.material.refazer', [
            'material' => $material,
            'f' => $f,
            'painel' => $this->painel($todas),
            'resumo' => $this->resumo($todas),
            'assuntos' => $todas->whereNotNull('ponto_id')->groupBy('ponto_id')
                ->map(fn ($qs) => ['id' => $qs->first()->ponto_id, 'titulo' => $qs->first()->ponto->titulo, 'n' => $qs->count()])
                ->sortBy('titulo')->values(),
            'capitulos' => $todas->whereNotNull('capitulo_id')->groupBy('capitulo_id')
                ->map(fn ($qs) => ['id' => $qs->first()->capitulo_id, 'titulo' => $qs->first()->capitulo->titulo, 'n' => $qs->count()])
                ->sortBy('titulo')->values(),
            'bancas' => $todas->map(fn ($q) => self::banca($q))->filter()->countBy()->sortKeys(),
            'disponiveis' => $filtradas->count(),
            'fila' => $fila->map(fn ($q) => $this->paraTela($q))->all(),
        ]);
    }

    public function responder(Request $request, MaterialQuestao $questao, Citacoes $citacoes): JsonResponse
    {
        abort_unless($questao->material->user_id === $request->user()->id, 404);
        $dados = $request->validate([
            'resposta' => ['required', Rule::in($questao->opcoes())],
            'certeza' => ['nullable', Rule::in(array_keys(MaterialQuestao::CERTEZAS))],
        ]);

        $acertou = $dados['resposta'] === $questao->gabarito;
        $caixaAntes = $questao->caixa;
        MaterialTentativa::create([
            'questao_id' => $questao->id,
            'user_id' => $request->user()->id,
            'resposta' => $dados['resposta'],
            'acertou' => $acertou,
            'certeza' => $dados['certeza'] ?? null,
        ]);
        $questao->registrarResposta($acertou, $dados['certeza'] ?? null);

        $pontos = $this->pontosDa($questao);
        if (!$acertou) {
            foreach ($pontos as $p) {
                $p->update(['vezes_errado' => $p->vezes_errado + 1, 'ultimo_erro_em' => today()]);
            }
        }

        return response()->json([
            'acertou' => $acertou,
            'gabarito' => $questao->gabarito,
            'comentario' => $questao->comentario,
            'tec' => $questao->id_externo ? 'https://www.tecconcursos.com.br/questoes/' . $questao->id_externo : null,
            'caixa' => $this->textoCaixa($caixaAntes, $questao->fresh()),
            'pontos' => $pontos->map(fn ($p) => [
                'titulo' => $p->titulo,
                'etiqueta' => $p->etiqueta,
                'armadilha' => $citacoes->html($p->armadilha),
                'regra' => $citacoes->html($p->regra),
                'macete' => $citacoes->html($p->macete),
                'base_legal' => $citacoes->html($p->base_legal),
                'url' => MaterialController::urlDoPonto($p),
            ])->values(),
            'livro' => $questao->capitulo_id ? route('concursos.material.capitulo', [$questao->material_id, $questao->capitulo_id]) : null,
        ]);
    }

    /** Pontos do resumo ligados à questão: o dela e os que citam o nº do TEC (baterias diferentes). */
    private function pontosDa(MaterialQuestao $q): Collection
    {
        $pontos = Material::pontosDe($q->material_id)->get();

        return $pontos->filter(fn (MaterialPonto $p) => $p->id === $q->ponto_id
            || ($q->id_externo && in_array($q->id_externo, array_map('strval', $p->questoes ?? []), true)))
            ->sortByDesc(fn ($p) => $p->id === $q->ponto_id)->values();
    }

    private function textoCaixa(?int $antes, MaterialQuestao $q): ?string
    {
        if (!$q->caixa) {
            return null;
        }
        if ($q->caixa > 3) {
            return 'Passou pelas três caixas: saiu da fila (dominada).';
        }
        $quando = $q->revisar_em->isTomorrow() ? 'amanhã' : 'em ' . (int) today()->diffInDays($q->revisar_em) . ' dias (' . $q->revisar_em->format('d/m') . ')';
        $mov = $antes && $antes !== $q->caixa ? "Caixa {$antes} → {$q->caixa}" : "Caixa {$q->caixa}";

        return "{$mov}: volta {$quando}.";
    }

    /** @return list<int> o capítulo e todos os subcapítulos dele, do mesmo livro */
    private function comSubcapitulos(Material $material, int $capituloId): array
    {
        $ids = MaterialCapitulo::where('material_id', $material->id)->whereKey($capituloId)->pluck('id')->all();
        for ($nivel = $ids; $nivel; ) {
            $nivel = MaterialCapitulo::where('material_id', $material->id)->whereIn('parent_id', $nivel)->pluck('id')->all();
            array_push($ids, ...$nivel);
        }

        return $ids ?: [0];
    }

    /** Números do topo: fila de hoje por caixa, próximos dias e situação de todas. */
    private function painel(Collection $todas): array
    {
        $naFila = $todas->filter(fn ($q) => $q->caixa >= 1 && $q->caixa <= 3);
        $hoje = $naFila->filter(fn ($q) => $q->naFilaHoje());

        return [
            'hoje' => $hoje->count(),
            'porCaixa' => [1 => $hoje->where('caixa', 1)->count(), 2 => $hoje->where('caixa', 2)->count(), 3 => $hoje->where('caixa', 3)->count()],
            'amanha' => $naFila->filter(fn ($q) => $q->revisar_em?->isTomorrow())->count(),
            'semana' => $naFila->filter(fn ($q) => $q->revisar_em && $q->revisar_em->gt(today()) && $q->revisar_em->lte(today()->addDays(7)))->count(),
            'estados' => $todas->countBy(fn ($q) => $q->estado())->all() + ['errando' => 0, 'revisando' => 0, 'dominado' => 0],
            'total' => $todas->count(),
        ];
    }

    /** Por caderno: total e situação calculada. */
    private function resumo(Collection $todas): Collection
    {
        return $todas->groupBy('secao_id')->map(fn ($qs) => [
            'id' => $qs->first()->secao_id,
            'titulo' => $qs->first()->secao?->titulo ?? '—',
            'total' => $qs->count(),
            'errando' => $qs->filter(fn ($q) => $q->estado() === 'errando')->count(),
            'revisando' => $qs->filter(fn ($q) => $q->estado() === 'revisando')->count(),
            'dominado' => $qs->filter(fn ($q) => $q->estado() === 'dominado')->count(),
        ])->values();
    }

    /** "CESPE · 2024 · …" → banca (primeiro pedaço da origem). */
    private static function banca(MaterialQuestao $q): ?string
    {
        $b = trim(explode(' · ', (string) $q->origem)[0]);

        return $b !== '' && !preg_match('/^\d{4}$/', $b) ? $b : null;
    }

    private function paraTela(MaterialQuestao $q): array
    {
        // Itens "I. … II. …" que o PDF juntou numa linha voltam a ficar um por linha.
        $quebra = fn (string $t) => preg_replace('/\s+(?=(?:I|II|III|IV|V|VI)\s?[.\-–)]\s)/u', "\n", $t);
        $antes = $q->ultimaTentativa
            ? ($q->ultimaTentativa->acertou ? 'acertou' : 'errou') . ' no app em ' . $q->ultimaTentativa->created_at->format('d/m')
            : ($q->resultado_tec ? ($q->resultado_tec === 'certa' ? 'acertou' : 'errou') . ' no TEC' : null);

        return [
            'id' => $q->id,
            'url' => route('concursos.material.questoes.responder', $q),
            'caderno' => $q->secao?->titulo,
            'assunto' => $q->ponto?->titulo ?? $q->capitulo?->titulo,
            'origem' => $q->origem,
            'tipo' => $q->tipo,
            'caixa' => $q->caixa >= 1 && $q->caixa <= 3 ? $q->caixa : null,
            'enunciado' => $quebra($q->enunciado),
            'alternativas' => $q->tipo === 'ME' ? array_map(fn ($a) => ['letra' => $a['letra'], 'texto' => $quebra($a['texto'])], $q->alternativas ?? []) : [['letra' => 'C', 'texto' => 'Certo'], ['letra' => 'E', 'texto' => 'Errado']],
            'antes' => $antes,
        ];
    }
}
