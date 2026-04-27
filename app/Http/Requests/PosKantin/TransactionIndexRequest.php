<?php

namespace App\Http\Requests\PosKantin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'transactionDate' => ['nullable', Rule::date()->format('Y-m-d')],
            'startDate' => ['nullable', Rule::date()->format('Y-m-d')],
            'endDate' => ['nullable', Rule::date()->format('Y-m-d'), 'after_or_equal:startDate'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'supplierId' => ['nullable', 'string', 'max:100'],
            'commissionBaseType' => ['nullable', 'in:revenue,profit'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transactionDate' => 'tanggal transaksi',
            'startDate' => 'tanggal mulai',
            'endDate' => 'tanggal akhir',
            'pageSize' => 'jumlah data per halaman',
            'commissionBaseType' => 'basis komisi',
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function filters(): array
    {
        return array_filter([
            'transactionDate' => $this->string('transactionDate')->toString(),
            'startDate' => $this->string('startDate')->toString(),
            'endDate' => $this->string('endDate')->toString(),
            'search' => $this->string('search')->trim()->toString(),
            'page' => $this->integer('page'),
            'pageSize' => $this->integer('pageSize') ?: 10,
            'supplierId' => $this->string('supplierId')->toString(),
            'commissionBaseType' => $this->string('commissionBaseType')->toString(),
        ], fn (mixed $value): bool => ! in_array($value, ['', 0, null], true));
    }
}
