<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('tahunan')->index();
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('cover')->nullable();       // path WebP
            $table->string('file_path')->nullable();   // path PDF
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
