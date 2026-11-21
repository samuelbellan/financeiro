<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Consulta ou define os módulos que um usuário pode acessar.
 *   php artisan usuarios:modulos                       (lista todos)
 *   php artisan usuarios:modulos email@x --set=estudos,concursos
 *   php artisan usuarios:modulos email@x --todos      (libera tudo)
 */
class UsuarioModulosCommand extends Command
{
    protected $signature = 'usuarios:modulos
                            {email? : E-mail do usuário (sem ele, lista todos)}
                            {--set= : Módulos separados por vírgula}
                            {--todos : Libera todos os módulos (modulos = null)}';

    protected $description = 'Consulta ou define os módulos liberados para um usuário.';

    public function handle(): int
    {
        $validos = array_keys(config('modulos.disponiveis'));

        if (!$this->argument('email')) {
            $this->table(['ID', 'Nome', 'Dono', 'Gestor', 'Módulos'], User::orderBy('id')->get()->map(fn (User $u) => [
                $u->id, $u->name, $u->is_owner ? 'sim' : '', $u->is_admin ? 'sim' : '',
                $u->is_owner || $u->modulos === null ? 'todos' : implode(', ', $u->modulos),
            ]));
            return self::SUCCESS;
        }

        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
        if (!$user) {
            $this->error('Usuário não encontrado.');
            return self::FAILURE;
        }

        if ($this->option('todos')) {
            $user->forceFill(['modulos' => null])->save();
            $this->info("{$user->name}: todos os módulos liberados.");
            return self::SUCCESS;
        }

        if ($this->option('set') !== null) {
            $modulos = array_values(array_unique(array_filter(array_map('trim', explode(',', $this->option('set'))))));
            $invalidos = array_diff($modulos, $validos);
            if ($invalidos) {
                $this->error('Módulo(s) inválido(s): ' . implode(', ', $invalidos) . '. Válidos: ' . implode(', ', $validos));
                return self::FAILURE;
            }
            $user->forceFill(['modulos' => $modulos])->save();
        }

        $this->info("{$user->name}: " . ($user->is_owner || $user->modulos === null ? 'todos' : (implode(', ', $user->modulos) ?: 'nenhum')));
        return self::SUCCESS;
    }
}
