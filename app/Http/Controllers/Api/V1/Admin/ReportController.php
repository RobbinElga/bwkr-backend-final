<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\AuditService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request)
    {
        $reports = Report::query()
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->search, fn($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('year')->orderBy('order')->latest()
            ->paginate($request->integer('per_page', 15));

        return ReportResource::collection($reports);
    }

    public function store(StoreReportRequest $request)
    {
        $data = $request->safe()->except(['cover', 'file']);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);

        if ($request->hasFile('cover')) {
            $data['cover'] = $this->images->store($request->file('cover'), 'reports');
        }
        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('reports', 'public');
        }

        $report = Report::create($data);
        $this->audit->log('created', $report, new: $report->toArray());

        return (new ReportResource($report))->response()->setStatusCode(201);
    }

    public function show(Report $report)
    {
        return new ReportResource($report);
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $old  = $report->toArray();
        $data = $request->safe()->except(['cover', 'file']);

        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $report->id);
        }
        if ($request->hasFile('cover')) {
            $this->images->delete($report->cover);
            $data['cover'] = $this->images->store($request->file('cover'), 'reports');
        }
        if ($request->hasFile('file')) {
            if ($report->file_path) {
                Storage::disk('public')->delete($report->file_path);
            }
            $data['file_path'] = $request->file('file')->store('reports', 'public');
        }

        $report->update($data);
        $this->audit->log('updated', $report, $old, $report->fresh()->toArray());

        return new ReportResource($report->fresh());
    }

    public function destroy(Report $report)
    {
        $report->delete(); // soft delete
        $this->audit->log('deleted', $report);

        return response()->json(['message' => 'Laporan berhasil dihapus.']);
    }

    private function uniqueSlug(string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text);
        $slug = $base;
        $i = 1;

        while (
            Report::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
