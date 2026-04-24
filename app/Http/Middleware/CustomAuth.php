<?php
// app/Http/Middleware/CustomAuth.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class CustomAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Kontrola existence session
        if (empty(session('user'))) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect('/login');
        }

        // Kontrola platnosti uživatele v databázi
        $userId = session('user.id');
        $user = User::find($userId);

        if (!$user || !$user->is_active) {
            session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account inactive'], 401);
            }
            return redirect('/login')->withErrors(['auth' => 'Účet není aktivní']);
        }

        // Session timeout (30 minut nečinnosti)
        $lastActivity = session('last_activity');
        $sessionTimeout = config('session.lifetime', 120) * 60; // převod na sekundy

        if ($lastActivity && (time() - $lastActivity) > $sessionTimeout) {
            session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session expired'], 401);
            }
            return redirect('/login')->with('message', 'Relace vypršela z důvodu nečinnosti');
        }

        // Refresh activity timestamp
        session(['last_activity' => time()]);

        // Aktualizace last_login pouze jednou za 5 minut (optimalizace)
        $lastLoginUpdate = session('last_login_updated', 0);
        if ((time() - $lastLoginUpdate) > 300) { // 5 minut
            $user->update(['last_login' => now()]);
            session(['last_login_updated' => time()]);
        }

        return $next($request);
    }
}
