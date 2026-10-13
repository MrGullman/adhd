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
        // A calendar shared as an iCal link (Google, iPhone, Outlook).
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            // Whose calendar; null for the whole family's.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('provider', 20)->default('ics');
            // Encrypted: the link gives read access to the whole calendar.
            $table->text('url');
            // An adult's events reach the children only when chosen.
            $table->boolean('visible_to_children')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });

        // Events within the synced window, recurring events as one row each.
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained()->cascadeOnDelete();
            $table->string('uid');
            // UTC. For all-day events the dates themselves, ends_at exclusive.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('title');
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'starts_at']);
            $table->index(['calendar_id', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('calendars');
    }
};
