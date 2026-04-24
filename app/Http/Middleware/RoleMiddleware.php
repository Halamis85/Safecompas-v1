<?php
// app/Http/Middleware/RoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!session('user')) {
            return redirect('/login');
        }

        $user = \App\Models\User::find(session('user')['id']);

        if (!$user || !$user->is_active) {
            return redirect('/login')->withErrors(['auth' => 'Uživatelský účet není aktivní']);
        }

        // Kontrola rolí
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return abort(403, 'Nemáte oprávnění k přístupu na tuto stránku');
    }
}
