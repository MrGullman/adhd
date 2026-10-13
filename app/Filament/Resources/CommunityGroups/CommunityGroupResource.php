<?php

namespace App\Filament\Resources\CommunityGroups;

use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\CommunityGroups\Pages\CreateCommunityGroup;
use App\Filament\Resources\CommunityGroups\Pages\EditCommunityGroup;
use App\Filament\Resources\CommunityGroups\Pages\ListCommunityGroups;
use App\Models\CommunityGroup;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The groups in Community, e.g. "Skola och förskola".
 */
class CommunityGroupResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = CommunityGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Grupper';

    protected static ?string $modelLabel = 'grupp';

    protected static ?string $pluralModelLabel = 'grupper';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Namn')->required()->maxLength(60),
            TextInput::make('description')->label('Beskrivning')->maxLength(255),
            TextInput::make('sort_order')
                ->label('Ordning')
                ->helperText('Lägst först.')
                ->required()
                ->integer()
                ->minValue(0)
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Namn')->searchable(),
                TextColumn::make('members_count')->label('Medlemmar')->counts('members'),
                TextColumn::make('threads_count')->label('Trådar')->counts('threads'),
                TextColumn::make('sort_order')->label('Ordning')->numeric()->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityGroups::route('/'),
            'create' => CreateCommunityGroup::route('/create'),
            'edit' => EditCommunityGroup::route('/{record}/edit'),
        ];
    }
}
