<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('Vérifiez votre adresse e-mail NutriTrace')
            ->greeting('Bienvenue '.$notifiable->name.' !')
            ->line('Merci de confirmer votre adresse e-mail pour activer pleinement votre compte.')
            ->action('Vérifier mon e-mail', $url)
            ->line('Ce lien expire dans '.Config::get('auth.verification.expire', 60).' minutes.')
            ->salutation('L\'équipe NutriTrace');
    }
}
