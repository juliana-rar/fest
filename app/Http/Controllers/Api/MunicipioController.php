<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fiesta;
use App\Models\Municipio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    /**
     * GET /api/municipios?q= — autocomplete for the hero search.
     * The unicode collation makes "gracia" match "Gràcia" and "lleida" match "Lleida".
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->validate(['q' => ['required', 'string', 'min:2', 'max:60']])['q']);
        $like = '%'.addcslashes($q, '%_\\').'%';

        $municipios = Municipio::query()
            ->with('comarca')
            ->where('nombre', 'like', $like)
            ->orWhereHas('fiestas', fn ($f) => $f->publicadas()->where('nombre', 'like', $like))
            ->orderByRaw('nombre like ? desc', [addcslashes($q, '%_\\').'%'])
            ->orderBy('nombre')
            ->limit(8)
            ->get();

        $next = Fiesta::query()
            ->publicadas()
            ->proximas()
            ->whereIn('municipio_id', $municipios->pluck('id'))
            ->get()
            ->groupBy('municipio_id');

        return response()->json([
            'data' => $municipios->map(function (Municipio $m) use ($next) {
                $fiesta = $next->get($m->id)?->first();

                return [
                    'nombre' => $m->nombre,
                    'slug' => $m->slug,
                    'comarca' => $m->comarca->nombre,
                    'lat' => $m->lat,
                    'lng' => $m->lng,
                    'fiesta' => $fiesta ? [
                        'nombre' => $fiesta->nombre,
                        'url' => route('fiestas.show', $fiesta),
                        'rango' => $fiesta->rangoFechas(),
                    ] : null,
                ];
            }),
        ]);
    }
}
