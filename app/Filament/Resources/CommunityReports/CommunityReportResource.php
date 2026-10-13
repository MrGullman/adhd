<?php

namespace App\Filament\Resources\CommunityReports;

use App\Filament\Concerns\AuthorizesAdmins;
use App\Filament\Resources\CommunityReports\Pages\ListCommunityReports;
use App\Models\CommunityReport;
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
 * Posts parents have reported. The admin removes the post or lets it stay.
 */
class CommunityReportResource extends Resource
{
    use AuthorizesAdmins;

    protected static ?string $model = CommunityReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?string $navigationLabel = 'Anmälda inlägg';

    protected static ?string $modelLabel = 'anmälan';

    protected static ?string $pluralModelLabel = 'anmälningar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $open = CommunityReport::query()->whereNull('resolved_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['post.author', 'post.thread', 'reporter']))
            ->columns([
                TextColumn::make('post.body')->label('Inlägg')->limit(120)->wrap(),
                TextColumn::make('post.thread.title')->label('Tråd')->limit(40),
                TextColumn::make('post.author.community_name')->label('Skrivet av'),
                TextColumn::make('reason')->label('Anledning')->limit(60)->placeholder('–'),
                TextColumn::make('reporter.community_name')->label('Anmält av'),
                TextColumn::make('created_at')->label('När')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('resolved_at')
                    ->label('Hanterad')
                    ->nullable()
                    ->default(false),
            ])
            ->recordActions([
                Action::make('removePost')
                    ->label('Ta bort inlägget')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Är det trådens första inlägg tas hela tråden bort.')
                    ->action(function (CommunityReport $record): void {
                        $post = $record->post;
                        $thread = $post->thread;

                        $thread->posts()->orderBy('id')->value('id') === $post->id
                            ? $thread->delete()
                            : $post->delete();
                    }),
                Action::make('resolve')
                    ->label('Låt vara kvar')
                    ->visible(fn (CommunityReport $record) => $record->resolved_at === null)
                    ->action(fn (CommunityReport $record) => $record->forceFill(['resolved_at' => now()])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityReports::route('/'),
        ];
    }
}
