<?php

namespace App\Http\Requests\PosKantin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActiveUser() && ($this->user()?->isAdmin() || $this->user()?->isPetugas());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sync_interval' => (int) preg_replace('/[^\d]/', '', (string) $this->input('sync_interval', '60')),
            'rows_per_page' => (int) preg_replace('/[^\d]/', '', (string) $this->input('rows_per_page', '10')),
            'offline_session_days' => (int) preg_replace('/[^\d]/', '', (string) $this->input('offline_session_days', (string) config('services.kansor.offline_login_days', 30))),
        ]);
    }

    public function rules(): array
    {
        return [
            'sync_interval' => ['required', 'integer', 'min:10', 'max:3600'],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'rows_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'offline_session_days' => ['required', 'integer', 'min:1', 'max:'.(int) config('services.kansor.offline_login_days_max', 30)],
        ];
    }
}

