<?php

namespace App\Enums;

enum FamilyRole: string
{
    case Parent = 'parent';
    case Editor = 'editor';
    case Viewer = 'viewer';
    case Child = 'child';

    /**
     * Roles a parent can invite another adult to.
     *
     * @return array<int, self>
     */
    public static function adultRoles(): array
    {
        return [self::Parent, self::Editor, self::Viewer];
    }

    public function label(): string
    {
        return match ($this) {
            self::Parent => 'Vårdnadshavare',
            self::Editor => 'Vuxen som kan ändra',
            self::Viewer => 'Vuxen som kan se',
            self::Child => 'Barn',
        };
    }

    public function isAdult(): bool
    {
        return $this !== self::Child;
    }

    /**
     * Whether the role may change tasks, routines, activities and food.
     */
    public function canEdit(): bool
    {
        return $this === self::Parent || $this === self::Editor;
    }

    /**
     * Whether the role may manage members, child codes and devices.
     */
    public function canManage(): bool
    {
        return $this === self::Parent;
    }
}
