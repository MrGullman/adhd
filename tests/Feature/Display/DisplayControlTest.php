<?php

namespace Tests\Feature\Display;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Hemskärmen from the phone: a member's day for a while, back to the family
 * view, or off for the night. The display itself stays read-only.
 */
class DisplayControlTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    private Device $device;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 13 October 2026, 07:15 in Sweden.
        $this->travelTo('2026-10-13 05:15:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
        [$this->device, $this->token] = Device::issue(DeviceType::Display, 'Köket', null, $this->family);
        DisplayWidget::factory()->for($this->device)->create(['family_id' => $this->family->id, 'type' => 'shopping_list']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function choose(User $as, array $data, ?Device $device = null): TestResponse
    {
        return $this->actingAs($as)->put(route('displays.view.update', ($device ?? $this->device)->id), $data);
    }

    private function display(): TestResponse
    {
        $this->post(route('logout'));

        return $this->get(route('display.show', $this->token));
    }

    public function test_an_adult_shows_a_childs_day_for_a_while_and_the_display_goes_back_by_itself()
    {
        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->child->id, 'duration' => '15'])
            ->assertRedirect(route('displays.control'));

        $this->display()->assertInertia(fn (Assert $page) => $page
            ->where('view.mode', 'member')
            ->where('view.member.name', 'Alva')
            ->where('view.until', '07:30')
            ->where('widgets.0.type', 'child')
            ->where('widgets.0.data.child.name', 'Alva')
            ->where('widgets.1.type', 'tasks')
            ->where('widgets.1.data.member.name', 'Alva'));

        $this->travel(16)->minutes();

        $this->display()->assertInertia(fn (Assert $page) => $page
            ->where('view.mode', 'family')
            ->where('widgets.0.type', 'shopping_list'));
        $this->assertArrayNotHasKey('view', $this->device->fresh()?->settings ?? []);
    }

    public function test_the_display_only_has_to_ask_for_the_key_to_notice_a_change()
    {
        $before = null;
        $this->display()->assertInertia(function (Assert $page) use (&$before) {
            $before = $page->toArray()['props']['viewKey'];
        });

        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->child->id, 'duration' => '5']);

        $this->post(route('logout'));
        $this->get(route('display.show', $this->token), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) Inertia::getVersion(),
            'X-Inertia-Partial-Component' => 'display/Index',
            'X-Inertia-Partial-Data' => 'viewKey',
        ])->assertOk()->assertJsonMissingPath('props.widgets')->assertJsonPath('props.viewKey', fn (string $key) => $key !== $before);
    }

    public function test_until_the_routine_is_done_goes_back_when_the_child_has_finished()
    {
        $routine = Routine::factory()->for($this->family)->withSteps([['Gå upp'], ['Borsta tänderna']])->forChild($this->child)->create();
        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->child->id, 'duration' => 'rutin']);

        $this->display()->assertInertia(fn (Assert $page) => $page
            ->where('view.mode', 'member')
            ->where('view.untilRoutine', true));

        foreach ($routine->steps()->get() as $step) {
            $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $step]));
        }

        $this->display()->assertInertia(fn (Assert $page) => $page->where('view.mode', 'family'));
    }

    public function test_until_the_routine_is_done_is_only_for_children()
    {
        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->parent->id, 'duration' => 'rutin'])
            ->assertSessionHasErrors('duration');
        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->child->id, 'duration' => '45'])
            ->assertSessionHasErrors('duration');
    }

    public function test_an_adults_day_only_shows_what_the_children_may_see()
    {
        Activity::factory()->for($this->family)->concerning($this->parent)->create(['name' => 'Möte på jobbet', 'date' => '2026-10-13', 'visible_to_children' => false]);
        Activity::factory()->for($this->family)->concerning($this->parent)->create(['name' => 'Fotboll med Alva', 'date' => '2026-10-13', 'visible_to_children' => true]);
        Task::factory()->for($this->family)->create(['title' => 'Handla', 'assigned_to' => $this->parent->id, 'date' => '2026-10-13']);

        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $this->parent->id, 'duration' => '30']);

        $this->display()->assertInertia(fn (Assert $page) => $page
            ->where('view.member.name', 'Kim')
            ->where('widgets.0.type', 'activities')
            ->has('widgets.0.data.activities', 1)
            ->where('widgets.0.data.activities.0.name', 'Fotboll med Alva')
            ->where('widgets.1.data.tasks.0.title', 'Handla'));
    }

    public function test_the_display_is_turned_off_until_the_morning()
    {
        $this->choose($this->parent, ['mode' => 'off'])->assertRedirect();

        $this->display()->assertInertia(fn (Assert $page) => $page
            ->where('view.mode', 'off')
            ->where('view.until', '06:00')
            ->where('widgets', []));

        // Wednesday 06:01 in Sweden: on again by itself.
        $this->travelTo('2026-10-14 04:01:00');
        $this->display()->assertInertia(fn (Assert $page) => $page->where('view.mode', 'family'));

        // Or turned on from the phone before that.
        $this->choose($this->parent, ['mode' => 'off']);
        $this->choose($this->parent, ['mode' => 'family']);
        $this->display()->assertInertia(fn (Assert $page) => $page->where('view.mode', 'family'));
    }

    public function test_adults_who_may_change_the_planning_control_the_display()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();

        $this->choose($editor, ['mode' => 'off'])->assertRedirect(route('displays.control'));
        $this->actingAs($editor)->get(route('displays.control'))->assertInertia(fn (Assert $page) => $page
            ->component('display/Control')
            ->where('displays.0.name', 'Köket')
            ->where('displays.0.view.mode', 'off')
            ->where('canSetUp', false));

        $this->choose($viewer, ['mode' => 'family'])->assertForbidden();
        $this->actingAs($viewer)->get(route('displays.control'))->assertInertia(fn (Assert $page) => $page->has('displays', 0));
        $this->choose($this->child, ['mode' => 'family'])->assertRedirect();

        $this->assertSame('off', $this->device->fresh()?->settings['view']['mode'] ?? null);
    }

    public function test_another_familys_display_and_members_are_never_reached()
    {
        $other = Family::factory()->create();
        $otherChild = User::factory()->child()->inFamily($other, FamilyRole::Child)->create();
        [$theirs] = Device::issue(DeviceType::Display, 'Deras kök', null, $other);

        $this->choose($this->parent, ['mode' => 'off'], $theirs)->assertNotFound();
        $this->choose($this->parent, ['mode' => 'member', 'user_id' => $otherChild->id, 'duration' => '15'])->assertSessionHasErrors('user_id');
        $this->actingAs($this->parent)->get(route('displays.control'))->assertInertia(fn (Assert $page) => $page
            ->has('displays', 1)
            ->where('displays.0.name', 'Köket'));

        $this->assertNull($theirs->fresh()?->settings['view'] ?? null);
        $this->assertNull($this->device->fresh()?->settings['view'] ?? null);
    }
}
