<?php

namespace App\Services;

use GdImage;

/**
 * Procedural festival artwork: a night-sky gradient with firework bursts,
 * a human-tower ("castell") silhouette and film grain. Used for seeded
 * festivals (no third-party photos are shipped) and as the Story
 * background when a festival has no cover image.
 */
class CoverArt
{
    /** Senyera red, gold, neon mint, violet and coral. */
    private const SPARKS = [[230, 57, 70], [255, 183, 3], [94, 234, 212], [167, 139, 250], [255, 122, 89], [255, 255, 255]];

    private const SKIES = [
        [[15, 16, 32], [58, 20, 64]],
        [[11, 18, 38], [24, 58, 92]],
        [[18, 12, 28], [92, 24, 46]],
        [[10, 22, 30], [20, 72, 70]],
        [[20, 14, 40], [70, 40, 110]],
    ];

    public function make(string $seed, int $width = 1280, int $height = 720, bool $castell = true): GdImage
    {
        mt_srand(crc32($seed));

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);

        [$top, $bottom] = self::SKIES[mt_rand(0, count(self::SKIES) - 1)];
        self::verticalGradient($img, $top, $bottom, 0, $height);

        $bursts = mt_rand(3, 6);
        for ($i = 0; $i < $bursts; $i++) {
            $this->burst(
                $img,
                mt_rand((int) ($width * 0.08), (int) ($width * 0.92)),
                mt_rand((int) ($height * 0.1), (int) ($height * 0.55)),
                mt_rand((int) ($height * 0.12), (int) ($height * 0.3)),
                self::SPARKS[mt_rand(0, count(self::SPARKS) - 1)],
            );
        }

        if ($castell) {
            $this->castell($img, (int) ($width * (mt_rand(25, 75) / 100)), $height, (int) ($height * 0.075));
        }

        self::grain($img, 14);
        mt_srand();

        return $img;
    }

    public function webp(string $seed, int $width = 1280, int $height = 720): string
    {
        $img = $this->make($seed, $width, $height);
        ob_start();
        imagewebp($img, null, 84);

        return (string) ob_get_clean();
    }

    /** @param array{int,int,int} $from @param array{int,int,int} $to */
    public static function verticalGradient(GdImage $img, array $from, array $to, int $y0, int $y1, int $alphaFrom = 0, int $alphaTo = 0): void
    {
        $width = imagesx($img);
        $span = max(1, $y1 - $y0);
        for ($y = $y0; $y < $y1; $y++) {
            $t = ($y - $y0) / $span;
            $color = imagecolorallocatealpha(
                $img,
                (int) ($from[0] + ($to[0] - $from[0]) * $t),
                (int) ($from[1] + ($to[1] - $from[1]) * $t),
                (int) ($from[2] + ($to[2] - $from[2]) * $t),
                (int) ($alphaFrom + ($alphaTo - $alphaFrom) * $t),
            );
            imageline($img, 0, $y, $width, $y, $color);
        }
    }

    /** Overlay a tiled noise texture for a subtle film-grain finish. */
    public static function grain(GdImage $img, int $strength = 12): void
    {
        $size = 160;
        $tile = imagecreatetruecolor($size, $size);
        imagesavealpha($tile, true);
        imagealphablending($tile, false);
        imagefill($tile, 0, 0, imagecolorallocatealpha($tile, 0, 0, 0, 127));
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $v = mt_rand(0, 255);
                imagesetpixel($tile, $x, $y, imagecolorallocatealpha($tile, $v, $v, $v, 127 - mt_rand(0, $strength)));
            }
        }
        imagealphablending($img, true);
        imagesettile($img, $tile);
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), IMG_COLOR_TILED);
    }

    /** @param array{int,int,int} $rgb */
    private function burst(GdImage $img, int $cx, int $cy, int $radius, array $rgb): void
    {
        // Soft halo.
        for ($r = $radius; $r > 0; $r -= max(4, (int) ($radius / 10))) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], 124));
        }

        $rays = mt_rand(22, 40);
        imagesetthickness($img, 2);
        for ($i = 0; $i < $rays; $i++) {
            $angle = (2 * M_PI / $rays) * $i + mt_rand(-10, 10) / 100;
            $length = $radius * mt_rand(70, 100) / 100;
            $x1 = $cx + (int) (cos($angle) * $length * 0.25);
            $y1 = $cy + (int) (sin($angle) * $length * 0.25);
            $x2 = $cx + (int) (cos($angle) * $length);
            $y2 = $cy + (int) (sin($angle) * $length) + (int) ($length * 0.06);
            imageline($img, $x1, $y1, $x2, $y2, imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], mt_rand(30, 70)));
            $dot = mt_rand(4, 8);
            imagefilledellipse($img, $x2, $y2, $dot, $dot, imagecolorallocatealpha($img, 255, 255, 255, mt_rand(10, 50)));
        }
        imagesetthickness($img, 1);
    }

    /** A stylised human tower silhouette: rows of heads narrowing to the top. */
    private function castell(GdImage $img, int $cx, int $baseY, int $unit): void
    {
        $color = imagecolorallocatealpha($img, 5, 6, 14, 18);
        $rows = [6, 5, 4, 3, 2, 1, 1];
        $y = $baseY;
        foreach ($rows as $i => $count) {
            $bodyH = (int) ($unit * ($i === count($rows) - 1 ? 0.8 : 1.1));
            $w = (int) ($unit * 0.62);
            $start = $cx - (int) (($count - 1) * $w * 0.55);
            for ($p = 0; $p < $count; $p++) {
                $x = $start + (int) ($p * $w * 1.1);
                imagefilledrectangle($img, $x - (int) ($w / 2.4), $y - $bodyH, $x + (int) ($w / 2.4), $y, $color);
                imagefilledellipse($img, $x, $y - $bodyH - (int) ($unit * 0.2), (int) ($unit * 0.42), (int) ($unit * 0.42), $color);
            }
            $y -= $bodyH + (int) ($unit * 0.38);
        }
        // Raised arm of the "enxaneta" crowning the tower.
        imagesetthickness($img, max(2, (int) ($unit * 0.12)));
        imageline($img, $cx, $y + (int) ($unit * 0.35), $cx + (int) ($unit * 0.35), $y - (int) ($unit * 0.1), $color);
        imagesetthickness($img, 1);
        // Pinya (crowd at the base).
        imagefilledellipse($img, $cx, $baseY + (int) ($unit * 0.6), $unit * 12, (int) ($unit * 2.4), $color);
    }
}
