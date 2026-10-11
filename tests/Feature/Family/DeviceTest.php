<?php

namespace Tests\Feature\Family;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_makes_their_device_a_family_device()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $response = $this->actingAs($parent)
            ->post(route('devices.store'), ['name' => 'Familjens läsplatta']);

        $response->assertRedirect(route('family.show'))->assertCookie(Device::COOKIE);

        $this->assertDatabaseHas('devices', [
            'family_id' => $family->id,
            'name' => 'Familjens läsplatta',
            'type' => 'family',
        ]);
    }

    public function test_the_start_page_on_a_family_device_is_the_childrens_login()
    {
        [, $token] = Device::issue(DeviceType::Family, 'Köksplattan', null, Family::factory()->create());

        $this->withCookie(Device::COOKIE, $token)
            ->get(route('home'))
            ->assertRedirect(route('child-login'));
    }

    public function test_a_childs_own_device_is_paired_with_the_link_from_the_qr_code()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->post(route('children.pairing.store', $child->id))
            ->assertRedirect(route('children.show', $child->id));

        $url = session(SessionKey::FLASH_DATA)['pairing']['url'];

        $this->post(route('logout'));

        $this->post($url)
            ->assertRedirect(route('child-login'))
            ->assertCookie(Device::COOKIE);

        $this->assertDatabaseHas('devices', [
            'family_id' => $family->id,
            'user_id' => $child->id,
            'type' => 'child',
        ]);
    }

    public function test_a_pairing_link_works_only_once()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        $this->actingAs($parent)->post(route('children.pairing.store', $child->id));

        $url = session(SessionKey::FLASH_DATA)['pairing']['url'];

        $this->post(route('logout'));
        $this->post($url);

        $this->post($url)->assertCookieMissing(Device::COOKIE);

        $this->assertSame(1, Device::withoutGlobalScopes()->where('user_id', $child->id)->count());
    }

    public function test_a_parent_removes_a_device()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $device = Device::factory()->for($family)->create();

        $this->actingAs($parent)
            ->delete(route('devices.destroy', $device->id))
            ->assertRedirect(route('family.show'));

        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_viewers_cannot_remove_devices()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $device = Device::factory()->for($family)->create();

        $this->actingAs($viewer)
            ->delete(route('devices.destroy', $device->id))
            ->assertForbidden();
    }
}
