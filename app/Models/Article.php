<?php

namespace App\Models;

use App\Enums\ArticleKind;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A page in Om NPF, the public knowledge library that admins write in the
 * admin portal. Text is Markdown. A situation ("Känner du igen dig?") has
 * three parts: why it can happen, what can help and how the app can help.
 *
 * @property int $id
 * @property ArticleKind $kind
 * @property string $slug
 * @property string $title
 * @property string|null $summary
 * @property string|null $icon
 * @property string|null $body
 * @property string|null $why
 * @property string|null $helps
 * @property string|null $in_app
 * @property int $sort_order
 * @property bool $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['kind', 'slug', 'title', 'summary', 'icon', 'body', 'why', 'helps', 'in_app', 'sort_order', 'published'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /** Icons an article can have on its card (Lucide names, see info/icons.ts). */
    public const ICONS = ['brain', 'zap', 'puzzle', 'house', 'school', 'user', 'users', 'heart-handshake', 'sparkles', 'sun', 'clock', 'book-open'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ArticleKind::class,
            'sort_order' => 'integer',
            'published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Article>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('published', true);
    }

    /**
     * Markdown as safe HTML: raw HTML in the text is removed, and so are unsafe links.
     */
    public static function html(?string $markdown): ?string
    {
        return $markdown === null || trim($markdown) === ''
            ? null
            : Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
