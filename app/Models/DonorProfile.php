<?php

namespace App\Models;

use App\Enums\DonorTier;
use Illuminate\Database\Eloquent\Model;

class DonorProfile extends Model
{
    protected $fillable = ['donor_phone_hash', 'donor_name', 'tier', 'notes'];

    protected $attributes = ['tier' => 'reguler'];

    protected function casts(): array
    {
        return ['tier' => DonorTier::class];
    }
}
