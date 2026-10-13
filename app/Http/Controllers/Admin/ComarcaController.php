<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComarcaRequest;
use App\Models\Comarca;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComarcaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Comarca::class);

        $provincia = (string) $request->query('provincia');

        $comarcas = Comarca::query()
            ->withCount('municipios')
            ->when(in_array($provincia, Comarca::PROVINCIAS, true), fn ($q) => $q->where('provincia', $provincia))
            ->orderBy('nombre')
            ->paginate(50)
            ->withQueryString();

        return view('admin.comarcas.index', compact('comarcas', 'provincia'));
    }

    public function create(): View
    {
        $this->authorize('create', Comarca::class);

        return view('admin.comarcas.form', ['comarca' => new Comarca]);
    }

    public function store(ComarcaRequest $request): RedirectResponse
    {
        $comarca = Comarca::query()->create($request->validated());

        return redirect()->route('admin.comarcas.index')->with('status', __('admin.saved', ['name' => $comarca->nombre]));
    }

    public function edit(Comarca $comarca): View
    {
        $this->authorize('update', $comarca);

        return view('admin.comarcas.form', compact('comarca'));
    }

    public function update(ComarcaRequest $request, Comarca $comarca): RedirectResponse
    {
        $comarca->update($request->validated());

        return redirect()->route('admin.comarcas.index')->with('status', __('admin.saved', ['name' => $comarca->nombre]));
    }

    public function destroy(Comarca $comarca): RedirectResponse
    {
        if (! request()->user()->can('delete', $comarca)) {
            return back()->with('error', __('admin.comarca_has_municipios'));
        }

        $comarca->delete();

        return redirect()->route('admin.comarcas.index')->with('status', __('admin.deleted', ['name' => $comarca->nombre]));
    }
}
