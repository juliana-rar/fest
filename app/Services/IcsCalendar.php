<?php

namespace App\Services;

use App\Models\Fiesta;

/**
 * Minimal RFC 5545 iCalendar export (one all-day VEVENT per festival).
 */
class IcsCalendar
{
    public function forFiesta(Fiesta $fiesta): string
    {
        $fiesta->loadMissing('municipio.comarca');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Festes Majors de Catalunya//CA',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$fiesta->slug.'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'festesmajors'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$fiesta->fecha_inicio->format('Ymd'),
            // DTEND is exclusive for all-day events.
            'DTEND;VALUE=DATE:'.$fiesta->fecha_fin->copy()->addDay()->format('Ymd'),
            'SUMMARY:'.$this->escape($fiesta->nombre),
            'LOCATION:'.$this->escape($fiesta->municipio->nombre.', '.$fiesta->municipio->comarca->nombre.', Catalunya'),
            'GEO:'.$fiesta->municipio->lat.';'.$fiesta->municipio->lng,
            'DESCRIPTION:'.$this->escape($fiesta->descripcionTexto(600)."\n\n".route('fiestas.show', $fiesta)),
            'URL:'.route('fiestas.show', $fiesta),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n";
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Fold lines longer than 75 octets, as required by RFC 5545. */
    private function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            // Never split a multibyte UTF-8 sequence.
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
