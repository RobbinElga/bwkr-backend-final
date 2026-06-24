<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;

class PublicReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::where('is_published', true)
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->orderByDesc('year')->orderBy('order')->latest()
            ->get();

        return ReportResource::collection($reports);
    }

    public function show(Report $report)
    {
        abort_unless($report->is_published, 404);

        return new ReportResource($report);
    }
}
