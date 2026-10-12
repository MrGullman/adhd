<?php

namespace App\Policies;

use App\Models\Picture;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class PicturePolicy
{
    use ResolvesFamilyRole;

    /**
     * Everyone in a family sees the gallery and the family's own photos.
     */
    public function view(User $user, Picture $picture): bool
    {
        return $picture->isGallery() || $this->roleIn($user, (int) $picture->family_id) !== null;
    }

    /**
     * Upload the family's own photos.
     */
    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }
}
