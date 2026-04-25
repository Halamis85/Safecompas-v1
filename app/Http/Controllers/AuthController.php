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

class AuthController extends Controller
{
    /**
     * Maximální počet pokusů o přihlášení v časovém okně.
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Délka zámku v sekundách po vyčerpání pokusů.
     */
    private const LOCKOUT_SECONDS = 60;

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
}
