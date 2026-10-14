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
        // support (bildstöd) or food (recipe photos).
        Schema::table('pictures', function (Blueprint $table) {
            $table->string('purpose', 10)->default('support')->after('source');
        });

        // A family's own photo that only a recipe uses is a food photo.
        $usedElsewhere = collect([
            ['tasks', 'picture_id'], ['recurring_tasks', 'picture_id'], ['list_items', 'picture_id'],
            ['routines', 'picture_id'], ['routine_steps', 'picture_id'], ['activities', 'picture_id'],
            ['activity_items', 'picture_id'], ['rewards', 'picture_id'], ['users', 'picture_id'], ['ingredients', 'picture_id'],
        ])->flatMap(fn (array $use) => DB::table($use[0])->whereNotNull($use[1])->pluck($use[1]))->unique()->all();

        DB::table('pictures')
            ->whereNotNull('family_id')
            ->whereIn('id', DB::table('recipes')->whereNotNull('picture_id')->select('picture_id'))
            ->whereNotIn('id', $usedElsewhere)
            ->update(['purpose' => 'food']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pictures', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
