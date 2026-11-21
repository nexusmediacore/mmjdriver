<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && $user->role->name === 'SUPERADMIN';
    }

    public function rules(): array
    {
        $rules = [
            'code' => 'required|string|max:10|unique:departments,code',
            'name' => 'required|string|max:100',
            'head_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];

        if ($this->route('department')) {
            $rules['code'] = 'required|string|max:10|unique:departments,code,'.$this->route('department')->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode departemen wajib diisi.',
            'name.required' => 'Nama departemen wajib diisi.',
        ];
    }
}
