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
        // Quiet hours belong to the person, in every family. Both null: none.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('reminders_enabled')->default(true)->after('avatar_color');
            $table->time('quiet_from')->nullable()->default('20:00:00')->after('reminders_enabled');
            $table->time('quiet_until')->nullable()->default('07:00:00')->after('quiet_from');
        });

        // Every reminder sent, so that none is sent twice.
        Schema::create('sent_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // E.g. "task:12" or "packing:4:2026-10-13".
            $table->string('key', 100);
            $table->timestamp('sent_at');

            $table->unique(['user_id', 'key']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_reminders');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reminders_enabled', 'quiet_from', 'quiet_until']);
        });
    }
};
