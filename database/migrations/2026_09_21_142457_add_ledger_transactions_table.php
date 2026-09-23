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
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();

            $table->bigInteger('amount')->unsigned();

            $table->enum('type', ['earning', 'payout', 'refund']);
            $table->nullableMorphs('reference');
            $table->enum('status', ['New', 'Completed'])->default('New');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
