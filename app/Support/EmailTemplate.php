<?php

namespace App\Support;

use Throwable;

/**
 * Rend un template Blade e-mail, avec repli HTML si la compilation échoue.
 */
class EmailTemplate
{
    public const LOGO_CID = 'nutritrace-logo';

    public static function render(string $view, array $data, string $fallbackHtml): string
    {
        try {
            return view($view, $data)->render();
        } catch (Throwable $e) {
            report($e);

            return $fallbackHtml;
        }
    }

    /**
     * URL publique (prévisualisation locale). En envoi SMTP, préférer cid:logo.
     */
    public static function logoUrl(): string
    {
        return 'cid:'.self::LOGO_CID;
    }

    public static function logoPublicUrl(): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if (file_exists(public_path('emails/logo.php'))) {
            return $base.'/emails/logo.php';
        }

        return $base.'/emails/logo.svg';
    }

    /**
     * PNG du logo en base64 (pièce jointe inline SMTP).
     */
    public static function logoAttachment(): array
    {
        return [
            'filename' => 'nutritrace-logo.png',
            'content_id' => self::LOGO_CID,
            'content' => base64_encode(self::logoPngBinary()),
        ];
    }

    public static function logoPngBinary(): string
    {
        $size = 256;
        $img = imagecreatetruecolor($size, $size);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        $radius = 56;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $ok = true;
                if ($x < $radius && $y < $radius) {
                    $ok = (($x - $radius) ** 2 + ($y - $radius) ** 2) <= $radius ** 2;
                } elseif ($x >= $size - $radius && $y < $radius) {
                    $ok = (($x - ($size - $radius - 1)) ** 2 + ($y - $radius) ** 2) <= $radius ** 2;
                } elseif ($x < $radius && $y >= $size - $radius) {
                    $ok = (($x - $radius) ** 2 + ($y - ($size - $radius - 1)) ** 2) <= $radius ** 2;
                } elseif ($x >= $size - $radius && $y >= $size - $radius) {
                    $ok = (($x - ($size - $radius - 1)) ** 2 + ($y - ($size - $radius - 1)) ** 2) <= $radius ** 2;
                }
                if (! $ok) {
                    continue;
                }
                $p = ($x + $y) / (2 * ($size - 1));
                $r = (int) (0x34 + (0x0f - 0x34) * $p);
                $g = (int) (0xb4 + (0x5f - 0xb4) * $p);
                $b = (int) (0x6c + (0x35 - 0x6c) * $p);
                imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
            }
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $gold = imagecolorallocate($img, 0xf2, 0xb1, 0x34);
        $dark = imagecolorallocate($img, 0x1a, 0x7a, 0x45);

        imagefilledellipse($img, 148, 118, 118, 148, $white);
        imagefilledellipse($img, 108, 150, 70, 90, $dark);
        imagefilledellipse($img, 158, 100, 90, 110, $white);
        imagefilledellipse($img, 44, 210, 26, 26, $gold);
        imagesetthickness($img, 9);
        imageline($img, 56, 204, 92, 172, $gold);

        ob_start();
        imagepng($img);
        $binary = (string) ob_get_clean();
        imagedestroy($img);

        return $binary;
    }
}
