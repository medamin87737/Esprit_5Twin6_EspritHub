<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Envoi d'e-mails via l'API Resend (sans le package resend/resend-php).
 */
class ResendMailer
{
    /**
     * @param  list<array{filename: string, content: string, content_id?: string}>  $attachments
     */
    public function send(string $to, string $subject, string $html, array $attachments = []): void
    {
        $key = config('services.resend.key');

        if (! filled($key)) {
            throw new RuntimeException('RESEND_API_KEY est absente du fichier .env.');
        }

        $fromAddress = config('mail.from.address', 'beth.t@example.com');
        $fromName = config('mail.from.name', 'NutriTrace');

        // Domaine de test Resend si l'expéditeur n'est pas un domaine vérifié.
        if (str_ends_with((string) $fromAddress, '@gmail.com')
            || str_ends_with((string) $fromAddress, '@example.com')
            || str_ends_with((string) $fromAddress, '@example.tn')) {
            $fromAddress = 'onboarding@resend.dev';
        }

        $payload = [
            'from' => "{$fromName} <{$fromAddress}>",
            'to' => [$to],
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
            $this->echecLocal($to, $subject, $html, $e->getMessage());

            throw new RuntimeException(
                'Impossible de joindre Resend. En local, un aperçu a été enregistré.',
                0,
                $e,
            );
        }

        if ($response->successful()) {
            return;
        }

        $detail = $response->json('message') ?? $response->body();
        $this->echecLocal($to, $subject, $html, (string) $detail);

        throw new RuntimeException('Resend a refusé l\'envoi : '.$detail);
    }

    private function echecLocal(string $to, string $subject, string $html, string $detail): void
    {
        Log::warning('Échec envoi e-mail Resend', [
            'to' => $to,
            'subject' => $subject,
            'detail' => $detail,
        ]);

        if (! app()->environment('local')) {
            return;
        }

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
