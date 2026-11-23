<?php

namespace App\Services\Concursos\Livro;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Models\Concursos\MaterialQuestao;
use App\Models\Concursos\MaterialSecao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Monta o livro de um material a partir do que já existe (seções de caderno, pontos e questões):
 * - capítulos pela árvore de assuntos do TEC (Assunto + pai), achados pelo assunto das questões;
 * - sem assunto conhecido, o capítulo é a seção do caderno;
 * - cada ponto vira um bloco "ponto de erro" com as caixas Pegadinha/Regra/Macete/Base legal;
 * - cada questão fica ligada ao capítulo do seu assunto.
 * Pode rodar de novo: capítulos têm chave estável e pontos/questões já no livro não se mexem.
 */
class ConversorLivro
{
    /** Profundidade máxima da árvore no sumário (raiz, …, assunto). */
    private const NIVEIS = 3;

    public function __construct(private readonly Documento $documento)
    {
    }

    /** @return array{capitulos: int, pontos: int, questoes: int} */
    public function converter(Material $material): array
    {
        return DB::connection('concursos_pgsql')->transaction(function () use ($material) {
            $existentes = $material->capitulos()->pluck('id')->all();
            $r = ['pontos' => 0, 'questoes' => 0];

            // 1. Questões ainda fora do livro: capítulo do assunto (ou da seção).
            $questoes = MaterialQuestao::where('material_id', $material->id)->whereNull('capitulo_id')->with('secao')->get();
            foreach ($questoes as $q) {
                $cap = $this->capituloDoAssunto($material, $q->assuntoTec()) ?? $this->capituloDaSecao($material, $q->secao);
                $q->update(['capitulo_id' => $cap?->id, 'assunto' => $q->assunto ?? $q->assuntoTec()]);
                $r['questoes']++;
            }

            // 2. Pontos de seção: vão para o capítulo onde estão as questões deles (ou o da seção).
            $pontos = MaterialPonto::whereNull('capitulo_id')
                ->whereIn('secao_id', $material->secoes()->pluck('id'))
                ->with('secao')->orderBy('secao_id')->orderBy('ordem')->orderBy('id')->get();
            $porCapitulo = [];
            foreach ($pontos as $p) {
                $capId = MaterialQuestao::where('ponto_id', $p->id)->whereNotNull('capitulo_id')
                    ->selectRaw('capitulo_id, count(*) as n')->groupBy('capitulo_id')->orderByDesc('n')->value('capitulo_id');
                $cap = $capId ? MaterialCapitulo::find($capId) : $this->capituloDaSecao($material, $p->secao);
                $porCapitulo[$cap->id][] = $p;
            }
            foreach ($porCapitulo as $capId => $lista) {
                $cap = MaterialCapitulo::find($capId);
                $doc = $cap->conteudo ?? ['type' => 'doc', 'content' => []];
                $conteudo = $this->semParagrafoVazio($doc['content'] ?? []);
                foreach ($lista as $p) {
                    $conteudo[] = $this->blocoDoPonto($p);
                    $p->update(['capitulo_id' => $cap->id]);
                    $r['pontos']++;
                }
                $this->salvar($cap, ['type' => 'doc', 'content' => $conteudo]);
            }

            // 3. Introdução da seção (padrões do caderno) no começo do capítulo dela, quando ele acabou de nascer.
            foreach ($material->secoes()->get() as $secao) {
                $cap = MaterialCapitulo::where('material_id', $material->id)->where('chave', 's' . $secao->id)->first();
                if ($cap && $secao->intro && !in_array($cap->id, $existentes, true)) {
                    $doc = $cap->conteudo ?? Documento::vazio();
                    $intro = ['type' => 'caixa', 'attrs' => ['tipo' => 'atencao'], 'content' => $this->documento->deMarkdown($secao->intro) ?: [['type' => 'paragraph']]];
                    $this->salvar($cap, ['type' => 'doc', 'content' => [$intro, ...$this->semParagrafoVazio($doc['content'] ?? [])]]);
                }
            }

            return $r + ['capitulos' => $material->capitulos()->count() - count($existentes)];
        });
    }

