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
        // Läxa: the date is when it is due, and it is shown every day until then.
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('homework')->default(false)->after('timer_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('homework');
        });
    }
};
