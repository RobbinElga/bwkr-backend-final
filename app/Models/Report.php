<?php

namespace App\Models;

use App\Enums\ReportCategory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'year',
        'description',
        'cover',
        'file_path',
        'is_published',
        'order',
    ];

    protected $attributes = [
        'category'     => 'tahunan',
        'is_published' => true,
        'order'        => 0,
    ];

    protected function casts(): array
    {
        return [
            'category'     => ReportCategory::class,
            'year'         => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn() => $this->cover ? Storage::disk('public')->url($this->cover) : null);
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::get(fn() => $this->file_path ? Storage::disk('public')->url($this->file_path) : null);
    }
}
