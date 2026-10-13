<?php

namespace App\Models;

use App\Support\FiestaCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Fiesta extends Model
{
    use HasFactory;

    /** Mean Earth radius in km, used by the Haversine formula. */
    public const EARTH_RADIUS_KM = 6371;

    /** Number of bundled SVG placeholder covers in public/img/covers. */
    private const PLACEHOLDER_COVERS = 6;

    protected $fillable = [
        'municipio_id', 'nombre', 'slug', 'descripcion', 'fecha_inicio', 'fecha_fin',
        'imagen', 'destacada', 'publicada', 'visitas', 'compartidos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'destacada' => 'boolean',
            'publicada' => 'boolean',
            'visitas' => 'integer',
            'compartidos' => 'integer',
            'distancia' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Fiesta $fiesta) {
            if (blank($fiesta->slug)) {
                $fiesta->slug = static::uniqueSlug($fiesta->nombre, $fiesta->fecha_inicio, $fiesta->id);
            }
        });

        static::saved(fn () => FiestaCache::flush());

        static::deleted(function (Fiesta $fiesta) {
            $fiesta->deleteImageIfUnused();
            FiestaCache::flush();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* -----------------------------------------------------------------
     | Relationships
     | ----------------------------------------------------------------- */

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function actos(): HasMany
    {
        return $this->hasMany(Acto::class)->orderBy('dia')->orderBy('hora');
    }

    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(Tipo::class)->orderBy('orden')->orderBy('nombre');
    }

    /* -----------------------------------------------------------------
     | Scopes
     | ----------------------------------------------------------------- */

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('fiestas.publicada', true);
    }

    /** Festivals that have not finished yet (ongoing or upcoming), soonest first. */
    public function scopeProximas(Builder $query): Builder
    {
        return $query->whereDate('fiestas.fecha_fin', '>=', today())
            ->orderBy('fiestas.fecha_inicio')
            ->orderBy('fiestas.nombre');
    }

    /** Festivals happening today. */
    public function scopeEnCurso(Builder $query): Builder
    {
        return $query->whereDate('fiestas.fecha_inicio', '<=', today())
            ->whereDate('fiestas.fecha_fin', '>=', today());
    }

    /**
     * Festivals within $km of a point, with a computed `distancia` column (km),
     * nearest first. Uses the Haversine great-circle formula in SQL, with a
     * bounding-box pre-filter so the (lat, lng) index can be used.
     */
    public function scopeCercanas(Builder $query, float $lat, float $lng, float $km = 50): Builder
    {
        $haversine = sprintf(
            '(%d * 2 * ASIN(SQRT(POWER(SIN(RADIANS(geo.lat - ?) / 2), 2) + COS(RADIANS(?)) * COS(RADIANS(geo.lat)) * POWER(SIN(RADIANS(geo.lng - ?) / 2), 2))))',
            self::EARTH_RADIUS_KM
        );

        $latDelta = $km / 111.045;
        $lngDelta = $km / max(0.01, 111.045 * cos(deg2rad($lat)));

        if (empty($query->getQuery()->columns)) {
            $query->select('fiestas.*');
        }

        return $query
            ->join('municipios as geo', 'geo.id', '=', 'fiestas.municipio_id')
            ->selectRaw("{$haversine} as distancia", [$lat, $lat, $lng])
            ->whereBetween('geo.lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('geo.lng', [$lng - $lngDelta, $lng + $lngDelta])
            ->whereRaw("{$haversine} <= ?", [$lat, $lat, $lng, $km])
            ->reorder('distancia')
            ->orderBy('fiestas.fecha_inicio');
    }

    /**
     * Festivals overlapping a month. $mes accepts 1–12 (with $any) or "YYYY-MM".
     */
    public function scopeDelMes(Builder $query, int|string $mes, ?int $any = null): Builder
    {
        [$start, $end] = static::monthBounds($mes, $any);

        return $query->whereDate('fiestas.fecha_inicio', '<=', $end)
            ->whereDate('fiestas.fecha_fin', '>=', $start);
    }

    public function scopeDeProvincia(Builder $query, string $provincia): Builder
    {
        return $query->whereHas('municipio.comarca', fn ($q) => $q->where('provincia', $provincia));
    }

    public function scopeDeComarca(Builder $query, string $comarcaSlug): Builder
    {
        return $query->whereHas('municipio.comarca', fn ($q) => $q->where('slug', $comarcaSlug));
    }

    /**
     * Festivals having at least one of the given types (OR semantics).
     *
     * @param  string|list<string>  $slugs
     */
    public function scopeDeTipo(Builder $query, string|array $slugs): Builder
    {
        return $query->whereHas('tipos', fn ($q) => $q->whereIn('tipos.slug', (array) $slugs));
    }

    /**
     * Apply the public filters shared by the calendar and the map.
     *
     * @param  array{mes?: ?string, any?: ?int, provincia?: ?string, comarca?: ?string, tipos?: list<string>}  $filters
     */
    public function scopeFiltrar(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['mes'] ?? null, fn ($q, $mes) => $q->delMes($mes, $filters['any'] ?? null))
            ->when(empty($filters['mes']), fn ($q) => $q->whereDate('fiestas.fecha_fin', '>=', today()))
            ->when($filters['provincia'] ?? null, fn ($q, $p) => $q->deProvincia($p))
            ->when($filters['comarca'] ?? null, fn ($q, $c) => $q->deComarca($c))
            ->when($filters['tipos'] ?? [], fn ($q, $t) => $q->deTipo($t));
    }

    /* -----------------------------------------------------------------
     | Helpers
     | ----------------------------------------------------------------- */

    /** @return array{0: Carbon, 1: Carbon} */
    public static function monthBounds(int|string $mes, ?int $any = null): array
    {
        if (is_string($mes) && preg_match('/^(\d{4})-(\d{1,2})$/', $mes, $m)) {
            [$any, $mes] = [(int) $m[1], (int) $m[2]];
        }

        $start = Carbon::create($any ?? today()->year, max(1, min(12, (int) $mes)), 1)->startOfDay();

        return [$start, $start->copy()->endOfMonth()];
    }

    public static function uniqueSlug(string $nombre, mixed $fechaInicio = null, ?int $ignoreId = null): string
    {
        $year = $fechaInicio ? Carbon::parse($fechaInicio)->year : today()->year;
        $base = Str::slug($nombre);
        if (! str_ends_with($base, (string) $year)) {
            $base .= '-'.$year;
        }

        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function estaEnCurso(): bool
    {
        return today()->between($this->fecha_inicio, $this->fecha_fin);
    }

    public function finalizada(): bool
    {
        return $this->fecha_fin->lt(today());
    }

    /** Whole days until the festival starts (0 when ongoing, negative when finished). */
    public function diasRestantes(): int
    {
        if ($this->estaEnCurso()) {
            return 0;
        }

        return (int) today()->diffInDays($this->fecha_inicio, false);
    }

    /** Human countdown: "Ara mateix", "Demà", "Falten 3 dies"… */
    public function cuentaAtras(): string
    {
        return match (true) {
            $this->estaEnCurso() => __('app.now'),
            $this->finalizada() => __('app.finished'),
            $this->diasRestantes() === 1 => __('app.tomorrow'),
            default => trans_choice('app.days_left', $this->diasRestantes(), ['count' => $this->diasRestantes()]),
        };
    }

    /** Localised date range: "15–21 d’agost", "28 d’agost – 2 de setembre". */
    public function rangoFechas(): string
    {
        $format = __('app.date_format');
        $start = $this->fecha_inicio->locale(app()->getLocale());
        $end = $this->fecha_fin->locale(app()->getLocale());

        if ($start->isSameDay($end)) {
            return $start->isoFormat($format);
        }

        if ($start->isSameMonth($end)) {
            return $start->day.'–'.$end->isoFormat($format);
        }

        return $start->isoFormat($format).' – '.$end->isoFormat($format);
    }

    public function imagenUrl(): string
    {
        return $this->imagen
            ? Storage::disk('uploads')->url($this->imagen)
            : asset('img/covers/cover-'.(($this->id ?? 0) % self::PLACEHOLDER_COVERS + 1).'.svg');
    }

    public function thumbUrl(): string
    {
        if ($this->imagen && Storage::disk('uploads')->exists(static::thumbPath($this->imagen))) {
            return Storage::disk('uploads')->url(static::thumbPath($this->imagen));
        }

        return $this->imagenUrl();
    }

    public static function thumbPath(string $path): string
    {
        return dirname($path).'/thumbs/'.basename($path);
    }

    public function descripcionTexto(int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags((string) $this->descripcion), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    public function incrementarContador(string $column): void
    {
        // Raw increment: does not touch updated_at nor flush the API cache.
        DB::table('fiestas')->where('id', $this->id)->increment($column);
    }

    /** Copy this festival (with its programme and image) to the following year. */
    public function duplicarAlAnySeguent(): Fiesta
    {
        $copy = $this->replicate(['slug', 'visitas', 'compartidos']);
        $copy->setRelations([]); // replicate() keeps the original's loaded relations
        $copy->fecha_inicio = $this->fecha_inicio->copy()->addYear();
        $copy->fecha_fin = $this->fecha_fin->copy()->addYear();
        $copy->nombre = $this->nombre;
        $copy->publicada = false;
        $copy->visitas = 0;
        $copy->compartidos = 0;
        $copy->slug = static::uniqueSlug(preg_replace('/\s*\d{4}$/', '', $this->nombre), $copy->fecha_inicio);

        $disk = Storage::disk('uploads');
        if ($this->imagen && $disk->exists($this->imagen)) {
            $newPath = dirname($this->imagen).'/'.Str::random(12).'.'.pathinfo($this->imagen, PATHINFO_EXTENSION);
            $disk->copy($this->imagen, $newPath);
            if ($disk->exists(static::thumbPath($this->imagen))) {
                $disk->copy(static::thumbPath($this->imagen), static::thumbPath($newPath));
            }
            $copy->imagen = $newPath;
        }

        $copy->save();
        $copy->tipos()->sync($this->tipos()->pluck('tipos.id'));

        foreach ($this->actos as $acto) {
            $copy->actos()->create([
                'dia' => $acto->dia->copy()->addYear(),
                'hora' => $acto->hora,
                'titulo' => $acto->titulo,
                'lugar' => $acto->lugar,
            ]);
        }

        return $copy->load(['actos', 'tipos']);
    }

    public function deleteImageIfUnused(?string $path = null): void
    {
        $path ??= $this->imagen;
        if (! $path || static::query()->where('imagen', $path)->whereKeyNot($this->id)->exists()) {
            return;
        }

        Storage::disk('uploads')->delete([$path, static::thumbPath($path)]);
    }
}
