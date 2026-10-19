<?php

namespace Tests\Feature;

use App\Display\DisplayContext;
use App\Display\Widgets\PointsWidget;
use App\Enums\FamilyRole;
use App\Enums\RedemptionStatus;
use App\Models\Activity;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RewardTest extends TestCase
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

    private function points(User $child): int
    {
        return (int) PointTransaction::query()->withoutGlobalScopes()->where('user_id', $child->id)->where('pending', false)->sum('points');
    }

    public function test_ticking_a_task_gives_points_once_and_unticking_takes_them_back()
    {
        $task = Task::factory()->for($this->family)->create(['title' => 'Duka bordet', 'assigned_to' => $this->child->id]);

        $this->actingAs($this->child)->post(route('tasks.completion.store', $task));
        $this->actingAs($this->child)->post(route('tasks.completion.store', $task));
        $this->assertSame(1, $this->points($this->child));

        $this->actingAs($this->child)->delete(route('tasks.completion.destroy', $task));
        $this->assertSame(0, $this->points($this->child));

        // An adult ticking off the child's task gives the child the points.
        $this->actingAs($this->parent)->post(route('tasks.completion.store', $task));
        $this->assertSame(1, $this->points($this->child));
        $this->assertSame(0, $this->points($this->parent), 'adults never get points');
    }

    public function test_a_finished_routine_and_packing_give_the_familys_points()
    {
        $this->family->forceFill(['settings' => ['points' => ['enabled' => true, 'task' => 1, 'routine' => 5, 'packing' => 2]]])->save();
        $routine = Routine::factory()->for($this->family)->withSteps([['Gå upp'], ['Klä på dig']])->forChild($this->child)->create();
        [$up, $dress] = $routine->steps()->get()->all();

        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $up]));
        $this->assertSame(0, $this->points($this->child), 'only a finished routine counts');
        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $dress]));
        $this->assertSame(5, $this->points($this->child));

        $this->actingAs($this->child)->delete(route('child.routines.steps.uncheck', [$routine, $dress]));
        $this->assertSame(0, $this->points($this->child));

        $swimming = Activity::factory()->for($this->family)->weekly(2)->withItems(['Badbyxor'])->concerning($this->child)->create();
        $this->actingAs($this->child)->post(route('child.activities.confirm', [$swimming, '2026-10-13']));
        $this->actingAs($this->child)->post(route('child.activities.confirm', [$swimming, '2026-10-13']));
        $this->assertSame(1, PointTransaction::query()->withoutGlobalScopes()->where('user_id', $this->child->id)->where('pending', true)->count());
    }

    public function test_packing_points_count_only_when_a_parent_approves()
    {
        $this->family->forceFill(['settings' => ['points' => ['enabled' => true, 'task' => 1, 'routine' => 5, 'packing' => 2]]])->save();
        $swimming = Activity::factory()->for($this->family)->weekly(2)->withItems(['Badbyxor', 'Handduk'])->concerning($this->child)->create(['name' => 'Simskola']);
        $this->actingAs($this->child)->post(route('child.activities.pack', [$swimming, '2026-10-13', $swimming->items()->firstOrFail()]));
        $this->actingAs($this->child)->post(route('child.activities.confirm', [$swimming, '2026-10-13']));

        $this->assertSame(0, $this->points($this->child));
        $this->actingAs($this->child)
            ->get(route('child.rewards.index'))
            ->assertInertia(fn (Assert $page) => $page->where('points', 0)->where('pending', 2));

        $waiting = PointTransaction::query()->withoutGlobalScopes()->where('user_id', $this->child->id)->firstOrFail();
        $this->actingAs($this->parent)
            ->get(route('rewards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('children.0.points', 0)
                ->where('waiting.0.child', 'Alva')
                ->where('waiting.0.reason', 'Packade till Simskola')
                ->where('waiting.0.packed', '1 av 2 saker bockade'));

        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $this->actingAs($editor)->post(route('points.approve', $waiting))->assertForbidden();
        $this->actingAs($this->child)->post(route('points.approve', $waiting));
        $this->assertTrue($waiting->fresh()?->pending);

        $this->actingAs($this->parent)->post(route('points.approve', $waiting))->assertRedirect();
        $this->assertSame(2, $this->points($this->child));
        $this->actingAs($this->parent)->post(route('points.decline', $waiting))->assertForbidden();
    }

    public function test_not_now_removes_waiting_packing_points()
    {
        $this->family->forceFill(['settings' => ['points' => ['enabled' => true, 'task' => 1, 'routine' => 5, 'packing' => 2]]])->save();
        $swimming = Activity::factory()->for($this->family)->weekly(2)->withItems(['Badbyxor'])->concerning($this->child)->create();
        $this->actingAs($this->child)->post(route('child.activities.confirm', [$swimming, '2026-10-13']));
        $waiting = PointTransaction::query()->withoutGlobalScopes()->where('user_id', $this->child->id)->firstOrFail();

        $this->actingAs($this->parent)->post(route('points.decline', $waiting))->assertRedirect();

        $this->assertModelMissing($waiting);
    }

    public function test_no_automatic_points_when_the_family_has_turned_them_off()
    {
        $this->actingAs($this->parent)
            ->put(route('points.settings'), ['enabled' => false, 'task' => 1, 'routine' => 3, 'packing' => 1])
            ->assertRedirect();

        $task = Task::factory()->for($this->family)->create(['assigned_to' => $this->child->id]);
        $this->actingAs($this->child)->post(route('tasks.completion.store', $task));

        $this->assertSame(0, $this->points($this->child));
        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page->where('points', null));
    }

    public function test_a_parent_gives_and_takes_points_with_a_reason()
    {
        $this->actingAs($this->parent)
            ->post(route('points.store'), ['child_id' => $this->child->id, 'points' => 5, 'reason' => 'Hjälpte till med disken'])
            ->assertRedirect();
        $this->actingAs($this->parent)
            ->post(route('points.store'), ['child_id' => $this->child->id, 'points' => -2, 'reason' => 'Rättning'])
            ->assertRedirect();

        $this->actingAs($this->parent)
            ->get(route('rewards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('rewards/Index')
                ->where('children.0.points', 3)
                ->where('history.0.reason', 'Rättning')
                ->where('history.1.reason', 'Hjälpte till med disken'));
    }

    public function test_only_parents_give_points_or_manage_rewards()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $reward = Reward::factory()->for($this->family)->create();

        foreach ([$editor, $viewer] as $adult) {
            $this->actingAs($adult)->get(route('rewards.index'))->assertForbidden();
            $this->actingAs($adult)->post(route('points.store'), ['child_id' => $this->child->id, 'points' => 5, 'reason' => 'Bra'])->assertForbidden();
            $this->actingAs($adult)->post(route('rewards.store'), ['name' => 'Glass', 'cost' => 5])->assertForbidden();
            $this->actingAs($adult)->delete(route('rewards.destroy', $reward))->assertForbidden();
        }

        // A child never reaches the parents' page, so it cannot give itself points.
        $this->actingAs($this->child)
            ->post(route('points.store'), ['child_id' => $this->child->id, 'points' => 50, 'reason' => 'Jag'])
            ->assertRedirect(route('child.home'));

        $this->assertSame(0, $this->points($this->child));
        $this->assertModelExists($reward);
    }

    public function test_points_are_only_given_to_the_familys_children()
    {
        $theirChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        foreach ([$this->parent, $theirChild] as $notAChild) {
            $this->actingAs($this->parent)
                ->post(route('points.store'), ['child_id' => $notAChild->id, 'points' => 5, 'reason' => 'Bra'])
                ->assertSessionHasErrors('child_id');
        }
    }

    public function test_a_child_wishes_and_a_parent_says_yes()
    {
        $movie = Reward::factory()->for($this->family)->create(['name' => 'Välja film', 'cost' => 10]);
        PointTransaction::factory()->for($this->family)->create(['user_id' => $this->child->id, 'points' => 12]);

        $this->actingAs($this->child)->post(route('child.rewards.wish', $movie))->assertRedirect();
        $this->actingAs($this->child)->post(route('child.rewards.wish', $movie));

        $this->actingAs($this->child)
            ->get(route('child.rewards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Rewards')
                ->where('points', 12)
                ->where('available', 2)
                ->where('rewards.0.waiting', true));

        $wish = RewardRedemption::query()->withoutGlobalScopes()->sole();

        $this->actingAs($this->parent)->post(route('redemptions.approve', $wish))->assertRedirect();
        $this->actingAs($this->parent)->post(route('redemptions.approve', $wish))->assertForbidden();

        $this->assertSame(RedemptionStatus::Approved, $wish->fresh()?->status);
        $this->assertSame(2, $this->points($this->child));
    }

    public function test_a_child_cannot_wish_for_more_than_it_has()
    {
        $trip = Reward::factory()->for($this->family)->create(['cost' => 30]);
        PointTransaction::factory()->for($this->family)->create(['user_id' => $this->child->id, 'points' => 12]);

        $this->actingAs($this->child)->post(route('child.rewards.wish', $trip))->assertRedirect();

        $this->assertDatabaseCount('reward_redemptions', 0);
    }

    public function test_saying_not_now_keeps_the_points()
    {
        $movie = Reward::factory()->for($this->family)->create(['cost' => 10]);
        PointTransaction::factory()->for($this->family)->create(['user_id' => $this->child->id, 'points' => 10]);
        $this->actingAs($this->child)->post(route('child.rewards.wish', $movie));
        $wish = RewardRedemption::query()->withoutGlobalScopes()->sole();

        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $this->actingAs($editor)->post(route('redemptions.approve', $wish))->assertForbidden();

        $this->actingAs($this->parent)->post(route('redemptions.decline', $wish))->assertRedirect();

        $this->assertSame(RedemptionStatus::Declined, $wish->fresh()?->status);
        $this->assertSame(10, $this->points($this->child));
    }

    public function test_the_display_shows_points_and_the_next_reward()
    {
        Reward::factory()->for($this->family)->create(['name' => 'Välja film', 'cost' => 10]);
        Reward::factory()->for($this->family)->create(['name' => 'Utflykt', 'cost' => 40]);
        PointTransaction::factory()->for($this->family)->create(['user_id' => $this->child->id, 'points' => 12]);

        app(CurrentFamily::class)->set($this->family);
        $widget = (new PointsWidget)->data(new DisplayContext($this->family), new DisplayWidget);

        $this->assertSame(12, $widget['children'][0]['points']);
        $this->assertSame(['name' => 'Utflykt', 'cost' => 40], $widget['children'][0]['next']);
    }

    public function test_another_familys_rewards_and_wishes_are_never_reached()
    {
        $theirs = Reward::factory()->create(['name' => 'Deras glass']);
        $theirWish = RewardRedemption::query()->forceCreate([
            'family_id' => $theirs->family_id,
            'reward_id' => $theirs->id,
            'user_id' => User::factory()->create()->id,
            'cost' => 10,
            'status' => RedemptionStatus::Requested,
        ]);

        $this->actingAs($this->parent)->put(route('rewards.update', $theirs), ['name' => 'Ändrat', 'cost' => 1])->assertNotFound();
        $this->actingAs($this->parent)->delete(route('rewards.destroy', $theirs))->assertNotFound();
        $this->actingAs($this->parent)->post(route('redemptions.approve', $theirWish))->assertNotFound();
        $theirPoints = PointTransaction::factory()->create(['user_id' => User::factory()->create()->id, 'pending' => true]);
        $this->actingAs($this->parent)->post(route('points.approve', $theirPoints))->assertNotFound();
        $this->actingAs($this->parent)->post(route('points.decline', $theirPoints))->assertNotFound();
        $this->assertTrue($theirPoints->fresh()?->pending);
        $this->actingAs($this->child)->post(route('child.rewards.wish', $theirs))->assertNotFound();

        $this->actingAs($this->parent)
            ->get(route('rewards.index'))
            ->assertInertia(fn (Assert $page) => $page->has('rewards', 0)->has('wishes', 0));
        $this->assertSame('Deras glass', $theirs->fresh()?->name);
    }
}
