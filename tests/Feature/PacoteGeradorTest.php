<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;
use App\Models\Concursos\Lote;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Subtopico;
use App\Services\Concursos\ContratoJsonValidator;
use App\Services\Concursos\PacoteGerador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

/** Questões inventadas: o repositório é público. */
class PacoteGeradorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    private int $seq = 1000;

    private Disciplina $disc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBancoConcursos();
        $this->disc = Disciplina::create(['nome' => 'Direito Inventado']);
    }

    private function assunto(string $nome, ?Assunto $pai = null): Assunto
    {
        return Assunto::create(['disciplina_id' => $this->disc->id, 'nome' => $nome, 'parent_id' => $pai?->id]);
    }

    private function questoes(Assunto $a, int $n, bool $anulada = false): void
    {
        for ($i = 0; $i < $n; $i++) {
            $q = Questao::create([
                'fonte' => 'tec', 'id_externo' => (string) $this->seq++, 'banca' => 'BANCA X', 'banca_sigla' => 'BANCA X',
                'ano' => 2025, 'tipo' => 'ME', 'enunciado' => "Enunciado inventado {$this->seq}.",
                'gabarito' => $anulada ? null : 'B', 'anulada' => $anulada,
            ]);
            foreach (['A', 'B', 'C', 'D'] as $o => $l) {
                $q->alternativas()->create(['letra' => $l, 'texto' => "alternativa {$l}", 'ordem' => $o + 1]);
            }
            $q->assuntos()->attach($a->id, ['principal' => true]);
        }
    }

    private function ligarAoEdital(Assunto $a): void
    {
        DB::connection(config('concursos.connection'))->table('edital_topico_assunto')->insert([
            'topico_id' => $this->topicoId(), 'assunto_id' => $a->id, 'status' => 'sugerido',
        ]);
    }

    private ?int $topico = null;

    /** Um tópico real do edital de exemplo (8.1), importado uma vez por teste. */
    private function topicoId(): int
    {
        if (!$this->topico) {
            $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
            app(\App\Services\Concursos\EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('b', 64)]);
            $this->topico = \App\Models\Concursos\EditalTopico::where('ordem', '8.1')->value('id');
        }

        return $this->topico;
    }

    private function resumo(array $plano): array
    {
        $nomes = Assunto::pluck('nome', 'id');

        return array_map(fn ($u) => [$nomes[$u['assunto_id']], count($u['questoes'])], $plano);
    }

    public function test_sub_assuntos_grandes_viram_lotes_e_os_pequenos_se_juntam_no_pai(): void
    {
        $lei = $this->assunto('Lei Inventada');
        $this->questoes($this->assunto('Capítulo A', $lei), 25);
        $this->questoes($this->assunto('Capítulo B', $lei), 30);
        $this->questoes($this->assunto('Capítulo C', $lei), 3);
        $this->questoes($this->assunto('Capítulo D', $lei), 4);
        $this->ligarAoEdital($lei);

        $this->assertSame([['Capítulo A', 25], ['Capítulo B', 30], ['Lei Inventada', 7]], $this->resumo((new PacoteGerador)->planejar()));
    }

    public function test_assunto_que_cabe_num_lote_vai_inteiro(): void
    {
        $lei = $this->assunto('Lei Inventada');
        $this->questoes($this->assunto('Capítulo A', $lei), 20);
        $this->questoes($this->assunto('Capítulo B', $lei), 15);
        $this->ligarAoEdital($lei);

        $this->assertSame([['Lei Inventada', 35]], $this->resumo((new PacoteGerador)->planejar()));
    }

    public function test_folha_grande_e_dividida_em_partes_iguais(): void
    {
        $a = $this->assunto('Capítulo Enorme');
        $this->questoes($a, 67);
        $this->ligarAoEdital($a);

        $this->assertSame([['Capítulo Enorme', 34], ['Capítulo Enorme', 33]], $this->resumo((new PacoteGerador)->planejar()));
    }

    public function test_ignora_anuladas_lotes_pequenos_e_assuntos_fora_do_edital(): void
    {
        $no = $this->assunto('No edital');
        $this->questoes($no, 12);
        $this->questoes($no, 3, anulada: true);
        $this->ligarAoEdital($no);
        $this->questoes($this->assunto('Fora do edital'), 20);
        $pequeno = $this->assunto('Pequeno');
        $this->questoes($pequeno, 4);
        $this->ligarAoEdital($pequeno);

        $this->assertSame([['No edital', 12]], $this->resumo((new PacoteGerador)->planejar()));
        $this->assertCount(3, (new PacoteGerador(minimoLote: 1, soEdital: false))->planejar());
    }

    public function test_gera_pacote_e_so_envia_cada_questao_uma_vez(): void
    {
        $lei = $this->assunto('Lei Inventada');
        $cap = $this->assunto('Capítulo A', $lei);
        $this->questoes($cap, 8);
        $this->ligarAoEdital($lei);
        Subtopico::create(['assunto_id' => $lei->id, 'slug' => 'prazos', 'nome' => 'Prazos', 'status' => 'aprovado']);
        Subtopico::create(['assunto_id' => $lei->id, 'slug' => 'rascunho', 'nome' => 'Rascunho', 'status' => 'pendente']);
        $pasta = storage_path('framework/testing/pacotes-' . uniqid());

        $g = new PacoteGerador;
        [$saida] = $g->gerar($g->planejar(), $pasta);
        $json = json_decode(file_get_contents($saida['arquivo']), true);

        $this->assertSame(['disciplina' => 'Direito Inventado', 'assunto' => 'Lei Inventada'], $json['assunto']);
        $this->assertSame(['numero' => 1, 'total_questoes' => 8], $json['lote']);
        $this->assertSame([['id' => 'prazos', 'nome' => 'Prazos']], $json['subtopicos_aprovados']);
        $this->assertSame('Capítulo A', $json['questoes'][0]['assunto_tec']);
        $this->assertSame(['A', 'B', 'C', 'D'], array_column($json['questoes'][0]['alternativas'], 'letra'));
        $this->assertSame('B', $json['questoes'][0]['gabarito']);
        if (is_file(config('concursos.python'))) {
            app(ContratoJsonValidator::class)->validar($saida['arquivo'], 'contexto');
        }

        // Nada pendente; chegam questões novas → só elas vão para o lote 2.
        $this->assertSame([], (new PacoteGerador)->planejar());
        $this->questoes($cap, 6);
        $g = new PacoteGerador;
        [$saida2] = $g->gerar($g->planejar(), $pasta);
        $this->assertSame(2, $saida2['lote']->numero);
        $this->assertSame(6, $saida2['total']);
        $this->assertSame(14, DB::connection(config('concursos.connection'))->table('lote_questao')->count());
        $this->assertSame(2, Lote::count());

        array_map('unlink', glob("{$pasta}/*/*"));
    }
}
