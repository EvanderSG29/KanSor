<?php

namespace App\Http\Requests\PosKantin\Admin;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmSupplierPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActiveUser();
    }

    protected function prepareForValidation(): void
    {
        $paidAmount = $this->input('paid_amount');

        $this->merge([
            'paid_amount' => $paidAmount === null || $paidAmount === ''
                ? null
                : (int) preg_replace('/[^\d-]/', '', (string) $paidAmount),
        ]);
    }

    public function rules(): array
    {
        return [
            'paid_at' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && CarbonImmutable::parse($value, 'Asia/Jakarta')->isAfter(now('Asia/Jakarta'))) {
                        $fail('Tanggal pembayaran tidak boleh di masa depan.');
                    }
                },
            ],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'taken_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
