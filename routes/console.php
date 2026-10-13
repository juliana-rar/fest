<?php

use App\Services\CoverArt;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/*
 | Generates the PNG app icons (PWA) and the default Open Graph image from
 | code, so they can be rebuilt whenever the brand colours change.
 */
Artisan::command('festes:brand-assets', function (CoverArt $art) {
    File::ensureDirectoryExists(public_path('icons'));
    File::ensureDirectoryExists(public_path('img'));

    $icon = function (int $size, bool $maskable) {
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, true);
        imagesavealpha($img, true);
        $night = imagecolorallocate($img, 15, 16, 32);
        $gold = imagecolorallocate($img, 255, 183, 3);
        $red = imagecolorallocate($img, 230, 57, 70);

        imagefill($img, 0, 0, $maskable ? $night : imagecolorallocatealpha($img, 0, 0, 0, 127));
        if (! $maskable) {
            // Rounded square background.
            $r = (int) ($size * 0.22);
            imagefilledrectangle($img, $r, 0, $size - $r, $size, $night);
            imagefilledrectangle($img, 0, $r, $size, $size - $r, $night);
            foreach ([[$r, $r], [$size - $r, $r], [$r, $size - $r], [$size - $r, $size - $r]] as [$x, $y]) {
                imagefilledellipse($img, $x, $y, $r * 2, $r * 2, $night);
            }
        }

        $c = $size / 2;
        $scale = $maskable ? 0.32 : 0.4; // keep the mark inside the maskable safe zone
        imagesetthickness($img, max(2, (int) ($size * 0.055)));
        foreach ([[0, -1], [0, 1], [-1, 0], [1, 0]] as [$dx, $dy]) {
            imageline($img, (int) ($c + $dx * $size * $scale * 0.45), (int) ($c + $dy * $size * $scale * 0.45), (int) ($c + $dx * $size * $scale), (int) ($c + $dy * $size * $scale), $gold);
        }
        imagesetthickness($img, max(2, (int) ($size * 0.048)));
        foreach ([[-1, -1], [1, 1], [-1, 1], [1, -1]] as [$dx, $dy]) {
            $d = $size * $scale * 0.7;
            imageline($img, (int) ($c + $dx * $d * 0.45), (int) ($c + $dy * $d * 0.45), (int) ($c + $dx * $d), (int) ($c + $dy * $d), $red);
        }
        imagefilledellipse($img, (int) $c, (int) $c, (int) ($size * 0.2), (int) ($size * 0.2), $gold);
        imagefilledellipse($img, (int) $c, (int) $c, (int) ($size * 0.085), (int) ($size * 0.085), $night);

        return $img;
    };

    imagepng($icon(192, false), public_path('icons/icon-192.png'));
    imagepng($icon(512, false), public_path('icons/icon-512.png'));
    imagepng($icon(512, true), public_path('icons/icon-maskable-512.png'));

    // Default Open Graph image (1200×630).
    $og = $art->make('festes-majors-og', 1200, 630);
    $white = imagecolorallocate($og, 245, 241, 232);
    $gold = imagecolorallocate($og, 255, 183, 3);
    $font = resource_path('fonts/BricolageGrotesque-ExtraBold.ttf');
    $body = resource_path('fonts/Inter-SemiBold.ttf');
    imagefilledrectangle($og, 0, 0, 1200, 630, imagecolorallocatealpha($og, 15, 16, 32, 50));
    imagettftext($og, 64, 0, 80, 300, $white, $font, 'Festes Majors');
    imagettftext($og, 64, 0, 80, 395, $gold, $font, 'de Catalunya');
    imagettftext($og, 26, 0, 84, 470, $white, $body, 'Calendari i mapa de totes les festes majors');
    imagejpeg($og, public_path('img/og-default.jpg'), 86);

    $this->info('Icons and Open Graph image generated.');
})->purpose('Generate PWA icons and the default Open Graph image');
