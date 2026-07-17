<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ExportDownloadController extends Controller
{
    /** Unduh file export via token sekali-pakai (native download). */
    public function show(string $token)
    {
        $meta = Cache::pull("export:{$token}");   // ambil + hapus (sekali pakai)
        abort_unless($meta, 404, 'Tautan unduh tidak valid atau kedaluwarsa.');

        $full = Storage::disk('local')->path($meta['path']);
        abort_unless(is_file($full), 404);

        return response()->download($full, $meta['name'])->deleteFileAfterSend(true);
    }
}
