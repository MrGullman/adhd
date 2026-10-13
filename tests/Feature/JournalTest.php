<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\JournalEntry;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    public function test_a_parent_writes_a_note_about_a_child_and_it_is_encrypted()
    {
        $this->actingAs($this->parent)
            ->post(route('journal.store'), [
                'date' => '2026-10-13',
                'content' => 'Jobbig morgon, men bildschemat hjälpte.',
                'children' => [$this->child->id],
            ])
            ->assertRedirect(route('journal.index'));

        $raw = DB::table('journal_entries')->value('content');
        $this->assertStringNotContainsString('bildschemat', (string) $raw, 'never stored readable');

        $this->actingAs($this->parent)
            ->get(route('journal.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('journal/Index')
                ->where('entries.0.content', 'Jobbig morgon, men bildschemat hjälpte.')
                ->where('entries.0.children.0.name', 'Alva')
                ->where('entries.0.isYours', true));
    }

    public function test_only_parents_ever_reach_the_journal()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();

        foreach ([$editor, $viewer] as $adult) {
            $this->actingAs($adult)->get(route('journal.index'))->assertForbidden();
            $this->actingAs($adult)->post(route('journal.store'), ['date' => '2026-10-13', 'content' => 'Hej'])->assertForbidden();
        }

        $this->actingAs($this->child)->get(route('journal.index'))->assertRedirect(route('child.home'));
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_the_history_is_filtered_by_child_and_searched()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Bo']);
        JournalEntry::factory()->for($this->family)->create(['content' => 'Alva sov dåligt.'])->children()->attach($this->child);
        JournalEntry::factory()->for($this->family)->create(['content' => 'Bo hade en bra dag i skolan.'])->children()->attach($sibling);

        $this->actingAs($this->parent)
            ->get(route('journal.index', ['barn' => $sibling->id]))
            ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.content', 'Bo hade en bra dag i skolan.'));

        $this->actingAs($this->parent)
            ->get(route('journal.index', ['sok' => 'SOV']))
            ->assertInertia(fn (Assert $page) => $page->has('entries', 1)->where('entries.0.content', 'Alva sov dåligt.'));
    }

    public function test_both_parents_can_change_a_note()
    {
        $other = User::factory()->inFamily($this->family)->create(['name' => 'Sam']);
        $entry = JournalEntry::factory()->for($this->family)->create(['author_id' => $this->parent->id]);

        $this->actingAs($other)
            ->put(route('journal.update', $entry), ['date' => '2026-10-12', 'content' => 'Tillägg från Sam.', 'children' => [$this->child->id]])
            ->assertRedirect();

        $this->assertSame('Tillägg från Sam.', $entry->fresh()?->content);

        $this->actingAs($other)
            ->get(route('journal.index'))
            ->assertInertia(fn (Assert $page) => $page->where('entries.0.author', 'Kim')->where('entries.0.isYours', false));
    }

    public function test_another_familys_journal_is_never_reached()
    {
        $theirs = JournalEntry::factory()->create(['content' => 'Deras hemlighet']);
        $theirChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($this->parent)->put(route('journal.update', $theirs->id), ['date' => '2026-10-13', 'content' => 'Ändrat'])->assertNotFound();
        $this->actingAs($this->parent)->delete(route('journal.destroy', $theirs->id))->assertNotFound();
        $this->actingAs($this->parent)
            ->post(route('journal.store'), ['date' => '2026-10-13', 'content' => 'Hej', 'children' => [$theirChild->id]])
            ->assertSessionHasErrors('children.0');

        app(CurrentFamily::class)->set($this->family);
        $this->assertSame(0, JournalEntry::query()->count());
        $this->assertSame('Deras hemlighet', $theirs->fresh()?->content);
    }
}
