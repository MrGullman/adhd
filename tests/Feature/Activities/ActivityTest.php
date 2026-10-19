<?php

namespace Tests\Feature\Activities;

use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Family;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 12 October 2026, 15:00 in Sweden.
        $this->travelTo('2026-10-12 13:00:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Mamma']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    /**
     * Simskola every Tuesday 17:15 with a packing list, for the child.
     */
    private function swimming(): Activity
    {
        return Activity::factory()->for($this->family)->weekly(2)
            ->withItems(['Badbyxor', 'Handduk', 'Schampo'])
            ->concerning($this->child)
            ->create();
    }

    public function test_an_editor_adds_a_weekly_activity_with_a_packing_list()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->post(route('activities.store'), [
                'type' => 'activity',
                'name' => 'Simskola',
                'participants' => [$this->child->id],
                'repeat' => 'weekly',
                'weekday' => 2,
                'time' => '17:15',
                'visible_to_children' => true,
                'items' => [['label' => 'Badbyxor'], ['label' => 'Handduk']],
            ])
            ->assertRedirect(route('activities.index'));

        $activity = Activity::query()->withoutGlobalScopes()->where('name', 'Simskola')->firstOrFail();
        $this->assertSame($this->family->id, $activity->family_id);
        $this->assertSame(2, $activity->weekday);
        $this->assertSame(['Badbyxor', 'Handduk'], $activity->items()->pluck('label')->all());
    }

    public function test_only_activities_have_packing_lists()
    {
        $this->actingAs($this->parent)->post(route('activities.store'), [
            'type' => 'pickup',
            'name' => 'Mormor hämtar',
            'repeat' => 'once',
            'date' => '2026-10-13',
            'items' => [['label' => 'Nyckel']],
        ]);

        $this->assertSame(0, Activity::query()->withoutGlobalScopes()->where('name', 'Mormor hämtar')->firstOrFail()->items()->count());
    }

    public function test_a_viewer_cannot_add_activities()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();

        $this->actingAs($viewer)
            ->post(route('activities.store'), ['type' => 'activity', 'name' => 'Fotboll', 'repeat' => 'once', 'date' => '2026-10-13'])
            ->assertForbidden();
    }

    public function test_the_day_before_the_child_only_sees_what_to_pack()
    {
        $swimming = $this->swimming();
        [$trunks] = $swimming->items()->get()->all();

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('packTomorrow.0.name', 'Simskola')
                ->has('packTomorrow.0.packing.items', 3),
            );

        // Children clicked through tomorrow's packing without packing: it waits for the day.
        $this->actingAs($this->child)->post(route('child.activities.pack', [$swimming, '2026-10-13', $trunks]))->assertNotFound();
        $this->actingAs($this->child)->get(route('child.activities.show', [$swimming, '2026-10-13']))->assertNotFound();
        $this->assertDatabaseCount('activity_item_checks', 0);
    }

    public function test_on_the_day_the_child_packs_one_thing_at_a_time_on_its_own_page()
    {
        $swimming = $this->swimming();
        [$trunks] = $swimming->items()->get()->all();
        $this->travelTo('2026-10-13 06:00:00');

        $this->actingAs($this->child)->post(route('child.activities.pack', [$swimming, '2026-10-13', $trunks]));

        $this->actingAs($this->child)
            ->get(route('child.activities.show', [$swimming, '2026-10-13']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Pack')
                ->where('activity.name', 'Simskola')
                ->where('activity.packing.items.0.label', 'Badbyxor')
                ->where('activity.packing.items.0.packed', true)
                ->where('activity.packing.items.1.packed', false)
                ->where('activity.packing.packed', 1));

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('activities.0.packing.packed', 1));

        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();
        $this->actingAs($sibling)->get(route('child.activities.show', [$swimming, '2026-10-13']))->assertForbidden();
        $this->actingAs($this->child)->get(route('child.activities.show', [$swimming, '2026-10-20']))->assertNotFound();

        $theirs = Activity::factory()->for(Family::factory())->weekly(2)->withItems(['Handduk'])->create();
        $this->actingAs($this->child)->get(route('child.activities.show', [$theirs, '2026-10-13']))->assertNotFound();
    }

    public function test_on_the_day_the_child_answers_that_they_have_packed_and_the_adults_see_it()
    {
        $swimming = $this->swimming();
        $this->travelTo('2026-10-13 06:00:00');

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.0.name', 'Simskola')
                ->where('activities.0.packing.confirmed', false),
            );

        $this->actingAs($this->child)
            ->post(route('child.activities.confirm', [$swimming, '2026-10-13']))
            ->assertRedirect();

        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.0.packing.0.child', 'Alva')
                ->where('activities.0.packing.0.confirmed', true),
            );
    }

    public function test_the_packing_list_is_used_again_next_time()
    {
        $swimming = $this->swimming();
        $this->travelTo('2026-10-13 06:00:00');
        $this->actingAs($this->child)->post(route('child.activities.pack', [$swimming, '2026-10-13', $swimming->items()->firstOrFail()]));

        // The next Tuesday starts with nothing packed.
        $this->travelTo('2026-10-20 06:00:00');

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.0.packing.date', '2026-10-20')
                ->where('activities.0.packing.packed', 0),
            );
    }

    public function test_a_child_packs_only_for_today_and_only_what_they_go_to()
    {
        $swimming = $this->swimming();
        $item = $swimming->items()->firstOrFail();
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();
        $this->travelTo('2026-10-13 06:00:00');

        // Next week's Tuesday is not today.
        $this->actingAs($this->child)
            ->post(route('child.activities.pack', [$swimming, '2026-10-20', $item]))
            ->assertNotFound();

        // Not a day the activity happens.
        $this->actingAs($this->child)
            ->post(route('child.activities.confirm', [$swimming, '2026-10-12']))
            ->assertNotFound();

        $this->actingAs($sibling)
            ->post(route('child.activities.pack', [$swimming, '2026-10-13', $item]))
            ->assertForbidden();

        $this->assertDatabaseCount('activity_item_checks', 0);
    }

    public function test_children_see_what_concerns_them_but_never_the_adults_other_events()
    {
        Activity::factory()->for($this->family)->concerning($this->parent)->create([
            'type' => 'activity', 'name' => 'Möte på jobbet', 'visible_to_children' => false,
        ]);
        Activity::factory()->for($this->family)->create([
            'type' => 'pickup', 'name' => 'Mormor hämtar', 'time' => '15:30:00', 'visible_to_children' => true, 'unusual' => true,
        ]);
        Activity::factory()->for($this->family)->concerning($this->child)->create([
            'name' => 'Kalas', 'visible_to_children' => false,
        ]);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities', 2)
                ->where('activities.0.name', 'Mormor hämtar')
                ->where('activities.0.unusual', true)
                ->where('activities.1.name', 'Kalas'),
            );

        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('activities', 3));
    }

    public function test_a_child_does_not_see_a_siblings_activity()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();
        Activity::factory()->for($this->family)->concerning($sibling)->create(['name' => 'Syskonets simskola']);
        Activity::factory()->for($this->family)->create(['type' => 'day_off', 'name' => 'Studiedag', 'time' => null]);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities', 1)
                ->where('activities.0.name', 'Studiedag'),
            );
    }

    public function test_children_count_the_nights_until_someone_comes_home()
    {
        Activity::factory()->for($this->family)->concerning($this->parent)->create([
            'type' => 'away', 'name' => 'Mamma i Stockholm', 'date' => '2026-10-12', 'end_date' => '2026-10-14', 'time' => null,
        ]);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.0.nightsLeft', 2)
                ->where('activities.0.participants.0.name', 'Mamma')
                ->where('tomorrow.0.nightsLeft', 1),
            );
    }

    public function test_the_childs_week_shows_at_most_three_things_a_day()
    {
        foreach (['Simskola', 'Fotboll', 'Kalas', 'Bio'] as $name) {
            Activity::factory()->for($this->family)->concerning($this->child)->create(['name' => $name, 'date' => '2026-10-14']);
        }

        $this->actingAs($this->child)
            ->get(route('child.week'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Week')
                ->where('days.0.date', '2026-10-12')
                ->where('days.0.isToday', true)
                ->has('days.2.activities', 3)
                ->where('days.2.more', 1),
            );

        $this->actingAs($this->parent)
            ->get(route('week'))
            ->assertInertia(fn (Assert $page) => $page->has('days.2.activities', 4));
    }

    public function test_tomorrow_shows_what_to_get_ready_for()
    {
        $this->swimming();

        $this->actingAs($this->parent)
            ->get(route('tomorrow'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('activities/Tomorrow')
                ->where('date', '2026-10-13')
                ->where('activities.0.name', 'Simskola')
                ->where('activities.0.packing.0.packed', 0)
                ->where('activities.0.packing.0.total', 3),
            );
    }

    public function test_the_routine_step_kolla_packningen_shows_todays_packing()
    {
        $this->swimming();
        $this->travelTo('2026-10-13 05:00:00');
        $routine = Routine::factory()->for($this->family)->withSteps([['Kolla packningen']])->forChild($this->child)->create();

        $this->actingAs($this->child)
            ->get(route('child.routines.show', $routine))
            ->assertInertia(fn (Assert $page) => $page
                ->where('packing.0.name', 'Simskola')
                ->has('packing.0.packing.items', 3),
            );
    }
}
