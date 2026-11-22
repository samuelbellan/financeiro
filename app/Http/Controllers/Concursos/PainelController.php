<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\Perfil;
use App\Models\Concursos\Simulado;
use App\Models\User;
use App\Services\Concursos\PainelDesempenho;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Painel de desempenho. O aluno vê o próprio; o curador (admin/dono) escolhe o aluno (?aluno=id),
 * como decidido na sabatina: o curador acompanha os índices individuais.
 */
class PainelController extends Controller
{
    public function index(Request $request, PainelDesempenho $painel): View|RedirectResponse
    {
        $eu = $request->user();
        $curador = $eu->is_admin || $eu->is_owner;
        $alunoId = $curador && $request->integer('aluno') ? $request->integer('aluno') : $eu->id;

        $perfil = Perfil::with('cargo.edital')->where('user_id', $alunoId)->first();
        if (!$perfil) {
            abort_if($alunoId !== $eu->id, 404);

            return redirect()->route('concursos.perfil')->with('status', 'Preencha o perfil para ver o desempenho.');
        }

        $alunos = $curador
            ? User::whereIn('id', Perfil::pluck('user_id'))->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('concursos.painel.index', [
            'd' => $painel->calcular($perfil, today()),
            'perfil' => $perfil,
            'aluno' => User::find($alunoId),
            'alunos' => $alunos,
            'proprio' => $alunoId === $eu->id,
            'simulados' => Simulado::where('user_id', $alunoId)->whereNotNull('finalizado_em')->latest('finalizado_em')->limit(5)->get(),
        ]);
    }
}
