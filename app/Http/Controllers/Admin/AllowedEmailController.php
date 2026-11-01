<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllowedGoogleEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
