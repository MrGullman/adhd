<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A ZIP with all of a family's data, for a parent to download.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $requested_by
 * @property string $status pending, ready or failed
 * @property string|null $path
 * @property int|null $size
 * @property Carbon|null $finished_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['requested_by'])]
class DataExport extends Model
{
    use BelongsToFamily;

    public const DAYS_KEPT = 7;

    /**
     * The file goes when the export goes.
     */
    protected static function booted(): void
    {
        static::deleted(function (DataExport $export): void {
            if ($export->path !== null) {
                Storage::disk(Picture::DISK)->delete($export->path);
            }
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
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
