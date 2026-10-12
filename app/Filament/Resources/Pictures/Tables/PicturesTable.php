<?php

namespace App\Filament\Resources\Pictures\Tables;

use App\Models\Picture;
use App\Models\PictureCategory;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PicturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('Bild')
                    ->state(fn (Picture $record): string => $record->url())
                    ->imageSize(64),
                // Labels and keywords are edited right in the list after a big upload.
                TextInputColumn::make('label')
                    ->label('Text')
                    ->rules(['required', 'max:50'])
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('picture_category_id')
                    ->label('Kategori')
                    ->options(fn (): array => PictureCategory::query()->orderBy('sort_order')->pluck('name', 'id')->all()),
                TextInputColumn::make('keywords')
                    ->label('Sökord')
                    ->rules(['nullable', 'max:255'])
                    ->searchable(),
                IconColumn::make('processed_at')
                    ->label('Klar')
                    ->boolean()
                    ->state(fn (Picture $record): bool => $record->processed_at !== null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('picture_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                TernaryFilter::make('without_category')
                    ->label('Utan kategori')
                    ->queries(
                        true: fn ($query) => $query->whereNull('picture_category_id'),
                        false: fn ($query) => $query->whereNotNull('picture_category_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setCategory')
                        ->label('Sätt kategori')
                        ->schema([
                            Select::make('picture_category_id')
                                ->label('Kategori')
                                ->options(fn (): array => PictureCategory::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                                ->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => Picture::query()
                            ->whereKey($records->modelKeys())
                            ->update(['picture_category_id' => $data['picture_category_id']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
