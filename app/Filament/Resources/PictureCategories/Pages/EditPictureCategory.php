<?php

namespace App\Filament\Resources\PictureCategories\Pages;

use App\Filament\Resources\PictureCategories\PictureCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPictureCategory extends EditRecord
{
    protected static string $resource = PictureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
