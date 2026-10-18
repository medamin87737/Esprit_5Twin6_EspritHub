<?php

/**
 * Logo NutriTrace PNG (compatible clients e-mail).
 * Accès : /emails/logo.php
 */
declare(strict_types=1);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');
header('Expires: '.gmdate('D, d M Y H:i:s', time() + 604800).' GMT');

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

// Feuille stylisée
imagefilledellipse($img, 148, 118, 118, 148, $white);
imagefilledellipse($img, 108, 150, 70, 90, imagecolorallocate($img, 0x1a, 0x7a, 0x45));
imagefilledellipse($img, 158, 100, 90, 110, $white);

// Accent or « trajet »
imagefilledellipse($img, 44, 210, 26, 26, $gold);
imagesetthickness($img, 9);
imageline($img, 56, 204, 92, 172, $gold);

imagepng($img);
imagedestroy($img);
