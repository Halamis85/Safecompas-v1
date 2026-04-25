<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActivity;
use App\Notifications\LoginReset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    /**
     * Maximální počet pokusů resetu hesla z jedné IP (proti enumeraci).
     */
    private const MAX_RESET_ATTEMPTS = 10;

    /**
     * Časové okno pro počítání pokusů (sekundy).
     */
    private const RESET_THROTTLE_WINDOW = 60;

    /**
     * Seznam uživatelů s RBAC rolemi.
     */
    public function index(): JsonResponse
    {
        $users = User::with('roles:id,name,display_name')
            ->select('id', 'username', 'firstname', 'lastname', 'email', 'is_active')
            ->orderBy('lastname')
            ->get()
            ->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'username'   => $user->username,
                    'firstname'  => $user->firstname,
                    'lastname'   => $user->lastname,
                    'email'      => $user->email,
                    'is_active'  => $user->is_active,
                    'roles'      => $user->roles->pluck('display_name')->toArray(),
                    'role_names' => $user->roles->pluck('name')->toArray(),
                ];
            });

        return response()->json($users);
    }

    /**
     * Seznam dostupných RBAC rolí pro frontend formulář.
     */
    public function availableRoles(): JsonResponse
    {
        $query = Role::where('is_active', true)
            ->select('id', 'name', 'display_name', 'description');

        if (!session('user.is_super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        $roles = $query->orderBy('id')->get();

        return response()->json($roles);
    }

    /**
     * Vytvoření nového uživatele s RBAC rolí.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email:rfc,dns|max:255|unique:users,email',
            'username'  => 'required|string|min:3|max:50|alpha_dash|unique:users,username',
            'password'  => 'required|string|min:8|max:255',
            'role'      => 'required|string|exists:roles,name',
            'alias'     => 'nullable|string|max:50',
        ], [
            'firstname.required' => 'Jméno je povinné.',
            'lastname.required'  => 'Příjmení je povinné.',
            'email.required'     => 'E-mail je povinný.',
            'email.email'        => 'E-mail nemá platný formát.',
            'email.unique'       => 'Tento e-mail je již zaregistrován.',
            'username.required'  => 'Uživatelské jméno je povinné.',
            'username.min'       => 'Uživatelské jméno musí mít alespoň 3 znaky.',
            'username.alpha_dash'=> 'Uživatelské jméno může obsahovat pouze písmena, číslice, pomlčky a podtržítka.',
            'username.unique'    => 'Toto uživatelské jméno je již obsazeno.',
            'password.required'  => 'Heslo je povinné.',
            'password.min'       => 'Heslo musí mít alespoň 8 znaků.',
            'password.max'       => 'Heslo je příliš dlouhé (max. 255 znaků).',
            'role.required'      => 'Role je povinná.',
            'role.exists'        => 'Vybraná role neexistuje v systému.',
        ]);

        if ($validated['role'] === 'super_admin' && !session('user.is_super_admin')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pouze super_admin může vytvořit dalšího super_admina.',
            ], 403);
        }

        $user = User::create([
            'name'      => trim($validated['firstname'] . ' ' . $validated['lastname']),
            'firstname' => $validated['firstname'],
            'lastname'  => $validated['lastname'],
            'email'     => $validated['email'],
            'username'  => $validated['username'],
            'alias'     => $validated['alias'] ?? null,
            'password'  => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->giveRoleTo($validated['role']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Uživatel byl úspěšně zaregistrován.',
            'user_id' => $user->id,
        ], 201);
    }

    /**
     * Smazání uživatele s ochranami:
     * 1. Nelze smazat sám sebe.
     * 2. Pouze super_admin může smazat jiného super_admina.
     * 3. Nelze smazat posledního super_admina v systému.
     */
    public function destroy($id): JsonResponse
    {
        $user = User::with('roles:id,name')->find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => "Uživatel s ID {$id} nebyl nalezen nebo již byl smazán.",
            ], 404);
        }

        $currentUserId = session('user.id');
        $currentUserIsSuperAdmin = (bool) session('user.is_super_admin');
        $targetIsSuperAdmin = $user->roles->contains('name', 'super_admin');

        if ((int) $user->id === (int) $currentUserId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nelze smazat svůj vlastní účet. Požádejte jiného administrátora.',
            ], 403);
        }

        if ($targetIsSuperAdmin && !$currentUserIsSuperAdmin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pouze super_admin může smazat jiného super_admina.',
            ], 403);
        }

        if ($targetIsSuperAdmin) {
            $superAdminCount = User::whereHas('roles', function ($q) {
                $q->where('name', 'super_admin');
            })->where('is_active', true)->count();

            if ($superAdminCount <= 1) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Nelze smazat posledního super_admina. Nejprve vytvořte nebo aktivujte jiného super_admina.',
                ], 403);
            }
        }

        $deletedUsername = $user->username;
        $user->delete();

        Log::info("Uživatel '{$deletedUsername}' (ID: {$id}) byl smazán uživatelem ID: {$currentUserId}");

        return response()->json([
            'status'  => 'success',
            'message' => "Uživatel '{$deletedUsername}' byl úspěšně smazán.",
        ]);
    }

    /**
     * Reset hesla a odeslání nových přihlašovacích údajů.
     *
     * BEZPEČNOSTNÍ POZNÁMKA: Tato metoda je odolná proti username enumeraci.
     * Vrací vždy stejnou generickou odpověď bez ohledu na to, zda uživatel
     * existuje, je aktivní nebo ne. Detaily o úspěchu/neúspěchu lze najít
     * jen v server logu, nikoliv v HTTP odpovědi.
     */
    public function sendLoginEmail(Request $request): JsonResponse
    {
        // Throttle proti masivnímu skenování (10 pokusů / minutu z 1 IP)
        $throttleKey = 'reset.' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_RESET_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Reset password rate limit exceeded', [
                'ip' => $request->ip(),
                'admin_user_id' => session('user.id'),
                'retry_after' => $seconds,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => "Příliš mnoho pokusů. Zkuste to znovu za {$seconds} sekund.",
            ], 429);
        }
        RateLimiter::hit($throttleKey, self::RESET_THROTTLE_WINDOW);

        // Validace formátu, ne existence
        $request->validate([
            'username'  => 'required|string|max:50',
            'email'     => 'required|email|max:255',
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
        ]);

        // ⚠️ Generická odpověď - vždy stejná, bez ohledu na výsledek
        $genericResponse = [
            'status'  => 'success',
            'message' => 'Pokud zadané údaje souhlasí s platným uživatelem, byly přihlašovací údaje odeslány na zadaný e-mail.',
        ];

        $user = User::where('username', $request->username)
            ->where('email', $request->email)
            ->where('firstname', $request->firstname)
            ->where('lastname', $request->lastname)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            // Pokus o reset pro neexistujícího/neaktivního uživatele - logujeme pro audit,
            // ale uživateli vracíme stejnou odpověď jako při úspěchu.
            Log::info('Password reset attempted for non-matching user', [
                'username_attempted' => $request->username,
                'ip' => $request->ip(),
                'admin_user_id' => session('user.id'),
            ]);

            return response()->json($genericResponse);
        }

        // Generování nového hesla a odeslání e-mailu
        $newPassword = bin2hex(random_bytes(4));
        $user->update(['password' => Hash::make($newPassword)]);

        try {
            $user->notify(new LoginReset($user, $newPassword));

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'username' => $user->username,
                'admin_user_id' => session('user.id'),
            ]);
        } catch (\Exception $e) {
            // Heslo již bylo resetováno, ale e-mail selhal - logujeme,
            // ale stále vracíme generickou odpověď, abychom neposkytli timing rozdíl.
            Log::error('Password reset email failed to send', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'admin_user_id' => session('user.id'),
            ]);
        }

        return response()->json($genericResponse);
    }

    public function getUserActivity(): JsonResponse
    {
        $activities = UserActivity::with('user:id,firstname,lastname')
            ->select('id', 'created_at', 'action', 'table_name', 'old_values', 'new_values', 'user_id')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->map(function ($activity) {
                $details = $activity->table_name ? "Tabulka: {$activity->table_name}" : '';

                if ($activity->new_values) {
                    $keys = array_keys($activity->new_values);
                    $details .= ($details ? ' | ' : '') . 'Pole: ' . implode(', ', $keys);
                }

                return [
                    'timestamp'     => $activity->created_at,
                    'activity_type' => $activity->action,
                    'details'       => $details ?: '—',
                    'firstname'     => $activity->user->firstname ?? '',
                    'lastname'      => $activity->user->lastname ?? '',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $activities,
        ]);
    }
}
