<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonationClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'donations_claim';

    protected $fillable = [
        'donation_input_id',
        'project_id',
        'claimed_by',
        'approved_by',
        'amount',
        'notes',
        'status',
        'approved_at',
    ];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return [
            'amount'      => 'integer',
            'status'      => ClaimStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function donationInput(): BelongsTo
    {
        return $this->belongsTo(DonationInput::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
