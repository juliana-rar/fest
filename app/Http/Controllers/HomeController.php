<?php

namespace App\Http\Controllers;

use App\Models\Comarca;
use App\Models\Fiesta;
use App\Models\Tipo;
use App\Support\FiestaCache;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Only the IDs are cached (the cache store never unserializes objects).
        $ids = FiestaCache::remember('home.proximas', ['day' => today()->toDateString()], 900, fn () => Fiesta::query()
            ->publicadas()
            ->proximas()
            ->limit(12)
            ->pluck('fiestas.id')
            ->all());

        $proximas = Fiesta::query()
            ->with(['municipio.comarca', 'tipos'])
            ->findMany($ids)
            ->sortBy(fn (Fiesta $f) => array_search($f->id, $ids, true))
            ->values();

        $comarcas = Comarca::query()->orderBy('nombre')->get(['nombre', 'slug', 'provincia', 'geojson_id']);
        $tipos = Tipo::query()->ordenados()->get();

        // Next 12 months for the month filter (value "YYYY-MM").
        $meses = collect(range(0, 11))->map(fn ($i) => today()->startOfMonth()->addMonths($i));

        return view('home', compact('proximas', 'comarcas', 'meses', 'tipos'));
    }
}
