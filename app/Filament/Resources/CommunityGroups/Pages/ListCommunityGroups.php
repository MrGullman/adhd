<?php

namespace App\Filament\Resources\CommunityGroups\Pages;

use App\Filament\Resources\CommunityGroups\CommunityGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityGroups extends ListRecords
{
    protected static string $resource = CommunityGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
