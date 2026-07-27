<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->enum('period', ['monthly', 'yearly', 'custom']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('rollover')->default(false);
            $table->unsignedTinyInteger('notification_threshold')->default(80);
            $table->timestamps();

            $table->unique(['team_id', 'category_id', 'start_date']);
            $table->index(['team_id', 'category_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
