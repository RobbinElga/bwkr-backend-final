<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'image', 'description', 'status', 'order'];
    protected $attributes = [
        'status' => 'aktif',
        'order'  => 0,
    ];

    protected function casts(): array
    {
        return ['status' => ProgramStatus::class];
    }

    /** URL publik gambar (dipakai frontend). */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn() => $this->image ? Storage::disk('public')->url($this->image) : null
        );
    }

    /** Route model binding pakai slug, bukan id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function donations(): HasMany
    {
        return $this->hasMany(DonationInput::class);
    }
}
