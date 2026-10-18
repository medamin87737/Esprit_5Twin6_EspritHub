<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Envoi d'e-mails : SMTP (Gmail) en priorité si configuré, sinon API Resend.
 */
class ResendMailer
{
    /**
     * @param  list<array{filename: string, content: string, content_id?: string}>  $attachments
     */
    public function send(string $to, string $subject, string $html, array $attachments = []): void
    {
        if (config('mail.default') === 'smtp') {
            if (! $this->smtpPret()) {
                throw new RuntimeException(
                    'SMTP Gmail incomplet : renseigne MAIL_USERNAME et MAIL_PASSWORD (mot de passe d\'application) dans .env.'
                );
            }

            $this->sendViaSmtp($to, $subject, $html, $attachments);

            return;
        }

        $this->sendViaResend($to, $subject, $html, $attachments);
    }

    private function smtpPret(): bool
    {
        return filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'));
    }

    /**
     * @param  list<array{filename: string, content: string, content_id?: string}>  $attachments
     */
    private function sendViaSmtp(string $to, string $subject, string $html, array $attachments): void
    {
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
                'Envoi SMTP (Gmail) impossible : '.$e->getMessage(),
                0,
                $e,
            );
        }

        Log::info('E-mail SMTP envoyé', [
            'to' => $to,
            'subject' => $subject,
        ]);
    }

    /**
     * @param  list<array{filename: string, content: string, content_id?: string}>  $attachments
     */
    private function sendViaResend(string $to, string $subject, string $html, array $attachments = []): void
    {
        $key = config('services.resend.key') ?: env('RESEND_API_KEY');

        if (! filled($key)) {
            throw new RuntimeException('Ni SMTP Gmail ni RESEND_API_KEY configurés dans .env.');
        }

        $fromAddress = config('mail.from.address', 'beth.t@example.com');
        $fromName = config('mail.from.name', 'NutriTrace');

        if (str_ends_with((string) $fromAddress, '@gmail.com')
            || str_ends_with((string) $fromAddress, '@example.com')
            || str_ends_with((string) $fromAddress, '@example.tn')) {
            $fromAddress = 'onboarding@resend.dev';
        }

        $destinataire = $to;
        $redirect = config('services.resend.redirect_to') ?: env('RESEND_REDIRECT_TO');

        if (filled($redirect) && strcasecmp((string) $redirect, $to) !== 0) {
            $destinataire = (string) $redirect;
            $subject = '[Pour '.$to.'] '.$subject;
            $html = '<div style="background:#FFF8E6;border:1px solid #F0D78C;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-family:Segoe UI,Arial,sans-serif;font-size:13px;color:#5C4308">'
                .'<strong>Destinataire prévu :</strong> '.e($to)
                .' — e-mail redirigé en mode test Resend vers '.e($destinataire).'.'
                .'</div>'.$html;
        }

        $payload = [
            'from' => "{$fromName} <{$fromAddress}>",
            'to' => [$destinataire],
            'subject' => $subject,
            'html' => $html,
        ];

        if ($attachments !== []) {
            $payload['attachments'] = array_map(static function (array $file): array {
                $item = [
                    'filename' => $file['filename'],
                    'content' => $file['content'],
                ];
                if (! empty($file['content_id'])) {
                    $item['content_id'] = $file['content_id'];
                }

                return $item;
            }, $attachments);
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(20)
                ->connectTimeout(8)
                ->post('https://api.resend.com/emails', $payload);
        } catch (Throwable $e) {
            $this->sauvegarderApercu($to, $subject, $html, $e->getMessage());

            throw new RuntimeException(
                'Impossible de joindre Resend. Un aperçu a été enregistré dans storage/app/email-previews/.',
                0,
                $e,
            );
        }

        if ($response->successful()) {
            Log::info('E-mail Resend envoyé', [
                'to' => $to,
                'via' => $destinataire,
                'subject' => $subject,
            ]);

            return;
        }

        $detail = $response->json('message') ?? $response->body();
        $this->sauvegarderApercu($to, $subject, $html, (string) $detail);

        throw new RuntimeException('Resend a refusé l\'envoi : '.$detail);
    }

    private function sauvegarderApercu(string $to, string $subject, string $html, string $detail): void
    {
        Log::warning('Échec envoi e-mail', [
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
