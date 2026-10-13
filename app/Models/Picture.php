<?php

namespace App\Models;

use App\Enums\PictureSource;
use App\Models\Scopes\SharedOrFamilyScope;
use Database\Factories\PictureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Bildstöd: a picture from the shared gallery (family_id null) or one of the
 * family's own photos. Files are on the private disk and served through
 * PictureController, which checks access.
 *
 * Not BelongsToFamily: the gallery is shared, so SharedOrFamilyScope shows the
 * gallery and the current family's photos. family_id is always set explicitly.
 *
 * @property int $id
 * @property int|null $family_id
 * @property int|null $picture_category_id
 * @property PictureSource $source
 * @property string $path
 * @property Carbon|null $processed_at
 * @property string $label
 * @property string|null $keywords
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['picture_category_id', 'label', 'keywords'])]
#[ScopedBy([SharedOrFamilyScope::class])]
class Picture extends Model
{
    /** @use HasFactory<PictureFactory> */
    use HasFactory;

    /**
     * The private disk the files are on.
     */
    public const DISK = 'local';

    /**
     * The file goes when the picture goes.
     */
    protected static function booted(): void
    {
        static::deleted(function (Picture $picture): void {
            Storage::disk(self::DISK)->delete($picture->path);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => PictureSource::class,
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PictureCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PictureCategory::class, 'picture_category_id');
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function isGallery(): bool
    {
        return $this->family_id === null;
    }

    /**
     * Where the picture is shown. The version makes browsers fetch it again
     * after it has been converted or replaced.
     */
    public function url(): string
    {
        return route('pictures.show', [$this, 'v' => $this->updated_at?->getTimestamp()]);
    }

    /**
     * The picture as sent to pages.
     *
     * @return array{id: int, label: string, url: string}
     */
    public function toPicker(): array
    {
        return ['id' => $this->id, 'label' => $this->label, 'url' => $this->url()];
    }
}
