<?php

namespace App\Jobs;

use App\Models\Family;
use App\Models\Picture;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Turns an uploaded picture into a small WebP: turned the right way up,
 * at most 600 px and without camera data (e.g. where the photo was taken).
 */
class ProcessPicture implements ShouldQueue
{
    use Queueable;

    /**
     * Largest side in pixels. Enough for the biggest picture on the home display.
     */
    public const MAX_SIZE = 600;

    public int $tries = 3;

    /**
     * The family is passed along so that the picture is looked up within it,
     * like on any page. Null for the shared gallery.
     */
    public function __construct(public int $pictureId, public ?int $familyId) {}

    public function handle(CurrentFamily $current): void
    {
        $family = $this->familyId === null ? null : Family::find($this->familyId);

        if ($family !== null) {
            $current->set($family);
        }

        try {
            $picture = Picture::query()
                ->whereKey($this->pictureId)
                ->where('family_id', $this->familyId)
                ->first();

            if ($picture === null || $picture->processed_at !== null) {
                return;
            }

            $this->convert($picture);
        } finally {
            $current->forget();
        }
    }

    private function convert(Picture $picture): void
    {
        $disk = Storage::disk(Picture::DISK);
        $original = $picture->path;

        $webp = ImageManager::usingDriver(GdDriver::class)
            ->decodeBinary((string) $disk->get($original))
            ->scaleDown(self::MAX_SIZE, self::MAX_SIZE)
            ->encode(new WebpEncoder(quality: 80, strip: true));

        $folder = $picture->family_id === null ? 'galleri' : "familjer/{$picture->family_id}";
        $path = "pictures/{$folder}/".Str::uuid().'.webp';

        $disk->put($path, $webp->toString());

        $picture->forceFill(['path' => $path, 'processed_at' => now()])->save();

        $disk->delete($original);
    }
}
