<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations_input', function (Blueprint $table) {
            $table->date('donation_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('donations_input', function (Blueprint $table) {
            $table->dropColumn('donation_date');
        });
    }
};
