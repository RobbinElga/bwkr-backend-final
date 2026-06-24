<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_logs', function (Blueprint $table) {
            $table->id();
            $table->string('donor_phone_hash', 64)->index();
            $table->foreignId('contacted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default('whatsapp');
            $table->foreignId('template_id')->nullable()->constrained('broadcast_templates')->nullOnDelete();
            $table->text('message');
            $table->string('status')->default('sent');
            $table->timestamp('created_at')->nullable();   // log immutable
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_logs');
    }
};
