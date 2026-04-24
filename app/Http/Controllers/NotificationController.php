<?php

// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user() ?? $this->getCurrentUser();

        if (!$user) {
            return response()->json(['notifications' => []]);
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->format('d.m.Y H:i')
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $user = auth()->user() ?? $this->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false]);
        }

        if ($request->has('id')) {
            // Označit konkrétní notifikaci
            $user->notifications()
                ->where('id', $request->id)
                ->update(['read_at' => now()]);
        } else {
            // Označit všechny jako přečtené
            $user->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user() ?? $this->getCurrentUser();

        if (!$user) {
            return response()->json(['success' => false]);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    // Helper pro získání současného uživatele ze session
    private function getCurrentUser()
    {
        $sessionUser = session('user');
        if ($sessionUser && isset($sessionUser['id'])) {
            return \App\Models\User::find($sessionUser['id']);
        }
        return null;
    }
}
