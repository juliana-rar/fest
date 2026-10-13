<?php

namespace App\Http\Requests\Admin;

use App\Models\Tipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tipo = $this->route('tipo');

        return $tipo instanceof Tipo
            ? $this->user()->can('update', $tipo)
            : $this->user()->can('create', Tipo::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('nombre', '')),
            'orden' => $this->filled('orden') ? $this->input('orden') : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60'],
            'nombre_es' => ['nullable', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tipos', 'slug')->ignore($this->route('tipo')?->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'orden' => ['integer', 'between:0,999'],
        ];
    }
}
