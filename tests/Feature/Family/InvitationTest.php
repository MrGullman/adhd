<?php

namespace Tests\Feature\Family;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\User;
use App\Notifications\FamilyInvitationNotification;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_invites_an_adult_by_email()
    {
        Notification::fake();
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->post(route('family-invitations.store'), ['email' => 'Mormor@Exempel.se', 'role' => 'editor'])
            ->assertRedirect(route('family.show'));

        $this->assertDatabaseHas('family_invitations', [
            'family_id' => $family->id,
            'email' => 'mormor@exempel.se',
            'role' => 'editor',
        ]);

        Notification::assertSentOnDemand(
            FamilyInvitationNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'mormor@exempel.se',
        );
    }

    public function test_viewers_cannot_invite()
    {
        $viewer = User::factory()->inFamily(Family::factory()->create(), FamilyRole::Viewer)->create();

        $this->actingAs($viewer)
            ->post(route('family-invitations.store'), ['email' => 'mormor@exempel.se', 'role' => 'viewer'])
            ->assertForbidden();
    }

    public function test_children_cannot_be_invited_as_a_role()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();

        $this->actingAs($parent)
            ->post(route('family-invitations.store'), ['email' => 'barn@exempel.se', 'role' => 'child'])
            ->assertSessionHasErrors('role');
    }

    public function test_a_new_adult_creates_an_account_from_the_invitation()
    {
        $family = Family::factory()->create();
        $token = $this->invite($family, 'mormor@exempel.se', FamilyRole::Editor);

        $this->post(route('invitations.accept', $token), [
            'name' => 'Mormor',
            'password' => 'ett-långt-lösenord',
            'password_confirmation' => 'ett-långt-lösenord',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'mormor@exempel.se')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(FamilyRole::Editor, $user->roleIn($family));
    }

    public function test_an_adult_with_an_account_is_asked_to_log_in_first()
    {
        $family = Family::factory()->create();
        User::factory()->create(['email' => 'mormor@exempel.se']);
        $token = $this->invite($family, 'mormor@exempel.se', FamilyRole::Viewer);

        $this->post(route('invitations.accept', $token))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_logged_in_adult_with_the_invited_address_joins_the_family()
    {
        $family = Family::factory()->create();
        $user = User::factory()->create(['email' => 'mormor@exempel.se']);
        $token = $this->invite($family, 'mormor@exempel.se', FamilyRole::Viewer);

        $this->actingAs($user)
            ->post(route('invitations.accept', $token))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(FamilyRole::Viewer, $user->roleIn($family));
    }

    public function test_an_adult_logged_in_with_another_address_cannot_use_the_invitation()
    {
        $family = Family::factory()->create();
        $other = User::factory()->create(['email' => 'nagon@exempel.se']);
        $token = $this->invite($family, 'mormor@exempel.se', FamilyRole::Viewer);

        $this->actingAs($other)
            ->post(route('invitations.accept', $token))
            ->assertSessionHasErrors('email');

        $this->assertNull($other->roleIn($family));
    }

    public function test_an_expired_invitation_cannot_be_used()
    {
        $family = Family::factory()->create();
        $token = $this->invite($family, 'mormor@exempel.se', FamilyRole::Viewer);
        $this->travel(FamilyInvitation::VALID_DAYS + 1)->days();

        $this->post(route('invitations.accept', $token), [
            'name' => 'Mormor',
            'password' => 'ett-långt-lösenord',
            'password_confirmation' => 'ett-långt-lösenord',
        ])->assertRedirect(route('invitations.show', $token));

        $this->assertDatabaseMissing('users', ['email' => 'mormor@exempel.se']);
    }

    private function invite(Family $family, string $email, FamilyRole $role): string
    {
        $inviter = User::factory()->inFamily($family)->create();
        app(CurrentFamily::class)->set($family);

        [, $token] = FamilyInvitation::issue($email, $role, $inviter);

        app(CurrentFamily::class)->forget();

        return $token;
    }
}
