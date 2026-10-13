<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FiestaRequest;
use App\Http\Requests\Admin\ImportFiestasRequest;
use App\Models\Comarca;
use App\Models\Fiesta;
use App\Models\Tipo;
use App\Services\FiestaCsv;
use App\Services\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FiestaController extends Controller
{
    public function __construct(private ImageStore $images) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Fiesta::class);

        $q = trim((string) $request->query('q'));
        $estado = (string) $request->query('estado');

        $fiestas = Fiesta::query()
            ->with(['municipio.comarca', 'tipos'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$q}%")
                ->orWhereHas('municipio', fn ($m) => $m->where('nombre', 'like', "%{$q}%"))))
            ->when($estado === 'publicadas', fn ($query) => $query->where('publicada', true))
            ->when($estado === 'borradores', fn ($query) => $query->where('publicada', false))
            ->when($estado === 'destacadas', fn ($query) => $query->where('destacada', true))
            ->when($estado === 'proximas', fn ($query) => $query->whereDate('fecha_fin', '>=', today()))
            ->when($estado === 'pasadas', fn ($query) => $query->whereDate('fecha_fin', '<', today()))
            ->orderBy($estado === 'pasadas' ? 'fecha_inicio' : 'fecha_fin', $estado === 'pasadas' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.fiestas.index', compact('fiestas', 'q', 'estado'));
    }

    public function create(): View
    {
        $this->authorize('create', Fiesta::class);

        $fiesta = new Fiesta([
            'publicada' => true,
            'fecha_inicio' => today()->addMonth(),
            'fecha_fin' => today()->addMonth()->addDays(3),
        ]);

        return view('admin.fiestas.form', ['fiesta' => $fiesta, 'comarcas' => $this->comarcas(), 'tipos' => $this->tipos()]);
    }

    public function store(FiestaRequest $request): RedirectResponse
    {
        $fiesta = DB::transaction(function () use ($request) {
            $fiesta = new Fiesta($request->fiestaData());
            if ($request->hasFile('imagen')) {
                $fiesta->imagen = $this->images->store($request->file('imagen'));
            }
            $fiesta->save();
            $fiesta->tipos()->sync($request->tipos());
            $fiesta->actos()->createMany($request->actos());

            return $fiesta;
        });

        return redirect()->route('admin.fiestas.edit', $fiesta)->with('status', __('admin.saved', ['name' => $fiesta->nombre]));
    }

    public function edit(Fiesta $fiesta): View
    {
        $this->authorize('update', $fiesta);
        $fiesta->load(['actos', 'tipos']);

        return view('admin.fiestas.form', ['fiesta' => $fiesta, 'comarcas' => $this->comarcas(), 'tipos' => $this->tipos()]);
    }

    public function update(FiestaRequest $request, Fiesta $fiesta): RedirectResponse
    {
        DB::transaction(function () use ($request, $fiesta) {
            $fiesta->fill($request->fiestaData());
            $oldImage = $fiesta->imagen;

            if ($request->hasFile('imagen')) {
                $fiesta->imagen = $this->images->store($request->file('imagen'));
            } elseif ($request->boolean('eliminar_imagen')) {
                $fiesta->imagen = null;
            }

            $fiesta->tipos()->sync($request->tipos());

            // Programme is replaced as a whole (repeater semantics).
            $fiesta->actos()->delete();
            $fiesta->actos()->createMany($request->actos());
            $fiesta->touch();
            $fiesta->save();

            if ($oldImage && $oldImage !== $fiesta->imagen) {
                $fiesta->deleteImageIfUnused($oldImage);
            }
        });

        return redirect()->route('admin.fiestas.edit', $fiesta)->with('status', __('admin.saved', ['name' => $fiesta->nombre]));
    }

    public function destroy(Fiesta $fiesta): RedirectResponse
    {
        $this->authorize('delete', $fiesta);
        $fiesta->delete();

        return redirect()->route('admin.fiestas.index')->with('status', __('admin.deleted', ['name' => $fiesta->nombre]));
    }

    public function duplicate(Fiesta $fiesta): RedirectResponse
    {
        $this->authorize('duplicate', $fiesta);
        $copy = DB::transaction(fn () => $fiesta->load('actos')->duplicarAlAnySeguent());

        return redirect()->route('admin.fiestas.edit', $copy)->with('status', __('admin.duplicated', ['year' => $copy->fecha_inicio->year]));
    }

    public function export(FiestaCsv $csv): StreamedResponse
    {
        $this->authorize('export', Fiesta::class);

        return $csv->export();
    }

    public function import(ImportFiestasRequest $request, FiestaCsv $csv): RedirectResponse
    {
        $report = $csv->import($request->file('csv')->getRealPath());

        return redirect()->route('admin.fiestas.index')
            ->with('status', __('admin.imported', ['created' => $report['created'], 'updated' => $report['updated']]))
            ->with('import_errors', $report['errors']);
    }

    private function tipos()
    {
        return Tipo::query()->ordenados()->get();
    }

    private function comarcas()
    {
        return Comarca::query()->with('municipios:id,comarca_id,nombre')->orderBy('nombre')->get();
    }
}
