<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Notifications\PasswordResetLink;

class AuthController extends Controller
{
    /**
     * Maximální počet pokusů o přihlášení v časovém okně.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Délka zámku v sekundách po vyčerpání pokusů.
     */
    private const LOCKOUT_SECONDS = 900;

    /**
     * Zobrazení loginu.
     */
    public function showLogin()
    {
        if (!empty(session('user'))) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * Přihlášení uživatele s rate limitingem.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $throttleKey = $this->throttleKey($request);

        // Kontrola rate limitu PŘED pokusem o login
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Login rate limit exceeded', [
                'ip' => $request->ip(),
                'username' => $request->username,
                'retry_after' => $seconds,
            ]);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'login' => "Příliš mnoho pokusů o přihlášení. Zkuste to znovu za {$seconds} sekund.",
                ]);
        }

        // Načtení uživatele s RBAC vztahy
        $user = User::with('roles.permissions')
            ->where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Neúspěšný pokus — započítat do rate limitu
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            $remaining = RateLimiter::remaining($throttleKey, self::MAX_LOGIN_ATTEMPTS);

            $errorMessage = 'Neplatné údaje nebo neaktivní účet.';
            if ($remaining <= 2 && $remaining > 0) {
                $errorMessage .= " Zbývá {$remaining} pokusů.";
            }

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $errorMessage]);
        }

        // ✅ Úspěšné přihlášení — vyčistit rate limit
        RateLimiter::clear($throttleKey);

        // Aktualizace posledního přihlášení
        $user->update(['last_login' => now()]);

        // Získání oprávnění z RBAC rolí
        $permissions = collect();
        foreach ($user->roles as $role) {
            if ($role->permissions) {
                $permissions = $permissions->merge($role->permissions);
            }
        }

        // Kompletní RBAC session
        session([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $permissions->unique('name')->pluck('name')->toArray(),
                'firstname' => $user->firstname ?? '',
                'lastname' => $user->lastname ?? '',
                'alias' => $user->alias ?? null,
                'is_super_admin' => $user->hasRole('super_admin'),
                'is_admin' => $user->hasRole('admin') || $user->hasRole('super_admin'),
            ],
            'last_activity' => time(),
            'login_timestamp' => time(),
        ]);

        $request->session()->regenerate();

        // Debug log pouze v lokálním prostředí
        if (app()->isLocal()) {
            Log::info('RBAC Login:', [
                'user' => $user->username,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions_count' => $permissions->unique('name')->count(),
                'is_super_admin' => $user->hasRole('super_admin'),
            ]);
        }

        return redirect()->intended('/');
    }

    /**
     * Odhlášení.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Byli jste úspěšně odhlášeni.');
    }

    /**
     * Kontrola platnosti session (volá se z JS).
     */
    public function checkSession(Request $request): JsonResponse
    {
        if (empty(session('user'))) {
            return response()->json([
                'authenticated' => false,
            ], 401);
        }

        $lastActivity = session('last_activity', time());
        $lifetime = config('session.lifetime', 120) * 60; // v sekundách
        $remaining = max(0, $lifetime - (time() - $lastActivity));

        return response()->json([
            'authenticated' => true,
            'remaining_seconds' => $remaining,
            'username' => session('user.username'),
        ]);
    }

    /**
     * Prodloužení session (volá se z JS na aktivitu uživatele).
     */
    public function extendSession(Request $request): JsonResponse
    {
        if (empty(session('user'))) {
            return response()->json(['success' => false], 401);
        }

        session(['last_activity' => time()]);

        return response()->json([
            'success' => true,
            'last_activity' => time(),
        ]);
    }

    /**
     * Sestavení klíče pro rate limiter.
     * Kombinace IP + username zajistí, že útok na jednoho uživatele
     * nezablokuje přístup ostatních ze stejné sítě (firemní NAT).
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('username', '')) . '|' . $request->ip();
    }

    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

    // Vždy vrátíme stejnou zprávu - žádné enumeration
    $genericMessage = 'Pokud zadaný e-mail patří aktivnímu účtu, byl odeslán odkaz pro reset hesla.';

    $user = User::where('email', $request->email)->where('is_active', true)->first();

    if ($user) {
        $token = Str::random(64);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()]
        );

        try {
            $user->notify(new PasswordResetLink($token, $user->email));
        } catch (\Exception $e) {
            Log::error('Reset email failed', ['error' => $e->getMessage()]);
        }
    } else {
        Log::info('Password reset requested for unknown/inactive email', ['email' => $request->email, 'ip' => $request->ip()]);
    }

    return back()->with('status', $genericMessage);
}

    public function showReset($token, Request $request)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
    $request->validate([
        'token'    => 'required|string',
        'email'    => 'required|email',
        'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
    ]);

    $record = \DB::table('password_reset_tokens')->where('email', $request->email)->first();
    if (!$record || !Hash::check($request->token, $record->token)) {
        return back()->withErrors(['email' => 'Neplatný nebo expirovaný odkaz.']);
    }

    if (now()->diffInMinutes($record->created_at) > 60) {
        return back()->withErrors(['email' => 'Odkaz vypršel.']);
    }

    $user = User::where('email', $request->email)->where('is_active', true)->first();
    if (!$user) {
        return back()->withErrors(['email' => 'Účet neexistuje nebo je neaktivní.']);
    }

    $user->update(['password' => Hash::make($request->password)]);
    \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return redirect('/login')->with('status', 'Heslo bylo úspěšně změněno. Můžete se přihlásit.');
    }

}
