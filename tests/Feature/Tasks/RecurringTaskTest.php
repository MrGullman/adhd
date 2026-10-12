<?php

namespace Tests\Feature\Tasks;

use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecurringTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 12 October 2026, morning in Sweden.
        $this->travelTo('2026-10-12 06:00:00');
    }

    /**
     * The days that have a task for the recurring task, also skipped ones.
     *
     * @return list<string>
     */
    private function days(RecurringTask $recurring, bool $withSkipped = false): array
    {
        return Task::query()->withoutGlobalScopes()
            ->where('recurring_task_id', $recurring->id)
            ->when(! $withSkipped, fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => substr((string) $date, 0, 10))
            ->all();
    }

    public function test_a_daily_task_is_created_a_week_ahead_and_never_twice()
    {
        $recurring = RecurringTask::factory()->create(['starts_on' => '2026-10-12']);

        $this->artisan('tasks:generate-recurring')->assertSuccessful();
        $this->artisan('tasks:generate-recurring')->assertSuccessful();

        $this->assertSame([
            '2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15',
            '2026-10-16', '2026-10-17', '2026-10-18',
        ], $this->days($recurring));

        // The next night one more day is added.
        $this->travelTo('2026-10-13 00:05:00');
        $this->artisan('tasks:generate-recurring');

        $this->assertContains('2026-10-19', $this->days($recurring));
        $this->assertCount(8, $this->days($recurring));
    }

    public function test_a_weekly_task_comes_on_its_weekdays_only()
    {
        $recurring = RecurringTask::factory()->weekly([2, 4])->create(['starts_on' => '2026-10-12']);

        $this->artisan('tasks:generate-recurring');

        // Tuesday and Thursday.
        $this->assertSame(['2026-10-13', '2026-10-15'], $this->days($recurring));
    }

    public function test_every_other_week_counts_from_the_week_it_starts()
    {
        // Starts on Wednesday 14 October: that week counts, the next does not.
        $recurring = RecurringTask::factory()->weekly([1, 5], 2)->create(['starts_on' => '2026-10-14']);

        foreach (['2026-10-12', '2026-10-19', '2026-10-26'] as $monday) {
            $this->travelTo("{$monday} 06:00:00");
            $this->artisan('tasks:generate-recurring');
        }

        // Friday the first week, nothing the second, Monday and Friday the third.
        $this->assertSame(['2026-10-16', '2026-10-26', '2026-10-30'], $this->days($recurring));
    }

    public function test_a_task_comes_only_between_its_first_and_last_day()
    {
        $recurring = RecurringTask::factory()->create(['starts_on' => '2026-10-14', 'ends_on' => '2026-10-16']);

        $this->artisan('tasks:generate-recurring');

        $this->assertSame(['2026-10-14', '2026-10-15', '2026-10-16'], $this->days($recurring));
    }

    public function test_the_time_stays_the_same_when_summer_time_starts_and_ends()
    {
        $recurring = RecurringTask::factory()->create(['starts_on' => '2026-10-12', 'time' => '07:30:00']);

        // 00:05 in Sweden on the day winter time starts (25 October 2026) and
        // on the day summer time starts (28 March 2027).
        foreach (['2026-10-24 22:05:00' => '2026-10-25', '2027-03-27 23:05:00' => '2027-03-28'] as $utc => $swedishDay) {
            $this->travelTo($utc);
            $this->artisan('tasks:generate-recurring');

            $task = Task::query()->withoutGlobalScopes()
                ->where('recurring_task_id', $recurring->id)
                ->whereDate('date', $swedishDay)
                ->firstOrFail();

            $this->assertSame('07:30', $task->shortTime());
            $this->assertSame(DayPart::Morning, $task->day_part);
        }
    }

    public function test_the_night_run_creates_each_familys_tasks_in_that_family()
    {
        $ours = RecurringTask::factory()->create(['title' => 'Vår']);
        $theirs = RecurringTask::factory()->create(['title' => 'Deras']);

        $this->artisan('tasks:generate-recurring');

        $this->assertSame(7, DB::table('tasks')->where('recurring_task_id', $ours->id)->where('family_id', $ours->family_id)->count());
        $this->assertSame(7, DB::table('tasks')->where('recurring_task_id', $theirs->id)->where('family_id', $theirs->family_id)->count());
    }

    public function test_the_night_run_is_scheduled_after_midnight_in_sweden()
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('tasks:generate-recurring')
            ->assertSuccessful();
    }

    public function test_an_editor_adds_a_recurring_task_and_the_week_appears_at_once()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($editor)
            ->post(route('recurring-tasks.store'), [
                'title' => 'Simskola',
                'assigned_to' => $child->id,
                'frequency' => 'weekly',
                'weekdays' => ['2'],
                'week_interval' => '1',
                'starts_on' => '2026-10-12',
                'time' => '17:15',
            ])
            ->assertRedirect(route('recurring-tasks.index'));

        $recurring = RecurringTask::query()->withoutGlobalScopes()->where('title', 'Simskola')->firstOrFail();
        $this->assertSame([2], $recurring->weekdays);
        $this->assertSame(DayPart::Evening, $recurring->day_part);

        $task = Task::query()->withoutGlobalScopes()->where('recurring_task_id', $recurring->id)->firstOrFail();
        $this->assertSame('2026-10-13', $task->date->toDateString());
        $this->assertSame($child->id, $task->assigned_to);
        $this->assertSame(DayPart::Evening, $task->day_part);
    }

    public function test_weekly_tasks_need_a_weekday()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('recurring-tasks.store'), [
                'title' => 'Simskola',
                'frequency' => 'weekly',
                'week_interval' => '1',
                'starts_on' => '2026-10-12',
            ])
            ->assertSessionHasErrors(['weekdays' => 'Välj minst en veckodag.']);
    }

    public function test_a_viewer_cannot_add_recurring_tasks()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();

        $this->actingAs($viewer)
            ->post(route('recurring-tasks.store'), [
                'title' => 'Städa',
                'frequency' => 'daily',
                'starts_on' => '2026-10-12',
            ])
            ->assertForbidden();
    }

    public function test_a_skipped_day_is_not_created_again()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $recurring = RecurringTask::factory()->for($family)->create();
        $this->artisan('tasks:generate-recurring');

        $wednesday = Task::query()->withoutGlobalScopes()
            ->where('recurring_task_id', $recurring->id)
            ->whereDate('date', '2026-10-14')
            ->firstOrFail();

        $this->actingAs($parent)->delete(route('tasks.destroy', $wednesday));
        $this->artisan('tasks:generate-recurring');

        $this->assertNotContains('2026-10-14', $this->days($recurring));
        $this->assertContains('2026-10-14', $this->days($recurring, withSkipped: true));
    }

    public function test_changing_a_recurring_task_changes_coming_days_only()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $recurring = RecurringTask::factory()->for($family)->create(['time' => '07:00:00', 'day_part' => 'morning']);
        $this->artisan('tasks:generate-recurring');

        $occurrence = fn (string $date) => Task::query()->withoutGlobalScopes()
            ->where('recurring_task_id', $recurring->id)
            ->whereDate('date', $date)
            ->firstOrFail();

        // Ticked off ahead, and changed by hand for one day.
        $occurrence('2026-10-14')->forceFill(['completed_at' => now()])->save();
        $this->actingAs($parent)->put(route('tasks.update', $occurrence('2026-10-15')), [
            'title' => 'Borsta tänderna hos mormor',
            'date' => '2026-10-15',
            'time' => '08:00',
        ]);

        $this->actingAs($parent)
            ->put(route('recurring-tasks.update', $recurring), [
                'title' => 'Borsta tänderna',
                'frequency' => 'daily',
                'starts_on' => '2026-10-12',
                'time' => '07:15',
            ])
            ->assertRedirect(route('recurring-tasks.index'));

        $this->assertSame('07:00', $occurrence('2026-10-12')->shortTime(), 'today stays');
        $this->assertSame('07:00', $occurrence('2026-10-14')->shortTime(), 'ticked off stays');
        $this->assertSame('08:00', $occurrence('2026-10-15')->shortTime(), 'changed by hand stays');
        $this->assertSame('07:15', $occurrence('2026-10-16')->shortTime());
        $this->assertSame('07:15', $occurrence('2026-10-18')->shortTime());
    }

    public function test_removing_a_recurring_task_keeps_today_and_what_is_done()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $recurring = RecurringTask::factory()->for($family)->create();
        $this->artisan('tasks:generate-recurring');

        $this->actingAs($parent)
            ->delete(route('recurring-tasks.destroy', $recurring))
            ->assertRedirect(route('recurring-tasks.index'));

        $this->assertDatabaseMissing('recurring_tasks', ['id' => $recurring->id]);
        $this->assertSame(
            ['2026-10-12'],
            Task::query()->withoutGlobalScopes()->where('family_id', $family->id)->pluck('date')->map(fn ($date) => substr((string) $date, 0, 10))->all(),
        );
    }
}
