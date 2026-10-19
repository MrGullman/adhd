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
        // Om NPF: the public knowledge library, written by admins. Not family data.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('slug', 80)->unique();
            $table->string('title', 120);
            $table->string('summary', 255)->nullable();
            $table->string('icon', 30)->nullable();
            $table->longText('body')->nullable();
            $table->text('why')->nullable();
            $table->text('helps')->nullable();
            $table->text('in_app')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index(['kind', 'published', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
