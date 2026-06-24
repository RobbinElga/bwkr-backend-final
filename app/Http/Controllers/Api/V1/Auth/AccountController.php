<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /** GET /auth/me — data user yang sedang login. */
    /** GET /auth/me — data user yang sedang login. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'phone'              => $user->phone,
            'role'               => $user->role->value,
            'avatar_url'         => $user->avatar_url,
            'is_active'          => $user->is_active,
            'two_factor_enabled' => $user->two_factor_enabled,
            'created_at'         => $user->created_at,
        ]);
    }
    /** POST /auth/logout — cabut token yang sedang dipakai. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /** PUT /auth/password — ganti kata sandi. */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Kata sandi saat ini salah.'], 422);
        }

        $user->password = $request->password;   // di-hash otomatis
        $user->save();

        // Keamanan: cabut semua token LAIN, sisakan token yang sedang dipakai
        $currentId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentId)->delete();

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }
}
