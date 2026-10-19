<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Envoi d'e-mails NutriTrace — uniquement SMTP Gmail.
 *
 * Requis dans .env :
 *   MAIL_MAILER=smtp
 *   MAIL_HOST=smtp.gmail.com
 *   MAIL_PORT=587
 *   MAIL_USERNAME=...@gmail.com
 *   MAIL_PASSWORD=...  (mot de passe d'application Google, 16 caractères)
 *   MAIL_FROM_ADDRESS=même adresse que MAIL_USERNAME
 */
class ResendMailer
{
    /**
     * @param  list<array{filename: string, content: string, content_id?: string}>  $attachments
     */
    public function send(string $to, string $subject, string $html, array $attachments = []): void
    {
        if (! $this->smtpPret()) {
            throw new RuntimeException(
                'SMTP Gmail incomplet : dans .env renseigne MAIL_USERNAME et MAIL_PASSWORD '
                .'(mot de passe d\'application Google — pas le mot de passe du compte), '
                .'puis exécute : php artisan config:clear'
            );
        }

        try {
            Mail::mailer('smtp')->html($html, function ($message) use ($to, $subject, $attachments) {
                $message->to($to)->subject($subject);

                foreach ($attachments as $file) {
                    $binary = base64_decode((string) $file['content'], true);
                    if ($binary === false) {
                        $binary = (string) $file['content'];
                    }

                    if (! empty($file['content_id'])) {
                        $message->embedData(
                            $binary,
                            $file['filename'],
                            'image/png',
                        );
                    } else {
                        $message->attachData($binary, $file['filename']);
                    }
                }
            });
        } catch (Throwable $e) {
            $this->sauvegarderApercu($to, $subject, $html, $e->getMessage());

            throw new RuntimeException(
                'Envoi Gmail impossible : '.$e->getMessage()
                .' — Vérifie le mot de passe d\'application, php artisan config:clear, '
                .'et que le port 587 n\'est pas bloqué (firewall / Wi‑Fi fac).',
                0,
                $e,
            );
        }

        Log::info('E-mail Gmail envoyé', [
            'to' => $to,
            'subject' => $subject,
        ]);
    }

    private function smtpPret(): bool
    {
        return filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'));
    }

    private function sauvegarderApercu(string $to, string $subject, string $html, string $detail): void
    {
        Log::warning('Échec envoi e-mail Gmail', [
            'to' => $to,
            'subject' => $subject,
            'detail' => $detail,
        ]);

        try {
            Storage::disk('local')->put(
                'email-previews/'.now()->format('Ymd-His').'-'.preg_replace('/[^a-z0-9]+/i', '-', $to).'.html',
                "<!-- to: {$to} | subject: {$subject} | {$detail} -->\n".$html,
            );
        } catch (Throwable) {
            // ignore
        }
    }
}
