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
        // Reminders before an activity, in minutes, e.g. [120, 60]: "2 timmar kvar till Simskola".
        Schema::table('activities', function (Blueprint $table) {
            $table->json('reminders')->nullable()->after('time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('reminders');
        });
    }
};
