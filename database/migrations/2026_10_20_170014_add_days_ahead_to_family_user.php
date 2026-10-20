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
        Schema::table('family_user', function (Blueprint $table) {
            // How many days ahead a child may look in Min dag: 0 or 4. Null follows the
            // child's view (Barn 0, Ungdom 4). The family's decision, like rewards_enabled.
            $table->unsignedTinyInteger('days_ahead')->nullable()->after('all_recipes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_user', function (Blueprint $table) {
            $table->dropColumn('days_ahead');
        });
    }
};
