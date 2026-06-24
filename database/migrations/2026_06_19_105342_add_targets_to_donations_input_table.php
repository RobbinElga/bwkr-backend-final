<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations_input', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('user_id')->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('program_id')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donations_input', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
