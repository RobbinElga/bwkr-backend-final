<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Backfill: ambil kategori yang sudah dipakai di tabel news
        $existing = DB::table('news')->whereNotNull('category')->distinct()->pluck('category');
        foreach ($existing as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            DB::table('news_categories')->updateOrInsert(
                ['name' => $name],
                ['slug' => Str::slug($name), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news_categories');
    }
};
