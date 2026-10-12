<?php

namespace Tests\Feature\Family;

use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\Family;
use App\Models\FamilyInvitation;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\Picture;
use App\Models\RecurringTask;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Family A must never read or change family B's data (docs/ARCHITECTURE.md).
 * Other families' records answer 404, so their existence is not revealed.
 */
class FamilyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_cannot_open_another_familys_child()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherChild = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->get(route('children.show', $otherChild->id))
            ->assertNotFound();
    }

    public function test_a_parent_cannot_change_another_familys_child_code()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherChild = User::factory()->child('4826')->inFamily(Family::factory()->create(), FamilyRole::Child)->create();

        $this->actingAs($parent)
            ->put(route('children.pin.update', $otherChild->id), ['pin' => '7351'])
            ->assertNotFound();

        $this->assertSame('4826', $otherChild->fresh()->pin);
    }

    public function test_a_parent_cannot_remove_another_familys_device()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherDevice = Device::factory()->create();

        $this->actingAs($parent)
            ->delete(route('devices.destroy', $otherDevice->id))
            ->assertNotFound();

        $this->assertDatabaseHas('devices', ['id' => $otherDevice->id]);
    }

    public function test_a_parent_cannot_remove_another_familys_invitation()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherInvitation = FamilyInvitation::factory()->create();

        $this->actingAs($parent)
            ->delete(route('family-invitations.destroy', $otherInvitation->id))
            ->assertNotFound();

        $this->assertDatabaseHas('family_invitations', ['id' => $otherInvitation->id]);
    }

    public function test_a_parent_cannot_open_or_change_another_familys_list()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherList = FamilyList::factory()->create(['name' => 'Deras lista']);
        $otherItem = ListItem::factory()->for($otherList, 'list')->create(['name' => 'Deras vara']);

        $this->actingAs($parent)->get(route('lists.show', $otherList->id))->assertNotFound();
        $this->actingAs($parent)->put(route('lists.update', $otherList->id), ['name' => 'Vår nu'])->assertNotFound();
        $this->actingAs($parent)->post(route('list-items.store', $otherList->id), ['name' => 'Mjölk'])->assertNotFound();
        $this->actingAs($parent)
            ->post(route('list-items.completion.store', [$otherList->id, $otherItem->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('lists', ['id' => $otherList->id, 'name' => 'Deras lista']);
        $this->assertDatabaseMissing('list_items', ['name' => 'Mjölk']);
        $this->assertNull($otherItem->fresh()?->completed_at);
    }

    public function test_a_child_cannot_add_to_another_familys_shopping_list()
    {
        $child = User::factory()->child()->inFamily(Family::factory()->create(), FamilyRole::Child)->create();
        $otherShoppingList = FamilyList::query()->withoutGlobalScopes()
            ->where('family_id', Family::factory()->create()->id)
            ->firstOrFail();

        $this->actingAs($child)
            ->post(route('list-items.store', $otherShoppingList->id), ['name' => 'Godis'])
            ->assertNotFound();

        $this->assertDatabaseMissing('list_items', ['name' => 'Godis']);
    }

    public function test_a_parent_cannot_change_tick_off_or_remove_another_familys_task()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $otherTask = Task::factory()->create(['title' => 'Deras uppgift']);

        $this->actingAs($parent)->get(route('tasks.edit', $otherTask->id))->assertNotFound();
        $this->actingAs($parent)
            ->put(route('tasks.update', $otherTask->id), ['title' => 'Vår nu', 'date' => '2026-10-14'])
            ->assertNotFound();
        $this->actingAs($parent)->post(route('tasks.completion.store', $otherTask->id))->assertNotFound();
        $this->actingAs($parent)->delete(route('tasks.destroy', $otherTask->id))->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $otherTask->id, 'title' => 'Deras uppgift', 'completed_at' => null]);
    }

    public function test_a_parent_cannot_open_change_or_remove_another_familys_recurring_task()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $other = RecurringTask::factory()->create(['title' => 'Deras']);

        $this->actingAs($parent)->get(route('recurring-tasks.edit', $other->id))->assertNotFound();
        $this->actingAs($parent)
            ->put(route('recurring-tasks.update', $other->id), ['title' => 'Vår nu', 'frequency' => 'daily', 'starts_on' => '2026-10-12'])
            ->assertNotFound();
        $this->actingAs($parent)->delete(route('recurring-tasks.destroy', $other->id))->assertNotFound();

        $this->assertDatabaseHas('recurring_tasks', ['id' => $other->id, 'title' => 'Deras']);
    }

    public function test_a_parent_cannot_open_change_or_remove_another_familys_routine()
    {
        $parent = User::factory()->inFamily(Family::factory()->create())->create();
        $other = Routine::factory()->withSteps([['Deras steg']])->create(['name' => 'Deras rutin']);

        $this->actingAs($parent)->get(route('routines.edit', $other->id))->assertNotFound();
        $this->actingAs($parent)
            ->put(route('routines.update', $other->id), ['name' => 'Vår nu', 'steps' => [['label' => 'X', 'type' => 'step']]])
            ->assertNotFound();
        $this->actingAs($parent)->delete(route('routines.destroy', $other->id))->assertNotFound();

        $this->assertDatabaseHas('routines', ['id' => $other->id, 'name' => 'Deras rutin']);
    }

    public function test_routines_are_the_current_familys_and_never_templates()
    {
        $family = Family::factory()->create();
        Routine::factory()->for($family)->create(['name' => 'Vår rutin']);
        Routine::factory()->create(['name' => 'Deras rutin']);

        app(CurrentFamily::class)->set($family);

        $this->assertSame(['Vår rutin'], Routine::query()->pluck('name')->all());
    }

    public function test_lists_items_and_tasks_only_return_the_current_familys_rows()
    {
        $family = Family::factory()->create();
        $list = FamilyList::factory()->for($family)->create(['name' => 'Vår lista']);
        ListItem::factory()->for($list, 'list')->create(['name' => 'Vår vara']);
        Task::factory()->for($family)->create(['title' => 'Vår uppgift']);

        $otherList = FamilyList::factory()->create(['name' => 'Deras lista']);
        ListItem::factory()->for($otherList, 'list')->create(['name' => 'Deras vara']);
        Task::factory()->create(['title' => 'Deras uppgift']);
        RecurringTask::factory()->for($family)->create(['title' => 'Vår återkommande']);
        RecurringTask::factory()->create(['title' => 'Deras återkommande']);

        app(CurrentFamily::class)->set($family);

        $this->assertSame(['Handlingslista', 'Vår lista'], FamilyList::query()->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Vår vara'], ListItem::query()->pluck('name')->all());
        $this->assertSame(['Vår uppgift'], Task::query()->pluck('title')->all());
        $this->assertSame(['Vår återkommande'], RecurringTask::query()->pluck('title')->all());
    }

    public function test_pictures_are_the_gallery_and_the_current_familys_own_photos()
    {
        $family = Family::factory()->create();
        Picture::factory()->create(['label' => 'Galleriet']);
        Picture::factory()->ofFamily($family)->create(['label' => 'Vårt foto']);
        Picture::factory()->ofFamily(Family::factory()->create())->create(['label' => 'Deras foto']);

        $this->assertSame(['Galleriet'], Picture::query()->pluck('label')->all(), 'without a family only the gallery');

        app(CurrentFamily::class)->set($family);

        $this->assertSame(['Galleriet', 'Vårt foto'], Picture::query()->orderBy('label')->pluck('label')->all());
    }

    public function test_family_scoped_models_only_return_the_current_familys_rows()
    {
        $family = Family::factory()->create();
        Device::factory()->for($family)->create(['name' => 'Vår platta']);
        Device::factory()->create(['name' => 'Någon annans platta']);

        app(CurrentFamily::class)->set($family);

        $this->assertSame(['Vår platta'], Device::query()->pluck('name')->all());
    }

    public function test_family_scoped_models_return_nothing_without_a_current_family()
    {
        Device::factory()->create();

        $this->assertSame(0, Device::query()->count());
    }

    public function test_new_family_scoped_models_get_the_current_family()
    {
        $family = Family::factory()->create();
        app(CurrentFamily::class)->set($family);

        [$device] = Device::issue(DeviceType::Family, 'Köksplattan');

        $this->assertSame($family->id, $device->family_id);
    }
}
