<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
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

        // Super admin má přístup ke všemu bez ohledu na konkrétní permission
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nemáte oprávnění'], 403);
            }
            return abort(403, 'Nemáte oprávnění k této akci');
        }

        return $next($request);
    }
}
