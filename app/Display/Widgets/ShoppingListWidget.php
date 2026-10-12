<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Enums\ListType;
use App\Models\DisplayWidget;
use App\Models\FamilyList;
use App\Models\ListItem;

/**
 * Handlingslista: what is left to buy, and who added it.
 */
class ShoppingListWidget implements DisplayWidgetType
{
    private const LIMIT = 8;

    public function type(): string
    {
        return 'shopping_list';
    }

    public function label(): string
    {
        return 'Handlingslista';
    }

    public function defaultSize(): array
    {
        return [3, 4];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $list = FamilyList::query()->where('type', ListType::Shopping)->first();

        $items = $list === null ? collect() : $list->items()
            ->with(['picture', 'adder:id,name'])
            ->whereNull('completed_at')
            ->orderBy('id')
            ->get();

        return [
            'items' => $items->take(self::LIMIT)->map(fn (ListItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'picture' => $item->picture?->toPicker(),
                'addedBy' => $item->adder?->name,
            ])->values()->all(),
            'more' => max(0, $items->count() - self::LIMIT),
        ];
    }
}
