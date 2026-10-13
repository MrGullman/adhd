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
        // Every point given or used. A child's points are the sum.
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('points');
            $table->string('reason', 100);
            // What gave the points, e.g. a task, so that nothing counts twice.
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            // The parent who gave or took them; null when automatic.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'user_id']);
            $table->unique(['source_type', 'source_id', 'user_id']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('picture_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('cost');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // A child's wish for a reward, answered by a parent.
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('cost');
            $table->string('status', 20)->default('requested');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('point_transactions');
    }
};
