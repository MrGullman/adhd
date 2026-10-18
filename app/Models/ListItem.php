<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use App\Observers\ListItemObserver;
use Database\Factories\ListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property int $list_id
 * @property int|null $picture_id
 * @property string $name
 * @property int|null $ingredient_id
 * @property string|null $quantity
 * @property string|null $unit
 * @property Carbon|null $meal_plan_week
 * @property string|null $for_meals
 * @property int|null $added_by
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property string|null $ica_row_id The row in the family's ICA list, when connected.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(ListItemObserver::class)]
#[Fillable(['name', 'picture_id', 'added_by', 'ingredient_id', 'quantity', 'unit', 'meal_plan_week', 'for_meals'])]
class ListItem extends Model
{
    /** @use HasFactory<ListItemFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'meal_plan_week' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<FamilyList, $this>
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(FamilyList::class, 'list_id');
    }

    /**
     * Bildstöd.
     *
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * The grocery, when known: from the meal plan, or chosen when added.
     *
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function isFromMealPlan(): bool
    {
        return $this->meal_plan_week !== null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function complete(User $user): void
    {
        $this->forceFill(['completed_at' => now(), 'completed_by' => $user->id])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['completed_at' => null, 'completed_by' => null])->save();
    }
}
