<?php

namespace App\Services;

use App\Models\Fiesta;
use App\Models\Setting;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use GdImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Renders a 1080×1920 (9:16) Instagram Story for a festival.
 *
 * Server-side rendering with GD/Intervention was chosen over html2canvas or
 * Browsershot: it is deterministic on every device, needs no headless browser
 * in the container, can be cached on disk and served from a stable URL
 * (/story/{slug}) that the Web Share API and the download button both reuse.
 */
class StoryImage
{
    public const WIDTH = 1080;

    public const HEIGHT = 1920;

    private const NIGHT = [15, 16, 32];

    private const INK = [21, 22, 43];

    private const GOLD = [255, 183, 3];

    private const RED = [230, 57, 70];

    private const WHITE = [255, 255, 255];

    /** Text blocks must end above the footer (URL + QR code). */
    private const TEXT_BOTTOM = 1560;

    public function __construct(private CoverArt $art) {}

    /** Absolute path to the cached JPEG, rendering it when needed. */
    public function path(Fiesta $fiesta, string $template): string
    {
        $config = Setting::storyTemplates()[$template];
        $hash = md5(implode('|', [
            $fiesta->updated_at?->timestamp, $fiesta->imagen, today()->toDateString(), app()->getLocale(),
            json_encode($config), Setting::get('short_url'), Setting::get('logo'), 'v4',
        ]));

        $dir = storage_path("app/stories/{$fiesta->slug}");
        $file = "{$dir}/{$template}-".app()->getLocale()."-{$hash}.jpg";

        if (! is_file($file)) {
            File::ensureDirectoryExists($dir);
            // Drop stale renders of this template/locale (e.g. yesterday's countdown).
            foreach (glob("{$dir}/{$template}-".app()->getLocale().'-*.jpg') ?: [] as $old) {
                @unlink($old);
            }

            $img = $this->render($fiesta, $template, $config);
            imagejpeg($img, $file, 88);
        }

        return $file;
    }

    /** @param array{name: string, enabled: bool, accent: string} $config */
    public function render(Fiesta $fiesta, string $template, array $config): GdImage
    {
        $fiesta->loadMissing('municipio.comarca');
        $accent = self::hex($config['accent'] ?? '#5EEAD4');

        return match ($template) {
            'senyera' => $this->senyera($fiesta, $accent),
            'minimal' => $this->minimal($fiesta, $accent),
            default => $this->nit($fiesta, $accent),
        };
    }

    /* -----------------------------------------------------------------
     | Templates
     | ----------------------------------------------------------------- */

    /** Night-out template: full photo, neon accent, bold title at the bottom. */
    private function nit(Fiesta $fiesta, array $accent): GdImage
    {
        $img = $this->background($fiesta, self::WIDTH, self::HEIGHT);
        CoverArt::verticalGradient($img, self::NIGHT, self::NIGHT, 0, 420, 40, 127);
        CoverArt::verticalGradient($img, self::NIGHT, self::NIGHT, 760, 1300, 127, 0);
        imagefilledrectangle($img, 0, 1300, self::WIDTH, self::HEIGHT, $this->color($img, self::NIGHT));
        CoverArt::grain($img, 10);

        $this->brand($img, 80, 140, self::WHITE, $accent);
        $this->pill($img, $this->countdown($fiesta), self::WIDTH - 80, 92, $fiesta->estaEnCurso() ? self::RED : $accent, $fiesta->estaEnCurso() ? self::WHITE : self::NIGHT, true);

        $lines = $this->wrap($fiesta->nombre, 'BricolageGrotesque-ExtraBold', 92, self::WIDTH - 160, 3);
        $y = self::TEXT_BOTTOM - (10 + 90 + 40 + count($lines) * 128 + 90 + 56);
        imagefilledrectangle($img, 80, $y, 220, $y + 10, $this->color($img, $accent));
        $y += 90;
        $this->text($img, mb_strtoupper($fiesta->municipio->nombre), 'Inter-SemiBold', 34, 80, $y, $accent, 6);
        $y += 40;
        foreach ($lines as $line) {
            $y += 128;
            $this->text($img, $line, 'BricolageGrotesque-ExtraBold', 92, 80, $y, self::WHITE);
        }
        $y += 90;
        $this->text($img, $fiesta->rangoFechas().' '.$fiesta->fecha_inicio->year, 'Inter-SemiBold', 40, 80, $y, self::GOLD);
        $this->text($img, $fiesta->municipio->comarca->nombre.' · '.$fiesta->municipio->comarca->provincia, 'Inter-Regular', 30, 80, $y + 56, [200, 200, 215]);

        $this->footer($img, $fiesta, self::WHITE);

        return $img;
    }

