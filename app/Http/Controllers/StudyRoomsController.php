<?php

namespace App\Http\Controllers;

use App\Services\StudyRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Salas de estudo do módulo de estudos. Toda resposta devolve a visão atualizada das salas do usuário.
 * Qualquer usuário entra pelo código e sai; criar salas, remover membros e excluir salas é só para
 * gestores (is_admin: o dono do sistema e quem ele delegar).
 */
class StudyRoomsController extends Controller
{
    public function __construct(protected StudyRoomService $rooms)
    {
    }

    public function index(): JsonResponse
    {
        return $this->overview();
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManager();
        $data = $request->validate(['nome' => 'required|string|max:60']);
        $this->rooms->create(Auth::id(), trim($data['nome']));

        return $this->overview(201);
    }

    public function join(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo' => 'required|string|max:12']);
        $this->rooms->join(Auth::id(), $data['codigo']);

        return $this->overview();
    }

    public function leave(string $room): JsonResponse
    {
        $this->rooms->leave(Auth::id(), $room);

        return $this->overview();
    }

    public function removeMember(string $room, int $user): JsonResponse
    {
        $this->authorizeManager($room);
        $this->rooms->leave($user, $room);

        return $this->overview();
    }

    public function destroy(string $room): JsonResponse
    {
        $this->authorizeManager($room);
        $this->rooms->destroy($room);

        return $this->overview();
    }

    /** Gestor e, quando há sala, membro dela (gestores gerenciam as salas em que estão). */
    protected function authorizeManager(?string $room = null): void
    {
        abort_unless(Auth::user()->is_admin, 403, 'Só gestores podem criar e gerenciar salas.');
        abort_if($room && !$this->rooms->isMember(Auth::id(), $room), 404);
    }

    protected function overview(int $status = 200): JsonResponse
    {
        return response()->json(
            $this->rooms->overview(Auth::id()) + ['pode_gerenciar' => (bool) Auth::user()->is_admin],
            $status,
        );
    }
}
