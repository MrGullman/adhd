<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Models\Calendar;
use App\Models\DataExport;
use App\Models\Family;
use App\Models\JournalEntry;
use App\Models\Picture;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class FamilyDataTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->family = Family::factory()->create(['name' => 'Ekfamiljen']);
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim', 'password' => 'hemligt-losen-123']);
        $this->child = User::factory()->child('5817')->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    private function picture(Family $family): Picture
    {
        $picture = Picture::factory()->create(['family_id' => $family->id, 'path' => "pictures/familjer/{$family->id}/egen.webp"]);
        Storage::disk('local')->put($picture->path, 'bild');

        return $picture;
    }

    /**
     * @return array<string, string>
     */
    private function zip(DataExport $export): array
    {
        $zip = new ZipArchive;
        $zip->open(Storage::disk('local')->path((string) $export->path));
        $files = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $files[$name] = (string) $zip->getFromIndex($i);
        }

        return $files;
    }

    public function test_a_parent_downloads_all_the_familys_data_without_secrets()
    {
        app(CurrentFamily::class)->set($this->family);
        JournalEntry::factory()->for($this->family)->create(['content' => 'Bra morgon med bildschemat.']);
        Calendar::factory()->for($this->family)->create(['url' => 'https://8.8.8.8/hemlig-kalender.ics']);
        Task::factory()->for($this->family)->create(['title' => 'Mata katten']);
        $picture = $this->picture($this->family);
        $other = Family::factory()->create();
        Task::factory()->for($other)->create(['title' => 'Deras uppgift']);
        app(CurrentFamily::class)->forget();

        $this->actingAs($this->parent)->post(route('family.data.export'))->assertRedirect(route('family.data'));

        $export = DataExport::query()->withoutGlobalScopes()->sole();
        $this->assertSame('ready', $export->status);
        $files = $this->zip($export);
        $all = implode("\n", $files);

        $this->assertArrayHasKey("bilder/{$picture->id}.webp", $files);
        $this->assertStringContainsString('Bra morgon med bildschemat.', $files['dagbok.json'], 'the journal is readable');
        $this->assertStringContainsString('Mata katten', $files['uppgifter.json']);
        $this->assertStringContainsString('Alva', $files['medlemmar.json']);
        $this->assertStringNotContainsString('Deras uppgift', $all);
        $this->assertStringNotContainsString('hemlig-kalender', $all);
        $this->assertStringNotContainsString('5817', $all, "the child's code");
        $this->assertStringNotContainsString('"password"', $all);
        $this->assertStringNotContainsString('token_hash', $all);

        $this->actingAs($this->parent)->get(route('family.data.download', $export->id))->assertOk()->assertDownload();
    }

    public function test_only_the_familys_parents_reach_its_exports()
    {
        $this->actingAs($this->parent)->post(route('family.data.export'));
        $export = DataExport::query()->withoutGlobalScopes()->sole();

        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $this->actingAs($editor)->get(route('family.data'))->assertForbidden();
        $this->actingAs($editor)->get(route('family.data.download', $export->id))->assertForbidden();
        $this->actingAs($this->child)->get(route('family.data'))->assertRedirect(route('child.home'));

        $stranger = User::factory()->inFamily(Family::factory()->create())->create();
        $this->actingAs($stranger)->get(route('family.data.download', $export->id))->assertNotFound();
    }

    public function test_removing_a_child_removes_everything_about_the_child()
    {
        app(CurrentFamily::class)->set($this->family);
        $photo = $this->picture($this->family);
        $this->child->forceFill(['picture_id' => $photo->id])->save();
        Task::factory()->for($this->family)->create(['assigned_to' => $this->child->id]);
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();
        Task::factory()->for($this->family)->create(['assigned_to' => $sibling->id]);
        $this->child->updatePushSubscription('https://8.8.8.8/push/a', 'k', 't');

        $this->actingAs($this->parent)
            ->delete(route('children.destroy', $this->child->id), ['password' => 'fel'])
            ->assertSessionHasErrors('password');
        $this->assertModelExists($this->child);

        $this->actingAs($this->parent)
            ->delete(route('children.destroy', $this->child->id), ['password' => 'hemligt-losen-123'])
            ->assertRedirect(route('family.show'));

        $this->assertModelMissing($this->child);
        $this->assertSame(1, Task::query()->count(), "only the sibling's task is left");
        $this->assertFalse(Storage::disk('local')->exists($photo->path));
        $this->assertSame(0, DB::table('push_subscriptions')->count());
    }

    public function test_a_parent_cannot_remove_another_familys_child()
    {
        $theirChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($this->parent)
            ->delete(route('children.destroy', $theirChild->id), ['password' => 'hemligt-losen-123'])
            ->assertNotFound();

        $this->assertModelExists($theirChild);
    }

    public function test_removing_the_family_leaves_no_data_or_files_behind()
    {
        app(CurrentFamily::class)->set($this->family);
        $this->picture($this->family);
        JournalEntry::factory()->for($this->family)->create();
        $grandma = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();
        $otherFamily = Family::factory()->create();
        $otherFamily->addMember($grandma, FamilyRole::Editor);
        $admin = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create(['is_admin' => true]);
        $theirPicture = $this->picture($otherFamily);
        app(CurrentFamily::class)->forget();

        $this->actingAs($this->parent)
            ->delete(route('family.destroy'), ['name' => 'Fel namn', 'password' => 'hemligt-losen-123'])
            ->assertSessionHasErrors('name');
        $this->assertModelExists($this->family);

        $this->actingAs($this->parent)
            ->delete(route('family.destroy'), ['name' => 'Ekfamiljen', 'password' => 'hemligt-losen-123'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertModelMissing($this->family);
        $this->assertModelMissing($this->parent);
        $this->assertModelMissing($this->child);
        $this->assertModelExists($grandma);
        $this->assertModelExists($admin);
        $this->assertSame(0, DB::table('journal_entries')->where('family_id', $this->family->id)->count());
        $this->assertSame(0, DB::table('pictures')->where('family_id', $this->family->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles("pictures/familjer/{$this->family->id}"));
        $this->assertTrue(Storage::disk('local')->exists($theirPicture->path), "another family's files are left");
    }

    public function test_only_parents_remove_the_family()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create(['password' => 'hemligt-losen-123']);

        $this->actingAs($editor)
            ->delete(route('family.destroy'), ['name' => 'Ekfamiljen', 'password' => 'hemligt-losen-123'])
            ->assertForbidden();

        $this->assertModelExists($this->family);
    }

    public function test_the_privacy_policy_is_public()
    {
        config(['privacy.controller' => 'Exempelföreningen', 'privacy.contact' => 'integritet@exempel.se']);

        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Privacy')->where('controller', 'Exempelföreningen')->where('backupDays', 14));
    }
}
