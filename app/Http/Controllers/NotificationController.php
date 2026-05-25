<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->filter(fn (DatabaseNotification $n) => ($n->data['type'] ?? null) !== 'case_message')
            ->take(30)
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'type' => $n->data['type'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
                'created_at_raw' => $n->created_at->toIso8601String(),
            ]);

        $unreadCount = $user->unreadNotifications
            ->filter(fn (DatabaseNotification $n) => ($n->data['type'] ?? null) !== 'case_message')
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->values(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $unreadCount = $request->user()->unreadNotifications
            ->filter(fn (DatabaseNotification $n) => ($n->data['type'] ?? null) !== 'case_message')
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications
            ->filter(fn (DatabaseNotification $n) => ($n->data['type'] ?? null) !== 'case_message')
            ->markAsRead();

        $unreadCount = $request->user()->unreadNotifications
            ->filter(fn (DatabaseNotification $n) => ($n->data['type'] ?? null) !== 'case_message')
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
