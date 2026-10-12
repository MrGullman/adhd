<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\UpdateDayPartsRequest;
use App\Models\Task;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * When the parts of the day start. Coming tasks with a time move to the
 * part of the day their time now belongs to.
 */
class DayPartController extends Controller
{
    public function update(UpdateDayPartsRequest $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();

        DB::transaction(function () use ($request, $family): void {
            $family->forceFill(['day_parts' => $request->validated()])->save();

            Task::query()
                ->whereNotNull('time')
                ->whereDate('date', '>=', LocalTime::today())
                ->each(function (Task $task) use ($family): void {
                    $task->update(['day_part' => $family->dayPartFor((string) $task->time)]);
                });
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dagens delar är sparade.']);

        return to_route('family.show');
    }
}
