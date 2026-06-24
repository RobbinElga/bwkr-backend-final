<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_secret'       => 'encrypted',
            'two_factor_enabled'      => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'is_active'               => 'boolean',
            'role'                    => UserRole::class,
        ];
    }

    /**
     * Nomor HP: enkripsi saat disimpan, dekripsi saat dibaca, sekaligus
     * mengisi phone_hash dalam satu langkah agar tetap bisa dicari.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn(?string $value) => [
                'phone'      => $value ? Crypt::encryptString($value) : null,
                'phone_hash' => $value ? self::hashPhone($value) : null,
            ],
        );
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    /** Hash pencarian (bukan keamanan password) dari nomor ternormalisasi. */
    public static function hashPhone(string $phone): string
    {
        return hash('sha256', self::normalizePhone($phone));
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }
    /** URL publik foto profil (null jika belum upload). */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn() => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null
        );
    }
}
