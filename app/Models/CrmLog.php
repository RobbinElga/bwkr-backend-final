<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLog extends Model
{
    public $timestamps = false;   // hanya created_at

    protected $fillable = [
        'donor_phone_hash',
        'contacted_by',
        'channel',
        'template_id',
        'message',
        'status',
    ];

    protected $casts = ['created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(fn(CrmLog $log) => $log->created_at ??= now());
    }
}
