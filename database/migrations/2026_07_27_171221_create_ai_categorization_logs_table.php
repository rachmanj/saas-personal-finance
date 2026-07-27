<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_categorization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predicted_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('confidence', 4, 3);
            $table->foreignId('actual_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('was_correct')->nullable();
            $table->string('model_version');
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_categorization_logs');
    }
};
