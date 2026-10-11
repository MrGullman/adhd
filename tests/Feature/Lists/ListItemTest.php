<?php

namespace Tests\Feature\Lists;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListItemTest extends TestCase
{
    use RefreshDatabase;

    private function shoppingList(Family $family): FamilyList
    {
        return FamilyList::query()->withoutGlobalScopes()
            ->where('family_id', $family->id)
            ->where('type', 'shopping')
            ->firstOrFail();
    }

    public function test_adults_see_who_added_an_item()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create(['name' => 'Kim']);
        $list = $this->shoppingList($family);

        $this->actingAs($parent)
            ->from(route('lists.show', $list))
            ->post(route('list-items.store', $list), ['name' => 'Mjölk'])
            ->assertRedirect(route('lists.show', $list));

        $this->actingAs($parent)
            ->get(route('lists.show', $list))
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/Show')
                ->where('items.0.name', 'Mjölk')
                ->where('items.0.addedBy', 'Kim')
                ->where('can.addItems', true),
            );
    }

    public function test_a_child_adds_to_the_shopping_list()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $list = $this->shoppingList($family);

        $this->actingAs($child)
            ->from(route('child.shopping'))
            ->post(route('list-items.store', $list), ['name' => 'Glass'])
            ->assertRedirect(route('child.shopping'));

        $this->assertDatabaseHas('list_items', [
            'family_id' => $family->id,
            'list_id' => $list->id,
            'name' => 'Glass',
            'added_by' => $child->id,
        ]);
    }

    public function test_a_child_cannot_add_to_other_lists()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $list = FamilyList::factory()->for($family)->create();

        $this->actingAs($child)
            ->post(route('list-items.store', $list), ['name' => 'Glass'])
            ->assertForbidden();

        $this->assertDatabaseMissing('list_items', ['name' => 'Glass']);
    }

    public function test_a_viewer_cannot_add_or_tick_off_items()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $list = $this->shoppingList($family);
        $item = ListItem::factory()->for($list, 'list')->create();

        $this->actingAs($viewer)
            ->post(route('list-items.store', $list), ['name' => 'Bröd'])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('list-items.completion.store', [$list, $item]))
            ->assertForbidden();

        $this->assertNull($item->fresh()?->completed_at);
    }

    public function test_ticking_off_records_who_did_it_and_can_be_undone()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $list = $this->shoppingList($family);
        $item = ListItem::factory()->for($list, 'list')->create();

        $this->actingAs($child)->post(route('list-items.completion.store', [$list, $item]));

        $item->refresh();
        $this->assertNotNull($item->completed_at);
        $this->assertSame($child->id, $item->completed_by);

        $this->actingAs($child)->delete(route('list-items.completion.destroy', [$list, $item]));

        $item->refresh();
        $this->assertNull($item->completed_at);
        $this->assertNull($item->completed_by);
    }

    public function test_a_child_cannot_remove_items()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $list = $this->shoppingList($family);
        $item = ListItem::factory()->for($list, 'list')->create();

        $this->actingAs($child)
            ->delete(route('list-items.destroy', [$list, $item]))
            ->assertRedirect(route('child.home'));

        $this->assertDatabaseHas('list_items', ['id' => $item->id]);
    }

    public function test_an_editor_renames_and_removes_items()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();
        $list = $this->shoppingList($family);
        $item = ListItem::factory()->for($list, 'list')->create(['name' => 'Mjök']);

        $this->actingAs($editor)->put(route('list-items.update', [$list, $item]), ['name' => 'Mjölk']);
        $this->assertSame('Mjölk', $item->fresh()?->name);

        $this->actingAs($editor)->delete(route('list-items.destroy', [$list, $item]));
        $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
    }

    public function test_clearing_what_is_done_keeps_what_is_left()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $list = $this->shoppingList($family);
        $open = ListItem::factory()->for($list, 'list')->create();
        $done = ListItem::factory()->for($list, 'list')->completed()->create();

        $this->actingAs($parent)
            ->delete(route('lists.completed.destroy', $list))
            ->assertRedirect(route('lists.show', $list));

        $this->assertDatabaseHas('list_items', ['id' => $open->id]);
        $this->assertDatabaseMissing('list_items', ['id' => $done->id]);
    }

    public function test_an_item_is_only_reached_through_its_own_list()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $item = ListItem::factory()->for($this->shoppingList($family), 'list')->create();
        $otherList = FamilyList::factory()->for($family)->create();

        $this->actingAs($parent)
            ->delete(route('list-items.destroy', [$otherList, $item]))
            ->assertNotFound();

        $this->assertDatabaseHas('list_items', ['id' => $item->id]);
    }

    public function test_an_item_needs_a_name()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('list-items.store', $this->shoppingList($family)), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_the_childs_shopping_page_shows_the_shopping_list()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $list = $this->shoppingList($family);
        ListItem::factory()->for($list, 'list')->create(['name' => 'Bananer']);
        ListItem::factory()->for(FamilyList::factory()->for($family), 'list')->create(['name' => 'Hemligt']);

        $this->actingAs($child)
            ->get(route('child.shopping'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/ShoppingList')
                ->where('list.id', $list->id)
                ->has('items', 1)
                ->where('items.0.name', 'Bananer'),
            );
    }
}
