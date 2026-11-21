<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Lote;
use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoClassificacao;
use App\Models\Concursos\QuestaoResolucao;
use App\Models\Concursos\Subtopico;
use App\Services\Concursos\GeminiImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Questões e respostas inventadas: o repositório é público. */
class GeminiImportadorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private Assunto $assunto;

    private Lote $lote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBancoConcursos();
        $d = Disciplina::create(['nome' => 'Gestão Inventada']);
        $this->assunto = Assunto::create(['disciplina_id' => $d->id, 'nome' => 'Estoques Inventados']);
        $this->lote = Lote::create(['assunto_id' => $this->assunto->id, 'numero' => 1, 'arquivo' => 'x.json']);
        foreach (['101' => 'B', '102' => 'D'] as $id => $gab) {
            $q = Questao::create(['fonte' => 'tec', 'id_externo' => $id, 'banca' => 'BANCA X', 'banca_sigla' => 'BANCA X',
                'ano' => 2024, 'tipo' => 'ME', 'enunciado' => "Enunciado {$id}.", 'gabarito' => $gab]);
            foreach (['A', 'B', 'C', 'D'] as $o => $l) {
                $q->alternativas()->create(['letra' => $l, 'texto' => "alt {$l}", 'ordem' => $o + 1]);
            }
            $this->lote->questoes()->attach($q->id, ['ordem' => (int) $id - 100]);
        }
    }

    private function base(array $questoes): array
    {
        return ['schema_versao' => '1', 'assunto' => ['disciplina' => 'Gestão Inventada', 'assunto' => 'Estoques Inventados'],
            'lote' => ['numero' => 1, 'parte' => 1, 'total_partes' => 1], 'questoes' => $questoes];
    }

    private function ledger(string $id, string $gab, ?array $subtopicos = null, array $extra = []): array
    {
        return array_merge(['id_externo' => $id, 'gabarito_conferido' => $gab,
            'subtopicos' => $subtopicos ?? [['novo' => 'Ponto de pedido', 'justificativa' => 'Cálculo do ponto de pedido.']],
            'tipologia' => 'calculo_direto', 'dificuldade' => 'B', 'ponto_cobrado' => 'Ponto de pedido = consumo no lead time + segurança.',
            'distrator_principal' => ['letra' => 'A', 'mecanismo' => 'esquece o estoque de segurança'], 'comando' => 'calcule'], $extra);
    }

    private function resolucao(string $id, string $gab, ?string $correta = null): array
    {
        return ['id_externo' => $id, 'gabarito_conferido' => $gab, 'resumo' => 'Resumo inventado da questão.',
            'alternativas' => array_map(fn ($l) => ['letra' => $l, 'correta' => $l === ($correta ?? $gab), 'explicacao' => "Explica {$l}."], ['A', 'B', 'C', 'D'])];
    }

    private function gravar(array $dados, string $tipo): array
    {
        return app(GeminiImportador::class)->gravar($dados, $tipo, 'resp.txt', ['caminho' => 'x', 'sha256' => str_repeat('c', 64)]);
    }

    private function recusa(array $dados, string $tipo, string $trecho): void
    {
        try {
            $this->gravar($dados, $tipo);
            $this->fail('Deveria recusar.');
        } catch (TecParserException $e) {
            $this->assertStringContainsString($trecho, $e->getMessage());
        }
    }

    public function test_reconhece_o_tipo_pelo_conteudo_mesmo_com_cerca_de_codigo(): void
    {
        $f = storage_path('framework/testing/resp-' . uniqid() . '.txt');
        @mkdir(dirname($f), 0775, true);
        $imp = app(GeminiImportador::class);

        file_put_contents($f, "Aqui está:\n```json\n" . json_encode($this->base([$this->ledger('101', 'B')])) . "\n```\nPronto.");
        $this->assertSame('ledger', $imp->ler($f)['tipo']);
        file_put_contents($f, json_encode($this->base([$this->resolucao('101', 'B')])));
        $this->assertSame('resolucoes', $imp->ler($f)['tipo']);
        file_put_contents($f, '{"questoes": [{"id_externo": "1", "enunciado": "x"}]}');
        $this->assertSame('contexto', $imp->ler($f)['tipo']);
        file_put_contents($f, '{ cortado');
        $this->assertNull($imp->ler($f)['tipo']);
        unlink($f);
    }

    public function test_ledger_grava_classificacao_e_cria_subtopico_pendente_uma_vez(): void
    {
        $r = $this->gravar($this->base([
            $this->ledger('101', 'B', extra: ['flags' => ['ilegivel']]),
            $this->ledger('102', 'D', [['novo' => 'Ponto de pedido', 'justificativa' => 'Repetido.'], ['novo' => 'Curva ABC', 'justificativa' => 'Classe A.']]),
        ]), 'ledger');

        $this->assertSame(['Ponto de pedido', 'Curva ABC'], $r['subtopicos_novos']);
        $this->assertSame(['101' => ['ilegivel']], $r['flags']);
        $this->assertSame('ledger', $r['lote_status']);
        $this->assertSame(2, $r['sem_resolucao']);
        $this->assertSame(2, Subtopico::where('assunto_id', $this->assunto->id)->where('status', 'pendente')->count());

        $q = Questao::where('id_externo', '102')->first();
        $this->assertSame('calculo_direto', $q->classificacao->tipologia);
        $this->assertSame('A', $q->classificacao->distrator_letra);
        $this->assertSame(['ponto-de-pedido' => 1, 'curva-abc' => 0],
            $q->subtopicos->mapWithKeys(fn ($s) => [$s->slug => (int) $s->pivot->principal])->all());
        $this->assertTrue(Questao::where('id_externo', '101')->first()->classificacao->temFlag('ilegivel'));

        // Reimportar sobrescreve, sem duplicar.
        $this->gravar($this->base([$this->ledger('101', 'B', extra: ['dificuldade' => 'A'])]), 'ledger');
        $this->assertSame(2, QuestaoClassificacao::count());
        $this->assertSame('A', Questao::where('id_externo', '101')->first()->classificacao->dificuldade);
        $this->assertSame(2, Subtopico::count());
    }

    public function test_subtopico_aprovado_por_id_e_id_inexistente_recusado(): void
    {
        Subtopico::create(['assunto_id' => $this->assunto->id, 'slug' => 'giro', 'nome' => 'Giro', 'status' => 'aprovado']);
        $this->gravar($this->base([$this->ledger('101', 'B', [['id' => 'giro']])]), 'ledger');
        $this->assertSame(['giro'], Questao::where('id_externo', '101')->first()->subtopicos->pluck('slug')->all());

        $this->recusa($this->base([$this->ledger('102', 'D', [['id' => 'nao-existe']])]), 'ledger', 'subtópico "nao-existe"');
    }

    public function test_recusa_o_arquivo_inteiro_se_o_gabarito_diverge_ou_a_questao_nao_e_do_lote(): void
    {
        $this->recusa($this->base([$this->ledger('101', 'B'), $this->ledger('102', 'C')]), 'ledger', 'o do TEC é D');
        $this->recusa($this->base([$this->ledger('101', 'B'), $this->ledger('999', 'A')]), 'ledger', 'Questão 999 não faz parte');
        $this->assertSame(0, QuestaoClassificacao::count());
        $this->assertSame(0, Subtopico::count());

        $outro = $this->base([$this->ledger('101', 'B')]);
        $outro['lote']['numero'] = 7;
        $this->recusa($outro, 'ledger', 'Não existe o lote 7');
    }

    public function test_resolucoes_conferem_a_alternativa_correta_e_completam_o_lote(): void
    {
        $this->recusa($this->base([$this->resolucao('101', 'B', correta: 'C')]), 'resolucoes', 'marca como correta C');
        $ruim = $this->resolucao('102', 'D');
        $ruim['alternativas'][] = ['letra' => 'E', 'correta' => false, 'explicacao' => 'Inventada.'];
        $this->recusa($this->base([$ruim]), 'resolucoes', 'não batem');

        $this->gravar($this->base([$this->ledger('101', 'B'), $this->ledger('102', 'D')]), 'ledger');
        $r = $this->gravar($this->base([$this->resolucao('101', 'B')]), 'resolucoes');
        $this->assertSame('ledger', $r['lote_status']);
        $r = $this->gravar($this->base([$this->resolucao('102', 'D')]), 'resolucoes');
        $this->assertSame('completo', $r['lote_status']);
        $this->assertSame('completo', $this->lote->fresh()->status);
        $this->assertSame('Explica D.', QuestaoResolucao::where('questao_id', Questao::where('id_externo', '102')->value('id'))->first()->explicacao('D'));
    }
}
