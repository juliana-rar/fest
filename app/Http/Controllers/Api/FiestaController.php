<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FiestaIndexRequest;
use App\Http\Resources\FiestaResource;
use App\Models\Fiesta;
use App\Support\FiestaCache;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiestaController extends Controller
{
    /** Cache lifetime for list endpoints (also invalidated on every admin edit). */
    private const TTL = 900;

    /**
     * GET /api/fiestas?mes=&any=&provincia=&comarca=
     * Feeds both the calendar and the map. Without `mes` it returns every
     * festival that has not finished yet.
     */
    public function index(FiestaIndexRequest $request): JsonResponse
    {
        $filters = $request->filters();

        return $this->cachedCollection($request, 'index', $filters, fn () => Fiesta::query()
            ->publicadas()
            ->filtrar($filters)
            ->with(['municipio.comarca', 'tipos'])
            ->orderBy('fecha_inicio')
            ->limit(500)
            ->get());
    }

    /** GET /api/fiestas/proximas?limit= */
    public function proximas(Request $request): JsonResponse
    {
        $data = $request->validate(['limit' => ['nullable', 'integer', 'between:1,50']]);
        $limit = (int) ($data['limit'] ?? 12);

        return $this->cachedCollection($request, 'proximas', ['limit' => $limit], fn () => Fiesta::query()
            ->publicadas()
            ->proximas()
            ->with(['municipio.comarca', 'tipos'])
            ->limit($limit)
            ->get());
    }

    /** GET /api/fiestas/cercanas?lat=&lng=&radio= (Haversine, km) */
    public function cercanas(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radio' => ['nullable', 'numeric', 'between:1,300'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        // Rounding to ~100 m keeps the cache useful without losing precision.
        $lat = round((float) $data['lat'], 3);
        $lng = round((float) $data['lng'], 3);
        $radio = (float) ($data['radio'] ?? 50);
        $limit = (int) ($data['limit'] ?? 12);

        return $this->cachedCollection($request, 'cercanas', compact('lat', 'lng', 'radio', 'limit'), fn () => Fiesta::query()
            ->publicadas()
            ->cercanas($lat, $lng, $radio)
            ->whereDate('fiestas.fecha_fin', '>=', today())
            ->with(['municipio.comarca', 'tipos'])
            ->limit($limit)
            ->get());
    }

    /** POST /api/fiestas/{slug}/share — counts Story shares/downloads. */
    public function share(Fiesta $fiesta): JsonResponse
    {
        abort_unless($fiesta->publicada, 404);
        $fiesta->incrementarContador('compartidos');

        return response()->json(['compartidos' => $fiesta->compartidos + 1]);
    }

    /**
     * Caches the resolved resource arrays (never Eloquent objects: the cache
     * only unserializes plain data). Keyed by day and locale because the
     * countdown and the formatted dates depend on both.
     *
     * @param  array<string, mixed>  $params
     */
    private function cachedCollection(Request $request, string $name, array $params, Closure $query): JsonResponse
    {
        $params += ['day' => today()->toDateString(), 'locale' => app()->getLocale()];

        $data = FiestaCache::remember($name, $params, self::TTL, fn () => FiestaResource::collection($query())->resolve($request));

        return response()->json(['data' => $data]);
    }
}
