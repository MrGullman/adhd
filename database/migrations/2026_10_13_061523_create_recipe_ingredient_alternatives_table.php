<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // "Smör eller margarin": the other groceries that will do for a line.
        Schema::create('recipe_ingredient_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();

            $table->unique(['recipe_ingredient_id', 'ingredient_id'], 'recipe_ingredient_alternative_unique');
        });

        // A common alternative that was missing from the basic list.
        if (! DB::table('ingredients')->whereNull('family_id')->where('name', 'Margarin')->exists()) {
            DB::table('ingredients')->insert([
                'family_id' => null,
                'name' => 'Margarin',
                'category' => 'dairy',
                'default_unit' => 'g',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredient_alternatives');
    }
};
