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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->cascadeOnDelete();
            $table->string('direction'); // 'inbound' or 'outbound'
            $table->string('message_type'); // 'text', 'photo', 'voice', 'command', 'reply'
            $table->text('content')->nullable();
            $table->string('file_id')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // 'pending', 'processed', 'failed'
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
