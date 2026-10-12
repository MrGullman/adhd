<?php

namespace App\Filament\Resources\Pictures;

use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\Pictures\Pages\EditPicture;
use App\Filament\Resources\Pictures\Pages\ListPictures;
use App\Filament\Resources\Pictures\Schemas\PictureForm;
use App\Filament\Resources\Pictures\Tables\PicturesTable;
use App\Models\Picture;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The shared gallery. Families' own photos are never shown here.
 */
class PictureResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Picture::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Bildgalleri';

    protected static ?string $navigationLabel = 'Bilder';

    protected static ?string $modelLabel = 'bild';

    protected static ?string $pluralModelLabel = 'bilder';

    protected static ?int $navigationSort = 1;

    /**
     * @return Builder<Picture>
     */
    public static function getEloquentQuery(): Builder
    {
        return Picture::query()->whereNull('family_id');
    }

    public static function form(Schema $schema): Schema
    {
        return PictureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PicturesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPictures::route('/'),
            'edit' => EditPicture::route('/{record}/edit'),
        ];
    }
}
