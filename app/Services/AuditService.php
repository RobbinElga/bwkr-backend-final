<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /** Catat perubahan: siapa, aksi apa, model apa, dari nilai apa ke apa. */
    public function log(string $action, ?Model $model = null, array $old = [], array $new = []): void
    {
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => $model ? $model::class : null,
            'model_id'   => $model?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
