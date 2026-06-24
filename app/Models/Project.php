<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'name',
        'slug',
        'images',
        'description',
        'start_date',
        'end_date',
        'target_amount',
        'status',
    ];

    protected $attributes = [
        'status'        => 'draft',
        'target_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'images'        => 'array',
            'start_date'    => 'date',
            'end_date'      => 'date',
            'target_amount' => 'integer',
            'status'        => ProjectStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->orderByDesc('published_at');
    }

    /** Semua klaim donasi ke proyek ini. */
    public function donationClaims(): HasMany
    {
        return $this->hasMany(DonationClaim::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /** URL publik semua gambar (untuk frontend). */
    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn($p) => Storage::disk('public')->url($p))
            ->all();
    }

    /*
     |-------------------------------------------------------------
     | FIELD TERHITUNG (read-only)
     | Stub 0 dulu; disambung ke donations_claim (Sprint 3) & expenses (Sprint 4).
     |-------------------------------------------------------------
     */

    /** Total dana terkumpul = SUM(donations_claim approved). */
    public function getAmountRaisedAttribute(): int
    {
        return (int) $this->donationClaims()
            ->where('status', 'approved')
            ->sum('amount');
    }

    /** Total dana terpakai = SUM(expenses approved). */
    public function getAmountSpentAttribute(): int
    {
        return (int) $this->expenses()
            ->where('status', 'approved')
            ->sum('amount');
    }

    /** Persentase progress (0-100). Membaca dana TERKUMPUL — tidak dikurangi pengeluaran. */
    public function getProgressPercentAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return round(min(100, ($this->amount_raised / $this->target_amount) * 100), 2);
    }

    /** Kekurangan menuju target. */
    public function getShortfallAttribute(): int
    {
        return max(0, $this->target_amount - $this->amount_raised);
    }

    /** Sisa dana (terkumpul - terpakai). */
    public function getRemainingFundsAttribute(): int
    {
        return $this->amount_raised - $this->amount_spent;
    }

    public function bankAccounts(): BelongsToMany
    {
        return $this->belongsToMany(BankAccount::class);
    }
}
