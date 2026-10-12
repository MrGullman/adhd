<?php

namespace Tests\Feature\Food;

use App\Enums\FamilyRole;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Models\Family;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
    }

    private function basic(string $name): Ingredient
    {
        return Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->where('name', $name)->firstOrFail();
    }

    private function tacos(): Recipe
    {
        return Recipe::factory()->for($this->family)->withIngredients([
            'Köttfärs' => [500, 'g'],
            'Tortillabröd' => [8, 'st'],
            'Tacokrydda' => [1, 'påse'],
            'Salt' => [null, null],
        ])->create(['name' => 'Tacos']);
    }

    public function test_there_is_a_basic_list_of_ingredients_from_the_start()
    {
        $this->assertGreaterThan(80, Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->count());
        $this->assertSame('dairy', $this->basic('Mjölk')->category->value);
    }

    public function test_an_editor_saves_a_recipe_and_new_groceries_become_the_familys_own()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->post(route('recipes.store'), [
                'name' => 'Pannkakor',
                'portions' => 4,
                'time_minutes' => 30,
                'instructions' => "Vispa ihop.\nStek.",
                'ingredients' => [
                    ['ingredient_id' => $this->basic('Mjöl')->id, 'quantity' => 2.5, 'unit' => 'dl'],
                    ['name' => 'mjölk', 'quantity' => 6, 'unit' => 'dl'],
                    ['name' => 'Lingonsylt från mormor', 'quantity' => null, 'unit' => null],
                ],
            ])
            ->assertRedirect();

        $recipe = Recipe::query()->withoutGlobalScopes()->where('name', 'Pannkakor')->firstOrFail();
        $lines = $recipe->ingredients()->get();

        $this->assertSame($this->basic('Mjöl')->id, $lines[0]->ingredient_id);
        $this->assertSame($this->basic('Mjölk')->id, $lines[1]->ingredient_id, '"mjölk" is the basic list\'s Mjölk');
        $this->assertSame('2.50', $lines[0]->quantity);

        $own = Ingredient::query()->withoutGlobalScopes()->findOrFail($lines[2]->ingredient_id);
        $this->assertSame($this->family->id, $own->family_id);
        $this->assertSame('Lingonsylt från mormor', $own->name);
    }

    public function test_a_viewer_sees_recipes_but_cannot_change_food()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $recipe = $this->tacos();

        $this->actingAs($viewer)->get(route('recipes.show', $recipe))->assertOk();
        $this->actingAs($viewer)->post(route('recipes.store'), ['name' => 'Soppa', 'portions' => 4])->assertForbidden();
        $this->actingAs($viewer)->post(route('pantry.store'), ['name' => 'Mjölk'])->assertForbidden();
    }

    public function test_the_pantry_keeps_one_row_per_grocery_and_marks_staples()
    {
        $this->actingAs($this->parent)->post(route('pantry.store'), ['name' => 'Mjölk', 'quantity' => 1, 'unit' => 'l']);
        $this->actingAs($this->parent)->post(route('pantry.store'), ['ingredient_id' => $this->basic('Mjölk')->id, 'quantity' => 2, 'unit' => 'l']);
        $this->actingAs($this->parent)->post(route('pantry.store'), ['name' => 'Salt', 'always_home' => true]);

        $this->assertSame(2, PantryItem::query()->withoutGlobalScopes()->where('family_id', $this->family->id)->count());
        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Mjölk')->id, 'quantity' => 2]);
        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Salt')->id, 'always_home' => true]);

        $this->actingAs($this->parent)
            ->get(route('pantry.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('food/Pantry')
                ->where('sections.0.label', 'Mejeri')
                ->where('sections.0.items.0.name', 'Mjölk'));
    }

    public function test_suggestions_show_what_can_be_cooked_and_what_misses_one_or_two_things()
    {
        $this->tacos();
        Recipe::factory()->for($this->family)->withIngredients(['Pasta' => [400, 'g'], 'Krossade tomater' => [1, 'förp'], 'Lök' => [1, 'st']])->create(['name' => 'Pastasås']);
        Recipe::factory()->for($this->family)->withIngredients(['Laxfilé' => [400, 'g'], 'Potatis' => [800, 'g'], 'Citron' => [1, 'st']])->create(['name' => 'Lax']);

        foreach (['Köttfärs', 'Tortillabröd', 'Tacokrydda', 'Pasta'] as $name) {
            PantryItem::factory()->for($this->family)->of($name)->create();
        }
        // A staple counts as at home.
        PantryItem::factory()->for($this->family)->of('Salt')->create(['always_home' => true]);

        $this->actingAs($this->parent)
            ->get(route('meal-suggestions'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('food/Suggestions')
                ->where('canCook.0.name', 'Tacos')
                ->has('canCook', 1)
                ->where('missingFew.0.name', 'Pastasås')
                ->where('missingFew.0.missing', ['Krossade tomater', 'Lök'])
                ->has('missingFew', 1));
    }

    public function test_the_recipe_shows_what_is_at_home()
    {
        $recipe = $this->tacos();
        PantryItem::factory()->for($this->family)->of('Köttfärs')->create();

        $this->actingAs($this->parent)
            ->get(route('recipes.show', $recipe))
            ->assertInertia(fn (Assert $page) => $page
                ->where('recipe.ingredients.0.name', 'Köttfärs')
                ->where('recipe.ingredients.0.atHome', true)
                ->where('recipe.ingredients.1.atHome', false));
    }

    public function test_the_autocomplete_shows_the_basic_list_and_the_familys_own_only()
    {
        $own = new Ingredient(['name' => 'Mjölk från gården', 'category' => 'dairy']);
        $own->family_id = $this->family->id;
        $own->save();
        $theirs = new Ingredient(['name' => 'Mjölk hemlig', 'category' => 'dairy']);
        $theirs->family_id = Family::factory()->create()->id;
        $theirs->save();

        $names = collect($this->actingAs($this->parent)->getJson(route('ingredients.index', ['sok' => 'mjölk']))->json('ingredients'))->pluck('name');

        $this->assertContains('Mjölk', $names);
        $this->assertContains('Mjölk från gården', $names);
        $this->assertNotContains('Mjölk hemlig', $names);
    }

    public function test_another_familys_food_is_never_reached()
    {
        $other = Family::factory()->create();
        $recipe = Recipe::factory()->for($other)->create(['name' => 'Deras recept']);
        $item = PantryItem::factory()->for($other)->of('Mjölk')->create();
        $theirs = new Ingredient(['name' => 'Deras vara', 'category' => 'other']);
        $theirs->family_id = $other->id;
        $theirs->save();

        $this->actingAs($this->parent)->get(route('recipes.show', $recipe->id))->assertNotFound();
        $this->actingAs($this->parent)->delete(route('pantry.destroy', $item->id))->assertNotFound();
        $this->actingAs($this->parent)
            ->post(route('recipes.store'), ['name' => 'Smyg', 'portions' => 2, 'ingredients' => [['ingredient_id' => $theirs->id]]])
            ->assertSessionHasErrors('ingredients.0.ingredient_id');

        $this->assertDatabaseHas('pantry_items', ['id' => $item->id]);
    }

    public function test_the_admin_portal_keeps_up_the_basic_list_only()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $own = new Ingredient(['name' => 'Familjens vara', 'category' => 'other']);
        $own->family_id = $this->family->id;
        $own->save();

        $this->actingAs($admin);

        Livewire::test(ListIngredients::class)
            ->searchTable('Mjölk')
            ->assertCanSeeTableRecords([$this->basic('Mjölk')])
            ->assertCanNotSeeTableRecords([$own])
            ->assertActionVisible('create');
    }
}
