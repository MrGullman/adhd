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
        // The children who see a calendar ("Visa för"), instead of one choice for all children.
        Schema::create('calendar_child', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['calendar_id', 'user_id']);
        });

        // Keep what each child saw: their own calendar, or the ones shown to the children.
        foreach (DB::table('calendars')->get(['id', 'family_id', 'user_id', 'visible_to_children']) as $calendar) {
            $children = DB::table('family_user')
                ->where('family_id', $calendar->family_id)
                ->where('role', 'child')
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id);

            $seenBy = $children->contains((int) $calendar->user_id)
                ? collect([(int) $calendar->user_id])
                : ($calendar->visible_to_children ? $children : collect());

            DB::table('calendar_child')->insert($seenBy->map(fn (int $id) => ['calendar_id' => $calendar->id, 'user_id' => $id])->all());
        }

        Schema::table('calendars', function (Blueprint $table) {
            $table->dropColumn('visible_to_children');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->boolean('visible_to_children')->default(false)->after('url');
        });

        DB::table('calendars')
            ->whereIn('id', DB::table('calendar_child')->select('calendar_id'))
            ->update(['visible_to_children' => true]);

        Schema::dropIfExists('calendar_child');
    }
};
