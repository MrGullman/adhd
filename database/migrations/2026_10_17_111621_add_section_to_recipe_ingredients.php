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
        // A heading for a part of the recipe, e.g. "Kaka" and "Fyllning".
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->string('section', 50)->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
