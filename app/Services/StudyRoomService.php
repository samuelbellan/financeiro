<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Salas de estudo: criar, entrar pelo código, sair e o status de hoje de cada membro.
 * Só expõe nome, avatar, tempo estudado hoje (no dia de estudo do próprio membro) e desde quando
 * está estudando; matérias, notas e histórico continuam privados.
 */
class StudyRoomService
{
    public const MAX_ROOMS_PER_USER = 10;
    public const MAX_MEMBERS = 50;

    protected const DB_FORMAT = 'Y-m-d H:i:s.v';
    // Sem letras/números que se confundem (0/O, 1/I/L).
    protected const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function create(int $userId, string $nome): string
    {
        $this->assertRoomLimit($userId);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($id, $userId, $nome) {
            DB::table('study_rooms')->insert([
                'id' => $id,
                'nome' => $nome,
                'codigo' => $this->newCode(),
                'owner_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('study_room_members')->insert(['room_id' => $id, 'user_id' => $userId, 'joined_at' => now()]);
        });

        return $id;
    }

    public function join(int $userId, string $codigo): string
    {
        $room = DB::table('study_rooms')->where('codigo', strtoupper(trim($codigo)))->first();
        if (!$room) {
            throw ValidationException::withMessages(['codigo' => 'Código de sala não encontrado.']);
        }
        $already = DB::table('study_room_members')->where(['room_id' => $room->id, 'user_id' => $userId])->exists();
        if (!$already) {
            $this->assertRoomLimit($userId);
            if (DB::table('study_room_members')->where('room_id', $room->id)->count() >= self::MAX_MEMBERS) {
                throw ValidationException::withMessages(['codigo' => 'Essa sala está cheia.']);
            }
            DB::table('study_room_members')->insert(['room_id' => $room->id, 'user_id' => $userId, 'joined_at' => now()]);
        }

        return $room->id;
    }

    /** Sai da sala; a última pessoa a sair apaga a sala, e se o dono sai o membro mais antigo assume. */
    public function leave(int $userId, string $roomId): void
    {
        DB::transaction(function () use ($userId, $roomId) {
            DB::table('study_room_members')->where(['room_id' => $roomId, 'user_id' => $userId])->delete();
            $next = DB::table('study_room_members')->where('room_id', $roomId)->orderBy('joined_at')->value('user_id');
            if ($next === null) {
                DB::table('study_rooms')->where('id', $roomId)->delete();
            } else {
                DB::table('study_rooms')->where(['id' => $roomId, 'owner_id' => $userId])->update(['owner_id' => $next]);
            }
        });
    }

    public function destroy(string $roomId): void
    {
        DB::transaction(function () use ($roomId) {
            DB::table('study_room_members')->where('room_id', $roomId)->delete();
            DB::table('study_rooms')->where('id', $roomId)->delete();
        });
    }

    public function isMember(int $userId, string $roomId): bool
    {
        return DB::table('study_room_members')->where(['room_id' => $roomId, 'user_id' => $userId])->exists();
    }

    /** Salas do usuário com o status de hoje de cada membro. */
    public function overview(int $userId): array
    {
        $now = Carbon::now('UTC');
        $rooms = DB::table('study_rooms')
            ->join('study_room_members', 'study_room_members.room_id', '=', 'study_rooms.id')
            ->where('study_room_members.user_id', $userId)
            ->orderBy('study_room_members.joined_at')
            ->select('study_rooms.*')
            ->get();
        if ($rooms->isEmpty()) {
            return ['server_time' => $this->iso($now), 'rooms' => []];
        }

        $members = DB::table('study_room_members')
            ->join('users', 'users.id', '=', 'study_room_members.user_id')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->orderBy('joined_at')
            ->get(['room_id', 'users.id as user_id', 'users.name']);
        $status = $this->todayStatus($members->pluck('user_id')->unique()->all(), $now);

        return [
            'server_time' => $this->iso($now),
            'rooms' => $rooms->map(fn ($room) => [
                'id' => $room->id,
                'nome' => $room->nome,
                'codigo' => $room->codigo,
                'dono' => (int) $room->owner_id === $userId,
                'membros' => $members->where('room_id', $room->id)->values()->map(fn ($m) => [
                    'user_id' => (int) $m->user_id,
                    'nome' => $m->name,
                    'eu' => (int) $m->user_id === $userId,
                ] + $status[(int) $m->user_id])->all(),
            ])->all(),
        ];
    }

    /**
     * Por usuário: avatar, ms estudados hoje até agora e o início da sessão do cronômetro em andamento.
     * "Hoje" segue o fuso e a virada de dia de cada um, como no app.
     */
    public function todayStatus(array $userIds, Carbon $now): array
    {
        $settings = DB::table('study_settings')->whereIn('user_id', $userIds)->get()->keyBy('user_id');
        $result = [];
        $starts = [];
        foreach ($userIds as $id) {
            $s = $settings->get($id);
            $starts[$id] = $this->dayStart($now, $s->timezone ?? 'America/Sao_Paulo', (int) ($s->virada_hora ?? 1));
            $result[$id] = ['avatar' => $s->avatar ?? 'bichinho', 'hoje_ms' => 0, 'estudando_desde' => null];
        }

        $earliest = min($starts);
        $sessions = DB::table('study_sessions')
            ->whereIn('user_id', $userIds)
            ->whereNull('deleted_at')
            ->where('inicio', '<', $now->format(self::DB_FORMAT))
            ->where(fn ($q) => $q->whereNull('fim')->orWhere('fim', '>', $earliest->format(self::DB_FORMAT)))
            ->get(['user_id', 'inicio', 'fim', 'origem']);
        foreach ($sessions as $session) {
            $id = (int) $session->user_id;
            $inicio = Carbon::parse($session->inicio, 'UTC');
            $fim = $session->fim ? Carbon::parse($session->fim, 'UTC') : $now;
            $from = $inicio->max($starts[$id]);
            $to = $fim->min($now);
            if ($to->gt($from)) {
                $result[$id]['hoje_ms'] += (int) $from->diffInMilliseconds($to);
            }
            if (!$session->fim && $session->origem === 'timer') {
                $result[$id]['estudando_desde'] = $this->iso($inicio);
            }
        }

        return $result;
    }

    protected function dayStart(Carbon $now, string $timezone, int $virada): Carbon
    {
        $local = $now->copy()->setTimezone($timezone);
        $start = $local->copy()->startOfDay()->addHours($virada);
        if ($local->lt($start)) {
            $start->subDay();
        }

        return $start->utc();
    }

    protected function assertRoomLimit(int $userId): void
    {
        if (DB::table('study_room_members')->where('user_id', $userId)->count() >= self::MAX_ROOMS_PER_USER) {
            throw ValidationException::withMessages(['codigo' => 'Você já está no limite de ' . self::MAX_ROOMS_PER_USER . ' salas.']);
        }
    }

    protected function newCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (DB::table('study_rooms')->where('codigo', $code)->exists());

        return $code;
    }

    protected function iso(Carbon $time): string
    {
        return $time->copy()->utc()->format('Y-m-d\TH:i:s.v\Z');
    }
}
