<?php

namespace App\Services;

use App\Models\Fiesta;
use App\Models\Municipio;
use App\Models\Tipo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * CSV import/export of festivals. Rows are matched by slug on import
 * (existing → updated, new → created); municipalities are matched by slug or name.
 */
class FiestaCsv
{
    /** "tipos" holds type slugs separated by "|", e.g. "festa-major|barraques". */
    public const COLUMNS = ['nombre', 'slug', 'municipio', 'comarca', 'fecha_inicio', 'fecha_fin', 'descripcion', 'tipos', 'destacada', 'publicada', 'visitas', 'compartidos'];

    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so spreadsheets detect the encoding.
            fputcsv($out, self::COLUMNS, ';', '"', '');

            Fiesta::query()->with(['municipio.comarca', 'tipos'])->orderBy('fecha_inicio')->chunk(200, function ($fiestas) use ($out) {
                foreach ($fiestas as $f) {
                    fputcsv($out, [
                        $f->nombre, $f->slug, $f->municipio->slug, $f->municipio->comarca->nombre,
                        $f->fecha_inicio->toDateString(), $f->fecha_fin->toDateString(), $f->descripcion,
                        $f->tipos->pluck('slug')->implode('|'),
                        (int) $f->destacada, (int) $f->publicada, $f->visitas, $f->compartidos,
                    ], ';', '"', '');
                }
            });

            fclose($out);
        }, 'festes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{created: int, updated: int, errors: list<string>}
     */
    public function import(string $path): array
    {
        $report = ['created' => 0, 'updated' => 0, 'errors' => []];
        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, null, $delimiter, '"', '');
        if (! $header) {
            fclose($handle);
            $report['errors'][] = __('admin.csv_empty');

            return $report;
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);

        $line = 1;
        DB::transaction(function () use ($handle, $delimiter, $header, &$line, &$report) {
            while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null));

                try {
                    $this->importRow($data, $report);
                } catch (Throwable $e) {
                    $report['errors'][] = __('admin.csv_row_error', ['line' => $line, 'error' => $e->getMessage()]);
                }
            }
        });

        fclose($handle);

        return $report;
    }

    /** @param array<string, ?string> $data */
    private function importRow(array $data, array &$report): void
    {
        $validator = Validator::make($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);
        if ($validator->fails()) {
            throw new \RuntimeException($validator->errors()->first());
        }

        $municipio = Municipio::query()
            ->where('slug', trim($data['municipio']))
            ->orWhere('nombre', trim($data['municipio']))
            ->first();
        if (! $municipio) {
            throw new \RuntimeException(__('admin.csv_unknown_municipio', ['name' => $data['municipio']]));
        }

        $attributes = [
            'nombre' => trim($data['nombre']),
            'municipio_id' => $municipio->id,
            'fecha_inicio' => Carbon::parse($data['fecha_inicio']),
            'fecha_fin' => Carbon::parse($data['fecha_fin']),
            'descripcion' => $this->sanitizer->clean($data['descripcion'] ?? ''),
            'destacada' => filter_var($data['destacada'] ?? false, FILTER_VALIDATE_BOOL),
            'publicada' => filter_var($data['publicada'] ?? true, FILTER_VALIDATE_BOOL),
        ];

        $slug = trim((string) ($data['slug'] ?? ''));
        $fiesta = $slug !== '' ? Fiesta::query()->where('slug', $slug)->first() : null;

        if ($fiesta) {
            $fiesta->update($attributes);
            $report['updated']++;
        } else {
            $fiesta = Fiesta::query()->create($attributes + ['slug' => $slug ?: null]);
            $report['created']++;
        }

        // Only touch the types when the column is present in the file.
        if (array_key_exists('tipos', $data)) {
            $slugs = array_filter(array_map('trim', explode('|', (string) $data['tipos'])));
            $fiesta->tipos()->sync(Tipo::query()->whereIn('slug', $slugs)->pluck('id'));
        }
    }
}
