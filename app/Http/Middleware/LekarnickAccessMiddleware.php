<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LekarnickAccessMiddleware
{
    public function handle(Request $request, Closure $next, $access_level = 'view')
    {
        if (!session('user')) {
            return redirect('/login');
        }

        $user = \App\Models\User::find(session('user')['id']);
        $lekarnicky_id = $request->route('id') ?? $request->get('lekarnicky_id');

        if (!$user->canAccessLekarnicky($lekarnicky_id, $access_level)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nemáte přístup k této lékárničce'], 403);
            }
            return abort(403, 'Nemáte přístup k této lékárničce');
        }

        return $next($request);
    }
}
