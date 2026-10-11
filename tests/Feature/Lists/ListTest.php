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

class ListTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_family_gets_a_shopping_list()
    {
        $family = Family::factory()->create();

        $this->assertDatabaseHas('lists', [
            'family_id' => $family->id,
            'type' => 'shopping',
            'name' => 'Handlingslista',
        ]);
    }

    public function test_adults_see_the_shopping_list_first_with_what_is_left()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $list = FamilyList::factory()->for($family)->create(['name' => 'Att göra i helgen']);
        ListItem::factory()->for($list, 'list')->create();
        ListItem::factory()->for($list, 'list')->completed()->create();

        $this->actingAs($viewer)
            ->get(route('lists.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/Index')
                ->has('lists', 2)
                ->where('lists.0.name', 'Handlingslista')
                ->where('lists.0.openCount', 0)
                ->where('lists.1.name', 'Att göra i helgen')
                ->where('lists.1.openCount', 1)
                ->where('canCreate', false),
            );
    }

    public function test_an_editor_creates_a_list()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();

        $response = $this->actingAs($editor)
            ->post(route('lists.store'), ['name' => 'Packa till semestern']);

        $list = FamilyList::query()->withoutGlobalScopes()->where('name', 'Packa till semestern')->firstOrFail();

        $response->assertRedirect(route('lists.show', $list));
        $this->assertSame($family->id, $list->family_id);
        $this->assertSame('general', $list->type->value);
        $this->assertSame($editor->id, $list->created_by);
    }

    public function test_a_viewer_cannot_create_rename_or_remove_lists()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $list = FamilyList::factory()->for($family)->create();

        $this->actingAs($viewer)->post(route('lists.store'), ['name' => 'Ny'])->assertForbidden();
        $this->actingAs($viewer)->put(route('lists.update', $list), ['name' => 'Nytt namn'])->assertForbidden();
        $this->actingAs($viewer)->delete(route('lists.destroy', $list))->assertForbidden();

        $this->assertDatabaseHas('lists', ['id' => $list->id, 'name' => $list->name]);
    }

    public function test_the_shopping_list_can_be_renamed_but_not_removed()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $shopping = FamilyList::query()->withoutGlobalScopes()->where('family_id', $family->id)->firstOrFail();

        $this->actingAs($parent)
            ->put(route('lists.update', $shopping), ['name' => 'Handla'])
            ->assertRedirect(route('lists.show', $shopping));

        $this->actingAs($parent)
            ->delete(route('lists.destroy', $shopping))
            ->assertForbidden();

        $this->assertDatabaseHas('lists', ['id' => $shopping->id, 'name' => 'Handla']);
    }

    public function test_a_parent_removes_a_list_with_its_items()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $list = FamilyList::factory()->for($family)->create();
        $item = ListItem::factory()->for($list, 'list')->create();

        $this->actingAs($parent)
            ->delete(route('lists.destroy', $list))
            ->assertRedirect(route('lists.index'));

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
    }

    public function test_the_list_name_is_required()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('lists.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_children_are_sent_to_their_own_page_from_the_adults_lists()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($child)
            ->get(route('lists.index'))
            ->assertRedirect(route('child.home'));
    }
}
