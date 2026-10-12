<?php

namespace App\Filament\Resources\PictureCategories;

use App\Filament\Resources\PictureCategories\Pages\CreatePictureCategory;
use App\Filament\Resources\PictureCategories\Pages\EditPictureCategory;
use App\Filament\Resources\PictureCategories\Pages\ListPictureCategories;
use App\Filament\Resources\PictureCategories\Schemas\PictureCategoryForm;
use App\Filament\Resources\PictureCategories\Tables\PictureCategoriesTable;
use App\Models\PictureCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PictureCategoryResource extends Resource
{
    protected static ?string $model = PictureCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Bildgalleri';

    protected static ?string $navigationLabel = 'Kategorier';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategorier';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PictureCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PictureCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPictureCategories::route('/'),
            'create' => CreatePictureCategory::route('/create'),
            'edit' => EditPictureCategory::route('/{record}/edit'),
        ];
    }
}
