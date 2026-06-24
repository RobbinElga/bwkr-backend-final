<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'count', 'label', 'period', 'order'];

    protected $attributes = ['count' => 0, 'order' => 0];

    protected function casts(): array
    {
        return ['count' => 'integer', 'order' => 'integer'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
