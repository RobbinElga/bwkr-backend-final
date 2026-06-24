<?php

namespace App\Models;

use App\Enums\DonationSource;
use App\Enums\DonationStatus;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DonationInput extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'donations_input';

    protected $fillable = [
        'ref_no',
        'user_id',
        'program_id',
        'project_id',
        'donor_name',
        'salutation',
        'donor_alias',
        'donor_phone',
        'donor_email',
        'amount',
        'on_behalf',
        'message',
        'proof_file',
        'bank_account_id',
        'input_by',
        'source',
        'status',
        'payment_method',
        'payment_gateway_ref',
    ];

    protected $attributes = [
        'source' => 'online',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'integer',
            'proof_file' => 'encrypted',
            'source'     => DonationSource::class,
            'status'     => DonationStatus::class,
        ];
    }

    protected function donorPhone(): Attribute
    {
        return Attribute::make(
            get: fn(?string $v) => $v ? Crypt::decryptString($v) : null,
            set: fn(?string $v) => [
                'donor_phone'      => $v ? Crypt::encryptString($v) : null,
                'donor_phone_hash' => $v ? self::hashPhone($v) : null,
            ],
        );
    }

    /** Nama lengkap dengan sapaan, mis. "Bu Aisyah" (untuk pesan WA). */
    protected function greetingName(): Attribute
    {
        return Attribute::get(
            fn() => trim(($this->salutation ? $this->salutation . ' ' : '') . $this->donor_name)
        );
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getHasProofAttribute(): bool
    {
        return ! empty($this->attributes['proof_file']);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    public static function hashPhone(string $phone): string
    {
        return hash('sha256', self::normalizePhone($phone));
    }

    public static function generateRefNo(): string
    {
        do {
            $ref = strtoupper(Str::random(8));
        } while (static::where('ref_no', $ref)->exists());

        return $ref;
    }

    /** Teks WA saat donasi diterima (pending) — bisa di-custom dari Pengaturan. */
    public function pendingNotificationText(): string
    {
        $default = "Assalamualaikum [Sapaan] [Nama],\n\n"
            . "Terima kasih. Donasi Anda sebesar [Nominal] (Ref: *[Ref]*) telah kami terima dan sedang diverifikasi.\n\n"
            . "Jazakumullah khairan.\n- BWKR";

        return app(SettingService::class)->renderTemplate('wa_pending_message', [
            '[Sapaan]'  => $this->salutation ?? '',
            '[Nama]'    => $this->donor_name,
            '[Nominal]' => 'Rp' . number_format($this->amount, 0, ',', '.'),
            '[Ref]'     => $this->ref_no,
        ], $default);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
