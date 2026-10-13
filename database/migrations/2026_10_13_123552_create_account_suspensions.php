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
        // An account an admin has turned off. The reason is only for admins.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->timestamp('suspended_at')->nullable()->after('last_login_at');
            $table->string('suspended_reason', 255)->nullable()->after('suspended_at');
        });

        // A whole family turned off: nobody in it logs in. Nothing is removed.
        Schema::table('families', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('day_parts');
            $table->string('suspended_reason', 255)->nullable()->after('suspended_at');
        });

        // What admins did, e.g. turned an account off and why.
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            // Kept when the subject is gone, so that the log still says who it was.
            $table->string('subject_name', 255);
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');

        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspended_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'suspended_at', 'suspended_reason']);
        });
    }
};
