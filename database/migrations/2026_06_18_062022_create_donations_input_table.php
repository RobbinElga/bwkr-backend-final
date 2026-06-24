<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations_input', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no', 8)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // donatur login (opsional)
            $table->string('donor_name');
            $table->text('donor_phone');                          // DIENKRIPSI
            $table->string('donor_phone_hash', 64)->nullable()->index();  // pencarian
            $table->string('donor_email')->nullable();
            $table->unsignedBigInteger('amount');                 // total transfer (rupiah)
            $table->string('on_behalf')->nullable();
            $table->text('message')->nullable();
            $table->text('proof_file')->nullable();               // DIENKRIPSI (path privat)
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('input_by')->nullable()->constrained('users')->nullOnDelete();  // CS/admin (manual)
            $table->string('source')->default('online')->index();
            $table->string('status')->default('pending')->index();
            $table->string('payment_method')->nullable();         // future
            $table->string('payment_gateway_ref')->nullable();    // future
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations_input');
    }
};
