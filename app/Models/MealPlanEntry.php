<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\MealPlanEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A meal on the family's week: a recipe with portions, or free text such as
 * "Pizza ute". Never mixed up with school meals.
 *
 * @property int $id
 * @property int $family_id
 * @property Carbon $date
 * @property string $meal
 * @property int|null $recipe_id
 * @property string|null $title
 * @property int $portions
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['date', 'meal', 'recipe_id', 'title', 'portions'])]
class MealPlanEntry extends Model
{
    /** @use HasFactory<MealPlanEntryFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'portions' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function name(): string
    {
        return $this->recipe->name ?? $this->title ?? 'Middag';
    }

    /**
     * For the pages: name, picture and portions.
     *
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'name' => $this->name(),
            'recipeId' => $this->recipe_id,
            'picture' => $this->recipe?->picture?->toPicker(),
            'portions' => $this->portions,
        ];
    }
}
