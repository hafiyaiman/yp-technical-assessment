<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'invitation' => 1,
        ]);

        return (new MailMessage)
            ->subject('You are invited to Exam Portal')
            ->greeting('Welcome to Exam Portal!')
            ->line('An account has been created for you.')
            ->line('Use the button below to set your password and activate your access.')
            ->action('Set Password', $url)
            ->line('This invitation link will expire in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you were not expecting this invitation, you can safely ignore this email.');
    }
}
