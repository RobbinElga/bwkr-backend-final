<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

class ProofFileService
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic'];

    /** Simpan file privat. Gambar -> WebP; PDF/lainnya -> apa adanya. */
    public function store(UploadedFile $file, string $folder = 'proofs'): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, self::IMAGE_EXT, true)) {
            return $this->storeAsWebp($file, $folder);
        }

        $path = trim($folder, '/') . '/' . Str::ulid() . '.' . $ext;
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    private function storeAsWebp(UploadedFile $file, string $folder): string
    {
        $driverClass = config('bwkr.image.driver') === 'imagick' ? ImagickDriver::class : GdDriver::class;
        $manager = ImageManager::usingDriver($driverClass);

        $image = $manager->decodePath($file->getRealPath());
        $image->scaleDown(width: (int) config('bwkr.image.max_width', 1600));
        $encoded = $image->encodeUsingFileExtension('webp', quality: (int) config('bwkr.image.quality', 80));

        $path = trim($folder, '/') . '/' . Str::ulid() . '.webp';
        Storage::disk('local')->put($path, (string) $encoded);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
