<?php

use App\Display\Layout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Widgets added to a full display used to land on top of another one.
     * They are moved to a free place, smaller if needed.
     */
    public function up(): void
    {
        $byDevice = DB::table('display_widgets')->orderBy('id')->get(['id', 'device_id', 'x', 'y', 'w', 'h'])->groupBy('device_id');

        foreach ($byDevice as $widgets) {
            $changes = Layout::arrange(array_values($widgets->map(fn (object $widget) => [
                'id' => (int) $widget->id,
                'x' => (int) $widget->x,
                'y' => (int) $widget->y,
                'w' => (int) $widget->w,
                'h' => (int) $widget->h,
            ])->all()));

            foreach ($changes as $id => $spot) {
                DB::table('display_widgets')->where('id', $id)->update($spot);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The old, overlapping places are not kept.
    }
};
