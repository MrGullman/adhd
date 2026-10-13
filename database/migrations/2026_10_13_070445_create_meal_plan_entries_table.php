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
        // The family's own week of meals. Never mixed up with school meals.
        Schema::create('meal_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal', 20)->default('dinner');
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            // Without a recipe, e.g. "Pizza ute".
            $table->string('title', 100)->nullable();
            $table->unsignedTinyInteger('portions')->default(4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'date']);
        });

        Schema::table('list_items', function (Blueprint $table) {
            $table->foreignId('ingredient_id')->nullable()->after('picture_id')->constrained()->nullOnDelete();
            $table->decimal('quantity', 10, 2)->nullable()->after('name');
            $table->string('unit', 10)->nullable()->after('quantity');
            // Made from the meal plan for the week starting this Monday. The
            // family's own items have none and are never changed by it.
            $table->date('meal_plan_week')->nullable()->after('unit');
            // Which meals it is for, e.g. "Tacos, Lasagne".
            $table->string('for_meals', 150)->nullable()->after('meal_plan_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingredient_id');
            $table->dropColumn(['quantity', 'unit', 'meal_plan_week', 'for_meals']);
        });

        Schema::dropIfExists('meal_plan_entries');
    }
};
