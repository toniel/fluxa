<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TenantInvitation $invitation,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $this->invitation->tenant->name;
        $roleLabel = $this->invitation->role->label();

        return (new MailMessage)
            ->subject("Undangan bergabung ke {$tenantName}")
            ->greeting('Halo!')
            ->line("Anda diundang bergabung ke tenant {$tenantName} sebagai {$roleLabel}.")
            ->line('Undangan ini kedaluwarsa dalam 7 hari.')
            ->action('Lihat Undangan', route('invitations.show', $this->invitation->token))
            ->line('Abaikan email ini bila Anda tidak mengenal pengundangnya.');
    }
}
