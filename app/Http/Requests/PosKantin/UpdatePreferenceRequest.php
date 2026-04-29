<?php

namespace App\Http\Requests\PosKantin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActiveUser() && ($this->user()?->isAdmin() || $this->user()?->isPetugas());
    }

    public function rules(): array
    {
        return [
            'key' => ['required', Rule::in(['sync_interval', 'theme', 'rows_per_page'])],
            'value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
