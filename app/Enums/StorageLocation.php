<?php

namespace App\Enums;

/**
 * Where something is kept at home, for the pantry and for taking stock.
 */
enum StorageLocation: string
{
    case Fridge = 'kyl';
    case Freezer = 'frys';
    case Pantry = 'skafferi';
    case Other = 'annat';

    public function label(): string
    {
        return match ($this) {
            self::Fridge => 'Kylskåp',
            self::Freezer => 'Frys',
            self::Pantry => 'Skafferi',
            self::Other => 'Annat',
        };
    }

    /**
     * Where a grocery usually goes, e.g. dairy in the fridge.
     */
    public static function for(?StoreSection $section): self
    {
        return match ($section) {
            StoreSection::Dairy, StoreSection::Meat, StoreSection::Produce => self::Fridge,
            StoreSection::Frozen => self::Freezer,
            StoreSection::Household, StoreSection::Other, null => self::Other,
            default => self::Pantry,
        };
    }
}
