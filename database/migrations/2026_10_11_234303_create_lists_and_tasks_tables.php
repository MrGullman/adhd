<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            // When each part of the day starts, e.g. {"morning": "06:00"}. Null means the defaults.
            $table->json('day_parts')->nullable()->after('settings');
        });

        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name', 100);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'type']);
        });

        Schema::create('list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['list_id', 'completed_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            // Null means the whole family.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->cascadeOnDelete();
            // The day and the time on the wall clock in Europe/Stockholm, not instants.
            $table->date('date');
            $table->string('day_part', 20)->nullable();
            $table->time('time')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'date']);
        });

        // Every family has a shopping list, also the ones created before this migration.
        $now = now();

        DB::table('families')->orderBy('id')->each(function (object $family) use ($now): void {
            DB::table('lists')->insert([
                'family_id' => $family->id,
                'type' => 'shopping',
                'name' => 'Handlingslista',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('list_items');
        Schema::dropIfExists('lists');

        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('day_parts');
        });
    }
};
