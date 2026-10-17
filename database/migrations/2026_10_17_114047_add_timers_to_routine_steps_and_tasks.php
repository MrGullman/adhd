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
        // A visual timer the child starts: a routine step uses its own minutes.
        Schema::table('routine_steps', function (Blueprint $table) {
            $table->boolean('timer')->default(false)->after('minutes');
        });

        // A task's timer, e.g. Äta for 20 minutes. Copied to each occurrence of a recurring task.
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('timer_minutes')->nullable()->after('time');
        });

        Schema::table('recurring_tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('timer_minutes')->nullable()->after('time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routine_steps', function (Blueprint $table) {
            $table->dropColumn('timer');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('timer_minutes');
        });

        Schema::table('recurring_tasks', function (Blueprint $table) {
            $table->dropColumn('timer_minutes');
        });
    }
};
