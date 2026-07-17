<?php

namespace App\Services;

use App\Exports\TableExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExporter
{
    /**
     * Bangun file lalu simpan sementara. Kembalikan token unduh sekali-pakai.
     * Frontend memakai token ini untuk mengunduh secara native.
     */
    public function respond(string $format, string $filenameBase, array $payload)
    {
        $payload['generatedAt'] = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $token = Str::random(40);

        if ($format === 'pdf') {
            $orientation = count($payload['columns'] ?? []) > 5 ? 'landscape' : 'portrait';
            $bytes = Pdf::loadView('exports.table', $payload)->setPaper('a4', $orientation)->output();
            $ext = 'pdf';
        } else {
            $bytes = Excel::raw(new TableExport(
                $payload['title'] ?? 'Laporan',
                $payload['columns'] ?? [],
                $payload['rows'] ?? [],
                $payload['summary'] ?? [],
            ), ExcelFormat::XLSX);
            $ext = 'xlsx';
        }

        $path = "exports/{$token}.{$ext}";
        Storage::disk('local')->put($path, $bytes);

        Cache::put("export:{$token}", [
            'path' => $path,
            'name' => $filenameBase . '.' . $ext,
        ], now()->addMinutes(10));

        return response()->json(['token' => $token]);
    }
}
