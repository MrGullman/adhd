<?php

namespace App\Policies;

use App\Enums\ChildView;
use App\Enums\FamilyRole;
use App\Models\Task;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class TaskPolicy
{
    use ResolvesFamilyRole;

    /**
     * Adults see every task. Children see their own.
     */
    public function view(User $user, Task $task): bool
    {
        $role = $this->roleIn($user, $task->family_id);

        return ($role?->isAdult() ?? false)
            || ($role === FamilyRole::Child && $task->assigned_to === $user->id);
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    /**
     * A child in läget Ungdom adds tasks for themselves.
     */
    public function createOwn(User $user): bool
    {
        return $this->currentRole($user) === FamilyRole::Child && $user->child_view === ChildView::Youth;
    }

    /**
     * A child removes a task they added themselves.
     */
    public function deleteOwn(User $user, Task $task): bool
    {
        return $this->roleIn($user, $task->family_id) === FamilyRole::Child
            && $task->isOwnTask()
            && $task->assigned_to === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->roleIn($user, $task->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Tick a task off or back on. Children tick off their own tasks.
     */
    public function complete(User $user, Task $task): bool
    {
        $role = $this->roleIn($user, $task->family_id);

        return ($role?->canEdit() ?? false)
            || ($role === FamilyRole::Child && $task->assigned_to === $user->id);
    }
}
