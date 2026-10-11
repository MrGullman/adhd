<?php

namespace Tests\Feature\Family;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_adults_without_a_family_are_sent_to_create_one()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('family.create'));
    }

    public function test_an_adult_creates_a_family_and_becomes_its_parent()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('family.store'), ['name' => 'Familjen Andersson'])
            ->assertRedirect(route('dashboard'));

        $family = Family::query()->where('name', 'Familjen Andersson')->firstOrFail();

        $this->assertSame(FamilyRole::Parent, $user->roleIn($family));
        $this->assertSame($family->id, $user->fresh()->active_family_id);
    }

    public function test_a_family_needs_a_name()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('family.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Familjens namn är obligatoriskt.']);
    }

    public function test_the_family_page_lists_adults_and_children()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create(['name' => 'Kim']);
        User::factory()->child()->inFamily($family, FamilyRole::Child)->create(['name' => 'Ella']);

        $this->actingAs($parent)
            ->get(route('family.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('family/Show')
                ->where('adults.0.name', 'Kim')
                ->where('adults.0.isYou', true)
                ->where('children.0.name', 'Ella')
                ->where('canManage', true));
    }

    public function test_viewers_see_the_family_but_not_devices_or_invitations()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();

        $this->actingAs($viewer)
            ->get(route('family.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canManage', false)
                ->where('devices', [])
                ->where('invitations', []));
    }

    public function test_editors_cannot_add_children()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->post(route('children.store'), ['name' => 'Ella', 'avatar_color' => 'orange'])
            ->assertForbidden();

        $this->assertCount(0, $family->children);
    }

    public function test_an_adult_switches_to_another_of_their_families()
    {
        $first = Family::factory()->create();
        $second = Family::factory()->create();
        $user = User::factory()->inFamily($first)->create();
        $second->addMember($user, FamilyRole::Viewer);

        $this->actingAs($user)
            ->post(route('active-family.update', $second))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($second->id, $user->fresh()->active_family_id);
    }

    public function test_an_adult_cannot_switch_to_a_family_they_do_not_belong_to()
    {
        $user = User::factory()->inFamily(Family::factory()->create())->create();
        $other = Family::factory()->create();

        $this->actingAs($user)
            ->post(route('active-family.update', $other))
            ->assertForbidden();
    }
}
