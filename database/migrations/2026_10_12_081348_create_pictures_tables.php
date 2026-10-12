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
        Schema::create('picture_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pictures', function (Blueprint $table) {
            $table->id();
            // Null means the shared gallery, kept up in the admin portal.
            $table->foreignId('family_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('picture_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20);
            // On the private disk. The uploaded original until it is converted to WebP.
            $table->string('path');
            $table->timestamp('processed_at')->nullable();
            // Short text shown with the picture, also used as alt text.
            $table->string('label', 50);
            $table->string('keywords')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'label']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('avatar_color');
            $table->foreignId('picture_id')->nullable()->after('avatar_color')->constrained()->nullOnDelete();
        });

        foreach (['tasks', 'recurring_tasks', 'list_items'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('picture_id')->nullable()->after('family_id')->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['tasks', 'recurring_tasks', 'list_items', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('picture_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::dropIfExists('pictures');
        Schema::dropIfExists('picture_categories');
    }
};
