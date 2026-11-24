<?php

namespace App\Http\Controllers\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concursos\UsoEvento;
use App\Models\User;
use App\Services\Concursos\UsoConcursos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Telemetria de uso: painel só para o curador (admin/dono) e o beacon de tempo visível de cada tela. */
class UsoController extends Controller
{
    public const PERIODOS = [7, 14, 30, 90];

    public function index(Request $request, UsoConcursos $uso): View
    {
        $eu = $request->user();
        abort_unless($eu->is_admin || $eu->is_owner, 403);

        $dias = in_array($request->integer('dias'), self::PERIODOS, true) ? $request->integer('dias') : 14;
        $desde = today()->subDays($dias - 1);
        $geral = $uso->visaoGeral($desde);
        $nomes = User::whereIn('id', array_column($geral, 'user_id'))->pluck('name', 'id');

        $alunoId = $request->integer('aluno') ?: null;
        $aluno = $alunoId ? User::find($alunoId) : null;

        return view('concursos.uso.index', [
            'dias' => $dias,
            'geral' => $geral,
            'nomes' => $nomes,
            'aluno' => $aluno,
            'd' => $aluno ? $uso->detalhe($aluno->id, $desde) : null,
        ]);
    }

    /** O navegador manda, ao esconder/sair da tela, quantos segundos ela ficou à vista (acumulado). */
    public function tempo(Request $request): Response
    {
        $dados = $request->validate(['id' => ['required', 'uuid'], 's' => ['required', 'integer', 'min:0']]);
        $s = min((int) $dados['s'], 4 * 3600);

        UsoEvento::where('uuid', $dados['id'])->where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->whereNull('visivel_s')->orWhere('visivel_s', '<', $s))
            ->update(['visivel_s' => $s]);

        return response()->noContent();
    }
}
