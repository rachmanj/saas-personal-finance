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
        Schema::table('telegram_users', function (Blueprint $table) {
            // Drop the existing foreign key + unique constraint, make it nullable, then re-add
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            // Re-add unique without NOT NULL (nullable unique allows one NULL)
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            // Add language_code for /start handler
            if (!Schema::hasColumn('telegram_users', 'language_code')) {
                $table->string('language_code', 10)->nullable()->after('last_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First remove any NULL user_id rows
        DB::table('telegram_users')->whereNull('user_id')->delete();

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
