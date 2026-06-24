<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Achievement\StoreAchievementRequest;
use App\Http\Requests\Achievement\UpdateAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Services\AuditService;
use App\Services\ImageService;

class AchievementController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index()
    {
        return AchievementResource::collection(Achievement::orderBy('order')->latest()->paginate(15));
    }

    public function store(StoreAchievementRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->store($request->file('image'), 'achievements');
        }

        $achievement = Achievement::create($data);
        $this->audit->log('created', $achievement, new: $achievement->toArray());

        return (new AchievementResource($achievement))->response()->setStatusCode(201);
    }

    public function show(Achievement $achievement)
    {
        return new AchievementResource($achievement);
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement)
    {
        $old  = $achievement->toArray();
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->images->delete($achievement->image);
            $data['image'] = $this->images->store($request->file('image'), 'achievements');
        }

        $achievement->update($data);
        $this->audit->log('updated', $achievement, $old, $achievement->fresh()->toArray());

        return new AchievementResource($achievement->fresh());
    }

    public function destroy(Achievement $achievement)
    {
        $achievement->delete();
        $this->audit->log('deleted', $achievement);

        return response()->json(['message' => 'Pencapaian berhasil dihapus.']);
    }
}
