<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\DisplayWidgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A widget on a home display, placed in a 12-column grid.
 *
 * @property int $id
 * @property int $family_id
 * @property int $device_id
 * @property string $type
 * @property int $x
 * @property int $y
 * @property int $w
 * @property int $h
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'x', 'y', 'w', 'h', 'settings'])]
class DisplayWidget extends Model
{
    /** @use HasFactory<DisplayWidgetFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Columns and rows of the grid. Density changes the size of things, not the grid.
     */
    public const COLUMNS = 12;

    public const ROWS = 6;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'w' => 'integer',
            'h' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function setting(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }
}
