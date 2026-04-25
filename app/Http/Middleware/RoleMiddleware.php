<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect('/login');
        }

        if (!empty($sessionUser['is_super_admin'])) {
            return $next($request);
        }

        $userRoles = $sessionUser['roles'] ?? [];
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Nemáte oprávnění'], 403);
        }
        return abort(403, 'Nemáte oprávnění k přístupu na tuto stránku');
    }
}