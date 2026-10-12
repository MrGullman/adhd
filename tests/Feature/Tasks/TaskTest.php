<?php

namespace Tests\Feature\Tasks;

use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Task;
use App\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function storedTask(string $title): Task
    {
        return Task::query()->withoutGlobalScopes()->where('title', $title)->firstOrFail();
    }

    public function test_a_task_with_a_time_gets_the_part_of_the_day_from_the_time()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->post(route('tasks.store'), [
                'title' => 'Simskola',
                'date' => '2026-10-14',
                // A chosen part of the day gives way to the time.
                'day_part' => 'morning',
                'time' => '17:15',
            ])
            ->assertRedirect(route('tasks.index', ['dag' => '2026-10-14']));

        $task = $this->storedTask('Simskola');
        $this->assertSame($family->id, $task->family_id);
        $this->assertSame(DayPart::Evening, $task->day_part);
        $this->assertSame('17:15', $task->shortTime());
        $this->assertSame($editor->id, $task->created_by);
    }

    public function test_a_task_can_have_a_part_of_the_day_without_a_time()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)->post(route('tasks.store'), [
            'title' => 'Vattna blommorna',
            'date' => '2026-10-14',
            'day_part' => 'forenoon',
        ]);

        $task = $this->storedTask('Vattna blommorna');
        $this->assertSame(DayPart::Forenoon, $task->day_part);
        $this->assertNull($task->time);
    }

    public function test_a_task_is_given_to_a_family_member_but_not_to_someone_outside_the_family()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $stranger = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($parent)->post(route('tasks.store'), [
            'title' => 'Duka bordet',
            'date' => '2026-10-14',
            'assigned_to' => $child->id,
        ]);
        $this->assertSame($child->id, $this->storedTask('Duka bordet')->assigned_to);

        $this->actingAs($parent)
            ->post(route('tasks.store'), [
                'title' => 'Mata katten',
                'date' => '2026-10-14',
                'assigned_to' => $stranger->id,
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_a_viewer_cannot_create_tasks()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();

        $this->actingAs($viewer)->get(route('tasks.create'))->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('tasks.store'), ['title' => 'Städa', 'date' => '2026-10-14'])
            ->assertForbidden();
    }

    public function test_the_day_is_grouped_by_part_of_the_day_and_sorted_by_time()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $date = '2026-10-14';

        Task::factory()->for($family)->create(['title' => 'Middag', 'date' => $date, 'day_part' => 'evening', 'time' => '18:00']);
        Task::factory()->for($family)->create(['title' => 'Läxor', 'date' => $date, 'day_part' => 'evening']);
        Task::factory()->for($family)->create(['title' => 'Simskola', 'date' => $date, 'day_part' => 'evening', 'time' => '17:15']);
        Task::factory()->for($family)->create(['title' => 'Borsta tänderna', 'date' => $date, 'day_part' => 'morning']);
        Task::factory()->for($family)->create(['title' => 'Ring mormor', 'date' => $date]);
        Task::factory()->for($family)->create(['title' => 'Imorgon', 'date' => '2026-10-15']);

        $this->actingAs($viewer)
            ->get(route('tasks.index', ['dag' => $date]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/Index')
                ->where('date', $date)
                ->where('previousDate', '2026-10-13')
                ->where('nextDate', '2026-10-15')
                ->where('groups.0.label', 'Morgon')
                ->where('groups.0.tasks.0.title', 'Borsta tänderna')
                ->has('groups.1.tasks', 0)
                ->where('groups.3.label', 'Kväll')
                ->where('groups.3.tasks.0.title', 'Simskola')
                ->where('groups.3.tasks.1.title', 'Middag')
                ->where('groups.3.tasks.2.title', 'Läxor')
                ->where('groups.4.label', 'Under dagen')
                ->has('groups.4.tasks', 1)
                ->where('canCreate', false),
            );
    }

    public function test_today_is_the_day_in_sweden()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        // 23:30 UTC is already the next day in Sweden.
        $this->travelTo('2026-10-12 23:30:00');

        $this->actingAs($parent)
            ->get(route('tasks.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('date', '2026-10-13')
                ->where('today', '2026-10-13'),
            );

        $this->actingAs($parent)
            ->get(route('tasks.index', ['dag' => '2026-02-30']))
            ->assertInertia(fn (Assert $page) => $page->where('date', '2026-10-13'));
    }

    public function test_a_child_ticks_off_their_own_task_but_not_someone_elses()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $sibling = User::factory()->child('7351')->inFamily($family, FamilyRole::Child)->create();
        $own = Task::factory()->for($family)->create(['assigned_to' => $child->id]);
        $siblings = Task::factory()->for($family)->create(['assigned_to' => $sibling->id]);

        $this->actingAs($child)->post(route('tasks.completion.store', $own));
        $this->assertSame($child->id, $own->fresh()?->completed_by);

        $this->actingAs($child)
            ->post(route('tasks.completion.store', $siblings))
            ->assertForbidden();
        $this->assertNull($siblings->fresh()?->completed_at);

        $this->actingAs($child)->delete(route('tasks.completion.destroy', $own));
        $this->assertNull($own->fresh()?->completed_at);
    }

    public function test_an_editor_changes_and_removes_a_task()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();
        $task = Task::factory()->for($family)->create([
            'title' => 'Simskola',
            'date' => '2026-10-14',
            'day_part' => 'evening',
            'time' => '17:15',
        ]);

        $this->actingAs($editor)->get(route('tasks.edit', $task))->assertOk();

        $this->actingAs($editor)
            ->put(route('tasks.update', $task), [
                'title' => 'Simskola',
                'date' => '2026-10-15',
                'day_part' => 'afternoon',
                'time' => '',
            ])
            ->assertRedirect(route('tasks.index', ['dag' => '2026-10-15']));

        $task->refresh();
        $this->assertSame('2026-10-15', $task->date->toDateString());
        $this->assertSame(DayPart::Afternoon, $task->day_part);
        $this->assertNull($task->time);

        $this->actingAs($editor)->delete(route('tasks.destroy', $task));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_a_viewer_cannot_change_tick_off_or_remove_tasks()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $task = Task::factory()->for($family)->create(['date' => LocalTime::today()]);

        $this->actingAs($viewer)->put(route('tasks.update', $task), ['title' => 'Ny', 'date' => '2026-10-14'])->assertForbidden();
        $this->actingAs($viewer)->post(route('tasks.completion.store', $task))->assertForbidden();
        $this->actingAs($viewer)->delete(route('tasks.destroy', $task))->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'completed_at' => null]);
    }
}
