<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the Cashier/Paddle transactions table to make room for our financial transactions
        Schema::dropIfExists('transactions');
    }

    public function down(): void
    {
        // No-op; the Cashier migration will recreate this if needed
    }
};
