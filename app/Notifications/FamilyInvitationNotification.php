<?php

namespace App\Notifications;

use App\Models\FamilyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public FamilyInvitation $invitation,
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $family = $this->invitation->family->name;
        $inviter = $this->invitation->inviter->name ?? 'Någon';

        return (new MailMessage)
            ->subject("Inbjudan till {$family}")
            ->greeting('Hej!')
            ->line("{$inviter} har bjudit in dig till {$family} i NPF-familjen.")
            ->line('Du blir: '.$this->invitation->role->label().'.')
            ->action('Gå med i familjen', route('invitations.show', $this->token))
            ->line('Inbjudan gäller i '.FamilyInvitation::VALID_DAYS.' dagar. Om du inte känner igen den kan du strunta i det här mejlet.');
    }
}
