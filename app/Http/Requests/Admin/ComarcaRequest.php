<?php

namespace App\Http\Requests\Admin;

use App\Models\Comarca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comarca = $this->route('comarca');

        return $comarca instanceof Comarca
            ? $this->user()->can('update', $comarca)
            : $this->user()->can('create', Comarca::class);
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
            'nombre' => ['required', 'string', 'max:255'],
            'provincia' => ['required', Rule::in(Comarca::PROVINCIAS)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('comarcas', 'slug')->ignore($this->route('comarca')?->id)],
            'geojson_id' => ['nullable', 'string', 'max:10'],
        ];
    }
}
