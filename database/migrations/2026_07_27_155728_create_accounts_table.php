<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['checking', 'savings', 'credit_card', 'cash', 'investment']);
            $table->char('currency', 3)->default('USD');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->decimal('initial_balance', 15, 2)->default(0.00);
            $table->boolean('include_in_net_worth')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();
            $table->timestamps();

            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
