<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->date('due_date');
            $table->json('reminder_days_before');
            $table->boolean('is_recurring')->default(false);
            $table->string('frequency')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('subscription_slug')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'due_date']);
            $table->index(['team_id', 'is_paid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_reminders');
    }
};
