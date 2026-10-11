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
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('family_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['family_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('active_family_id')->nullable()->after('id')->constrained('families')->nullOnDelete();
            $table->string('avatar_color', 20)->default('teal')->after('name');

            // Children have no e-mail or password. They log in with a code on a trusted device.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            // The child's code is stored encrypted (not hashed) so that parents can show it.
            $table->text('pin')->nullable()->after('password');
            $table->unsignedTinyInteger('failed_pin_attempts')->default(0)->after('pin');
            $table->timestamp('login_disabled_at')->nullable()->after('failed_pin_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_family_id');
            $table->dropColumn(['avatar_color', 'pin', 'failed_pin_attempts', 'login_disabled_at']);
        });

        Schema::dropIfExists('family_user');
        Schema::dropIfExists('families');
    }
};
