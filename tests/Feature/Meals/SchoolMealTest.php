<?php

namespace Tests\Feature\Meals;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Jobs\FetchSchoolMealsJob;
use App\Meals\MatildaMealProvider;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\SchoolMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Made-up schools only. Matilda is never called for real: Http::fake().
 */
class SchoolMealTest extends TestCase
{
    use RefreshDatabase;

    private const SCHOOL_ID = '0123456789abcdef01234567';

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Wednesday 14 October 2026, 10:00 in Sweden.
        $this->travelTo('2026-10-14 08:00:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $days  date => [lunch 1, lunch 2]
     */
    private function fakeMatilda(array $days): void
    {
        $meals = [];

        foreach ($days as $date => [$first, $second]) {
            $meals[] = [
                'name' => 'Lunch',
                'date' => "{$date}T00:00:00",
                'holidayName' => null,
                'courses' => [
                    ['name' => $first.'  ', 'optionName' => 'Lunch 1', 'tags' => []],
                    ['name' => $second, 'optionName' => 'Lunch 2', 'tags' => []],
                ],
            ];
        }

        Http::fake([MatildaMealProvider::URL.'*' => Http::response(['distributor' => ['name' => 'Ekbackeskolan'], 'meals' => $meals])]);
    }

    private function school(?User $child = null): SchoolMenu
    {
        $menu = SchoolMenu::factory()->for($this->family)->create(['name' => 'Ekbackeskolan', 'external_id' => self::SCHOOL_ID]);

        DB::table('family_user')
            ->where('family_id', $this->family->id)
            ->where('user_id', ($child ?? $this->child)->id)
            ->update(['school_menu_id' => $menu->id]);

        return $menu;
    }

    public function test_a_matilda_link_gives_the_school_and_its_name()
    {
        $this->assertSame(
            ['id' => self::SCHOOL_ID, 'name' => 'Ekbackeskolan'],
            MatildaMealProvider::parseLink('https://menu.matildaplatform.com/meals/week/'.self::SCHOOL_ID.'_ekbackeskolan'),
        );
        $this->assertNull(MatildaMealProvider::parseLink('https://example.com/meals/week/'.self::SCHOOL_ID));
        $this->assertNull(MatildaMealProvider::parseLink(self::SCHOOL_ID));
    }

    public function test_a_parent_adds_a_school_and_it_is_fetched_in_the_background()
    {
        Queue::fake();

        $this->actingAs($this->parent)
            ->post(route('school-menus.store'), ['link' => 'https://menu.matildaplatform.com/meals/week/'.self::SCHOOL_ID.'_ekbackeskolan'])
            ->assertRedirect(route('family.show'));

        $menu = SchoolMenu::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame('Ekbackeskolan', $menu->name);
        $this->assertSame($this->family->id, $menu->family_id);
        Queue::assertPushed(FetchSchoolMealsJob::class, fn (FetchSchoolMealsJob $job) => $job->schoolMenuId === $menu->id);
        Http::assertNothingSent();
    }

    public function test_only_matilda_links_are_accepted()
    {
        $this->actingAs($this->parent)
            ->post(route('school-menus.store'), ['link' => 'https://example.com/skolmat'])
            ->assertSessionHasErrors('link');
    }

    public function test_this_and_next_week_are_fetched_and_saved()
    {
        $this->school();
        $this->fakeMatilda(['2026-10-14' => ['Fiskgratäng', 'Pasta'], '2026-10-15' => ['Tacos', 'Soppa']]);

        $this->artisan('school-meals:fetch')->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'distributorId='.self::SCHOOL_ID)
            && str_contains($request->url(), 'startDate=2026-10-12')
            && str_contains($request->url(), 'endDate=2026-10-25'));

