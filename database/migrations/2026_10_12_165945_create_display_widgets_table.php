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
        // What a home display shows and where, in a 12-column grid.
        Schema::create('display_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->unsignedTinyInteger('x')->default(0);
            $table->unsignedTinyInteger('y')->default(0);
            $table->unsignedTinyInteger('w')->default(4);
            $table->unsignedTinyInteger('h')->default(2);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_widgets');
    }
};
