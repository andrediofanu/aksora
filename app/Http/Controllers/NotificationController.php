<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Tandai satu notifikasi dibaca. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'unread' => $request->user()->unreadNotifications()->count(),
                'url' => $url,
            ]);
        }

        return $url ? redirect($url) : back();
    }

    /** Tandai semua dibaca. */
    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok', 'unread' => 0]);
        }

        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }

    /** Ringkasan notifikasi untuk topbar (polling ringan). */
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_notifications' => $user->unreadNotifications()->count(),
            'unread_messages' => $user->totalUnreadMessages(),
            'items' => $user->notifications()->limit(8)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notifikasi',
                'body' => $n->data['body'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
