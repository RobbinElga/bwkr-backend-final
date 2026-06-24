<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'type', 'pic_name', 'pic_phone', 'pic_email', 'logo', 'is_visible'];

    protected $attributes = ['is_visible' => true];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
