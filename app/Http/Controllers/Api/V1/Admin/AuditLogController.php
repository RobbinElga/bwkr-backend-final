<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->model_type, fn($q, $m) => $q->where('model_type', $m))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return AuditLogResource::collection($logs);
    }
}
