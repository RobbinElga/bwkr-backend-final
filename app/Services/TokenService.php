<?php

namespace App\Services;

use App\Models\User;

class TokenService
{
    /** Terbitkan token akses penuh sesuai role (TTL & scope berbeda). */
    public function issue(User $user): array
    {
        $role = $user->role->value;
        $ttl  = (int) config("bwkr.token_ttl.$role", 60 * 8);
        $scope = $user->isStaff() ? 'staff' : 'donatur';   // pemisah token staff vs donatur

        $token = $user->createToken('auth', [$scope], now()->addMinutes($ttl));

        return [
            'token'      => $token->plainTextToken,
            'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
        ];
    }
}
