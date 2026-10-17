<?php

namespace Tests\Feature\Food;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Laga: a recipe step by step for adults and children, with timers from
 * the steps and Lagad at the end for adults.
 */
class CookTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 14 October 2026, 10:00 in Sweden. The week starts Monday 12 October.
        $this->travelTo('2026-10-14 08:00:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
        $this->recipe = Recipe::factory()->for($this->family)->withIngredients(['Köttfärs' => [500, 'g']])->create([
            'name' => 'Köttbullar',
            'portions' => 4,
            'instructions' => "1. Blanda köttfärsen.\n2. Stek i 10 minuter.",
        ]);
    }

    private function plan(Recipe $recipe, string $date, int $portions = 4): MealPlanEntry
    {
        return MealPlanEntry::factory()->for($this->family)->create(['recipe_id' => $recipe->id, 'title' => null, 'date' => $date, 'meal' => 'dinner', 'portions' => $portions]);
    }

    public function test_an_adult_cooks_step_by_step_for_the_meals_portions_and_marks_it_cooked()
    {
        $entry = $this->plan($this->recipe, '2026-10-14', 6);

        $this->actingAs($this->parent)
            ->get(route('recipes.cook', [$this->recipe, 'maltid' => $entry->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('cook/Recipe')
                ->where('recipe.portions', 6)
                ->where('recipe.ingredients.0.quantity', 750)
                ->where('recipe.steps.0.text', 'Blanda köttfärsen.')
                ->where('recipe.steps.0.ingredientIds', [$this->recipe->ingredients()->value('id')])
                ->where('recipe.steps.1.ingredientIds', [])
                ->where('recipe.steps.1.timers.0.seconds', 600)
                ->where('entry.canMarkCooked', true)
                ->where('backHref', route('recipes.show', $this->recipe)));

        $this->actingAs($this->parent)->post(route('meal-plan.cook', $entry))->assertRedirect();
        $this->assertNotNull($entry->fresh()?->cooked_at);
    }

    public function test_an_adult_who_only_looks_cooks_but_never_marks_it_cooked()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $entry = $this->plan($this->recipe, '2026-10-14');

        $this->actingAs($viewer)
            ->get(route('recipes.cook', [$this->recipe, 'maltid' => $entry->id]))
            ->assertInertia(fn (Assert $page) => $page->where('entry.canMarkCooked', false));
    }

    public function test_a_child_cooks_the_weeks_dinners_and_every_recipe_only_when_a_parent_says_so()
    {
        $other = Recipe::factory()->for($this->family)->create(['name' => 'Pannkakor']);
        $entry = $this->plan($this->recipe, '2026-10-14');
        $this->plan($other, '2026-10-20');

        $this->actingAs($this->child)
            ->get(route('recipes.cook', [$this->recipe, 'maltid' => $entry->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('entry.canMarkCooked', false)
                ->where('backHref', route('child.recipes')));
        $this->actingAs($this->child)->get(route('recipes.cook', $other))->assertForbidden();
        $this->actingAs($this->child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page
            ->where('dinner.name', 'Köttbullar')
            ->where('dinner.canCook', true));
        $this->actingAs($this->child)->get(route('child.recipes'))->assertInertia(fn (Assert $page) => $page
            ->component('child/Recipes')
            ->has('week', 1)
            ->where('week.0.name', 'Köttbullar')
            ->where('recipes', []));

        // The child may not mark it cooked.
        $this->actingAs($this->child)->post(route('meal-plan.cook', $entry))->assertRedirect();
        $this->assertNull($entry->fresh()?->cooked_at);

        $this->actingAs($this->parent)->put(route('children.view.update', $this->child->id), [
            'child_view' => 'youth',
            'child_pictures' => true,
            'rewards_enabled' => true,
            'all_recipes' => true,
        ])->assertRedirect();

        $this->actingAs($this->child)->get(route('recipes.cook', $other))->assertOk();
        $this->actingAs($this->child)->get(route('child.recipes'))->assertInertia(fn (Assert $page) => $page
            ->where('recipes.0.name', 'Köttbullar')
            ->where('recipes.1.name', 'Pannkakor'));
    }

    public function test_the_meal_must_be_this_recipe_in_this_family()
    {
        $other = Recipe::factory()->for($this->family)->create();
        $entry = $this->plan($other, '2026-10-14');

        $this->actingAs($this->parent)
            ->get(route('recipes.cook', [$this->recipe, 'maltid' => $entry->id]))
            ->assertSessionHasErrors('maltid');
        $this->actingAs($this->parent)
            ->get(route('recipes.cook', [$this->recipe, 'portioner' => 50]))
            ->assertSessionHasErrors('portioner');
    }

    public function test_another_familys_recipes_are_never_reached()
    {
        $theirs = Recipe::factory()->for(Family::factory())->create();
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($this->parent)->get(route('recipes.cook', $theirs))->assertNotFound();
        $this->actingAs($otherChild)->get(route('recipes.cook', $this->recipe))->assertNotFound();
    }
}
