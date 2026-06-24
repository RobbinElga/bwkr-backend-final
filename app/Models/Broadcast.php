<?php

namespace App\Models;

use App\Enums\BroadcastStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    protected $fillable = [
        'title',
        'message',
        'template_id',
        'tier',
        'status',
        'sent_at',
        'recipient_count',
        'created_by',
    ];

    protected $casts = [
        'status'  => BroadcastStatus::class,
        'sent_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BroadcastTemplate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
