<?php

namespace App\Filament\Resources\Avatars;

use App\Enums\AvatarGroup;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\Avatars\Pages\ListAvatars;
use App\Models\Picture;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Avatars children (and later adults) choose for their profile, in two
 * styles: Barn and Ungdom. Kept apart from bildstöd.
 */
class AvatarResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Picture::class;

    protected static ?string $slug = 'avatarer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    protected static string|UnitEnum|null $navigationGroup = 'Bildgalleri';

    protected static ?string $navigationLabel = 'Avatarer';

    protected static ?string $modelLabel = 'avatar';

    protected static ?string $pluralModelLabel = 'avatarer';

    protected static ?int $navigationSort = 2;

    /**
     * @return Builder<Picture>
     */
    public static function getEloquentQuery(): Builder
    {
        return Picture::query()->whereNull('family_id')->whereNotNull('avatar_group');
    }

    public static function table(Table $table): Table
    {
        $groups = collect(AvatarGroup::cases())->mapWithKeys(fn (AvatarGroup $group) => [$group->value => $group->label()])->all();

        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('Avatar')
                    ->state(fn (Picture $record): string => $record->url())
                    ->imageSize(64)
                    ->circular(),
                TextInputColumn::make('label')
                    ->label('Text')
                    ->rules(['required', 'max:50'])
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('avatar_group')
                    ->label('Stil')
                    ->options($groups)
                    ->selectablePlaceholder(false),
                IconColumn::make('processed_at')
                    ->label('Klar')
                    ->boolean()
                    ->state(fn (Picture $record): bool => $record->processed_at !== null),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('avatar_group')->label('Stil')->options($groups),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAvatars::route('/'),
        ];
    }
}
