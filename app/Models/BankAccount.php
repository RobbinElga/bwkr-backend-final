<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['type', 'bank_name', 'account_number', 'account_name', 'initial_balance', 'logo', 'qris_image', 'is_active', 'created_by'];

    protected $attributes = ['is_active' => true, 'type' => 'bank'];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',   // otomatis enkripsi/dekripsi
            'is_active'      => 'boolean',
            'initial_balance' => 'integer',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getQrisImageUrlAttribute(): ?string
    {
        return $this->qris_image ? Storage::disk('public')->url($this->qris_image) : null;
    }
}
