<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorization_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pattern');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('confidence', 4, 3)->default(1.000);
            $table->enum('source', ['manual', 'ai_trained'])->default('manual');
            $table->timestamps();

            $table->index(['team_id', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorization_rules');
    }
};
