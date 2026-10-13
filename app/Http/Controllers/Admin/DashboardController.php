<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fiesta;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Fiesta::class);

        $delMes = Fiesta::query()->delMes(today()->format('Y-m'))->with('municipio')->orderBy('fecha_inicio')->get();

        return view('admin.dashboard', [
            'stats' => [
                'mes' => $delMes->count(),
                'en_curso' => Fiesta::query()->publicadas()->enCurso()->count(),
                'publicadas' => Fiesta::query()->publicadas()->count(),
                'borradores' => Fiesta::query()->where('publicada', false)->count(),
                'visitas' => (int) Fiesta::query()->sum('visitas'),
                'compartidos' => (int) Fiesta::query()->sum('compartidos'),
            ],
            'delMes' => $delMes,
            'masVistas' => Fiesta::query()->with('municipio')->orderByDesc('visitas')->limit(6)->get(),
            'masCompartidas' => Fiesta::query()->with('municipio')->orderByDesc('compartidos')->limit(6)->get(),
        ]);
    }
}
