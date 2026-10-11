<?php

namespace App\Support;

use App\Enums\FamilyRole;
use App\Models\Device;
use App\Models\Family;

/**
 * The family the current request acts for. Set once per request by
 * ResolveCurrentFamily and read by BelongsToFamily to scope every query.
 */
class CurrentFamily
{
    private ?Family $family = null;

    private ?FamilyRole $role = null;

    private ?Device $device = null;

    public function set(Family $family, ?FamilyRole $role = null): void
    {
        $this->family = $family;
        $this->role = $role;
    }

    public function setDevice(?Device $device): void
    {
        $this->device = $device;
    }

    public function forget(): void
    {
        $this->family = null;
        $this->role = null;
        $this->device = null;
    }

    public function get(): ?Family
    {
        return $this->family;
    }

    /**
     * The current family on routes guarded by the "family" middleware.
     */
    public function getOrFail(): Family
    {
        return $this->family ?? abort(404);
    }

    public function id(): ?int
    {
        return $this->family?->id;
    }

    /**
     * The authenticated user's role in the current family.
     */
    public function role(): ?FamilyRole
    {
        return $this->role;
    }

    /**
     * The trusted device the request comes from, if any.
     */
    public function device(): ?Device
    {
        return $this->device;
    }
}
