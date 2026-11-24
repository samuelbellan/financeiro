<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllowedGoogleEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AllowedEmailController extends Controller
{
    /**
     * Lista todos os e-mails convidados e autorizados a usar o login do Google.
     */
    public function index()
    {
        if (!Auth::user()?->is_admin) {
            abort(403, 'Acesso restrito ao administrador do sistema.');
        }

        $emails = AllowedGoogleEmail::with('creator')
            ->orderByDesc('created_at')
            ->get();
        $users = User::orderByDesc('is_owner')->orderByDesc('is_admin')->orderBy('name')->get(['id', 'name', 'email', 'is_admin', 'is_owner']);

        return view('admin.convites', compact('emails', 'users'));
    }

    /**
     * Adiciona um novo e-mail à lista de autorizados.
     */
    public function store(Request $request)
    {
        if (!Auth::user()?->is_admin) {
            abort(403, 'Acesso restrito ao administrador do sistema.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'nome' => ['nullable', 'string', 'max:100'],
        ]);

        $email = strtolower(trim($validated['email']));

        if (AllowedGoogleEmail::where('email', $email)->exists()) {
            return back()->with('info', "O e-mail '{$email}' já está na lista de autorizados.");
        }

        AllowedGoogleEmail::create([
            'email' => $email,
            'nome' => $validated['nome'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', "E-mail '{$email}' autorizado com sucesso para acesso via Google!");
    }

    /**
     * Remove um e-mail da lista de autorizados.
     */
    public function destroy(int $id)
    {
        if (!Auth::user()?->is_admin) {
            abort(403, 'Acesso restrito ao administrador do sistema.');
        }

        $record = AllowedGoogleEmail::findOrFail($id);
        // Gestores não tiram o acesso do dono nem de outros gestores; só o dono faz isso.
        $target = User::where('email', $record->email)->first();
        if ($target && ($target->is_owner || ($target->is_admin && !Auth::user()->is_owner))) {
            abort(403, 'Só o dono do sistema pode remover o acesso de um gestor.');
        }
        $record->delete();

        return back()->with('success', "Acesso do e-mail '{$record->email}' removido com sucesso.");
    }

    /**
     * Dá ou retira a função de gestor (convidar usuários e criar/gerenciar salas de estudo).
     * Só o dono do sistema delega; o dono é sempre gestor.
     */
    public function toggleManager(User $user)
    {
        if (!Auth::user()?->is_owner) {
            abort(403, 'Só o dono do sistema pode delegar a função de gestor.');
        }
        if ($user->is_owner) {
            return back()->with('info', 'O dono do sistema é sempre gestor.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        return back()->with('success', $user->is_admin
            ? "{$user->name} agora é gestor: pode convidar usuários e criar e gerenciar salas."
            : "{$user->name} deixou de ser gestor.");
    }

    /**
     * Cadastra o usuário já com uma senha definida pelo gestor. Ele troca a senha no primeiro acesso
     * e continua podendo entrar com o Google, pois o e-mail também entra na lista de autorizados.
     */
    public function storeUsuario(Request $request)
    {
        if (!Auth::user()?->is_admin) {
            abort(403, 'Acesso restrito ao administrador do sistema.');
        }

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'password.confirmed' => 'A confirmação não confere com a senha.',
        ]);

        $email = $validated['email'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
        ]);
        // Convidado novo só enxerga os módulos de estudo; o dono amplia depois se quiser.
        $user->forceFill([
            'modulos' => config('modulos.padrao_convidado'),
            'must_change_password' => true,
        ])->save();

        AllowedGoogleEmail::firstOrCreate(
            ['email' => $email],
            ['nome' => $validated['name'], 'created_by' => Auth::id()]
        );

        return back()->with('success', "Usuário '{$email}' criado. Ele deve trocar a senha no primeiro acesso.");
    }

    /**
     * Define uma nova senha para um usuário (ex.: quem esqueceu). Ele é obrigado a trocá-la no próximo acesso.
     * Gestores não redefinem senha de outros gestores nem do dono; só o dono faz isso.
     */
    public function definirSenha(Request $request, User $user)
    {
        $admin = Auth::user();
        if (!$admin?->is_admin) {
            abort(403, 'Acesso restrito ao administrador do sistema.');
        }
        if ($user->id === $admin->id) {
            return back()->with('info', 'Para trocar a sua senha, use "Alterar minha senha".');
        }
        if (($user->is_owner || $user->is_admin) && !$admin->is_owner) {
            abort(403, 'Só o dono do sistema pode redefinir a senha de um gestor.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'A confirmação não confere com a senha.',
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ])->save();

        return back()->with('success', "Senha de {$user->name} definida. Ela deve trocá-la no próximo acesso.");
    }
}
