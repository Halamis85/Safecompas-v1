<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivity;
use App\Notifications\LoginReset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use function Laravel\Prompts\error;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'username', 'role', 'firstname', 'lastname', 'email')->get();
        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'string|in:user,admin',
            'alias' => 'nullable|string'
        ]);

        $user = User::create([
            'name' => trim($request->firstname . ' ' . $request->lastname),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'username' => $request->username,
            'alias' => $request->alias,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Uživatel byl úspěšně zaregistrován.'
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => "Uživatel s ID {$id} nebyl nalezen nebo již byl smazán."
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Uživatel s ID {$id} byl úspěšně smazán."
        ]);
    }

    public function sendLoginEmail(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|exists:users,username',
            'email' => 'required|email',
            'firstname' => 'required|string',
            'lastname' => 'required|string'
        ]);

        $user = User::where('username', $request->username)
            ->where('email', $request->email)
            ->where('firstname', $request->firstname)
            ->where('lastname', $request->lastname)
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Uživatel nenalezen.'], 404);
        }

        // Generování nového hesla
        $newPassword = bin2hex(random_bytes(4));
        $user->update(['password' => Hash::make($newPassword)]);
//pro odeslaní emailu
        try {
        $user->notify(new LoginReset($user, $newPassword));

        return response()->json([
            'status' => 'success',
            'message' => "Přihlašovací údaje byly odeslány na e-mail{$user->email}"
        ]);

    } catch (\Exception $e) {
            \Log::error('Chyba při odeslání emailu: ' . $e->getMessage());

            return response()->json([
                 'status' => 'success',
                 'message' => 'Heslo bylo restartováno. Email se nepodařilo odeslat, prosím kontaktujte administrátora. '
        ]);
        }
    }

    public function getUserActivity(): JsonResponse
    {
        $activities = UserActivity::with('user:id,firstname,lastname')
            ->select('timestamp', 'activity_type', 'details', 'user_id')
            ->orderBy('timestamp', 'desc')
            ->get()
            ->map(function ($activity) {
                return [
                    'timestamp' => $activity->timestamp,
                    'activity_type' => $activity->activity_type,
                    'details' => $activity->details,
                    'firstname' => $activity->user->firstname ?? '',
                    'lastname' => $activity->user->lastname ?? ''
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $activities
        ]);
    }
}
