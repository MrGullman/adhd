<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\RecurringTask;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * A visual timer the child starts: on a routine step (its own minutes) or a
 * task (e.g. Äta for 20 minutes), set by the adults.
 */
class VisualTimerTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 13 October 2026, 07:15 in Sweden.
        $this->travelTo('2026-10-13 05:15:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    public function test_a_routine_step_gets_a_timer_for_its_minutes()
    {
        $this->actingAs($this->parent)->post(route('routines.store'), [
            'name' => 'Morgon',
            'children' => [$this->child->id],
            'start_time' => '07:00',
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'ordered' => true,
            'steps' => [
                ['label' => 'Borsta tänderna', 'minutes' => 2, 'timer' => true, 'type' => 'step', 'optional' => false, 'weekdays' => []],
                ['label' => 'Klä på dig', 'minutes' => 5, 'timer' => false, 'type' => 'step', 'optional' => false, 'weekdays' => []],
            ],
        ])->assertRedirect(route('routines.index'));

        $routine = Routine::query()->withoutGlobalScopes()->where('name', 'Morgon')->firstOrFail();

        $this->actingAs($this->child)->get(route('child.routines.show', $routine))->assertInertia(fn (Assert $page) => $page
            ->where('routine.steps.0.timerMinutes', 2)
            ->where('routine.steps.1.timerMinutes', null));
    }

    public function test_a_step_timer_needs_minutes()
    {
        $this->actingAs($this->parent)->post(route('routines.store'), [
            'name' => 'Morgon',
            'children' => [$this->child->id],
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'steps' => [['label' => 'Borsta tänderna', 'minutes' => null, 'timer' => true, 'type' => 'step']],
        ])->assertSessionHasErrors('steps.0.minutes');
    }

    public function test_a_task_has_a_timer_and_the_child_sees_it()
    {
        $this->actingAs($this->parent)->post(route('tasks.store'), [
            'title' => 'Äta frukost',
            'assigned_to' => $this->child->id,
            'date' => '2026-10-13',
            'timer_minutes' => 20,
        ])->assertRedirect();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->where('now.title', 'Äta frukost')
            ->where('now.timerMinutes', 20));

        $this->actingAs($this->parent)->post(route('tasks.store'), ['title' => 'För länge', 'date' => '2026-10-13', 'timer_minutes' => 500])
            ->assertSessionHasErrors('timer_minutes');
    }

    public function test_every_occurrence_of_a_recurring_task_gets_its_timer()
    {
        $this->actingAs($this->parent)->post(route('recurring-tasks.store'), [
            'title' => 'Äta middag',
            'assigned_to' => $this->child->id,
            'frequency' => 'daily',
            'starts_on' => '2026-10-13',
            'time' => '17:30',
            'timer_minutes' => 25,
        ])->assertRedirect(route('recurring-tasks.index'));

        $this->assertSame(25, RecurringTask::query()->withoutGlobalScopes()->where('title', 'Äta middag')->value('timer_minutes'));
        $this->assertSame([25], Task::query()->withoutGlobalScopes()->where('title', 'Äta middag')->pluck('timer_minutes')->unique()->values()->all());
    }
}
