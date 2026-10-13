<?php

namespace Tests\Feature;

use App\Admin\Suspensions;
use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Models\AdminAction;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\CommunityThread;
use App\Models\Device;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class SuspensionTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::factory()->create(['name' => 'Ekfamiljen']);
        $this->parent = User::factory()->inFamily($this->family)->create(['email' => 'kim@exempel.se', 'password' => 'hemligt-losen-123']);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_a_suspended_account_is_logged_out_and_cannot_log_in()
    {
        $this->actingAs($this->parent)->get(route('dashboard'))->assertOk();

        app(Suspensions::class)->suspendUser($this->parent, 'Skräppost i Community', $this->admin);

        $this->actingAs($this->parent)->get(route('dashboard'))->assertRedirect(route('suspended'));
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => 'kim@exempel.se', 'password' => 'hemligt-losen-123'])
            ->assertSessionHasErrors(['email' => 'Kontot är avstängt. Kontakta oss om du tror att det är fel.']);
        $this->assertGuest();

        app(Suspensions::class)->unsuspendUser($this->parent, $this->admin);
        $this->post(route('login.store'), ['email' => 'kim@exempel.se', 'password' => 'hemligt-losen-123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->parent);
        $this->assertNotNull($this->parent->fresh()?->last_login_at);

        $this->assertSame(['suspended', 'unsuspended'], AdminAction::query()->orderBy('id')->pluck('action')->all());
        $this->assertSame('Skräppost i Community', AdminAction::query()->value('reason'));
    }

    public function test_a_suspended_family_stops_everyone_in_it_and_its_devices()
    {
        $child = User::factory()->child('4826')->inFamily($this->family, FamilyRole::Child)->create();
        [, $deviceToken] = Device::issue(DeviceType::Family, 'Köket', null, $this->family);
        [, $displayToken] = Device::issue(DeviceType::Display, 'Hallen', null, $this->family);

        app(Suspensions::class)->suspendFamily($this->family, 'Missbruk', $this->admin);

        $this->actingAs($this->parent)->get(route('dashboard'))->assertRedirect(route('suspended'));
        $this->actingAs($child)->get(route('child.home'))->assertRedirect(route('suspended'));
        $this->post(route('logout'));

        $this->withCookie(Device::COOKIE, $deviceToken)->get(route('child-login'))->assertRedirect(route('suspended'));
        $this->withCookie(Device::COOKIE, $deviceToken)
            ->post(route('child-login.store'), ['child_id' => $child->id, 'pin' => '4826'])
            ->assertRedirect(route('suspended'));
        $this->assertGuest();

        $this->get(route('display.show', $displayToken))->assertForbidden();
        $this->assertModelExists($child);
    }

    public function test_an_adult_in_another_family_continues_there()
    {
        $other = Family::factory()->create();
        $other->addMember($this->parent, FamilyRole::Editor);

        app(Suspensions::class)->suspendFamily($this->family, 'Missbruk', $this->admin);

        $this->actingAs($this->parent)->get(route('dashboard'))->assertRedirect(route('dashboard'));
        $this->assertSame($other->id, $this->parent->fresh()?->active_family_id);
        $this->actingAs($this->parent)->get(route('dashboard'))->assertOk();
    }

    public function test_a_suspended_accounts_community_posts_are_hidden()
    {
        $group = CommunityGroup::factory()->create();
        $reader = User::factory()->inFamily(Family::factory()->create())->create(['community_name' => 'Läsare']);
        $group->members()->attach([$reader->id, $this->parent->id]);
        $this->parent->forceFill(['community_name' => 'Skräpare'])->save();
        $thread = CommunityThread::factory()->for($group, 'group')->for($reader, 'author')->create();
        CommunityPost::factory()->for($thread, 'thread')->for($reader, 'author')->create();
        CommunityPost::factory()->for($thread, 'thread')->for($this->parent, 'author')->create(['body' => 'Köp billiga klockor']);

        app(Suspensions::class)->suspendUser($this->parent, 'Reklam', $this->admin);

        $this->actingAs($reader)
            ->get(route('community.threads.show', $thread))
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1));
        $this->assertSame(2, CommunityPost::query()->count(), 'hidden, not removed');
    }

    public function test_admins_are_never_suspended()
    {
        $this->expectException(InvalidArgumentException::class);

        app(Suspensions::class)->suspendUser($this->admin, 'Test', $this->admin);
    }

    public function test_admins_suspend_from_the_admin_portal_and_others_cannot()
    {
        $this->actingAs($this->admin);

        Livewire::test(ListAccounts::class)
            ->assertCanSeeTableRecords([$this->parent])
            ->callTableAction('suspend', $this->parent, data: ['reason' => 'Hot mot andra'])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($this->parent->fresh()?->isSuspended());

        Livewire::test(ListFamilies::class)
            ->assertCanSeeTableRecords([$this->family])
            ->callTableAction('suspend', $this->family, data: ['reason' => 'Missbruk'])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($this->family->fresh()?->isSuspended());

        $this->actingAs($this->admin)->get('/admin/logg')->assertOk()->assertSee('Hot mot andra');

        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $this->actingAs($parent)->get('/admin/konton')->assertForbidden();
        $this->actingAs($parent)->get('/admin/families')->assertForbidden();
    }
}
