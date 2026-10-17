<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe NutriTrace')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Vous recevez cet e-mail parce qu\'une demande de réinitialisation de mot de passe a été faite pour votre compte.')
            ->action('Réinitialiser le mot de passe', url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line('Ce lien expire dans '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('Si vous n\'avez pas demandé de réinitialisation, ignorez cet e-mail.')
            ->salutation('L\'équipe NutriTrace');
    }
}
