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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            // activity, away, pickup or day_off.
            $table->string('type', 20);
            $table->string('name', 100);
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            // Once: date (to end_date for several days). Every week: weekday,
            // between date and end_date when they are set. Days in Sweden.
            $table->date('date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->time('time')->nullable();
            // "Visa för barnen": children always see what they take part in.
            $table->boolean('visible_to_children')->default(true);
            $table->boolean('unusual')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'date']);
        });

        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['activity_id', 'user_id']);
        });

        Schema::create('activity_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // The child ticks off each thing the day before.
        Schema::create('activity_item_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The day of the activity, not the day of packing.
            $table->date('date');
            $table->timestamp('checked_at');

            $table->unique(['activity_item_id', 'user_id', 'date']);
        });

        // The child answers "Ja, jag har packat" on the day.
        Schema::create('activity_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('confirmed_at');

            $table->unique(['activity_id', 'user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_confirmations');
        Schema::dropIfExists('activity_item_checks');
        Schema::dropIfExists('activity_items');
        Schema::dropIfExists('activity_participants');
        Schema::dropIfExists('activities');
    }
};
