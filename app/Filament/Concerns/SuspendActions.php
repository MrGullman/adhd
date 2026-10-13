<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * "Stäng av" with a reason, and "Häv avstängningen", for accounts and families.
 */
trait SuspendActions
{
    /**
     * @param  Closure(Model, string, User): void  $suspend
     * @param  Closure(Model, User): void  $unsuspend
     * @return list<Action>
     */
    protected static function suspendActions(string $description, Closure $suspend, Closure $unsuspend): array
    {
        return [
            Action::make('suspend')
                ->label('Stäng av')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (Model $record) => $record->getAttribute('suspended_at') === null && ! $record->getAttribute('is_admin'))
                ->modalHeading('Stäng av')
                ->modalDescription($description)
                ->schema([
                    Textarea::make('reason')
                        ->label('Anledning (syns bara för admin)')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (Model $record, array $data) use ($suspend): void {
                    $suspend($record, (string) $data['reason'], self::admin());
                }),
            Action::make('unsuspend')
                ->label('Häv avstängningen')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (Model $record) => $record->getAttribute('suspended_at') !== null)
                ->requiresConfirmation()
                ->action(function (Model $record) use ($unsuspend): void {
                    $unsuspend($record, self::admin());
                }),
        ];
    }

    private static function admin(): User
    {
        $admin = Auth::user();
        abort_unless($admin instanceof User && $admin->is_admin, 403);

        return $admin;
    }
}
