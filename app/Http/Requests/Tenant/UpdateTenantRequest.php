<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant');

        return [
            'name'   => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255', "unique:tenants,domain,{$tenantId}"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'domain.unique' => 'Domain ini sudah digunakan.',
        ];
    }
}
