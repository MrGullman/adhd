<?php

namespace App\Filament\Resources\Families\Pages;

use App\Filament\Resources\Families\FamilyResource;
use App\Models\AdminAction;
use App\Models\User;
use App\Support\Registration;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListFamilies extends ListRecords
{
    protected static string $resource = FamilyResource::class;

    public function getSubheading(): string
    {
        return Registration::isOpen()
            ? 'Registreringen är öppen: vem som helst kan skapa ett konto och en ny familj.'
            : 'Registreringen är stängd: bara inbjudna vuxna kan gå med.';
    }

    /**
     * Open or close registration. Invited adults can always join.
     */
    protected function getHeaderActions(): array
    {
        $open = Registration::isOpen();

        return [
            Action::make('toggleRegistration')
                ->label($open ? 'Stäng registreringen' : 'Öppna registreringen')
                ->icon($open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                ->color($open ? 'danger' : 'primary')
                ->requiresConfirmation()
                ->modalHeading($open ? 'Stäng registreringen?' : 'Öppna registreringen?')
                ->modalDescription($open
                    ? 'Ingen kan skapa nya konton. Vuxna som bjuds in till en familj kan fortfarande gå med.'
                    : 'Vem som helst kan skapa ett konto och en ny familj.')
                ->action(function () use ($open): void {
                    $admin = Auth::user();
                    abort_unless($admin instanceof User && $admin->is_admin, 403);

                    Registration::set(! $open);

                    $entry = new AdminAction;
                    $entry->forceFill([
                        'admin_id' => $admin->id,
                        'action' => $open ? 'registration_closed' : 'registration_opened',
                        'subject_type' => 'app',
                        'subject_id' => 0,
                        'subject_name' => 'Registreringen',
                    ])->save();

                    Notification::make()
                        ->title($open ? 'Registreringen är stängd.' : 'Registreringen är öppen.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
