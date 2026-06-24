<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */ public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('broadcast_templates')->nullOnDelete();
            $table->string('tier')->nullable();                 // null = semua, 'reguler', 'premium'
            $table->string('status')->default('draft');         // draft|scheduled|processing|sent|failed
            $table->timestamp('scheduled_at')->nullable();      // null = kirim langsung
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipient_count')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);          // dipakai scheduler cari yang due
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
