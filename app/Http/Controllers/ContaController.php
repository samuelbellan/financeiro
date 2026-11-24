<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ContaController extends Controller
{
    public function senhaForm()
    {
        return view('conta.senha', ['user' => auth()->user()]);
    }

    /**
     * Troca a senha do próprio usuário. Quem entra só pelo Google (sem senha ainda)
     * define a primeira senha sem informar a atual.
     */
    public function senhaSalvar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $regras = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
        if ($user->password !== null) {
            $regras['senha_atual'] = ['required', 'current_password'];
        }

        $dados = $request->validate($regras, [
            'senha_atual.required' => 'Informe a senha atual.',
            'senha_atual.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação não confere com a nova senha.',
        ]);

        $user->forceFill([
            'password' => Hash::make($dados['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()->intended(route('home'))->with('success', 'Senha alterada com sucesso.');
    }
}
