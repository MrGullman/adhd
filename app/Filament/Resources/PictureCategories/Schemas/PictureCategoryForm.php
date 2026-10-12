<?php

namespace App\Filament\Resources\PictureCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PictureCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Namn')
                    ->required()
                    ->maxLength(50),
                TextInput::make('sort_order')
                    ->label('Ordning')
                    ->helperText('Lägst först i bildväljaren.')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
