<?php

namespace App\Support;

/**
 * TOTP (RFC 6238) — authentification à deux facteurs sans dépendance Fortify.
 */
class Totp
{
    public static function genererSecret(int $longueur = 16): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $longueur; $i++) {
            $secret .= $alphabet[random_int(0, 31)];
        }

        return $secret;
    }

    public static function code(string $secret, ?int $timestamp = null, int $periode = 30, int $chiffres = 6): string
    {
        $time = (int) floor(($timestamp ?? time()) / $periode);
        $secretBin = self::base32Decode($secret);
        $timeBin = pack('N*', 0, $time);
        $hash = hash_hmac('sha1', $timeBin, $secretBin, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $valeur = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** $chiffres);

        return str_pad((string) $valeur, $chiffres, '0', STR_PAD_LEFT);
    }

    public static function verifier(string $secret, string $code, int $fenetre = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $maintenant = time();

        for ($i = -$fenetre; $i <= $fenetre; $i++) {
            if (hash_equals(self::code($secret, $maintenant + ($i * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $secret, string $email, string $emetteur = 'NutriTrace'): string
    {
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($emetteur.':'.$email),
            $secret,
            rawurlencode($emetteur),
        );
    }

    public static function qrUrl(string $otpauthUri, int $taille = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$taille.'x'.$taille.'&data='.urlencode($otpauthUri);
    }

    /**
     * @return list<string>
     */
    public static function codesRecuperation(int $nombre = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $nombre; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(2)));
        }

        return $codes;
    }

    private static function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $buffer = 0;
        $bits = 0;
        $result = '';

        foreach (str_split($secret) as $char) {
            $buffer = ($buffer << 5) | strpos($alphabet, $char);
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $result;
    }
}
