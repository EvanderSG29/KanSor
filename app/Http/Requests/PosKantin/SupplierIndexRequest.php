<?php

namespace App\Http\Requests\PosKantin;

use Illuminate\Foundation\Http\FormRequest;

class SupplierIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'includeInactive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'includeInactive' => 'opsi pemasok nonaktif',
        ];
    }

    /**
     * @return array{includeInactive?: bool}
     */
    public function filters(): array
    {
        return array_filter([
            'includeInactive' => $this->boolean('includeInactive'),
        ], fn (mixed $value): bool => $value !== false);
    }
}
