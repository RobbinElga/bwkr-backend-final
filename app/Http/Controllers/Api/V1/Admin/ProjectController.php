<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\AuditService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\DonationClaim;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request)
    {
        $projects = Project::with('program', 'bankAccounts')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->program_id, fn($q, $id) => $q->where('program_id', $id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ProjectResource::collection($projects);
    }

    /** GET /admin/projects/{slug}/stats — rata-rata donasi, jumlah donatur, pertumbuhan harian. */
    public function stats(Project $project)
    {
        $approved = fn() => DonationClaim::where('project_id', $project->id)->where('status', 'approved');

        $total = (int) $approved()->sum('amount');
        $count = $approved()->count();
        $donorCount = $approved()->distinct('donation_input_id')->count('donation_input_id');
        $average = $count > 0 ? intdiv($total, $count) : 0;

        // Pertumbuhan harian: 24 jam terakhir vs 24 jam sebelumnya (by approved_at)
        $now    = now();
        $last24 = (int) $approved()->where('approved_at', '>=', $now->copy()->subDay())->sum('amount');
        $prev24 = (int) $approved()
            ->whereBetween('approved_at', [$now->copy()->subDays(2), $now->copy()->subDay()])
            ->sum('amount');

        $growth = $prev24 > 0
            ? round((($last24 - $prev24) / $prev24) * 100, 1)
            : ($last24 > 0 ? 100.0 : 0.0);

        return response()->json([
            'average_donation'     => $average,
            'donor_count'          => $donorCount,
            'daily_growth_percent' => $growth,
        ]);
    }


    /** GET /admin/projects/{slug}/donors — donatur yang klaimnya approved untuk project ini. */
    public function donors(Project $project)
    {
        $donors = DonationClaim::with('donationInput:id,donor_name,ref_no')
            ->where('project_id', $project->id)
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get()
            ->map(fn(DonationClaim $c) => [
                'id'          => $c->id,
                'donor_name'  => $c->donationInput?->donor_name,
                'ref_no'      => $c->donationInput?->ref_no,
                'amount'      => (int) $c->amount,
                'approved_at' => $c->approved_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $donors]);
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);

        if ($request->hasFile('images')) {
            $data['images'] = $this->images->storeMany($request->file('images'), 'projects');
        }

        $project = Project::create($data);
        $project->bankAccounts()->sync($request->input('bank_account_ids', []));
        $this->audit->log('created', $project, new: $project->toArray());

        return (new ProjectResource($project->load('program')))->response()->setStatusCode(201);
    }

    public function show(Project $project)
    {
        return new ProjectResource($project->load('program', 'bankAccounts'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $old  = $project->toArray();
        $data = $request->validated();

        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $project->id);
        }

        if ($request->has('kept_images') || $request->hasFile('images')) {
            // URL gambar yang dipertahankan -> path
            $kept = collect($request->input('kept_images', []))
                ->map(function ($url) {
                    $pos = strpos((string) $url, '/storage/');
                    return $pos !== false ? substr($url, $pos + strlen('/storage/')) : $url;
                })
                ->all();

            // hapus file lama yang tidak dipertahankan
            foreach (array_diff($project->images ?? [], $kept) as $removed) {
                $this->images->delete($removed);
            }

            $newPaths = $kept;
            if ($request->hasFile('images')) {
                $newPaths = array_merge($newPaths, $this->images->storeMany($request->file('images'), 'projects'));
            }
            $data['images'] = array_values($newPaths);
        }

        $project->update($data);
        $project->bankAccounts()->sync($request->input('bank_account_ids', []));
        $this->audit->log('updated', $project, $old, $project->fresh()->toArray());

        return new ProjectResource($project->fresh()->load('program'));
    }

    public function destroy(Project $project)
    {
        $project->delete();
        $this->audit->log('deleted', $project);

        return response()->json(['message' => 'Proyek berhasil dihapus.']);
    }

    public function trashed()
    {
        return ProjectResource::collection(
            Project::onlyTrashed()->with('program')->orderByDesc('deleted_at')->get()
        );
    }

    public function restore(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);
        $project->restore();
        $this->audit->log('restored', $project);

        return response()->json(['message' => 'Proyek berhasil dipulihkan.']);
    }

    public function forceDelete(int $id)
    {
        $project = Project::onlyTrashed()->findOrFail($id);

        foreach ($project->images ?? [] as $path) {
            $this->images->delete($path);
        }
        $project->bankAccounts()->detach();        // bersihkan pivot
        $project->forceDelete();
        $this->audit->log('force_deleted', $project);

        return response()->json(['message' => 'Proyek dihapus permanen.']);
    }

    private function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text);
        $slug = $base;
        $i = 1;

        while (
            Project::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
