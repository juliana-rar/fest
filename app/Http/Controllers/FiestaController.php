<?php

namespace App\Http\Controllers;

use App\Models\Fiesta;
use App\Models\Setting;
use App\Services\IcsCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FiestaController extends Controller
{
    public function show(Request $request, Fiesta $fiesta): View
    {
        $this->ensureVisible($request, $fiesta);
        $fiesta->load(['municipio.comarca', 'actos', 'tipos']);

        // Count one visit per session and festival.
        $seen = $request->session()->get('fiestas_vistas', []);
        if (! in_array($fiesta->id, $seen, true) && $fiesta->publicada) {
            $fiesta->incrementarContador('visitas');
            $request->session()->push('fiestas_vistas', $fiesta->id);
        }

        $cercanas = Fiesta::query()
            ->publicadas()
            ->cercanas($fiesta->municipio->lat, $fiesta->municipio->lng, 40)
            ->whereDate('fiestas.fecha_fin', '>=', today())
            ->whereKeyNot($fiesta->id)
            ->with(['municipio.comarca', 'tipos'])
            ->limit(4)
            ->get();

        return view('fiestas.show', [
            'fiesta' => $fiesta,
            'programa' => $fiesta->actos->groupBy(fn ($a) => $a->dia->toDateString()),
            'cercanas' => $cercanas,
            'plantillas' => Setting::storyTemplates(),
            'plantillaDefecto' => Setting::defaultStoryTemplate(),
        ]);
    }

    public function ics(Request $request, Fiesta $fiesta, IcsCalendar $calendar): Response
    {
        $this->ensureVisible($request, $fiesta);

        return response($calendar->forFiesta($fiesta), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$fiesta->slug.'.ics"',
        ]);
    }

    /** Drafts are only visible to administrators (preview). */
    private function ensureVisible(Request $request, Fiesta $fiesta): void
    {
        abort_unless($fiesta->publicada || $request->user()?->isAdmin(), 404);
    }
}
