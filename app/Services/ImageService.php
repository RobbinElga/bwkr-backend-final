<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use enshrined\svgSanitize\Sanitizer;

class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $driverClass = config('bwkr.image.driver') === 'imagick'
            ? ImagickDriver::class
            : GdDriver::class;

        // v4: pakai usingDriver() dengan class-string
        $this->manager = ImageManager::usingDriver($driverClass);
    }

    /** Simpan satu gambar: konversi ke WebP + kompres. Kembalikan path relatif. */
    public function store(UploadedFile $file, string $folder = 'images'): string
    {
        $maxWidth = (int) config('bwkr.image.max_width', 1600);
        $quality  = (int) config('bwkr.image.quality', 80);

        // v4: decodePath() menggantikan read()
        $image = $this->manager->decodePath($file->getRealPath());

        // perkecil HANYA jika lebih lebar dari batas (gambar kecil tidak diperbesar)
        $image->scaleDown(width: $maxWidth);

        // v4: encodeUsingFileExtension() menggantikan toWebp()
        $encoded = $image->encodeUsingFileExtension('webp', quality: $quality);

        $path = trim($folder, '/') . '/' . Str::ulid() . '.webp';
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }

    /** Simpan banyak gambar sekaligus; kembalikan array path. */
    public function storeMany(array $files, string $folder = 'images'): array
    {
        return collect($files)
            ->filter(fn($f) => $f instanceof UploadedFile)
            ->map(fn(UploadedFile $f) => $this->store($f, $folder))
            ->values()
            ->all();
    }

    /** Hapus file lama (aman walau file sudah tidak ada). */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }


    /**
     * Simpan gambar yang MUNGKIN berupa SVG.
     * - SVG  → disimpan apa adanya (tanpa konversi WebP) + disanitasi.
     * - Lainnya (jpg/png/webp) → lewat jalur lama (dikompres jadi WebP).
     */
    public function storeImageOrSvg(UploadedFile $file, string $folder = 'images'): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'svg') {
            $svg  = $this->sanitizeSvg((string) file_get_contents($file->getRealPath()));
            $path = trim($folder, '/') . '/' . Str::ulid() . '.svg';
            Storage::disk('public')->put($path, $svg);
            return $path;
        }

        return $this->store($file, $folder);
    }

    /** Sanitasi dasar SVG: buang <script>, atribut on*, dan href javascript:. */
    private function sanitizeSvg(string $svg): string
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);   // blokir <image href="http://..."> dsb.
        $clean = $sanitizer->sanitize($svg);

        return $clean !== false ? $clean : '';
    }
}
