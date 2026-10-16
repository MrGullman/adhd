<?php

namespace Tests\Feature;

use App\Enums\ChildView;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Membership;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Läget Barn or Ungdom per child, bildstöd and points per child, and the
 * tasks a child in läget Ungdom adds for themselves.
 */
class ChildViewTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-13 06:00:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function setView(User $as, User $child, array $overrides = []): TestResponse
    {
        return $this->actingAs($as)->put(route('children.view.update', $child->id), [
            'child_view' => 'youth',
            'child_pictures' => true,
            'rewards_enabled' => true,
            'all_recipes' => false,
            ...$overrides,
        ]);
    }

    private function youth(): void
    {
        $this->child->forceFill(['child_view' => ChildView::Youth])->save();
    }

    private function points(User $child): int
    {
        return (int) PointTransaction::query()->withoutGlobalScopes()->where('user_id', $child->id)->sum('points');
    }

    public function test_a_parent_switches_a_child_to_youth_and_the_childs_own_views_follow()
    {
        $routine = Routine::factory()->for($this->family)->withSteps([['Gå upp'], ['Klä på dig']])->forChild($this->child)->create();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->component('child/Home')
            ->where('childView', ['youth' => false, 'pictures' => true]));

        $this->setView($this->parent, $this->child, ['child_pictures' => false])->assertRedirect(route('children.show', $this->child->id));
        $this->child->refresh();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->component('child/YouthHome')
            ->where('childView', ['youth' => true, 'pictures' => false]));
        $this->actingAs($this->child)->get(route('child.routines.show', $routine))->assertInertia(fn (Assert $page) => $page->component('child/YouthRoutine'));
        $this->actingAs($this->child)->get(route('child.routines.show', [$routine, 'fokus' => 1]))->assertInertia(fn (Assert $page) => $page->component('child/Routine'));

        // Adults' views never change with the child's mode.
        $this->actingAs($this->parent)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('childView', null));
    }

    public function test_bildstod_is_always_shown_in_laget_barn()
    {
        $this->setView($this->parent, $this->child, ['child_view' => 'child', 'child_pictures' => false]);
        $this->child->refresh();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->component('child/Home')
            ->where('childView', ['youth' => false, 'pictures' => true]));
    }

    public function test_only_parents_change_a_childs_view()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->setView($editor, $this->child)->assertForbidden();
        // Children never reach the adults' pages.
        $this->setView($this->child, $this->child)->assertRedirect();
        $this->setView($this->parent, $this->child, ['child_view' => 'teen'])->assertSessionHasErrors('child_view');

        $this->assertSame(ChildView::Child, $this->child->fresh()?->child_view);
    }

    public function test_points_can_be_turned_off_for_one_child()
    {
        $sibling = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        $reward = Reward::factory()->for($this->family)->create(['cost' => 0]);
        $this->setView($this->parent, $this->child, ['child_view' => 'child', 'rewards_enabled' => false]);
        $this->child->refresh();

        $mine = Task::factory()->for($this->family)->create(['assigned_to' => $this->child->id]);
        $theirs = Task::factory()->for($this->family)->create(['assigned_to' => $sibling->id]);
        $this->actingAs($this->child)->post(route('tasks.completion.store', $mine));
        $this->actingAs($sibling)->post(route('tasks.completion.store', $theirs));

        $this->assertSame(0, $this->points($this->child));
        $this->assertSame(1, $this->points($sibling), 'the other child still gets points');
        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page->where('points', null));
        $this->actingAs($this->child)->get(route('child.rewards.index'))->assertRedirect(route('child.home'));
        $this->actingAs($this->child)->post(route('child.rewards.wish', $reward))->assertForbidden();
        $this->actingAs($sibling)->post(route('child.rewards.wish', $reward))->assertRedirect();
    }

    public function test_a_child_in_youth_adds_and_removes_their_own_tasks()
    {
        $this->youth();
        $sibling = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create();

        $this->actingAs($this->child)
            ->post(route('child.tasks.store'), ['title' => 'Plugga till provet', 'date' => '2026-10-14', 'time' => '16:00', 'assigned_to' => $sibling->id])
            ->assertRedirect(route('child.home'));

        $task = Task::query()->withoutGlobalScopes()->where('title', 'Plugga till provet')->firstOrFail();
        $this->assertSame($this->family->id, $task->family_id);
        $this->assertSame($this->child->id, $task->assigned_to, 'always for the child who adds it');
        $this->assertTrue($task->isOwnTask());

        // Adults see it in Idag, marked as the child's own, and can remove it.
        $this->actingAs($this->parent)->get(route('tasks.index', ['dag' => '2026-10-14']))->assertInertia(fn (Assert $page) => $page
            ->where('groups.2.tasks.0.title', 'Plugga till provet')
            ->where('groups.2.tasks.0.ownTask', true));

        // Ticking it off gives no points.
        $this->actingAs($this->child)->post(route('tasks.completion.store', $task));
        $this->assertSame(0, $this->points($this->child));

        $this->actingAs($this->child)->delete(route('child.tasks.destroy', $task))->assertRedirect();
        $this->assertNull(Task::query()->withoutGlobalScopes()->find($task->id));
    }

    public function test_a_childs_own_tasks_have_limits()
    {
        $parentsTask = Task::factory()->for($this->family)->create(['assigned_to' => $this->child->id, 'created_by' => $this->parent->id]);

        // Läget Barn: no own tasks.
        $this->actingAs($this->child)->get(route('child.tasks.create'))->assertForbidden();
        $this->actingAs($this->child)->post(route('child.tasks.store'), ['title' => 'Spela', 'date' => '2026-10-13'])->assertForbidden();

        $this->youth();
        $this->actingAs($this->child)->post(route('child.tasks.store'), ['title' => 'Spela', 'date' => '2026-10-12'])->assertSessionHasErrors('date');
        $this->actingAs($this->child)->post(route('child.tasks.store'), ['title' => 'Spela', 'date' => '2026-12-24'])->assertSessionHasErrors('date');

        // A task from an adult stays.
        $this->actingAs($this->child)->delete(route('child.tasks.destroy', $parentsTask))->assertForbidden();
        $this->assertNotNull($parentsTask->fresh());

        // Adults use the adults' pages.
        $this->actingAs($this->parent)->post(route('child.tasks.store'), ['title' => 'Spela', 'date' => '2026-10-13'])->assertForbidden();
    }

    public function test_another_family_never_reaches_a_childs_view_or_own_tasks()
    {
        $this->youth();
        $this->actingAs($this->child)->post(route('child.tasks.store'), ['title' => 'Plugga', 'date' => '2026-10-13']);
        $task = Task::query()->withoutGlobalScopes()->where('title', 'Plugga')->firstOrFail();

        $other = Family::factory()->create();
        $otherParent = User::factory()->inFamily($other)->create();
        $otherChild = User::factory()->child()->inFamily($other, FamilyRole::Child)->create(['child_view' => ChildView::Youth]);

        $this->setView($otherParent, $this->child, ['child_view' => 'child'])->assertNotFound();
        $this->actingAs($otherChild)->delete(route('child.tasks.destroy', $task))->assertNotFound();

        $this->assertSame(ChildView::Youth, $this->child->fresh()?->child_view);
        $this->assertNotNull($task->fresh());
        $this->assertTrue((bool) Membership::query()->where('user_id', $this->child->id)->value('rewards_enabled'));
    }
}
