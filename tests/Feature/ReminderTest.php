<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Support\CurrentFamily;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        // Tuesday 13 October 2026, 07:45 in Sweden.
        $this->travelTo('2026-10-13 05:45:00');
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
        $this->withDevice($this->parent);
        $this->withDevice($this->child);
    }

    private function withDevice(User $user): void
    {
        $user->updatePushSubscription('https://8.8.8.8/push/'.$user->id, 'public-key', 'auth-token', 'aes128gcm');
    }

    private function send(): void
    {
        $this->artisan('reminders:send')->assertSuccessful();
    }

    /**
     * @return list<string>
     */
    private function sentTo(User $user): array
    {
        return Notification::sent($user, ReminderNotification::class)->map(fn (ReminderNotification $notification) => $notification->title)->values()->all();
    }

    public function test_a_task_with_a_time_reminds_the_one_it_is_for_once()
    {
        Task::factory()->for($this->family)->create(['title' => 'Borsta tänderna', 'date' => '2026-10-13', 'time' => '07:45', 'assigned_to' => $this->child->id]);
        Task::factory()->for($this->family)->create(['title' => 'Senare', 'date' => '2026-10-13', 'time' => '08:30', 'assigned_to' => $this->child->id]);
        Task::factory()->for($this->family)->completed()->create(['title' => 'Redan klar', 'date' => '2026-10-13', 'time' => '07:40', 'assigned_to' => $this->child->id]);

        $this->send();
        $this->send();

        $this->assertSame(['Borsta tänderna'], $this->sentTo($this->child));
        $this->assertSame([], $this->sentTo($this->parent));
    }

    public function test_a_reminder_still_comes_when_a_run_was_a_few_minutes_late()
    {
        Task::factory()->for($this->family)->create(['title' => 'Packa väskan', 'date' => '2026-10-13', 'time' => '07:38', 'assigned_to' => $this->child->id]);
        Task::factory()->for($this->family)->create(['title' => 'För länge sen', 'date' => '2026-10-13', 'time' => '07:00', 'assigned_to' => $this->child->id]);

        $this->send();

        $this->assertSame(['Packa väskan'], $this->sentTo($this->child));
    }

    public function test_nothing_is_sent_during_quiet_hours_or_when_turned_off_or_without_a_device()
    {
        $this->travelTo('2026-10-13 18:30:00'); // 20:30 in Sweden.
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['quiet_from' => null, 'quiet_until' => null]);
        $this->withDevice($sibling);
        $off = User::factory()->child('7352')->inFamily($this->family, FamilyRole::Child)->create(['reminders_enabled' => false, 'quiet_from' => null, 'quiet_until' => null]);
        $this->withDevice($off);
        $noDevice = User::factory()->child('7353')->inFamily($this->family, FamilyRole::Child)->create(['quiet_from' => null, 'quiet_until' => null]);

        foreach ([$this->child, $sibling, $off, $noDevice] as $child) {
            Task::factory()->for($this->family)->create(['title' => 'Läggdags', 'date' => '2026-10-13', 'time' => '20:30', 'assigned_to' => $child->id]);
        }

        $this->send();

        $this->assertSame([], $this->sentTo($this->child), 'quiet 20:00–07:00 by default');
        $this->assertSame(['Läggdags'], $this->sentTo($sibling));
        $this->assertSame([], $this->sentTo($off));
        $this->assertSame([], $this->sentTo($noDevice));
    }

    public function test_a_routine_reminds_each_child_when_it_starts_unless_already_done()
    {
        Routine::factory()->for($this->family)->withSteps([['Gå upp']])->forChild($this->child)->create(['name' => 'Morgonrutin', 'start_time' => '07:45', 'done_by' => '08:15']);

        $this->send();

        $this->assertSame(['Morgonrutin'], $this->sentTo($this->child));
    }

    public function test_a_calendar_event_reminds_whose_calendar_it_is_15_minutes_before()
    {
        $calendar = Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id]);
        CalendarEvent::factory()->for($calendar)->create(['title' => 'Tandläkaren', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 07:00:00']);
        $family = Calendar::factory()->for($this->family)->create();
        CalendarEvent::factory()->for($family)->create(['title' => 'Ingen särskild', 'starts_at' => '2026-10-13 06:00:00', 'ends_at' => '2026-10-13 07:00:00']);

        $this->send();

        $this->assertSame(['Tandläkaren'], $this->sentTo($this->parent));
        $this->assertSame([], $this->sentTo($this->child));
    }

    public function test_children_are_reminded_to_pack_in_the_morning_of_the_day()
    {
        Activity::factory()->for($this->family)->weekly(3)->withItems(['Badbyxor'])->concerning($this->child)->create(['name' => 'Simskola']);
        $this->child->forceFill(['quiet_from' => '20:00', 'quiet_until' => '07:00'])->save();

        // The evening before: nothing, packing is not done ahead.
        $this->travelTo('2026-10-13 16:02:00'); // 18:02 in Sweden.
        $this->send();
        $this->assertSame([], $this->sentTo($this->child));

        $this->travelTo('2026-10-14 05:02:00'); // 07:02 on the Wednesday.
        $this->send();

        $this->assertSame(['Packa till Simskola'], $this->sentTo($this->child));
    }

    public function test_a_device_is_turned_on_and_off_and_private_addresses_are_refused()
    {
        $this->actingAs($this->parent)
            ->postJson(route('push-subscriptions.store'), ['endpoint' => 'https://10.0.0.5/push', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])
            ->assertJsonValidationErrors('endpoint');

        $this->actingAs($this->parent)
            ->postJson(route('push-subscriptions.store'), ['endpoint' => 'https://8.8.4.4/push/new', 'keys' => ['p256dh' => 'a', 'auth' => 'b'], 'content_encoding' => 'aes128gcm'])
            ->assertCreated()
            ->assertJsonPath('devices', 2);

        // Sending the same device again, as the app does on every visit, changes nothing.
        $this->actingAs($this->parent)
            ->postJson(route('push-subscriptions.store'), ['endpoint' => 'https://8.8.4.4/push/new', 'keys' => ['p256dh' => 'a', 'auth' => 'b'], 'content_encoding' => 'aes128gcm'])
            ->assertJsonPath('devices', 2);

        // Someone else's device is left alone.
        $this->actingAs($this->child)->delete(route('push-subscriptions.destroy'), ['endpoint' => 'https://8.8.4.4/push/new']);
        $this->assertSame(2, $this->parent->pushSubscriptions()->count());

        $this->actingAs($this->parent)->delete(route('push-subscriptions.destroy'), ['endpoint' => 'https://8.8.4.4/push/new']);
        $this->assertSame(1, $this->parent->pushSubscriptions()->count());
    }

    public function test_adults_set_their_quiet_hours_and_parents_set_their_childrens()
    {
        $this->actingAs($this->parent)
            ->put(route('reminders.update'), ['reminders_enabled' => true, 'quiet_from' => '21:30', 'quiet_until' => '06:30'])
            ->assertRedirect(route('reminders.edit'));
        $this->assertSame('21:30:00', $this->parent->fresh()?->quiet_from);

        $this->actingAs($this->parent)
            ->put(route('children.reminders.update', $this->child->id), ['reminders_enabled' => false])
            ->assertRedirect(route('children.show', $this->child->id));
        $this->assertFalse((bool) $this->child->fresh()?->reminders_enabled);
        $this->assertNull($this->child->fresh()?->quiet_from);

        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $this->actingAs($editor)
            ->put(route('children.reminders.update', $this->child->id), ['reminders_enabled' => true])
            ->assertForbidden();

        $theirChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();
        $this->actingAs($this->parent)
            ->put(route('children.reminders.update', $theirChild->id), ['reminders_enabled' => false])
            ->assertNotFound();

        app(CurrentFamily::class)->forget();
        $this->assertTrue((bool) $theirChild->fresh()?->reminders_enabled);
    }

    public function test_a_test_notification_shows_what_the_push_service_answered()
    {
        $sentTo = [];
        $this->mock(WebPushChannel::class, function ($mock) use (&$sentTo): void {
            $mock->shouldReceive('send')->andReturnUsing(function (User $user) use (&$sentTo): array {
                $sentTo[] = $user->id;

                return [new MessageSentReport(new Request('POST', 'https://8.8.8.8/push'), null, false, '403 Forbidden: BadJwtToken')];
            });
        });

        // During quiet hours too.
        $this->travelTo('2026-10-13 21:00:00');

        $this->actingAs($this->parent)->post(route('reminders.test'))->assertRedirect(route('reminders.edit'));
        $this->assertSame([$this->parent->id], $sentTo);
        $this->assertStringContainsString('BadJwtToken', (string) data_get(session('inertia.flash_data'), 'toast.message', json_encode(session()->all())));

        $withoutDevice = User::factory()->inFamily($this->family)->create();
        $this->actingAs($withoutDevice)->post(route('reminders.test'))->assertRedirect();
        $this->assertSame([$this->parent->id], $sentTo, 'nothing is sent without a device');
    }

    public function test_a_parent_sends_a_test_notification_to_a_childs_devices()
    {
        $sentTo = [];
        $this->mock(WebPushChannel::class, function ($mock) use (&$sentTo): void {
            $mock->shouldReceive('send')->andReturnUsing(function (User $user) use (&$sentTo): array {
                $sentTo[] = $user->id;

                return [new MessageSentReport(new Request('POST', 'https://8.8.8.8/push'), null, true, 'OK')];
            });
        });

        $this->actingAs($this->parent)->get(route('children.show', $this->child->id))
            ->assertInertia(fn (Assert $page) => $page->where('child.pushDevices', 1));

        $this->actingAs($this->parent)->post(route('children.reminders.test', $this->child->id))
            ->assertRedirect(route('children.show', $this->child->id));
        $this->assertSame([$this->child->id], $sentTo);

        // Only parents, and only their own family's children.
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $this->actingAs($editor)->post(route('children.reminders.test', $this->child->id))->assertForbidden();
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();
        $this->actingAs($this->parent)->post(route('children.reminders.test', $otherChild->id))->assertNotFound();

        $this->assertSame([$this->child->id], $sentTo);
    }

    public function test_an_activity_reminds_everyone_taking_part_as_long_before_as_chosen()
    {
        $sibling = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        $this->withDevice($sibling);
        Activity::factory()->for($this->family)->concerning($this->child, $this->parent)->create([
            'name' => 'Simskola', 'date' => '2026-10-13', 'time' => '09:45', 'reminders' => [120, 60],
        ]);

        // 07:45: two hours before.
        $this->send();
        $this->send();

        $bodies = fn (User $user) => Notification::sent($user, ReminderNotification::class)->map(fn (ReminderNotification $notification) => $notification->body)->values()->all();
        $this->assertSame(['Simskola'], $this->sentTo($this->child));
        $this->assertSame(['Börjar 09:45. 2 timmar kvar.'], $bodies($this->child));
        $this->assertSame(['Simskola'], $this->sentTo($this->parent));
        $this->assertSame([], $this->sentTo($sibling), 'only to those who take part');

        // 08:45: one hour before.
        $this->travelTo('2026-10-13 06:45:00');
        $this->send();
        $this->assertSame(['Börjar 09:45. 2 timmar kvar.', 'Börjar 09:45. 1 timme kvar.'], $bodies($this->child));
    }

    public function test_reminders_are_chosen_on_the_activity_and_need_a_time()
    {
        $fields = ['type' => 'activity', 'name' => 'Fotboll', 'participants' => [$this->child->id], 'repeat' => 'once', 'date' => '2026-10-14', 'visible_to_children' => true, 'unusual' => false];

        $this->actingAs($this->parent)->post(route('activities.store'), [...$fields, 'time' => '17:00', 'reminders' => [60, 120]])->assertRedirect();
        $this->assertSame([120, 60], Activity::query()->withoutGlobalScopes()->where('name', 'Fotboll')->firstOrFail()->reminders);

        $this->actingAs($this->parent)->post(route('activities.store'), [...$fields, 'name' => 'Utan tid', 'reminders' => [60]])->assertRedirect();
        $this->assertNull(Activity::query()->withoutGlobalScopes()->where('name', 'Utan tid')->firstOrFail()->reminders);

        $this->actingAs($this->parent)->post(route('activities.store'), [...$fields, 'time' => '17:00', 'reminders' => [45]])->assertSessionHasErrors('reminders.0');
    }

    public function test_an_adult_who_follows_the_children_gets_their_activity_reminders_too()
    {
        $bo = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        $other = User::factory()->inFamily($this->family)->create(['name' => 'Sam']);
        $this->withDevice($other);
        Activity::factory()->for($this->family)->concerning($this->child)->create([
            'name' => 'Simskola', 'date' => '2026-10-13', 'time' => '09:45', 'reminders' => [120],
        ]);

        // Kim follows Alva; Sam follows only Bo.
        $this->actingAs($this->parent)->put(route('reminders.children'), ['child_reminders' => true, 'all_children' => false, 'child_ids' => [$this->child->id]])->assertRedirect(route('reminders.edit'));
        $this->actingAs($other)->put(route('reminders.children'), ['child_reminders' => true, 'all_children' => false, 'child_ids' => [$bo->id]])->assertRedirect();

        $this->send();

        $this->assertSame(['Simskola'], $this->sentTo($this->child));
        $this->assertSame(['Simskola (Alva)'], $this->sentTo($this->parent));
        $this->assertSame([], $this->sentTo($other));

        $this->actingAs($this->parent)->get(route('reminders.edit'))->assertInertia(fn (Assert $page) => $page
            ->where('childReminders', ['enabled' => true, 'childIds' => [$this->child->id]])
            ->has('children', 2));
    }

    public function test_only_the_familys_children_can_be_followed()
    {
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($this->parent)->put(route('reminders.children'), ['child_reminders' => true, 'all_children' => false, 'child_ids' => [$otherChild->id]])->assertSessionHasErrors('child_ids.0');
        $this->actingAs($this->parent)->put(route('reminders.children'), ['child_reminders' => true, 'all_children' => false, 'child_ids' => []])->assertSessionHasErrors('child_ids');
        $this->actingAs($this->parent)->put(route('reminders.children'), ['child_reminders' => true, 'all_children' => true])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('family_user', ['user_id' => $this->parent->id, 'child_reminders' => true, 'child_reminder_ids' => null]);
    }
}
