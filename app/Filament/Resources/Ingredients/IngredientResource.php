<?php

namespace App\Filament\Resources\Ingredients;

use App\Enums\StoreSection;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Resources\Ingredients\Pages\ListIngredients;
use App\Food\Units;
use App\Models\Ingredient;
use App\Models\Picture;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The shared basic list of ingredients. Families' own are never shown here.
 */
class IngredientResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Mat';

    protected static ?string $navigationLabel = 'Grundlista';

    protected static ?string $modelLabel = 'vara';

    protected static ?string $pluralModelLabel = 'varor';

    protected static ?string $slug = 'grundlista';

    /**
     * @return Builder<Ingredient>
     */
    public static function getEloquentQuery(): Builder
    {
        return Ingredient::query()->whereNull('family_id');
    }

    /**
     * @return array<string, string>
     */
    private static function sections(): array
    {
        return collect(StoreSection::cases())->mapWithKeys(fn (StoreSection $section) => [$section->value => $section->label()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Namn')->required()->maxLength(50),
            Select::make('category')->label('Butiksavdelning')->options(self::sections())->required()->default(StoreSection::Other->value),
            Select::make('default_unit')->label('Vanlig enhet')->options(array_combine(Units::all(), Units::all())),
            Select::make('picture_id')
                ->label('Bild')
                ->searchable()
                ->getSearchResultsUsing(fn (?string $search): array => Picture::query()
                    ->whereNull('family_id')
                    ->when($search, fn ($query) => $query->where('label', 'like', "%{$search}%"))
                    ->orderBy('label')
                    ->limit(50)
                    ->pluck('label', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Picture::query()->whereKey($value)->value('label')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Namn')->searchable()->sortable(),
                SelectColumn::make('category')->label('Butiksavdelning')->options(self::sections()),
                TextColumn::make('default_unit')->label('Enhet'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category')->label('Butiksavdelning')->options(self::sections()),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIngredients::route('/'),
            'create' => CreateIngredient::route('/create'),
            'edit' => EditIngredient::route('/{record}/edit'),
        ];
    }
}