        $courses = json_decode((string) DB::table('school_meals')->whereDate('date', '2026-10-14')->value('courses'), true);
        $this->assertSame([['name' => 'Fiskgratäng', 'option' => 'Lunch 1'], ['name' => 'Pasta', 'option' => 'Lunch 2']], $courses);
        $this->assertNotNull(DB::table('school_menus')->value('fetched_at'));
    }

    public function test_when_matilda_does_not_answer_what_was_fetched_before_is_kept()
    {
        $this->school();
        $this->fakeMatilda(['2026-10-14' => ['Fiskgratäng', 'Pasta']]);
        $this->artisan('school-meals:fetch');

        Http::fake([MatildaMealProvider::URL.'*' => Http::response('Fel', 500)]);
        $this->artisan('school-meals:fetch')->assertSuccessful();

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('lunch.courses.0.name', 'Fiskgratäng'));
    }

    public function test_the_lunch_switches_to_the_next_school_day_after_school()
    {
        $this->school();
        $this->fakeMatilda([
            '2026-10-14' => ['Fiskgratäng', 'Pasta'],
            '2026-10-15' => ['Tacos', 'Soppa'],
            '2026-10-19' => ['Köttbullar', 'Falafel'],
        ]);
        $this->artisan('school-meals:fetch');

        $this->actingAs($this->child)->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('lunch.label', 'Skollunch idag')->where('lunch.courses.0.name', 'Fiskgratäng'));

        $this->travelTo('2026-10-14 13:30:00');
        $this->actingAs($this->child)->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('lunch.label', 'Skollunch imorgon')->where('lunch.courses.0.name', 'Tacos'));

        // Friday afternoon: Monday's lunch.
        $this->travelTo('2026-10-16 14:00:00');
        $this->actingAs($this->child)->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('lunch.label', 'Skollunch måndag')->where('lunch.courses.0.name', 'Köttbullar'));
    }

    public function test_the_family_chooses_when_tomorrows_lunch_is_shown()
    {
        $this->school();
        $this->fakeMatilda(['2026-10-14' => ['Fiskgratäng', 'Pasta'], '2026-10-15' => ['Tacos', 'Soppa']]);
        $this->artisan('school-meals:fetch');

        $this->actingAs($this->parent)->put(route('school-menus.switch'), ['time' => '09:00']);

        $this->actingAs($this->child)->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('lunch.label', 'Skollunch imorgon'));
    }

    public function test_siblings_at_different_schools_see_their_own_lunch()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        $this->school();
        $other = SchoolMenu::factory()->for($this->family)->create(['name' => 'Solgläntans skola']);
        $this->actingAs($this->parent)->put(route('school-menus.assign', $sibling->id), ['school_menu_id' => $other->id]);

        DB::table('school_meals')->insert([
            ['school_menu_id' => $other->id, 'date' => '2026-10-14', 'meal_name' => 'Lunch', 'courses' => json_encode([['name' => 'Pannkakor', 'option' => null]]), 'fetched_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['school_menu_id' => SchoolMenu::query()->withoutGlobalScopes()->where('name', 'Ekbackeskolan')->value('id'), 'date' => '2026-10-14', 'meal_name' => 'Lunch', 'courses' => json_encode([['name' => 'Fiskgratäng', 'option' => null]]), 'fetched_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($this->parent)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('lunches.0.child.name', 'Alva')
                ->where('lunches.0.courses.0.name', 'Fiskgratäng')
                ->where('lunches.1.child.name', 'Bo')
                ->where('lunches.1.courses.0.name', 'Pannkakor'));
    }

    public function test_the_home_display_shows_each_childs_lunch_without_calling_matilda()
    {
        $this->school();
        DB::table('school_meals')->insert(['school_menu_id' => SchoolMenu::query()->withoutGlobalScopes()->value('id'), 'date' => '2026-10-14', 'meal_name' => 'Lunch', 'courses' => json_encode([['name' => 'Tacos', 'option' => 'Lunch 1']]), 'fetched_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        [$device, $token] = Device::issue(DeviceType::Display, 'Köket', null, $this->family);
        DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => 'school_meals']);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.label', 'Skollunch idag')
                ->has('widgets.0.data.days', 1)
                ->where('widgets.0.data.days.0.children.0.child.name', 'Alva')
                ->where('widgets.0.data.days.0.children.0.courses.0.name', 'Tacos'));

        Http::assertNothingSent();
    }

    public function test_a_wider_lunch_widget_shows_more_school_days()
    {
        $this->school();
        $menuId = SchoolMenu::query()->withoutGlobalScopes()->value('id');

        // Wednesday to the next Wednesday: no lunch at the weekend, a holiday on Monday.
        foreach (['2026-10-14' => 'Tacos', '2026-10-15' => 'Soppa', '2026-10-16' => 'Pizza', '2026-10-19' => null, '2026-10-20' => 'Lasagne', '2026-10-21' => 'Fisk'] as $date => $dish) {
            DB::table('school_meals')->insert([
                'school_menu_id' => $menuId,
                'date' => $date,
                'meal_name' => 'Lunch',
                'holiday_name' => $dish === null ? 'Studiedag' : null,
                'courses' => json_encode($dish === null ? [] : [['name' => $dish, 'option' => null]]),
                'fetched_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        [$device, $token] = Device::issue(DeviceType::Display, 'Köket', null, $this->family);
        $widget = DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => 'school_meals', 'x' => 0, 'w' => 12]);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.label', 'Skollunch')
                ->where('widgets.0.data.days', fn ($days) => collect($days)->pluck('label')->all() === ['Idag', 'Imorgon', 'Fredag', 'Måndag', 'Tisdag'])
                ->where('widgets.0.data.days.3.children.0.holiday', 'Studiedag')
                ->where('widgets.0.data.days.4.children.0.courses.0.name', 'Lasagne'));

        $widget->update(['w' => 6]);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page->has('widgets.0.data.days', 2));
    }

    public function test_a_child_cannot_be_given_another_familys_school()
    {
        $other = SchoolMenu::factory()->create();

        $this->actingAs($this->parent)
            ->put(route('school-menus.assign', $this->child->id), ['school_menu_id' => $other->id])
            ->assertSessionHasErrors('school_menu_id');

        $this->actingAs($this->parent)->delete(route('school-menus.destroy', $other->id))->assertNotFound();
        $this->assertDatabaseHas('school_menus', ['id' => $other->id]);
    }

    public function test_the_morning_fetch_is_scheduled()
    {
        $this->artisan('schedule:list')->expectsOutputToContain('school-meals:fetch')->assertSuccessful();
    }
}
