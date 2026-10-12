<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One meal on one day, e.g. Lunch with "Lunch 1" and "Lunch 2". Always
 * reached through the family's school menus.
 *
 * @property int $id
 * @property int $school_menu_id
 * @property Carbon $date
 * @property string $meal_name
 * @property string|null $holiday_name
 * @property list<array{name: string, option: string|null}> $courses
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['date', 'meal_name', 'holiday_name', 'courses', 'fetched_at'])]
class SchoolMeal extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'courses' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SchoolMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(SchoolMenu::class, 'school_menu_id');
    }
}
