<?php

namespace Tests\Feature\Food;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\DisplayWidget;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\Ingredient;
use App\Models\ListItem;
use App\Models\MealPlanEntry;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private FamilyList $shopping;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 14 October 2026, 10:00 in Sweden. The week starts Monday 12 October.
        $this->travelTo('2026-10-14 08:00:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->shopping = FamilyList::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->firstOrFail();
    }

    private function basic(string $name): Ingredient
    {
        return Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->where('name', $name)->firstOrFail();
    }

    private function plan(Recipe $recipe, string $date, int $portions): MealPlanEntry
    {
        return MealPlanEntry::factory()->for($this->family)->create(['recipe_id' => $recipe->id, 'title' => null, 'date' => $date, 'portions' => $portions]);
    }

    /**
     * @return array<string, array{quantity: string|null, unit: string|null, for_meals: string|null}>
     */
    private function generated(): array
    {
        return ListItem::query()->withoutGlobalScopes()
            ->where('list_id', $this->shopping->id)
            ->whereNotNull('meal_plan_week')
            ->get()
            ->mapWithKeys(fn (ListItem $item) => [$item->name => ['quantity' => $item->quantity, 'unit' => $item->unit, 'for_meals' => $item->for_meals]])
            ->all();
    }

    private function refresh(): void
    {
        $this->actingAs($this->parent)->post(route('meal-plan.shopping-list'))->assertRedirect();
    }

    public function test_the_week_is_added_up_scaled_to_portions_and_converted()
    {
        $tacos = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [400, 'g'], 'Mjölk' => [3, 'dl']])->create(['name' => 'Tacos', 'portions' => 4]);
        $lasagne = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [0.5, 'kg'], 'Mjölk' => [2, 'msk']])->create(['name' => 'Lasagne', 'portions' => 4]);
        $this->plan($tacos, '2026-10-14', 6);
        $this->plan($lasagne, '2026-10-16', 4);

        $this->refresh();

        $items = $this->generated();
        // 400 g × 6/4 = 600 g, plus 500 g.
        $this->assertSame(['quantity' => '1.10', 'unit' => 'kg', 'for_meals' => 'Tacos, Lasagne'], $items['Köttfärs']);
        // 3 dl × 6/4 = 450 ml, plus 30 ml.
        $this->assertSame(['quantity' => '4.80', 'unit' => 'dl', 'for_meals' => 'Tacos, Lasagne'], $items['Mjölk']);
    }

    public function test_what_is_at_home_is_taken_off()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients([
            'Köttfärs' => [600, 'g'],
            'Lök' => [2, 'st'],
            'Salt' => [1, 'tsk'],
            'Tortillabröd' => [8, 'st'],
            'Krossade tomater' => [1, 'förp'],
        ])->create(['portions' => 4]);
        $this->plan($recipe, '2026-10-15', 4);

        PantryItem::factory()->for($this->family)->of('Köttfärs')->create(['quantity' => 500, 'unit' => 'g']);
        PantryItem::factory()->for($this->family)->of('Lök')->create();
        PantryItem::factory()->for($this->family)->of('Salt')->create(['always_home' => true]);
        // Grams cannot be compared with pieces: still to buy.
        PantryItem::factory()->for($this->family)->of('Tortillabröd')->create(['quantity' => 200, 'unit' => 'g']);

        $this->refresh();

        $items = $this->generated();
        // In store order: bread, meat, pantry.
        $this->assertSame(['Tortillabröd', 'Köttfärs', 'Krossade tomater'], array_keys($items));
        $this->assertSame(['100.00', 'g'], [$items['Köttfärs']['quantity'], $items['Köttfärs']['unit']]);
    }

    public function test_one_alternative_at_home_is_enough()
    {
        $this->actingAs($this->parent)->post(route('recipes.store'), [
            'name' => 'Stekt ägg', 'portions' => 2,
            'ingredients' => [['name' => 'Ägg', 'quantity' => 4, 'unit' => 'st'], ['name' => 'smör el margarin', 'quantity' => 10, 'unit' => 'g']],
        ]);
        $this->plan(Recipe::query()->withoutGlobalScopes()->where('name', 'Stekt ägg')->firstOrFail(), '2026-10-15', 2);
        PantryItem::factory()->for($this->family)->of('Margarin')->create();

        $this->refresh();

        $this->assertSame(['Ägg'], array_keys($this->generated()));
    }

    public function test_updating_again_keeps_the_familys_own_and_what_is_ticked_off()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g'], 'Lök' => [1, 'st']])->create(['name' => 'Tacos']);
        $entry = $this->plan($recipe, '2026-10-15', 4);
        ListItem::factory()->for($this->shopping, 'list')->create(['name' => 'Glass']);

        $this->refresh();
        $onion = ListItem::query()->withoutGlobalScopes()->where('name', 'Lök')->firstOrFail();
        $this->actingAs($this->parent)->post(route('list-items.completion.store', [$this->shopping, $onion]));

        // Bought, so it is at home now.
        $this->assertDatabaseHas('pantry_items', ['family_id' => $this->family->id, 'ingredient_id' => $this->basic('Lök')->id]);

        $this->actingAs($this->parent)->delete(route('meal-plan.destroy', $entry));
        $this->refresh();

        $names = ListItem::query()->withoutGlobalScopes()->where('list_id', $this->shopping->id)->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Glass', 'Lök'], $names, 'Köttfärs went with the meal, Glass and the ticked-off Lök stay');
    }

    public function test_meals_that_have_been_are_not_shopped_for()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g']])->create();
        $this->plan($recipe, '2026-10-13', 4);

        $this->refresh();

        $this->assertSame([], $this->generated());
    }

    public function test_the_shopping_list_goes_through_the_store_section_by_section()
    {
        foreach (['Mjölk', 'Banan', 'Köttfärs', 'Glass'] as $name) {
            $this->actingAs($this->parent)->post(route('list-items.store', $this->shopping), ['name' => $name]);
        }

        $this->actingAs($this->parent)
            ->get(route('lists.show', $this->shopping))
            ->assertInertia(fn (Assert $page) => $page
                ->where('items.0.name', 'Banan')
                ->where('items.0.section', 'Frukt & grönt')
                ->where('items.1.name', 'Mjölk')
                ->where('items.2.name', 'Köttfärs')
                ->where('items.3.name', 'Glass')
                ->where('items.3.section', 'Frys'));
    }

    public function test_an_editor_plans_dinners_and_a_viewer_cannot()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $recipe = Recipe::factory()->for($this->family)->create(['name' => 'Tacos', 'portions' => 5]);
        $theirs = Recipe::factory()->create();

        $this->actingAs($editor)->post(route('meal-plan.store'), ['date' => '2026-10-15', 'recipe_id' => $recipe->id]);
        $this->actingAs($editor)->post(route('meal-plan.store'), ['date' => '2026-10-16', 'title' => 'Pizza ute']);

        $this->assertDatabaseHas('meal_plan_entries', ['recipe_id' => $recipe->id, 'portions' => 5]);
        $this->assertDatabaseHas('meal_plan_entries', ['title' => 'Pizza ute', 'recipe_id' => null]);

        $this->actingAs($editor)
            ->post(route('meal-plan.store'), ['date' => '2026-10-15', 'recipe_id' => $theirs->id])
            ->assertSessionHasErrors('recipe_id');
        $this->actingAs($viewer)
            ->post(route('meal-plan.store'), ['date' => '2026-10-15', 'title' => 'Soppa'])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('meal-plan.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('food/MealPlan')
                ->where('days.3.entries.0.name', 'Tacos')
                ->where('days.4.entries.0.name', 'Pizza ute')
                ->where('canEdit', false));
    }

    public function test_tonights_dinner_shows_on_idag_and_switches_after_dinner()
    {
        $child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create();
        $tacos = Recipe::factory()->for($this->family)->create(['name' => 'Tacos']);
        $this->plan($tacos, '2026-10-14', 4);
        MealPlanEntry::factory()->for($this->family)->create(['date' => '2026-10-15', 'title' => 'Fiskpinnar']);

        $this->actingAs($this->parent)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('dinner.label', 'Middag ikväll')->where('dinner.name', 'Tacos'));

        $this->travelTo('2026-10-14 17:30:00');
        $this->actingAs($child)->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page->where('dinner.label', 'Middag imorgon')->where('dinner.name', 'Fiskpinnar'));
    }

    public function test_the_home_display_shows_the_dinner()
    {
        MealPlanEntry::factory()->for($this->family)->create(['date' => '2026-10-14', 'title' => 'Pannkakor']);
        MealPlanEntry::factory()->for($this->family)->create(['date' => '2026-10-16', 'title' => 'Tacos']);
        [$device, $token] = Device::issue(DeviceType::Display, 'Köket', null, $this->family);
        DisplayWidget::factory()->for($device)->create(['family_id' => $this->family->id, 'type' => 'meal_plan']);

        $this->get(route('display.show', $token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('widgets.0.data.dinner.name', 'Pannkakor')
                ->where('widgets.0.data.coming.0.name', 'Tacos'));
    }

    public function test_another_familys_dinner_is_never_reached()
    {
        $theirs = MealPlanEntry::factory()->create(['title' => 'Deras middag']);

        $this->actingAs($this->parent)->delete(route('meal-plan.destroy', $theirs->id))->assertNotFound();
        $this->actingAs($this->parent)->put(route('meal-plan.update', $theirs->id), ['portions' => 2])->assertNotFound();

        $this->assertDatabaseHas('meal_plan_entries', ['id' => $theirs->id, 'portions' => 4]);
    }

    private function pantry(string $name, ?float $quantity, ?string $unit, bool $alwaysHome = false): PantryItem
    {
        return PantryItem::factory()->for($this->family)->create([
            'ingredient_id' => $this->basic($name)->id,
            'quantity' => $quantity,
            'unit' => $unit,
            'always_home' => $alwaysHome,
        ]);
    }

    public function test_a_cooked_meal_comes_off_the_pantry_once_and_can_be_undone()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients([
            'Köttfärs' => [500, 'g'],
            'Tortillabröd' => [8, 'st'],
            'Tacokrydda' => [1, 'påse'],
            'Salt' => [1, 'krm'],
        ])->create(['name' => 'Tacos', 'portions' => 4]);
        $entry = $this->plan($recipe, '2026-10-13', 8);

        $mince = $this->pantry('Köttfärs', 1.5, 'kg');
        $tortillas = $this->pantry('Tortillabröd', 20, 'st');
        $spice = $this->pantry('Tacokrydda', null, null);
        $salt = $this->pantry('Salt', null, null, alwaysHome: true);

        $this->actingAs($this->parent)->post(route('meal-plan.cook', $entry))->assertRedirect();
        $this->actingAs($this->parent)->post(route('meal-plan.cook', $entry))->assertRedirect();

        $this->assertSame(0.5, (float) $mince->fresh()?->quantity, '1,5 kg minus 2 × 500 g');
        $this->assertSame('kg', $mince->fresh()?->unit);
        $this->assertSame(4.0, (float) $tortillas->fresh()?->quantity);
        $this->assertModelExists($spice);
        $this->assertModelExists($salt);
        $this->assertNotNull($entry->fresh()?->cooked_at);

        $this->actingAs($this->parent)->delete(route('meal-plan.uncook', $entry))->assertRedirect();

        $this->assertSame(1.5, (float) $mince->fresh()?->quantity);
        $this->assertSame(20.0, (float) $tortillas->fresh()?->quantity);
        $this->assertNull($entry->fresh()?->cooked_at);
    }

    public function test_what_runs_out_is_removed_and_comes_back_on_undo()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g']])->create(['name' => 'Köttbullar', 'portions' => 4]);
        $entry = $this->plan($recipe, '2026-10-13', 4);
        $mince = $this->pantry('Köttfärs', 400, 'g');

        $this->actingAs($this->parent)->post(route('meal-plan.cook', $entry));
        $this->assertModelMissing($mince);

        $this->actingAs($this->parent)->delete(route('meal-plan.uncook', $entry));
        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Köttfärs')->id, 'quantity' => 400, 'unit' => 'g']);
    }

    public function test_only_those_who_change_food_mark_a_meal_cooked()
    {
        $recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g']])->create(['portions' => 4]);
        $entry = $this->plan($recipe, '2026-10-13', 4);
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $theirs = MealPlanEntry::factory()->create();

        $this->actingAs($viewer)->post(route('meal-plan.cook', $entry))->assertForbidden();
        $this->actingAs($this->parent)->post(route('meal-plan.cook', $theirs->id))->assertNotFound();

        $this->assertNull($entry->fresh()?->cooked_at);
    }
}
