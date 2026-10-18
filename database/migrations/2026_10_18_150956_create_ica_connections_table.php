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
        // A family's connection to an ICA shopping list. Only ICA's tokens are
        // kept, encrypted; the ICA password is never stored.
        Schema::create('ica_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('client');
            $table->text('token');
            $table->timestamp('token_expires_at')->nullable();
            $table->json('lists')->nullable();
            $table->string('list_id', 64)->nullable();
            $table->string('list_title', 100)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
        });

        // The row in ICA's list that an item was sent as, to tick it off or remove it there too.
        Schema::table('list_items', function (Blueprint $table) {
            $table->string('ica_row_id', 64)->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->dropColumn('ica_row_id');
        });

        Schema::dropIfExists('ica_connections');
    }
};