    /** Senyera template: gold field with red bands and a framed photo. */
    private function senyera(Fiesta $fiesta, array $accent): GdImage
    {
        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($img, true);
        imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, $this->color($img, self::GOLD));

        // Four red bands, tilted, reinterpreting the senyera.
        $red = $this->color($img, $accent);
        for ($i = 0; $i < 4; $i++) {
            $x = -400 + $i * 230;
            imagefilledpolygon($img, [$x, 0, $x + 110, 0, $x + 110 + 900, self::HEIGHT, $x + 900, self::HEIGHT], $red);
        }
        CoverArt::verticalGradient($img, self::GOLD, self::GOLD, 1150, 1350, 127, 0);
        imagefilledrectangle($img, 0, 1350, self::WIDTH, self::HEIGHT, $this->color($img, self::GOLD));
        CoverArt::grain($img, 12);

        $this->brand($img, 80, 140, self::INK, $accent);
        $this->pill($img, $this->countdown($fiesta), self::WIDTH - 80, 92, self::INK, self::GOLD, true);

        // Framed photo with an offset "print" shadow.
        $photo = $this->background($fiesta, 880, 700);
        imagefilledrectangle($img, 120, 300, 120 + 880 + 24, 300 + 700 + 24, $this->color($img, self::INK));
        imagefilledrectangle($img, 88, 268, 88 + 880 + 24, 268 + 700 + 24, $this->color($img, self::WHITE));
        imagecopy($img, $photo, 100, 280, 0, 0, 880, 700);

        $lines = $this->wrap($fiesta->nombre, 'BricolageGrotesque-ExtraBold', 80, self::WIDTH - 160, 3);
        $y = max(1110, self::TEXT_BOTTOM - (count($lines) * 110 + 84));
        $this->text($img, mb_strtoupper($fiesta->municipio->nombre), 'Inter-SemiBold', 34, 80, $y, self::INK, 6);
        foreach ($lines as $line) {
            $y += 110;
            $this->text($img, $line, 'BricolageGrotesque-ExtraBold', 80, 80, $y, self::INK);
        }
        $y += 84;
        $this->text($img, $fiesta->rangoFechas().' '.$fiesta->fecha_inicio->year, 'Inter-SemiBold', 40, 80, $y, [150, 20, 35]);

        $this->footer($img, $fiesta, self::INK);

