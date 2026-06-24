<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpactVideo extends Model
{
    use HasFactory;

    protected $fillable = ['youtube_url', 'caption', 'program_id', 'project_id', 'order'];

    protected $attributes = ['order' => 0];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    /** Ambil ID video YouTube dari berbagai format URL (untuk embed di frontend). */
    public function getYoutubeIdAttribute(): ?string
    {
        if (! $this->youtube_url) {
            return null;
        }

        preg_match('%(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})%', $this->youtube_url, $m);

        return $m[1] ?? null;
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
