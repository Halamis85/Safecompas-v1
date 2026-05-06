<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LekarnickAccessMiddleware
{
    public function handle(Request $request, Closure $next, $access_level = 'view')
    {
        if (!session('user')) {
            return redirect('/login');
        }

        $user = \App\Models\User::find(session('user')['id']);
        $lekarnicky_id = $request->route('id') ?? $request->get('lekarnicky_id');

        if ($this->hasGlobalLekarnickyAccess((int) session('user')['id'])) {
            return $next($request);
        }

        if (!$user->canAccessLekarnicky($lekarnicky_id, $access_level)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nemáte přístup k této lékárničce'], 403);
            }
            return abort(403, 'Nemáte přístup k této lékárničce');
        }

        return $next($request);
    }

    private function hasGlobalLekarnickyAccess(int $userId): bool
    {
        if (session('user.is_super_admin')) {
            return true;
        }

        $perms = session('user.permissions', []);
        if (array_intersect(['lekarnicke.create', 'lekarnicke.edit', 'lekarnicke.delete'], $perms)) {
            return true;
        }

        $hasAssignedLekarnicky = DB::table('user_lekarnicky_access')
            ->where('user_id', $userId)
            ->exists();

        return in_array('lekarnicke.material', $perms, true) && !$hasAssignedLekarnicky;
    }
}
