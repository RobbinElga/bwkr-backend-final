<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gambar_disimpan_sebagai_webp(): void
    {
        Storage::fake('public');

        $path = app(ImageService::class)->store(
            UploadedFile::fake()->image('foto.jpg', 2000, 1500),
            'programs'
        );

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('programs/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_many_mengembalikan_banyak_path(): void
    {
        Storage::fake('public');

        $paths = app(ImageService::class)->storeMany([
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.png'),
        ], 'projects');

        $this->assertCount(2, $paths);
        foreach ($paths as $p) {
            Storage::disk('public')->assertExists($p);
        }
    }
}
