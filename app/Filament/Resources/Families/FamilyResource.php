<?php

namespace App\Filament\Resources\Families;

use App\Admin\Suspensions;
use App\Enums\FamilyRole;
use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Concerns\SuspendActions;
use App\Filament\Resources\Families\Pages\ListFamilies;
use App\Models\Family;
use App\Models\User;
use App\Support\FamilyFeatures;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * The families using the app: who started them, how many they are and when
 * they were last used. Never the family's own content.
 */
class FamilyResource extends Resource
{
    use AuthorizesAdmins, SuspendActions;

    protected static ?string $model = Family::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Konton';

    protected static ?string $navigationLabel = 'Familjer';

    protected static ?string $modelLabel = 'familj';

    protected static ?string $pluralModelLabel = 'familjer';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->addSelect([
                    // The first parent: the one who created the family.
                    'creator_name' => DB::table('family_user')
                        ->join('users', 'users.id', '=', 'family_user.user_id')
                        ->whereColumn('family_user.family_id', 'families.id')
                        ->where('family_user.role', FamilyRole::Parent->value)
                        ->orderBy('family_user.id')
                        ->limit(1)
                        ->selectRaw("concat(users.name, ' (', coalesce(users.email, ''), ')')"),
                    'adults_count' => DB::table('family_user')->whereColumn('family_id', 'families.id')->where('role', '!=', FamilyRole::Child->value)->selectRaw('count(*)'),
                    'children_count' => DB::table('family_user')->whereColumn('family_id', 'families.id')->where('role', FamilyRole::Child->value)->selectRaw('count(*)'),
                    'last_login_at' => DB::table('family_user')
                        ->join('users', 'users.id', '=', 'family_user.user_id')
                        ->whereColumn('family_user.family_id', 'families.id')
                        ->selectRaw('max(users.last_login_at)'),
                ]))
            ->columns([
                TextColumn::make('name')->label('Familj')->searchable(),
                TextColumn::make('creator_name')->label('Skapad av')->placeholder('–'),
                TextColumn::make('adults_count')->label('Vuxna'),
                TextColumn::make('children_count')->label('Barn'),
                TextColumn::make('created_at')->label('Skapad')->date('Y-m-d')->sortable(),
                TextColumn::make('last_login_at')->label('Senast inloggad')->since()->placeholder('–'),
                TextColumn::make('suspended_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === null ? 'Aktiv' : 'Avstängd')
                    ->color(fn ($state) => $state === null ? 'success' : 'gray')
                    ->default(null)
                    ->placeholder('Aktiv'),
                TextColumn::make('ica')
                    ->label('ICA (test)')
                    ->badge()
                    ->state(fn (Family $record) => FamilyFeatures::enabled($record, FamilyFeatures::ICA) ? 'På' : 'Av')
                    ->color(fn (string $state) => $state === 'På' ? 'success' : 'gray')
                    ->toggleable(),
                TextColumn::make('suspended_reason')->label('Anledning')->limit(40)->placeholder('–')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('suspended_at')->label('Avstängd')->nullable(),
            ])
            ->recordActions([
                // The ICA connection is tried out with one family at a time: ICA's API is unofficial.
                Action::make('ica')
                    ->label(fn (Family $record) => FamilyFeatures::enabled($record, FamilyFeatures::ICA) ? 'Stäng av ICA' : 'Slå på ICA (test)')
                    ->icon('heroicon-o-shopping-cart')
                    ->requiresConfirmation()
                    ->modalDescription('ICA-kopplingen använder ICA:s inofficiella API och kan sluta fungera. Familjens egen handlingslista fungerar alltid som vanligt.')
                    ->action(function (Family $record): void {
                        $on = ! FamilyFeatures::enabled($record, FamilyFeatures::ICA);
                        FamilyFeatures::set($record, FamilyFeatures::ICA, $on);

                        if (! $on) {
                            DB::table('ica_connections')->where('family_id', $record->id)->delete();
                        }
                    }),
                ...self::suspendActions(
                    'Ingen i familjen kan logga in, inte heller barnen eller hemskärmarna. Ingenting tas bort, och avstängningen kan hävas.',
                    fn (Model $family, string $reason, User $admin) => $family instanceof Family ? app(Suspensions::class)->suspendFamily($family, $reason, $admin) : null,
                    fn (Model $family, User $admin) => $family instanceof Family ? app(Suspensions::class)->unsuspendFamily($family, $admin) : null,
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilies::route('/'),
        ];
    }
}
