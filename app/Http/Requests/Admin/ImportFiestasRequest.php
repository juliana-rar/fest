<?php

namespace App\Http\Requests\Admin;

use App\Models\Fiesta;
use Illuminate\Foundation\Http\FormRequest;

class ImportFiestasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', Fiesta::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
