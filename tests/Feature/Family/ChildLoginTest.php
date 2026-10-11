<?php

namespace Tests\Feature\Family;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Http\Controllers\ChildSessionController;
use App\Models\Device;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChildLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_child_logs_in_with_the_code_on_a_family_device()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();
        $token = $this->device($family, DeviceType::Family);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertRedirect(route('child.home'));

        $this->assertAuthenticatedAs($child);
        $this->assertDatabaseHas('login_events', ['user_id' => $child->id, 'succeeded' => true]);
    }

    public function test_a_child_cannot_log_in_without_a_trusted_device()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();

        $this->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertRedirect(route('child-login'));

        $this->assertGuest();
    }

    public function test_a_device_from_another_family_cannot_log_the_child_in()
    {
        $child = User::factory()->child('4826')->inFamily(Family::factory()->create(), FamilyRole::Child)->create();
        $token = $this->device(Family::factory()->create(), DeviceType::Family);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertSessionHasErrors(['pin' => 'Välj dig själv igen.']);

        $this->assertGuest();
    }

    public function test_a_childs_own_device_only_logs_in_that_child()
    {
        $family = Family::factory()->create();
        $ella = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();
        $noah = User::factory()->child('7351')->inFamily($family, FamilyRole::Child)->create();
        $token = $this->device($family, DeviceType::Child, $ella);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $noah->id, 'pin' => '7351'])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_a_wrong_code_is_rejected_and_counted()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();
        $token = $this->device($family, DeviceType::Family);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '1111'])
            ->assertSessionHasErrors(['pin' => 'Fel kod. Prova igen.']);

        $this->assertGuest();
        $this->assertSame(1, $child->fresh()->failed_pin_attempts);
        $this->assertDatabaseHas('login_events', ['user_id' => $child->id, 'succeeded' => false]);
    }

    public function test_the_child_waits_a_minute_after_five_wrong_codes()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create();
        $token = $this->device($family, DeviceType::Family);

        for ($i = 0; $i < ChildSessionController::ATTEMPTS_PER_MINUTE; $i++) {
            $this->withCookie(Device::COOKIE, $token)
                ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '1111']);
        }

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertSessionHasErrors(['pin' => 'Vänta en minut och prova igen.']);

        $this->assertGuest();
    }

    public function test_login_is_turned_off_after_ten_wrong_codes_in_a_row()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create([
            'failed_pin_attempts' => ChildSessionController::MAX_FAILED_ATTEMPTS - 1,
        ]);
        $token = $this->device($family, DeviceType::Family);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '1111'])
            ->assertSessionHasErrors(['pin' => 'Inloggningen är avstängd. Be en vuxen om hjälp.']);

        $this->assertNotNull($child->fresh()->login_disabled_at);
    }

    public function test_a_child_with_login_turned_off_cannot_log_in()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create([
            'login_disabled_at' => now(),
        ]);
        $token = $this->device($family, DeviceType::Family);

        $this->withCookie(Device::COOKIE, $token)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertSessionHasErrors(['pin' => 'Inloggningen är avstängd. Be en vuxen om hjälp.']);

        $this->assertGuest();
    }

    public function test_turning_off_login_logs_the_child_out_everywhere()
    {
        config(['session.driver' => 'database']);

        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $this->device($family, DeviceType::Child, $child);
        $rememberToken = $child->remember_token;

        DB::table('sessions')->insert([
            'id' => 'barnets-session',
            'user_id' => $child->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($parent)
            ->delete(route('children.access.destroy', $child->id))
            ->assertRedirect(route('children.show', $child->id));

        $child->refresh();
        $this->assertNotNull($child->login_disabled_at);
        $this->assertNotSame($rememberToken, $child->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'barnets-session']);
        $this->assertDatabaseMissing('devices', ['user_id' => $child->id]);
    }

    public function test_a_child_who_is_turned_off_is_logged_out_on_the_next_request()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create([
            'login_disabled_at' => now(),
        ]);

        $this->actingAs($child)
            ->get(route('child.home'))
            ->assertRedirect(route('child-login'));

        $this->assertGuest();
    }

    public function test_a_parent_turns_login_back_on_with_a_new_code()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child('4826')->inFamily($family, FamilyRole::Child)->create([
            'login_disabled_at' => now(),
        ]);

        $this->actingAs($parent)
            ->post(route('children.access.store', $child->id), ['pin' => '7351']);

        $child->refresh();
        $this->assertNull($child->login_disabled_at);
        $this->assertSame('7351', $child->pin);
    }

    private function device(Family $family, DeviceType $type, ?User $child = null): string
    {
        [, $token] = Device::issue($type, 'Köksplattan', $child, $family);

        return $token;
    }
}
