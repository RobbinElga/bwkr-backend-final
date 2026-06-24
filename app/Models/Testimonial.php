<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'title', 'photo', 'content', 'is_visible', 'order'];

    protected $attributes = ['is_visible' => true, 'order' => 0];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }
}
