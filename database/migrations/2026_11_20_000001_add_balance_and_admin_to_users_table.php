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
        Schema::table('users', function (Blueprint $table) {
            // Balance in USD, high precision (9 decimals) to prevent rounding drift
            $table->decimal('balance', 14, 9)->default('10.000000000')->after('email');
            $table->boolean('is_admin')->default(false)->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['balance', 'is_admin']);
        });
    }
};
