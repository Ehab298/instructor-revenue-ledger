<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();

            $table->bigInteger('amount');

            $table->enum('status', ['pending', 'success', 'failed', 'timeout'])->default('pending');
            $table->string('provider_reference')->nullable()->comment('رقم العملية من بوابة الدفع الخارجية');

            $table->string('idempotency_key')->unique();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
