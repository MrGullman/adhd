<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The login page is an Inertia page: it can only follow redirects to other
 * Inertia pages, so the admin portal is opened with a full page load.
 */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user, array $session = []): TestResponse
    {
        return $this->withSession($session)
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request())])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    }

    public function test_an_admin_without_a_family_lands_in_the_admin_portal()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->login($admin)
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', url('/admin'));
    }

    public function test_a_parent_goes_to_idag()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->login($parent)->assertRedirect(route('dashboard'));
    }

    public function test_a_parent_is_not_sent_to_the_admin_portal_they_tried_before()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->login($parent, ['url.intended' => url('/admin')])->assertRedirect(route('dashboard'));
    }

    public function test_addresses_that_are_not_pages_are_skipped()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->login($parent, ['url.intended' => url('/bilder?sok=mj')])->assertRedirect(route('dashboard'));
    }

    public function test_a_parent_returns_to_the_page_they_tried_to_open()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->login($parent, ['url.intended' => route('lists.index')])->assertRedirect(route('lists.index'));
    }
}
