<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->get('user_id', 1));

        $notifications = $user->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($n) => array_merge($n->data, [
                'id'         => $n->id,
                'created_at' => $n->created_at->diffForHumans(),
                'read'       => false,
            ]));

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->get('user_id', 1));

        if ($request->has('notification_id')) {
            $user->notifications()->findOrFail($request->notification_id)->markAsRead();
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Marked as read']);
    }
}
