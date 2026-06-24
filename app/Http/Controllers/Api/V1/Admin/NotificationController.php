<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /** Daftar notifikasi milik user + jumlah belum dibaca. */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $items = AdminNotification::where('user_id', $userId)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        $unread = AdminNotification::where('user_id', $userId)->unread()->count();

        return NotificationResource::collection($items)
            ->additional(['unread_count' => $unread]);
    }

    /** Tandai satu notifikasi dibaca (harus milik user). */
    public function markRead(AdminNotification $notification)
    {
        abort_if($notification->user_id !== Auth::id(), 404); // 404 utk resource bukan miliknya

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Ditandai dibaca.']);
    }

    /** Tandai semua notifikasi user dibaca. */
    public function markAllRead()
    {
        AdminNotification::where('user_id', Auth::id())->unread()->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua ditandai dibaca.']);
    }
}
