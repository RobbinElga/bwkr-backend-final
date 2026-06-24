<?php

namespace App\Services;

use App\Exports\TableExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportExporter
{
    /**
     * @param array $payload [title, subtitle?, columns[], rows[], summary?]
     */
    public function respond(string $format, string $filenameBase, array $payload)
    {
        $payload['generatedAt'] = now()->translatedFormat('d F Y, H:i') . ' WIB';

        if ($format === 'pdf') {
            // tabel lebar -> landscape biar muat
            $orientation = count($payload['columns'] ?? []) > 5 ? 'landscape' : 'portrait';

            return Pdf::loadView('exports.table', $payload)
                ->setPaper('a4', $orientation)
                ->download($filenameBase . '.pdf');
        }

        // default: excel
        return Excel::download(
            new TableExport(
                $payload['title'] ?? 'Laporan',
                $payload['columns'] ?? [],
                $payload['rows'] ?? [],
                $payload['summary'] ?? [],
            ),
            $filenameBase . '.xlsx'
        );
    }
}
