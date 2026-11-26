<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\LeiGrifo;
use App\Models\Concursos\LeiMarca;
use App\Models\Concursos\MaterialCapitulo;
use App\Models\Concursos\MaterialPonto;
use App\Services\Concursos\Leis\AcervoLeis;
use App\Services\Concursos\Leis\Reancorador;
use App\Services\Concursos\Livro\Documento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Vade Mecum anotável: marcador e nota do aluno em cada artigo, e o "Levar ao material", que copia
 * a nota para um ponto novo (status revisando) no fim de um capítulo do livro, com a citação do artigo.
 */
class VadeMecumController extends Controller
{
    public function marcar(Request $request, string $lei, string $ancora, AcervoLeis $acervo): JsonResponse
    {
        $this->artigoExiste($acervo, $lei, $ancora);
        $dados = $request->validate([
            'marcado' => ['sometimes', 'boolean'],
            'nota' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $chave = ['user_id' => $request->user()->id, 'lei' => $lei, 'ancora' => $ancora];
        $marca = LeiMarca::firstOrNew($chave);
        if (array_key_exists('marcado', $dados)) {
            $marca->marcado = (bool) $dados['marcado'];
        }
        if (array_key_exists('nota', $dados)) {
            $marca->nota = trim((string) $dados['nota']) === '' ? null : trim($dados['nota']);
        }

        if (!$marca->marcado && $marca->nota === null) {
            if ($marca->exists) {
                $marca->delete();
            }

            return response()->json(['marcado' => false, 'nota' => null]);
        }
        $marca->save();

        return response()->json(['marcado' => $marca->marcado, 'nota' => $marca->nota, 'em' => $marca->updated_at->format('d/m/Y')]);
    }

    public function levar(Request $request, string $lei, string $ancora, AcervoLeis $acervo, Documento $documento): JsonResponse
    {
        $artigo = $this->artigoExiste($acervo, $lei, $ancora);
        $dados = $request->validate([
            'capitulo_id' => ['required', 'integer'],
            'titulo' => ['nullable', 'string', 'max:200'],
        ]);
        $capitulo = MaterialCapitulo::with('material')->find($dados['capitulo_id']);
        abort_unless($capitulo && $capitulo->material->user_id === $request->user()->id, 404);

        $nota = LeiMarca::where(['user_id' => $request->user()->id, 'lei' => $lei, 'ancora' => $ancora])->value('nota');
        abort_if($nota === null, 422, 'Escreva a nota antes de levá-la ao material.');

        $ref = $lei . ' ' . Str::upper(preg_replace('/^(adct-)?art-/', '', $ancora));
        $titulo = trim($dados['titulo'] ?? '') ?: ($acervo->info($lei)['sigla'] . ', ' . $artigo['rotulo']);

        $ponto = DB::connection('concursos_pgsql')->transaction(function () use ($capitulo, $documento, $titulo, $nota, $ref) {
            $ponto = MaterialPonto::create([
                'capitulo_id' => $capitulo->id,
                'titulo' => mb_substr($titulo, 0, 200),
                'regra' => '',
                'base_legal' => "[[{$ref}]]",
                'origem' => 'manual',
                'status' => 'revisando',
                'vezes_errado' => 0,
                'ordem' => 0,
            ]);

            $paragrafos = array_map(
                fn ($linha) => ['type' => 'paragraph', 'content' => $documento->emLinha($linha)],
                array_values(array_filter(preg_split('/\R/', $nota), fn ($l) => trim($l) !== '')),
            );
            // A citação vai na frente: no fim do capítulo o texto corrido perderia o espaço depois
            // dela, e a página da lei (que procura " sigla " no texto) não acharia o ponto.
            array_unshift($paragrafos, ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Base legal: '], ['type' => 'lei', 'attrs' => ['ref' => $ref]], ['type' => 'text', 'text' => '.']]]);

            $doc = $capitulo->conteudo ?: ['type' => 'doc', 'content' => []];
            $doc['content'][] = ['type' => 'pontoErro', 'attrs' => ['pontoId' => $ponto->id, 'titulo' => $ponto->titulo], 'content' => $paragrafos];
            $doc = $documento->limpar($doc);
            $capitulo->update(['conteudo' => $doc, 'texto' => $documento->texto($doc)]);

            return $ponto;
        });

        return response()->json([
            'ponto' => $ponto->id,
            'url' => route('concursos.material.capitulo', [$capitulo->material_id, $capitulo->id]) . '#ponto-' . $ponto->id,
        ]);
    }

    /**
     * Grifo novo: o navegador manda o intervalo (em caracteres do texto puro do artigo) e o servidor
     * tira dali o trecho e o contexto, sem confiar no texto enviado. Sobreposição com outro grifo
     * do mesmo artigo é recusada.
     */
    public function grifar(Request $request, string $lei, string $ancora, AcervoLeis $acervo, Reancorador $reancorador): JsonResponse
    {
        $artigo = $this->artigoExiste($acervo, $lei, $ancora);
        $dados = $request->validate([
            'inicio' => ['required', 'integer', 'min:0'],
            'fim' => ['required', 'integer', 'gt:inicio'],
            'cor' => ['nullable', Rule::in(LeiGrifo::CORES)],
        ]);
        $texto = Reancorador::textoDoHtml($acervo->mdParaHtml($artigo['md']));
        abort_if($dados['fim'] > mb_strlen($texto) || $dados['fim'] - $dados['inicio'] > 2000, 422, 'Seleção fora do artigo.');
        $trecho = mb_substr($texto, $dados['inicio'], $dados['fim'] - $dados['inicio']);
        abort_if(trim($trecho) === '', 422, 'Selecione um trecho do artigo.');

        $existentes = LeiGrifo::where(['user_id' => $request->user()->id, 'lei' => $lei, 'ancora' => $ancora])->get();
        foreach ($reancorador->posicionar($texto, $existentes) as $p) {
            if ($p['inicio'] !== null && $p['inicio'] < $dados['fim'] && $dados['inicio'] < $p['fim']) {
                abort(422, 'Já existe um grifo nesse trecho. Clique nele para trocar a cor ou apagar.');
            }
        }

        $g = LeiGrifo::create([
            'user_id' => $request->user()->id, 'lei' => $lei, 'ancora' => $ancora,
            'cor' => $dados['cor'] ?? 'amarelo', 'trecho' => $trecho,
        ] + $reancorador->contexto($texto, $dados['inicio'], $dados['fim']));

        return response()->json($reancorador->posicionar($texto, [$g])[0]);
    }

    public function recolorir(Request $request, LeiGrifo $grifo): JsonResponse
    {
        abort_unless($grifo->user_id === $request->user()->id, 404);
        $grifo->update($request->validate(['cor' => ['required', Rule::in(LeiGrifo::CORES)]]));

        return response()->json(['id' => $grifo->id, 'cor' => $grifo->cor]);
    }

    public function desgrifar(Request $request, LeiGrifo $grifo): JsonResponse
    {
        abort_unless($grifo->user_id === $request->user()->id, 404);
        $grifo->delete();

        return response()->json(['apagado' => true]);
    }

    /** @return array{lei: string, ancora: string, rotulo: string, md: string} */
    private function artigoExiste(AcervoLeis $acervo, string $lei, string $ancora): array
    {
        abort_unless($acervo->existe($lei), 404);
        $prefixo = $acervo->info($lei)['prefixo'] ?? '';
        abort_unless(preg_match('/^' . preg_quote($prefixo, '/') . 'art-(\d+(?:-[a-z])?)$/', $ancora, $m), 404);
        try {
            $artigo = $acervo->artigo($lei, Str::upper($m[1]));
        } catch (RuntimeException) {
            abort(503, 'Não consegui abrir a lei agora.');
        }
        abort_if($artigo === null, 404);

        return $artigo;
    }
}
