<?php

namespace App\Http\Controllers;

use App\Enums\ArticleKind;
use App\Models\Article;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Om NPF: public pages about NPF and the everyday, no login needed, so that
 * they can be shared with relatives and school. Written by admins.
 */
class InfoController extends Controller
{
    public function index(): Response
    {
        $intro = Article::query()->published()->where('kind', ArticleKind::Intro)->orderBy('sort_order')->first();

        return Inertia::render('info/Index', [
            'intro' => $intro === null ? null : [
                'title' => $intro->title,
                'summary' => $intro->summary,
                'html' => Article::html($intro->body),
            ],
            'topics' => $this->cards(ArticleKind::Topic),
            'situations' => $this->cards(ArticleKind::Situation),
        ]);
    }

    /**
     * Känner du igen dig?: everyday situations.
     */
    public function situations(): Response
    {
        return Inertia::render('info/Situations', [
            'situations' => $this->cards(ArticleKind::Situation),
        ]);
    }

    public function show(Article $article): Response
    {
        Gate::authorize('view', $article);
        abort_if($article->kind === ArticleKind::Intro, 404);

        return Inertia::render('info/Article', [
            'article' => [
                'slug' => $article->slug,
                'kind' => $article->kind->value,
                'title' => $article->title,
                'summary' => $article->summary,
                'icon' => $article->icon,
                'html' => Article::html($article->body),
                'why' => Article::html($article->why),
                'helps' => Article::html($article->helps),
                'inApp' => Article::html($article->in_app),
            ],
            // The other pages of the same kind, to read on.
            'more' => $this->cards($article->kind, except: $article->id),
        ]);
    }

    /**
     * @return list<array{slug: string, title: string, summary: string|null, icon: string|null}>
     */
    private function cards(ArticleKind $kind, ?int $except = null): array
    {
        return array_values(Article::query()
            ->published()
            ->where('kind', $kind)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Article $article) => [
                'slug' => $article->slug,
                'title' => $article->title,
                'summary' => $article->summary,
                'icon' => $article->icon,
            ])
            ->all());
    }
}
