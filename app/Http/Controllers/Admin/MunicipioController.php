<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MunicipioRequest;
use App\Models\Comarca;
use App\Models\Municipio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Municipio::class);

        $q = trim((string) $request->query('q'));
        $comarca = (string) $request->query('comarca');

        $municipios = Municipio::query()
            ->with('comarca')
            ->withCount('fiestas')
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->when($comarca !== '', fn ($query) => $query->whereHas('comarca', fn ($c) => $c->where('slug', $comarca)))
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('admin.municipios.index', [
            'municipios' => $municipios,
            'comarcas' => Comarca::query()->orderBy('nombre')->get(),
            'q' => $q,
            'comarca' => $comarca,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Municipio::class);

        return view('admin.municipios.form', [
            'municipio' => new Municipio(['lat' => 41.8, 'lng' => 1.7]),
            'comarcas' => Comarca::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(MunicipioRequest $request): RedirectResponse
    {
        $municipio = Municipio::query()->create($request->validated());

        return redirect()->route('admin.municipios.index')->with('status', __('admin.saved', ['name' => $municipio->nombre]));
    }

    public function edit(Municipio $municipio): View
    {
        $this->authorize('update', $municipio);

        return view('admin.municipios.form', [
            'municipio' => $municipio,
            'comarcas' => Comarca::query()->orderBy('nombre')->get(),
        ]);
    }

    public function update(MunicipioRequest $request, Municipio $municipio): RedirectResponse
    {
        $municipio->update($request->validated());

        return redirect()->route('admin.municipios.index')->with('status', __('admin.saved', ['name' => $municipio->nombre]));
    }

    public function destroy(Municipio $municipio): RedirectResponse
    {
        if (! request()->user()->can('delete', $municipio)) {
            return back()->with('error', __('admin.municipio_has_fiestas'));
        }

        $municipio->delete();

        return redirect()->route('admin.municipios.index')->with('status', __('admin.deleted', ['name' => $municipio->nombre]));
    }
}
