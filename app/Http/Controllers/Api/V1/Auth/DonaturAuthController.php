<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterDonaturRequest;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class DonaturAuthController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    /** POST /auth/donatur/register */
    public function register(RegisterDonaturRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => $request->password,         // di-hash otomatis oleh cast
            'role'      => UserRole::Donatur->value,
            'is_active' => true,
        ]);

        $auth = $this->tokens->issue($user);

        return response()->json([
            'message'    => 'Registrasi berhasil.',
            'token'      => $auth['token'],
            'expires_at' => $auth['expires_at'],
            'user'       => $this->payload($user),
        ], 201);
    }

    /** POST /auth/donatur/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau kata sandi salah.'], 401);
        }

        if ($user->role !== UserRole::Donatur) {
            return response()->json(['message' => 'Gunakan halaman login staff.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun nonaktif.'], 403);
        }

        $auth = $this->tokens->issue($user);

        return response()->json([
            'message'    => 'Login berhasil.',
            'token'      => $auth['token'],
            'expires_at' => $auth['expires_at'],
            'user'       => $this->payload($user),
        ]);
    }

    private function payload(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role'  => $user->role->value,
        ];
    }
}
