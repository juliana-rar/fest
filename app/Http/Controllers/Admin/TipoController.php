<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TipoRequest;
use App\Models\Tipo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TipoController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Tipo::class);

        return view('admin.tipos.index', [
            'tipos' => Tipo::query()->withCount('fiestas')->ordenados()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Tipo::class);

        return view('admin.tipos.form', ['tipo' => new Tipo(['color' => '#FFB703', 'orden' => Tipo::query()->max('orden') + 1])]);
    }

    public function store(TipoRequest $request): RedirectResponse
    {
        $tipo = Tipo::query()->create($request->validated());

        return redirect()->route('admin.tipos.index')->with('status', __('admin.saved', ['name' => $tipo->nombre]));
    }

    public function edit(Tipo $tipo): View
    {
        $this->authorize('update', $tipo);

        return view('admin.tipos.form', compact('tipo'));
    }

    public function update(TipoRequest $request, Tipo $tipo): RedirectResponse
    {
        $tipo->update($request->validated());

        return redirect()->route('admin.tipos.index')->with('status', __('admin.saved', ['name' => $tipo->nombre]));
    }

    public function destroy(Tipo $tipo): RedirectResponse
    {
        $this->authorize('delete', $tipo);
        $tipo->delete();

        return redirect()->route('admin.tipos.index')->with('status', __('admin.deleted', ['name' => $tipo->nombre]));
    }
}
