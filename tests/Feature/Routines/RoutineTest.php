<?php

namespace Tests\Feature\Routines;

use App\Enums\FamilyRole;
use App\Filament\Resources\RoutineTemplates\Pages\ListRoutineTemplates;
use App\Models\Family;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class RoutineTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 14 October 2026, 07:00 in Sweden.
        $this->travelTo('2026-10-14 05:00:00');

        $this->family = Family::factory()->create();
        $this->parent = User::factory()->inFamily($this->family)->create();
        $this->child = User::factory()->child()->inFamily($this->family, FamilyRole::Child)->create(['name' => 'Alva']);
    }

    private function stored(string $name): Routine
    {
        return Routine::query()->withoutGlobalScopes()->where('name', $name)->whereNotNull('family_id')->firstOrFail();
    }

    /**
     * @param  list<array{0: string, 1?: int, 2?: bool}>  $steps
     */
    private function routine(array $steps, array $attributes = []): Routine
    {
        return Routine::factory()->for($this->family)->withSteps($steps)->forChild($this->child)->create($attributes);
    }

    public function test_a_family_starts_from_a_template_and_gets_its_own_copy()
    {
        $template = Routine::templates()->where('name', 'Morgonrutin')->firstOrFail();

        $this->actingAs($this->parent)
            ->post(route('routine-templates.copy', $template->id))
            ->assertRedirect();

        $copy = $this->stored('Morgonrutin');
        $this->assertSame($this->family->id, $copy->family_id);
        $this->assertSame(8, $copy->steps()->count());
        $this->assertSame(8, $template->steps()->count(), 'the template is left as it is');
    }

    public function test_an_editor_creates_a_routine_with_steps()
    {
        $editor = User::factory()->inFamily($this->family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->post(route('routines.store'), [
                'name' => 'Läggdags',
                'children' => [$this->child->id],
                'done_by' => '20:00',
                'weekdays' => [1, 2, 3, 4, 5, 6, 7],
                'ordered' => true,
                'steps' => [
                    ['label' => 'Ta på pyjamas', 'minutes' => 5, 'type' => 'step', 'optional' => false, 'weekdays' => []],
                    ['label' => 'Läsa en bok', 'minutes' => 15, 'type' => 'step', 'optional' => true, 'weekdays' => [5, 6]],
                ],
            ])
            ->assertRedirect(route('routines.index'));

        $routine = $this->stored('Läggdags');
        $this->assertNull($routine->weekdays, 'every day is stored as null');
        $this->assertSame([$this->child->id], $routine->children()->pluck('users.id')->all());
        $this->assertSame(['Ta på pyjamas', 'Läsa en bok'], $routine->steps()->pluck('label')->all());
        $this->assertTrue($routine->steps()->where('label', 'Läsa en bok')->value('optional'));
        $this->assertSame([5, 6], $routine->steps()->where('label', 'Läsa en bok')->firstOrFail()->weekdays);
    }

    public function test_only_the_familys_children_can_do_a_routine()
    {
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        foreach ([$this->parent->id, $otherChild->id] as $id) {
            $this->actingAs($this->parent)
                ->post(route('routines.store'), [
                    'name' => 'Morgon',
                    'children' => [$id],
                    'steps' => [['label' => 'Gå upp', 'type' => 'step']],
                ])
                ->assertSessionHasErrors('children.0');
        }
    }

    public function test_a_viewer_cannot_create_routines()
    {
        $viewer = User::factory()->inFamily($this->family, FamilyRole::Viewer)->create();

        $this->actingAs($viewer)
            ->post(route('routines.store'), ['name' => 'Morgon', 'steps' => [['label' => 'Gå upp', 'type' => 'step']]])
            ->assertForbidden();
    }

    public function test_changing_a_routine_keeps_what_is_done_today()
    {
        $routine = $this->routine([['Gå upp'], ['Klä på dig'], ['Äta frukost']]);
        [$up, $dress, $breakfast] = $routine->steps()->get()->all();

        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $up]));

        $this->actingAs($this->parent)
            ->put(route('routines.update', $routine), [
                'name' => 'Morgonrutin',
                'children' => [$this->child->id],
                'ordered' => true,
                'steps' => [
                    ['id' => $up->id, 'label' => 'Vakna', 'type' => 'step'],
                    ['id' => $breakfast->id, 'label' => 'Äta frukost', 'type' => 'step'],
                    ['label' => 'Borsta tänderna', 'type' => 'step'],
                ],
            ])
            ->assertRedirect(route('routines.index'));

        $this->assertSame(['Vakna', 'Äta frukost', 'Borsta tänderna'], $routine->steps()->pluck('label')->all());
        $this->assertDatabaseMissing('routine_steps', ['id' => $dress->id]);
        $this->assertDatabaseHas('routine_step_checks', ['routine_step_id' => $up->id]);
    }

    public function test_the_child_does_the_routine_one_step_at_a_time()
    {
        $routine = $this->routine([['Gå upp'], ['Klä på dig'], ['Äta frukost']]);
        [$up, $dress, $breakfast] = $routine->steps()->get()->all();

        $this->actingAs($this->child)
            ->get(route('child.routines.show', $routine))
            ->assertInertia(fn (Assert $page) => $page
                ->component('child/Routine')
                ->where('routine.currentStepId', $up->id)
                ->where('routine.nextStepId', $dress->id)
                ->where('routine.minutesLeft', 45),
            );

        foreach ([$up, $dress, $breakfast] as $step) {
            $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $step]));
        }

        $this->assertNotNull(DB::table('routine_runs')->where('routine_id', $routine->id)->value('completed_at'));

        $this->actingAs($this->child)->delete(route('child.routines.steps.uncheck', [$routine, $breakfast]));

        $this->assertNull(DB::table('routine_runs')->where('routine_id', $routine->id)->value('completed_at'));
    }

    public function test_steps_for_certain_days_only_come_those_days()
    {
        $routine = $this->routine([['Gå upp'], ['Packa gympapåsen']]);
        // Only on Mondays; today is a Wednesday.
        $routine->steps()->where('label', 'Packa gympapåsen')->update(['weekdays' => json_encode([1])]);

        $this->actingAs($this->child)
            ->get(route('child.routines.show', $routine))
            ->assertInertia(fn (Assert $page) => $page
                ->has('routine.steps', 1)
                ->where('routine.steps.0.label', 'Gå upp'),
            );
    }

    public function test_when_time_is_short_optional_steps_can_be_skipped()
    {
        // 30 minutes of steps, 20 minutes left until 07:45.
        $this->travelTo('2026-10-14 05:25:00');
        $routine = $this->routine([['Klä på dig', 10], ['Kamma håret', 10, true], ['Äta frukost', 10]]);
        [, $hair, $breakfast] = $routine->steps()->get()->all();

        $this->actingAs($this->child)
            ->get(route('child.routines.show', $routine))
            ->assertInertia(fn (Assert $page) => $page
                ->where('routine.minutesLeft', 20)
                ->where('routine.skippable', [$hair->id]),
            );

        $this->actingAs($this->child)->post(route('child.routines.steps.skip', [$routine, $hair]));
        $this->assertDatabaseHas('routine_step_checks', ['routine_step_id' => $hair->id, 'skipped' => true]);

        // Only optional steps can be skipped.
        $this->actingAs($this->child)
            ->post(route('child.routines.steps.skip', [$routine, $breakfast]))
            ->assertForbidden();
    }

    public function test_a_child_only_does_their_own_routines()
    {
        $sibling = User::factory()->child('7351')->inFamily($this->family, FamilyRole::Child)->create();
        $routine = Routine::factory()->for($this->family)->withSteps([['Gå upp']])->forChild($sibling)->create();
        $step = $routine->steps()->firstOrFail();

        $this->actingAs($this->child)->get(route('child.routines.show', $routine))->assertForbidden();
        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $step]))->assertForbidden();
    }

    public function test_the_routine_it_is_time_for_comes_first_in_the_childs_day()
    {
        $this->routine([['Pyjamas']], ['name' => 'Läggdags', 'day_part' => 'evening', 'done_by' => '20:00:00']);
        $this->routine([['Gå upp'], ['Klä på dig']], ['name' => 'Morgonrutin']);

        $this->actingAs($this->child)
            ->get(route('child.home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('routines.0.name', 'Morgonrutin')
                ->where('routines.0.due', true)
                ->where('routines.1.name', 'Läggdags')
                ->where('routines.1.due', false),
            );
    }

    public function test_the_adults_follow_the_childrens_routines_on_idag()
    {
        $routine = $this->routine([['Gå upp'], ['Klä på dig']]);
        $this->actingAs($this->child)->post(route('child.routines.steps.check', [$routine, $routine->steps()->firstOrFail()]));

        $this->actingAs($this->parent)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('routines.0.child.name', 'Alva')
                ->where('routines.0.progress', ['total' => 2, 'completed' => 1])
                ->where('routines.0.currentStep', 'Klä på dig'),
            );
    }

    public function test_the_admin_portal_shows_templates_but_never_family_routines()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $familyRoutine = $this->routine([['Gå upp']], ['name' => 'Familjens morgon']);

        $this->actingAs($admin);

        Livewire::test(ListRoutineTemplates::class)
            ->assertCanSeeTableRecords(Routine::templates()->get())
            ->assertCanNotSeeTableRecords([$familyRoutine]);
    }

    public function test_a_family_routine_cannot_be_copied_as_a_template()
    {
        $other = Routine::factory()->withSteps([['Hemligt']])->create();

        $this->actingAs($this->parent)
            ->post(route('routine-templates.copy', $other->id))
            ->assertNotFound();
    }
}
