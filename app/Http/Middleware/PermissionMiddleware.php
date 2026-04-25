<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect('/login');
        }

        // FIX V-11: Super admin bypass přes session - žádný DB query
        if (!empty($sessionUser['is_super_admin'])) {
            return $next($request);
        }

        // FIX V-11: Permissions už jsou v session od loginu
        $permissions = $sessionUser['permissions'] ?? [];
        if (!in_array($permission, $permissions, true)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nemáte oprávnění'], 403);
            }
            return abort(403, 'Nemáte oprávnění k této akci');
        }

        return $next($request);
    }
}
