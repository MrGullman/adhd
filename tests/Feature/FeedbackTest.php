<?php

namespace Tests\Feature;

use App\Enums\FamilyRole;
use App\Filament\Resources\Feedback\Pages\ListFeedback;
use App\Models\Family;
use App\Models\Feedback;
use App\Models\User;
use App\Privacy\DeleteFamily;
use App\Support\FeedbackButton;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create(['name' => 'Kim']);
    }

    public function test_an_adult_sends_feedback_with_the_page_it_was_written_on()
    {
        $this->actingAs($this->parent)
            ->postJson(route('feedback.store'), [
                'message' => 'Jag vill kunna sortera skafferiet efter datum.',
                'page' => 'food/Pantry',
                'url' => '/mat/skafferi',
            ])
            ->assertCreated();

        $feedback = Feedback::query()->sole();
        $this->assertSame($this->parent->id, $feedback->user_id);
        $this->assertSame($this->family->id, $feedback->family_id);
        $this->assertSame('food/Pantry', $feedback->page);
        $this->assertSame('/mat/skafferi', $feedback->url);
        $this->assertNull($feedback->handled_at);
    }

    public function test_the_button_follows_the_admins_choice()
    {
        $feedback = fn () => $this->actingAs($this->parent)->get(route('dashboard'))->viewData('page')['props']['feedback'];

        $this->assertSame('development', $feedback()['mode'], 'pages under development from the start');
        $this->assertContains('food/Inventory', $feedback()['pages']);

        FeedbackButton::set(FeedbackButton::OFF);
        $this->assertSame('off', $feedback()['mode']);

        FeedbackButton::set(FeedbackButton::ALL);
        $this->assertSame('all', $feedback()['mode']);
    }

    public function test_children_never_get_the_button_or_send_feedback()
    {
        $child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create();

        $this->actingAs($child)->get(route('child.home'))->assertInertia(fn (Assert $page) => $page->where('feedback', null));
        $this->actingAs($child)
            ->postJson(route('feedback.store'), ['message' => 'Hej', 'page' => 'child/Home', 'url' => '/min-dag'])
            ->assertStatus(302);

        $this->assertSame(0, Feedback::query()->count());
    }

    public function test_admins_read_handle_and_choose_where_the_button_is()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->parent)->postJson(route('feedback.store'), ['message' => 'Saknar sökfält', 'page' => 'food/MealPlan', 'url' => '/mat/vecka']);
        $feedback = Feedback::query()->sole();

        $this->actingAs($admin);
        Livewire::test(ListFeedback::class)
            ->assertCanSeeTableRecords([$feedback])
            ->callTableAction('handle', $feedback)
            ->callAction('button', data: ['mode' => FeedbackButton::OFF])
            ->assertHasNoActionErrors();

        $this->assertNotNull($feedback->fresh()?->handled_at);
        $this->assertSame(FeedbackButton::OFF, FeedbackButton::mode());

        $this->actingAs($this->parent)->get('/admin/feedback')->assertForbidden();
    }

    public function test_feedback_goes_with_the_family()
    {
        $this->actingAs($this->parent)->postJson(route('feedback.store'), ['message' => 'Bra app', 'page' => 'today/Index', 'url' => '/dashboard']);

        app(DeleteFamily::class)->handle($this->family);

        $this->assertSame(0, Feedback::query()->count());
    }
}
