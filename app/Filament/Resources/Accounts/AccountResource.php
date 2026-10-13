<?php

namespace App\Filament\Resources\Accounts;

use App\Admin\Suspensions;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Concerns\SuspendActions;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Adults' accounts. Children's accounts belong to their families and are
 * handled by turning the family off.
 */
class AccountResource extends Resource
{
    use AuthorizesAdmins, SuspendActions;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'konton';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Konton';

    protected static ?string $navigationLabel = 'Vuxna';

    protected static ?string $modelLabel = 'konto';

    protected static ?string $pluralModelLabel = 'konton';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('email');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('families:id,name'))
            ->columns([
                TextColumn::make('name')->label('Namn')->searchable(),
                TextColumn::make('email')->label('E-post')->searchable(),
                TextColumn::make('families.name')->label('Familjer')->badge()->placeholder('–'),
                TextColumn::make('community_name')->label('Namn i Community')->placeholder('–')->toggleable(),
                TextColumn::make('created_at')->label('Skapat')->date('Y-m-d')->sortable(),
                TextColumn::make('last_login_at')->label('Senast inloggad')->since()->sortable()->placeholder('–'),
                TextColumn::make('suspended_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === null ? 'Aktiv' : 'Avstängt')
                    ->color(fn ($state) => $state === null ? 'success' : 'gray')
                    ->placeholder('Aktiv'),
                TextColumn::make('is_admin')
                    ->label('Admin')
                    ->formatStateUsing(fn ($state) => $state ? 'Admin' : '')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('suspended_reason')->label('Anledning')->limit(40)->placeholder('–')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('suspended_at')->label('Avstängt')->nullable(),
            ])
            ->recordActions(self::suspendActions(
                'Kontot loggas ut och kan inte logga in. Inläggen i Community döljs. Ingenting tas bort, och avstängningen kan hävas.',
                fn (Model $user, string $reason, User $admin) => $user instanceof User ? app(Suspensions::class)->suspendUser($user, $reason, $admin) : null,
                fn (Model $user, User $admin) => $user instanceof User ? app(Suspensions::class)->unsuspendUser($user, $admin) : null,
            ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
        ];
    }
}
