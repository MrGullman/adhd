<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityThread;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private CommunityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::factory()->create(['name' => 'Ekfamiljen']);
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim Ek', 'community_name' => 'Pappa till två', 'password' => 'hemligt-losen-123']);
        $this->group = CommunityGroup::factory()->create(['name' => 'Sömn']);
        $this->group->members()->attach($this->parent);
    }

    private function otherParent(string $alias = 'Mamma i norr'): User
    {
        $parent = User::factory()->inFamily(Family::factory()->create(['name' => 'Björkfamiljen']))->create(['name' => 'Sam Björk', 'community_name' => $alias]);
        $this->group->members()->attach($parent);

        return $parent;
    }

    public function test_parents_in_different_families_talk_under_their_chosen_names()
    {
        $this->actingAs($this->parent)
            ->post(route('community.threads.store', $this->group), ['title' => 'Kvällarna', 'body' => 'Hur gör ni vid läggdags?'])
            ->assertRedirect();
        $thread = CommunityThread::query()->sole();

        $other = $this->otherParent();
        $this->actingAs($other)
            ->post(route('community.posts.store', $thread), ['body' => 'Vi har bildschema på kvällen.'])
            ->assertRedirect();

        $this->actingAs($other)
            ->get(route('community.threads.show', $thread))
            ->assertInertia(fn (Assert $page) => $page
                ->component('community/Thread')
                ->has('posts.data', 2)
                ->where('posts.data.0.author', 'Pappa till två')
                ->where('posts.data.1.isYours', true))
            ->assertDontSee('Kim Ek')
            ->assertDontSee('Ekfamiljen');

        $this->actingAs($this->parent)
            ->get(route('community.index'))
            ->assertInertia(fn (Assert $page) => $page->where('recent.0.title', 'Kvällarna')->where('recent.0.replies', 1));
    }

    public function test_only_parents_ever_reach_community()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();
        $child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create();

        foreach ([$editor, $viewer] as $adult) {
            $this->actingAs($adult)->get(route('community.index'))->assertForbidden();
            $this->actingAs($adult)->post(route('community.groups.join', $this->group))->assertForbidden();
        }

        $this->actingAs($child)->get(route('community.index'))->assertRedirect(route('child.home'));
        $this->actingAs($child)->post(route('community.groups.join', $this->group))->assertRedirect(route('child.home'));
        $this->assertSame(1, $this->group->members()->count());
    }

    public function test_a_group_is_read_and_written_only_by_its_members()
    {
        $thread = CommunityThread::factory()->for($this->group, 'group')->create();
        $outsider = User::factory()->inFamily(Family::factory()->create())->create(['community_name' => 'Utanför']);

        $this->actingAs($outsider)->get(route('community.groups.show', $this->group))->assertForbidden();
        $this->actingAs($outsider)->get(route('community.threads.show', $thread))->assertForbidden();
        $this->actingAs($outsider)->post(route('community.posts.store', $thread), ['body' => 'Hej'])->assertForbidden();

        $this->actingAs($outsider)->post(route('community.groups.join', $this->group))->assertRedirect(route('community.groups.show', $this->group));
        $this->actingAs($outsider)->get(route('community.threads.show', $thread))->assertOk();
    }

    public function test_a_name_is_needed_before_writing_and_names_are_unique()
    {
        $nameless = User::factory()->inFamily(Family::factory()->create())->create();
        $this->group->members()->attach($nameless);

        $this->actingAs($nameless)
            ->post(route('community.threads.store', $this->group), ['title' => 'Hej', 'body' => 'Text'])
            ->assertForbidden();

        $this->actingAs($nameless)
            ->put(route('community.name'), ['community_name' => 'Pappa till två'])
            ->assertSessionHasErrors('community_name');

        $this->actingAs($nameless)->put(route('community.name'), ['community_name' => 'Ny här'])->assertRedirect();
        $this->actingAs($nameless)
            ->post(route('community.threads.store', $this->group), ['title' => 'Hej', 'body' => 'Text'])
            ->assertRedirect();
    }

    public function test_only_the_author_changes_or_removes_a_post_and_others_can_report_it()
    {
        $thread = CommunityThread::factory()->for($this->group, 'group')->for($this->parent, 'author')->create();
        $first = CommunityPost::factory()->for($thread, 'thread')->for($this->parent, 'author')->create();
        $other = $this->otherParent();
        $reply = CommunityPost::factory()->for($thread, 'thread')->for($other, 'author')->create(['body' => 'Mitt svar']);

        $this->actingAs($this->parent)->put(route('community.posts.update', $reply), ['body' => 'Ändrat'])->assertForbidden();
        $this->actingAs($this->parent)->delete(route('community.posts.destroy', $reply))->assertForbidden();
        $this->actingAs($other)->post(route('community.posts.report', $reply))->assertForbidden();

        $this->actingAs($this->parent)->post(route('community.posts.report', $reply), ['reason' => 'Elakt'])->assertRedirect();
        $this->assertSame(1, CommunityReport::query()->count());

        $this->actingAs($other)->put(route('community.posts.update', $reply), ['body' => 'Rättat svar'])->assertRedirect();
        $this->assertNotNull($reply->fresh()?->edited_at);

        // Removing the first post removes the whole thread.
        $this->actingAs($this->parent)->delete(route('community.posts.destroy', $first))->assertRedirect(route('community.groups.show', $this->group));
        $this->assertModelMissing($thread);
        $this->assertModelMissing($reply);
    }

    public function test_removing_a_family_removes_its_parents_posts()
    {
        $other = $this->otherParent();
        $thread = CommunityThread::factory()->for($this->group, 'group')->for($other, 'author')->create();
        CommunityPost::factory()->for($thread, 'thread')->for($other, 'author')->create();
        $mine = CommunityPost::factory()->for($thread, 'thread')->for($this->parent, 'author')->create();

        $this->actingAs($this->parent)
            ->delete(route('family.destroy'), ['name' => 'Ekfamiljen', 'password' => 'hemligt-losen-123'])
            ->assertRedirect('/');

        $this->assertModelMissing($mine);
        $this->assertModelExists($thread);
    }

    public function test_admins_moderate_reported_posts_in_the_admin_portal()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/community-reports')->assertOk();
        $this->actingAs($admin)->get('/admin/community-groups')->assertOk()->assertSee('Sömn');
        $this->actingAs($this->parent)->get('/admin/community-reports')->assertForbidden();
    }
}
