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
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('type', 10)->default('bank')->after('id'); // bank | qris
            $table->string('qris_image')->nullable()->after('logo');  // path WebP QR
            $table->text('account_number')->nullable()->change();      // QRIS tak punya no rek
            $table->string('account_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['type', 'qris_image']);
            // catatan: account_number/account_name dibiarkan nullable
        });
    }
};
