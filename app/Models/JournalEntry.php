<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Dagboken: a parent's note about one or more of the children. Only parents
 * ever read it; it is never shown on the home display. The content is
 * encrypted in the database with the app key.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $author_id
 * @property Carbon $date
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['date', 'content'])]
#[Hidden(['content'])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
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
            'content' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The children the note is about.
     *
     * @return BelongsToMany<User, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'journal_entry_child');
    }
}
