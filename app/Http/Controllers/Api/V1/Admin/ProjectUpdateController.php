<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectUpdate\StoreProjectUpdateRequest;
use App\Http\Requests\ProjectUpdate\UpdateProjectUpdateRequest;
use App\Http\Resources\ProjectUpdateResource;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Services\AuditService;
use App\Services\ImageService;

class ProjectUpdateController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index(Project $project)
    {
        return ProjectUpdateResource::collection($project->updates()->paginate(15));
    }

    public function store(StoreProjectUpdateRequest $request, Project $project)
    {
        $data = $request->validated();
        $data['published_at'] = $data['published_at'] ?? now();

        if ($request->hasFile('images')) {
            $data['images'] = $this->images->storeMany($request->file('images'), 'project-updates');
        }

        $update = $project->updates()->create($data);
        $this->audit->log('created', $update, new: $update->toArray());

        return (new ProjectUpdateResource($update))->response()->setStatusCode(201);
    }

    public function show(Project $project, ProjectUpdate $update)
    {
        return new ProjectUpdateResource($update);
    }

    public function update(UpdateProjectUpdateRequest $request, Project $project, ProjectUpdate $update)
    {
        $old  = $update->toArray();
        $data = $request->validated();

        if ($request->hasFile('images')) {
            foreach ($update->images ?? [] as $oldPath) {
                $this->images->delete($oldPath);
            }
            $data['images'] = $this->images->storeMany($request->file('images'), 'project-updates');
        }

        $update->update($data);
        $this->audit->log('updated', $update, $old, $update->fresh()->toArray());

        return new ProjectUpdateResource($update->fresh());
    }

    public function destroy(Project $project, ProjectUpdate $update)
    {
        foreach ($update->images ?? [] as $path) {
            $this->images->delete($path);
        }

        $update->delete();
        $this->audit->log('deleted', $update);

        return response()->json(['message' => 'Update proyek berhasil dihapus.']);
    }
}
