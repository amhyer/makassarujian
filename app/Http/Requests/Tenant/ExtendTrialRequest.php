<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ExtendTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.required' => 'Jumlah hari wajib diisi.',
            'days.min'      => 'Minimal perpanjangan adalah 1 hari.',
            'days.max'      => 'Maksimal perpanjangan adalah 365 hari.',
        ];
    }
}
