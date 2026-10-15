<?php

namespace Tests\Feature\Display;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\MealPlanEntry;
use App\Models\Routine;
use App\Models\SchoolMenu;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DisplayTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 13 October 2026, 07:15 in Sweden.
        $this->travelTo('2026-10-13 05:15:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Mamma']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    /**
     * @return array{0: Device, 1: string}
     */
    private function display(): array
    {
        return Device::issue(DeviceType::Display, 'Köket', null, $this->family);
    }

    private function widget(Device $device, string $type, array $attributes = []): DisplayWidget
    {
        return DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => $type, ...$attributes]);
    }

    public function test_a_parent_creates_a_display_and_gets_its_link_once()
    {
        $response = $this->actingAs($this->parent)->post(route('displays.store'), ['name' => 'Köket']);

        $device = Device::query()->withoutGlobalScopes()->where('type', 'display')->firstOrFail();
        $response->assertRedirect(route('displays.edit', $device));

        $link = session(SessionKey::FLASH_DATA)['displayLink'];
        $this->post(route('logout'));

        // No login: the link is enough.
        $this->get($link)
            ->assertOk()
            ->assertCookie(Device::COOKIE)
            ->assertInertia(fn (Assert $page) => $page
                ->component('display/Index')
                ->where('density', 'medium')
                ->where('date', '2026-10-13')
                ->where('dayPart.value', 'morning'),
            );
    }

    public function test_only_display_links_open_the_display()
    {
        [, $familyToken] = Device::issue(DeviceType::Family, 'Läsplattan', null, $this->family);

        $this->get(route('display.show', str_repeat('a', 64)))->assertNotFound();
        $this->get(route('display.show', $familyToken))->assertNotFound();
    }

    public function test_a_new_link_or_removing_the_display_revokes_the_old_link()
    {
        [$device, $token] = $this->display();

        $this->actingAs($this->parent)->post(route('displays.link', $device));
        $newLink = session(SessionKey::FLASH_DATA)['displayLink'];

        $this->get(route('display.show', $token))->assertNotFound();
        $this->get($newLink)->assertOk();

        $this->actingAs($this->parent)->delete(route('devices.destroy', $device));
        $this->get($newLink)->assertNotFound();
    }

    public function test_only_parents_set_up_displays()
    {
        [$device] = $this->display();
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)->post(route('displays.store'), ['name' => 'Hallen'])->assertForbidden();
        $this->actingAs($editor)->get(route('displays.edit', $device))->assertForbidden();
        $this->actingAs($editor)
            ->post(route('displays.widgets.store', $device), ['type' => 'shopping_list'])
            ->assertForbidden();
    }

    public function test_a_parent_adds_and_arranges_widgets()
    {
        [$device] = $this->display();

        $this->actingAs($this->parent)
            ->post(route('displays.widgets.store', $device), ['type' => 'child'])
            ->assertSessionHasErrors('child_id');

        $this->actingAs($this->parent)->post(route('displays.widgets.store', $device), ['type' => 'child', 'child_id' => $this->child->id]);
        $this->actingAs($this->parent)->post(route('displays.widgets.store', $device), ['type' => 'shopping_list']);

        [$child, $shopping] = DisplayWidget::query()->withoutGlobalScopes()->orderBy('id')->get()->all();
        $this->assertSame(['child_id' => $this->child->id], $child->settings);
        $this->assertSame([4, 0], [$shopping->x, $shopping->y], 'added in the first free spot');

        $this->actingAs($this->parent)
            ->put(route('displays.layout', $device), ['widgets' => [
                ['id' => $child->id, 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 6],
                ['id' => $shopping->id, 'x' => 9, 'y' => 0, 'w' => 3, 'h' => 4],
            ]])
            ->assertRedirect(route('displays.edit', $device));

        $this->assertSame([9, 0], [$shopping->fresh()?->x, $shopping->fresh()?->y]);

        $this->actingAs($this->parent)
            ->put(route('displays.layout', $device), ['widgets' => [['id' => $shopping->id, 'x' => 10, 'y' => 0, 'w' => 3, 'h' => 4]]])
            ->assertSessionHasErrors('widgets.0');
    }

    public function test_the_child_widget_shows_the_routine_step_with_the_time_left()
    {
        [$device, $token] = $this->display();
        $this->widget($device, 'child', ['settings' => ['child_id' => $this->child->id]]);
        $routine = Routine::factory()->for($this->family)->withSteps([['Gå upp'], ['Borsta tänderna']])->forChild($this->child)->create();

        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $routine->steps()->firstOrFail()]));
        $this->post(route('logout'));

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.type', 'child')
                ->where('widgets.0.data.child.name', 'Alva')
                ->where('widgets.0.data.routine.step.label', 'Borsta tänderna')
                ->where('widgets.0.data.routine.step.number', 2)
                ->where('widgets.0.data.routine.minutesLeft', 30)
                ->where('widgets.0.data.routine.doneBy', '07:45'),
            );
    }

    public function test_the_display_never_shows_the_adults_private_events()
    {
        [$device, $token] = $this->display();
        $this->widget($device, 'activities');
        Activity::factory()->for($this->family)->concerning($this->parent)->create(['name' => 'Möte på jobbet', 'date' => '2026-10-13', 'visible_to_children' => false]);
        Activity::factory()->for($this->family)->concerning($this->child)->create(['name' => 'Simskola', 'date' => '2026-10-13', 'visible_to_children' => false]);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->has('widgets.0.data.activities', 1)
                ->where('widgets.0.data.activities.0.name', 'Simskola'),
            );
    }

    public function test_tasks_and_the_shopping_list_are_shown()
    {
        [$device, $token] = $this->display();
        $this->widget($device, 'tasks', ['settings' => ['member_id' => $this->child->id]]);
        $this->widget($device, 'shopping_list', ['x' => 6]);
        Task::factory()->for($this->family)->create(['title' => 'Mata katten', 'date' => '2026-10-13', 'assigned_to' => $this->child->id]);
        Task::factory()->for($this->family)->create(['title' => 'Betala räkningar', 'date' => '2026-10-13', 'assigned_to' => $this->parent->id]);
        ListItem::factory()
            ->for(FamilyList::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->firstOrFail(), 'list')
            ->create(['name' => 'Mjölk', 'added_by' => $this->child->id]);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->has('widgets.0.data.tasks', 1)
                ->where('widgets.0.data.tasks.0.title', 'Mata katten')
                ->where('widgets.1.data.items.0.name', 'Mjölk')
                ->where('widgets.1.data.items.0.addedBy', 'Alva'),
            );
    }

    public function test_a_parent_adds_a_week_grid_with_chosen_members_and_rows()
    {
        [$device] = $this->display();
        $stranger = User::factory()->inFamily(Family::factory()->create())->create();

        $this->actingAs($this->parent)
            ->post(route('displays.widgets.store', $device), ['type' => 'week_grid', 'member_ids' => [$stranger->id]])
            ->assertSessionHasErrors('member_ids.0');

        $this->actingAs($this->parent)
            ->post(route('displays.widgets.store', $device), ['type' => 'week_grid', 'member_ids' => [$this->child->id], 'rows' => ['dinner', 'school_meals']])
            ->assertRedirect(route('displays.edit', $device));

        $widget = DisplayWidget::query()->withoutGlobalScopes()->sole();
        $this->assertSame(['member_ids' => [$this->child->id], 'rows' => ['dinner', 'school_meals']], $widget->settings);
        $this->assertSame([12, 5], [$widget->w, $widget->h]);
    }

    public function test_the_week_grid_shows_each_members_week_like_the_whiteboard()
    {
        [$device, $token] = $this->display();
        $this->widget($device, 'week_grid', ['w' => 12, 'h' => 4, 'settings' => ['member_ids' => [], 'rows' => ['dinner', 'school_meals']]]);

        Activity::factory()->for($this->family)->concerning($this->parent)->create(['name' => 'Möte på jobbet', 'date' => '2026-10-13', 'visible_to_children' => false]);
        Activity::factory()->for($this->family)->concerning($this->parent)->create(['name' => 'Jobbar natt', 'date' => '2026-10-15', 'time' => '21:20', 'visible_to_children' => true]);
        Activity::factory()->for($this->family)->weekly(2)->concerning($this->child)->create(['name' => 'Simskola', 'time' => '17:00']);
        Activity::factory()->for($this->family)->create(['name' => 'Utflykt', 'date' => '2026-10-18', 'visible_to_children' => true]);
        MealPlanEntry::factory()->for($this->family)->create(['date' => '2026-10-14', 'title' => 'Tacos']);
        $menu = SchoolMenu::factory()->for($this->family)->create();
        $menu->meals()->create(['date' => '2026-10-12', 'meal_name' => 'Lunch', 'courses' => [['name' => 'Fiskgratäng', 'option' => 'Lunch 1'], ['name' => 'Linsgryta', 'option' => 'Lunch 2']], 'fetched_at' => now()]);
        $this->family->children()->updateExistingPivot($this->child->id, ['school_menu_id' => $menu->id]);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.days.0.date', '2026-10-12')
                ->where('widgets.0.data.days.1.isToday', true)
                ->where('widgets.0.data.rows.0.label', 'Mamma')
                ->has('widgets.0.data.rows.0.cells.1', 0)
                ->where('widgets.0.data.rows.0.cells.3.0.name', 'Jobbar natt')
                ->where('widgets.0.data.rows.0.cells.3.0.time', '21:20')
                ->where('widgets.0.data.rows.1.label', 'Alva')
                ->where('widgets.0.data.rows.1.cells.1.0.name', 'Simskola')
                ->where('widgets.0.data.rows.2.label', 'Alla')
                ->where('widgets.0.data.rows.2.cells.6.0.name', 'Utflykt')
                ->where('widgets.0.data.rows.3.label', 'Middag')
                ->where('widgets.0.data.rows.3.cells.2.0.name', 'Tacos')
                ->where('widgets.0.data.rows.4.label', 'Skolmat')
                ->where('widgets.0.data.rows.4.cells.0.0.name', 'Fiskgratäng')
                ->where('widgets.0.data.rows.4.cells.0.1.name', 'Linsgryta'),
            );
    }

    public function test_the_week_grid_turns_to_next_week_on_sunday_evening()
    {
        [$device, $token] = $this->display();
        $this->widget($device, 'week_grid', ['w' => 12, 'h' => 4, 'settings' => ['member_ids' => [$this->child->id], 'rows' => []]]);

        $this->travelTo('2026-10-18 18:00:00');

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.days.0.date', '2026-10-19')
                ->has('widgets.0.data.rows', 1)
                ->where('widgets.0.data.rows.0.label', 'Alva'),
            );
    }

    public function test_a_widget_is_never_added_on_top_of_another()
    {
        [$device] = $this->display();
        // Everything but a strip at the bottom is taken.
        $this->widget($device, 'week', ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 5]);

        $this->actingAs($this->parent)->post(route('displays.widgets.store', $device), ['type' => 'shopping_list'])->assertRedirect();
        $shopping = DisplayWidget::query()->withoutGlobalScopes()->where('type', 'shopping_list')->sole();
        $this->assertSame([0, 5, 3, 1], [$shopping->x, $shopping->y, $shopping->w, $shopping->h], 'made smaller to fit');

        $this->widget($device, 'activities', ['x' => 3, 'y' => 5, 'w' => 9, 'h' => 1]);
        $this->actingAs($this->parent)->post(route('displays.widgets.store', $device), ['type' => 'tasks'])->assertRedirect();
        $this->assertSame(0, DisplayWidget::query()->withoutGlobalScopes()->where('type', 'tasks')->count(), 'a full display takes no more');
    }

    public function test_the_editor_moves_widgets_that_are_on_top_of_each_other()
    {
        [$device] = $this->display();
        $first = $this->widget($device, 'child', ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 4, 'settings' => ['child_id' => $this->child->id]]);
        $onTop = $this->widget($device, 'points', ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 3]);

        $this->actingAs($this->parent)->get(route('displays.edit', $device))->assertOk();

        $this->assertSame([0, 0], [$first->fresh()?->x, $first->fresh()?->y]);
        $this->assertSame([3, 0, 3, 3], [$onTop->fresh()?->x, $onTop->fresh()?->y, $onTop->fresh()?->w, $onTop->fresh()?->h]);
    }

    public function test_the_display_is_dark_in_the_evening()
    {
        [, $token] = $this->display();

        $this->get(route('display.show', $token))->assertInertia(fn (Assert $page) => $page->where('evening', false));

        $this->travelTo('2026-10-13 18:30:00');
        $this->get(route('display.show', $token))->assertInertia(fn (Assert $page) => $page->where('evening', true));
    }

    public function test_a_parent_cannot_change_another_familys_display()
    {
        [, $token] = Device::issue(DeviceType::Display, 'Deras kök', null, Family::factory()->create());
        $other = Device::findByToken($token);

        $this->actingAs($this->parent)->get(route('displays.edit', $other->id))->assertNotFound();
        $this->actingAs($this->parent)
            ->post(route('displays.widgets.store', $other->id), ['type' => 'shopping_list'])
            ->assertNotFound();

        $this->assertDatabaseCount('display_widgets', 0);
    }
}
