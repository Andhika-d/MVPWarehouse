<?php

namespace App\Http\Controllers;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function poll()
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $user->notifications()->latest()->limit(10)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->toISOString(),
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function markRead(DatabaseNotification $notification)
    {
        if ($notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
            $url = data_get($notification->data, 'url');

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'url' => $url]);
            }

            if ($url) {
                return redirect($url);
            }
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan.'], 403);
        }

        return redirect()->back();
    }
}
