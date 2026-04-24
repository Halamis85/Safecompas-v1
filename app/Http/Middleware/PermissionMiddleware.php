<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!session('user')) {
            return redirect('/login');
        }

        $user = \App\Models\User::find(session('user')['id']);

        if (!$user || !$user->is_active) {
            return redirect('/login')->withErrors(['auth' => 'Uživatelský účet není aktivní']);
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
