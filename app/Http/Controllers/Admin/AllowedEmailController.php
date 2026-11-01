<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllowedGoogleEmail;
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

        return view('admin.convites', compact('emails'));
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
        $record->delete();

        return back()->with('success', "Acesso do e-mail '{$record->email}' removido com sucesso.");
    }
}
