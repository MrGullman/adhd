<?php

namespace Tests\Feature;

use App\Enums\ChildView;
use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Family;
use App\Models\Membership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Stepping a few days ahead in Min dag instead of a tab for Imorgon. A day
 * ahead is only to look at.
 */
class DaysAheadTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 13 October 2026, 08:00 in Sweden.
        $this->travelTo('2026-10-13 06:00:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    private function allowDaysAhead(int $days): void
    {
        Membership::query()->where('family_id', $this->family->id)->where('user_id', $this->child->id)->update(['days_ahead' => $days]);
    }

    public function test_adults_step_up_to_four_days_ahead()
    {
        Task::factory()->for($this->family)->create(['title' => 'Tvätta', 'date' => '2026-10-15']);
        Task::factory()->for($this->family)->create(['title' => 'Handla', 'date' => '2026-10-13']);
        Activity::factory()->for($this->family)->concerning($this->child)->create(['name' => 'Fotboll', 'date' => '2026-10-15']);

        $this->actingAs($this->parent)
            ->get(route('dashboard', ['dag' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('today/Ahead')
                ->where('date', '2026-10-15')
                ->where('activities.0.name', 'Fotboll')
                ->where('groups.4.tasks.0.title', 'Tvätta')
                ->has('groups.4.tasks', 1)
                ->missing('routines'));

        $this->actingAs($this->parent)
            ->get(route('dashboard', ['dag' => 9]))
            ->assertInertia(fn (Assert $page) => $page->where('date', '2026-10-17')->where('offset', 4));

        $this->get('/imorgon')->assertRedirect('/dashboard?dag=1');
    }

    public function test_a_child_in_läget_barn_only_sees_today_unless_a_parent_allows_more()
    {
        $this->actingAs($this->child)
            ->get(route('child.home', ['dag' => 1]))
            ->assertInertia(fn (Assert $page) => $page->component('child/Home')->where('daysAhead', 0));

        $this->allowDaysAhead(4);

        $this->actingAs($this->child)
            ->get(route('child.home', ['dag' => 1]))
            ->assertInertia(fn (Assert $page) => $page->component('child/Ahead')->where('date', '2026-10-14'));
    }

    public function test_a_child_in_läget_ungdom_looks_ahead_unless_a_parent_says_only_today()
    {
        $this->child->forceFill(['child_view' => ChildView::Youth])->save();

        $this->actingAs($this->child)
            ->get(route('child.home', ['dag' => 4]))
            ->assertInertia(fn (Assert $page) => $page->component('child/Ahead')->where('date', '2026-10-17'));

        $this->actingAs($this->parent)
            ->put(route('children.view.update', $this->child->id), ['child_view' => 'youth', 'child_pictures' => true, 'rewards_enabled' => true, 'all_recipes' => false, 'days_ahead' => 0])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->child)
            ->get(route('child.home', ['dag' => 1]))
            ->assertInertia(fn (Assert $page) => $page->component('child/YouthHome')->where('daysAhead', 0));

        $this->actingAs($this->parent)
            ->put(route('children.view.update', $this->child->id), ['child_view' => 'youth', 'child_pictures' => true, 'rewards_enabled' => true, 'all_recipes' => false, 'days_ahead' => 2])
            ->assertSessionHasErrors('days_ahead');
    }

    public function test_a_child_sees_only_their_own_things_on_a_day_ahead()
    {
        $this->allowDaysAhead(4);
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        Task::factory()->for($this->family)->create(['title' => 'Packa gympapåsen', 'date' => '2026-10-14', 'assigned_to' => $this->child->id]);
        Task::factory()->for($this->family)->create(['title' => 'Bos uppgift', 'date' => '2026-10-14', 'assigned_to' => $sibling->id]);
        Activity::factory()->for($this->family)->concerning($sibling)->create(['name' => 'Bos kalas', 'date' => '2026-10-14']);

        $this->actingAs($this->child)
            ->get(route('child.home', ['dag' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities', 0)
                ->has('groups.4.tasks', 1)
                ->where('groups.4.tasks.0.title', 'Packa gympapåsen'));
    }

    public function test_a_child_cannot_tick_off_a_task_on_a_day_ahead()
    {
        $this->allowDaysAhead(4);
        $tomorrow = Task::factory()->for($this->family)->create(['date' => '2026-10-14', 'assigned_to' => $this->child->id]);
        $homework = Task::factory()->for($this->family)->create(['date' => '2026-10-16', 'homework' => true, 'assigned_to' => $this->child->id]);
        $today = Task::factory()->for($this->family)->create(['date' => '2026-10-13', 'assigned_to' => $this->child->id]);

        $this->actingAs($this->child)->post(route('tasks.completion.store', $tomorrow))->assertForbidden();
        $this->assertNull($tomorrow->fresh()?->completed_at);

        // Läxor can be done before they are due, and today's tasks as usual.
        $this->actingAs($this->child)->post(route('tasks.completion.store', $homework))->assertRedirect();
        $this->actingAs($this->child)->post(route('tasks.completion.store', $today))->assertRedirect();
        $this->assertNotNull($homework->fresh()?->completed_at);
        $this->assertNotNull($today->fresh()?->completed_at);

        // Adults plan ahead and may still tick off.
        $this->actingAs($this->parent)->post(route('tasks.completion.store', $tomorrow))->assertRedirect();
        $this->assertNotNull($tomorrow->fresh()?->completed_at);
    }
}
