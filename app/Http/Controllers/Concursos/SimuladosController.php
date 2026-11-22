<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Questao;
use App\Models\Concursos\Simulado;
use App\Models\Concursos\Tentativa;
use App\Services\Concursos\SimuladoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Simulado cronometrado: prova inteira numa página, sem gabarito até entregar. */
class SimuladosController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $perfil = Perfil::with('cargo.areas', 'cargo.edital')->where('user_id', $request->user()->id)->first();
        if (!$perfil) {
            return redirect()->route('concursos.perfil')->with('status', 'Preencha o perfil antes do primeiro simulado.');
        }

        return view('concursos.simulados.index', [
            'perfil' => $perfil,
            'simulados' => Simulado::where('user_id', $request->user()->id)->latest('id')->get(),
            'tipos' => Simulado::TIPOS,
        ]);
    }

    public function store(Request $request, SimuladoService $service): RedirectResponse
    {
        $dados = $request->validate(['tipo' => ['required', Rule::in(array_keys(Simulado::TIPOS))]]);
        $perfil = Perfil::where('user_id', $request->user()->id)->firstOrFail();
        if ($aberto = Simulado::where('user_id', $perfil->user_id)->whereNull('finalizado_em')->first()) {
            return redirect()->route('concursos.simulados.show', $aberto)->with('status', 'Você já tem um simulado em andamento.');
        }
        $s = $service->criar($perfil, $dados['tipo']);
        if (!$s->questao_ids) {
            $s->delete();

            return back()->with('status', 'Ainda não há questões no banco para montar um simulado.');
        }

        return redirect()->route('concursos.simulados.show', $s);
    }

    public function show(Request $request, Simulado $simulado, SimuladoService $service): View
    {
        $this->dono($request, $simulado);
        if ($simulado->emAndamento() && $simulado->esgotado()) {
            $service->entregar($simulado);
        }

        $questoes = Questao::with('alternativas', 'resolucao', 'classificacao')->whereIn('id', $simulado->questao_ids)->get()->keyBy('id');
        $view = $simulado->emAndamento() ? 'concursos.simulados.prova' : 'concursos.simulados.resultado';

        return view($view, [
            's' => $simulado->fresh(),
            'questoes' => $questoes,
            'tentativas' => $simulado->emAndamento() ? collect()
                : Tentativa::where('simulado_id', $simulado->id)->get()->keyBy('questao_id'),
        ]);
    }

    /** Autosave das respostas (fetch). Depois do prazo, não aceita mais. */
    public function salvar(Request $request, Simulado $simulado, SimuladoService $service): JsonResponse
    {
        $this->dono($request, $simulado);
        if (!$simulado->emAndamento() || $simulado->esgotado()) {
            return response()->json(['ok' => false, 'message' => 'Simulado encerrado.'], 409);
        }
        $service->salvarRespostas($simulado, (array) $request->input('respostas', []));

        return response()->json(['ok' => true, 'salvas' => count($simulado->fresh()->respostas ?? [])]);
    }

    public function entregar(Request $request, Simulado $simulado, SimuladoService $service): RedirectResponse
    {
        $this->dono($request, $simulado);
        if ($simulado->emAndamento() && !$simulado->esgotado()) {
            $service->salvarRespostas($simulado, (array) $request->input('respostas', []));
        }
        $service->entregar($simulado->fresh());

        return redirect()->route('concursos.simulados.show', $simulado);
    }

    private function dono(Request $request, Simulado $s): void
    {
        abort_unless($s->user_id === $request->user()->id, 404);
    }
}
