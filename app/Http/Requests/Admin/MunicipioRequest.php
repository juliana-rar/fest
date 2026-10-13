<?php

namespace App\Http\Requests\Admin;

use App\Models\Municipio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $municipio = $this->route('municipio');

        return $municipio instanceof Municipio
            ? $this->user()->can('update', $municipio)
            : $this->user()->can('create', Municipio::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('nombre', ''))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comarca_id' => ['required', 'integer', 'exists:comarcas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('municipios', 'slug')->ignore($this->route('municipio')?->id)],
            // Catalonia plus a generous margin.
            'lat' => ['required', 'numeric', 'between:40.4,42.95'],
            'lng' => ['required', 'numeric', 'between:0.1,3.4'],
        ];
    }
}
