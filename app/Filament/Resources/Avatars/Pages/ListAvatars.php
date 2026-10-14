<?php

namespace App\Filament\Resources\Avatars\Pages;

use App\Actions\Pictures\StorePicture;
use App\Enums\AvatarGroup;
use App\Filament\Resources\Avatars\AvatarResource;
use App\Models\Picture;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAvatars extends ListRecords
{
    protected static string $resource = AvatarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Ladda upp avatarer')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Ladda upp avatarer')
                ->modalDescription('Bara huvudet, mitt i bilden och med genomskinlig bakgrund (PNG). Filnamnet blir avatarens text.')
                ->schema([
                    FileUpload::make('files')
                        ->label('Avatarer')
                        ->multiple()
                        ->image()
                        ->disk(Picture::DISK)
                        ->directory('pictures/originals')
                        ->storeFileNamesIn('file_names')
                        ->maxSize(12 * 1024)
                        ->maxFiles(100)
                        ->maxParallelUploads(4)
                        ->required(),
                    Select::make('avatar_group')
                        ->label('Stil')
                        ->options(collect(AvatarGroup::cases())->mapWithKeys(fn (AvatarGroup $group) => [$group->value => $group->label()])->all())
                        ->required(),
                ])
                ->action(function (array $data, StorePicture $store): void {
                    /** @var User $admin */
                    $admin = Auth::user();
                    $names = $data['file_names'] ?? [];
                    $group = AvatarGroup::from((string) $data['avatar_group']);

                    foreach ($data['files'] as $path) {
                        $store->fromStoredFile(
                            $path,
                            StorePicture::labelFromFileName($names[$path] ?? basename($path)),
                            null,
                            $admin,
                            avatarGroup: $group,
                        );
                    }

                    Notification::make()
                        ->title(count($data['files']).' avatarer uppladdade')
                        ->body('De görs om till WebP i bakgrunden.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
