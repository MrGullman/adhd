<?php

namespace Tests\Feature\Pictures;

use App\Actions\Pictures\StorePicture;
use App\Enums\AvatarGroup;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Picture::DISK);
        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    private function avatar(string $label, AvatarGroup $group): Picture
    {
        return Picture::factory()->create(['label' => $label, 'avatar_group' => $group]);
    }

    public function test_an_uploaded_avatar_keeps_its_see_through_background()
    {
        $image = imagecreatetruecolor(800, 800);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledellipse($image, 400, 400, 500, 500, (int) imagecolorallocate($image, 240, 190, 150));
        ob_start();
        imagepng($image);
        Storage::disk(Picture::DISK)->put('pictures/originals/pojke.png', (string) ob_get_clean());

        $avatar = app(StorePicture::class)->fromStoredFile('pictures/originals/pojke.png', 'Pojke', null, $this->parent, avatarGroup: AvatarGroup::Child);
        $avatar->refresh();

        $this->assertSame(AvatarGroup::Child, $avatar->avatar_group);
        $webp = imagecreatefromstring((string) Storage::disk(Picture::DISK)->get($avatar->path));
        $this->assertNotFalse($webp);
        $this->assertSame(127, (imagecolorat($webp, 0, 0) >> 24) & 0x7F, 'the corner is still see-through');
    }

    public function test_avatars_are_kept_apart_from_bildstod()
    {
        Picture::factory()->create(['label' => 'Tandborste']);
        $this->avatar('Pojke med lockar', AvatarGroup::Child);
        $this->avatar('Tonåring med undercut', AvatarGroup::Youth);
        Picture::factory()->ofFamily($this->family)->create(['label' => 'Foto på Alva']);

        $labels = fn (array $query) => collect($this->actingAs($this->parent)->getJson(route('pictures.index', $query))->json('pictures'))
            ->pluck('label')
            ->all();

        $this->assertSame(['Foto på Alva', 'Tandborste'], $labels([]));
        $this->assertSame(['Foto på Alva', 'Pojke med lockar', 'Tonåring med undercut'], $labels(['till' => 'avatar']));
        $this->assertSame(['Tonåring med undercut'], $labels(['till' => 'avatar', 'kategori' => 'avatar-youth']));
        $this->assertSame(['Foto på Alva'], $labels(['till' => 'avatar', 'kategori' => 'egna']));
    }

    public function test_a_child_chooses_their_own_avatar()
    {
        $young = $this->avatar('Pojke med lockar', AvatarGroup::Child);
        $this->avatar('Tonåring med undercut', AvatarGroup::Youth);

        $this->actingAs($this->child)
            ->get(route('child.avatar'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Avatar')
                ->where('groups.0.label', 'Barn')
                ->where('groups.0.avatars.0.label', 'Pojke med lockar')
                ->where('groups.1.label', 'Ungdom'));

        $this->actingAs($this->child)->put(route('child.avatar.update'), ['picture_id' => $young->id])->assertRedirect(route('child.home'));
        $this->assertSame($young->id, $this->child->fresh()?->picture_id);

        $this->actingAs($this->child)->put(route('child.avatar.update'), ['picture_id' => null]);
        $this->assertNull($this->child->fresh()?->picture_id);
    }

    public function test_a_child_can_only_choose_an_avatar()
    {
        $gallery = Picture::factory()->create(['label' => 'Tandborste']);
        $familyPhoto = Picture::factory()->ofFamily($this->family)->create();
        $other = Picture::factory()->ofFamily(Family::factory()->create())->create();

        foreach ([$gallery, $familyPhoto, $other] as $picture) {
            $this->actingAs($this->child)
                ->put(route('child.avatar.update'), ['picture_id' => $picture->id])
                ->assertSessionHasErrors('picture_id');
        }

        $this->actingAs($this->parent)->get(route('child.avatar'))->assertForbidden();
        $this->assertNull($this->child->fresh()?->picture_id);
    }

    public function test_admins_keep_avatars_in_their_own_list()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->avatar('Pojke med lockar', AvatarGroup::Child);
        Picture::factory()->create(['label' => 'Tandborste']);

        $this->actingAs($admin)->get('/admin/avatarer')->assertOk()->assertSee('Pojke med lockar')->assertDontSee('Tandborste');
        $this->actingAs($admin)->get('/admin/pictures')->assertOk()->assertSee('Tandborste')->assertDontSee('Pojke med lockar');
        $this->actingAs($this->parent)->get('/admin/avatarer')->assertForbidden();
    }

    public function test_adults_choose_from_the_adult_style_and_children_never_see_it()
    {
        $adult = $this->avatar('Skägg', AvatarGroup::Adult);
        $young = $this->avatar('Pojke med lockar', AvatarGroup::Child);
        $photo = Picture::factory()->ofFamily($this->family)->create(['label' => 'Foto på Kim']);
        $theirPhoto = Picture::factory()->ofFamily(Family::factory()->create())->create();

        $labels = fn (array $query) => collect($this->actingAs($this->parent)->getJson(route('pictures.index', $query))->json('pictures'))
            ->pluck('label')
            ->all();

        $this->assertSame(['Foto på Kim', 'Skägg'], $labels(['till' => 'avatar-vuxen']));
        $this->assertSame(['Foto på Kim', 'Pojke med lockar'], $labels(['till' => 'avatar']));

        $this->actingAs($this->parent)->put(route('profile.picture.update'), ['picture_id' => $adult->id])->assertRedirect(route('profile.edit'));
        $this->assertSame($adult->id, $this->parent->fresh()?->picture_id);

        $this->actingAs($this->parent)->put(route('profile.picture.update'), ['picture_id' => $photo->id])->assertRedirect();
        $this->assertSame($photo->id, $this->parent->fresh()?->picture_id);

        foreach ([$young, $theirPhoto] as $picture) {
            $this->actingAs($this->parent)
                ->put(route('profile.picture.update'), ['picture_id' => $picture->id])
                ->assertSessionHasErrors('picture_id');
        }

        $this->actingAs($this->parent)->put(route('profile.picture.update'), ['picture_id' => null]);
        $this->assertNull($this->parent->fresh()?->picture_id);

        // A child never gets the adult style, not even by asking for it.
        $this->actingAs($this->child)->get(route('child.avatar'))->assertInertia(fn (Assert $page) => $page->has('groups', 1));
        $this->actingAs($this->child)->put(route('child.avatar.update'), ['picture_id' => $adult->id])->assertSessionHasErrors('picture_id');
    }
}
