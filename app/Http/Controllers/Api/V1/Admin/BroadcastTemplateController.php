<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreBroadcastTemplateRequest;
use App\Http\Requests\Crm\UpdateBroadcastTemplateRequest;
use App\Http\Resources\BroadcastTemplateResource;
use App\Models\BroadcastTemplate;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class BroadcastTemplateController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index()
    {
        return BroadcastTemplateResource::collection(BroadcastTemplate::latest()->paginate(15));
    }

    public function store(StoreBroadcastTemplateRequest $request)
    {
        $template = BroadcastTemplate::create($request->validated() + ['created_by' => Auth::id()]);
        $this->audit->log('created', $template, new: $template->toArray());

        return (new BroadcastTemplateResource($template))->response()->setStatusCode(201);
    }

    public function update(UpdateBroadcastTemplateRequest $request, BroadcastTemplate $template)
    {
        $old = $template->toArray();
        $template->update($request->validated());
        $this->audit->log('updated', $template, $old, $template->fresh()->toArray());

        return new BroadcastTemplateResource($template->fresh());
    }

    public function destroy(BroadcastTemplate $template)
    {
        $template->delete();
        $this->audit->log('deleted', $template);

        return response()->json(['message' => 'Template berhasil dihapus.']);
    }
}
