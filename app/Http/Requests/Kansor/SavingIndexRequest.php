<?php

namespace App\Http\Requests\Kansor;

use Illuminate\Foundation\Http\FormRequest;

class SavingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
    }
}


