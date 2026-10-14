<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Jobs\SyncCalendarJob;
use App\Models\Activity;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarTest extends TestCase
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
        app(CurrentFamily::class)->set($this->family);
    }

    private function calendar(string $fixture, array $attributes = []): Calendar
    {
        Http::fake(['8.8.8.8/*' => Http::response((string) file_get_contents(base_path("tests/Fixtures/calendars/{$fixture}.ics")))]);

        return Calendar::factory()->for($this->family)->create([
            'url' => "https://8.8.8.8/{$fixture}/basic.ics",
            ...$attributes,
        ]);
    }

    private function sync(): void
    {
        $this->artisan('calendars:sync')->assertSuccessful();
        app(CurrentFamily::class)->set($this->family);
    }

    /**
     * @return list<array{0: string, 1: string|null, 2: bool}> title, start in Sweden, all day
     */
    private function events(): array
    {
        return CalendarEvent::query()->orderBy('starts_at')->get()->map(fn (CalendarEvent $event) => [
            $event->title,
            $event->all_day ? $event->starts_at->toDateString() : $event->starts_at->copy()->setTimezone('Europe/Stockholm')->format('Y-m-d H:i'),
            $event->all_day,
        ])->all();
    }

    public function test_google_weekly_events_keep_their_time_over_the_change_to_winter_time()
    {
        $this->calendar('google');
        $this->sync();

        $swimming = array_values(array_filter($this->events(), fn (array $event) => $event[0] === 'Simskola'));

        // From a week back to eight weeks ahead, without the cancelled 20 October.
        $this->assertSame(['2026-10-06 17:15', '2026-10-13 17:15', '2026-10-27 17:15'], array_column(array_slice($swimming, 0, 3), 1));
        $this->assertContains(['Studiedag', '2026-10-16', true], $this->events());
        $this->assertContains(['Tandläkaren', '2026-10-14 08:00', false], $this->events());
    }

    public function test_icloud_overnight_and_multi_day_events()
    {
        $this->calendar('icloud');
        $this->sync();

        $night = CalendarEvent::query()->where('title', 'Jobbar natt')->sole();
        $this->assertSame('2026-10-15 19:20', $night->starts_at->format('Y-m-d H:i'), 'stored in UTC');
        $this->assertTrue($night->occursOn(now()->setDate(2026, 10, 16)->toImmutable()));

        $cabin = CalendarEvent::query()->where('title', 'Stugan')->sole();
        $this->assertTrue($cabin->all_day);
        $this->assertSame('2026-10-26', $cabin->ends_at?->toDateString());
        $this->assertFalse($cabin->occursOn(now()->setDate(2026, 10, 26)->toImmutable()), 'the end date is not included');
    }

    public function test_outlook_windows_time_zones_moved_and_cancelled_events()
    {
        $this->calendar('outlook');
        $this->sync();

        $meetings = array_values(array_filter($this->events(), fn (array $event) => $event[0] === 'Möte på jobbet'));

        $this->assertSame(
            ['2026-10-12 09:00', '2026-10-13 09:00', '2026-10-14 13:00', '2026-10-15 09:00', '2026-10-16 09:00'],
            array_column($meetings, 1),
        );
        $this->assertNotContains('Inställt', array_column($this->events(), 0));
    }

    public function test_a_failed_read_keeps_what_was_read_before()
    {
        Http::fake(['8.8.8.8/*' => Http::sequence()
            ->push((string) file_get_contents(base_path('tests/Fixtures/calendars/google.ics')))
            ->push('', 500)
            ->push('', 500)
            ->push('', 500)]);
        $calendar = Calendar::factory()->for($this->family)->create(['url' => 'https://8.8.8.8/google/basic.ics']);

        $this->sync();
        $count = CalendarEvent::query()->count();
        $this->assertGreaterThan(0, $count);

        $this->sync();

        $this->assertSame($count, CalendarEvent::query()->count());
        $this->assertNotNull($calendar->fresh()?->last_failed_at);
    }

    public function test_a_parent_adds_a_calendar_and_it_is_read_in_the_background()
    {
        Queue::fake();

        $this->actingAs($this->parent)
            ->post(route('calendars.store'), [
                'name' => 'Jobb',
                'url' => 'webcal://8.8.8.8/published/2/abc.ics',
                'user_id' => $this->parent->id,
            ])
            ->assertRedirect(route('calendars.index'));

        $calendar = Calendar::query()->sole();
        $this->assertSame('webcal://8.8.8.8/published/2/abc.ics', $calendar->url);
        $this->assertStringNotContainsString('abc.ics', (string) DB::table('calendars')->value('url'), 'the link is encrypted');
        Queue::assertPushed(SyncCalendarJob::class);

        $this->actingAs($this->parent)
            ->get(route('calendars.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('calendars/Index')
                ->where('calendars.0.name', 'Jobb')
                ->missing('calendars.0.url'));
    }

    public function test_links_to_the_servers_own_network_are_refused()
    {
        foreach (['http://8.8.8.8/a.ics', 'https://127.0.0.1/a.ics', 'https://10.0.0.5/a.ics', 'https://localhost/a.ics', 'https://169.254.169.254/latest', 'file:///etc/passwd'] as $link) {
            $this->actingAs($this->parent)
                ->post(route('calendars.store'), ['name' => 'Test', 'url' => $link])
                ->assertSessionHasErrors('url');
        }

        $this->assertSame(0, Calendar::query()->count());
    }

    public function test_only_parents_manage_calendars()
    {
        $calendar = Calendar::factory()->for($this->family)->create();
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)->get(route('calendars.index'))->assertForbidden();
        $this->actingAs($editor)->post(route('calendars.store'), ['name' => 'X', 'url' => 'https://8.8.8.8/a.ics'])->assertForbidden();
        $this->actingAs($editor)->delete(route('calendars.destroy', $calendar))->assertForbidden();
        $this->actingAs($this->child)->get(route('calendars.index'))->assertRedirect(route('child.home'));

        $this->assertModelExists($calendar);
    }

    public function test_children_only_see_their_own_calendar_and_the_ones_shown_to_them()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        $at = fn (string $time) => now()->setTimeFromTimeString($time);
        $event = fn (Calendar $calendar, string $title) => CalendarEvent::factory()->for($calendar)->create(['title' => $title, 'starts_at' => $at('14:00'), 'ends_at' => $at('15:00')]);

        $event(Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id]), 'Lönesamtal');
        $event(Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id, 'visible_to_children' => true]), 'Mamma jobbar sent');
        $event(Calendar::factory()->for($this->family)->create(['user_id' => $this->child->id]), 'Kalas hos Siv');
        $event(Calendar::factory()->for($this->family)->create(['user_id' => $sibling->id]), 'Bos tandläkare');

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->has('calendarEvents', 2)
                ->where('calendarEvents.0.title', 'Mamma jobbar sent')
                ->where('calendarEvents.1.title', 'Kalas hos Siv'));

        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('calendarEvents', 4)
                ->where('calendarEvents.0.time', '16:00')
                ->where('calendarEvents.0.endTime', '17:00'));
    }

    public function test_an_event_named_like_an_activity_is_shown_once_with_its_picture()
    {
        $picture = Picture::factory()->create(['family_id' => $this->family->id, 'label' => 'Simning']);
        Activity::factory()->for($this->family)->weekly(2)->concerning($this->child)->create(['name' => 'Simskola', 'picture_id' => $picture->id]);
        $calendar = Calendar::factory()->for($this->family)->create(['visible_to_children' => true]);
        CalendarEvent::factory()->for($calendar)->create(['title' => 'Simskola', 'starts_at' => '2026-10-13 15:15:00', 'ends_at' => '2026-10-13 16:00:00']);
        CalendarEvent::factory()->for($calendar)->create(['title' => 'simskola', 'starts_at' => '2026-10-14 15:15:00', 'ends_at' => '2026-10-14 16:00:00']);

        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('activities', 1)->has('calendarEvents', 0));

        $this->travelTo('2026-10-14 06:00:00');
        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('calendarEvents.0.picture.label', 'Simning'));
    }

    public function test_the_display_shows_calendars_in_the_week_grid_and_the_calendar_widget()
    {
        [$device, $token] = Device::issue(DeviceType::Display, 'Köket', null, $this->family);
        DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => 'week_grid', 'w' => 12, 'h' => 4, 'settings' => ['member_ids' => [], 'rows' => []]]);
        DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => 'calendar', 'y' => 4, 'w' => 4, 'h' => 2]);

        $work = Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id, 'visible_to_children' => true]);
        CalendarEvent::factory()->for($work)->create(['title' => 'Jobbar natt', 'starts_at' => '2026-10-13 19:20:00', 'ends_at' => '2026-10-14 05:20:00']);
        $private = Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id]);
        CalendarEvent::factory()->for($private)->create(['title' => 'Lönesamtal', 'starts_at' => '2026-10-13 12:00:00', 'ends_at' => '2026-10-13 13:00:00']);
        $family = Calendar::factory()->for($this->family)->create(['visible_to_children' => true]);
        CalendarEvent::factory()->for($family)->create(['title' => 'Studiedag', 'all_day' => true, 'starts_at' => '2026-10-16 00:00:00', 'ends_at' => '2026-10-17 00:00:00']);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.rows.0.label', 'Kim')
                ->where('widgets.0.data.rows.0.cells.1.0.name', 'Jobbar natt')
                ->where('widgets.0.data.rows.0.cells.1.0.time', '21:20')
                ->where('widgets.0.data.rows.0.cells.2.0.time', '→ 07:20')
                ->where('widgets.0.data.rows.2.label', 'Alla')
                ->where('widgets.0.data.rows.2.cells.4.0.name', 'Studiedag')
                ->has('widgets.1.data.days.0.events', 1)
                ->where('widgets.1.data.days.0.events.0.title', 'Jobbar natt'));
    }

    public function test_tomorrow_and_the_weeks_show_calendar_events_too()
    {
        $work = Calendar::factory()->for($this->family)->create(['user_id' => $this->parent->id]);
        $shared = Calendar::factory()->for($this->family)->create(['visible_to_children' => true]);
        CalendarEvent::factory()->for($work)->create(['title' => 'Lönesamtal', 'starts_at' => '2026-10-14 08:00:00', 'ends_at' => '2026-10-14 09:00:00']);
        CalendarEvent::factory()->for($shared)->create(['title' => 'Höstlov', 'all_day' => true, 'starts_at' => '2026-10-26 00:00:00', 'ends_at' => '2026-10-31 00:00:00']);

        $this->actingAs($this->parent)
            ->get(route('tomorrow'))
            ->assertInertia(fn (Assert $page) => $page->where('calendarEvents.0.title', 'Lönesamtal')->where('calendarEvents.0.time', '10:00'));

        // In two weeks, Monday 26 October: the autumn break.
        $this->actingAs($this->parent)
            ->get(route('week', ['vecka' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.date', '2026-10-26')
                ->where('days.0.calendarEvents.0.title', 'Höstlov')
                ->where('days.0.calendarEvents.0.allDay', true));

        // The child sees the shared calendar, never the parent's work.
        $this->actingAs($this->child)
            ->get(route('child.week'))
            ->assertInertia(fn (Assert $page) => $page->has('days.2.calendarEvents', 0));
        $this->actingAs($this->child)
            ->get(route('child.week', ['vecka' => 2]))
            ->assertInertia(fn (Assert $page) => $page->where('days.0.calendarEvents.0.title', 'Höstlov'));
    }

    public function test_another_familys_calendar_is_never_reached()
    {
        $theirs = Calendar::factory()->create(['name' => 'Deras']);
        CalendarEvent::factory()->for($theirs)->create(['title' => 'Deras möte']);

        $this->actingAs($this->parent)->put(route('calendars.update', $theirs), ['name' => 'Ändrat'])->assertNotFound();
        $this->actingAs($this->parent)->delete(route('calendars.destroy', $theirs))->assertNotFound();
        $this->actingAs($this->parent)->get(route('calendars.index'))->assertInertia(fn (Assert $page) => $page->has('calendars', 0));
        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->has('calendarEvents', 0));
    }
}
