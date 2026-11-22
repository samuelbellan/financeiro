<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\EditalCargo;
use App\Models\Concursos\MetaAtividade;
use App\Models\Concursos\Perfil;
use App\Services\Concursos\JornadaMeta;
use App\Services\Concursos\MotorMeta;
use App\Services\Concursos\TempoEstudosSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Perfil do aluno (o que já viu, quanto tempo tem) e a meta do dia gerada pelo motor. */
class MetaController extends Controller
{
    public function meta(Request $request, MotorMeta $motor, TempoEstudosSync $tempo, JornadaMeta $jornada): View|RedirectResponse
    {
        $perfil = Perfil::where('user_id', $request->user()->id)->first();
        if (!$perfil) {
            return redirect()->route('concursos.perfil')->with('status', 'Antes da primeira meta, conte o que você já estudou e quanto tempo tem por dia.');
        }

        $tempo->talvez(); // tempo cronometrado no /estudos: calibra a meta (antes de gerar a de hoje)
        $meta = $motor->metaDoDia($perfil, today());
        $meta->load('atividades.topico.disciplina', 'atividades.meta');
        $userId = $request->user()->id;

        // Questões/revisão viram "feita" sozinhas quando todas as questões foram respondidas.
        $atividades = $meta->atividades->map(function (MetaAtividade $a) use ($userId) {
            $feitas = $a->questao_ids ? count($a->respondidas($userId)) : null;
            if ($feitas !== null && $feitas >= count($a->questao_ids) && $a->status === 'pendente') {
                $a->update(['status' => 'feita', 'concluida_em' => now()]);
            }

            return ['a' => $a, 'feitas' => $feitas];
        });

        // Calendário: um dia passado da semana pode ser aberto para ver o que foi feito (só leitura).
        $j = $jornada->calcular($perfil, today());
        $diaSel = collect($j['semana'])->first(fn ($x) => $x['data']->toDateString() === $request->query('dia') && !$x['hoje'] && $x['data']->isPast());

        return view('concursos.meta.index', [
            'meta' => $meta,
            'jornada' => $j,
            'diaSel' => $diaSel,
            'atividades' => $atividades,
            'perfil' => $perfil->load('cargo.edital'),
            'tipos' => MetaAtividade::TIPOS,
            'ritmo' => $motor->minutosPorQuestao($userId, today()),
            'estudosUrl' => rtrim((string) config('services.estudos.cloud_url'), '/') ?: url(''),
            'tempoLigado' => $tempo->configurado(),
        ]);
    }

    public function feita(Request $request, MetaAtividade $atividade): RedirectResponse
    {
        $this->dono($request, $atividade);
        abort_unless($atividade->tipo === 'teoria', 422);
        $atividade->update(['status' => 'feita', 'concluida_em' => now()]);

        // Quem estudou a teoria de um tópico que marcou "Não vi" passa a ter visto parte dele.
        $perfil = Perfil::where('user_id', $request->user()->id)->first();
        if ($perfil && $atividade->topico_id && ($perfil->declaracoes()[$atividade->topico_id] ?? null) === 'nao_vi') {
            $perfil->declarar($atividade->topico_id, 'vi_parte');
        }

        return redirect()->route('concursos.meta');
    }

    public function adiar(Request $request, MetaAtividade $atividade): RedirectResponse
    {
        $this->dono($request, $atividade);
        abort_unless($atividade->status === 'pendente', 422);
        $atividade->update(['status' => 'adiada', 'adiamentos' => $atividade->adiamentos + 1]);

        return redirect()->route('concursos.meta')->with('status', 'Adiada para amanhã.');
    }

    public function perfil(Request $request, MotorMeta $motor): View
    {
        $perfil = Perfil::where('user_id', $request->user()->id)->first();
        $cargos = EditalCargo::with('edital')->orderByDesc('id')->get();
        $cargo = $perfil?->cargo ?? $cargos->first();
        $perfilVisao = $perfil ?? ($cargo ? new Perfil(['user_id' => $request->user()->id, 'cargo_id' => $cargo->id, 'minutos_semana' => []]) : null);

        return view('concursos.meta.perfil', [
            'perfil' => $perfil,
            'cargos' => $cargos,
            'cargo' => $cargo?->load('edital', 'areas.disciplinas.topicos'),
            'declaracoes' => $perfil?->declaracoes() ?? [],
            'indices' => $perfilVisao ? $motor->indices($perfilVisao, today())->keyBy(fn ($i) => $i['topico']->id) : collect(),
            'dias' => Perfil::DIAS,
            'opcoes' => Perfil::DECLARACOES,
        ]);
    }

    public function salvarPerfil(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'cargo_id' => ['required', Rule::exists(config('concursos.connection') . '.edital_cargos', 'id')],
            'minutos' => ['required', 'array'],
            'minutos.*' => ['nullable', 'integer', 'min:0', 'max:900'],
            'topicos' => ['array'],
            'topicos.*' => [Rule::in(array_keys(Perfil::DECLARACOES))],
        ]);

        $minutos = [];
        foreach (array_keys(Perfil::DIAS) as $d) {
            $minutos[(string) $d] = (int) ($dados['minutos'][$d] ?? 0);
        }
        $perfil = Perfil::updateOrCreate(['user_id' => $request->user()->id], ['cargo_id' => $dados['cargo_id'], 'minutos_semana' => $minutos]);
        foreach ($dados['topicos'] ?? [] as $topicoId => $declarado) {
            $perfil->declarar((int) $topicoId, $declarado);
        }

        // A meta é congelada, mas se ninguém mexeu nela ainda, refaz com o perfil novo.
        $hoje = \App\Models\Concursos\Meta::where('user_id', $perfil->user_id)->whereDate('data', today()->toDateString())->with('atividades.meta')->first();
        if ($hoje && $hoje->atividades->every(fn ($a) => $a->status === 'pendente' && !$a->respondidas($perfil->user_id))) {
            MetaAtividade::where('status', 'adiada')->where('concluida_em', '>=', $hoje->created_at)
                ->whereHas('meta', fn ($q) => $q->where('user_id', $perfil->user_id))->update(['concluida_em' => null]);
            $hoje->delete();
        }

        return redirect()->route('concursos.meta')->with('status', 'Perfil salvo.');
    }

    private function dono(Request $request, MetaAtividade $a): void
    {
        abort_unless($a->meta->user_id === $request->user()->id, 404);
    }
}
