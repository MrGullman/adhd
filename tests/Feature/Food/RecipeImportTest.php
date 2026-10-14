<?php

namespace Tests\Feature\Food;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecipeImportTest extends TestCase
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

    public function test_a_recipe_from_a_link_fills_in_a_new_recipe_to_check()
    {
        Http::fake(['8.8.8.8/*' => Http::response((string) file_get_contents(base_path('tests/Fixtures/recipes/tacogryta.html')))]);

        $this->actingAs($this->parent)
            ->post(route('recipes.import.store'), ['url' => 'https://8.8.8.8/recept/tacogryta'])
            ->assertRedirect(route('recipes.create'));

        $mince = Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->where('name', 'Köttfärs')->value('id');

        $this->actingAs($this->parent)
            ->get(route('recipes.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('food/recipes/Create')
                ->where('draft.name', 'Tacogryta med & bönor')
                ->where('draft.portions', 4)
                ->where('draft.timeMinutes', 45)
                ->where('draft.sourceUrl', 'https://8.8.8.8/recept/tacogryta')
                ->where('draft.instructions', "Grytan:\n1. Bryn färsen och löken.\n2. Rör ner kryddan, tomaterna och grädden.\n3. Låt puttra i 20 minuter.")
                ->has('draft.ingredients', 7)
                ->where('draft.ingredients.0.ingredientId', (int) $mince)
                ->where('draft.ingredients.0.quantity', 500)
                ->where('draft.ingredients.0.unit', 'g')
                ->where('draft.ingredients.1.name', 'Gul lök')
                ->where('draft.ingredients.1.note', 'finhackad')
                ->where('draft.ingredients.4.quantity', 1.5)
                ->where('draft.ingredients.4.unit', 'dl')
                ->where('draft.ingredients.5.name', 'Salt')
                ->where('draft.ingredients.6.name', 'Peppar'));

        // The site's photo is never fetched.
        Http::assertSentCount(1);
    }

    public function test_the_saved_recipe_keeps_its_source()
    {
        $this->actingAs($this->parent)
            ->post(route('recipes.store'), [
                'name' => 'Tacogryta',
                'portions' => 4,
                'source_url' => 'https://8.8.8.8/recept/tacogryta',
                'ingredients' => [['name' => 'Köttfärs', 'quantity' => 500, 'unit' => 'g']],
            ])
            ->assertRedirect();

        $recipe = Recipe::query()->withoutGlobalScopes()->sole();

        $this->actingAs($this->parent)
            ->get(route('recipes.show', $recipe))
            ->assertInertia(fn (Assert $page) => $page->where('recipe.sourceUrl', 'https://8.8.8.8/recept/tacogryta'));
    }

    public function test_a_page_without_a_recipe_says_so()
    {
        Http::fake(['8.8.8.8/*' => Http::response('<html><body><h1>Nyheter</h1></body></html>')]);

        $this->actingAs($this->parent)
            ->from(route('recipes.import'))
            ->post(route('recipes.import.store'), ['url' => 'https://8.8.8.8/nyheter'])
            ->assertRedirect(route('recipes.import'))
            ->assertSessionHasErrors('url');
    }

    public function test_links_to_the_servers_own_network_are_refused()
    {
        Http::fake();

        foreach (['http://8.8.8.8/recept', 'https://127.0.0.1/recept', 'https://10.0.0.5/recept', 'https://localhost/recept'] as $url) {
            $this->actingAs($this->parent)
                ->post(route('recipes.import.store'), ['url' => $url])
                ->assertSessionHasErrors('url');
        }

        Http::assertNothingSent();
    }

    public function test_only_those_who_change_food_import()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        Http::fake();

        $this->actingAs($viewer)->get(route('recipes.import'))->assertForbidden();
        $this->actingAs($viewer)->post(route('recipes.import.store'), ['url' => 'https://8.8.8.8/recept'])->assertForbidden();

        Http::assertNothingSent();
    }
}
