<?php

namespace App\Actions\Pictures;

use App\Enums\PicturePurpose;
use App\Enums\PictureSource;
use App\Jobs\ProcessPicture;
use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Saves an uploaded picture on the private disk and has it converted to
 * WebP in the queue. Without a family it goes into the shared gallery.
 */
class StorePicture
{
    public function upload(UploadedFile $file, string $label, ?Family $family, ?User $uploader, ?int $categoryId = null, PicturePurpose $purpose = PicturePurpose::Support): Picture
    {
        $path = $file->store('pictures/originals', Picture::DISK);

        return $this->fromStoredFile((string) $path, $label, $family, $uploader, $categoryId, $purpose);
    }

    /**
     * For files already on the private disk, e.g. from the admin portal's upload.
     */
    public function fromStoredFile(string $path, string $label, ?Family $family, ?User $uploader, ?int $categoryId = null, PicturePurpose $purpose = PicturePurpose::Support): Picture
    {
        $picture = new Picture(['label' => $label, 'picture_category_id' => $categoryId]);
        $picture->forceFill([
            'family_id' => $family?->id,
            'source' => $family === null ? PictureSource::Gallery : PictureSource::Upload,
            'purpose' => $purpose,
            'path' => $path,
            'uploaded_by' => $uploader?->id,
        ])->save();

        ProcessPicture::dispatch($picture->id, $family?->id);

        return $picture;
    }

    /**
     * A label from a file name: "borsta-tanderna_2.png" becomes "Borsta tanderna 2".
     */
    public static function labelFromFileName(string $fileName): string
    {
        $name = trim((string) preg_replace('/[\s_\-]+/', ' ', pathinfo($fileName, PATHINFO_FILENAME)));

        return mb_substr(mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1), 0, 50) ?: 'Bild';
    }
}
