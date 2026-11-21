<?php

namespace Tests\Feature;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\EditalTopico;
use App\Services\Concursos\ArvoreAssuntosImportador;
use App\Services\Concursos\EditalImportador;
use App\Services\Concursos\TecCadernoParser;
use App\Services\Concursos\TecImportador;
use App\Services\Concursos\TecParserException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaBancoConcursos;
use Tests\TestCase;

class ArvoreAssuntosImportadorTest extends TestCase
{
    use RefreshDatabase, UsaBancoConcursos;

    /** Mesmo formato da página do caderno do TEC copiada (markdown, contagens, marcadores vazios). */
    private const ARVORE = <<<'TXT'
    * [Direito Processual Civil](https://www.tecconcursos.com.br/questoes/)
    4 (100.00%)
       * [Recursos](https://www.tecconcursos.com.br/questoes/)
    4 (100.00%)
          * [Recursos em Espécie - Teoria Geral](https://www.tecconcursos.com.br/questoes/)
    4 (100.00%)
             * [Disposições Gerais - Recursos (arts. 994 a 1.008)](https://www.tecconcursos.com.br/questoes/)
    4 (100.00%)
                *
             * [Apelação (arts. 1.009 a 1.014)](https://www.tecconcursos.com.br/questoes/)
    0 (0.00%)
       * [Processo de Conhecimento](https://www.tecconcursos.com.br/questoes/)
    0 (0.00%)
    TXT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBancoConcursos();
    }

    private function importar(string $texto = self::ARVORE): array
    {
        $imp = app(ArvoreAssuntosImportador::class);

        return $imp->gravar($imp->parse($texto), 'arvore.txt', ['caminho' => 'x', 'sha256' => str_repeat('d', 64)]);
    }

    private function importarCaderno(): void
    {
        $f = json_decode(file_get_contents(base_path('tests/Fixtures/tec/caderno-sintetico.json')), true);
        app(TecImportador::class)->gravar((new TecCadernoParser)->parse($f['paginas'], $f['imagens']), 'c.pdf', ['caminho' => 'x', 'sha256' => str_repeat('c', 64)]);
    }

    public function test_parse_le_niveis_e_ignora_contagens_e_links(): void
    {
        $nos = app(ArvoreAssuntosImportador::class)->parse(self::ARVORE);

        $this->assertSame([
            ['nivel' => 0, 'nome' => 'Direito Processual Civil', 'questoes' => 4],
            ['nivel' => 1, 'nome' => 'Recursos', 'questoes' => 4],
            ['nivel' => 2, 'nome' => 'Recursos em Espécie - Teoria Geral', 'questoes' => 4],
            ['nivel' => 3, 'nome' => 'Disposições Gerais - Recursos (arts. 994 a 1.008)', 'questoes' => 4],
            ['nivel' => 3, 'nome' => 'Apelação (arts. 1.009 a 1.014)', 'questoes' => 0],
            ['nivel' => 1, 'nome' => 'Processo de Conhecimento', 'questoes' => 0],
        ], $nos);
    }

    public function test_parse_aceita_texto_simples_com_contagem_na_mesma_linha(): void
    {
        $nos = app(ArvoreAssuntosImportador::class)->parse("Direito Civil 10 (100%)\n  - Prescrição 10 (100,00%)\n    - Causas interruptivas\n");

        $this->assertSame(['Direito Civil', 'Prescrição', 'Causas interruptivas'], array_column($nos, 'nome'));
        $this->assertSame([0, 1, 2], array_column($nos, 'nivel'));
        $this->assertSame([10, 10, null], array_column($nos, 'questoes'));
    }

    public function test_grava_contagem_do_tec_por_caderno_e_reimportar_substitui(): void
    {
        $this->importar();
        $db = \Illuminate\Support\Facades\DB::connection(config('concursos.connection'));
        $recursos = Assunto::where('nome', 'Recursos')->value('id');
        $this->assertSame(4, (int) $db->table('assunto_incidencias')->where('assunto_id', $recursos)->value('questoes'));
        $this->assertSame('arvore', $db->table('assunto_incidencias')->value('caderno'));

        $this->importar(str_replace("4 (100.00%)", "9 (100.00%)", self::ARVORE));
        $this->assertSame(9, (int) $db->table('assunto_incidencias')->where('assunto_id', $recursos)->value('questoes'));
        $this->assertSame(5, $db->table('assunto_incidencias')->count());
    }

    public function test_folha_do_caderno_ganha_os_pais_e_nao_duplica(): void
    {
        $this->importarCaderno();
        $this->importar();

        $folha = Assunto::where('nome', 'Disposições Gerais - Recursos (arts. 994 a 1.008)')->sole();
        $this->assertSame('Recursos em Espécie - Teoria Geral', $folha->pai->nome);
        $this->assertSame('Recursos', $folha->pai->pai->nome);
        $this->assertNull($folha->pai->pai->parent_id);
        $this->assertSame(4, $folha->questoes()->count());
        $this->assertSame(5, Assunto::count());
    }

    public function test_reimportar_e_idempotente(): void
    {
        $this->importar();
        $r = $this->importar();

        $this->assertSame(0, $r['pai_alterado']);
        $this->assertSame([], $r['assuntos_criados']);
        $this->assertSame(5, Assunto::count());
    }

    public function test_topico_do_edital_ligado_ao_pai_alcanca_questoes_das_folhas(): void
    {
        $d = json_decode(file_get_contents(resource_path('schemas/concursos/exemplos/edital-ufms-152-2026-cargo-523.json')), true);
        $d['areas'][4]['disciplinas'][7]['topicos'][0]['assuntos_sugeridos'] = ['Recursos em Espécie - Teoria Geral']; // só o nome
        app(EditalImportador::class)->gravar($d, 'edital.json', ['caminho' => 'x', 'sha256' => str_repeat('b', 64)]);
        $this->importarCaderno();

        $topico = EditalTopico::where('ordem', '8.1')->first();
        $this->assertSame(0, $topico->questoesQuery()->count(), 'sem a árvore, o pai não tem questões');

        $r = $this->importar();

        $this->assertSame(['Direito Processual Civil - Recursos em Espécie - Teoria Geral'], $r['assuntos_adotados']);
        $this->assertSame(4, $topico->questoesQuery()->count());
    }

    public function test_recusa_arquivo_sem_assuntos(): void
    {
        $this->expectException(TecParserException::class);
        $this->importar("547 (90.26%)\n   * \n");
    }
}
