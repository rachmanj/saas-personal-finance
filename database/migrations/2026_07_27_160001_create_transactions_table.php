<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->enum('type', ['income', 'expense', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->char('base_currency', 3)->nullable();
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->string('receipt_path')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->enum('source', ['manual', 'ocr', 'voice', 'import', 'recurring'])->default('manual');
            $table->softDeletes();
            $table->timestamps();

            $table->index('team_id');
            $table->index('account_id');
            $table->index('category_id');
            $table->index('to_account_id');
            $table->index('transaction_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
