<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use App\Services\TokenService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class StaffAuthController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly TwoFactorService $twoFactor,
    ) {}

    /** POST /auth/masuk-sistem — login staff (email + password). */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau kata sandi salah.'], 401);
        }

        if (! $user->isStaff()) {
            return response()->json(['message' => 'Akun ini bukan akun staff.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun nonaktif. Hubungi Super Admin.'], 403);
        }

        $ttl = (int) config('bwkr.two_factor.challenge_ttl_minutes', 5);

        // Belum aktifkan 2FA -> wajib setup dulu (2FA mandatory untuk staff)
        if (! $user->two_factor_enabled) {
            $setup = $user->createToken('2fa-setup', ['2fa-setup'], now()->addMinutes($ttl));

            return response()->json([
                'status'      => '2fa_setup_required',
                'message'     => '2FA belum aktif. Lakukan setup terlebih dahulu.',
                'setup_token' => $setup->plainTextToken,
            ]);
        }

        // Sudah aktif -> minta kode 2FA (terbitkan challenge token sementara)
        $challenge = $user->createToken('2fa-challenge', ['2fa-challenge'], now()->addMinutes($ttl));

        return response()->json([
            'status'          => '2fa_required',
            'message'         => 'Masukkan kode 2FA Anda.',
            'challenge_token' => $challenge->plainTextToken,
        ]);
    }

    /** POST /auth/masuk-sistem/2fa — verifikasi kode TOTP atau backup code. */
    public function verify(TwoFactorChallengeRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->tokenCan('2fa-challenge'), 403, 'Token tidak sah untuk verifikasi 2FA.');

        $valid = $this->twoFactor->verifyCode($user->two_factor_secret, $request->code)
            || $this->twoFactor->consumeBackupCode($user, $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Kode 2FA tidak valid.'], 422);
        }

        // Tantangan lolos: hapus challenge token, terbitkan token penuh
        $user->currentAccessToken()->delete();
        $auth = $this->tokens->issue($user);

        return response()->json([
            'message'    => 'Login berhasil.',
            'token'      => $auth['token'],
            'expires_at' => $auth['expires_at'],
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
        ]);
    }
}
