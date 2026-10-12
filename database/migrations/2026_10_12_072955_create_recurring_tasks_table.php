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
        Schema::create('recurring_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            // Null means the whole family.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('day_part', 20)->nullable();
            $table->time('time')->nullable();
            $table->string('frequency', 20);
            // ISO weekdays, 1 = Monday ... 7 = Sunday. Only for weekly tasks.
            $table->json('weekdays')->nullable();
            // 1 = every week, 2 = every other week.
            $table->unsignedTinyInteger('week_interval')->default(1);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('recurring_task_id')->nullable()->after('family_id')->constrained()->nullOnDelete();
            // An occurrence changed by hand. Changes to the recurring task leave it alone.
            $table->boolean('customized')->default(false)->after('time');
            // A deleted occurrence stays as a skipped day, so it is not created again.
            $table->softDeletes();

            $table->unique(['recurring_task_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['recurring_task_id', 'date']);
            $table->dropConstrainedForeignId('recurring_task_id');
            $table->dropColumn(['customized', 'deleted_at']);
        });

        Schema::dropIfExists('recurring_tasks');
    }
};
