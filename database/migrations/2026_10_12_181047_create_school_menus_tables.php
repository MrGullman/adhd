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
        // A school's menu at a meal provider, e.g. Matilda.
        Schema::create('school_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('provider', 20);
            $table->string('external_id', 64);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'provider', 'external_id']);
        });

        Schema::create('school_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_menu_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal_name', 50);
            $table->string('holiday_name', 100)->nullable();
            // [{name, option}], e.g. option "Lunch 1".
            $table->json('courses');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['school_menu_id', 'date', 'meal_name']);
        });

        // Which school a member eats at. Siblings at the same school share a menu.
        Schema::table('family_user', function (Blueprint $table) {
            $table->foreignId('school_menu_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_menu_id');
        });

        Schema::dropIfExists('school_meals');
        Schema::dropIfExists('school_menus');
    }
};
