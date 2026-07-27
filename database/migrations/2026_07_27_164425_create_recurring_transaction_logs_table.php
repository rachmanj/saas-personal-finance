<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->boolean('was_skipped')->default(false);
            $table->string('skip_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transaction_logs');
    }
};
