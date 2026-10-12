<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TodayTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 14 October 2026, 16:30 in Sweden: the afternoon.
        $this->travelTo('2026-10-14 14:30:00');

        $this->family = Family::factory()->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    private function task(array $attributes): Task
    {
        return Task::factory()->for($this->family)->create(['date' => '2026-10-14', ...$attributes]);
    }

    public function test_the_adults_see_the_whole_familys_day_with_each_childs_progress()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create(['name' => 'Vera']);
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);

        $this->task(['title' => 'Borsta tänderna', 'assigned_to' => $this->child->id, 'day_part' => 'morning', 'completed_at' => now()]);
        $this->task(['title' => 'Läxor', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon']);
        $this->task(['title' => 'Simskola', 'assigned_to' => $sibling->id, 'day_part' => 'evening', 'time' => '17:15']);
        $this->task(['title' => 'Duka bordet']);
        $this->task(['title' => 'Imorgon', 'date' => '2026-10-15']);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('today/Index')
                ->where('date', '2026-10-14')
                ->where('currentPart', 'afternoon')
                ->where('groups.0.tasks.0.title', 'Borsta tänderna')
                ->where('groups.2.tasks.0.title', 'Läxor')
                ->where('groups.3.tasks.0.title', 'Simskola')
                ->where('groups.4.tasks.0.title', 'Duka bordet')
                ->has('groups.4.tasks', 1)
                ->has('members', 3)
                ->where('members.0.name', 'Alva')
                ->where('members.0.total', 2)
                ->where('members.0.completed', 1)
                ->where('members.0.isChild', true)
                ->where('members.1.name', 'Bo')
                ->where('members.1.total', 1),
            );
    }

    public function test_a_child_sees_only_their_own_tasks_today()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();

        $this->task(['title' => 'Mina läxor', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon']);
        $this->task(['title' => 'Syskonets läxor', 'assigned_to' => $sibling->id, 'day_part' => 'afternoon']);
        $this->task(['title' => 'Duka bordet']);
        $this->task(['title' => 'Imorgon', 'assigned_to' => $this->child->id, 'date' => '2026-10-15']);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Home')
                ->where('child.name', 'Alva')
                ->where('progress', ['total' => 1, 'completed' => 0])
                ->where('groups.2.tasks.0.title', 'Mina läxor')
                ->has('groups.2.tasks', 1)
                ->has('groups.4.tasks', 0),
            );
    }

    public function test_now_is_the_next_task_from_the_current_part_of_the_day()
    {
        // Missed in the morning: not pushed again as "now".
        $this->task(['title' => 'Bädda', 'assigned_to' => $this->child->id, 'day_part' => 'morning']);
        $this->task(['title' => 'Mellanmål', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon', 'completed_at' => now()]);
        $this->task(['title' => 'Simskola', 'assigned_to' => $this->child->id, 'day_part' => 'evening', 'time' => '17:15']);
        $this->task(['title' => 'Läxor', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon', 'time' => '16:45']);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentPart', 'afternoon')
                ->where('now.title', 'Läxor'),
            );
    }

    public function test_the_part_of_the_day_follows_the_familys_own_times()
    {
        // The family's evening starts 16:00, so 16:30 is the evening.
        $this->family->forceFill(['day_parts' => ['morning' => '06:00', 'forenoon' => '09:00', 'afternoon' => '12:00', 'evening' => '16:00']])->save();
        $this->task(['title' => 'Läxor', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon']);
        $this->task(['title' => 'Kvällsmat', 'assigned_to' => $this->child->id, 'day_part' => 'evening']);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentPart', 'evening')
                ->where('now.title', 'Kvällsmat'),
            );
    }

    public function test_when_everything_is_done_there_is_nothing_now()
    {
        $this->task(['title' => 'Läxor', 'assigned_to' => $this->child->id, 'day_part' => 'afternoon', 'completed_at' => now()]);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('now', null)
                ->where('progress', ['total' => 1, 'completed' => 1]),
            );
    }
}
