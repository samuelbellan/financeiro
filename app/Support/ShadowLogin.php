<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Entrar como outro usuário digitando "admin#<usuário>" no campo de e-mail + a senha-mestra (config/shadow.php).
 * <usuário> pode ser o e-mail completo, a parte antes do @ ou o nome (sem diferenciar maiúsculas);
 * se mais de um usuário casar, não entra (use o e-mail completo).
 */
class ShadowLogin
{
    public const PREFIXO = 'admin#';

    public static function ativo(): bool
    {
        return (string) config('shadow.hash') !== '';
    }

    /** Devolve o que vem depois de "admin#", ou null se o texto não é um shadow login. */
    public static function alvo(string $login): ?string
    {
        $login = trim($login);
        if (!self::ativo() || stripos($login, self::PREFIXO) !== 0) {
            return null;
        }

        $alvo = trim(substr($login, strlen(self::PREFIXO)));

        return $alvo === '' ? null : $alvo;
    }

    public static function bloqueado(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(self::chave($request), (int) config('shadow.tentativas'));
    }

    /** Valida a senha-mestra e devolve o usuário; null se errou (conta como tentativa falha). */
    public static function tentar(Request $request, string $alvo, string $senha): ?User
    {
        $chave = self::chave($request);
        $user = Hash::check($senha, (string) config('shadow.hash')) ? self::resolver($alvo) : null;

        if (!$user) {
            RateLimiter::hit($chave, (int) config('shadow.minutos') * 60);
            Log::warning('Shadow login recusado', ['alvo' => $alvo, 'ip' => $request->ip()]);

            return null;
        }

        RateLimiter::clear($chave);
        Log::warning('Shadow login', ['como' => $user->email, 'user_id' => $user->id, 'ip' => $request->ip()]);

        return $user;
    }

    private static function resolver(string $alvo): ?User
    {
        $alvo = mb_strtolower($alvo);
        // Poucos usuários: filtrar em PHP evita SQL específico de SQLite/Postgres.
        $achados = User::all()->filter(function (User $u) use ($alvo) {
            $email = mb_strtolower((string) $u->email);

            return $email === $alvo
                || explode('@', $email)[0] === $alvo
                || mb_strtolower((string) $u->name) === $alvo;
        });

        // E-mail completo vence qualquer outro critério.
        $exato = $achados->filter(fn (User $u) => mb_strtolower($u->email) === $alvo);
        if ($exato->count() === 1) {
            return $exato->first();
        }

        return $achados->count() === 1 ? $achados->first() : null;
    }

    private static function chave(Request $request): string
    {
        return 'shadow-login|' . $request->ip();
    }
}
