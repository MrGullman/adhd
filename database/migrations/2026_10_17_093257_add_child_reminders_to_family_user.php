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
        // An adult gets the children's activity reminders too: all children (ids null) or some.
        Schema::table('family_user', function (Blueprint $table) {
            $table->boolean('child_reminders')->default(false)->after('all_recipes');
            $table->json('child_reminder_ids')->nullable()->after('child_reminders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_user', function (Blueprint $table) {
            $table->dropColumn(['child_reminders', 'child_reminder_ids']);
        });
    }
};
