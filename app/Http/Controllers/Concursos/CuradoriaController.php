<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Assunto;
use App\Models\Concursos\Subtopico;
use App\Services\Concursos\CuradoriaSubtopicos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Curadoria dos subtópicos propostos pelo Gemini. Só o curador (admin/dono). */
class CuradoriaController extends Controller
{
    public function index(Request $request): View
    {
        $this->curador($request);
        $porAssunto = Subtopico::selectRaw("assunto_id, sum(case when status = 'pendente' then 1 else 0 end) as pendentes, count(*) as total")
            ->groupBy('assunto_id')->get()->keyBy('assunto_id');
        $assuntos = Assunto::with('disciplina')->findMany($porAssunto->keys())
            ->sortBy(fn ($a) => [$porAssunto[$a->id]->pendentes ? 0 : 1, $a->disciplina->nome, $a->nome])
            ->groupBy(fn ($a) => $a->disciplina->nome);

        return view('concursos.curadoria.index', [
            'grupos' => $assuntos,
            'contagens' => $porAssunto,
            'pendentes' => (int) $porAssunto->sum('pendentes'),
        ]);
    }

    public function assunto(Request $request, Assunto $assunto, CuradoriaSubtopicos $curadoria): View
    {
        $this->curador($request);
        $assunto->load('disciplina');
        $subs = Subtopico::where('assunto_id', $assunto->id)->orderByRaw("case when status = 'pendente' then 0 else 1 end")->orderBy('nome')->get();
        $daDisciplina = Subtopico::whereIn('assunto_id', Assunto::where('disciplina_id', $assunto->disciplina_id)->pluck('id'))
            ->with('assunto')->orderBy('nome')->get();
        $db = DB::connection(config('concursos.connection'));
        $contagem = $db->table('questao_subtopico')->whereIn('subtopico_id', $subs->pluck('id'))
            ->selectRaw('subtopico_id, count(*) as n, sum(case when principal then 1 else 0 end) as principais')
            ->groupBy('subtopico_id')->get()->keyBy('subtopico_id');
        $exemplos = $db->table('questao_subtopico as qs')->join('questao_classificacoes as c', 'c.questao_id', '=', 'qs.questao_id')
            ->whereIn('qs.subtopico_id', $subs->pluck('id'))->where('qs.principal', true)
            ->orderBy('c.questao_id')->get(['qs.subtopico_id', 'c.ponto_cobrado'])
            ->groupBy('subtopico_id')->map(fn ($g) => $g->take(3)->pluck('ponto_cobrado'));

        // Próximo assunto com pendências, para a curadoria andar sem voltar à lista.
        $proximo = Subtopico::where('status', 'pendente')->where('assunto_id', '!=', $assunto->id)->orderBy('assunto_id')->value('assunto_id');

        return view('concursos.curadoria.assunto', [
            'assunto' => $assunto,
            'subs' => $subs,
            'daDisciplina' => $daDisciplina,
            'contagem' => $contagem,
            'exemplos' => $exemplos,
            'parecidos' => $curadoria->parecidos($subs->where('status', 'pendente'), $daDisciplina),
            'proximo' => $proximo,
        ]);
    }

    public function acao(Request $request, Subtopico $subtopico, CuradoriaSubtopicos $curadoria): RedirectResponse
    {
        $this->curador($request);
        $dados = $request->validate([
            'acao' => ['required', Rule::in(['aprovar', 'juntar', 'excluir'])],
            'nome' => ['nullable', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'destino' => ['required_if:acao,juntar', 'nullable', 'integer'],
        ]);
        $assuntoId = $subtopico->assunto_id;

        try {
            $msg = match ($dados['acao']) {
                'aprovar' => tap('Aprovado: ' . ($dados['nome'] ?? $subtopico->nome), fn () => $curadoria->aprovar($subtopico, $dados['nome'] ?? null, $dados['descricao'] ?? null)),
                'juntar' => (function () use ($curadoria, $subtopico, $dados) {
                    $destino = Subtopico::findOrFail($dados['destino']);
                    $n = $curadoria->juntar($subtopico, $destino);

                    return "\"{$subtopico->nome}\" juntado em \"{$destino->nome}\" ({$n} questão(ões) movidas).";
                })(),
                'excluir' => tap("Excluído: {$subtopico->nome}", fn () => $curadoria->excluir($subtopico)),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['acao' => $e->getMessage()]);
        }

        return redirect()->to(route('concursos.curadoria.assunto', $assuntoId) . '#lista')->with('status', $msg);
    }

    public function aprovarTodos(Request $request, Assunto $assunto, CuradoriaSubtopicos $curadoria): RedirectResponse
    {
        $this->curador($request);
        $subs = Subtopico::where('assunto_id', $assunto->id)->where('status', 'pendente')->get();
        $subs->each(fn ($s) => $curadoria->aprovar($s));

        return redirect()->route('concursos.curadoria.assunto', $assunto)->with('status', "{$subs->count()} subtópico(s) aprovados.");
    }

    private function curador(Request $request): void
    {
        abort_unless($request->user()->is_admin || $request->user()->is_owner, 403, 'Só o curador revisa os subtópicos.');
    }
}
