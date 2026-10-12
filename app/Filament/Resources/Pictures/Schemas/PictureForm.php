<?php

namespace App\Filament\Resources\Pictures\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PictureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Text')
                    ->helperText('Kort, högst tre ord. Visas med bilden och läses upp av skärmläsare.')
                    ->required()
                    ->maxLength(50),
                Select::make('picture_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->preload(),
                TextInput::make('keywords')
                    ->label('Sökord')
                    ->helperText('Andra ord som familjer kan söka på, med komma emellan.')
                    ->maxLength(255),
            ]);
    }
}