        return $img;
    }

    /** Minimal template: photo, giant countdown number, thin lines. */
    private function minimal(Fiesta $fiesta, array $accent): GdImage
    {
        $img = $this->background($fiesta, self::WIDTH, self::HEIGHT);
        imagefilledrectangle($img, 0, 0, self::WIDTH, self::HEIGHT, imagecolorallocatealpha($img, 0, 0, 0, 80));
        CoverArt::verticalGradient($img, [0, 0, 0], [0, 0, 0], 900, self::HEIGHT, 127, 10);

        $this->brand($img, 80, 140, self::WHITE, $accent);

        $dias = $fiesta->diasRestantes();
        $big = $fiesta->estaEnCurso() ? mb_strtoupper(__('app.story_now_short')) : (string) max(0, $dias);
        $this->text($img, $big, 'BricolageGrotesque-ExtraBold', 260, 70, 600, $accent);
        $label = $fiesta->estaEnCurso() ? __('app.story_happening') : trans_choice('app.story_days_to_go', $dias);
        $this->text($img, mb_strtoupper($label), 'Inter-SemiBold', 34, 84, 680, self::WHITE, 6);

        $lines = $this->wrap($fiesta->nombre, 'BricolageGrotesque-SemiBold', 80, self::WIDTH - 160, 3);
        $y = self::TEXT_BOTTOM - (count($lines) * 112 + 80);
        imagefilledrectangle($img, 80, $y, self::WIDTH - 80, $y + 2, $this->color($img, self::WHITE));
        foreach ($lines as $line) {
            $y += 112;
            $this->text($img, $line, 'BricolageGrotesque-SemiBold', 80, 80, $y, self::WHITE);
        }
        $y += 80;
        $this->text($img, $fiesta->municipio->nombre.'  ·  '.$fiesta->rangoFechas(), 'Inter-Regular', 38, 80, $y, [230, 230, 235]);

        $this->footer($img, $fiesta, self::WHITE);

        return $img;
    }

    /* -----------------------------------------------------------------
     | Building blocks
     | ----------------------------------------------------------------- */

    /** Cover-cropped festival photo, or generated artwork when there is none. */
    private function background(Fiesta $fiesta, int $w, int $h): GdImage
    {
        $disk = Storage::disk('uploads');
        if ($fiesta->imagen && $disk->exists($fiesta->imagen)) {
            try {
                $image = (new ImageManager(new Driver))->read($disk->path($fiesta->imagen))->cover($w, $h);

                return $image->core()->native();
            } catch (Throwable) {
                // Fall back to generated artwork below.
            }
        }

        return $this->art->make($fiesta->slug, $w, $h);
    }

    private function brand(GdImage $img, int $x, int $y, array $color, array $accent): void
    {
        $logo = Setting::get('logo');
        if ($logo && Storage::disk('uploads')->exists($logo)) {
            try {
                $mark = (new ImageManager(new Driver))->read(Storage::disk('uploads')->path($logo))->scale(height: 72)->core()->native();
                imagecopy($img, $mark, $x, $y - 60, 0, 0, imagesx($mark), imagesy($mark));

                return;
            } catch (Throwable) {
                // Text wordmark below.
            }
        }

        imagefilledellipse($img, $x + 14, $y - 13, 26, 26, $this->color($img, $accent));
        $this->text($img, 'FESTES MAJORS', 'Inter-SemiBold', 28, $x + 44, $y, $color, 5);
    }

    private function footer(GdImage $img, Fiesta $fiesta, array $color): void
    {
        $size = 210;
        $x = self::WIDTH - 80 - $size;
        $y = self::HEIGHT - 80 - $size;

        if ($qr = $this->qr(route('fiestas.show', $fiesta))) {
            imagefilledrectangle($img, $x - 12, $y - 12, $x + $size + 12, $y + $size + 12, $this->color($img, self::WHITE));
            imagecopyresized($img, $qr, $x, $y, 0, 0, $size, $size, imagesx($qr), imagesy($qr));
        }

        $this->text($img, __('app.story_more_info'), 'Inter-Regular', 28, 80, self::HEIGHT - 170, $color);
        $this->text($img, (string) Setting::get('short_url', parse_url(config('app.url'), PHP_URL_HOST)), 'Inter-SemiBold', 40, 80, self::HEIGHT - 110, $color);
    }

    private function qr(string $url): ?GdImage
    {
        try {
            $png = (new QRCode(new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
                'outputBase64' => false,
                'eccLevel' => EccLevel::M,
                'scale' => 6,
                'quietzoneSize' => 1,
            ])))->render($url);

            return imagecreatefromstring($png) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /** Right-aligned rounded pill (used for the countdown). */
    private function pill(GdImage $img, string $label, int $right, int $top, array $bg, array $fg, bool $alignRight = false): void
    {
        $label = mb_strtoupper($label);
        $font = 'Inter-SemiBold';
        $size = 28;
        $tracking = 3;
        $textW = $this->width($label, $font, $size, $tracking);
        $h = 76;
        $w = $textW + 64;
        $x = $alignRight ? $right - $w : $right;
        $color = $this->color($img, $bg);

        imagefilledrectangle($img, $x + $h / 2, $top, $x + $w - $h / 2, $top + $h, $color);
        imagefilledellipse($img, (int) ($x + $h / 2), (int) ($top + $h / 2), $h, $h, $color);
        imagefilledellipse($img, (int) ($x + $w - $h / 2), (int) ($top + $h / 2), $h, $h, $color);
        $this->text($img, $label, $font, $size, $x + 32, $top + 50, $fg, $tracking);
    }

    private function countdown(Fiesta $fiesta): string
    {
        return $fiesta->cuentaAtras();
    }

    /* -----------------------------------------------------------------
     | Typography helpers
     | ----------------------------------------------------------------- */

    private function font(string $name): string
    {
        return resource_path("fonts/{$name}.ttf");
    }

    private function text(GdImage $img, string $text, string $font, int $size, int $x, int $y, array $rgb, int $tracking = 0): void
    {
        $color = $this->color($img, $rgb);
        if ($tracking === 0) {
            imagettftext($img, $size, 0, $x, $y, $color, $this->font($font), $text);

            return;
        }

        foreach (mb_str_split($text) as $char) {
            imagettftext($img, $size, 0, $x, $y, $color, $this->font($font), $char);
            $x += $this->width($char, $font, $size) + $tracking;
        }
    }

    private function width(string $text, string $font, int $size, int $tracking = 0): int
    {
        $box = imagettfbbox($size, 0, $this->font($font), $text);

        return (int) (abs($box[2] - $box[0]) + max(0, mb_strlen($text) - 1) * $tracking);
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) as $word) {
            $try = $line === '' ? $word : "{$line} {$word}";
            if ($line !== '' && $this->width($try, $font, $size) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $try;
            }
        }
        $lines[] = $line;

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' ,.').'…';
        }

        return $lines;
    }

    private function color(GdImage $img, array $rgb, int $alpha = 0): int
    {
        return imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);
    }

    /** @return array{int,int,int} */
    public static function hex(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
