<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'email_admin' => ['required', 'email', 'max:255', 'unique:users,email'],
            'domain'      => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Nama sekolah wajib diisi.',
            'email_admin.required' => 'Email admin sekolah wajib diisi.',
            'email_admin.unique'   => 'Email ini sudah digunakan oleh akun lain.',
            'domain.unique'        => 'Domain ini sudah digunakan oleh sekolah lain.',
        ];
    }
}
