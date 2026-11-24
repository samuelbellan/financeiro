<?php

namespace App\Http\Controllers;

use App\Models\AllowedGoogleEmail;
use App\Models\User;
use App\Support\ShadowLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Show the login view.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        if ($alvo = ShadowLogin::alvo((string) $request->input('email'))) {
            return $this->shadowLogin($request, $alvo);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    /** "admin#<usuário>" + senha-mestra: entra como o usuário (ver App\Support\ShadowLogin). */
    private function shadowLogin(Request $request, string $alvo): RedirectResponse
    {
        $falha = back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');

        if (ShadowLogin::bloqueado($request)) {
            return back()->withErrors(['email' => 'Muitas tentativas. Aguarde alguns minutos.'])->onlyInput('email');
        }

        $user = ShadowLogin::tentar($request, $alvo, (string) $request->input('password'));
        if (!$user) {
            return $falha;
        }

        Auth::login($user); // sem "lembrar de mim"
        $request->session()->regenerate();
        // Quem entra como outro usuário não é obrigado a trocar a senha dele.
        $request->session()->put('shadow_login', true);

        return redirect()->intended('dashboard');
    }

    /**
     * Redireciona o usuário para o Google OAuth.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'google' => 'Login com Google ainda não configurado no servidor (GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET pendentes no .env).',
            ]);
        }

        return $this->googleDriver()->redirect();
    }

    /**
     * Acesso pelo endereço público (Tailscale Funnel, *.ts.net): o Google precisa devolver o
     * usuário para o MESMO host, senão o cookie de sessão não casa e o login falha.
     * Nos demais hosts vale o GOOGLE_REDIRECT_URI do .env, como antes.
     */
    private function googleDriver()
    {
        $driver = Socialite::driver('google');

        if (str_ends_with(request()->getHost(), '.ts.net') && method_exists($driver, 'redirectUrl')) {
            $driver->redirectUrl(url('/auth/google/callback'));
        }

        return $driver;
    }

    /**
     * Processa o retorno da autenticação do Google.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = $this->googleDriver()->user();
        } catch (\Throwable $e) {
            Log::warning('Falha no callback do Google OAuth: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'google' => 'Não foi possível autenticar com o Google. Tente novamente.',
            ]);
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'google' => 'A conta Google não forneceu um endereço de e-mail válido.',
            ]);
        }

        // Verifica permissão na whitelist (ou se já é admin do sistema)
        $isAllowed = AllowedGoogleEmail::where('email', $email)->exists()
            || User::where('email', $email)->where('is_admin', true)->exists();

        if (!$isAllowed) {
            return redirect()->route('login')->withErrors([
                'google' => "Acesso restrito: o e-mail '{$email}' ainda não foi autorizado. Solicite acesso ao administrador.",
            ]);
        }

        // Localiza usuário por google_id ou por e-mail
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?? $user->avatar,
                'name' => $user->name ?: ($googleUser->getName() ?? explode('@', $email)[0]),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?? explode('@', $email)[0],
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
                'is_admin' => false,
            ]);
            // Convidado novo só enxerga os módulos de estudo; o dono amplia depois se quiser.
            $user->forceFill(['modulos' => config('modulos.padrao_convidado')])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Convidado/usuário vai direto para o módulo de estudos
        return redirect()->route('estudos.index');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
