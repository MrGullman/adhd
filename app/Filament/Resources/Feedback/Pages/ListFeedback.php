<?php

namespace App\Filament\Resources\Feedback\Pages;

use App\Filament\Resources\Feedback\FeedbackResource;
use App\Support\FeedbackButton;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;

    public function getSubheading(): string
    {
        return 'Knappen Tyck till visas på: '.mb_strtolower(FeedbackButton::label(FeedbackButton::mode())).'.';
    }

    /**
     * Where "Tyck till" is shown.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('button')
                ->label('Var visas knappen?')
                ->icon('heroicon-o-adjustments-horizontal')
                ->fillForm(['mode' => FeedbackButton::mode()])
                ->schema([
                    Radio::make('mode')
                        ->label('Knappen Tyck till')
                        ->options([
                            FeedbackButton::OFF => FeedbackButton::label(FeedbackButton::OFF),
                            FeedbackButton::DEVELOPMENT => FeedbackButton::label(FeedbackButton::DEVELOPMENT),
                            FeedbackButton::ALL => FeedbackButton::label(FeedbackButton::ALL),
                        ])
                        ->descriptions([
                            FeedbackButton::DEVELOPMENT => 'De sidor som är nya eller ändras just nu (config/feedback.php).',
                            FeedbackButton::ALL => 'Alla vuxnas sidor.',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    FeedbackButton::set((string) $data['mode']);

                    Notification::make()->title('Sparat.')->success()->send();
                }),
        ];
    }
}
