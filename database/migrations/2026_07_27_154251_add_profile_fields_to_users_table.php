<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('currency', 3)->default('IDR');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('locale', 5)->default('id');
            $table->string('profile_photo_path')->nullable();
            $table->foreignId('current_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_team_id']);
            $table->dropColumn([
                'currency',
                'timezone',
                'locale',
                'profile_photo_path',
                'current_team_id',
                'two_factor_secret',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
