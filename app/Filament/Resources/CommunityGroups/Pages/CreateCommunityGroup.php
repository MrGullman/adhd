<?php

namespace App\Filament\Resources\CommunityGroups\Pages;

use App\Filament\Resources\CommunityGroups\CommunityGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityGroup extends CreateRecord
{
    protected static string $resource = CommunityGroupResource::class;
}
