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
        // A child cooks the week's dinners. Parents can let an older child cook every recipe.
        Schema::table('family_user', function (Blueprint $table) {
            $table->boolean('all_recipes')->default(false)->after('rewards_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_user', function (Blueprint $table) {
            $table->dropColumn('all_recipes');
        });
    }
};
