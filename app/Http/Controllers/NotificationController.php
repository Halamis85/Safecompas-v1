<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($n) => [
                'id'              => $n->id,
                'type'            => class_basename($n->type),
                'data'            => $n->data,
                'read_at'         => $n->read_at,
                'created_at'      => $n->created_at->diffForHumans(),
                'created_at_full' => $n->created_at->format('d.m.Y H:i'),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        if ($request->has('id')) {
            $user->notifications()->where('id', $request->id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $user->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Získá uživatele ze session (vlastní auth v této aplikaci).
     */
    private function currentUser(): ?\App\Models\User
    {
        $sessionUser = session('user');
        if (!$sessionUser || !isset($sessionUser['id'])) {
            return null;
        }
        return \App\Models\User::find($sessionUser['id']);
    }
}
