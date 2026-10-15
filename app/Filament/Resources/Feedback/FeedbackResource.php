<?php

namespace App\Filament\Resources\Feedback;

use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\Feedback\Pages\ListFeedback;
use App\Models\Feedback;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * "Tyck till" from the families, with the page it was written on.
 */
class FeedbackResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = Feedback::class;

    protected static ?string $slug = 'feedback';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Konton';

    protected static ?string $navigationLabel = 'Feedback';

    protected static ?string $modelLabel = 'feedback';

    protected static ?string $pluralModelLabel = 'feedback';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $new = Feedback::query()->whereNull('handled_at')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user:id,name,email', 'family:id,name']))
            ->columns([
                TextColumn::make('created_at')->label('När')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('page')
                    ->label('Sida')
                    ->description(fn (Feedback $record): string => $record->url)
                    ->searchable(),
                TextColumn::make('message')->label('Feedback')->wrap()->searchable(),
                TextColumn::make('user.name')
                    ->label('Från')
                    ->description(fn (Feedback $record): string => (string) $record->family?->name),
                TextColumn::make('handled_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === null ? 'Ny' : 'Hanterad')
                    ->color(fn ($state) => $state === null ? 'warning' : 'gray')
                    ->placeholder('Ny'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('handled_at')->label('Hanterad')->nullable()->default(false),
            ])
            ->recordActions([
                Action::make('handle')
                    ->label('Hanterad')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Feedback $record) => $record->handled_at === null)
                    ->action(fn (Feedback $record) => $record->forceFill(['handled_at' => now()])->save()),
                Action::make('reopen')
                    ->label('Öppna igen')
                    ->visible(fn (Feedback $record) => $record->handled_at !== null)
                    ->action(fn (Feedback $record) => $record->forceFill(['handled_at' => null])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedback::route('/'),
        ];
    }
}
