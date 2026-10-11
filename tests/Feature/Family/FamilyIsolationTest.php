<?php

namespace Tests\Feature\Family;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Family A must never read or change family B's data (docs/ARCHITECTURE.md).
 * Other families' records answer 404, so their existence is not revealed.
 */
class FamilyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_cannot_open_another_familys_child()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->get(route('children.show', $otherChild->id))
            ->assertNotFound();
    }

    public function test_a_parent_cannot_change_another_familys_child_code()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherChild = User::factory()->child('4826')->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->put(route('children.pin.update', $otherChild->id), ['pin' => '7351'])
            ->assertNotFound();

        $this->assertSame('4826', $otherChild->fresh()->pin);
    }

    public function test_a_parent_cannot_remove_another_familys_device()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherDevice = Device::factory()->create();

        $this->actingAs($parent)
            ->delete(route('devices.destroy', $otherDevice->id))
            ->assertNotFound();

        $this->assertDatabaseHas('devices', ['id' => $otherDevice->id]);
    }

    public function test_a_parent_cannot_remove_another_familys_invitation()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherInvitation = FamilyInvitation::factory()->create();

        $this->actingAs($parent)
            ->delete(route('family-invitations.destroy', $otherInvitation->id))
            ->assertNotFound();

        $this->assertDatabaseHas('family_invitations', ['id' => $otherInvitation->id]);
    }

    public function test_family_scoped_models_only_return_the_current_familys_rows()
    {
        $family = Family::factory()->create();
        Device::factory()->for($family)->create(['name' => 'Vår platta']);
        Device::factory()->create(['name' => 'Någon annans platta']);

        app(CurrentFamily::class)->set($family);

        $this->assertSame(['Vår platta'], Device::query()->pluck('name')->all());
    }

    public function test_family_scoped_models_return_nothing_without_a_current_family()
    {
        Device::factory()->create();

        $this->assertSame(0, Device::query()->count());
    }

    public function test_new_family_scoped_models_get_the_current_family()
    {
        $family = Family::factory()->create();
        app(CurrentFamily::class)->set($family);

        [$device] = Device::issue(DeviceType::Family, 'Köksplattan');

        $this->assertSame($family->id, $device->family_id);
    }
}
