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
        // The child's view (child or youth) and bildstöd in youth: they follow the child between families.
        Schema::table('users', function (Blueprint $table) {
            $table->string('child_view', 10)->default('child')->after('picture_id');
            $table->boolean('child_pictures')->default(true)->after('child_view');
        });

        // Points and rewards for a child are the family's decision.
        Schema::table('family_user', function (Blueprint $table) {
            $table->boolean('rewards_enabled')->default(true)->after('school_menu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['child_view', 'child_pictures']);
        });

        Schema::table('family_user', function (Blueprint $table) {
            $table->dropColumn('rewards_enabled');
        });
    }
};