    /** Capítulo (e pais) do assunto do TEC; cria o que faltar. "A, B" usa o primeiro assunto. */
    public function capituloDoAssunto(Material $material, ?string $assuntoTec): ?MaterialCapitulo
    {
        $nome = trim(explode(', ', (string) $assuntoTec)[0]);
        if ($nome === '') {
            return null;
        }

        $assunto = Assunto::where('nome', $nome)
            ->when($material->disciplina_id, fn ($q, $d) => $q->orderByRaw('case when disciplina_id = ? then 0 else 1 end', [$d]))
            ->orderBy('id')->first();
        if (!$assunto) {
            return $this->capitulo($material, null, 't-' . Str::limit(Str::slug($nome), 70, ''), $nome);
        }

        $cadeia = [$assunto];
        for ($a = $assunto, $guarda = 0; $a->parent_id && $guarda < 20; $guarda++) {
            $a = $a->pai;
            if (!$a) {
                break;
            }
            array_unshift($cadeia, $a);
        }
        if (count($cadeia) > self::NIVEIS) {
            $cadeia = [$cadeia[0], ...array_slice($cadeia, -(self::NIVEIS - 1))];
        }

        $pai = null;
        foreach ($cadeia as $a) {
            $pai = $this->capitulo($material, $pai, 'a' . $a->id, $a->nome, $a->id);
        }

        return $pai;
    }

    public function capituloDaSecao(Material $material, ?MaterialSecao $secao): ?MaterialCapitulo
    {
        if (!$secao) {
            return $this->capitulo($material, null, 'outros', 'Outros assuntos');
        }

        return $this->capitulo($material, null, 's' . $secao->id, $secao->titulo);
    }

    private function capitulo(Material $material, ?MaterialCapitulo $pai, string $chave, string $titulo, ?int $assuntoId = null): MaterialCapitulo
    {
        $cap = MaterialCapitulo::where('material_id', $material->id)->where('chave', $chave)->first();
        if ($cap) {
            return $cap;
        }
        $ordem = MaterialCapitulo::where('material_id', $material->id)->where('parent_id', $pai?->id)->max('ordem') ?? 0;

        return MaterialCapitulo::create([
            'material_id' => $material->id,
            'parent_id' => $pai?->id,
            'assunto_id' => $assuntoId,
            'chave' => $chave,
            'titulo' => mb_substr($titulo, 0, 250),
            'ordem' => $ordem + 1,
            'conteudo' => Documento::vazio(),
            'texto' => '',
        ]);
    }

    private function blocoDoPonto(MaterialPonto $p): array
    {
        $caixas = [];
        foreach (['armadilha' => 'pegadinha', 'regra' => 'regra', 'macete' => 'macete', 'base_legal' => 'base'] as $campo => $tipo) {
            if ($nos = $this->documento->deMarkdown($p->{$campo})) {
                $caixas[] = ['type' => 'caixa', 'attrs' => ['tipo' => $tipo], 'content' => $nos];
            }
        }
        if ($p->questoes) {
            $caixas[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Questões do TEC: ' . implode(', ', $p->questoes), 'marks' => [['type' => 'italic']]]]];
        }

        return ['type' => 'pontoErro', 'attrs' => ['pontoId' => $p->id, 'titulo' => $p->titulo], 'content' => $caixas ?: [['type' => 'paragraph']]];
    }

    private function salvar(MaterialCapitulo $cap, array $doc): void
    {
        $doc = $this->documento->limpar($doc);
        $cap->update(['conteudo' => $doc, 'texto' => $this->documento->texto($doc)]);
    }

    private function semParagrafoVazio(array $conteudo): array
    {
        return array_values(array_filter($conteudo, fn ($n) => !(($n['type'] ?? '') === 'paragraph' && empty($n['content']))));
    }
}
