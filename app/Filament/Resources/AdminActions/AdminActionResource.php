<?php

namespace App\Filament\Resources\AdminActions;

use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\AdminActions\Pages\ListAdminActions;
use App\Models\AdminAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * What admins have done: who turned what off or on, when and why.
 */
class AdminActionResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = AdminAction::class;

    protected static ?string $slug = 'logg';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Konton';

    protected static ?string $navigationLabel = 'Logg';

    protected static ?string $modelLabel = 'händelse';

    protected static ?string $pluralModelLabel = 'logg';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('När')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('action')
                    ->label('Vad')
                    ->formatStateUsing(fn (string $state) => $state === 'suspended' ? 'Stängde av' : 'Hävde avstängning'),
                TextColumn::make('subject_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state) => $state === 'family' ? 'Familj' : 'Konto'),
                TextColumn::make('subject_name')->label('Vem')->searchable(),
                TextColumn::make('reason')->label('Anledning')->wrap()->placeholder('–'),
                TextColumn::make('admin.name')->label('Admin')->placeholder('–'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActions::route('/'),
        ];
    }
}
