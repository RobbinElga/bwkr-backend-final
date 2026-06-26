<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\DonationClaim;

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

    public function donors(Project $project)
    {
        $donors = DonationClaim::with('donationInput:id,donor_name,donor_alias')
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->orderByDesc('amount')
            ->limit(100)
            ->get()
            ->map(fn($c) => [
                'name'   => $c->donationInput?->donor_alias
                    ?: ($c->donationInput?->donor_name ?: 'Hamba Allah'),
                'amount' => (int) $c->amount,
                'date'   => $c->approved_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $donors]);
    }
}
