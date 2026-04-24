<?php
// app/Http/Controllers/AuthController.php - NAHRADIT CELÝ OBSAH

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (!empty(session('user'))) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // ✅ OPRAVENO: User model s RBAC vztahy
        $user = User::with('roles.permissions')
            ->where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {

            // Aktualizace posledního přihlášení
            $user->update(['last_login' => now()]);

            // ✅ OPRAVENO: Získání oprávnění z RBAC rolí
            $permissions = collect();
            foreach ($user->roles as $role) {
                if ($role->permissions) {
                    $permissions = $permissions->merge($role->permissions);
                }
            }

            // ✅ OPRAVENO: Kompletní RBAC session
            session([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role, // Zachovat starý sloupec
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $permissions->unique('name')->pluck('name')->toArray(),
                    'firstname' => $user->firstname ?? '',
                    'lastname' => $user->lastname ?? '',
                    'alias' => $user->alias ?? null,
                    'is_super_admin' => $user->hasRole('super_admin'),
                    'is_admin' => $user->hasRole('admin') || $user->hasRole('super_admin')
                ],
                'last_activity' => time(),
                'login_timestamp' => time()
            ]);

            $request->session()->regenerate();

            // 🔍 DEBUG log
            \Log::info('RBAC Login:', [
                'user' => $user->username,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions_count' => $permissions->unique('name')->count(),
                'is_super_admin' => $user->hasRole('super_admin')
            ]);

            return redirect('/');
        }

        return back()->withErrors(['login' => 'Neplatné údaje nebo neaktivní účet.']);
    }

    public function logout()
    {
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/login')->with('success', 'Byli jste úspěšně odhlášeni.');
    }

    /**
     * AJAX endpoint pro kontrolu aktivity session
     */
    public function checkSession(Request $request)
    {
        if (empty(session('user'))) {
            return response()->json(['authenticated' => false]);
        }

        $lastActivity = session('last_activity');
        $sessionTimeout = config('session.lifetime', 120) * 60; // převod na sekundy

        if ($lastActivity && (time() - $lastActivity) > $sessionTimeout) {
            session()->flush();
            return response()->json(['authenticated' => false, 'reason' => 'timeout']);
        }

        // Refresh activity
        session(['last_activity' => time()]);

        return response()->json([
            'authenticated' => true,
            'user' => session('user'),
            'remaining_time' => $sessionTimeout - (time() - $lastActivity)
        ]);
    }

    /**
     * Prodloužení session
     */
    public function extendSession(Request $request)
    {
        if (empty(session('user'))) {
            return response()->json(['success' => false]);
        }

        session(['last_activity' => time()]);

        return response()->json([
            'success' => true,
            'message' => 'Session prodloužena',
            'remaining_time' => config('session.lifetime', 120) * 60
        ]);
    }
}
