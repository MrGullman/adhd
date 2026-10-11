<?php

namespace Tests\Feature\Family;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\User;
use App\Support\ChildPins;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChildAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_adds_a_child_with_a_code()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $response = $this->actingAs($parent)
            ->post(route('children.store'), ['name' => 'Ella', 'avatar_color' => 'orange', 'pin' => '4826']);

        $child = $family->children()->firstOrFail();
        $response->assertRedirect(route('children.show', $child->id));

        $this->assertNull($child->email);
        $this->assertSame('4826', $child->pin);
    }

    public function test_the_code_is_stored_encrypted()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('children.store'), ['name' => 'Ella', 'avatar_color' => 'orange', 'pin' => '4826']);

        $stored = DB::table('users')->where('name', 'Ella')->value('pin');

        $this->assertNotSame('4826', $stored);
        $this->assertSame('4826', decrypt($stored, false));
    }

    public function test_a_random_code_is_chosen_when_none_is_given()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('children.store'), ['name' => 'Ella', 'avatar_color' => 'orange']);

        $pin = $family->children()->firstOrFail()->pin;

        $this->assertTrue(ChildPins::hasValidFormat($pin));
        $this->assertFalse(ChildPins::isTooSimple($pin));
    }

    public function test_a_too_simple_code_is_rejected()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->actingAs($parent)
            ->post(route('children.store'), ['name' => 'Ella', 'avatar_color' => 'orange', 'pin' => '1234'])
            ->assertSessionHasErrors(['pin' => 'Välj en svårare kod. Undvik samma siffra och siffror i följd.']);
    }

    public function test_a_parent_sees_the_childs_code()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->get(route('children.show', $child->id))
            ->assertInertia(fn (Assert $page) => $page
                ->component('family/Child')
                ->where('child.pin', '4826'));
    }

    public function test_editors_cannot_open_the_child_page_with_the_code()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($editor)
            ->get(route('children.show', $child->id))
            ->assertForbidden();
    }

    public function test_the_code_is_never_shared_with_every_page()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();

        $this->assertArrayNotHasKey('pin', $child->toArray());
    }

    public function test_a_parent_resets_a_forgotten_code()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->post(route('children.pin.store', $child->id))
            ->assertRedirect(route('children.show', $child->id));

        $this->assertNotSame('4826', $child->fresh()->pin);
    }

    public function test_children_are_sent_to_their_own_page_from_adult_pages()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($child)
            ->get(route('family.show'))
            ->assertRedirect(route('child.home'));

        $this->actingAs($child)
            ->get(route('profile.edit'))
            ->assertRedirect(route('child.home'));
    }
}
