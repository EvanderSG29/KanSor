<?php

namespace App\Http\Requests\PosKantin\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActiveUser();
    }

    protected function prepareForValidation(): void
    {
        $defaultPrice = $this->input('default_price');

        $this->merge([
            'active' => $this->boolean('active'),
            'default_price' => $defaultPrice === null || $defaultPrice === ''
                ? null
                : (int) preg_replace('/[^\d-]/', '', (string) $defaultPrice),
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'default_price' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
