<?php

namespace Tests\Feature\Food;

use App\Enums\FamilyRole;
use App\Enums\StorageLocation;
use App\Models\Family;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTest extends TestCase
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

    private function item(string $name, array $attributes = []): PantryItem
    {
        return PantryItem::factory()->for($this->family)->create(['ingredient_id' => $this->basic($name)->id, ...$attributes]);
    }

    public function test_groceries_go_to_their_usual_place_unless_another_is_chosen()
    {
        $this->actingAs($this->parent)->post(route('pantry.store'), ['name' => 'Mjölk', 'quantity' => 1, 'unit' => 'l']);
        $this->actingAs($this->parent)->post(route('pantry.store'), ['name' => 'Köttfärs', 'quantity' => 500, 'unit' => 'g', 'location' => 'frys']);
        $this->actingAs($this->parent)->post(route('pantry.store'), ['name' => 'Salt', 'always_home' => true]);

        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Mjölk')->id, 'location' => 'kyl']);
        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Köttfärs')->id, 'location' => 'frys']);
        $this->assertDatabaseHas('pantry_items', ['ingredient_id' => $this->basic('Salt')->id, 'location' => 'skafferi']);
    }

    public function test_taking_stock_of_the_fridge_changes_amounts_moves_and_removes()
    {
        $milk = $this->item('Mjölk', ['quantity' => 2, 'unit' => 'l', 'location' => StorageLocation::Fridge]);
        $mince = $this->item('Köttfärs', ['quantity' => 500, 'unit' => 'g', 'location' => StorageLocation::Fridge]);
        $butter = $this->item('Smör', ['location' => StorageLocation::Fridge]);
        $this->item('Salt', ['location' => StorageLocation::Pantry, 'always_home' => true]);

        $this->actingAs($this->parent)
            ->get(route('inventory.show', 'kyl'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('food/Inventory')
                ->where('location.label', 'Kylskåp')
                ->has('items', 3)
                ->where('items.0.name', 'Köttfärs'));

        $this->actingAs($this->parent)->patch(route('inventory.update', $milk), ['quantity' => 0.5, 'unit' => 'l'])->assertRedirect();
        $this->actingAs($this->parent)->patch(route('inventory.update', $mince), ['location' => 'frys'])->assertRedirect();
        $this->actingAs($this->parent)->patch(route('inventory.update', $butter), ['quantity' => 0])->assertRedirect();

        $this->assertSame(0.5, (float) $milk->fresh()?->quantity);
        $this->assertSame(StorageLocation::Freezer, $mince->fresh()?->location);
        $this->assertModelMissing($butter);

        $this->actingAs($this->parent)->post(route('inventory.done', 'kyl'))->assertRedirect(route('pantry.index'));

        $this->actingAs($this->parent)
            ->get(route('pantry.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('locations.0.value', 'kyl')
                ->whereNot('locations.0.inventoriedAt', null)
                ->where('locations.1.inventoriedAt', null)
                ->where('locations.1.items.0.name', 'Köttfärs'));
    }

    public function test_viewers_and_children_cannot_take_stock()
    {
        $milk = $this->item('Mjölk', ['quantity' => 2, 'unit' => 'l', 'location' => StorageLocation::Fridge]);
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create();

        $this->actingAs($viewer)->get(route('inventory.show', 'kyl'))->assertForbidden();
        $this->actingAs($viewer)->patch(route('inventory.update', $milk), ['quantity' => 0])->assertForbidden();
        $this->actingAs($viewer)->post(route('inventory.done', 'kyl'))->assertForbidden();
        $this->actingAs($child)->patch(route('inventory.update', $milk), ['quantity' => 0])->assertRedirect(route('child.home'));

        $this->assertModelExists($milk);
    }

    public function test_another_familys_pantry_is_never_reached()
    {
        $theirs = PantryItem::factory()->create(['ingredient_id' => $this->basic('Mjölk')->id, 'quantity' => 1, 'unit' => 'l']);

        $this->actingAs($this->parent)->patch(route('inventory.update', $theirs->id), ['quantity' => 0])->assertNotFound();
        $this->actingAs($this->parent)->get(route('inventory.show', 'kyl'))->assertInertia(fn (Assert $page) => $page->has('items', 0));

        $this->assertModelExists($theirs);
    }
}
