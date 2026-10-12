<?php

namespace Tests\Feature\Pictures;

use App\Actions\Pictures\StorePicture;
use App\Filament\Resources\Pictures\Pages\ListPictures;
use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_reach_the_admin_portal()
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $this->actingAs($parent)->get('/admin')->assertForbidden();

        $parent->forceFill(['is_admin' => true])->save();
        $this->actingAs($parent)->get('/admin')->assertOk();
    }

    public function test_the_admin_gallery_never_shows_families_photos()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $gallery = Picture::factory()->create(['label' => 'Tandborste']);
        $familyPhoto = Picture::factory()->ofFamily(Family::factory()->create())->create(['label' => 'Familjens foto']);

        $this->actingAs($admin);

        Livewire::test(ListPictures::class)
            ->assertCanSeeTableRecords([$gallery])
            ->assertCanNotSeeTableRecords([$familyPhoto]);
    }

    public function test_an_uploaded_gallery_picture_is_converted_and_shared()
    {
        Storage::fake(Picture::DISK);
        $png = imagecreatetruecolor(800, 800);
        ob_start();
        imagepng($png);
        Storage::disk(Picture::DISK)->put('pictures/originals/borsta.png', (string) ob_get_clean());

        $picture = app(StorePicture::class)->fromStoredFile('pictures/originals/borsta.png', 'Borsta tänderna', null, null);

        $picture = Picture::query()->findOrFail($picture->id);
        $this->assertNull($picture->family_id);
        $this->assertStringStartsWith('pictures/galleri/', $picture->path);
        Storage::disk(Picture::DISK)->assertMissing('pictures/originals/borsta.png');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function fileNames(): array
    {
        return [
            'bindestreck' => ['borsta-tanderna.png', 'Borsta tanderna'],
            'understreck och siffra' => ['mjolk_2.jpg', 'Mjolk 2'],
            'svenska tecken' => ['ägg.webp', 'Ägg'],
        ];
    }

    #[DataProvider('fileNames')]
    public function test_the_file_name_becomes_the_label(string $fileName, string $label)
    {
        $this->assertSame($label, StorePicture::labelFromFileName($fileName));
    }

    public function test_a_separate_admin_account_is_created_on_the_server()
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Namn', 'Admin')
            ->expectsQuestion('E-post', 'admin@exempel.se')
            ->expectsQuestion('Lösenord (minst 8 tecken)', 'ett-langt-losenord-77')
            ->expectsQuestion('Samma lösenord igen', 'ett-langt-losenord-77')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@exempel.se')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(0, $admin->families()->count());

        // Without a family the admin goes to the admin portal, not to "Skapa familj".
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect('/admin');
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_an_admin_account_needs_a_valid_password()
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Namn', 'Admin')
            ->expectsQuestion('E-post', 'admin@exempel.se')
            ->expectsQuestion('Lösenord (minst 8 tecken)', 'kort')
            ->expectsQuestion('Samma lösenord igen', 'kort')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@exempel.se']);
    }

    public function test_an_admin_is_made_on_the_server_with_a_command()
    {
        $user = User::factory()->create(['email' => 'vuxen@exempel.se']);

        $this->artisan('users:admin', ['email' => 'vuxen@exempel.se'])->assertSuccessful();
        $this->assertTrue($user->fresh()?->is_admin);

        $this->artisan('users:admin', ['email' => 'vuxen@exempel.se', '--remove' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()?->is_admin);
    }
}
