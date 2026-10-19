<?php

namespace Tests\Feature\Activities;

use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Family;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Inställd den här gången": one occurrence of a weekly activity does not happen.
 */
class ActivityCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    private Activity $swimming;

    protected function setUp(): void
    {
        parent::setUp();

        // Monday 12 October 2026, 15:00 in Sweden. Simskola every Tuesday 17:15.
        $this->travelTo('2026-10-12 13:00:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
        $this->swimming = Activity::factory()->for($this->family)->weekly(2)->withItems(['Badbyxor'])->concerning($this->child)->create(['reminders' => [60]]);
    }

    public function test_one_occurrence_is_cancelled_and_shown_without_packing_or_reminders()
    {
        $this->actingAs($this->parent)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-13']))->assertRedirect();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->has('packTomorrow', 0)
            ->where('tomorrow.0.cancelled', true)
            ->where('tomorrow.0.unusual', true)
            ->where('tomorrow.0.packing', null));
        $this->actingAs($this->child)->get(route('child.activities.show', [$this->swimming, '2026-10-13']))->assertNotFound();

        // No packing reminder in the evening, and no reminder before it.
        $this->child->updatePushSubscription('https://8.8.8.8/push/child', 'public-key', 'auth-token', 'aes128gcm');
        Notification::fake();
        $this->travelTo('2026-10-12 16:00:00');
        $this->artisan('reminders:send')->assertSuccessful();
        $this->travelTo('2026-10-13 14:15:00');
        $this->artisan('reminders:send')->assertSuccessful();
        Notification::assertNotSentTo($this->child, ReminderNotification::class);

        // Next week as usual.
        $this->travelTo('2026-10-19 13:00:00');
        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->where('packTomorrow.0.name', $this->swimming->name));
    }

    public function test_undo_makes_it_happen_as_usual()
    {
        $this->actingAs($this->parent)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-13']));
        $this->actingAs($this->parent)->delete(route('activities.cancellations.destroy', [$this->swimming, '2026-10-13']))->assertRedirect();

        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->where('packTomorrow.0.cancelled', false));
        $this->assertDatabaseCount('activity_cancellations', 0);
    }

    public function test_only_a_coming_occurrence_of_a_weekly_activity_can_be_cancelled()
    {
        $once = Activity::factory()->for($this->family)->create(['date' => '2026-10-14']);

        $this->actingAs($this->parent)->post(route('activities.cancellations.store', [$once, '2026-10-14']))->assertNotFound();
        $this->actingAs($this->parent)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-14']))->assertNotFound();
        $this->actingAs($this->parent)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-06']))->assertNotFound();

        $this->assertDatabaseCount('activity_cancellations', 0);
    }

    public function test_viewers_children_and_other_families_cannot_cancel()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $otherParent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->actingAs($viewer)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-13']))->assertForbidden();
        $this->actingAs($this->child)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-13']))->assertRedirect(route('child.home'));
        $this->actingAs($otherParent)->post(route('activities.cancellations.store', [$this->swimming, '2026-10-13']))->assertNotFound();

        $this->assertDatabaseCount('activity_cancellations', 0);
    }
}
