<?php

namespace App\Filament\Resources\Pictures\Pages;

use App\Actions\Pictures\StorePicture;
use App\Filament\Resources\Pictures\PictureResource;
use App\Models\Picture;
use App\Models\PictureCategory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPictures extends ListRecords
{
    protected static string $resource = PictureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Ladda upp bilder')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Ladda upp bilder till galleriet')
                ->modalDescription('Filnamnet blir bildens text, t.ex. "borsta-tanderna.png" blir "Borsta tanderna". Rätta texten i listan efteråt.')
                ->schema([
                    FileUpload::make('files')
                        ->label('Bilder')
                        ->multiple()
                        ->image()
                        ->disk(Picture::DISK)
                        ->directory('pictures/originals')
                        ->storeFileNamesIn('file_names')
                        ->maxSize(12 * 1024)
                        ->maxFiles(200)
                        ->maxParallelUploads(4)
                        ->required(),
                    Select::make('picture_category_id')
                        ->label('Kategori (valfritt)')
                        ->options(fn (): array => PictureCategory::query()->orderBy('sort_order')->pluck('name', 'id')->all()),
                ])
                ->action(function (array $data, StorePicture $store): void {
                    /** @var User $admin */
                    $admin = Auth::user();
                    $names = $data['file_names'] ?? [];
                    $category = isset($data['picture_category_id']) ? (int) $data['picture_category_id'] : null;

                    foreach ($data['files'] as $path) {
                        $store->fromStoredFile(
                            $path,
                            StorePicture::labelFromFileName($names[$path] ?? basename($path)),
                            null,
                            $admin,
                            $category,
                        );
                    }

                    Notification::make()
                        ->title(count($data['files']).' bilder uppladdade')
                        ->body('De görs om till WebP i bakgrunden.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
