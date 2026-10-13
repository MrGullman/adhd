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
        // Community is shared between families and has no family_id. The
        // name shown is chosen for Community, never the account's name.
        Schema::table('users', function (Blueprint $table) {
            $table->string('community_name', 40)->nullable()->unique()->after('name');
        });

        Schema::create('community_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('community_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_group_id', 'user_id']);
        });

        // A thread goes with its author, replies included.
        Schema::create('community_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();

            $table->index(['community_group_id', 'last_posted_at']);
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });

        // Someone reported a post; an admin looks at it.
        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['community_post_id', 'user_id']);
        });

        $now = now();
        DB::table('community_groups')->insert(array_map(fn (array $group, int $order) => [
            ...$group,
            'sort_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            ['name' => 'ADHD', 'description' => 'Vardag, tips och frågor kring ADHD.'],
            ['name' => 'Autism', 'description' => 'Vardag, tips och frågor kring autism.'],
            ['name' => 'Skola och förskola', 'description' => 'Samarbete med skolan, anpassningar och stöd.'],
            ['name' => 'Morgon och kväll', 'description' => 'Rutiner, sömn och övergångar.'],
            ['name' => 'Mat och måltider', 'description' => 'Selektivt ätande, måltidsro och tips.'],
            ['name' => 'Syskon', 'description' => 'När syskon har olika behov.'],
            ['name' => 'Egen ork', 'description' => 'För oss vuxna: att orka och få stöd.'],
        ], range(1, 7)));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('community_threads');
        Schema::dropIfExists('community_group_members');
        Schema::dropIfExists('community_groups');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['community_name']);
            $table->dropColumn('community_name');
        });
    }
};
