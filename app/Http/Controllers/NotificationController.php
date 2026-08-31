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

        return redirect()->back();
    }

    public function markRead(DatabaseNotification $notification)
    {
        if ($notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();

            if ($url = data_get($notification->data, 'url')) {
                return redirect($url);
            }
        }

        return redirect()->back();
    }
}
