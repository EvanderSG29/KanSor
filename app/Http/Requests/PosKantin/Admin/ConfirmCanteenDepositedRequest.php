<?php

namespace App\Http\Requests\PosKantin\Admin;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmCanteenDepositedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActiveUser();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid_amount' => (int) preg_replace('/[^\d-]/', '', (string) $this->input('paid_amount', '0')),
        ]);
    }

    public function rules(): array
    {
        return [
            'paid_at' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && CarbonImmutable::parse($value, 'Asia/Jakarta')->isAfter(now('Asia/Jakarta'))) {
                        $fail('Tanggal setoran tidak boleh di masa depan.');
                    }
                },
            ],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'taken_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
