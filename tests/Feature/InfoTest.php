<?php

namespace Tests\Feature;

use App\Enums\ArticleKind;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Models\Article;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Om NPF: public pages, no login, written by admins in the admin portal.
 */
class InfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_reads_om_npf_without_logging_in()
    {
        $this->get(route('info.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('info/Index')
            ->where('intro.title', 'Välkommen till NPF Familjen')
            ->where('topics.0.slug', 'vad-ar-npf')
            ->has('topics', 7)
            ->has('situations', 6));

        $this->get(route('info.show', 'adhd'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('info/Article')
            ->where('article.title', 'Att förstå ADHD')
            ->where('article.html', fn (string $html) => str_contains($html, '<h2>Vad kan hjälpa?</h2>')));

        $this->get(route('info.show', 'brak-pa-morgonen'))->assertInertia(fn (Assert $page) => $page
            ->where('article.kind', 'situation')
            ->where('article.why', fn (string $html) => str_contains($html, '<p>'))
            ->where('article.inApp', fn (string $html) => str_contains($html, 'morgonrutin')));

        $this->get(route('info.situations'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('situations', 6));
    }

    public function test_unpublished_pages_and_the_welcome_text_are_not_shown_on_their_own()
    {
        $draft = Article::factory()->create(['slug' => 'utkast', 'published' => false]);

        $this->get(route('info.show', 'utkast'))->assertForbidden();
        $this->get(route('info.show', 'valkommen'))->assertNotFound();
        $this->get(route('info.index'))->assertInertia(fn (Assert $page) => $page
            ->where('topics', fn ($topics) => collect($topics)->pluck('slug')->doesntContain('utkast')));

        // An admin can look at a draft before publishing it.
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('info.show', $draft->slug))->assertOk();
    }

    public function test_html_in_the_text_is_removed()
    {
        Article::factory()->create(['slug' => 'test', 'body' => 'Hej <script>alert(1)</script> och [länk](javascript:alert(1)).']);

        $this->get(route('info.show', 'test'))->assertInertia(fn (Assert $page) => $page
            ->where('article.html', fn (string $html) => ! str_contains($html, '<script') && ! str_contains($html, 'javascript:')));
    }

    public function test_an_admin_writes_a_new_situation_and_others_cannot()
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'kind' => ArticleKind::Situation->value,
                'title' => 'Läggdags drar ut på tiden',
                'slug' => 'laggdags',
                'summary' => 'Kvällen blir sen varje kväll.',
                'why' => 'Därför.',
                'helps' => 'Det här.',
                'in_app' => 'En kvällsrutin.',
                'sort_order' => 20,
                'published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(ArticleKind::Situation, Article::query()->where('slug', 'laggdags')->value('kind'));

        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $this->actingAs($parent)->get('/admin/articles')->assertForbidden();
    }
}
