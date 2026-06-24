<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImpactVideo\StoreImpactVideoRequest;
use App\Http\Requests\ImpactVideo\UpdateImpactVideoRequest;
use App\Http\Resources\ImpactVideoResource;
use App\Models\ImpactVideo;
use App\Services\AuditService;

class ImpactVideoController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index()
    {
        return ImpactVideoResource::collection(ImpactVideo::orderBy('order')->latest()->paginate(15));
    }

    public function store(StoreImpactVideoRequest $request)
    {
        $video = ImpactVideo::create($request->validated());
        $this->audit->log('created', $video, new: $video->toArray());

        return (new ImpactVideoResource($video))->response()->setStatusCode(201);
    }

    public function show(ImpactVideo $impactVideo)
    {
        return new ImpactVideoResource($impactVideo);
    }

    public function update(UpdateImpactVideoRequest $request, ImpactVideo $impactVideo)
    {
        $old = $impactVideo->toArray();
        $impactVideo->update($request->validated());
        $this->audit->log('updated', $impactVideo, $old, $impactVideo->fresh()->toArray());

        return new ImpactVideoResource($impactVideo->fresh());
    }

    public function destroy(ImpactVideo $impactVideo)
    {
        $impactVideo->delete();
        $this->audit->log('deleted', $impactVideo);

        return response()->json(['message' => 'Video dampak berhasil dihapus.']);
    }
}
