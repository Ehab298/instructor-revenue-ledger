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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->enum('plan', ['monthly', 'quarterly', 'annual']);
            $table->bigInteger('amount_paid')->unsigned();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            // "Active" is derived from ends_at > now; the service serializes
            // concurrent subscriptions per student with a row lock.
            $table->index(['student_id', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
