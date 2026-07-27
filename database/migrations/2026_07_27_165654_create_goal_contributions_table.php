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
        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_goal_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('contributed_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('saving_goal_id');
            $table->index('contributed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
    }
};