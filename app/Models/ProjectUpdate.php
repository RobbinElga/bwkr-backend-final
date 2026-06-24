<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectUpdate extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'title', 'content', 'images', 'published_at', 'order'];

    protected $attributes = ['order' => 0];

    protected function casts(): array
    {
        return [
            'images'       => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn($p) => Storage::disk('public')->url($p))
            ->all();
    }
}
