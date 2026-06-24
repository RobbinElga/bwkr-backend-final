<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /** Buat secret key baru untuk 2FA. */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /** URL otpauth:// untuk di-scan aplikasi authenticator. */
    public function otpauthUrl(string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('bwkr.two_factor.issuer'),
            $email,
            $secret
        );
    }

    /** Render QR sebagai string SVG (langsung bisa ditampilkan frontend). */
    public function qrCodeSvg(string $email, string $secret): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString(
            $this->otpauthUrl($email, $secret)
        );
    }

    /** Verifikasi kode TOTP 6 digit terhadap secret. */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /** Hasilkan kode cadangan (plaintext) — ditampilkan ke user HANYA sekali. */
    public function generateBackupCodes(): array
    {
        $count = (int) config('bwkr.two_factor.backup_code_count', 8);

        return collect(range(1, $count))
            ->map(fn() => $this->formatBackupCode())
            ->all();
    }

    /** Hash kode cadangan untuk disimpan ke DB. */
    public function hashBackupCodes(array $codes): array
    {
        return array_map(fn($c) => hash('sha256', $this->normalizeCode($c)), $codes);
    }

    /**
     * Cek & konsumsi satu kode cadangan (one-time use).
     * true jika cocok; kode yang dipakai langsung dihapus dari user.
     */
    public function consumeBackupCode(User $user, string $code): bool
    {
        $stored = $user->two_factor_recovery_codes
            ? json_decode($user->two_factor_recovery_codes, true)
            : [];

        $hash  = hash('sha256', $this->normalizeCode($code));
        $index = array_search($hash, $stored, true);

        if ($index === false) {
            return false;
        }

        unset($stored[$index]);
        $user->two_factor_recovery_codes = json_encode(array_values($stored));
        $user->save();

        return true;
    }

    /** Kode cadangan: 10 karakter acak, dikelompokkan 5-5 agar mudah dibaca. */
    private function formatBackupCode(): string
    {
        $raw = strtoupper(Str::random(10));
        return substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
    }

    /** Samakan format saat hashing & verifikasi (buang tanda hubung/spasi). */
    private function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }
}
