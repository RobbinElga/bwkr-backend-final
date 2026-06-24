<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EnableTwoFactorRequest;
use App\Models\User;
use App\Services\TokenService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly TokenService $tokens,
    ) {}

    /** GET /auth/2fa/setup — buat secret + QR untuk di-scan authenticator. */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureCanManage2fa($user);

        $secret = $this->twoFactor->generateSecret();
        $user->two_factor_secret = $secret;   // tersimpan terenkripsi, BELUM diaktifkan
        $user->save();

        return response()->json([
            'secret'      => $secret,
            'otpauth_url' => $this->twoFactor->otpauthUrl($user->email, $secret),
            'qr_svg'      => $this->twoFactor->qrCodeSvg($user->email, $secret),
        ]);
    }

    /** POST /auth/2fa/enable — verifikasi kode, aktifkan 2FA, kirim backup codes. */
    public function enable(EnableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureCanManage2fa($user);

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'Belum ada setup. Panggil /2fa/setup dulu.'], 422);
        }

        if (! $this->twoFactor->verifyCode($user->two_factor_secret, $request->code)) {
            return response()->json(['message' => 'Kode 2FA tidak valid.'], 422);
        }

        // Aktifkan 2FA + buat backup codes (plaintext DITAMPILKAN SEKALI saja)
        $backupCodes = $this->twoFactor->generateBackupCodes();

        $user->two_factor_enabled        = true;
        $user->two_factor_confirmed_at   = now();
        $user->two_factor_recovery_codes = json_encode($this->twoFactor->hashBackupCodes($backupCodes));
        $user->save();

        // Setup selesai = sekalian login: hapus token setup, terbitkan token penuh
        $user->currentAccessToken()->delete();
        $auth = $this->tokens->issue($user);

        return response()->json([
            'message'      => '2FA aktif. Simpan kode cadangan ini di tempat aman — tidak akan ditampilkan lagi.',
            'backup_codes' => $backupCodes,
            'token'        => $auth['token'],
            'expires_at'   => $auth['expires_at'],
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
            ],
        ]);
    }

    /** Hanya token setup (saat first-login) atau token staff penuh yang boleh kelola 2FA. */
    private function ensureCanManage2fa(User $user): void
    {
        abort_unless($user->isStaff(), 403, 'Hanya staff yang menggunakan 2FA.');
        abort_unless(
            $user->tokenCan('2fa-setup') || $user->tokenCan('staff'),
            403,
            'Token tidak sah untuk mengelola 2FA.'
        );
    }
}
