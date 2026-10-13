<?php

namespace App\Http\Requests\Api;

use App\Models\Comarca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiestaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // "10" (with ?any=2026) or "2026-10".
            'mes' => ['nullable', 'regex:/^(\d{4}-(0?[1-9]|1[0-2])|0?[1-9]|1[0-2])$/'],
            'any' => ['nullable', 'integer', 'between:2000,2100'],
            'provincia' => ['nullable', Rule::in(Comarca::PROVINCIAS)],
            'comarca' => ['nullable', 'string', 'max:100', 'exists:comarcas,slug'],
            // One or more type slugs, comma separated: "festa-major,barraques".
            'tipo' => ['nullable', 'string', 'max:300', 'regex:/^[a-z0-9-]+(,[a-z0-9-]+)*$/'],
        ];
    }

    /**
     * @return array{mes: ?string, any: ?int, provincia: ?string, comarca: ?string, tipos: list<string>}
     */
    public function filters(): array
    {
        return [
            'mes' => $this->validated('mes'),
            'any' => $this->validated('any') ? (int) $this->validated('any') : null,
            'provincia' => $this->validated('provincia'),
            'comarca' => $this->validated('comarca'),
            'tipos' => array_values(array_unique(array_filter(explode(',', (string) $this->validated('tipo'))))),
        ];
    }
}
