<?php

namespace App\Filament\Resources\CommunityGroups\Pages;

use App\Filament\Resources\CommunityGroups\CommunityGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityGroup extends EditRecord
{
    protected static string $resource = CommunityGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription('Alla trådar och inlägg i gruppen tas bort.'),
        ];
    }
}
