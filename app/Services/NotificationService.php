<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Kirim notifikasi ke semua staf AKTIF dengan role tertentu.
     * Pelaku aksi (user yang sedang login) otomatis dikecualikan
     * agar tidak menerima notifikasi atas aksinya sendiri.
     */
    public function notifyRoles(array $roles, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $actorId = Auth::id();

        $userIds = User::query()
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->when($actorId, fn($q) => $q->where('id', '!=', $actorId))
            ->pluck('id')
            ->all();

        $this->createMany($userIds, $type, $title, $body, $link);
    }

    /** Kirim notifikasi ke satu user tertentu (mis. pembuat klaim saat di-approve). */
    public function notifyUser(?int $userId, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        if (! $userId) return;
        $this->createMany([$userId], $type, $title, $body, $link);
    }

    private function createMany(array $userIds, string $type, string $title, ?string $body, ?string $link): void
    {
        if (empty($userIds)) return;

        $now = now();
        $rows = array_map(fn($uid) => [
            'user_id'    => $uid,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'link'       => $link,
            'read_at'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        AdminNotification::insert($rows); // bulk insert, satu query
    }
}
