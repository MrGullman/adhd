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
        // "Lagad": when the meal was cooked, and the pantry before, so that it can be undone.
        Schema::table('meal_plan_entries', function (Blueprint $table) {
            $table->timestamp('cooked_at')->nullable()->after('portions');
            $table->json('pantry_before')->nullable()->after('cooked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_plan_entries', function (Blueprint $table) {
            $table->dropColumn(['cooked_at', 'pantry_before']);
        });
    }
};
