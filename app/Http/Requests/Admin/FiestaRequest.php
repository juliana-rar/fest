<?php

namespace App\Http\Requests\Admin;

use App\Models\Fiesta;
use App\Services\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FiestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiesta = $this->route('fiesta');

        return $fiesta instanceof Fiesta
            ? $this->user()->can('update', $fiesta)
            : $this->user()->can('create', Fiesta::class);
    }

    protected function prepareForValidation(): void
    {
        $actos = collect($this->input('actos', []))
            ->filter(fn ($a) => is_array($a) && filled($a['titulo'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'slug' => $this->filled('slug') ? Str::slug($this->input('slug')) : null,
            'destacada' => $this->boolean('destacada'),
            'publicada' => $this->boolean('publicada'),
            'actos' => $actos,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fiesta = $this->route('fiesta');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('fiestas', 'slug')->ignore($fiesta?->id)],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:min_width=600,min_height=300'],
            'eliminar_imagen' => ['nullable', 'boolean'],
            'destacada' => ['boolean'],
            'publicada' => ['boolean'],
            'tipos' => ['array'],
            'tipos.*' => ['integer', 'distinct', 'exists:tipos,id'],
            'actos' => ['array', 'max:200'],
            'actos.*.dia' => ['required', 'date', 'after_or_equal:fecha_inicio', 'before_or_equal:fecha_fin'],
            'actos.*.hora' => ['nullable', 'date_format:H:i'],
            'actos.*.titulo' => ['required', 'string', 'max:255'],
            'actos.*.lugar' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'actos.*.dia' => __('admin.acto_dia'),
            'actos.*.hora' => __('admin.acto_hora'),
            'actos.*.titulo' => __('admin.acto_titulo'),
            'actos.*.lugar' => __('admin.acto_lugar'),
        ];
    }

    /** Validated festival attributes (without image and programme). */
    public function fiestaData(): array
    {
        $data = collect($this->validated())->only([
            'nombre', 'slug', 'municipio_id', 'fecha_inicio', 'fecha_fin', 'descripcion', 'destacada', 'publicada',
        ])->all();

        $data['descripcion'] = app(HtmlSanitizer::class)->clean($data['descripcion'] ?? '');

        return $data;
    }

    /** @return list<int> */
    public function tipos(): array
    {
        return array_map('intval', $this->validated('tipos', []));
    }

    /** @return list<array{dia: string, hora: ?string, titulo: string, lugar: ?string}> */
    public function actos(): array
    {
        return array_map(fn ($a) => [
            'dia' => $a['dia'],
            'hora' => $a['hora'] ?? null,
            'titulo' => $a['titulo'],
            'lugar' => $a['lugar'] ?? null,
        ], $this->validated('actos', []));
    }
}
