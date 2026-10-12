<?php

namespace Tests\Feature\Pictures;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\FamilyList;
use App\Models\Picture;
use App\Models\PictureCategory;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PictureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Picture::DISK);
    }

    private function stored(string $label): Picture
    {
        return Picture::query()->withoutGlobalScopes()->where('label', $label)->firstOrFail();
    }

    public function test_a_family_photo_is_made_a_small_webp_without_the_original()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->postJson(route('pictures.store'), [
                'photo' => UploadedFile::fake()->image('IMG_1234.jpg', 2000, 1500),
                'label' => 'Min tandborste',
            ])
            ->assertCreated()
            ->assertJsonPath('picture.label', 'Min tandborste');

        $picture = $this->stored('Min tandborste');
        $this->assertSame($family->id, $picture->family_id);
        $this->assertSame($editor->id, $picture->uploaded_by);
        $this->assertNotNull($picture->processed_at);
        $this->assertStringEndsWith('.webp', $picture->path);
        $this->assertSame([], Storage::disk(Picture::DISK)->files('pictures/originals'));

        [$width, $height] = getimagesizefromstring((string) Storage::disk(Picture::DISK)->get($picture->path)) ?: [0, 0];
        $this->assertSame(600, $width);
        $this->assertSame(450, $height);
    }

    public function test_viewers_and_children_cannot_upload_photos()
    {
        $family = Family::factory()->create();
        $viewer = User::factory()->inFamily($family, FamilyRole::Viewer)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();

        foreach ([$viewer, $child] as $user) {
            $this->actingAs($user)
                ->postJson(route('pictures.store'), [
                    'photo' => UploadedFile::fake()->image('foto.jpg'),
                    'label' => 'Foto',
                ])
                ->assertForbidden();
        }

        $this->assertSame(0, Picture::query()->withoutGlobalScopes()->count());
    }

    public function test_only_pictures_can_be_uploaded()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->postJson(route('pictures.store'), [
                'photo' => UploadedFile::fake()->create('lista.pdf', 10, 'application/pdf'),
                'label' => 'Lista',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_the_picker_shows_the_gallery_and_the_familys_own_photos_only()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $food = PictureCategory::factory()->create(['name' => 'Mat']);

        Picture::factory()->create(['label' => 'Mjölk', 'picture_category_id' => $food->id]);
        Picture::factory()->create(['label' => 'Tandborste', 'keywords' => 'borsta, tänder']);
        Picture::factory()->ofFamily($family)->create(['label' => 'Vår katt']);
        Picture::factory()->ofFamily(Family::factory()->create())->create(['label' => 'Deras katt']);

        $labels = fn (array $query) => collect($this->actingAs($child)->getJson(route('pictures.index', $query))->json('pictures'))
            ->pluck('label')
            ->all();

        $this->assertSame(['Mjölk', 'Tandborste', 'Vår katt'], $labels([]));
        $this->assertSame(['Tandborste'], $labels(['sok' => 'borsta']));
        $this->assertSame(['Mjölk'], $labels(['kategori' => (string) $food->id]));
        $this->assertSame(['Vår katt'], $labels(['kategori' => 'egna']));
    }

    public function test_picture_files_are_shown_to_the_family_but_not_to_others_or_guests()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $own = Picture::factory()->ofFamily($family)->create();
        $gallery = Picture::factory()->create();
        $theirs = Picture::factory()->ofFamily(Family::factory()->create())->create();

        foreach ([$own, $gallery, $theirs] as $picture) {
            Storage::disk(Picture::DISK)->put($picture->path, 'bild');
        }

        $this->actingAs($parent)->get(route('pictures.show', $own))->assertOk();
        $this->actingAs($parent)->get(route('pictures.show', $gallery))->assertOk();
        $this->actingAs($parent)->get(route('pictures.show', $theirs))->assertNotFound();

        $this->post(route('logout'));
        $this->get(route('pictures.show', $gallery))->assertNotFound();
    }

    public function test_a_task_gets_a_picture_from_the_gallery_or_the_family_but_not_from_another_family()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $gallery = Picture::factory()->create();
        $theirs = Picture::factory()->ofFamily(Family::factory()->create())->create();

        $this->actingAs($parent)->post(route('tasks.store'), [
            'title' => 'Borsta tänderna',
            'date' => '2026-10-14',
            'picture_id' => $gallery->id,
        ]);
        $this->assertSame($gallery->id, Task::query()->withoutGlobalScopes()->where('title', 'Borsta tänderna')->value('picture_id'));

        $this->actingAs($parent)
            ->post(route('tasks.store'), [
                'title' => 'Mata katten',
                'date' => '2026-10-14',
                'picture_id' => $theirs->id,
            ])
            ->assertSessionHasErrors(['picture_id' => 'Bilden finns inte.']);
    }

    public function test_recurring_tasks_pass_their_picture_on_to_each_day()
    {
        $this->travelTo('2026-10-12 06:00:00');
        $picture = Picture::factory()->create();
        $recurring = RecurringTask::factory()->create(['picture_id' => $picture->id]);

        $this->artisan('tasks:generate-recurring');

        $this->assertSame(
            [$picture->id],
            Task::query()->withoutGlobalScopes()->where('recurring_task_id', $recurring->id)->distinct()->pluck('picture_id')->all(),
        );
    }

    public function test_a_child_adds_to_the_shopping_list_by_choosing_a_picture()
    {
        $family = Family::factory()->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $milk = Picture::factory()->create(['label' => 'Mjölk']);
        $list = FamilyList::query()->withoutGlobalScopes()->where('family_id', $family->id)->firstOrFail();

        $this->actingAs($child)->post(route('list-items.store', $list), ['name' => 'Mjölk', 'picture_id' => $milk->id]);

        $this->assertDatabaseHas('list_items', ['list_id' => $list->id, 'name' => 'Mjölk', 'picture_id' => $milk->id, 'added_by' => $child->id]);
    }

    public function test_a_parent_gives_a_child_a_picture_as_avatar()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $child = User::factory()->child()->inFamily($family, FamilyRole::Child)->create();
        $photo = Picture::factory()->ofFamily($family)->create();

        $this->actingAs($parent)
            ->put(route('children.update', $child->id), [
                'name' => $child->name,
                'avatar_color' => 'green',
                'picture_id' => $photo->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($photo->id, $child->fresh()?->picture_id);
    }

    public function test_removing_a_picture_removes_its_file()
    {
        $picture = Picture::factory()->create();
        Storage::disk(Picture::DISK)->put($picture->path, 'bild');

        $picture->delete();

        Storage::disk(Picture::DISK)->assertMissing($picture->path);
    }
}
