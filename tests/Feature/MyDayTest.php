<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Enums\ListType;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Min dag for adults, and läxor: tasks shown every day until they are due.
 */
class MyDayTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 13 October 2026, 16:00 in Sweden.
        $this->travelTo('2026-10-13 14:00:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    public function test_homework_is_shown_every_day_until_it_is_due()
    {
        $this->actingAs($this->parent)->post(route('tasks.store'), [
            'title' => 'Glosor',
            'assigned_to' => $this->child->id,
            'date' => '2026-10-16',
            'homework' => true,
        ])->assertRedirect();

        $task = Task::query()->withoutGlobalScopes()->where('title', 'Glosor')->firstOrFail();
        $this->assertTrue($task->homework);

        // Tuesday, the day it was added, and Wednesday: shown, due Friday.
        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->where('now.title', 'Glosor')
            ->where('now.homework', true)
            ->where('now.dueDate', '2026-10-16'));
        $this->actingAs($this->parent)->get(route('tasks.index', ['dag' => '2026-10-14']))->assertInertia(fn (Assert $page) => $page
            ->where('groups.4.tasks.0.title', 'Glosor'));

        // Not on Monday, before it was added.
        $this->actingAs($this->parent)->get(route('tasks.index', ['dag' => '2026-10-12']))->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)->flatMap(fn ($group) => $group['tasks'])->isEmpty()));

        // Done on Wednesday: shown as done that day, gone on Thursday.
        $this->travelTo('2026-10-14 15:00:00');
        $this->actingAs($this->child)->post(route('tasks.completion.store', $task));
        $this->actingAs($this->parent)->get(route('tasks.index', ['dag' => '2026-10-14']))->assertInertia(fn (Assert $page) => $page
            ->where('groups.4.tasks.0.completed', true));
        $this->actingAs($this->parent)->get(route('tasks.index', ['dag' => '2026-10-15']))->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)->flatMap(fn ($group) => $group['tasks'])->isEmpty()));
    }

    public function test_min_dag_shows_what_is_next_for_me_and_what_the_home_needs()
    {
        Task::factory()->for($this->family)->create(['title' => 'Ringa tandläkaren', 'date' => '2026-10-13', 'assigned_to' => $this->parent->id]);
        Task::factory()->for($this->family)->create(['title' => 'Läxa till Alva', 'date' => '2026-10-13', 'assigned_to' => $this->child->id]);

        app(CurrentFamily::class)->set($this->family);
        $list = FamilyList::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->where('type', ListType::Shopping)->firstOrFail();
        $list->items()->create(['name' => 'Mjölk']);
        $list->items()->create(['name' => 'Bröd']);

        $recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g']])->create(['name' => 'Tacos']);
        MealPlanEntry::factory()->for($this->family)->create(['recipe_id' => $recipe->id, 'title' => null, 'date' => '2026-10-13', 'meal' => 'dinner']);

        $this->actingAs($this->parent)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('myDay.userId', $this->parent->id)
            ->where('myDay.now.title', 'Ringa tandläkaren')
            ->where('myDay.shopping', ['count' => 2, 'items' => ['Mjölk', 'Bröd']])
            ->where('myDay.dinnerMissing', ['Köttfärs']));
    }
}
