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
        Schema::table('routines', function (Blueprint $table) {
            // all, even or odd ISO week numbers, e.g. the weeks at one parent's home.
            $table->string('weeks', 10)->default('all')->after('weekdays');
            // The period the routine is used, e.g. the summer holiday. Days in Sweden.
            $table->date('starts_on')->nullable()->after('weeks');
            $table->date('ends_on')->nullable()->after('starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn(['weeks', 'starts_on', 'ends_on']);
        });
    }
};
