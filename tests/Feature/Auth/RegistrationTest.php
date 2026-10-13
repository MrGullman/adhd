<?php

namespace Tests\Feature\Auth;

use App\Enums\FamilyRole;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Models\AdminAction;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_when_an_admin_has_closed_registration_nobody_can_register()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListFamilies::class)
            ->callAction('toggleRegistration')
            ->assertHasNoActionErrors();

        $this->assertFalse(Registration::isOpen());
        $this->assertSame('registration_closed', AdminAction::query()->value('action'));
        $this->post(route('logout'));

        $this->get(route('register'))->assertInertia(fn (Assert $page) => $page->component('auth/RegistrationClosed'));
        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('canRegister', false));

        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_invited_adults_can_join_while_registration_is_closed()
    {
        Registration::set(false);
        $family = Family::factory()->create();
        $inviter = User::factory()->inFamily($family)->create();
        app(CurrentFamily::class)->set($family);
        [, $token] = FamilyInvitation::issue('mormor@exempel.se', FamilyRole::Editor, $inviter);
        app(CurrentFamily::class)->forget();

        $this->post(route('invitations.accept', $token), [
            'name' => 'Mormor',
            'password' => 'ett-långt-lösenord',
            'password_confirmation' => 'ett-långt-lösenord',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_only_admins_reach_the_switch()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->actingAs($parent)->get('/admin/families')->assertForbidden();
        $this->assertTrue(Registration::isOpen());
    }
}
