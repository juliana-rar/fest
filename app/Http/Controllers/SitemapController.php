<?php

namespace App\Http\Controllers;

use App\Models\Fiesta;
use App\Support\FiestaCache;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = FiestaCache::remember('sitemap', [], 3600, function () {
            $fiestas = Fiesta::query()->publicadas()->orderByDesc('fecha_inicio')->get(['slug', 'updated_at', 'fecha_fin']);

            return view('sitemap', compact('fiestas'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
