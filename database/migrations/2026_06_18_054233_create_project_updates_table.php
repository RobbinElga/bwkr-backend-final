<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();        // rich text (HTML)
            $table->json('images')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->index(['project_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
