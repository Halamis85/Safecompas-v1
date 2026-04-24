<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!session('user')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect('/login');
        }

        $user = \App\Models\User::find(session('user')['id']);

        if (!$user || !$user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account inactive'], 401);
            }
            return redirect('/login')->withErrors(['auth' => 'Uživatelský účet není aktivní']);
        }

        // Super admin vždy projde
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Nemáte oprávnění'], 403);
        }
        return abort(403, 'Nemáte oprávnění k přístupu na tuto stránku');
    }
}