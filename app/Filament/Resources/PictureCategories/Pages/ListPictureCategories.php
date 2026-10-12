<?php

namespace App\Filament\Resources\PictureCategories\Pages;

use App\Filament\Resources\PictureCategories\PictureCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPictureCategories extends ListRecords
{
    protected static string $resource = PictureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
