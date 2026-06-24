<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;

class PublicProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['program', 'bankAccounts' => fn($q) => $q->where('is_active', true)])
            ->whereIn('status', [ProjectStatus::Running, ProjectStatus::Completed])
            ->latest()->get();

        return ProjectResource::collection($projects);
    }

    public function show(Project $project)
    {
        abort_if($project->status === ProjectStatus::Draft, 404);

        return new ProjectResource($project->load([
            'program',
            'updates',
            'bankAccounts' => fn($q) => $q->where('is_active', true),
        ]));
    }
}
